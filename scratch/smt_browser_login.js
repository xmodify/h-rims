const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log("==================================================");
console.log("Smart Money Transfer - ThaiD Login & Sync Tool");
console.log("==================================================");

(async () => {
    // Launch visible browser on user's screen
    const browser = await chromium.launch({
        headless: false,
        args: ['--start-maximized']
    });

    const context = await browser.newContext({
        viewport: null,
        acceptDownloads: true
    });

    const page = await context.newPage();

    console.log("1. กำลังเปิดหน้า Login ของ Smart Money Transfer...");
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 45000 });

    console.log("2. กำลังคลิกปุ่มเข้าสู่ระบบด้วย ThaiD...");
    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช."), :text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 15000 }).catch(() => null);
    if (loginBtn) {
        await loginBtn.click();
    }

    console.log("3. กำลังนำทางไปยังหน้า QR Code ของ ThaiD...");
    await page.waitForTimeout(3000);

    // If on SSO page, click ThaiD button to reveal QR code
    const thaidBtn = await page.waitForSelector('text="ThaiD"', { timeout: 15000 }).catch(() => null);
    if (thaidBtn) {
        await thaidBtn.click();
        console.log("คลิกปุ่ม ThaiD เพื่อแสดง QR Code เรียบร้อย");
    }

    console.log("\n==================================================");
    console.log(">>> กรุณาสแกน QR Code บนหน้าจอด้วยแอปพลิเคชัน ThaiD บนมือถือ <<<");
    console.log("==================================================\n");
    console.log("กำลังรอการสแกนและยืนยันตัวตนสำเร็จ (ให้เวลา 3 นาที)...\n");

    // Poll for token in localStorage
    let token = null;
    let attempts = 0;
    const maxAttempts = 180; // 3 minutes

    while (!token && attempts < maxAttempts) {
        await page.waitForTimeout(1000);
        attempts++;
        if (attempts % 15 === 0) {
            console.log(`กำลังรอสแกน ThaiD... (${attempts}/${maxAttempts} วินาที)`);
        }
        try {
            if (page.url().includes('smt.nhso.go.th')) {
                const rawToken = await page.evaluate(() => {
                    return localStorage.getItem('ngx-webstorage|token');
                });
                if (rawToken && rawToken !== 'null' && rawToken !== '""') {
                    try {
                        token = JSON.parse(rawToken);
                    } catch(e) {
                        token = rawToken;
                    }
                    break;
                }
            }
        } catch (e) {}
    }

    if (!token) {
        console.log("หมดเวลารอ หรือไม่พบ Token กรุณาลองใหม่อีกครั้ง");
        await browser.close();
        process.exit(1);
    }

    console.log("\n==================================================");
    console.log("เข้าสู่ระบบสำเร็จ! ตรวจพบ Token ของ Smart Money Transfer เรียบร้อยแล้ว");
    console.log("Token length:", token.length);

    // Save token to scratch file
    fs.writeFileSync(path.join(__dirname, 'smt_token.json'), JSON.stringify({
        token: token,
        updated_at: new Date().toISOString()
    }, null, 2));

    // Update user in Laravel DB safely
    try {
        const dbOut = execSync(`php scratch/save_token.php`, { encoding: 'utf-8' });
        console.log(dbOut.trim());
    } catch (e) {
        console.log("Error updating DB:", e.message);
    }

    // Helper function to download and import
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

                // Trigger PHP import script to import into smart_money_details
                console.log(`กำลังนำเข้าข้อมูลคนไข้เข้าสู่ smart_money_details ในฐานข้อมูล...`);
                const parserScript = path.join(__dirname, '../tools/smt/parse_detail_excel.php');
                const importOutput = execSync(`php "${parserScript}" "${downloadPath}" "${roundNo}"`, { encoding: 'utf-8' });
                console.log(importOutput.trim());

                // Remove temporary downloaded file immediately (zero storage policy)
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
