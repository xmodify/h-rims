const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const tokenData = JSON.parse(fs.readFileSync(path.join(__dirname, 'smt_token.json'), 'utf-8'));
    const token = tokenData.token;

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ acceptDownloads: true, viewport: { width: 1280, height: 800 } });
    const page = await context.newPage();

    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'domcontentloaded' });
    await page.evaluate((tok) => {
        localStorage.setItem('ngx-webstorage|token', JSON.stringify(tok));
    }, token);

    const lgohdUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/LGO-HD69-M11/10989/72668?mophId=1102050102.801%2F802';
    await page.goto(lgohdUrl);
    await page.waitForTimeout(6000);

    await page.screenshot({ path: path.join(__dirname, 'lgohd_headless.png') });
    console.log("Saved screenshot to scratch/lgohd_headless.png");

    const buttons = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('button, a, .mat-button, mat-chip')).map(e => ({
            tag: e.tagName,
            text: (e.innerText || e.textContent || '').trim()
        })).filter(e => e.text.length > 0);
    });
    console.log("Buttons found:", JSON.stringify(buttons, null, 2));

    await browser.close();
})();
