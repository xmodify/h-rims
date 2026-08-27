let isScraping = false;
const defaultBaseUrl = 'http://127.0.0.1/rims/api';

// Load saved settings & auto-detect open RiMS tabs
chrome.storage.local.get(['apiUrl', 'hospCode'], function (result) {
    if (result.apiUrl) {
        document.getElementById('apiUrl').value = result.apiUrl;
    } else {
        document.getElementById('apiUrl').value = defaultBaseUrl;
    }
    if (result.hospCode) {
        document.getElementById('hospCode').value = result.hospCode;
    }

    // Auto-detect if user currently has an open RiMS tab on a remote hospital server
    chrome.tabs.query({}, function (tabs) {
        if (!tabs || tabs.length === 0) return;
        for (let tab of tabs) {
            if (tab.url && (tab.url.includes('/import/') || tab.url.includes('/claim_') || tab.url.includes('/hosfin') || tab.url.includes('/rims') || (tab.title && tab.title.includes('RiMS')))) {
                try {
                    const u = new URL(tab.url);
                    let detectedApi = u.origin;
                    if (u.pathname.startsWith('/rims/')) {
                        detectedApi += '/rims/api';
                    } else {
                        detectedApi += '/api';
                    }

                    const currentConfigured = (result.apiUrl || defaultBaseUrl).replace(/\/+$/, '');
                    if (currentConfigured !== detectedApi) {
                        const badge = document.getElementById('detectedHostBadge');
                        const name = document.getElementById('detectedHostName');
                        if (badge && name) {
                            name.textContent = u.host;
                            badge.style.display = 'block';
                            badge.onclick = function () {
                                document.getElementById('apiUrl').value = detectedApi;
                                chrome.storage.local.set({ apiUrl: detectedApi }, () => {
                                    updateStatus("สลับ API ไปใช้ " + u.host + " แล้ว", "#198754");
                                    badge.style.display = 'none';
                                });
                            };
                        }
                    }
                    break;
                } catch (e) { }
            }
        }
    });
});

// Toggle Settings
document.getElementById('toggleSettings').addEventListener('click', () => {
    const panel = document.getElementById('settingsPanel');
    panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
});

// Save Settings
document.getElementById('saveBtn').addEventListener('click', () => {
    let url = document.getElementById('apiUrl').value.trim();
    let hcode = document.getElementById('hospCode').value.trim();
    if (url.endsWith('/')) { url = url.slice(0, -1); } // Remove trailing slash
    chrome.storage.local.set({ apiUrl: url, hospCode: hcode }, () => {
        updateStatus("บันทึกการตั้งค่าแล้ว", "#198754");
        setTimeout(() => {
            document.getElementById('settingsPanel').style.display = 'none';
        }, 1000);
    });
});

// Test Connection
document.getElementById('testBtn').addEventListener('click', async () => {
    const baseUrl = document.getElementById('apiUrl').value.trim() || defaultBaseUrl;
    const hcode = document.getElementById('hospCode').value.trim();
    const testUrl = baseUrl + '/eclaim/sync'; // We use the same endpoint for testing
    const resultDiv = document.getElementById('testResult');

    resultDiv.style.display = 'block';
    resultDiv.style.color = 'orange';
    resultDiv.textContent = 'กำลังทดสอบเชื่อมต่อ...';

    try {
        const response = await fetch(testUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ hospcode: hcode || 'TEST', data: [] }) // Use setting hcode if available
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

// ==================== E-Claim Session Sync ====================
document.getElementById('syncSessionBtn').addEventListener('click', async () => {
    updateStatus("กำลังอ่าน Session e-Claim...", "#ffc107");

    try {
        let cookieMap = new Map();

        // 1. Try reading cookies via chrome.cookies API if available
        if (chrome.cookies && typeof chrome.cookies.getAll === 'function') {
            const getCookiesFor = (query) => new Promise(resolve => {
                try {
                    chrome.cookies.getAll(query, (res) => {
                        if (chrome.runtime.lastError) {
                            resolve([]);
                        } else {
                            resolve(res || []);
                        }
                    });
                } catch(e) {
                    resolve([]);
                }
            });

            const [cUrl, cDomain, cNhso, cIam, cHome, cRoot] = await Promise.all([
                getCookiesFor({ url: "https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do" }),
                getCookiesFor({ domain: "eclaim.nhso.go.th" }),
                getCookiesFor({ domain: ".nhso.go.th" }),
                getCookiesFor({ domain: "iam.nhso.go.th" }),
                getCookiesFor({ url: "https://eclaim.nhso.go.th/Client/home" }),
                getCookiesFor({ url: "https://eclaim.nhso.go.th/" })
            ]);

            [...(cUrl || []), ...(cDomain || []), ...(cNhso || []), ...(cIam || []), ...(cHome || []), ...(cRoot || [])].forEach(c => {
                if (c && c.name && c.value) {
                    cookieMap.set(c.name, c.value);
                }
            });
        }

        // 2. Fallback: If chrome.cookies failed or didn't get JSESSIONID, try active tab
        if (!cookieMap.has('JSESSIONID')) {
            try {
                let [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
                if (tab && tab.id && tab.url && tab.url.includes('nhso.go.th')) {
                    const injectionResults = await chrome.scripting.executeScript({
                        target: { tabId: tab.id },
                        func: () => document.cookie
                    });
                    if (injectionResults && injectionResults[0] && injectionResults[0].result) {
                        const docCookies = injectionResults[0].result.split(';');
                        docCookies.forEach(item => {
                            const [k, v] = item.trim().split('=');
                            if (k && v) cookieMap.set(k, v);
                        });
                    }
                }
            } catch (e) {
                console.warn('Tab script cookie fallback error:', e);
            }
        }

        // 3. Fallback: Ask background service worker to sync
        if (!cookieMap.has('JSESSIONID')) {
            try {
                const response = await chrome.runtime.sendMessage({ action: 'sync_session' });
                if (response && response.status === 'started') {
                    updateStatus("🔄 ส่งคำขอให้ระบบ Background ซิงก์เรียบร้อยแล้ว", "#198754");
                    return;
                }
            } catch (e) {
                console.warn('Background message error:', e);
            }
        }

        if (!cookieMap.has('JSESSIONID') && (!chrome.cookies || typeof chrome.cookies.getAll !== 'function')) {
            updateStatus("⚠️ กรุณากดปุ่ม 🔄 Reload ส่วนเสริมในหน้า chrome://extensions ก่อนครับ", "red");
            return;
        }

        const hasAuthToken = cookieMap.has('ACCESS_TOKEN') || cookieMap.has('STEEXWDE') || cookieMap.has('AUTH_SESSION_ID');
        if (!cookieMap.has('JSESSIONID') || !hasAuthToken) {
            updateStatus("⚠️ ยังไม่ได้เข้าสู่ระบบ e-Claim หรือยังอยู่ที่หน้าประกาศ SSO กรุณาล็อกอินให้ถึงหน้าหลักก่อนกดซิงก์ครับ", "red");
            return;
        }

        // กรองเอาเฉพาะ Cookie ที่จำเป็นต่อการ Auth (ตัด Google Analytics _ga, _gid ออก ป้องกัน Header Too Large)
        const cleanCookies = [];
        for (let [k, v] of cookieMap.entries()) {
            if (k.startsWith('_ga') || k === '_gid' || k === '_gat' || k === '_gcl_au' || k.startsWith('__')) {
                continue;
            }
            cleanCookies.push(`${k}=${v}`);
        }
        const fullCookieString = cleanCookies.join('; ');

        const baseUrl = document.getElementById('apiUrl').value.trim() || defaultBaseUrl;
        const hcode = document.getElementById('hospCode').value.trim();
        const targetUrl = baseUrl.replace(/\/+$/, '') + '/eclaim/session-sync';

        const res = await fetch(targetUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ token: fullCookieString, hospcode: hcode })
        });
        const data = await res.json();
        if (data.status === 'success') {
            updateStatus("✅ ซิงก์ Session กับ RiMS สำเร็จแล้ว! (" + (data.user || '') + ")", "#198754");
        } else {
            updateStatus("ผิดพลาด: " + (data.message || 'บันทึกไม่สำเร็จ'), "red");
        }
    } catch (err) {
        updateStatus("เชื่อมต่อ RiMS ไม่ได้: " + err.message, "red");
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
    const hcode = document.getElementById('hospCode').value.trim();
    const targetUrl = baseUrl + '/eclaim/sync'; // Append endpoint

    isScraping = true;
    updateStatus("กำลังดึงข้อมูลและเตรียมส่ง...", "#ffc107");

    chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: (apiUrl, hcode) => { 
            window.rimsApiUrl = apiUrl; 
            window.rimsHospCode = hcode; 
        },
        args: [targetUrl, hcode]
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
