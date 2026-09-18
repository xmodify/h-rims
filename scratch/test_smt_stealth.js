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
        acceptDownloads: true
    });

    await context.addCookies(cookies);
    const page = await context.newPage();

    // Stealth evasion
    await page.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    });

    console.log("Navigating to SMT login...");
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 30000 });

    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช."), :text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
    if (loginBtn) {
        console.log("Clicking OSS login button...");
        await loginBtn.click();
        await page.waitForTimeout(8000);
        console.log("Current URL:", page.url());

        await page.screenshot({ path: path.join(__dirname, 'smt_stealth_after_sso.png') });
        console.log("Screenshot saved to scratch/smt_stealth_after_sso.png");
    }

    await browser.close();
})();
