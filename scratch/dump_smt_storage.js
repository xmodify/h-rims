const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
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
    });

    await context.addCookies(cookies);
    const page = await context.newPage();

    await page.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    });

    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 30000 });

    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช."), :text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
    if (loginBtn) {
        await loginBtn.click();
        await page.waitForTimeout(6000);
    }

    const storageDump = await page.evaluate(() => {
        const res = {};
        for (let i = 0; i < localStorage.length; i++) {
            const k = localStorage.key(i);
            res[k] = localStorage.getItem(k);
        }
        return res;
    });

    fs.writeFileSync(path.join(__dirname, 'smt_storage_dump.json'), JSON.stringify(storageDump, null, 2));
    console.log("Storage dumped successfully!");

    await browser.close();
})();
