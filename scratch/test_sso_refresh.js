const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const cookies = JSON.parse(fs.readFileSync(path.join(__dirname, 'cookies_for_playwright.json'), 'utf-8'));
    console.log("Loaded cookies count:", cookies.length);

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        acceptDownloads: true
    });

    await context.addCookies(cookies);
    const page = await context.newPage();

    page.on('response', async resp => {
        if (resp.url().includes('token') || resp.url().includes('thaid-callback')) {
            console.log('INTERESTING RESP:', resp.status(), resp.url());
        }
    });

    console.log("Navigating to SMT login page...");
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 30000 });

    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช."), :text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
    if (loginBtn) {
        console.log("Clicking OSS สปสช. button...");
        await loginBtn.click();
        await page.waitForTimeout(6000);
        console.log("Current URL:", page.url());

        // Check if token in localStorage
        const token = await page.evaluate(() => localStorage.getItem('ngx-webstorage|token'));
        console.log("Fresh token from SMT:", token ? token.substring(0, 50) + '...' : 'NONE');

        await page.screenshot({ path: path.join(__dirname, 'test_sso_refresh.png') });
    } else {
        console.log("Login button not found.");
    }

    await browser.close();
})();
