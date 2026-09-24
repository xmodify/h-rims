const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const batchNo = process.argv[2];
const roundNo = process.argv[3];
const transferDate = process.argv[4] || ''; // format YYYY-MM-DD
const accountCode = process.argv[5] || '';
const hcode = (process.argv[6] || '').trim();
const cookieFile = process.argv[7] || '';
const bearerToken = (process.argv[8] || '').trim();

if (!batchNo || !roundNo) {
    console.error(JSON.stringify({ status: 'error', message: 'กรุณาระบุ BatchNo และ RoundNo สำหรับดาวน์โหลด' }));
    process.exit(1);
}

if (!hcode) {
    console.error(JSON.stringify({ 
        status: 'error', 
        message: 'ไม่พบรหัสสถานพยาบาล (Hospital Code) ของหน่วยบริการนี้ กรุณาตั้งค่ารหัส รพ. ในระบบก่อนทำรายการ' 
    }));
    process.exit(1);
}

function findChromiumExecutable() {
    try {
        const dir = path.resolve(__dirname, '../../storage/app/playwright_browsers');
        if (fs.existsSync(dir)) {
            const rec = (d, target) => {
                for (const f of fs.readdirSync(d)) {
                    const fp = path.join(d, f);
                    if (fs.statSync(fp).isDirectory()) {
                        const r = rec(fp, target);
                        if (r) return r;
                    } else if (f === target) {
                        return fp;
                    }
                }
                return null;
            };
            const hTarget = process.platform === 'win32' ? 'chrome-headless-shell.exe' : 'chrome-headless-shell';
            const cTarget = process.platform === 'win32' ? 'chrome.exe' : 'chrome';
            const fH = rec(dir, hTarget);
            if (fH) {
                if (process.platform !== 'win32') {
                    try { fs.chmodSync(fH, 0o755); } catch(e) {}
                }
                return fH;
            }
            const fC = rec(dir, cTarget);
            if (fC) {
                if (process.platform !== 'win32') {
                    try { fs.chmodSync(fC, 0o755); } catch(e) {}
                }
                return fC;
            }
        }
    } catch(e) {}
    try {
        const def = chromium.executablePath();
        if (def && fs.existsSync(def)) return def;
    } catch(e) {}
    const sysList = process.platform === 'win32' ? [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'
    ] : [
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser'
    ];
    for (const s of sysList) {
        if (fs.existsSync(s)) return s;
    }
    return null;
}

async function launchBrowser(options) {
    const exe = findChromiumExecutable();
    const opts = { ...options };
    if (exe) {
        opts.executablePath = exe;
    }
    return await chromium.launch(opts);
}

// Helper to find export button with comprehensive selectors
async function findExportExcelButton(page, timeoutMs = 12000) {
    const exportSelectors = [
        'mat-chip:has-text("Export Excel")',
        'button:has-text("Export Excel")',
        'a:has-text("Export Excel")',
        ':text("Export Excel")',
        'button:has-text("Export")',
        'button:has-text("ส่งออก")',
        'button:has-text("Excel")',
        'button:has-text("ดาวน์โหลด")',
        'button:has(mat-icon:has-text("file_download"))',
        'button:has(mat-icon:has-text("download"))',
        'button:has(mat-icon:has-text("cloud_download"))',
        'button:has(mat-icon:has-text("get_app"))',
        'mat-icon:has-text("file_download")',
        'mat-icon:has-text("download")',
        'mat-icon:has-text("get_app")',
        '[title*="Export" i]',
        '[title*="Excel" i]',
        '[title*="ดาวน์โหลด" i]',
        '[aria-label*="Export" i]',
        '[aria-label*="Excel" i]',
        'a:has-text("Excel")',
        '.btn-excel',
        '.export-excel'
    ];

    const startTime = Date.now();
    while (Date.now() - startTime < timeoutMs) {
        // Dismiss any blocking dialogs/announcements if present
        try {
            const dismissBtn = await page.$('button:has-text("เข้าสู่เว็บไซต์"), mat-dialog-container button:has-text("ตกลง"), mat-dialog-container button:has-text("ปิด"), mat-dialog-container button:has-text("OK")');
            if (dismissBtn && await dismissBtn.isVisible()) {
                await dismissBtn.click().catch(() => {});
                await page.waitForTimeout(500);
            }
        } catch (e) {}

        // Check if any export button is visible
        for (const sel of exportSelectors) {
            try {
                const el = await page.$(sel);
                if (el && await el.isVisible()) {
                    return el;
                }
            } catch (e) {}
        }

        await page.waitForTimeout(1000);
    }
    return null;
}

(async () => {
    let browser;
    try {
        // Calculate Buddhist posting date e.g. 2026-09-15 -> 25690915 & fiscalYear
        let postingDate = '';
        let fiscalYear = '';
        if (transferDate) {
            const parts = transferDate.split('-');
            if (parts.length === 3) {
                let y = parseInt(parts[0], 10);
                if (y < 2400) y += 543;
                const m = parseInt(parts[1], 10);
                postingDate = `${y}${parts[1].padStart(2, '0')}${parts[2].padStart(2, '0')}`;
                fiscalYear = String(m >= 10 ? y + 1 : y);
            }
        }
        if (!postingDate) {
            const now = new Date();
            let bYear = now.getFullYear() + 543;
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            postingDate = `${bYear}${mm}${dd}`;
            fiscalYear = String((now.getMonth() + 1) >= 10 ? bYear + 1 : bYear);
        }

        const vendorId5Digit = hcode.replace(/\D/g, '');
        const vendorId10 = vendorId5Digit.padStart(10, '0');

        // 1. Read clean cookies & resolve Bearer Token
        let actualCookieFile = cookieFile;
        if (!actualCookieFile || !fs.existsSync(actualCookieFile)) {
            actualCookieFile = path.join(__dirname, '../../storage/app/cookies_for_playwright.json');
        }

        let cleanCookies = [];
        let resolvedToken = bearerToken;

        if (fs.existsSync(actualCookieFile)) {
            try {
                const rawCookies = JSON.parse(fs.readFileSync(actualCookieFile, 'utf-8'));
                // Filter out massive ACCESS_TOKEN and corrupt quoted session cookies to prevent F5 WAF 400 Bad Request
                cleanCookies = rawCookies.filter(c => 
                    c.name !== 'ACCESS_TOKEN' && 
                    c.name !== 'KEYCLOAK_IDENTITY' &&
                    !String(c.value).includes('"')
                ).map(c => {
                    return {
                        name: c.name,
                        value: c.value,
                        domain: c.domain || '.nhso.go.th',
                        path: c.path || '/'
                    };
                });
                if (!resolvedToken) {
                    const tokCookie = rawCookies.find(c => c.name === 'ACCESS_TOKEN') || rawCookies.find(c => c.name === 'KEYCLOAK_IDENTITY');
                    if (tokCookie && tokCookie.value) {
                        resolvedToken = tokCookie.value;
                    }
                }
            } catch (e) {}
        }

        // Decode JWT payload for user profile injection and check expiry
        let jwtPayload = {};
        if (resolvedToken) {
            try {
                jwtPayload = JSON.parse(Buffer.from(resolvedToken.split('.')[1], 'base64').toString('utf-8'));
                if (jwtPayload.exp && (Date.now() / 1000) > jwtPayload.exp) {
                    console.error(JSON.stringify({ 
                        status: 'error', 
                        message: 'Session ThaiD หมดอายุแล้ว (อายุ Session 30 นาที) กรุณากดเชื่อมต่อ ThaiD ใหม่อีกครั้ง' 
                    }));
                    process.exit(1);
                }
            } catch (e) {}
        }

        browser = await launchBrowser({
            headless: true,
            args: [
                '--disable-blink-features=AutomationControlled',
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--ignore-certificate-errors',
                '--allow-running-insecure-content',
                '--ignore-ssl-errors',
            ]
        });

        const context = await browser.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            viewport: { width: 1400, height: 900 },
            acceptDownloads: true,
            ignoreHTTPSErrors: true
        });

        if (cleanCookies.length > 0) {
            await context.addCookies(cleanCookies);
        }

        const page = await context.newPage();

        // Inject complete ngx-webstorage Angular credentials
        await page.addInitScript(({ tok, p }) => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
            if (tok) {
                try {
                    const jsonTok = JSON.stringify(tok);
                    localStorage.setItem('ngx-webstorage|token', jsonTok);
                    localStorage.setItem('ngx-webstorage|username', JSON.stringify(p.preferred_username || p.username || ''));
                    localStorage.setItem('ngx-webstorage|firstname', JSON.stringify(p.given_name || p.name || ''));
                    localStorage.setItem('ngx-webstorage|lastname', JSON.stringify(p.family_name || ''));
                    localStorage.setItem('ngx-webstorage|userId', JSON.stringify(p.userId || 1));
                    localStorage.setItem('ngx-webstorage|userType', JSON.stringify('H'));
                    localStorage.setItem('ngx-webstorage|loginProvider', JSON.stringify('CREDENTIAL'));
                    localStorage.setItem('ngx-webstorage|fullnameTh', JSON.stringify(p.nameTh || p.name || ''));

                    localStorage.setItem('access_token', tok);
                    localStorage.setItem('token', tok);
                    sessionStorage.setItem('access_token', tok);
                    sessionStorage.setItem('token', tok);
                } catch(e) {}
            }
        }, { tok: resolvedToken, p: jwtPayload });

        // 2. SSO Login Check
        await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'domcontentloaded', timeout: 30000 });
        const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 4000 }).catch(() => null);
        if (loginBtn) {
            await loginBtn.click();
            await page.waitForTimeout(3000);
        }

        // 3. Resolve Detail Parameters via SMT API
        let recId = '';
        let fromSystem = '';
        let sfundCd = '13';
        let efundCd = '1';
        let batchMophId = accountCode || '';

        const reqHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json, text/plain, */*',
            'Origin': 'https://smt.nhso.go.th',
            'Referer': 'https://smt.nhso.go.th/smtf/'
        };
        if (resolvedToken) {
            reqHeaders['Authorization'] = 'Bearer ' + resolvedToken;
        }

        // Search API with postingDate
        try {
            const apiRes = await context.request.post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', {
                ignoreHTTPSErrors: true,
                headers: reqHeaders,
                data: {
                    fiscalYear: fiscalYear,
                    vendorId: vendorId10,
                    postingDate: postingDate,
                    batchNo: batchNo,
                    offset: 0,
                    count: 100,
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
                    if (matched.mophId) batchMophId = String(matched.mophId);
                }
            }
        } catch (e) {}

        // Fallback search without postingDate if not matched
        if (!recId) {
            try {
                const apiRes2 = await context.request.post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', {
                    ignoreHTTPSErrors: true,
                    headers: reqHeaders,
                    data: {
                        fiscalYear: fiscalYear,
                        vendorId: vendorId10,
                        postingDate: '',
                        batchNo: batchNo,
                        offset: 0,
                        count: 100,
                        isTest: ''
                    }
                });
                if (apiRes2.ok()) {
                    const apiData2 = await apiRes2.json();
                    const matched2 = (apiData2.datas || []).find(d => String(d.batchNo) === String(batchNo) || d.refDocNo === roundNo);
                    if (matched2) {
                        if (matched2.recId) recId = String(matched2.recId);
                        if (matched2.fromSystem) fromSystem = String(matched2.fromSystem).toUpperCase();
                        if (matched2.sfundCd) sfundCd = String(matched2.sfundCd);
                        if (matched2.efundCd) efundCd = String(matched2.efundCd);
                        if (matched2.mophId) batchMophId = String(matched2.mophId);
                    }
                }
            } catch (e) {}
        }

        const tempDir = path.join(__dirname, '../../storage/app/temp');
        if (!fs.existsSync(tempDir)) {
            fs.mkdirSync(tempDir, { recursive: true });
        }

        let downloadedPath = null;

        // Fast Path: Try Direct SMT Export API
        const exportConfigs = [];
        if (fromSystem === 'E-CLAIM-D1' || roundNo.includes('_IP') || roundNo.includes('_OP')) {
            exportConfigs.push({
                url: 'https://smt.nhso.go.th/smtf/api/eclaim-d1/export/eclaim-d1',
                data: { refDocNo: roundNo, vendorId: vendorId5Digit, batchNo: String(batchNo), postingDate: postingDate, fcode: '', subFund: '', description: '', mophId: batchMophId }
            });
        }
        if (fromSystem === 'LGO-HD' || roundNo.includes('LGO-HD') || roundNo.includes('LGOHD') || roundNo.startsWith('HD-')) {
            if (recId) {
                exportConfigs.push({
                    url: 'https://smt.nhso.go.th/smtf/api/lgohd/export/lgohd',
                    data: { recId: recId, vendorId: vendorId5Digit, mophId: batchMophId }
                });
                exportConfigs.push({
                    url: 'https://smt.nhso.go.th/smtf/api/lgohd/export/lgohd-ap',
                    data: { recId: recId, vendorId: vendorId5Digit, mophId: batchMophId }
                });
            }
        }
        if (fromSystem === 'DMIS' || roundNo.startsWith('DM')) {
            exportConfigs.push({
                url: 'https://smt.nhso.go.th/smtf/api/dmis/export/dmis',
                data: { refDocNo: roundNo, vendorId: vendorId5Digit, postingDate: postingDate, sfundCd: sfundCd, efundCd: efundCd, batchNo: String(batchNo), mophId: batchMophId }
            });
        }

        for (const ec of exportConfigs) {
            try {
                const expRes = await context.request.post(ec.url, {
                    ignoreHTTPSErrors: true,
                    headers: reqHeaders,
                    data: ec.data
                });
                if (expRes.ok()) {
                    const bodyBuf = await expRes.body();
                    if (bodyBuf.length > 500) {
                        const savePath = path.join(tempDir, `${roundNo}_${Date.now()}.xlsx`);
                        fs.writeFileSync(savePath, bodyBuf);
                        downloadedPath = savePath;
                        break;
                    }
                }
            } catch(e) {}
        }

        // Browser UI Navigation Path (if direct export was not available)
        if (!downloadedPath) {
            const accEncoded = encodeURIComponent(batchMophId);
            const roundEncoded = encodeURIComponent(roundNo);

            // Build list of candidate URLs to try in priority order
            const candidateUrls = [];

            if (fromSystem === 'LGO-HD' || roundNo.includes('LGO-HD') || roundNo.includes('LGOHD') || roundNo.startsWith('HD-')) {
                if (recId) {
                    candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/${roundEncoded}/${vendorId5Digit}/${recId}?&from=/home/budget/summary-detail/${vendorId10}/${postingDate}/${batchNo}/${fiscalYear}/${vendorId5Digit}/LGO&mophId=${accEncoded}`);
                    candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/${roundEncoded}/${vendorId5Digit}/${recId}?mophId=${accEncoded}`);
                    candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd-ap/${roundEncoded}/${vendorId5Digit}/${recId}?mophId=${accEncoded}`);
                    candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgo-hd/${roundEncoded}/${vendorId5Digit}/${recId}?mophId=${accEncoded}`);
                    candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/${roundEncoded}/${vendorId10}/${recId}?mophId=${accEncoded}`);
                }
                candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail/${vendorId10}/${postingDate}/${batchNo}/${fiscalYear}/${vendorId5Digit}/LGO`);
                // Fallback to DMIS route if LGOHD route fails
                candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/${roundEncoded}/${vendorId5Digit}/${postingDate}/${batchNo}/${sfundCd}/${efundCd}?mophId=${accEncoded}`);
            } else if (fromSystem === 'E-CLAIM-D1' || roundNo.includes('_IP') || roundNo.includes('_OP')) {
                if (recId) {
                    candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-eclaim-d1/${roundEncoded}/${recId}/${vendorId5Digit}/${batchNo}/${postingDate}?mophId=${accEncoded}`);
                    candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-eclaim/${roundEncoded}/${recId}/${vendorId5Digit}/${batchNo}/${postingDate}?mophId=${accEncoded}`);
                    candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-eclaim-d1/${roundEncoded}/${recId}/${vendorId10}/${batchNo}/${postingDate}?mophId=${accEncoded}`);
                }
                candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/${roundEncoded}/${vendorId5Digit}/${postingDate}/${batchNo}/${sfundCd}/${efundCd}?mophId=${accEncoded}`);
                candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail/${vendorId10}/${postingDate}/${batchNo}/${fiscalYear}/${vendorId5Digit}/UC`);
            } else {
                // Default to DMIS
                candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/${roundEncoded}/${vendorId5Digit}/${postingDate}/${batchNo}/${sfundCd}/${efundCd}?mophId=${accEncoded}`);
                candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/${roundEncoded}/${vendorId10}/${postingDate}/${batchNo}/${sfundCd}/${efundCd}?mophId=${accEncoded}`);
                candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail/${vendorId10}/${postingDate}/${batchNo}/${fiscalYear}/${vendorId5Digit}/null`);
            }

            candidateUrls.push(`https://smt.nhso.go.th/smtf/#/home/budget/summary-detail/${vendorId10}/${postingDate}/${batchNo}/${fiscalYear}/${vendorId5Digit}/`);

            let exportBtn = null;
            for (const dUrl of candidateUrls) {
                try {
                    await page.goto(dUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
                    // Wait for any loading spinner to detach
                    await page.waitForSelector('mat-spinner, .mat-progress-bar, ngx-spinner', { state: 'detached', timeout: 15000 }).catch(() => {});
                    await page.waitForTimeout(2000);

                    // Dismiss any blocking dialogs
                    const dismissBtn = await page.$('button:has-text("เข้าสู่เว็บไซต์"), mat-dialog-container button:has-text("ตกลง"), mat-dialog-container button:has-text("ปิด")');
                    if (dismissBtn && await dismissBtn.isVisible()) {
                        await dismissBtn.click().catch(() => {});
                        await page.waitForTimeout(500);
                    }

                    exportBtn = await findExportExcelButton(page, 8000);
                    if (exportBtn) {
                        break;
                    }
                } catch (e) {}
            }

            // Fallback to Summary UI navigation if direct URL didn't find export button
            if (!exportBtn) {
                try {
                    const summaryUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary';
                    await page.goto(summaryUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
                    await page.waitForSelector('mat-spinner, .mat-progress-bar, ngx-spinner', { state: 'detached', timeout: 15000 }).catch(() => {});
                    await page.waitForTimeout(2000);

                    // Dismiss announcement
                    const dismissBtn = await page.$('button:has-text("เข้าสู่เว็บไซต์")');
                    if (dismissBtn && await dismissBtn.isVisible()) {
                        await dismissBtn.click().catch(() => {});
                        await page.waitForTimeout(1000);
                    }

                    // Try finding and clicking the row with batchNo or roundNo
                    const rowLink = await page.$(`tr:has-text("${batchNo}") a, tr:has-text("${roundNo}") a, tr:has-text("${batchNo}") button, tr:has-text("${roundNo}") button, tr:has-text("${batchNo}") mat-icon, tr:has-text("${roundNo}") mat-icon, mat-row:has-text("${batchNo}") mat-cell a, mat-row:has-text("${roundNo}") mat-cell a`);
                    if (rowLink) {
                        await rowLink.click();
                        await page.waitForSelector('mat-spinner, .mat-progress-bar, ngx-spinner', { state: 'detached', timeout: 15000 }).catch(() => {});
                        await page.waitForTimeout(3000);
                        exportBtn = await findExportExcelButton(page, 12000);
                    }
                } catch (e) {}
            }

            if (!exportBtn) {
                await browser.close();
                console.error(JSON.stringify({ 
                    status: 'error', 
                    message: `ไม่พบปุ่ม Export Excel ในหน้ารายละเอียดของ สปสช. สำหรับงวด ${roundNo} (หน่วยบริการ ${hcode}) อาจเป็นงบที่ไม่มีรายการผู้ป่วยรายบุคคล หรือยังไม่ออกรายงาน` 
                }));
                process.exit(1);
            }

            // Download File via browser event
            let download;
            try {
                [download] = await Promise.all([
                    page.waitForEvent('download', { timeout: 60000 }),
                    exportBtn.click(),
                ]);
            } catch (btnErr) {
                await browser.close();
                console.error(JSON.stringify({ 
                    status: 'error', 
                    message: `กดปุ่มดาวน์โหลดแล้วหมดเวลา (Timeout) หรือเกิดข้อผิดพลาด: ${btnErr.message}` 
                }));
                process.exit(1);
            }

            const dPath = path.join(tempDir, `${roundNo}_${Date.now()}.xlsx`);
            try {
                await download.saveAs(dPath);
                downloadedPath = dPath;
            } catch (saveErr) {
                const failReason = await download.failure().catch(() => null);
                await browser.close();
                console.error(JSON.stringify({ 
                    status: 'error', 
                    message: `การดาวน์โหลดไฟล์ Excel จาก สปสช. ถูกยกเลิก (${failReason || saveErr.message}) - อาจเกิดจาก Session ThaiD/สปสช. หมดอายุ หรือสิทธิ์เข้าถึงไม่ตรง กรุณาลองสแกนเข้าสู่ระบบ ThaiD ใหม่อีกครั้ง` 
                }));
                process.exit(1);
            }
        }

        await browser.close();

        if (!downloadedPath || !fs.existsSync(downloadedPath)) {
            console.error(JSON.stringify({ 
                status: 'error', 
                message: `ไม่พบไฟล์ที่ดาวน์โหลดสำหรับงวด ${roundNo}` 
            }));
            process.exit(1);
        }

        // 6. Parse and Insert into DB
        const phpParser = path.join(__dirname, 'parse_detail_excel.php');
        const cmd = `php "${phpParser}" "${downloadedPath}" "${roundNo}" "${batchNo}" "${vendorId5Digit}"`;
        const resultOutput = execSync(cmd, { encoding: 'utf-8' });

        // 7. Zero Server File Retention: delete downloaded file
        if (fs.existsSync(downloadedPath)) {
            fs.unlinkSync(downloadedPath);
        }

        // Print final JSON result
        console.log(resultOutput.trim());
    } catch (err) {
        if (browser) {
            try { await browser.close(); } catch(e) {}
        }
        console.error(JSON.stringify({ status: 'error', message: err.message }));
        process.exit(1);
    }
})();
