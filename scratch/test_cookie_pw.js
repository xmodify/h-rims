const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

function parseJwt(token) {
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        return JSON.parse(jsonPayload);
    } catch (e) {
        return {};
    }
}

(async () => {
    const cookies = JSON.parse(fs.readFileSync(path.join(__dirname, 'cookies_for_playwright.json'), 'utf-8'));
    const tokenData = JSON.parse(fs.readFileSync(path.join(__dirname, 'smt_token.json'), 'utf-8'));
    const token = tokenData.token;
    const jwt = parseJwt(token);

    console.log("Cookies count:", cookies.length, "JWT sub:", jwt.sub);

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        acceptDownloads: true
    });

    // Add cookies to browser context
    await context.addCookies(cookies);

    const page = await context.newPage();

    page.on('console', msg => console.log('PAGE LOG:', msg.text()));
    page.on('response', resp => {
        if (resp.status() >= 400 || resp.url().includes('adapter') || resp.url().includes('dmis') || resp.url().includes('lgohd')) {
            console.log('HTTP RESP:', resp.status(), resp.url());
        }
    });

    // 1. Visit base domain
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'domcontentloaded' });

    // 2. Populate all Angular localStorage keys
    await page.evaluate(({ token, jwt }) => {
        localStorage.setItem('ngx-webstorage|token', JSON.stringify(token));
        localStorage.setItem('ngx-webstorage|username', JSON.stringify(jwt.preferred_username || jwt.username || 'sirirerk'));
        localStorage.setItem('ngx-webstorage|firstname', JSON.stringify(jwt.nameTh || jwt.name || ''));
        localStorage.setItem('ngx-webstorage|lastname', JSON.stringify(''));
        localStorage.setItem('ngx-webstorage|userId', JSON.stringify(jwt.sub || ''));
        localStorage.setItem('ngx-webstorage|userType', JSON.stringify('EXTERNAL'));
        localStorage.setItem('ngx-webstorage|offaRole', JSON.stringify('USER'));
        localStorage.setItem('ngx-webstorage|position', JSON.stringify(''));
        localStorage.setItem('ngx-webstorage|smtPosition', JSON.stringify(''));
        localStorage.setItem('ngx-webstorage|smtPositionId', JSON.stringify(''));
    }, { token, jwt });

    // 3. Test LGO-HD
    const lgohdUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/LGO-HD69-M11/10989/72668?mophId=1102050102.801%2F802';
    console.log("\n--- Testing LGO-HD: " + lgohdUrl + " ---");
    await page.goto(lgohdUrl, { waitUntil: 'networkidle', timeout: 35000 }).catch(() => {});
    await page.waitForTimeout(4000);

    console.log("Current URL:", page.url(), "Title:", await page.title());
    await page.screenshot({ path: path.join(__dirname, 'test_cookie_lgohd.png') });

    let exportBtn = await page.waitForSelector(':text("Export Excel")', { timeout: 8000 }).catch(() => null);
    if (exportBtn) {
        console.log("🎉 SUCCESS! Found Export Excel on LGO-HD!");
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 30000 }),
            exportBtn.click(),
        ]);
        const dPath = path.join(__dirname, 'lgohd_downloaded.xlsx');
        await download.saveAs(dPath);
        console.log("🎉 DOWNLOADED LGO-HD:", dPath, "Size:", fs.statSync(dPath).size);
    } else {
        console.log("Export button not found on LGO-HD.");
    }

    // 4. Test DCKD
    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216%2F217';
    console.log("\n--- Testing DCKD: " + dmisUrl + " ---");
    await page.goto(dmisUrl, { waitUntil: 'networkidle', timeout: 35000 }).catch(() => {});
    await page.waitForTimeout(4000);

    console.log("Current URL:", page.url(), "Title:", await page.title());
    await page.screenshot({ path: path.join(__dirname, 'test_cookie_dckd.png') });

    exportBtn = await page.waitForSelector(':text("Export Excel")', { timeout: 8000 }).catch(() => null);
    if (exportBtn) {
        console.log("🎉 SUCCESS! Found Export Excel on DCKD!");
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 30000 }),
            exportBtn.click(),
        ]);
        const dPath = path.join(__dirname, 'dckd_downloaded.xlsx');
        await download.saveAs(dPath);
        console.log("🎉 DOWNLOADED DCKD:", dPath, "Size:", fs.statSync(dPath).size);
    } else {
        console.log("Export button not found on DCKD.");
    }

    await browser.close();
})();
