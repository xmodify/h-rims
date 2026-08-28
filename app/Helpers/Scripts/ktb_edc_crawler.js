/**
 * KTB Corporate Online EDC Crawler (Playwright Script)
 * Automates login, navigation, report search, and downloading text/zip files.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

function parseArgs() {
    const args = process.argv.slice(2);
    let configFile = null;
    for (let i = 0; i < args.length; i++) {
        if (args[i] === '--config' && args[i + 1]) {
            configFile = args[i + 1];
            break;
        }
    }

    if (configFile && fs.existsSync(configFile)) {
        try {
            return JSON.parse(fs.readFileSync(configFile, 'utf8'));
        } catch (e) {
            console.error('Error reading config file:', e);
        }
    }

    return {};
}

function formatDateForKtb(dateStr) {
    if (!dateStr) return '';
    // If YYYY-MM-DD -> DD/MM/YYYY
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        const [y, m, d] = dateStr.split('-');
        return `${d}/${m}/${y}`;
    }
    // If DD-MM-YYYY -> DD/MM/YYYY
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) {
        return dateStr.replace(/-/g, '/');
    }
    return dateStr;
}

function outputResult(result) {
    console.log('<<<JSON_START>>>');
    console.log(JSON.stringify(result));
    console.log('<<<JSON_END>>>');
}

function findChromiumExecutable() {
    const fs = require('fs');
    const path = require('path');
    const { chromium } = require('playwright');
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
            if (fH) {
                if (process.platform !== 'win32') {
                    try { fs.chmodSync(fH, 0o755); } catch(e) {}
                }
                return fH;
            }
            const fC = rec(dir, cTarget);
            if (fC) {
                if (process.platform !== 'win32') {
                    try { fs.chmodSync(fC, 0o755); } catch(e) {}
                }
                return fC;
            }
        }
    } catch(e) {}
    try {
        const def = chromium.executablePath();
        if (def && fs.existsSync(def)) return def;
    } catch(e) {}
    const sysList = process.platform === 'win32' ? [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe'
    ] : [
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser'
    ];
    for (const s of sysList) {
        if (fs.existsSync(s)) return s;
    }
    return null;
}

async function launchBrowser(options) {
    const { chromium } = require('playwright');
    const exe = findChromiumExecutable();
    const opts = { ...options };
    if (exe) {
        opts.executablePath = exe;
    }
    return await chromium.launch(opts);
}

async function run() {
    const config = parseArgs();
    const companyId = (config.company_id || '').trim();
    const userId = (config.user_id || '').trim();
    const password = (config.password || '').trim();
    const fromDateStr = formatDateForKtb((config.from_date || '').trim());
    const toDateStr = formatDateForKtb((config.to_date || '').trim());
    const outputDir = config.output_dir || path.join(__dirname, 'output');
    const isHeadless = config.headless !== false;
    const timeoutMs = config.timeout || 60000;

    if (!companyId || !userId || !password) {
        outputResult({
            success: false,
            message: 'ข้อมูล Company ID, User ID หรือ Password ของกรุงไทยไม่ครบถ้วน กรุณาตั้งค่าใน Main Setting'
        });
        process.exit(1);
    }

    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    let browser = null;
    try {
        browser = await launchBrowser({
            headless: isHeadless,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-blink-features=AutomationControlled',
                '--disable-infobars',
                '--window-size=1366,768'
            ]
        });

        const context = await browser.newContext({
            viewport: { width: 1366, height: 768 },
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            acceptDownloads: true
        });

        const page = await context.newPage();
        page.setDefaultTimeout(timeoutMs);

        // Stealth mode
        await page.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', {
                get: () => undefined
            });
        });

        // 1. Navigate to KTB Login Page
        await page.goto('https://www.bizgrowing.krungthai.com/government/', {
            waitUntil: 'domcontentloaded',
            timeout: timeoutMs
        });

        await page.waitForTimeout(1000);

        // Fill Company ID
        const companyInput = page.locator('input[name="companyId"], input[id*="company" i], input[placeholder*="Company" i]').first();
        if (await companyInput.count() > 0) {
            await companyInput.fill(companyId);
        } else {
            const firstText = page.locator('input[type="text"]').first();
            await firstText.fill(companyId);
        }

        // Fill User ID
        const userInput = page.locator('input[name="username"], input[id*="user" i], input[placeholder*="User" i]').first();
        if (await userInput.count() > 0) {
            await userInput.fill(userId);
        } else {
            const secondText = page.locator('input[type="text"]').nth(1);
            await secondText.fill(userId);
        }

        // Fill Password
        const passInput = page.locator('input[name="password"], #password-field, input[type="password"]').first();
        await passInput.fill(password);

        // Click Login
        const loginBtn = page.locator('#loginButton, input[value="Login"], button:has-text("Login"), button[type="submit"]').first();
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: timeoutMs }).catch(() => {}),
            loginBtn.click()
        ]);

        await page.waitForTimeout(2000);

        // Check if login failed
        const errorAlert = page.locator('.alert-danger, .error-msg, .text-danger, :text("Invalid"), :text("รหัสผ่านไม่ถูกต้อง"), :text("ไม่สามารถเข้าสู่ระบบได้")').first();
        if (await errorAlert.count() > 0 && await errorAlert.isVisible()) {
            const errText = await errorAlert.innerText();
            outputResult({
                success: false,
                message: 'เข้าสู่ระบบธนาคารกรุงไทยล้มเหลว: ' + errText.trim()
            });
            await browser.close();
            process.exit(1);
        }

        // 2. Navigate to Healthcare Download Page & Trigger Menu
        const targetUrl = 'https://www.bizgrowing.krungthai.com/ktbgovHealthCare/main';
        if (!page.url().includes('ktbgovHealthCare')) {
            await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: timeoutMs }).catch(() => {});
            await page.waitForTimeout(2000);
        }

        // Trigger Healthcare Download portal menu
        await page.evaluate(() => {
            if (typeof loadPortal !== 'undefined' && typeof downloadMenu !== 'undefined') {
                loadPortal.call(downloadMenu, "DL", "DL001");
            }
        });
        await page.waitForTimeout(2500);

        // 3. Fill Search Criteria using DOM and jQuery
        if (fromDateStr || toDateStr) {
            await page.evaluate((dates) => {
                const fillDateInput = (selectors, val) => {
                    if (!val) return;
                    for (const sel of selectors) {
                        const el = document.querySelector(sel);
                        if (el) {
                            el.value = val;
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                            if (typeof $ !== 'undefined' && $(sel).length) {
                                try { $(sel).val(val).trigger('change'); } catch(e) {}
                            }
                        }
                    }
                };

                fillDateInput(['input[name="postDateFrom"]', 'input#postDateFrom', 'input[name="dateFrom"]', 'input[name="fromDate"]'], dates.from);
                fillDateInput(['input[name="postDateTo"]', 'input#postDateTo', 'input[name="dateTo"]', 'input[name="toDate"]'], dates.to);
            }, { from: fromDateStr, to: toDateStr });
        }

        // Click Search Button
        const searchBtn = page.locator('button#doSearch, button:has-text("Search"), input[value="Search"], #btnSearch, .btn-search').first();
        if (await searchBtn.count() > 0) {
            await searchBtn.click();
            await page.waitForTimeout(4000);
        }

        // 4. Check results table (Handle DataTables empty states)
        const emptyCell = page.locator('#search_table tbody td.dataTables_empty, #search_table tbody td:has-text("No data"), #search_table tbody td:has-text("ไม่พบข้อมูล")');
        const itemCheckboxes = page.locator('#search_table tbody input[name="hospitalDownload"], #search_table tbody input[type="checkbox"]');
        const checkboxCount = await itemCheckboxes.count();
        const hasEmptyCell = (await emptyCell.count()) > 0;

        if (hasEmptyCell || checkboxCount === 0) {
            outputResult({
                success: true,
                message: `เข้าสู่ระบบสำเร็จ แต่ไม่พบรายการไฟล์รายงาน EDC ในช่วงวันที่ ${fromDateStr || 'ที่เลือก'} ถึง ${toDateStr || 'ที่เลือก'}`,
                downloaded_files: []
            });
            await browser.close();
            process.exit(0);
        }

        // Select all checkboxes in table
        await page.evaluate(() => {
            const selectAll = document.querySelector('input[name="allHospitalDownload"]');
            if (selectAll) {
                selectAll.checked = true;
                selectAll.dispatchEvent(new Event('click', { bubbles: true }));
                selectAll.dispatchEvent(new Event('change', { bubbles: true }));
                if (typeof hospitalInfo !== 'undefined' && hospitalInfo.selectAllHospitalDownload) {
                    hospitalInfo.selectAllHospitalDownload(selectAll);
                }
            }
            const itemChecks = document.querySelectorAll('#search_table tbody input[name="hospitalDownload"], #search_table tbody input[type="checkbox"]');
            itemChecks.forEach(cb => {
                if (!cb.checked) {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('click', { bubbles: true }));
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
        await page.waitForTimeout(1000);

        // 5. Click Download button inside dataTable wrapper & intercept download file
        const downloadedFiles = [];
        const downloadBtn = page.locator('#search_table_wrapper .dt-buttons a.dt-button:has-text("Download"), button:has-text("Download"), a:has-text("Download")').first();

        if (await downloadBtn.count() > 0) {
            try {
                const [download] = await Promise.all([
                    page.waitForEvent('download', { timeout: 25000 }).catch(() => null),
                    downloadBtn.click()
                ]);

                if (download) {
                    const failReason = await download.failure().catch(() => null);
                    if (!failReason) {
                        const suggestedFilename = download.suggestedFilename() || `edc_ktb_${Date.now()}.zip`;
                        const savePath = path.join(outputDir, suggestedFilename);
                        await download.saveAs(savePath).catch(e => console.error('saveAs err:', e.message));

                        if (fs.existsSync(savePath)) {
                            const stats = fs.statSync(savePath);
                            downloadedFiles.push({
                                name: suggestedFilename,
                                path: savePath,
                                size: stats.size
                            });
                        }
                    }
                }
            } catch (dlErr) {
                console.error('Download handling error:', dlErr.message);
            }
        }

        // Also check if any files were downloaded/saved to outputDir
        if (fs.existsSync(outputDir)) {
            const filesInDir = fs.readdirSync(outputDir);
            for (const file of filesInDir) {
                if (!downloadedFiles.some(f => f.name === file)) {
                    const fullP = path.join(outputDir, file);
                    const stats = fs.statSync(fullP);
                    if (stats.isFile() && stats.size > 0) {
                        downloadedFiles.push({
                            name: file,
                            path: fullP,
                            size: stats.size
                        });
                    }
                }
            }
        }

        if (downloadedFiles.length === 0) {
            outputResult({
                success: true,
                message: `เข้าสู่ระบบสำเร็จ แต่ไม่พบไฟล์รายงานให้ดาวน์โหลดในช่วงวันที่เลือก (อาจยังไม่มีการทำรายการในวันดังกล่าว)`,
                downloaded_files: []
            });
            await browser.close();
            process.exit(0);
        }

        outputResult({
            success: true,
            message: `ดาวน์โหลดไฟล์รายงานสำเร็จ พบทั้งหมด ${downloadedFiles.length} ไฟล์`,
            downloaded_files: downloadedFiles
        });

        await browser.close();
    } catch (err) {
        if (browser) {
            try { await browser.close(); } catch (e) {}
        }
        outputResult({
            success: false,
            message: 'เกิดข้อผิดพลาดในการรัน Crawler: ' + err.message
        });
        process.exit(1);
    }
}

run();
