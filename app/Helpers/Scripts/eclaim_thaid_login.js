/**
 * E-Claim ThaiD Login Worker (Playwright Script)
 * Automates opening NHSO IAM -> DOPA ThaiD QR Code -> Capturing QR Code -> Waiting for Mobile Scan -> Extracting Cookies.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

function parseArgs() {
    const args = process.argv.slice(2);
    let sessionId = null;
    for (let i = 0; i < args.length; i++) {
        if (args[i].startsWith('--sessionId=')) {
            sessionId = args[i].split('=')[1];
        } else if (args[i] === '--sessionId' && args[i + 1]) {
            sessionId = args[i + 1];
        }
    }
    return { sessionId: sessionId || 'default_' + Date.now() };
}

function updateSessionState(sessionFile, data) {
    try {
        const existing = fs.existsSync(sessionFile) ? JSON.parse(fs.readFileSync(sessionFile, 'utf8')) : {};
        const updated = { ...existing, ...data, updated_at: Date.now() };
        fs.writeFileSync(sessionFile, JSON.stringify(updated, null, 2), 'utf8');
    } catch (e) {
        console.error('Error writing session state:', e);
    }
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
    const { sessionId } = parseArgs();
    const storageDir = path.resolve(__dirname, '../../../storage/app');
    if (!fs.existsSync(storageDir)) {
        fs.mkdirSync(storageDir, { recursive: true });
    }
    const sessionFile = path.join(storageDir, `thaid_session_${sessionId}.json`);

    updateSessionState(sessionFile, {
        status: 'INITIALIZING',
        session_id: sessionId,
        message: 'กำลังเริ่มต้นเบราว์เซอร์ Chromium...'
    });

    let browser = null;
    try {
        browser = await launchBrowser({
            headless: true,
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
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            viewport: { width: 1366, height: 768 },
            locale: 'th-TH',
            timezoneId: 'Asia/Bangkok'
        });

        await context.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });

        const page = await context.newPage();
        page.setDefaultTimeout(45000);

        // 1. Navigate to e-Claim Portal
        updateSessionState(sessionFile, {
            status: 'CONNECTING_ECLAIM',
            message: 'กำลังเชื่อมต่อไปยังระบบ e-Claim สปสช...'
        });

        await page.goto('https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do', {
            waitUntil: 'domcontentloaded',
            timeout: 45000
        });

        // 2. Click "เข้าสู่ระบบผ่าน OSS สปสช"
        const ossBtn = page.locator('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช")').first();
        if (await ossBtn.count() > 0) {
            updateSessionState(sessionFile, {
                status: 'CONNECTING_IAM',
                message: 'กำลังเข้าสู่ระบบยืนยันตัวตนกลาง (NHSO SSO)...'
            });

            await ossBtn.click();
            await page.waitForURL(url => !url.href.includes('MainWebAction.do') || url.href.includes('iam.nhso.go.th'), { timeout: 15000 }).catch(() => {});
        }

        // 3. Click "ThaiD" Button on IAM page
        const thaidBtn = page.locator(':text("ThaiD"), button:has-text("ThaiD"), a:has-text("ThaiD")').first();
        await thaidBtn.waitFor({ state: 'visible', timeout: 15000 }).catch(() => {});
        if (await thaidBtn.count() > 0) {
            updateSessionState(sessionFile, {
                status: 'REQUESTING_THAID_QR',
                message: 'กำลังร้องขอ QR Code จากระบบ ThaiD (กรมการปกครอง)...'
            });

            await thaidBtn.click();
            await page.waitForURL(url => url.href.includes('imauth.bora.dopa.go.th') || url.href.includes('dopa'), { timeout: 20000 }).catch(() => {});
        }

        // 4. On DOPA ThaiD QR Code Page (imauth.bora.dopa.go.th)
        // Find QR Code image
        const qrImg = page.locator('img[src^="data:image"]').first();
        await qrImg.waitFor({ state: 'visible', timeout: 20000 });

        const qrSrc = await qrImg.getAttribute('src');
        const currentUrl = page.url();
        let refCode = '';
        const mRef = currentUrl.match(/refCode=([a-zA-Z0-9]+)/i);
        if (mRef) {
            refCode = mRef[1];
        } else {
            const pageText = await page.evaluate(() => document.body ? document.body.innerText : '');
            const mTextRef = pageText.match(/หมายเลขอ้างอิง\s*[:：]?\s*([a-zA-Z0-9]+)/u);
            if (mTextRef) refCode = mTextRef[1];
        }

        updateSessionState(sessionFile, {
            status: 'QR_READY',
            qr_image: qrSrc,
            ref_code: refCode,
            expires_in: 120,
            message: 'พร้อมสแกน QR Code ด้วยแอปพลิเคชัน ThaiD'
        });

        console.log(`[Session ${sessionId}] QR Code is ready. Waiting for user to scan...`);

        // 5. Wait for mobile scan authentication (Redirect back to eclaim.nhso.go.th)
        // Check session file periodically if user cancelled
        const scanStartTime = Date.now();
        const maxWaitMs = 180000; // 3 minutes

        while (Date.now() - scanStartTime < maxWaitMs) {
            // Check if cancelled by user
            if (fs.existsSync(sessionFile)) {
                try {
                    const st = JSON.parse(fs.readFileSync(sessionFile, 'utf8'));
                    if (st.status === 'CANCELLED') {
                        console.log(`[Session ${sessionId}] Cancelled by user.`);
                        await browser.close();
                        process.exit(0);
                    }
                } catch (e) {}
            }

            const url = page.url();
            // Check if redirect has reached eclaim main page
            if (url.includes('eclaim.nhso.go.th') && !url.includes('iam.nhso.go.th') && !url.includes('imauth.bora.dopa.go.th')) {
                break;
            }

            await page.waitForTimeout(2000);
        }

        // Wait an extra moment and navigate to Client/home to initialize Vue SPA and fresh ACCESS_TOKEN
        try {
            await page.goto('https://eclaim.nhso.go.th/Client/home', { waitUntil: 'networkidle', timeout: 25000 });
            await page.waitForTimeout(3000);
        } catch(e) {
            console.log('Client/home init note:', e.message);
        }

        // 6. Extract Cookies & User Info
        const cookies = await context.cookies();
        const cookiePairs = [];
        for (const c of cookies) {
            if (c.name && c.value) {
                cookiePairs.push(`${c.name}=${c.value}`);
            }
        }

        // Also check if Vue set localStorage token
        let localTok = '';
        try {
            localTok = await page.evaluate(() => window.localStorage.getItem('token') || '');
            if (localTok) {
                cookiePairs.push(`ACCESS_TOKEN=${localTok}`);
            }
        } catch(e) {}

        const fullCookieString = cookiePairs.join('; ');

        // Check if landing page has user info, hospcode, and cid
        let detectedUser = 'เจ้าหน้าที่ e-Claim';
        let detectedHcode = '';
        let detectedCid = '';

        // Extract from JWT tokens if available
        for (const pair of cookiePairs) {
            const parts = pair.split('=');
            if (parts.length >= 2 && (parts[0] === 'ACCESS_TOKEN' || parts[0] === 'KEYCLOAK_IDENTITY')) {
                const jwtVal = parts.slice(1).join('=');
                if (jwtVal && jwtVal.includes('.')) {
                    try {
                        const jwtParts = jwtVal.split('.');
                        if (jwtParts.length >= 2) {
                            const b64 = jwtParts[1].replace(/-/g, '+').replace(/_/g, '/');
                            const payload = JSON.parse(Buffer.from(b64, 'base64').toString('utf8'));
                            if (payload.hospMain) detectedHcode = payload.hospMain;
                            else if (payload.organize_id) detectedHcode = payload.organize_id;
                            else if (payload.hospCode) detectedHcode = payload.hospCode;

                            if (payload.cid) detectedCid = payload.cid;
                            else if (payload.id_card) detectedCid = payload.id_card;
                            else if (payload.pid) detectedCid = payload.pid;

                            if (payload.nameTh) detectedUser = payload.nameTh;
                            else if (payload.name) detectedUser = payload.name;
                        }
                    } catch(e) {}
                }
            }
        }

        try {
            if (!detectedHcode) {
                const wcRes = await context.request.get('https://eclaim.nhso.go.th/webComponent/checkdata/CheckDataAction.do');
                if (wcRes.ok()) {
                    const wcHtml = await wcRes.text();
                    const mHosp = wcHtml.match(/(?:หน่วยงาน|หน่วยบริการ|สถานพยาบาล)\s*:\s*([^\[<]+)\[(\d{5})\]/u);
                    if (mHosp) {
                        detectedHcode = mHosp[2].trim();
                        if (detectedUser === 'เจ้าหน้าที่ e-Claim') {
                            detectedUser = mHosp[1].trim();
                        }
                    }
                }
            }
        } catch (e) {}

        try {
            const html = await page.content();
            if (detectedUser === 'เจ้าหน้าที่ e-Claim') {
                const mUser = html.match(/(?:ยินดีต้อนรับ|สวัสดี|ชื่อ)\s*[:：]?\s*([^\r\n<\[]+)/u);
                if (mUser && !mUser[1].includes('Audit User') && !mUser[1].includes('SSO')) {
                    detectedUser = mUser[1].trim().replace(/<[^>]*>?/gm, '');
                }
            }
            if (!detectedHcode) {
                const mHosp = html.match(/(?:หน่วยงาน|หน่วยบริการ|สถานพยาบาล|Hospcode|Hcode|รหัส)\s*[:：\-]?\s*(\d{5})/u) || html.match(/\b(1\d{4})\b/);
                if (mHosp) {
                    detectedHcode = mHosp[1];
                }
            }
        } catch (e) {}

        if (fullCookieString.includes('JSESSIONID') || fullCookieString.includes('STEEXWDE') || fullCookieString.includes('ACCESS_TOKEN')) {
            updateSessionState(sessionFile, {
                status: 'SUCCESS',
                cookies: fullCookieString,
                user: detectedUser,
                hospcode: detectedHcode,
                cid: detectedCid,
                message: `เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ${detectedUser}`
            });
            console.log(`[Session ${sessionId}] Login success! User: ${detectedUser}, Hospcode: ${detectedHcode}, CID: ${detectedCid}`);
        } else {
            updateSessionState(sessionFile, {
                status: 'FAILED',
                message: 'ไม่พบ Session Token หลังการล็อกอิน'
            });
        }

        await browser.close();
        process.exit(0);

    } catch (err) {
        console.error(`[Session ${sessionId}] Error:`, err);
        updateSessionState(sessionFile, {
            status: 'FAILED',
            message: 'เกิดข้อผิดพลาด: ' + err.message
        });
        if (browser) {
            try { await browser.close(); } catch (e) {}
        }
        process.exit(1);
    }
}

run().catch(err => {
    console.error('Fatal ThaiD Runner error:', err);
    try {
        const { sessionId } = parseArgs();
        const storageDir = path.resolve(__dirname, '../../../storage/app');
        const sessionFile = path.join(storageDir, `thaid_session_${sessionId}.json`);
        updateSessionState(sessionFile, {
            status: 'FAILED',
            message: 'เกิดข้อผิดพลาดในการรันบอท: ' + err.message
        });
    } catch (e) {}
    process.exit(1);
});
