let isScraping = false;
const defaultBaseUrl = 'http://127.0.0.1:8000/api';
let currentStmFiles = [];

// Load saved settings
chrome.storage.local.get(['apiUrl'], function (result) {
    if (result.apiUrl) {
        document.getElementById('apiUrl').value = result.apiUrl;
    } else {
        document.getElementById('apiUrl').value = defaultBaseUrl;
    }
});

// Toggle Settings
document.getElementById('toggleSettings').addEventListener('click', () => {
    const panel = document.getElementById('settingsPanel');
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
});

// Save Settings
document.getElementById('saveBtn').addEventListener('click', () => {
    let url = document.getElementById('apiUrl').value.trim();
    if (url.endsWith('/')) { url = url.slice(0, -1); } // Remove trailing slash
    chrome.storage.local.set({ apiUrl: url }, () => {
        updateStatus("บันทึกการตั้งค่าแล้ว", "#198754");
        setTimeout(() => {
            document.getElementById('settingsPanel').style.display = 'none';
        }, 1000);
    });
});

// ==================== 1. E-Claim Status Sync ====================
document.getElementById('syncBtn').addEventListener('click', async () => {
    if (isScraping) return;

    let [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    const allowedUrl = "https://eclaim.nhso.go.th/Client/home";
    if (tab.url !== allowedUrl) {
        updateStatus("โปรดเปิดหน้าแรก e-Claim (Client/home) ก่อน", "red");
        return;
    }

    const baseUrl = document.getElementById('apiUrl').value.trim() || defaultBaseUrl;
    const targetUrl = baseUrl + '/eclaim/sync'; // Append endpoint

    isScraping = true;
    updateStatus("กำลังดึงข้อมูลและเตรียมส่ง...", "#ffc107");

    chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: (apiUrl) => { window.rimsApiUrl = apiUrl; },
        args: [targetUrl]
    }, () => {
        chrome.scripting.executeScript({
            target: { tabId: tab.id },
            files: ['content.js']
        }, (results) => {
            isScraping = false;
            handleScriptResult(results);
        });
    });
});

// ==================== 2. STM Sync (Scanning Phase) ====================
document.getElementById('stmSyncBtn').addEventListener('click', async () => {
    if (isScraping) return;

    let [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    // Check if on a valid statement page
    const validUrls = [
        "statementUCSAction.do",
        "StatementReportWebAction.do",
        "/bmt/mstatement.do",
        "/ktmn/mstatement.do",
        "/srt/mstatement.do"
    ];
    let isValid = validUrls.some(u => tab.url.includes(u));

    if (!isValid) {
        updateStatus("โปรดเปิดหน้าดาวน์โหลด Statement ก่อน", "red");
        return;
    }

    isScraping = true;
    updateStatus("กำลังสแกนหาไฟล์บนหน้าจอ...", "#ffc107");

    chrome.scripting.executeScript({
        target: { tabId: tab.id },
        files: ['content_stm_scan.js']
    }, async (results) => {
        isScraping = false;

        if (chrome.runtime.lastError) {
            updateStatus("Error: " + chrome.runtime.lastError.message, "red");
            return;
        }

        if (results && results[0] && results[0].result) {
            let res = results[0].result;
            if (res.success && res.data.length > 0) {
                // We found files. Now ask API about statuses.
                updateStatus("กำลังตรวจสอบข้อมูลกับระบบ RiMS...", "#ffc107");

                try {
                    const baseUrl = document.getElementById('apiUrl').value.trim() || defaultBaseUrl;
                    const checkUrl = baseUrl + '/import/stm_check'; // We'll create this API
                    const stmType = getStmTypeFromUrl(tab.url);

                    const response = await fetch(checkUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ stm_type: stmType, files_scanned: res.data })
                    });

                    if (response.ok) {
                        const apiRes = await response.json();
                        // Assume apiRes.files is the same array returned with a 'status' attached ('new' | 'exist')
                        currentStmFiles = apiRes.files || res.data.map(f => ({ ...f, status: 'new' }));
                        renderStmList(currentStmFiles);
                        document.getElementById('mainButtons').style.display = 'none';
                        document.getElementById('stmPanel').style.display = 'block';
                        updateStatus("", "");
                    } else {
                        const errorText = await response.text();
                        console.error("API Error Check:", errorText);
                        // Fallback if API doesn't exist yet or errors
                        currentStmFiles = res.data.map(f => ({ ...f, status: 'new' }));
                        renderStmList(currentStmFiles);
                        document.getElementById('mainButtons').style.display = 'none';
                        document.getElementById('stmPanel').style.display = 'block';
                        updateStatus("แจ้งเตือน: ไม่สามารถเช็คซ้ำได้ (" + response.status + ")", "#ffc107");
                    }
                } catch (e) {
                    console.error("Network Error:", e);
                    // Fallback on network error 
                    currentStmFiles = res.data.map(f => ({ ...f, status: 'new' }));
                    renderStmList(currentStmFiles);
                    document.getElementById('mainButtons').style.display = 'none';
                    document.getElementById('stmPanel').style.display = 'block';
                    updateStatus("แจ้งเตือน: เชื่อมต่อ API ล้มเหลว", "#ffc107");
                }

            } else {
                updateStatus(res.message || "ไม่พบลิงก์ดาวน์โหลด Statement", "red");
            }
        } else {
            updateStatus("เกิดข้อผิดพลาดในการสแกนข้อมูล", "red");
        }
    });
});

// ==================== 3. STM Sync (Upload Phase) ====================
document.getElementById('cancelStmBtn').addEventListener('click', () => {
    document.getElementById('stmPanel').style.display = 'none';
    document.getElementById('mainButtons').style.display = 'block';
});

document.getElementById('selectAllStm').addEventListener('change', (e) => {
    const checkboxes = document.querySelectorAll('.stm-file-cb');
    checkboxes.forEach(cb => cb.checked = e.target.checked);
});

document.getElementById('importStmBtn').addEventListener('click', async () => {
    if (isScraping) return;

    const checkboxes = document.querySelectorAll('.stm-file-cb:checked');
    if (checkboxes.length === 0) {
        alert("กรุณาเลือกไฟล์อย่างน้อย 1 รายการ");
        return;
    }

    const selectedFileIndexes = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index));
    const filesToUpload = selectedFileIndexes.map(i => currentStmFiles[i]);

    let [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    let baseUrl = document.getElementById('apiUrl').value.trim() || defaultBaseUrl;
    if (baseUrl.endsWith('/')) baseUrl = baseUrl.slice(0, -1);
    const stmType = getStmTypeFromUrl(tab.url);
    const targetUrl = baseUrl + '/import/stm_' + stmType + '_upload';

    isScraping = true;
    updateStatus(`<div class="loading-spinner"></div>กำลังดึงและส่งไฟล์ ${filesToUpload.length} รายการ...`, "#0d6efd");

    // Pass the list of files to the content script to perform the download & upload
    chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: (apiUrl, files) => {
            window.rimsApiUrl = apiUrl;
            window.filesToUpload = files;
        },
        args: [targetUrl, filesToUpload]
    }, () => {
        chrome.scripting.executeScript({
            target: { tabId: tab.id },
            files: ['content_stm_upload.js']
        }, (results) => {
            isScraping = false;

            if (chrome.runtime.lastError) {
                updateStatus("Error: " + chrome.runtime.lastError.message, "red");
                return;
            }

            if (results && results[0] && results[0].result) {
                let res = results[0].result;
                if (res.success) {
                    updateStatus(res.message, "#198754");
                    setTimeout(() => {
                        document.getElementById('stmPanel').style.display = 'none';
                        document.getElementById('mainButtons').style.display = 'block';
                    }, 3000);
                } else {
                    updateStatus(res.message, "red");
                }
            } else {
                updateStatus("เกิดข้อผิดพลาดในการอัปโหลดข้อมูล", "red");
            }
        });
    });
});

// Helper functions
function handleScriptResult(results) {
    if (chrome.runtime.lastError) {
        updateStatus("Error: " + chrome.runtime.lastError.message, "red");
        return;
    }

    if (results && results[0] && results[0].result) {
        let res = results[0].result;
        if (res.success) {
            updateStatus(res.message, "#198754");
        } else {
            updateStatus(res.message, "red");
        }
    } else {
        updateStatus("เกิดข้อผิดพลาดในการดึงข้อมูล", "red");
    }
}

function updateStatus(message, color) {
    const statusDiv = document.getElementById('status');
    statusDiv.innerHTML = message; // Use innerHTML for spinner
    statusDiv.style.color = color;
}

function getStmTypeFromUrl(url) {
    if (url.includes("statementUCSAction")) return "ucs";
    if (url.includes("StatementReportWebAction")) return "ofc";
    if (url.includes("/bmt/")) return "bmt";
    if (url.includes("/ktmn/")) return "bkk";
    if (url.includes("/srt/")) return "srt";
    return "unknown"; // Default fallback
}

function renderStmList(files) {
    const listDiv = document.getElementById('stmList');
    listDiv.innerHTML = "";

    document.getElementById('stmCountText').innerText = `พบไฟล์ Statement (${files.length} รายการ)`;

    files.forEach((file, index) => {
        const isExist = file.status === 'exist';
        const badgeClass = isExist ? 'badge-exist' : 'badge-new';
        const badgeText = isExist ? 'มีในระบบแล้ว' : 'พบใหม่';
        // Auto check only new ones
        const checkedStatus = isExist ? '' : 'checked';

        const itemHtml = `
            <div class="stm-item">
                <input type="checkbox" class="stm-checkbox stm-file-cb" data-index="${index}" ${checkedStatus}>
                <div class="stm-name" title="${file.stm_no}">
                    ${file.stm_no} (${file.date})
                    <span class="badge ${badgeClass}">${badgeText}</span>
                </div>
            </div>
        `;
        listDiv.insertAdjacentHTML('beforeend', itemHtml);
    });

    // Update master checkbox based on initial states
    const checkedCount = document.querySelectorAll('.stm-file-cb:checked').length;
    document.getElementById('selectAllStm').checked = (checkedCount === files.length && files.length > 0);
}
