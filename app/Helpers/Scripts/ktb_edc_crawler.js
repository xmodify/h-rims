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
    // If DD/MM/YYYY -> DD-MM-YYYY
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) {
        return dateStr.replace(/\//g, '-');
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
                '--disable-popup-blocking',
                '--disable-infobars',
                '--window-size=1366,768'
            ]
        });

        const downloadedFiles = [];
        const context = await browser.newContext({
            viewport: { width: 1366, height: 768 },
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            acceptDownloads: true
        });

        const handleDownload = async (download) => {
            try {
                const suggestedFilename = download.suggestedFilename() || `edc_ktb_${Date.now()}.zip`;
                const savePath = path.join(outputDir, suggestedFilename);
                await download.saveAs(savePath).catch(() => {});
                if (fs.existsSync(savePath)) {
                    const stats = fs.statSync(savePath);
                    if (stats.size > 0 && !downloadedFiles.some(f => f.path === savePath)) {
                        downloadedFiles.push({
                            name: suggestedFilename,
                            path: savePath,
                            size: stats.size
                        });
                    }
                }
            } catch(e) {}
        };

        context.on('download', handleDownload);

        const page = await context.newPage();
        page.on('download', handleDownload);
        page.setDefaultTimeout(timeoutMs);

        // Native CDP Download Handler (Guarantees Chromium saves downloads directly to outputDir)
        try {
            const client = await context.newCDPSession(page);
            await client.send('Page.setDownloadBehavior', {
                behavior: 'allow',
                downloadPath: outputDir
            });
        } catch(cdpErr) {}

        // Stealth mode & Polyfills for KTB Legacy JS
        await page.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', {
                get: () => undefined
            });

            // Polyfill String.prototype.padLeft / padRight required by KTB validator
            if (!String.prototype.padLeft) {
                String.prototype.padLeft = function (length, character) {
                    return String(this).padStart(length, character || '0');
                };
            }
            if (!String.prototype.padRight) {
                String.prototype.padRight = function (length, character) {
                    return String(this).padEnd(length, character || '0');
                };
            }
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
        await page.waitForTimeout(3000);

        // Inspect all validator methods on the page
        const validatorCode = await page.evaluate(() => {
            const methods = {};
            if (typeof $ !== 'undefined' && $.validator && $.validator.methods) {
                for (const k of Object.keys($.validator.methods)) {
                    if (k.includes('date') || k.includes('period') || k.includes('range')) {
                        methods[k] = $.validator.methods[k].toString();
                    }
                }
            }
            return methods;
        });
        fs.writeFileSync(path.resolve(__dirname, '../../../storage/app/fn_code.json'), JSON.stringify(validatorCode, null, 2));

        // Auto-detect if KTB uses Buddhist Era (พ.ศ.) or Christian Era (ค.ศ.)
        let actualFromDate = fromDateStr;
        let actualToDate = toDateStr;

        const pageYearType = await page.evaluate(() => {
            const inps = Array.from(document.querySelectorAll('input[type="text"]'));
            for (const el of inps) {
                const v = (el.value || el.getAttribute('placeholder') || '').trim();
                if (/25\d{2}/.test(v)) return 'BE';
                if (/20\d{2}/.test(v)) return 'CE';
            }
            return 'AUTO';
        });

        const convertToBuddhistYear = (dStr) => {
            if (/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/.test(dStr)) {
                const parts = dStr.split(/[\/\-]/);
                const num = parseInt(parts[2], 10);
                if (num < 2500) return `${parts[0]}-${parts[1]}-${num + 543}`;
            }
            return dStr;
        };

        if (pageYearType === 'BE') {
            actualFromDate = convertToBuddhistYear(actualFromDate);
            actualToDate = convertToBuddhistYear(actualToDate);
        }

        // 3. Fill Search Criteria using DOM and jQuery
        if (actualFromDate || actualToDate) {
            await page.evaluate((dates) => {
                if (!String.prototype.padLeft) {
                    String.prototype.padLeft = function (length, character) {
                        return String(this).padStart(length, character || '0');
                    };
                }
                if (!String.prototype.padRight) {
                    String.prototype.padRight = function (length, character) {
                        return String(this).padEnd(length, character || '0');
                    };
                }

                window._getyyyyMMddFromStrFormat = function(c, b) {
                    if (!c) return '';
                    var a = c.split(/[\/\-]/);
                    if (a.length < 3) return '';
                    var d = String(a[0]).padStart(2, '0');
                    var m = String(a[1]).padStart(2, '0');
                    var y = String(a[2]).padStart(4, '0');
                    return y + m + d;
                };

                // Update via bootstrap-datepicker if available
                if (typeof $ !== 'undefined') {
                    try {
                        $('.from-date').datepicker('update', dates.from);
                        $('.to-date').datepicker('update', dates.to);
                    } catch(e) {}
                }

                const setField = (sel, val) => {
                    const el = document.querySelector(sel);
                    if (el && val) {
                        el.removeAttribute('readonly');
                        el.value = val;
                        el.setAttribute('value', val);
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                };

                setField('input[name="postDateFrom"]', dates.from);
                setField('input[name="postDateTo"]', dates.to);

                // Auto select company code and service type if unselected
                const selComp = document.querySelector('select[name="searchCompanyCode"], #companyCode');
                if (selComp && selComp.options.length > 0 && selComp.selectedIndex <= 0) {
                    if (selComp.options[0].value === '' && selComp.options.length > 1) {
                        selComp.selectedIndex = 1;
                        selComp.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                const selService = document.querySelector('select[name="searchServiceTypeCode"], #serviceType');
                if (selService && selService.options.length > 0 && selService.selectedIndex <= 0) {
                    if (selService.options[0].value === '' && selService.options.length > 1) {
                        selService.selectedIndex = 1;
                        selService.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }, { from: actualFromDate, to: actualToDate });
        }

        // Click Search Button (#doSearch)
        await page.evaluate(() => {
            window._getyyyyMMddFromStrFormat = function(c, b) {
                if (!c) return '';
                var a = c.split(/[\/\-]/);
                if (a.length < 3) return '';
                var d = String(a[0]).padStart(2, '0');
                var m = String(a[1]).padStart(2, '0');
                var y = String(a[2]).padStart(4, '0');
                return y + m + d;
            };

            if (typeof $ !== 'undefined' && $('#doSearch').length) {
                $('#doSearch').trigger('click');
            } else {
                document.getElementById('doSearch')?.click();
            }
        });

        const searchBtn = page.locator('button#doSearch').first();
        if (await searchBtn.count() > 0) {
            await searchBtn.click({ force: true }).catch(() => {});
        }

        // Wait for KTB Loading Box to appear and then disappear
        await page.waitForTimeout(2000);
        await page.waitForSelector('#loadingBox', { state: 'hidden', timeout: 30000 }).catch(() => {});
        await page.waitForTimeout(4000);

        // Check for any visible validation errors on KTB page
        const pageErrorText = await page.evaluate(() => {
            const errorLabels = Array.from(document.querySelectorAll('label.error, .text-danger, .alert-danger, #errorBox, p.error, span.error'));
            const visibleErrors = errorLabels.filter(el => el.offsetParent !== null && (el.innerText || '').trim().length > 0);
            return visibleErrors.map(el => el.innerText.trim()).join(' | ');
        });

        if (pageErrorText) {
            console.error('KTB_PAGE_ERROR:', pageErrorText);
        }

        // Save debug screenshot after search
        try {
            const debugImgPath = path.resolve(__dirname, '../../../storage/app/ktb_edc_debug.png');
            await page.screenshot({ path: debugImgPath, fullPage: true });
        } catch(e) {}

        // 4. Check results table (Handle DataTables empty states)
        let emptyCell = page.locator('#search_table tbody td.dataTables_empty, #search_table tbody td:has-text("No data"), #search_table tbody td:has-text("ไม่พบข้อมูล")');
        let itemCheckboxes = page.locator('#search_table tbody input[name="hospitalDownload"], #search_table tbody input[type="checkbox"]');
        let checkboxCount = await itemCheckboxes.count();
        let hasEmptyCell = (await emptyCell.count()) > 0;

        // If no records found with Data Date, automatically retry searching with Loaded Date (Option 1)
        if (hasEmptyCell || checkboxCount === 0) {
            console.log('No records found with Data Date. Retrying search with Loaded Date...');
            await page.evaluate((dates) => {
                const dt = document.querySelector('select[name="searchDate"], #dateType');
                if (dt && dt.value !== '1') {
                    dt.value = '1';
                    dt.dispatchEvent(new Event('change', { bubbles: true }));
                    if (typeof $ !== 'undefined') $(dt).val('1').trigger('change');
                }

                const f = document.querySelector('input[name="postDateFrom"]');
                const t = document.querySelector('input[name="postDateTo"]');
                if (f) { f.removeAttribute('readonly'); f.value = dates.from; f.setAttribute('value', dates.from); }
                if (t) { t.removeAttribute('readonly'); t.value = dates.to; t.setAttribute('value', dates.to); }

                if (typeof $ !== 'undefined' && $('#doSearch').length) {
                    $('#doSearch').trigger('click');
                } else {
                    document.getElementById('doSearch')?.click();
                }
            }, { from: actualFromDate, to: actualToDate });

            await page.waitForTimeout(2000);
            await page.waitForSelector('#loadingBox', { state: 'hidden', timeout: 30000 }).catch(() => {});
            await page.waitForTimeout(4000);

            // Re-evaluate table results
            emptyCell = page.locator('#search_table tbody td.dataTables_empty, #search_table tbody td:has-text("No data"), #search_table tbody td:has-text("ไม่พบข้อมูล")');
            itemCheckboxes = page.locator('#search_table tbody input[name="hospitalDownload"], #search_table tbody input[type="checkbox"]');
            checkboxCount = await itemCheckboxes.count();
            hasEmptyCell = (await emptyCell.count()) > 0;
        }

        if (hasEmptyCell || checkboxCount === 0) {
            let msg = `เข้าสู่ระบบสำเร็จ แต่ไม่พบรายการไฟล์รายงาน EDC ในช่วงวันที่ ${fromDateStr || 'ที่เลือก'} ถึง ${toDateStr || 'ที่เลือก'}`;
            if (pageErrorText) {
                msg = `ธนาคารกรุงไทยแจ้ง: ${pageErrorText} (กรุณาปรับช่วงวันที่ไม่เกิน 7 วัน)`;
            }
            outputResult({
                success: true,
                message: msg,
                downloaded_files: []
            });
            await browser.close();
            process.exit(0);
        }

        // Download ZIP directly via in-browser fetch() with authenticated cookies
        const zipBase64 = await page.evaluate(async () => {
            const master = $('input[name="allHospitalDownload"]')[0] || document.querySelector('input[name="allHospitalDownload"]');
            if (master) {
                master.checked = true;
                if (typeof hospitalInfo !== 'undefined' && typeof hospitalInfo.selectAllHospitalDownload === 'function') {
                    hospitalInfo.selectAllHospitalDownload(master);
                }
            }
            $(':checkbox[name^="chBoxDownload"]').prop('checked', true);

            const formData = new URLSearchParams();
            formData.append('tab', 'HOSPITAL');
            $(':checkbox[name^="chBoxDownload"]:checked').each(function() {
                const val = $(this).val();
                if (val && val !== '1') {
                    formData.append('chBoxDownload', val);
                }
            });

            const actionUrl = $('#serviceType').val() === 'H2' ? 'HealthcareDownloadNhso' : 'HealthcareDownload';

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formData.toString()
                });

                if (!response.ok) {
                    return { success: false, error: `HTTP ${response.status} ${response.statusText}` };
                }

                const buffer = await response.arrayBuffer();
                let binary = '';
                const bytes = new Uint8Array(buffer);
                const len = bytes.byteLength;
                for (let i = 0; i < len; i++) {
                    binary += String.fromCharCode(bytes[i]);
                }
                const b64 = btoa(binary);

                // Get suggested filename from Content-Disposition header if present
                const disp = response.headers.get('content-disposition') || '';
                let fname = '';
                const m = disp.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                if (m && m[1]) {
                    fname = m[1].replace(/['"]/g, '').trim();
                }

                return {
                    success: true,
                    filename: fname || `DWN_HOSPITAL_${Date.now()}.zip`,
                    base64: b64,
                    byteLength: len
                };
            } catch(err) {
                return { success: false, error: err.message };
            }
        });

        if (zipBase64 && zipBase64.success && zipBase64.base64 && zipBase64.byteLength > 0) {
            const fileName = zipBase64.filename || `DWN_HOSPITAL_${Date.now()}.zip`;
            const savePath = path.join(outputDir, fileName);
            fs.writeFileSync(savePath, Buffer.from(zipBase64.base64, 'base64'));
            if (fs.existsSync(savePath)) {
                const stats = fs.statSync(savePath);
                if (stats.size > 0) {
                    downloadedFiles.push({
                        name: fileName,
                        path: savePath,
                        size: stats.size
                    });
                }
            }
        }

        // Also check if any files were downloaded/saved to outputDir
        if (fs.existsSync(outputDir)) {
            const filesInDir = fs.readdirSync(outputDir);
            for (const file of filesInDir) {
                const fullP = path.join(outputDir, file);
                const stats = fs.statSync(fullP);
                if (stats.isFile() && stats.size > 0 && !downloadedFiles.some(f => f.path === fullP)) {
                    downloadedFiles.push({
                        name: file,
                        path: fullP,
                        size: stats.size
                    });
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
