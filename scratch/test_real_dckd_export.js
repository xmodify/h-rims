const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

(async () => {
    const storageDump = JSON.parse(fs.readFileSync(path.join(__dirname, 'smt_storage_dump.json'), 'utf-8'));
    const cookies = JSON.parse(fs.readFileSync(path.join(__dirname, 'cookies_for_playwright.json'), 'utf-8'));

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
        viewport: { width: 1280, height: 800 },
        acceptDownloads: true
    });

    await context.addCookies(cookies);
    const page = await context.newPage();

    page.on('response', async resp => {
        if (resp.url().includes('dmis') || resp.url().includes('report') || resp.url().includes('export')) {
            console.log(`[HTTP RESP] ${resp.status()} ${resp.url()}`);
            if (resp.status() >= 400) {
                try {
                    console.log(`[HTTP ERROR BODY]`, await resp.text());
                } catch(e) {}
            }
        }
    });

    await page.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    });

    console.log("1. Setting localStorage directly for SMT...");
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'domcontentloaded' });
    
    await page.evaluate((dump) => {
        for (const [k, v] of Object.entries(dump)) {
            localStorage.setItem(k, v);
        }
    }, storageDump);

    console.log("2. Navigating to DCKD detail page...");
    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216%2F217';
    await page.goto(dmisUrl, { waitUntil: 'networkidle', timeout: 45000 }).catch(() => {});
    await page.waitForTimeout(4000);

    console.log("Current URL:", page.url());
    await page.screenshot({ path: path.join(__dirname, 'dckd_real_page2.png') });

    console.log("3. Looking for Export Excel button...");
    const exportBtn = await page.waitForSelector('mat-chip:has-text("Export Excel"), button:has-text("Export Excel"), :text("Export Excel")', { timeout: 30000 }).catch(() => null);

    if (exportBtn) {
        console.log("🎉 FOUND EXPORT EXCEL BUTTON! Downloading...");
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 60000 }),
            exportBtn.click(),
        ]);
        const dPath = path.join(__dirname, download.suggestedFilename());
        await download.saveAs(dPath);
        const fSize = fs.statSync(dPath).size;
        console.log("🎉 SUCCESS! Downloaded:", download.suggestedFilename(), "Size:", fSize, "bytes");

        // Run import
        const out = execSync(`php scratch/import_detail_excel.php "${dPath}" "DCKD6931080031"`, { encoding: 'utf-8' });
        console.log(out.trim());

        if (fs.existsSync(dPath)) {
            fs.unlinkSync(dPath);
            console.log("Deleted temporary file.");
        }
    } else {
        console.log("Export button not found.");
    }

    await browser.close();
})();
