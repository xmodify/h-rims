const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const rawCookies = JSON.parse(fs.readFileSync(path.join(__dirname, 'cookies_for_playwright.json'), 'utf-8'));
    // Filter out huge ACCESS_TOKEN from cookie header
    const cleanCookies = rawCookies.filter(c => c.name !== 'ACCESS_TOKEN' && c.name.length < 100);

    console.log("Filtered cookies count:", cleanCookies.length);

    const browser = await chromium.launch({
        headless: true,
        args: ['--disable-blink-features=AutomationControlled']
    });

    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    });

    await context.addCookies(cleanCookies);
    const page = await context.newPage();

    page.on('response', resp => {
        if (resp.url().includes('api')) {
            console.log(`[API RESP ${resp.status()}] ${resp.url()}`);
        }
    });

    await page.goto('https://smt.nhso.go.th/smtf/#/login');
    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
    if (loginBtn) {
        console.log("Clicking OSS login...");
        await loginBtn.click();
        await page.waitForTimeout(6000);
    }

    console.log("Final page URL:", page.url());
    await browser.close();
})();
