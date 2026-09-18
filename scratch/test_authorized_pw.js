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
    const tokenData = JSON.parse(fs.readFileSync(path.join(__dirname, 'smt_token.json'), 'utf-8'));
    const token = tokenData.token;
    const jwt = parseJwt(token);

    console.log("JWT sub:", jwt.sub, "name:", jwt.name || jwt.nameTh);

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        acceptDownloads: true
    });
    const page = await context.newPage();

    page.on('console', msg => console.log('PAGE LOG:', msg.text()));
    page.on('request', req => {
        if (req.url().includes('adapter') || req.url().includes('person')) {
            console.log('REQUEST URL:', req.url());
            console.log('REQUEST METHOD:', req.method());
            console.log('REQUEST HEADERS:', JSON.stringify(req.headers()));
            console.log('REQUEST POST DATA:', req.postData());
        }
    });
    page.on('response', async resp => {
        if (resp.url().includes('adapter') || resp.url().includes('person')) {
            console.log('HTTP RESP:', resp.status(), resp.url());
            try {
                console.log('RESP BODY:', await resp.text());
            } catch(e) {}
        }
    });

    // Go to base domain
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'domcontentloaded' });

    // Populate all Angular webstorage keys
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

    // Navigate to DCKD detail page
    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216%2F217';
    console.log("Navigating to DCKD detail...");
    await page.goto(dmisUrl, { waitUntil: 'networkidle', timeout: 35000 }).catch(() => {});
    await page.waitForTimeout(4000);

    console.log("Current URL:", page.url(), "Title:", await page.title());

    await page.screenshot({ path: path.join(__dirname, 'test_auth_dckd.png') });
    console.log("Screenshot saved to scratch/test_auth_dckd.png");

    const exportBtn = await page.waitForSelector(':text("Export Excel")', { timeout: 10000 }).catch(() => null);
    if (exportBtn) {
        console.log("🎉 SUCCESS! Found Export Excel button!");
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 30000 }),
            exportBtn.click(),
        ]);
        const dPath = path.join(__dirname, 'dckd_downloaded.xlsx');
        await download.saveAs(dPath);
        console.log("🎉 DOWNLOADED SUCCESSFULLY:", dPath, "Size:", fs.statSync(dPath).size);
    } else {
        console.log("Export button not found.");
    }

    await browser.close();
})();
