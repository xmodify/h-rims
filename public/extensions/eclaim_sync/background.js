// RiMS E-Claim Sync - Background Service Worker (Manifest V3)
// Features: Auto-Sync on Login + Heartbeat Keep-Alive every 5 minutes

const DEFAULT_BASE_URL = 'http://127.0.0.1/rims/api';
const HEARTBEAT_ALARM = 'eclaim_heartbeat_alarm';

// Initialize on install or startup
chrome.runtime.onInstalled.addListener(() => {
    console.log('[RiMS Sync] Extension installed/updated.');
    setupHeartbeat();
});

chrome.runtime.onStartup.addListener(() => {
    console.log('[RiMS Sync] Browser started.');
    setupHeartbeat();
});

// Setup 5-minute heartbeat alarm to keep e-Claim session alive
function setupHeartbeat() {
    chrome.alarms.create(HEARTBEAT_ALARM, {
        periodInMinutes: 5
    });
    console.log('[RiMS Sync] Heartbeat alarm created (every 5 minutes).');
}

// Alarm listener: Sends a lightweight ping to e-Claim to maintain session
chrome.alarms.onAlarm.addListener((alarm) => {
    if (alarm.name === HEARTBEAT_ALARM) {
        performHeartbeatPing();
    }
});

async function performHeartbeatPing() {
    try {
        const cookies = await chrome.cookies.getAll({ domain: "eclaim.nhso.go.th" });
        const hasSession = cookies && cookies.some(c => c.name === 'JSESSIONID');
        
        if (hasSession) {
            console.log('[RiMS Sync] Sending Keep-Alive ping to e-Claim...');
            await fetch('https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/127.0.0.0 Safari/537.36'
                }
            });
            console.log('[RiMS Sync] Keep-Alive ping sent successfully.');
            
            // Auto-resync latest session token to RiMS DB
            await autoSyncSessionToRims('heartbeat');
        }
    } catch (err) {
        console.warn('[RiMS Sync] Keep-Alive ping error:', err);
    }
}

// ============================================================
// Auto-Sync Function: Reads e-Claim Cookies & Sends to RiMS DB
// ============================================================
let syncDebounceTimer = null;

async function executeSyncSession(source = 'background') {
    try {
        const getCookiesFor = (query) => new Promise(resolve => chrome.cookies.getAll(query, resolve));

        const [cUrl, cDomain, cNhso, cIam, cHome, cRoot] = await Promise.all([
            getCookiesFor({ url: "https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do" }),
            getCookiesFor({ domain: "eclaim.nhso.go.th" }),
            getCookiesFor({ domain: ".nhso.go.th" }),
            getCookiesFor({ domain: "iam.nhso.go.th" }),
            getCookiesFor({ url: "https://eclaim.nhso.go.th/Client/home" }),
            getCookiesFor({ url: "https://eclaim.nhso.go.th/" })
        ]);

        const cookieMap = new Map();
        [...(cUrl || []), ...(cDomain || []), ...(cNhso || []), ...(cIam || []), ...(cHome || []), ...(cRoot || [])].forEach(c => {
            if (c && c.name && c.value) {
                cookieMap.set(c.name, c.value);
            }
        });

        const hasAuthToken = cookieMap.has('ACCESS_TOKEN') || cookieMap.has('STEEXWDE') || cookieMap.has('AUTH_SESSION_ID');
        if (!cookieMap.has('JSESSIONID') || !hasAuthToken) {
            return { success: false, message: 'ยังไม่ได้เข้าสู่ระบบ e-Claim หรือ Token ยังไม่ครบ' };
        }

        const cleanCookies = [];
        for (let [k, v] of cookieMap.entries()) {
            if (k.startsWith('_ga') || k === '_gid' || k === '_gat' || k === '_gcl_au' || k.startsWith('__')) {
                continue;
            }
            cleanCookies.push(`${k}=${v}`);
        }
        const fullCookieString = cleanCookies.join('; ');

        // Read settings from storage
        const settings = await chrome.storage.local.get(['apiUrl', 'hospCode']);
        const baseUrl = (settings.apiUrl && settings.apiUrl.trim()) ? settings.apiUrl.trim() : DEFAULT_BASE_URL;
        const hcode = (settings.hospCode && settings.hospCode.trim()) ? settings.hospCode.trim() : '10989';
        const targetUrl = baseUrl.replace(/\/+$/, '') + '/eclaim/session-sync';

        console.log(`[RiMS Sync] Syncing session (${source}) to:`, targetUrl);

        const res = await fetch(targetUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                token: fullCookieString,
                hospcode: hcode
            })
        });

        const data = await res.json().catch(() => ({}));
        if (res.ok && data.status === 'success') {
            console.log('[RiMS Sync] Session synced successfully:', data);
            return { success: true, user: data.user };
        } else {
            console.warn('[RiMS Sync] Session sync rejected:', data.message || res.status);
            return { success: false, message: data.message || 'License Expired กรุณาติดต่อผู้พัฒนา' };
        }
    } catch (err) {
        console.warn('[RiMS Sync] Sync failed:', err);
        return { success: false, message: err.message };
    }
}

async function autoSyncSessionToRims(source = 'background') {
    if (syncDebounceTimer) {
        clearTimeout(syncDebounceTimer);
    }

    syncDebounceTimer = setTimeout(() => {
        executeSyncSession(source);
    }, 1500); // 1.5s debounce to prevent flood
}

// 1. Listen for Cookie Changes on e-Claim domain
chrome.cookies.onChanged.addListener((changeInfo) => {
    if (changeInfo.cookie && changeInfo.cookie.domain && changeInfo.cookie.domain.includes('nhso.go.th')) {
        if (['JSESSIONID', 'STEEXWDE', 'ACCESS_TOKEN', 'AUTH_SESSION_ID'].includes(changeInfo.cookie.name)) {
            if (!changeInfo.removed) {
                autoSyncSessionToRims('cookie_changed');
            }
        }
    }
});

// 2. Listen for messages from content scripts or popup
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message && message.action === 'sync_session') {
        autoSyncSessionToRims('message_request');
        sendResponse({ status: 'started' });
        return true;
    } else if (message && message.action === 'sync_session_now') {
        executeSyncSession('popup_direct').then(res => sendResponse(res));
        return true;
    }
    return true;
});
