const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const cookies = JSON.parse(fs.readFileSync(path.join(__dirname, 'cookies_for_playwright.json'), 'utf-8'));

    const browser = await chromium.launch({
        headless: true,
        args: [
            '--disable-blink-features=AutomationControlled',
            '--no-sandbox',
            '--disable-setuid-sandbox',
        ]
    });

    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        viewport: { width: 1400, height: 900 },
        acceptDownloads: true
    });

    await context.addCookies(cookies);
    const page = await context.newPage();

    page.on('response', resp => {
        if (resp.url().includes('dmis') || resp.url().includes('report') || resp.url().includes('export')) {
            console.log(`[HTTP RESP ${resp.status()}] ${resp.url()}`);
        }
    });

    await page.addInitScript(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    });

    console.log("1. เข้าสู่ระบบ SMT...");
    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'networkidle', timeout: 30000 });

    const loginBtn = await page.waitForSelector('button:has-text("เข้าสู่ระบบผ่าน OSS สปสช."), :text("เข้าสู่ระบบผ่าน OSS สปสช.")', { timeout: 8000 }).catch(() => null);
    if (loginBtn) {
        await loginBtn.click();
        await page.waitForTimeout(6000);
    }

    console.log("2. อยู่ที่หน้าหลัก:", page.url());

    // Click card "รายงานการโอนเงินงบกองทุน"
    console.log("3. คลิกการ์ด รายงานการโอนเงินงบกองทุน...");
    const card = await page.waitForSelector(':text("รายงานการโอนเงินงบกองทุน")', { timeout: 15000 }).catch(() => null);
    if (card) {
        await card.click();
        await page.waitForTimeout(5000);
        console.log("URL หลังคลิกการ์ด:", page.url());
        await page.screenshot({ path: path.join(__dirname, 'budget_summary_page.png') });
    }

    // Look for Search button
    console.log("4. ค้นหารายการโอนเงิน...");
    const searchBtn = await page.waitForSelector('button:has-text("ค้นหา"), :text("ค้นหา")', { timeout: 10000 }).catch(() => null);
    if (searchBtn) {
        await searchBtn.click();
        await page.waitForTimeout(6000);
        await page.screenshot({ path: path.join(__dirname, 'search_results_page.png') });
        console.log("Screenshot saved to scratch/search_results_page.png");
    }

    // Look for Batch 3270 row or link
    console.log("5. กำลังหาแถว Batch 3270...");
    const batchRow = await page.waitForSelector('tr:has-text("3270"), td:has-text("3270")', { timeout: 15000 }).catch(() => null);
    if (batchRow) {
        console.log("🎉 พบแถว Batch 3270!");
        // Look for open in new icon in that row
        const openBtn = await page.locator('tr:has-text("3270")').locator('mat-icon:has-text("open_in_new"), button, a').first();
        if (await openBtn.count() > 0) {
            console.log("คลิกปุ่มเปิดรายละเอียด...");
            const [newPage] = await Promise.all([
                context.waitForEvent('page', { timeout: 20000 }).catch(() => null),
                openBtn.click(),
            ]);
            if (newPage) {
                console.log("🎉 หน้าต่างใหม่เปิดขึ้นมาแล้ว! URL:", newPage.url());
                await newPage.waitForTimeout(6000);
                await newPage.screenshot({ path: path.join(__dirname, 'new_tab_detail.png') });
            } else {
                console.log("หน้าต่างใหม่ไม่เปิดขึ้นมา หรือเปิดในหน้าเดิม URL:", page.url());
                await page.screenshot({ path: path.join(__dirname, 'after_click_3270.png') });
            }
        }
    } else {
        console.log("ไม่พบแถว 3270 ในหน้านี้");
    }

    await browser.close();
})();
