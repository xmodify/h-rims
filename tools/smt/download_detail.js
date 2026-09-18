const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const batchNo = process.argv[2];
const roundNo = process.argv[3];
const transferDate = process.argv[4] || ''; // format YYYY-MM-DD
const accountCode = process.argv[5] || '';
const hcode = process.argv[6] || '10989';

if (!batchNo || !roundNo) {
    console.error(JSON.stringify({ status: 'error', message: 'Missing batchNo or roundNo' }));
    process.exit(1);
}

(async () => {
    try {
        // Calculate Buddhist posting date e.g. 2026-09-15 -> 25690915
        let postingDate = '';
        if (transferDate) {
            const parts = transferDate.split('-');
            if (parts.length === 3) {
                const bYear = parseInt(parts[0], 10) + 543;
                postingDate = `${bYear}${parts[1]}${parts[2]}`;
            }
        }
        if (!postingDate) {
            // Default to current year
            const now = new Date();
            const bYear = now.getFullYear() + 543;
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            postingDate = `${bYear}${mm}${dd}`;
        }

        // 1. Read clean cookies
        const cookieFile = path.join(__dirname, '../../scratch/cookies_for_playwright.json');
        if (!fs.existsSync(cookieFile)) {
            console.error(JSON.stringify({ status: 'error', message: 'Cookie session file not found' }));
            process.exit(1);
        }

        const rawCookies = JSON.parse(fs.readFileSync(cookieFile, 'utf-8'));
        const cleanCookies = rawCookies.filter(c => c.name !== 'ACCESS_TOKEN' && c.name.length < 100);

        const browser = await chromium.launch({
            headless: true,
            args: [
                '--disable-blink-features=AutomationControlled',
                '--no-sandbox',
                '--disable-setuid-sandbox',
            ]
        });

        const context = await browser.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            viewport: { width: 1400, height: 900 },
            acceptDownloads: true
        });

        await context.addCookies(cleanCookies);
        const page = await context.newPage();

        await page.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });

        // 2. SSO Login
        await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 30000 });
        const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
        if (loginBtn) {
            await loginBtn.click();
            await page.waitForTimeout(6000);
        }

        // 3. Construct Detail URL
        let detailUrl = '';
        let recId = '';
        let fromSystem = '';
        let sfundCd = '13';
        let efundCd = '1';

        try {
            const apiRes = await context.request.post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', {
                data: {
                    fiscalYear: postingDate.substring(0, 4) || '2569',
                    vendorId: '0000010989',
                    postingDate: postingDate,
                    batchNo: batchNo,
                    offset: 0,
                    count: 50,
                    isTest: ''
                }
            });
            if (apiRes.ok()) {
                const apiData = await apiRes.json();
                const matched = (apiData.datas || []).find(d => String(d.batchNo) === String(batchNo) || d.refDocNo === roundNo);
                if (matched) {
                    if (matched.recId) recId = String(matched.recId);
                    if (matched.fromSystem) fromSystem = String(matched.fromSystem).toUpperCase();
                    if (matched.sfundCd) sfundCd = String(matched.sfundCd);
                    if (matched.efundCd) efundCd = String(matched.efundCd);
                }
            }
        } catch (e) {}

        const accEncoded = encodeURIComponent(accountCode);
        const roundEncoded = encodeURIComponent(roundNo);
        if (fromSystem === 'LGO-HD' || roundNo.includes('LGO-HD') || roundNo.includes('LGOHD') || roundNo.startsWith('HD-')) {
            const rId = recId || '72668';
            detailUrl = `https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/${roundEncoded}/${hcode}/${rId}?mophId=${accEncoded}`;
        } else if (fromSystem === 'E-CLAIM-D1' || roundNo.includes('_IP') || roundNo.includes('_OP')) {
            const rId = recId || '72783';
            detailUrl = `https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-eclaim-d1/${roundEncoded}/${rId}/${hcode}/${batchNo}/${postingDate}?mophId=${accEncoded}`;
        } else {
            // Default to DMIS
            detailUrl = `https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/${roundEncoded}/${hcode}/${postingDate}/${batchNo}/${sfundCd}/${efundCd}?mophId=${accEncoded}`;
        }

        await page.goto(detailUrl, { waitUntil: 'networkidle', timeout: 45000 }).catch(() => {});
        await page.waitForTimeout(4000);

        // 4. Wait for Export Excel Button
        const exportBtn = await page.waitForSelector('mat-chip:has-text("Export Excel"), button:has-text("Export Excel"), :text("Export Excel")', { timeout: 20000 }).catch(() => null);

        if (!exportBtn) {
            await browser.close();
            console.error(JSON.stringify({ status: 'error', message: 'Export Excel button not found or unauthorized' }));
            process.exit(1);
        }

        // 5. Download File
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 60000 }),
            exportBtn.click(),
        ]);

        const tempDir = path.join(__dirname, '../../storage/app/temp');
        if (!fs.existsSync(tempDir)) {
            fs.mkdirSync(tempDir, { recursive: true });
        }

        const dPath = path.join(tempDir, `${roundNo}_${Date.now()}.xlsx`);
        await download.saveAs(dPath);

        await browser.close();

        // 6. Parse and Insert into DB
        const phpParser = path.join(__dirname, 'parse_detail_excel.php');
        const cmd = `php "${phpParser}" "${dPath}" "${roundNo}" "${batchNo}"`;
        const resultOutput = execSync(cmd, { encoding: 'utf-8' });

        // 7. Zero Server File Retention: delete downloaded file
        if (fs.existsSync(dPath)) {
            fs.unlinkSync(dPath);
        }

        // Print final JSON result
        console.log(resultOutput.trim());
    } catch (err) {
        console.error(JSON.stringify({ status: 'error', message: err.message }));
        process.exit(1);
    }
})();
