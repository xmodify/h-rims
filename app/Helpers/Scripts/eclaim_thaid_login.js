process.env.PLAYWRIGHT_BROWSERS_PATH = process.env.PLAYWRIGHT_BROWSERS_PATH || '0';
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
        browser = await chromium.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
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

            await Promise.all([
                page.waitForNavigation({ timeout: 45000 }).catch(() => {}),
                ossBtn.click()
            ]);
        }

        await page.waitForTimeout(2000);

        // 3. Click "ThaiD" Button on IAM page
        const thaidBtn = page.locator(':text("ThaiD"), button:has-text("ThaiD"), a:has-text("ThaiD")').first();
        if (await thaidBtn.count() > 0) {
            updateSessionState(sessionFile, {
                status: 'REQUESTING_THAID_QR',
                message: 'กำลังร้องขอ QR Code จากระบบ ThaiD (กรมการปกครอง)...'
            });

            await Promise.all([
                page.waitForNavigation({ timeout: 45000 }).catch(() => {}),
                thaidBtn.click()
            ]);
        }

        // 4. On DOPA ThaiD QR Code Page (imauth.bora.dopa.go.th)
        await page.waitForTimeout(2000);

        // Find QR Code image
        const qrImg = page.locator('img[src^="data:image"]').first();
        await qrImg.waitFor({ state: 'visible', timeout: 30000 });

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

        // Wait an extra moment for session cookies to be fully set
        await page.waitForTimeout(3000);

        // 6. Extract Cookies & User Info
        const cookies = await context.cookies();
        const cookiePairs = [];
        for (const c of cookies) {
            if (c.name && c.value) {
                cookiePairs.push(`${c.name}=${c.value}`);
            }
        }
        const fullCookieString = cookiePairs.join('; ');

        // Check if landing page has user info
        let detectedUser = 'เจ้าหน้าที่ e-Claim';
        let detectedHcode = '';
        try {
            const html = await page.content();
            const mUser = html.match(/(?:ยินดีต้อนรับ|สวัสดี|ชื่อ)\s*[:：]?\s*([^\r\n<\[]+)/u);
            if (mUser && !mUser[1].includes('Audit User') && !mUser[1].includes('SSO')) {
                detectedUser = mUser[1].trim().replace(/<[^>]*>?/gm, '');
            }
            const mHosp = html.match(/(?:หน่วยงาน|หน่วยบริการ|สถานพยาบาล|Hospcode|Hcode|รหัส)\s*[:：\-]?\s*(\d{5})/u) || html.match(/\b(1\d{4})\b/);
            if (mHosp) {
                detectedHcode = mHosp[1];
            }
        } catch (e) {}

        if (fullCookieString.includes('JSESSIONID') || fullCookieString.includes('STEEXWDE') || fullCookieString.includes('ACCESS_TOKEN')) {
            updateSessionState(sessionFile, {
                status: 'SUCCESS',
                cookies: fullCookieString,
                user: detectedUser,
                hospcode: detectedHcode,
                message: `เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ${detectedUser}`
            });
            console.log(`[Session ${sessionId}] Login success! Cookies saved.`);
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

run();
