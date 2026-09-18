const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const cookies = JSON.parse(fs.readFileSync(path.join(__dirname, 'cookies_for_playwright.json'), 'utf-8'));
    console.log("Loaded", cookies.length, "cookies");

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ acceptDownloads: true });
    await context.addCookies(cookies);

    const page = await context.newPage();

    let interceptedCode = null;
    let interceptedToken = null;

    page.on('request', req => {
        if (req.url().includes('thaid/token')) {
            console.log("POST thaid/token data:", req.postData());
        }
    });

    page.on('response', async resp => {
        if (resp.url().includes('thaid/token')) {
            console.log("thaid/token HTTP Status:", resp.status());
            try {
                const body = await resp.json();
                console.log("thaid/token Response keys:", Object.keys(body));
                interceptedToken = body;
            } catch (e) {}
        }
    });

    console.log("Navigating to SMT login...");
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 30000 });

    // Look for button "เข้าสู่ระบบผ่าน OSS สปสช."
    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช."), :text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);

    if (loginBtn) {
        console.log("Found OSS login button! Clicking...");
        await loginBtn.click();
        
        // Wait for redirect back
        console.log("Waiting for navigation / auth redirect...");
        await page.waitForTimeout(8000);
        console.log("Final URL:", page.url());

        // Check localStorage
        const storageDump = await page.evaluate(() => {
            const res = {};
            for (let i = 0; i < localStorage.length; i++) {
                const k = localStorage.key(i);
                res[k] = localStorage.getItem(k);
            }
            return res;
        });

        console.log("LocalStorage keys in SMT:", Object.keys(storageDump));
        if (storageDump['ngx-webstorage|token']) {
            console.log("🎉 GOT SMT TOKEN from localStorage!");
            const tokVal = JSON.parse(storageDump['ngx-webstorage|token']);
            fs.writeFileSync(path.join(__dirname, 'smt_fresh_token.json'), JSON.stringify({
                token: tokVal,
                localStorage: storageDump
            }, null, 2));
            console.log("Saved to scratch/smt_fresh_token.json");
        }
        await page.screenshot({ path: path.join(__dirname, 'smt_after_sso.png') });
    } else {
        console.log("Login button not found.");
    }

    await browser.close();
})();
