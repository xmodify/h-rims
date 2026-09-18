const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const tokenData = JSON.parse(fs.readFileSync(path.join(__dirname, 'smt_token.json'), 'utf-8'));
    const token = tokenData.token;

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        acceptDownloads: true
    });
    const page = await context.newPage();

    await page.goto('https://smt.nhso.go.th/smtf/#/login', { waitUntil: 'domcontentloaded' });

    // Set localStorage token
    await page.evaluate((token) => {
        localStorage.setItem('ngx-webstorage|token', JSON.stringify(token));
    }, token);

    // Test 1: Fetch DMIS Excel directly in browser context
    console.log("=== Testing fetch /dmis/export/dmis in browser context ===");
    const dmisPayload = {
        refDocNo: "DCKD6931080031",
        vendorId: "10989",
        postingDate: "25690915",
        sfundCd: "13",
        efundCd: "1",
        batchNo: "3270",
        mophId: "1102050101.216/217"
    };

    const dmisResult = await page.evaluate(async ({ token, payload }) => {
        try {
            const res = await fetch('/smtf/api/dmis/export/dmis', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json, text/plain, */*'
                },
                body: JSON.stringify(payload)
            });
            const blob = await res.blob();
            const text = await blob.text();
            return {
                status: res.status,
                size: blob.size,
                isZip: text.startsWith('PK'),
                sample: text.substring(0, 150)
            };
        } catch (e) {
            return { error: e.message };
        }
    }, { token, payload: dmisPayload });

    console.log("DMIS Result:", dmisResult);

    // Test 2: Fetch LGO-HD Excel in browser context
    console.log("\n=== Testing fetch /adapter/export-excel-lgo-hd-by-person in browser context ===");
    const lgohdPayload = {
        refDocNo: "LGO-HD69-M11",
        vendorId: "10989",
        recId: "72668",
        mophId: "1102050102.801/802"
    };

    const lgohdResult = await page.evaluate(async ({ token, payload }) => {
        try {
            const res = await fetch('/smtf/api/adapter/export-excel-lgo-hd-by-person', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json, text/plain, */*'
                },
                body: JSON.stringify(payload)
            });
            const blob = await res.blob();
            const text = await blob.text();
            return {
                status: res.status,
                size: blob.size,
                isZip: text.startsWith('PK'),
                sample: text.substring(0, 150)
            };
        } catch (e) {
            return { error: e.message };
        }
    }, { token, payload: lgohdPayload });

    console.log("LGO-HD Result:", lgohdResult);

    await browser.close();
})();
