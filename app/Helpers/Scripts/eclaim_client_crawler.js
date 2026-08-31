/**
 * E-Claim Client/Home Automated Crawler
 * Pulls all claims from https://eclaim.nhso.go.th/Client/home with "ทุกรายการ" (All records)
 */
const fs = require('fs');
const path = require('path');

function findChromiumExecutable() {
    try {
        const dir = path.resolve(__dirname, '../../../storage/app/playwright_browsers');
        if (fs.existsSync(dir)) {
            const rec = (d, target) => {
                for (const f of fs.readdirSync(d)) {
                    const fp = path.join(d, f);
                    if (fs.statSync(fp).isDirectory()) {
                        const r = rec(fp, target);
                        if (r) return r;
                    } else if (f === target) {
                        return fp;
                    }
                }
                return null;
            };
            const hTarget = process.platform === 'win32' ? 'chrome-headless-shell.exe' : 'chrome-headless-shell';
            const cTarget = process.platform === 'win32' ? 'chrome.exe' : 'chrome';
            const fH = rec(dir, hTarget);
            if (fH) return fH;
            const fC = rec(dir, cTarget);
            if (fC) return fC;
        }
    } catch(e) {}
    const sysList = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe'
    ];
    for (const s of sysList) {
        if (fs.existsSync(s)) return s;
    }
    return null;
}

function parseArgs() {
    const args = process.argv.slice(2);
    const params = {};
    for (let i = 0; i < args.length; i++) {
        if (args[i].startsWith('--')) {
            const parts = args[i].substring(2).split('=');
            params[parts[0]] = parts[1] !== undefined ? parts[1] : (args[i + 1] || true);
        }
    }
    return params;
}

async function run() {
    const params = parseArgs();
    const startDate = params.startDate || ''; // YYYY-MM-DD
    const endDate = params.endDate || startDate;
    let cookieString = params.cookies || '';
    if (params.cookieFile && fs.existsSync(params.cookieFile)) {
        try {
            cookieString = fs.readFileSync(params.cookieFile, 'utf8');
        } catch(e) {}
    }
    const outputJsonFile = params.output || path.resolve(__dirname, '../../../storage/app/crawler_result_' + Date.now() + '.json');

    // Dynamic require playwright from local node_modules
    let chromium;
    try {
        chromium = require('playwright').chromium;
    } catch(e) {
        chromium = require(path.resolve(__dirname, '../../../node_modules/playwright')).chromium;
    }

    const exe = findChromiumExecutable();
    const browser = await chromium.launch({
        headless: true,
        executablePath: exe || undefined,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--disable-blink-features=AutomationControlled',
            '--window-size=1600,1000'
        ]
    });

    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        viewport: { width: 1600, height: 1000 },
        locale: 'th-TH',
        timezoneId: 'Asia/Bangkok'
    });

    // Parse cookies
    const cookiesToAdd = [];
    if (cookieString) {
        const pairs = cookieString.split(';');
        for (const p of pairs) {
            const parts = p.trim().split('=');
            if (parts.length >= 2) {
                const name = parts[0].trim();
                const val = parts.slice(1).join('=').trim();
                if (name && val) {
                    cookiesToAdd.push({ name, value: val, domain: 'eclaim.nhso.go.th', path: '/' });
                    cookiesToAdd.push({ name, value: val, domain: 'iam.nhso.go.th', path: '/' });
                }
            }
        }
        await context.addCookies(cookiesToAdd);
    }

    // Add init script for atob
    await context.addInitScript(() => {
        const origAtob = window.atob;
        window.atob = function(str) {
            try {
                let base64 = str.replace(/-/g, '+').replace(/_/g, '/');
                while (base64.length % 4) { base64 += '='; }
                return origAtob(base64);
            } catch(e) {
                return origAtob(str);
            }
        };
    });

    const page = await context.newPage();
    page.setDefaultTimeout(40000);

    const capturedRows = [];
    page.on('response', async (resp) => {
        try {
            const url = resp.url();
            if (url.includes('/api/') && (url.includes('m-registers') || url.includes('search'))) {
                const ct = resp.headers()['content-type'] || '';
                if (ct.includes('application/json')) {
                    const json = await resp.json();
                    if (json && json.data && Array.isArray(json.data)) {
                        capturedRows.push(...json.data);
                    }
                }
            }
        } catch(e) {}
    });

    console.log('Navigating to https://eclaim.nhso.go.th/Client/home...');
    await page.goto('https://eclaim.nhso.go.th/Client/home', { waitUntil: 'networkidle', timeout: 45000 });
    await page.waitForTimeout(3000);

    let currentUrl = page.url();
    if (currentUrl.includes('/login') || currentUrl.includes('iam.nhso.go.th')) {
        try {
            const closeBtn = page.locator('.ant-modal-close, button:has-text("ปิด")').first();
            if (await closeBtn.count() > 0) {
                await closeBtn.click();
                await page.waitForTimeout(1000);
            }
        } catch(e) {}
        const ssoLink = page.locator('a[href*="iam.nhso.go.th"], .ant-btn:has-text("เข้าสู่ระบบ")').first();
        if (await ssoLink.count() > 0) {
            await ssoLink.click();
            await page.waitForTimeout(6000);
        }
        currentUrl = page.url();
    }

    if (currentUrl.includes('/login') || currentUrl.includes('iam.nhso.go.th')) {
        console.log('SESSION_EXPIRED');
        fs.writeFileSync(outputJsonFile, JSON.stringify({ success: false, error: 'SESSION_EXPIRED', message: 'Session e-Claim หมดอายุ กรุณาสแกน ThaiD เพื่อเข้าสู่ระบบ' }));
        await browser.close();
        process.exit(0);
    }

    // Wait for Client/home to render
    await page.waitForSelector('.ant-radio-group, button:has-text("ค้นหา")', { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(1000);

    // On Client/home, select "ทุกรายการ" and enter date range
    try {
        // 1. Select "ทุกรายการ" (recordOwner: all)
        console.log('Selecting "ทุกรายการ"...');
        await page.evaluate(() => {
            const r = document.querySelector('input[value="all"]') || Array.from(document.querySelectorAll('label')).find(l => l.innerText.includes('ทุกรายการ'));
            if (r) {
                r.click();
                r.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await page.waitForTimeout(500);

        // 2. Format Dates to Thai DD/MM/YYYY
        const formatToTh = (dStr) => {
            if (!dStr) return '';
            if (dStr.includes('/')) return dStr;
            const parts = dStr.split('-');
            if (parts.length === 3) {
                const y = parseInt(parts[0], 10) > 2400 ? parseInt(parts[0], 10) : parseInt(parts[0], 10) + 543;
                return `${parts[2].padStart(2, '0')}/${parts[1].padStart(2, '0')}/${y}`;
            }
            return dStr;
        };

        const thStart = formatToTh(startDate);
        const thEnd = formatToTh(endDate || startDate);

        if (thStart) {
            console.log(`Filling dates: ${thStart} - ${thEnd}`);
            await page.evaluate(({ s, e }) => {
                const inputs = Array.from(document.querySelectorAll('input[placeholder="DD/MM/YYYY"]'));
                if (inputs.length >= 2) {
                    inputs[0].value = s;
                    inputs[0].dispatchEvent(new Event('input', { bubbles: true }));
                    inputs[0].dispatchEvent(new Event('change', { bubbles: true }));

                    inputs[1].value = e;
                    inputs[1].dispatchEvent(new Event('input', { bubbles: true }));
                    inputs[1].dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, { s: thStart, e: thEnd });
            await page.waitForTimeout(500);
        }

        // 3. Click Search Button
        const searchBtn = page.locator('button:has-text("ค้นหา"), .ant-btn-primary:has-text("ค้นหา")').first();
        if (await searchBtn.count() > 0) {
            console.log('Clicking search button...');
            await searchBtn.click();
            await page.waitForTimeout(6000);
        }

        // 4. Scrape all pages
        const allRows = [];
        let pageNum = 1;
        while (pageNum <= 25) {
            const rows = await page.evaluate(() => {
                const list = [];
                document.querySelectorAll('table tbody tr').forEach(tr => {
                    const cols = Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim());
                    if (cols.length >= 6) list.push(cols);
                });
                return list;
            });

            console.log(`Page ${pageNum}: Scraped ${rows.length} rows`);
            allRows.push(...rows);

            const nextBtn = page.locator('.ant-pagination-next:not(.ant-pagination-disabled)').first();
            if (await nextBtn.count() > 0) {
                await nextBtn.click();
                await page.waitForTimeout(3000);
                pageNum++;
            } else {
                break;
            }
        }

        console.log(`TOTAL ROWS EXTRACTED: ${allRows.length}`);
        fs.writeFileSync(outputJsonFile, JSON.stringify({
            success: true,
            total: allRows.length,
            dom_rows: allRows
        }, null, 2));

    } catch (e) {
        console.error('Extraction error:', e);
        fs.writeFileSync(outputJsonFile, JSON.stringify({
            success: false,
            error: e.message,
            dom_rows: []
        }));
    }

    await browser.close();
}

run().catch(err => {
    console.error('Fatal crawler error:', err);
    process.exit(1);
});
