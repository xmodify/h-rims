let isScraping = false;
const defaultBaseUrl = 'http://127.0.0.1:8000/api';

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

// ==================== E-Claim Status Sync ====================
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

function handleScriptResult(results) {
    if (chrome.runtime.lastError) {
        updateStatus("Error: " + chrome.runtime.lastError.message, "red");
        return;
    }

    if (results && results[0] && results[0].result) {
        let res = results[0].result;
        updateStatus(res.message, res.success ? "#198754" : "red");
    } else {
        updateStatus("ไม่สามารถรันสคริปต์ได้บนหน้านี้", "red");
    }
}

function updateStatus(msg, color) {
    let st = document.getElementById('status');
    st.textContent = msg;
    st.style.color = color || "#000";
}
