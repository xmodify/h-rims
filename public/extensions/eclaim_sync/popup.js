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

// Test Connection
document.getElementById('testBtn').addEventListener('click', async () => {
    const baseUrl = document.getElementById('apiUrl').value.trim() || defaultBaseUrl;
    const testUrl = baseUrl + '/eclaim/sync'; // We use the same endpoint for testing but with no data
    const resultDiv = document.getElementById('testResult');

    resultDiv.style.display = 'block';
    resultDiv.style.color = 'orange';
    resultDiv.textContent = 'กำลังทดสอบเชื่อมต่อ...';

    try {
        const response = await fetch(testUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ hospcode: 'TEST', data: [] }) // Test payload
        });

        if (response.status === 403 || response.ok) {
            // 403 is actually a "good" sign for connectivity because it means we reached the server 
            // and it processed our (invalid) hospcode.
            resultDiv.style.color = '#198754';
            resultDiv.textContent = 'เชื่อมต่อเซิร์ฟเวอร์สำเร็จ (Ready)';
        } else {
            resultDiv.style.color = 'red';
            resultDiv.textContent = 'เชื่อมต่อได้แต่เซิร์ฟเวอร์ตอบกลับ error: ' + response.status;
        }
    } catch (e) {
        resultDiv.style.color = 'red';
        if (e.message.includes('Failed to fetch')) {
            resultDiv.textContent = 'เชื่อมต่อไม่ได้: ตรวจสอบ URL หรือ Firewall/SSL (Mixed Content)';
        } else {
            resultDiv.textContent = 'Error: ' + e.message;
        }
    }
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
