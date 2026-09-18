const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const tokenData = JSON.parse(fs.readFileSync('scratch/smt_token.json', 'utf-8'));
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ acceptDownloads: true });
    const page = await context.newPage();
    
    await page.goto('https://smt.nhso.go.th/smtf/#/login');
    await page.evaluate((tok) => {
        localStorage.setItem('ngx-webstorage|token', JSON.stringify(tok));
    }, tokenData.token);
    
    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216%2F217';
    console.log('Navigating to DCKD detail...');
    await page.goto(dmisUrl, { waitUntil: 'networkidle', timeout: 45000 });
    await page.waitForTimeout(4000);
    
    const exportBtn = await page.waitForSelector('button:has-text("Export Excel"), mat-chip:has-text("Export Excel"), :text("Export Excel")', { timeout: 15000 });
    if (exportBtn) {
        console.log('Clicking export...');
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 30000 }),
            exportBtn.click(),
        ]);
        const dPath = path.join(__dirname, 'dckd_sample.xlsx');
        await download.saveAs(dPath);
        console.log('Saved to:', dPath, 'size:', fs.statSync(dPath).size);
    }
    await browser.close();
})();
