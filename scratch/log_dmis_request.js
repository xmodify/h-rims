const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const storageDump = JSON.parse(fs.readFileSync(path.join(__dirname, 'smt_storage_dump.json'), 'utf-8'));
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

    page.on('request', req => {
        if (req.url().includes('dmis')) {
            console.log('REQ URL:', req.url());
            console.log('REQ METHOD:', req.method());
            console.log('REQ HEADERS:', JSON.stringify(req.headers()));
            console.log('REQ POST DATA:', req.postData());
        }
    });

    page.on('response', async resp => {
        if (resp.url().includes('dmis')) {
            console.log('RESP STATUS:', resp.status(), resp.url());
            try {
                console.log('RESP BODY:', await resp.text());
            } catch(e) {}
        }
    });

    await page.goto('https://smt.nhso.go.th/smtf/#/login');
    await page.evaluate((dump) => {
        for (const [k, v] of Object.entries(dump)) {
            localStorage.setItem(k, v);
        }
    }, storageDump);

    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216%2F217';
    await page.goto(dmisUrl, { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
    await page.waitForTimeout(3000);

    await browser.close();
})();
