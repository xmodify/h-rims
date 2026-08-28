try {
    const fs = require('fs');
    const path = require('path');
    const { chromium } = require('playwright');
    let hasValidDefault = false;
    try {
        if (fs.existsSync(chromium.executablePath())) {
            hasValidDefault = true;
        }
    } catch (e) {}

    if (!hasValidDefault) {
        const storageBrowserPath = path.resolve(__dirname, '../../../storage/app/playwright_browsers');
        process.env.PLAYWRIGHT_BROWSERS_PATH = storageBrowserPath;
    }
} catch (e) {}

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
    // If YYYY-MM-DD -> DD-MM-YYYY
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        const [y, m, d] = dateStr.split('-');
        return `${d}-${m}-${y}`;
    }
    return dateStr;
}

function outputResult(result) {
    console.log('<<<JSON_START>>>');
    console.log(JSON.stringify(result));
    console.log('<<<JSON_END>>>');
}

async function launchBrowser(options) {
    const fs = require('fs');
    const path = require('path');
    try {
        const { chromium } = require('playwright');
        if (fs.existsSync(chromium.executablePath())) {
            return await chromium.launch(options);
        }
    } catch (e1) {}

    try {
        process.env.PLAYWRIGHT_BROWSERS_PATH = path.resolve(__dirname, '../../../storage/app/playwright_browsers');
        delete require.cache[require.resolve('playwright-core')];
        delete require.cache[require.resolve('playwright')];
        const { chromium: chromiumStorage } = require('playwright');
        return await chromiumStorage.launch(options);
    } catch (e2) {}

    const { chromium } = require('playwright');
    return await chromium.launch(options);
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
                '--disable-blink-features=AutomationControlled'
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

        // 3. Fill Search Criteria using DOM
        if (fromDateStr || toDateStr) {
            await page.evaluate((dates) => {
                const fromEl = document.querySelector('input[name="postDateFrom"]');
                const toEl = document.querySelector('input[name="postDateTo"]');
                if (fromEl && dates.from) {
                    fromEl.value = dates.from;
                    fromEl.dispatchEvent(new Event('input', { bubbles: true }));
                    fromEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (toEl && dates.to) {
                    toEl.value = dates.to;
                    toEl.dispatchEvent(new Event('input', { bubbles: true }));
                    toEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, { from: fromDateStr, to: toDateStr });
        }

        // Click Search Button
        const searchBtn = page.locator('button#doSearch, button:has-text("Search"), input[value="Search"]').first();
        if (await searchBtn.count() > 0) {
            await searchBtn.click();
            await page.waitForTimeout(4000);
        }

        // 4. Check results table
        const tableRows = page.locator('#search_table tbody tr');
        const rowCount = await tableRows.count();

        if (rowCount === 0) {
            outputResult({
                success: true,
                message: `ไม่พบรายการไฟล์รายงาน EDC ในช่วงวันที่ ${fromDateStr} ถึง ${toDateStr}`,
                downloaded_files: []
            });
            await browser.close();
            process.exit(0);
        }

        // Select all checkboxes in table header
        await page.evaluate(() => {
            const selectAll = document.querySelector('input[name="allHospitalDownload"]');
            if (selectAll) {
                selectAll.checked = true;
                if (typeof hospitalInfo !== 'undefined' && hospitalInfo.selectAllHospitalDownload) {
                    hospitalInfo.selectAllHospitalDownload(selectAll);
                }
            }
        });
        await page.waitForTimeout(1000);

        // 5. Click Download button inside dataTable wrapper & intercept download file
        const downloadedFiles = [];
        const downloadBtn = page.locator('#search_table_wrapper .dt-buttons a.dt-button:has-text("Download")').first();

        if (await downloadBtn.count() > 0) {
            const [download] = await Promise.all([
                page.waitForEvent('download', { timeout: 30000 }).catch(() => null),
                downloadBtn.click()
            ]);

            if (download) {
                const suggestedFilename = download.suggestedFilename();
                const savePath = path.join(outputDir, suggestedFilename);
                await download.saveAs(savePath);

                const stats = fs.statSync(savePath);
                downloadedFiles.push({
                    name: suggestedFilename,
                    path: savePath,
                    size: stats.size
                });
            }
        }

        // Also check if any files were downloaded/saved to outputDir
        const filesInDir = fs.readdirSync(outputDir);
        for (const file of filesInDir) {
            if (!downloadedFiles.some(f => f.name === file)) {
                const fullP = path.join(outputDir, file);
                downloadedFiles.push({
                    name: file,
                    path: fullP,
                    size: fs.statSync(fullP).size
                });
            }
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
