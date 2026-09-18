const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    console.log("Testing headless Playwright with token...");
    const tokenData = JSON.parse(fs.readFileSync(path.join(__dirname, 'smt_token.json'), 'utf-8'));
    const token = tokenData.token;

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ acceptDownloads: true });
    const page = await context.newPage();

    // 1. Go to base SMT domain first to set localStorage
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'domcontentloaded' });
    await page.evaluate((tok) => {
        localStorage.setItem('ngx-webstorage|token', JSON.stringify(tok));
    }, token);

    // 2. Navigate to summary page to let Angular initialize with token
    console.log("Navigating to home summary...");
    await page.goto('https://smt.nhso.go.th/smtf/#/home/budget/summary', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
    await page.waitForTimeout(3000);
    console.log("Summary page URL:", page.url(), "Title:", await page.title());

    // 3. Now navigate to LGO-HD detail
    const lgohdUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/LGO-HD69-M11/10989/72668?mophId=1102050102.801%2F802';
    console.log("Navigating to LGO-HD detail...");
    await page.goto(lgohdUrl, { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
    await page.waitForTimeout(4000);

    const exportBtn = await page.waitForSelector(':text("Export Excel")', { timeout: 15000 }).catch(() => null);
    if (exportBtn) {
        console.log("SUCCESS! Found Export Excel button on LGO-HD detail!");
    } else {
        console.log("Export button not found. URL:", page.url());
    }

    await browser.close();
})();
