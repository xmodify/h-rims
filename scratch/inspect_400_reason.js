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

    page.on('request', req => {
        if (req.url().includes('list-alert') || req.url().includes('dmis')) {
            console.log('REQ URL:', req.url());
            console.log('AUTH HEADER:', req.headers()['authorization'] ? req.headers()['authorization'].substring(0, 60) + '...' : 'none');
        }
    });

    page.on('response', async resp => {
        if (resp.url().includes('list-alert') || resp.url().includes('dmis')) {
            console.log('RESP STATUS:', resp.status(), resp.url());
            try {
                const text = await resp.text();
                console.log('RESP BODY:', text);
            } catch(e) {}
        }
    });

    await page.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    });

    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 30000 });

    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช."), :text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
    if (loginBtn) {
        await loginBtn.click();
        await page.waitForTimeout(6000);
    }

    const tok = await page.evaluate(() => localStorage.getItem('ngx-webstorage|token'));
    console.log("Raw localStorage token:", tok ? tok.substring(0, 60) + '...' : 'NULL');

    await browser.close();
})();
