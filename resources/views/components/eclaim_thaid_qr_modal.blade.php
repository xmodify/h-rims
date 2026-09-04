<!-- Modal ThaiD QR Login for e-Claim (Playwright Powered) -->
<div class="modal fade" id="modalEclaimThaidQr" tabindex="-1" aria-labelledby="modalEclaimThaidQrLabel" aria-hidden="true" data-bs-backdrop="static" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-qr-code-scan fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalEclaimThaidQrLabel">เข้าสู่ระบบ e-Claim สปสช. (ThaiD)</h6>
                        <span class="small text-white-50" style="font-size: 0.75rem;">สแกนผ่านหน้าเว็บโดยตรง ไม่ต้องใช้ส่วนเสริม</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnThaidQrCloseX"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 text-center bg-light">
                
                <!-- 1. LOADING STATE -->
                <div id="thaidQrLoadingState" class="py-4">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1" id="thaidQrLoadingText">กำลังเชื่อมต่อระบบ e-Claim สปสช...</h6>
                    <p class="text-muted small mb-0">กำลังขอ QR Code ยืนยันตัวตนจาก ThaiD (กรมการปกครอง)</p>
                </div>

                <!-- 2. QR READY STATE -->
                <div id="thaidQrReadyState" style="display: none;">
                    
                    <!-- QR Box -->
                    <div class="d-inline-block p-3 bg-white rounded-4 shadow-sm border mb-3 position-relative">
                        <img id="thaidQrImage" src="" alt="ThaiD QR Code" style="width: 240px; height: 240px; object-fit: contain;" class="rounded-3">
                        <div id="thaidQrRefBadge" class="mt-2 small fw-bold text-secondary font-monospace"></div>
                    </div>

                    <!-- Instructions & Status -->
                    <div class="mb-3">
                        <div class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 mb-2 d-inline-flex align-items-center gap-1 shadow-sm">
                            <span class="spinner-grow spinner-grow-sm text-primary" role="status"></span>
                            <span class="fw-bold" style="font-size: 0.82rem;">กำลังรอการสแกนจากแอปพลิเคชัน ThaiD</span>
                        </div>
                        <p class="text-muted small mb-1">
                            <i class="bi bi-phone me-1 text-primary"></i> เปิดแอป <b>ThaiD</b> บนมือถือ ➔ กดปุ่มสแกนที่หน้าจอนี้
                        </p>
                        <div class="small fw-semibold text-danger">
                            <i class="bi bi-clock-history me-1"></i> QR Code จะหมดอายุใน: <span id="thaidCountdownText" class="font-monospace">02:00</span>
                        </div>
                    </div>

                </div>

                <!-- 3. SUCCESS STATE -->
                <div id="thaidQrSuccessState" class="py-4" style="display: none;">
                    <div class="mb-3">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-lg" style="width: 68px; height: 68px; animation: scaleIn 0.3s ease;">
                            <i class="bi bi-check-lg fs-1"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-success mb-1">เข้าสู่ระบบ e-Claim สำเร็จ!</h5>
                    <p class="text-dark small mb-0" id="thaidSuccessUserText">ยินดีต้อนรับ</p>
                    <small class="text-muted">บันทึก Session เข้าส่วนกลางเรียบร้อยแล้ว</small>
                </div>

                <!-- 4. FAILED / EXPIRED STATE -->
                <div id="thaidQrFailedState" class="py-4" style="display: none;">
                    <div class="mb-3">
                        <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow" style="width: 60px; height: 60px;">
                            <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                        </div>
                    </div>
                    <h6 class="fw-bold text-danger mb-1" id="thaidFailedTitle">เกิดข้อผิดพลาด</h6>
                    <p class="text-muted small mb-3" id="thaidFailedMsg">QR Code หมดอายุ หรือการเชื่อมต่อล้มเหลว</p>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm" onclick="startThaidQrLogin()">
                        <i class="bi bi-arrow-repeat me-1"></i> ลองใหม่อีกครั้ง
                    </button>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-0 py-2.5 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal" id="btnThaidCancel">
                    <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
                </button>
                <div id="thaidFooterActions" style="display: none;">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="startThaidQrLogin()">
                        <i class="bi bi-arrow-clockwise me-1"></i> ขอ QR Code ใหม่
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes scaleIn {
    0% { transform: scale(0.5); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<script>
var currentThaidSessionId = null;
var thaidPollingInterval = null;
var thaidCountdownInterval = null;
var thaidRemainingSeconds = 120;
var onThaidLoginSuccessCallback = null;

// Modal Show/Hide Helpers (Safe across jQuery & Bootstrap versions)
function showEclaimThaidModal() {
    const el = document.getElementById('modalEclaimThaidQr');
    if (!el) return;

    if (window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(el).show();
    } else if (window.jQuery && typeof $(el).modal === 'function') {
        $(el).modal('show');
    } else {
        el.classList.add('show');
        el.style.display = 'block';
    }
}

function hideEclaimThaidModal() {
    const el = document.getElementById('modalEclaimThaidQr');
    if (!el) return;

    if (window.bootstrap && window.bootstrap.Modal) {
        const inst = window.bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
    } else if (window.jQuery && typeof $(el).modal === 'function') {
        $(el).modal('hide');
    } else {
        el.classList.remove('show');
        el.style.display = 'none';
    }
}

// Open ThaiD QR Modal & Start Login Flow
function openEclaimThaidQrModal(onSuccessCallback) {
    @php
        $is_thaid_licensed = \App\Services\LicenseVerificationService::isModuleLicensed('sync_eclaim_thaid');
    @endphp
    const isThaidLicensed = @json($is_thaid_licensed);
    if (!isThaidLicensed) {
        if (typeof showLicenseRequiredAlert === 'function') {
            showLicenseRequiredAlert();
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'License Expired',
                text: 'กรุณาติดต่อผู้พัฒนา',
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#3b82f6',
                borderRadius: '15px'
            });
        }
        return;
    }
    onThaidLoginSuccessCallback = onSuccessCallback || null;
    showEclaimThaidModal();
    startThaidQrLogin();
}

// Start/Restart ThaiD QR Login Process
async function startThaidQrLogin() {
    // Reset States
    clearInterval(thaidPollingInterval);
    clearInterval(thaidCountdownInterval);

    document.getElementById('thaidQrLoadingState').style.display = 'block';
    document.getElementById('thaidQrReadyState').style.display = 'none';
    document.getElementById('thaidQrSuccessState').style.display = 'none';
    document.getElementById('thaidQrFailedState').style.display = 'none';
    document.getElementById('thaidFooterActions').style.display = 'none';
    document.getElementById('thaidQrLoadingText').innerText = 'กำลังเชื่อมต่อระบบ e-Claim สปสช...';

    try {
        const res = await fetch("{{ route('import.eclaim-bot.thaid-qr.start') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Accept': 'application/json'
            }
        });

        const data = await res.json();

        if (res.ok && data.status === 'success' && data.qr_image) {
            currentThaidSessionId = data.session_id;
            showThaidQrReady(data.qr_image, data.ref_code, data.expires_in || 120);
        } else if (res.ok && data.session_id) {
            currentThaidSessionId = data.session_id;
            document.getElementById('thaidQrLoadingText').innerText = 'กำลังขอ QR Code จาก ThaiD...';
            startThaidPolling(currentThaidSessionId);
        } else {
            showThaidFailed('ขอ QR Code ล้มเหลว', data.message || 'ไม่สามารถเปิดหน้าล็อกอิน ThaiD ได้');
        }
    } catch (e) {
        showThaidFailed('เกิดข้อผิดพลาด', e.message);
    }
}

// Show QR Ready State
function showThaidQrReady(qrBase64, refCode, expiresIn) {
    document.getElementById('thaidQrLoadingState').style.display = 'none';
    document.getElementById('thaidQrReadyState').style.display = 'block';
    document.getElementById('thaidFooterActions').style.display = 'block';

    const qrImg = document.getElementById('thaidQrImage');
    qrImg.src = qrBase64;

    const refBadge = document.getElementById('thaidQrRefBadge');
    if (refCode) {
        refBadge.innerHTML = `หมายเลขอ้างอิง: <span class="badge bg-secondary">${refCode}</span>`;
    } else {
        refBadge.innerHTML = '';
    }

    // Start Countdown
    thaidRemainingSeconds = expiresIn || 120;
    updateThaidCountdownDisplay();

    clearInterval(thaidCountdownInterval);
    thaidCountdownInterval = setInterval(() => {
        thaidRemainingSeconds--;
        updateThaidCountdownDisplay();
        if (thaidRemainingSeconds <= 0) {
            clearInterval(thaidCountdownInterval);
            clearInterval(thaidPollingInterval);
            showThaidFailed('QR Code หมดอายุ', 'กรุณากดปุ่มขอ QR Code ใหม่เพื่อทำรายการ');
        }
    }, 1000);

    // Start Status Polling
    startThaidPolling(currentThaidSessionId);
}

// Update Countdown Display MM:SS
function updateThaidCountdownDisplay() {
    const mins = Math.floor(thaidRemainingSeconds / 60);
    const secs = thaidRemainingSeconds % 60;
    const formatted = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    const cdEl = document.getElementById('thaidCountdownText');
    if (cdEl) cdEl.innerText = formatted;
}

// Polling Status
function startThaidPolling(sessionId) {
    clearInterval(thaidPollingInterval);

    thaidPollingInterval = setInterval(async () => {
        if (!sessionId) return;

        try {
            const res = await fetch(`{{ route('import.eclaim-bot.thaid-qr.check') }}?session_id=${sessionId}`, {
                headers: { 'Accept': 'application/json' }
            });

            const data = await res.json();

            if (data.status === 'success' && data.state === 'LOGGED_IN') {
                clearInterval(thaidPollingInterval);
                clearInterval(thaidCountdownInterval);
                showThaidSuccess(data.user || 'เจ้าหน้าที่ e-Claim');
            } else if (data.status === 'success' && data.state === 'QR_READY' && data.qr_image) {
                if (document.getElementById('thaidQrReadyState').style.display === 'none') {
                    showThaidQrReady(data.qr_image, data.ref_code, data.expires_in || 120);
                }
            } else if (data.status === 'error') {
                clearInterval(thaidPollingInterval);
                clearInterval(thaidCountdownInterval);
                showThaidFailed('การล็อกอินล้มเหลว', data.message);
            } else if (data.message) {
                const loadingText = document.getElementById('thaidQrLoadingText');
                if (loadingText && document.getElementById('thaidQrLoadingState').style.display !== 'none') {
                    loadingText.innerText = data.message;
                }
            }
        } catch (e) {
            console.error('Polling error:', e);
        }
    }, 1500);
}

// Success State
function showThaidSuccess(userName) {
    document.getElementById('thaidQrLoadingState').style.display = 'none';
    document.getElementById('thaidQrReadyState').style.display = 'none';
    document.getElementById('thaidQrFailedState').style.display = 'none';
    document.getElementById('thaidFooterActions').style.display = 'none';
    document.getElementById('thaidQrSuccessState').style.display = 'block';

    document.getElementById('thaidSuccessUserText').innerHTML = `ยินดีต้อนรับ <b>${userName}</b>`;

    setTimeout(() => {
        hideEclaimThaidModal();

        if (typeof onThaidLoginSuccessCallback === 'function') {
            onThaidLoginSuccessCallback();
        } else if (typeof checkEclaimRepStatus === 'function') {
            checkEclaimRepStatus();
        } else if (typeof checkEclaimStatus === 'function') {
            checkEclaimStatus();
        } else if (typeof checkEclaimStmOfcStatus === 'function') {
            checkEclaimStmOfcStatus();
        }
    }, 1800);
}

// Failed State
function showThaidFailed(title, message) {
    document.getElementById('thaidQrLoadingState').style.display = 'none';
    document.getElementById('thaidQrReadyState').style.display = 'none';
    document.getElementById('thaidQrSuccessState').style.display = 'none';
    document.getElementById('thaidFooterActions').style.display = 'none';
    document.getElementById('thaidQrFailedState').style.display = 'block';

    document.getElementById('thaidFailedTitle').innerText = title;
    document.getElementById('thaidFailedMsg').innerText = message;
}

// Cleanup Handler
function cleanupThaidSession() {
    clearInterval(thaidPollingInterval);
    clearInterval(thaidCountdownInterval);

    if (currentThaidSessionId) {
        fetch("{{ route('import.eclaim-bot.thaid-qr.cancel') }}", {
            method: 'POST',
            body: JSON.stringify({ session_id: currentThaidSessionId }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            }
        }).catch(() => {});
        currentThaidSessionId = null;
    }
}

// Event Listeners for Modal Close & Action Interception
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalEclaimThaidQr');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            setTimeout(function() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    backdrops[backdrops.length - 1].style.zIndex = '1065';
                }
            }, 10);
        });
        modalEl.addEventListener('hidden.bs.modal', function () {
            cleanupThaidSession();
            if (document.querySelectorAll('.modal.show').length > 0) {
                document.body.classList.add('modal-open');
            }
        });
    }

    const isThaidLicensed = @json(\App\Services\LicenseVerificationService::isModuleLicensed('sync_eclaim_thaid'));
    if (!isThaidLicensed) {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('#btnBotRepSearch, #btnBotStmSearch, #btnStartAutoPull, #btnBotSearch, #btnEclaimRepLoginPopup, #btnEclaimStmLoginPopup, #btnEclaimStmSrtLoginPopup, #btnEclaimStmPvtLoginPopup, #btnEclaimStmOfcLoginPopup, #btnEclaimStmLgoLoginPopup, #btnEclaimStmBmtLoginPopup, #btnEclaimStmBkkLoginPopup');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                showLicenseRequiredAlert();
                return false;
            }
        }, true);
    }
});
</script>
