(function () {
    try {
        let stmFiles = [];

        // Find all rows in the table
        let rows = document.querySelectorAll('table tbody tr');
        if (!rows || rows.length === 0) {
            rows = document.querySelectorAll('table tr');
        }

        rows.forEach((row, index) => {
            // Skip headers
            if (row.closest('thead') || row.querySelectorAll('th').length > 0) return;
            const rowText = row.innerText.toLowerCase();
            if (rowText.includes('download statement') || rowText.includes('previous') || rowText.includes('หน่วยงานที่เกี่ยวข้อง')) return;

            let cols = row.querySelectorAll('td');
            if (cols.length < 5) return; // Not a valid statement row

            // Try to find the Download link
            let downloadLink = null;
            let downloadUrl = "";
            let onclickAttr = "";

            // Search all columns for a download link
            let links = row.querySelectorAll('a');
            links.forEach(a => {
                let txt = a.innerText.trim().toLowerCase();
                if (txt === 'download' || txt === 'ดาวน์โหลด') {
                    downloadLink = a;
                    downloadUrl = a.href || "";
                    onclickAttr = a.getAttribute('onclick') || "";
                }
            });

            if (!downloadLink) return;

            // Extract Statement No. Usually it's a long string like '10989_IPUCS256810_01'
            let stmNo = "";
            let stmDate = "";

            // Heuristic detection based on common NHSO table structure
            cols.forEach((col) => {
                let text = col.innerText.trim();
                // Match dates like DD/MM/YYYY
                if (text.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/) && !stmDate) {
                    stmDate = text;
                }
                // Match Statement No (e.g. 10989_IPUCS256810_01)
                else if (text.match(/^[A-Z0-9]+_[a-zA-Z0-9_]+$/) && text.length > 10 && !stmNo) {
                    stmNo = text;
                }
                // Match OFC style (e.g. OFC256810_01)
                else if (text.match(/^[A-Z]+\d+_\d+$/) && !stmNo) {
                    stmNo = text;
                }
            });

            // Fallback for specific columns based on screenshot
            if (!stmDate && cols[1]) stmDate = cols[1].innerText.trim();
            if (!stmNo && cols[6]) stmNo = cols[6].innerText.trim();

            if (stmNo && (downloadUrl || onclickAttr)) {
                stmFiles.push({
                    stm_no: stmNo,
                    date: stmDate || "-",
                    url: downloadUrl,
                    onclick: onclickAttr,
                    rowIndex: index
                });
            }
        });

        if (stmFiles.length === 0) {
            return { success: false, message: "ไม่พบข้อมูล Statement หรือลิงก์ดาวน์โหลดในหน้านี้" };
        }

        return { success: true, data: stmFiles };

    } catch (e) {
        console.error(e);
        return { success: false, message: "เกิดข้อผิดพลาดในการสแกน: " + e.message };
    }
})();
