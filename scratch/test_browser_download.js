const { chromium } = require('playwright');
const { execSync } = require('child_process');

// 1. Get user session token from DB via php artisan tinker / script
const tokenData = JSON.parse(execSync('php scratch/check_token_format.php', { encoding: 'utf-8' }).trim().split("\n").filter(l => l.startsWith('{') || l.startsWith('[')).join('') || '{}');

console.log("Starting Playwright script...");

// Get token from PHP directly
const phpOutput = execSync('php -r "require \'vendor/autoload.php\'; $app = require_once \'bootstrap/app.php\'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); $u = DB::table(\'users\')->where(\'id\', 3)->first(); echo json_encode([\'cookie\' => $u->eclaim_session_token]);"', { encoding: 'utf-8' });
const { cookie } = JSON.parse(phpOutput);

const jwtMatch = cookie.match(/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i);
const jwt = jwtMatch ? jwtMatch[1] : cookie;

console.log("JWT length:", jwt.length);

(async () => {
    const browser = await chromium.launch({
        headless: true,
        args: ['--ignore-certificate-errors', '--no-sandbox']
    });

    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        viewport: { width: 1400, height: 900 }
    });

    // Do not set huge cookies which cause 400 Bad Request (header too large)
    // SMT uses Bearer token in localStorage via ngx-webstorage

    const page = await context.newPage();

    // Log all requests & responses
    page.on('request', req => {
        if (req.url().includes('api') || req.url().includes('dmis') || req.url().includes('lgohd') || req.url().includes('export')) {
            console.log(`[REQ] ${req.method()} ${req.url()}`);
            if (req.postData()) {
                console.log(`[REQ DATA] ${req.postData()}`);
            }
        }
    });

    page.on('response', async res => {
        if (res.url().includes('api') || res.url().includes('dmis') || res.url().includes('lgohd') || res.url().includes('export')) {
            console.log(`[RES] ${res.status()} ${res.url()}`);
            try {
                const text = await res.text();
                if (text.length < 500) {
                    console.log(`[RES BODY] ${text}`);
                } else {
                    console.log(`[RES BODY] length: ${text.length}, preview: ${text.substring(0, 200)}`);
                }
            } catch (e) {}
        }
    });

    page.on('console', msg => console.log(`[BROWSER CONSOLE] ${msg.type()}: ${msg.text()}`));

    // First go to base URL to initialize localStorage
    console.log("Navigating to base URL...");
    await page.goto('https://smt.nhso.go.th/smtf/', { waitUntil: 'domcontentloaded', timeout: 30000 });

    // Set localStorage with JSON.stringify as expected by ngx-webstorage
    await page.evaluate(({ token }) => {
        localStorage.setItem('ngx-webstorage|token', JSON.stringify(token));
        localStorage.setItem('ngx-webstorage|userId', JSON.stringify('243761'));
        localStorage.setItem('ngx-webstorage|roleId', JSON.stringify('344'));
        localStorage.setItem('ngx-webstorage|fullnameTh', JSON.stringify('นายศิริฤกษ์ คณาดี'));
        localStorage.setItem('ngx-webstorage|firstname', JSON.stringify('ศิริฤกษ์'));
        localStorage.setItem('ngx-webstorage|lastname', JSON.stringify('คณาดี'));
        localStorage.setItem('ngx-webstorage|userType', JSON.stringify('SERVICE_UNIT'));
        localStorage.setItem('ngx-webstorage|organizationId', JSON.stringify('10989'));
        localStorage.setItem('ngx-webstorage|organizationName', JSON.stringify('โรงพยาบาลชุมชนหัวตะพาน'));
    }, { token: jwt });

    console.log("LocalStorage set. Waiting 2s...");
    await page.waitForTimeout(2000);

    // 1. Try DMIS Detail page
    console.log("\n==============================================");
    console.log("Testing DMIS detail page (DCKD6931080031)...");
    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216/217';
    await page.goto(dmisUrl, { waitUntil: 'networkidle', timeout: 30000 }).catch(e => console.log("Goto error:", e.message));

    await page.waitForTimeout(5000);

    const title = await page.title();
    console.log("Page title:", title);
    const content = await page.evaluate(() => document.body.innerText.substring(0, 500));
    console.log("Page text snippet:\n", content);

    // Take screenshot of DMIS page
    await page.screenshot({ path: 'scratch/dmis_page.png' });
    console.log("Saved scratch/dmis_page.png");

    // Check if Export Excel button is visible
    const exportBtn = await page.$('mat-chip:has-text("Export Excel"), button:has-text("Export Excel")');
    if (exportBtn) {
        console.log("Found Export Excel button! Clicking it...");
        // Listen for download
        const downloadPromise = page.waitForEvent('download', { timeout: 15000 }).catch(() => null);
        await exportBtn.click();
        const download = await downloadPromise;
        if (download) {
            console.log("DOWNLOAD EVENT TRIGGERED! Filename:", download.suggestedFilename());
        } else {
            console.log("No download event triggered within 15s.");
        }
        await page.waitForTimeout(3000);
        await page.screenshot({ path: 'scratch/dmis_after_click.png' });
    } else {
        console.log("Export Excel button NOT found.");
    }

    // 2. Try LGOHD Detail page
    console.log("\n==============================================");
    console.log("Testing LGOHD detail page (LGO-HD69-M11)...");
    const lgohdUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/LGO-HD69-M11/10989/72668?mophId=1102050102.801/802';
    await page.goto(lgohdUrl, { waitUntil: 'networkidle', timeout: 30000 }).catch(e => console.log("Goto error:", e.message));

    await page.waitForTimeout(5000);
    const contentLgo = await page.evaluate(() => document.body.innerText.substring(0, 500));
    console.log("LGOHD Page text snippet:\n", contentLgo);
    await page.screenshot({ path: 'scratch/lgohd_page.png' });

    const exportBtnLgo = await page.$('mat-chip:has-text("Export Excel"), button:has-text("Export Excel")');
    if (exportBtnLgo) {
        console.log("Found LGOHD Export Excel button! Clicking it...");
        const downloadPromise = page.waitForEvent('download', { timeout: 15000 }).catch(() => null);
        await exportBtnLgo.click();
        const download = await downloadPromise;
        if (download) {
            console.log("LGOHD DOWNLOAD EVENT TRIGGERED! Filename:", download.suggestedFilename());
        } else {
            console.log("LGOHD No download event triggered within 15s.");
        }
        await page.waitForTimeout(3000);
        await page.screenshot({ path: 'scratch/lgohd_after_click.png' });
    } else {
        console.log("LGOHD Export Excel button NOT found.");
    }

    await browser.close();
    console.log("Done!");
})();
