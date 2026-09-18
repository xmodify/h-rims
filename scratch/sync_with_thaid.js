const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const ARTIFACT_DIR = "C:\\Users\\LENOVO\\.gemini\\antigravity-ide\\brain\\44fa3fb6-122a-44ba-b2fa-ccf890c6bab4";

(async () => {
    console.log("==================================================");
    console.log("Smart Money Transfer - ThaiD QR Scanner & Sync");
    console.log("==================================================");

    // Launch visible browser
    const browser = await chromium.launch({
        headless: false,
        args: [
            '--window-size=1280,800',
            '--window-position=50,50'
        ]
    });

    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        acceptDownloads: true
    });

    const page = await context.newPage();

    console.log("1. กำลังเปิดหน้า Login ของ Smart Money Transfer...");
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 45000 });

    console.log("2. กำลังกดปุ่ม 'เข้าสู่ระบบผ่าน OSS สปสช. (ThaiD)'...");
    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช."), :text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 15000 });
    await loginBtn.click();

    console.log("3. กำลังรอหน้า SSO โหลด...");
    await page.waitForTimeout(3000);
    console.log("URL ปัจจุบัน:", page.url());

    console.log("4. กำลังกดปุ่ม 'ThaiD' ในหน้า SSO สปสช. เพื่อเปิด QR Code...");
    const thaidBtn = await page.waitForSelector('a:has-text("ThaiD"), button:has-text("ThaiD"), div:has-text("ThaiD")', { timeout: 15000 });
    await thaidBtn.click();

    console.log("5. กำลังรอ QR Code ของ ThaiD โหลด...");
    await page.waitForTimeout(4000);
    console.log("URL ปัจจุบัน (หน้า QR):", page.url());

    // Save screenshot of QR page to artifact directory and scratch
    const qrArtifactPath = path.join(ARTIFACT_DIR, 'thaid_qr.png');
    const qrScratchPath = path.join(__dirname, 'thaid_qr.png');

    await page.screenshot({ path: qrArtifactPath });
    fs.copyFileSync(qrArtifactPath, qrScratchPath);

    console.log(`[QR_READY] บันทึกภาพ QR Code เรียบร้อย: ${qrArtifactPath}`);
    console.log("\n>>> กรุณาสแกน QR Code ด้วยแอปพลิเคชัน ThaiD บนโทรศัพท์มือถือของคุณ <<<\n");

    // Wait for login to complete (polling for return to SMT or token in storage)
    let token = null;
    let attempts = 0;
    const maxAttempts = 180; // 3 minutes

    while (!token && attempts < maxAttempts) {
        await new Promise(r => setTimeout(r, 1000));
        attempts++;

        if (attempts % 15 === 0) {
            console.log(`กำลังรอการสแกน ThaiD... (${attempts}/${maxAttempts} วินาที)`);
        }

        try {
            // Check if page returned to smt
            const currentUrl = page.url();
            if (currentUrl.includes('smt.nhso.go.th')) {
                const rawToken = await page.evaluate(() => {
                    return localStorage.getItem('ngx-webstorage|token');
                });
                if (rawToken && rawToken !== 'null' && rawToken !== '""') {
                    try {
                        token = JSON.parse(rawToken);
                    } catch (e) {
                        token = rawToken;
                    }
                    break;
                }
            }
        } catch (e) {}
    }

    if (!token) {
        console.log("หมดเวลารอ หรือการสแกนไม่สำเร็จ");
        await browser.close();
        process.exit(1);
    }

    console.log("\n==================================================");
    console.log("เข้าสู่ระบบสำเร็จ! ตรวจพบ Token ของ Smart Money Transfer เรียบร้อยแล้ว");
    console.log("Token length:", token.length);

    // Save token
    fs.writeFileSync(path.join(__dirname, 'smt_token.json'), JSON.stringify({
        token: token,
        updated_at: new Date().toISOString()
    }, null, 2));

    // Update DB
    try {
        const dbOut = execSync(`php scratch/save_token.php`, { encoding: 'utf-8' });
        console.log(dbOut.trim());
    } catch (e) {
        console.log("DB update error:", e.message);
    }

    // Helper to download and import
    async function processRound(roundNo, targetUrl) {
        console.log(`\n--------------------------------------------------`);
        console.log(`กำลังเข้าสู่หน้ารายละเอียดงวด ${roundNo}...`);
        console.log(`URL: ${targetUrl}`);
        
        await page.goto(targetUrl, { waitUntil: 'networkidle', timeout: 45000 }).catch(() => {});
        await page.waitForTimeout(4000);

        const exportBtn = await page.waitForSelector('button:has-text("Export Excel"), mat-chip:has-text("Export Excel"), :text("Export Excel")', { timeout: 20000 }).catch(() => null);

        if (exportBtn) {
            console.log(`พบปุ่ม Export Excel ของงวด ${roundNo} กำลังเริ่มดาวน์โหลด...`);
            try {
                const [download] = await Promise.all([
                    page.waitForEvent('download', { timeout: 30000 }),
                    exportBtn.click(),
                ]);
                const downloadPath = path.join(__dirname, download.suggestedFilename());
                await download.saveAs(downloadPath);
                const fileSize = fs.statSync(downloadPath).size;
                console.log(`ดาวน์โหลดไฟล์สำเร็จ: ${download.suggestedFilename()} (${fileSize} bytes)`);

                // Run PHP import
                console.log(`กำลังนำเข้าข้อมูลคนไข้เข้าสู่ smart_money_details ในฐานข้อมูล...`);
                const importOutput = execSync(`php scratch/import_detail_excel.php "${downloadPath}" "${roundNo}"`, { encoding: 'utf-8' });
                console.log(importOutput.trim());

                // Delete immediately
                if (fs.existsSync(downloadPath)) {
                    fs.unlinkSync(downloadPath);
                    console.log(`ลบไฟล์ชั่วคราว ${download.suggestedFilename()} เรียบร้อย (ไม่เก็บไฟล์ที่ server ตามนโยบาย)`);
                }
            } catch (err) {
                console.log(`เกิดข้อผิดพลาดในการดาวน์โหลดงวด ${roundNo}:`, err.message);
            }
        } else {
            console.log(`ไม่พบปุ่ม Export Excel สำหรับงวด ${roundNo}`);
        }
    }

    // 1. Process LGO-HD69-M11
    const lgohdUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-lgohd/LGO-HD69-M11/10989/72668?mophId=1102050102.801%2F802';
    await processRound('LGO-HD69-M11', lgohdUrl);

    // 2. Process DCKD6931080031
    const dmisUrl = 'https://smt.nhso.go.th/smtf/#/home/budget/summary-detail-dmis/DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216%2F217';
    await processRound('DCKD6931080031', dmisUrl);

    console.log("\n==================================================");
    console.log("เสร็จสิ้นกระบวนการทั้งหมดเรียบร้อยแล้วครับ!");
    console.log("==================================================");

    await page.waitForTimeout(3000);
    await browser.close();
})();
