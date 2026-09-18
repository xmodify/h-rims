const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const cookies = JSON.parse(fs.readFileSync(path.join(__dirname, 'cookies_for_playwright.json'), 'utf-8'));

    const browser = await chromium.launch({
        headless: true,
        args: ['--disable-blink-features=AutomationControlled']
    });

    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    });

    await context.addCookies(cookies);
    const page = await context.newPage();

    page.on('response', async resp => {
        if (resp.status() === 400 && resp.url().includes('api')) {
            console.log('400 on URL:', resp.url());
            try {
                console.log('400 body:', await resp.text());
            } catch(e) {}
        }
    });

    await page.goto('https://smt.nhso.go.th/smtf/#/login');
    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
    if (loginBtn) {
        await loginBtn.click();
        await page.waitForTimeout(6000);
    }

    const currentCookies = await context.cookies();
    console.log("Cookies after SSO login:", currentCookies.map(c => `${c.name} (${c.domain})`));

    await browser.close();
})();
