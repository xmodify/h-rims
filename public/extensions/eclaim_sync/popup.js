let isScraping = false;
const defaultUrl = 'http://127.0.0.1:8000/api/eclaim/sync';

// Load saved settings
chrome.storage.local.get(['apiUrl'], function (result) {
    if (result.apiUrl) {
        document.getElementById('apiUrl').value = result.apiUrl;
    } else {
        document.getElementById('apiUrl').value = defaultUrl;
    }
});

// Toggle Settings
document.getElementById('toggleSettings').addEventListener('click', () => {
    const panel = document.getElementById('settingsPanel');
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
});

// Save Settings
document.getElementById('saveBtn').addEventListener('click', () => {
    const url = document.getElementById('apiUrl').value;
    chrome.storage.local.set({ apiUrl: url }, () => {
        updateStatus("บันทึกการตั้งค่าแล้ว", "#198754");
        setTimeout(() => {
            document.getElementById('settingsPanel').style.display = 'none';
        }, 1000);
    });
});

document.getElementById('syncBtn').addEventListener('click', async () => {
    if (isScraping) return;

    let [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    const allowedUrl = "https://eclaim.nhso.go.th/Client/home";
    if (tab.url !== allowedUrl) {
        updateStatus("โปรดจัดการข้อมูลในหน้า " + allowedUrl + " เท่านั้น", "red");
        return;
    }

    // Get the configured URL
    const result = await chrome.storage.local.get(['apiUrl']);
    const targetUrl = result.apiUrl || defaultUrl;

    isScraping = true;
    updateStatus("กำลังดึงข้อมูลและเตรียมส่ง...", "#ffc107");

    // Inject the target URL into the page context before running content.js
    chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: (apiUrl) => {
            window.rimsApiUrl = apiUrl;
        },
        args: [targetUrl]
    }, () => {
        chrome.scripting.executeScript({
            target: { tabId: tab.id },
            files: ['content.js']
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
                } else {
                    updateStatus(res.message, "red");
                }
            } else {
                updateStatus("เกิดข้อผิดพลาดในการดึงข้อมูล", "red");
            }
        });
    });
});

function updateStatus(message, color) {
    const statusDiv = document.getElementById('status');
    statusDiv.innerText = message;
    statusDiv.style.color = color;
}
