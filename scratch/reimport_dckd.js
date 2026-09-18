const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

(async () => {
    const tokenData = JSON.parse(fs.readFileSync(path.join(__dirname, 'smt_token.json'), 'utf-8'));
    console.log("Using captured token, length:", tokenData.token.length);

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ acceptDownloads: true });
    const page = await context.newPage();

    // Set token in localStorage for SMT domain
    await page.goto('https://smt.nhso.go.th/smtf/#/login');
    await page.evaluate((tok) => {
        localStorage.setItem('ngx-webstorage|token', JSON.stringify(tok));
    }, tokenData.token);

    // Navigate to DCKD6931080031 detail
    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216%2F217';
    console.log("Navigating to DCKD url:", dmisUrl);
    await page.goto(dmisUrl);
    
    // Wait for Export Excel button
    console.log("Waiting for Export Excel button...");
    const exportBtn = await page.waitForSelector('button:has-text("Export Excel"), mat-chip:has-text("Export Excel"), :text("Export Excel")', { timeout: 25000 }).catch(() => null);

    if (exportBtn) {
        console.log("Found Export Excel button! Clicking to download...");
        const [download] = await Promise.all([
            page.waitForEvent('download', { timeout: 30000 }),
            exportBtn.click(),
        ]);
        const downloadPath = path.join(__dirname, download.suggestedFilename());
        await download.saveAs(downloadPath);
        console.log("Downloaded:", download.suggestedFilename(), "size:", fs.statSync(downloadPath).size);

        // Run updated import script
        console.log("Re-importing DCKD with updated amount column mapping...");
        const out = execSync(`php scratch/import_detail_excel.php "${downloadPath}" "DCKD6931080031"`, { encoding: 'utf-8' });
        console.log(out.trim());

        // Delete immediately (zero storage policy)
        if (fs.existsSync(downloadPath)) {
            fs.unlinkSync(downloadPath);
            console.log("Deleted temporary file.");
        }
    } else {
        console.log("Export button not found. Current URL:", page.url());
    }

    await browser.close();
})();
