(async function () {
    try {
        const filesToUpload = window.filesToUpload || [];
        const apiUrl = window.rimsApiUrl;

        if (!filesToUpload.length || !apiUrl) {
            return { success: false, message: "ไม่มีข้อมูลสำหรับอัปโหลด หรือ URL ผิดพลาด" };
        }

        let successCount = 0;
        let failCount = 0;

        for (let i = 0; i < filesToUpload.length; i++) {
            let fileInfo = filesToUpload[i];

            try {
                let downloadUrl = fileInfo.url;

                // If the link uses javascript (e.g., href="javascript:downloadStmt(...)") 
                // we have to simulate a click and catch the download, which is tricky in a content script without extension background permissions.
                // For now, assuming standard NHSO links: they are mostly GET requests or relative URLs.
                if (downloadUrl.startsWith('javascript:')) {
                    // Try to hack it: sometimes the onclick contains the URL
                    if (fileInfo.onclick && fileInfo.onclick.includes('.do')) {
                        const match = fileInfo.onclick.match(/['"]([^'"]+\.do[^'"]*)['"]/);
                        if (match) downloadUrl = match[1];
                    } else {
                        throw new Error(`ลิ้งก์เป็น Javascript (ไม่ได้ระบุ URL) - ข้ามไฟล์ ${fileInfo.stm_no}`);
                    }
                }

                // Ensure absolute URL if relative
                if (downloadUrl.startsWith('/')) {
                    downloadUrl = window.location.origin + downloadUrl;
                } else if (!downloadUrl.startsWith('http')) {
                    // It might be like "statementUCSAction.do?mode=download&..."
                    let path = window.location.pathname;
                    path = path.substring(0, path.lastIndexOf('/') + 1);
                    downloadUrl = window.location.origin + path + downloadUrl;
                }

                console.log(`[STM Sync] Downloading ${fileInfo.stm_no} from: ${downloadUrl}`);

                // Fetch the file bytes securely using browser's ambient credentials (session)
                const response = await fetch(downloadUrl);
                if (!response.ok) throw new Error(`HTTP Fetch Error ${response.status}`);

                const blob = await response.blob();

                // Determine filename
                let filename = `${fileInfo.stm_no}.xls`;
                const disposition = response.headers.get('content-disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const filenameMatch = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (filenameMatch != null && filenameMatch[1]) {
                        filename = filenameMatch[1].replace(/['"]/g, '');
                    }
                }

                // Prepare Payload
                const formData = new FormData();
                formData.append('file', blob, filename);
                formData.append('stm_no', fileInfo.stm_no);
                formData.append('date', fileInfo.date);

                // Post to RiMS
                const apiRes = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        // Do NOT set Content-Type header to multipart/form-data explicitly. 
                        // Fetch will set it automatically with boundary.
                    },
                    body: formData
                });

                if (apiRes.ok) {
                    successCount++;
                } else {
                    console.error("RiMS API Error:", await apiRes.text());
                    failCount++;
                }

            } catch (err) {
                console.error("Error processing STM:", fileInfo.stm_no, err);
                failCount++;
            }
        }

        return {
            success: successCount > 0 || failCount === 0,
            message: `ดึงและส่งเสร็จสิ้น! สำเร็จ ${successCount} ไฟล์, ล้มเหลว ${failCount} ไฟล์`
        };

    } catch (e) {
        console.error("Global Upload Error:", e);
        return { success: false, message: "ระบบเกิดข้อผิดพลาดในการอัปโหลด: " + e.message };
    }
})();
