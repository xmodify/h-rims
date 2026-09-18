const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

(async () => {
    const rawCookies = JSON.parse(fs.readFileSync(path.join(__dirname, 'cookies_for_playwright.json'), 'utf-8'));
    // Filter out huge ACCESS_TOKEN from cookie header to prevent F5 BIG-IP ASM 400 Bad Request
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

    page.on('response', resp => {
        if (resp.url().includes('dmis') || resp.url().includes('report') || resp.url().includes('export')) {
            console.log(`[HTTP RESP ${resp.status()}] ${resp.url()}`);
        }
    });

    await page.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    });

    console.log("1. เข้าสู่ระบบ SMT ผ่าน SSO...");
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 30000 });

    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
    if (loginBtn) {
        await loginBtn.click();
        await page.waitForTimeout(6000);
    }

    console.log("2. เข้าสู่หน้ารายละเอียด DCKD6931080031...");
    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216%2F217';
    await page.goto(dmisUrl, { waitUntil: 'networkidle', timeout: 45000 }).catch(() => {});
    await page.waitForTimeout(4000);

    console.log("3. กำลังรอปุ่ม Export Excel...");
    const exportBtn = await page.waitForSelector('mat-chip:has-text("Export Excel"), button:has-text("Export Excel"), :text("Export Excel")', { timeout: 45000 }).catch(() => null);

    await page.screenshot({ path: path.join(__dirname, 'dckd_working_page.png') });

    if (exportBtn) {
        console.log("🎉 พบปุ่ม Export Excel! กำลังดาวน์โหลด...");
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 60000 }),
            exportBtn.click(),
        ]);
        const dPath = path.join(__dirname, download.suggestedFilename());
        await download.saveAs(dPath);
        const fSize = fs.statSync(dPath).size;
        console.log("🎉 ดาวน์โหลดสำเร็จ:", download.suggestedFilename(), "ขนาด:", fSize, "bytes");

        // Import into smart_money_details
        console.log("4. นำเข้าข้อมูลคนไข้เข้าสู่ smart_money_details ในฐานข้อมูล...");
        const out = execSync(`php scratch/import_detail_excel.php "${dPath}" "DCKD6931080031"`, { encoding: 'utf-8' });
        console.log(out.trim());

        // Zero storage policy - remove downloaded file
        if (fs.existsSync(dPath)) {
            fs.unlinkSync(dPath);
            console.log("5. ลบไฟล์ชั่วคราวเรียบร้อย (Zero server file retention)");
        }
    } else {
        console.log("❌ ไม่พบปุ่ม Export Excel ในหน้ารายละเอียด");
    }

    await browser.close();
})();
