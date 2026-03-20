// This content.js is injected into eclaim.nhso.go.th
// It scrapes the data table and sends it to the RiMS backend.

(async function () {
    try {
        let dataToSync = [];

        // Scrape Hospital Code from NHSO E-Claim Header (usually "[5-digit] Hospital Name")
        let hcode = "";
        const headerText = document.querySelector('.ant-layout-header')?.innerText ||
            document.querySelector('.user-info-detail')?.innerText ||
            document.body.innerText;
        const hcodeMatch = headerText.match(/\b\d{5}\b/);
        if (hcodeMatch) {
            hcode = hcodeMatch[0];
        }

        // This is a generic table scraper. 
        // Adjust the selectors below based on the actual HTML structure of eclaim.nhso.go.th/Client
        let rows = document.querySelectorAll('table tbody tr');

        if (!rows || rows.length === 0) {
            // Fallback to any table if tbody is not explicitly used
            rows = document.querySelectorAll('table tr');
        }

        if (rows.length <= 1) {
            return { success: false, message: "ไม่พบข้อมูลตารางในหน้านี้ (Table empty)" };
        }

        rows.forEach((row, index) => {
            // Skip header row
            if (row.closest('thead') || (index === 0 && row.querySelectorAll('th').length > 0)) {
                return;
            }

            let cols = row.querySelectorAll('td');
            if (cols.length >= 22) {
                // Table structure from Home search (0-22)
                // 1:EClaim, 2:PtType(OP/IP), 3:Benefit, 4:CID, 5:PtName, 6:HN, 7:AN
                // 8:vstdate, 9:vsttime, 10:dchdate, 11:dchtime, 12:status, 13:recorder
                // 14:tran_id, 15:net_charge, 16:claim_amount, 17:REP, 18:STM, 19:SEQ
                // 20:check_detail, 21:deny_warning, 22:channel

                let cidRaw = cols[4]?.innerText.trim() || "";
                let cid = cidRaw.replace(/-/g, ''); // Remove dashes to match Excel

                let rowData = {
                    "eclaim_no": cols[1]?.innerText.trim() || "",
                    "patient_type": cols[2]?.innerText.trim() || "",
                    "hipdata": cols[3]?.innerText.trim() || "",
                    "cid": cid,
                    "ptname": cols[5]?.innerText.trim() || "",
                    "hn": cols[6]?.innerText.trim() || "",
                    "an": cols[7]?.innerText.trim() || "",
                    "vstdate": cols[8]?.innerText.trim() || "",
                    "vsttime": cols[9]?.innerText.trim() || "",
                    "dchdate": cols[10]?.innerText.trim() || "",
                    "dchtime": cols[11]?.innerText.trim() || "",
                    "status": cols[12]?.innerText.trim() || "",
                    "recorder": cols[13]?.innerText.trim() || "",
                    "tran_id": cols[14]?.innerText.trim() || "",
                    "net_charge": cols[15]?.innerText.trim().replace(/,/g, '') || "0",
                    "claim_amount": cols[16]?.innerText.trim().replace(/,/g, '') || "0",
                    "rep": cols[17]?.innerText.trim() || "",
                    "stm": cols[18]?.innerText.trim() || "",
                    "seq": cols[19]?.innerText.trim() || "",
                    "check_detail": cols[20]?.innerText.trim() || "",
                    "deny_warning": cols[21]?.innerText.trim() || "",
                    "channel": cols[22]?.innerText.trim() || "API"
                };

                dataToSync.push(rowData);
            }
        });

        if (dataToSync.length === 0) {
            return { success: false, message: "ดึงข้อมูลจากตารางได้ 0 รายการ" };
        }

        // Send to RiMS API
        const targetUrl = window.rimsApiUrl || 'http://127.0.0.1:8000/api/eclaim/sync';
        try {
            const response = await fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    hospcode: hcode,
                    data: dataToSync
                })
            });

            if (response.ok) {
                const apiRes = await response.json();
                return { success: true, message: "ซิงค์ข้อมูลสำเร็จ " + apiRes.count + " รายการ" };
            } else if (response.status === 403) {
                const apiRes = await response.json();
                return { success: false, message: "ปฏิเสธการนำเข้า: " + (apiRes.message || "รหัสสถานพยาบาลไม่ถูกต้อง") };
            } else {
                return { success: false, message: "ส่งข้อมูลไม่สำเร็จ HTTP: " + response.status };
            }
        } catch (fetchError) {
            if (fetchError.message.includes('Failed to fetch')) {
                return { success: false, message: "เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ (ตรวจสอบ Firewall หรือใช้ HTTPS ให้ตรงกับหน้าเว็บ)" };
            }
            throw fetchError; // Rethrow other errors to be caught by the outer catch
        }
    } catch (e) {
        console.error(e);
        return { success: false, message: "เกิดข้อผิดพลาด: " + e.message };
    }
})();
