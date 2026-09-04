<!-- Modal E-Claim Auto Pull (ThaiD Session Powered) -->
<div class="modal fade" id="EclaimAutoPullModal" tabindex="-1" aria-labelledby="EclaimAutoPullModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
      <!-- Modal Header -->
      <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="bi bi-robot fs-5"></i>
          </div>
          <div>
            <h6 class="modal-title fw-bold mb-0" id="EclaimAutoPullModalLabel">Sync e-Claim Client (ระบบใหม่)</h6>
            <span class="small text-white-50" style="font-size: 0.75rem;">ซิงก์ตรงจาก e-Claim สปสช. (Client/home) ด้วย ThaiD Session</span>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnAutoPullCloseX"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4 bg-light">
        
        <!-- ThaiD Session Status Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-3" style="background: #ffffff;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2.5">
                <div id="pullSessionIcon" class="rounded-circle p-2 d-flex align-items-center justify-content-center bg-warning-subtle text-warning-emphasis" style="width: 36px; height: 36px;">
                  <i class="bi bi-shield-check fs-5"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark small" id="pullSessionTitle">สถานะการเชื่อมต่อ e-Claim:</span>
                    <span id="pullSessionBadge" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 0.75rem;">
                      <i class="bi bi-hourglass-split me-1"></i> กำลังตรวจสอบ...
                    </span>
                  </div>
                  <div class="text-muted" style="font-size: 0.75rem;" id="pullSessionDetail">
                    ผู้ใช้งาน: <span class="fw-semibold text-dark" id="pullSessionUser">-</span>
                  </div>
                </div>
              </div>
              <div id="pullSessionAction">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 small" onclick="openThaidFromSyncModal()" style="font-size: 0.78rem;">
                  <i class="bi bi-qr-code-scan me-1"></i> สแกน ThaiD QR
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- 1. FORM SETUP STATE (Before pull) -->
        <div id="pullFormState">
          <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
            <div class="row g-3">
              <!-- Date Range -->
              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1 text-secondary">
                  <i class="bi bi-calendar-event me-1 text-primary"></i> วันที่เริ่มต้น:
                </label>
                <div class="input-group input-group-sm">
                  <input type="text" id="pull_start_date_picker" class="form-control rounded-3" readonly style="background-color: #fff; cursor: pointer;">
                  <input type="hidden" id="pull_start_date" value="{{ date('Y-m-01') }}">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1 text-secondary">
                  <i class="bi bi-calendar-check me-1 text-primary"></i> ถึงวันที่:
                </label>
                <div class="input-group input-group-sm">
                  <input type="text" id="pull_end_date_picker" class="form-control rounded-3" readonly style="background-color: #fff; cursor: pointer;">
                  <input type="hidden" id="pull_end_date" value="{{ date('Y-m-d') }}">
                </div>
              </div>

              <!-- Benefit Scheme (สิทธิประโยชน์) -->
              <div class="col-md-12">
                <label class="form-label fw-bold small mb-1 text-secondary">
                  <i class="bi bi-card-checklist me-1 text-primary"></i> สิทธิประโยชน์ (Benefit Scheme):
                </label>
                <select id="pull_hipdata" class="form-select form-select-sm rounded-3">
                  <option value="">-- ทุกสิทธิประโยชน์ (ทั้งหมด) --</option>
                  <option value="UCS">UCS สิทธิ UC (บัตรทอง / ประกันสุขภาพถ้วนหน้า)</option>
                  <option value="OFC">OFC ข้าราชการ / กรมบัญชีกลาง</option>
                  <option value="SSS">SSS ประกันสังคม</option>
                  <option value="LGO">LGO สิทธิ อปท. (องค์กรปกครองส่วนท้องถิ่น)</option>
                  <option value="NHS">NHS สิทธิ สปสช.</option>
                  <option value="STP">STP บุคคลผู้มีปัญหาสถานะและสิทธิ</option>
                  <option value="BKK">BKK ข้าราชการ กรุงเทพมหานคร</option>
                  <option value="BMT">BMT สิทธิองค์การขนส่งมวลชนกรุงเทพ</option>
                  <option value="SRT">SRT สิทธิการรถไฟแห่งประเทศไทย</option>
                  <option value="PVT">PVT สิทธิครูเอกชน</option>
                </select>
              </div>

              <!-- Patient Type (ประเภทผู้ป่วย) -->
              <div class="col-md-12">
                <label class="form-label fw-bold small mb-1 text-secondary">
                  <i class="bi bi-person-badge me-1 text-primary"></i> ประเภทผู้ป่วย:
                </label>
                <div class="d-flex gap-3">
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="pull_patient_type" id="pull_pt_all" value="ALL" checked>
                    <label class="form-check-label small" for="pull_pt_all">ทุกประเภท (OPD + IPD)</label>
                  </div>
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="pull_patient_type" id="pull_pt_opd" value="OPD">
                    <label class="form-check-label small" for="pull_pt_opd">ผู้ป่วยนอก (OPD)</label>
                  </div>
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="pull_patient_type" id="pull_pt_ipd" value="IPD">
                    <label class="form-check-label small" for="pull_pt_ipd">ผู้ป่วยใน (IPD)</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-info border-0 rounded-4 p-3 small mb-0 d-flex gap-2">
            <i class="bi bi-info-circle-fill text-primary fs-5 flex-shrink-0"></i>
            <div>
              <b>ระบบจะวนดึงข้อมูลครบทุกหน้าอัตโนมัติ:</b> และทำการอัปเดตลงตาราง <code>eclaim_status</code> โดยจับคู่ด้วย <b>AN (ผู้ป่วยใน)</b> หรือ <b>SEQ/VN (ผู้ป่วยนอก)</b> ป้องกันข้อมูลซ้ำซ้อน 100%
            </div>
          </div>
        </div>

        <!-- 2. PROGRESS STATE (During pull) -->
        <div id="pullProgressState" class="py-4 text-center" style="display: none;">
          <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem;" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <h6 class="fw-bold text-dark mb-1" id="pullProgressTitle">กำลังเชื่อมต่อและดึงข้อมูลจาก E-Claim สปสช...</h6>
          <p class="text-muted small mb-3" id="pullProgressDesc">ระบบกำลังกวาดรายการข้อมูลและตรวจสอบสถานะการเคลม</p>
          
          <div class="progress rounded-pill mb-2 shadow-sm" style="height: 12px;">
            <div id="pullProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%;"></div>
          </div>
          <span class="small text-secondary font-monospace" id="pullProgressPercent">กำลังประมวลผลข้อมูล...</span>
        </div>

        <!-- 3. RESULT STATE (After pull completes) -->
        <div id="pullResultState" style="display: none;">
          <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 mb-3 d-flex align-items-center gap-2.5">
            <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
              <i class="bi bi-check-lg fs-5"></i>
            </div>
            <div>
              <h6 class="fw-bold text-success mb-0" id="pullResultHeading">ดึงข้อมูลสำเร็จ!</h6>
              <div class="small text-dark" id="pullResultMessage">-</div>
            </div>
          </div>

          <!-- Summary Stats 4 Cards -->
          <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 text-center p-2 bg-primary-subtle text-primary h-100">
                <span class="small text-muted" style="font-size: 0.72rem;">พบทั้งหมด</span>
                <h4 class="fw-bold mb-0 my-1" id="statTotalFound">0</h4>
                <span class="small" style="font-size: 0.7rem;">รายการ</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 text-center p-2 bg-info-subtle text-info h-100">
                <span class="small text-muted" style="font-size: 0.72rem;">ประเภท</span>
                <div class="fw-bold small my-1" id="statPtType">OPD: 0 | IPD: 0</div>
                <span class="small" style="font-size: 0.7rem;">ราย</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 text-center p-2 bg-success-subtle text-success h-100">
                <span class="small text-muted" style="font-size: 0.72rem;">เพิ่มใหม่</span>
                <h4 class="fw-bold mb-0 my-1" id="statInserted">0</h4>
                <span class="small" style="font-size: 0.7rem;">รายการ</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 text-center p-2 bg-warning-subtle text-warning-emphasis h-100">
                <span class="small text-muted" style="font-size: 0.72rem;">อัปเดตสถานะ</span>
                <h4 class="fw-bold mb-0 my-1" id="statUpdated">0</h4>
                <span class="small" style="font-size: 0.7rem;">รายการเดิม</span>
              </div>
            </div>
          </div>

          <!-- Breakdown Details -->
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-2">
            <h6 class="fw-bold small mb-2 text-dark border-bottom pb-1.5"><i class="bi bi-pie-chart-fill me-1 text-primary"></i> รายละเอียดตามกลุ่มสิทธิ & สถานะ:</h6>
            <div class="d-flex flex-wrap gap-1.5 mb-2" id="statSchemeBadges"></div>
            <div class="d-flex flex-wrap gap-1.5" id="statStatusBadges"></div>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-light border-0 py-2.5 px-4 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3.5" data-bs-dismiss="modal" id="btnPullCancel">
          <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
        </button>

        <div id="pullFooterActions">
          <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm d-inline-flex align-items-center" id="btnStartAutoPull" onclick="submitAutoPull()">
            <i class="bi bi-arrow-repeat me-2"></i> <span>เริ่ม Sync e-Claim Client</span>
          </button>
        </div>

        <div id="pullCompletedFooterActions" style="display: none;">
          <button type="button" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm" data-bs-dismiss="modal">
            <i class="bi bi-table me-1"></i> ดูข้อมูลในตารางทันที
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let hasPulledSuccessfully = false;

// --- Open ThaiD QR from inside Sync Modal (Smooth Transition) ---
function openThaidFromSyncModal() {
    const autoModalEl = document.getElementById('EclaimAutoPullModal');
    if (autoModalEl) {
        if (window.bootstrap && window.bootstrap.Modal) {
            const m = window.bootstrap.Modal.getInstance(autoModalEl) || window.bootstrap.Modal.getOrCreateInstance(autoModalEl);
            if (m) m.hide();
        } else if (window.jQuery && typeof $(autoModalEl).modal === 'function') {
            $(autoModalEl).modal('hide');
        }
    }

    setTimeout(function() {
        const thaidEl = document.getElementById('modalEclaimThaidQr');
        let reopened = false;

        const reopenSyncModal = function() {
            if (reopened) return;
            reopened = true;
            if (thaidEl) {
                thaidEl.removeEventListener('hidden.bs.modal', reopenSyncModal);
            }
            if (window.jQuery && thaidEl) {
                $(thaidEl).off('hidden.bs.modal', reopenSyncModal);
            }
            setTimeout(function() {
                openSyncEclaimClientModal();
                refreshPullModalBotStatus();
            }, 250);
        };

        if (thaidEl) {
            thaidEl.addEventListener('hidden.bs.modal', reopenSyncModal, { once: true });
            if (window.jQuery) {
                $(thaidEl).one('hidden.bs.modal', reopenSyncModal);
            }
        }

        openEclaimThaidQrModal(function() {
            refreshPullModalBotStatus();
            reopenSyncModal();
        });
    }, 250);
}

// --- Open Sync e-Claim Client Modal Smoothly (prevents modal backdrop lock) ---
function openSyncEclaimClientModal(fromModalId) {
    const targetEl = document.getElementById('EclaimAutoPullModal');
    if (!targetEl) {
        console.error('EclaimAutoPullModal not found');
        return;
    }

    if (fromModalId) {
        const fromEl = document.getElementById(fromModalId);
        if (fromEl) {
            try {
                if (window.bootstrap && window.bootstrap.Modal) {
                    const fromModal = window.bootstrap.Modal.getInstance(fromEl) || window.bootstrap.Modal.getOrCreateInstance(fromEl);
                    if (fromModal) fromModal.hide();
                } else if (window.jQuery && typeof $(fromEl).modal === 'function') {
                    $(fromEl).modal('hide');
                }
            } catch (err) {
                console.warn('Error hiding fromModal:', err);
            }
        }
    }

    setTimeout(function() {
        try {
            if (window.bootstrap && window.bootstrap.Modal) {
                const targetModal = window.bootstrap.Modal.getOrCreateInstance(targetEl);
                targetModal.show();
            } else if (window.jQuery && typeof $(targetEl).modal === 'function') {
                $(targetEl).modal('show');
            }
        } catch (err) {
            console.error('Error showing EclaimAutoPullModal:', err);
        }
    }, 250);
}

// --- E-Claim Auto Pull Functions ---
function refreshPullModalBotStatus() {
    fetch("{{ route('check.eclaim_status.bot_status') }}")
        .then(res => res.json())
        .then(data => {
            const icon = document.getElementById('pullSessionIcon');
            const badge = document.getElementById('pullSessionBadge');
            const userEl = document.getElementById('pullSessionUser');
            const btnStart = document.getElementById('btnStartAutoPull');

            if (data.connected) {
                if (icon) {
                    icon.className = "rounded-circle p-2 d-flex align-items-center justify-content-center bg-success-subtle text-success";
                    icon.innerHTML = '<i class="bi bi-shield-check fs-5"></i>';
                }
                if (badge) {
                    badge.className = "badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5";
                    badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> เชื่อมต่อแล้ว';
                }
                if (userEl) userEl.innerText = data.user || 'เจ้าหน้าที่ e-Claim';
                if (btnStart) btnStart.disabled = false;
            } else {
                if (icon) {
                    icon.className = "rounded-circle p-2 d-flex align-items-center justify-content-center bg-warning-subtle text-warning-emphasis";
                    icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-5"></i>';
                }
                if (badge) {
                    badge.className = "badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5";
                    badge.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> ยังไม่ได้เชื่อมต่อ';
                }
                if (userEl) userEl.innerText = data.message || 'กรุณาสแกน ThaiD QR ก่อน';
            }
        })
        .catch(() => {});
}

function submitAutoPull() {
    const startDate = document.getElementById('pull_start_date') ? document.getElementById('pull_start_date').value : '';
    const endDate = document.getElementById('pull_end_date') ? document.getElementById('pull_end_date').value : '';
    const hipdata = document.getElementById('pull_hipdata') ? document.getElementById('pull_hipdata').value : '';
    const ptRadios = document.getElementsByName('pull_patient_type');
    let patientType = 'ALL';
    for (let r of ptRadios) {
        if (r.checked) { patientType = r.value; break; }
    }

    // Show Progress State
    if (document.getElementById('pullFormState')) document.getElementById('pullFormState').style.display = 'none';
    if (document.getElementById('pullResultState')) document.getElementById('pullResultState').style.display = 'none';
    if (document.getElementById('pullProgressState')) document.getElementById('pullProgressState').style.display = 'block';
    if (document.getElementById('pullFooterActions')) document.getElementById('pullFooterActions').style.display = 'none';
    if (document.getElementById('pullCompletedFooterActions')) document.getElementById('pullCompletedFooterActions').style.display = 'none';
    if (document.getElementById('btnPullCancel')) document.getElementById('btnPullCancel').disabled = true;
    if (document.getElementById('btnAutoPullCloseX')) document.getElementById('btnAutoPullCloseX').disabled = true;

    fetch("{{ route('check.eclaim_status.auto_pull') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': "{{ csrf_token() }}",
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            start_date: startDate,
            end_date: endDate,
            hipdata: hipdata,
            patient_type: patientType
        })
    })
    .then(res => res.json())
    .then(data => {
        if (document.getElementById('pullProgressState')) document.getElementById('pullProgressState').style.display = 'none';
        if (document.getElementById('btnPullCancel')) document.getElementById('btnPullCancel').disabled = false;
        if (document.getElementById('btnAutoPullCloseX')) document.getElementById('btnAutoPullCloseX').disabled = false;

        if (data.status === 'success' || data.status === 'info') {
            hasPulledSuccessfully = true;
            if (document.getElementById('pullResultState')) document.getElementById('pullResultState').style.display = 'block';
            if (document.getElementById('pullCompletedFooterActions')) document.getElementById('pullCompletedFooterActions').style.display = 'block';

            if (document.getElementById('pullResultHeading')) document.getElementById('pullResultHeading').innerText = data.status === 'success' ? 'ดึงข้อมูลสำเร็จ!' : 'ผลการตรวจสอบ';
            if (document.getElementById('pullResultMessage')) document.getElementById('pullResultMessage').innerText = data.message;

            const stats = data.stats || {};
            if (document.getElementById('statTotalFound')) document.getElementById('statTotalFound').innerText = (stats.total || 0).toLocaleString();
            if (document.getElementById('statPtType')) document.getElementById('statPtType').innerText = `OPD: ${stats.opd || 0} | IPD: ${stats.ipd || 0}`;
            if (document.getElementById('statInserted')) document.getElementById('statInserted').innerText = (stats.inserted || 0).toLocaleString();
            if (document.getElementById('statUpdated')) document.getElementById('statUpdated').innerText = (stats.updated || 0).toLocaleString();

            // Scheme badges
            const schemeBox = document.getElementById('statSchemeBadges');
            if (schemeBox) {
                schemeBox.innerHTML = '';
                if (stats.by_scheme && Object.keys(stats.by_scheme).length > 0) {
                    for (let [scheme, count] of Object.entries(stats.by_scheme)) {
                        schemeBox.innerHTML += `<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small me-1 mb-1">${scheme}: ${count} ราย</span>`;
                    }
                } else {
                    schemeBox.innerHTML = '<span class="text-muted small">ไม่มีข้อมูลสิทธิ</span>';
                }
            }

            // Status badges
            const statusBox = document.getElementById('statStatusBadges');
            if (statusBox) {
                statusBox.innerHTML = '';
                if (stats.by_status && Object.keys(stats.by_status).length > 0) {
                    for (let [st, count] of Object.entries(stats.by_status)) {
                        statusBox.innerHTML += `<span class="badge bg-secondary-subtle text-dark border rounded-pill px-2.5 py-1 small me-1 mb-1">${st}: ${count} ราย</span>`;
                    }
                }
            }
        } else if (data.status === 'need_login') {
            // Session expired -> Prompt ThaiD QR
            if (document.getElementById('pullFormState')) document.getElementById('pullFormState').style.display = 'block';
            if (document.getElementById('pullFooterActions')) document.getElementById('pullFooterActions').style.display = 'block';
            
            var autoPullModalEl = document.getElementById('EclaimAutoPullModal');
            if (autoPullModalEl) {
                if (window.bootstrap && window.bootstrap.Modal) {
                    var autoPullModal = window.bootstrap.Modal.getInstance(autoPullModalEl);
                    if (autoPullModal) autoPullModal.hide();
                } else if (window.jQuery && typeof $(autoPullModalEl).modal === 'function') {
                    $(autoPullModalEl).modal('hide');
                }
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Session e-Claim หมดอายุ',
                    text: 'กรุณาสแกน ThaiD QR Code เพื่อเข้าสู่ระบบ e-Claim ก่อนเริ่มดึงข้อมูลครับ',
                    confirmButtonText: 'สแกน ThaiD ทันที',
                    showCancelButton: true,
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        openEclaimThaidQrModal(function() {
                            setTimeout(function() {
                                openSyncEclaimClientModal();
                                submitAutoPull();
                            }, 250);
                        });
                    }
                });
            } else {
                openEclaimThaidQrModal(function() {
                    setTimeout(function() {
                        openSyncEclaimClientModal();
                        submitAutoPull();
                    }, 250);
                });
            }
        } else {
            // Show Error
            if (document.getElementById('pullFormState')) document.getElementById('pullFormState').style.display = 'block';
            if (document.getElementById('pullFooterActions')) document.getElementById('pullFooterActions').style.display = 'block';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'ดึงข้อมูลไม่สำเร็จ',
                    text: data.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อกับ e-Claim สปสช.'
                });
            } else {
                alert(data.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อกับ e-Claim สปสช.');
            }
        }
    })
    .catch(err => {
        if (document.getElementById('pullProgressState')) document.getElementById('pullProgressState').style.display = 'none';
        if (document.getElementById('pullFormState')) document.getElementById('pullFormState').style.display = 'block';
        if (document.getElementById('pullFooterActions')) document.getElementById('pullFooterActions').style.display = 'block';
        if (document.getElementById('btnPullCancel')) document.getElementById('btnPullCancel').disabled = false;
        if (document.getElementById('btnAutoPullCloseX')) document.getElementById('btnAutoPullCloseX').disabled = false;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: ' + err.message
            });
        } else {
            alert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: ' + err.message);
        }
    });
}

function reloadEclaimTable() {
    hasPulledSuccessfully = false;
    const pullStart = document.getElementById('pull_start_date') ? document.getElementById('pull_start_date').value : '';
    const pullEnd = document.getElementById('pull_end_date') ? document.getElementById('pull_end_date').value : '';
    const pullHip = document.getElementById('pull_hipdata') ? document.getElementById('pull_hipdata').value : '';

    if (window.jQuery) {
        if (pullStart) {
            $('#start_date').val(pullStart);
            if ($('#start_date_picker').length && $.fn.datepicker) {
                $('#start_date_picker').datepicker('setDate', new Date(pullStart));
            }
        }
        if (pullEnd) {
            $('#end_date').val(pullEnd);
            if ($('#end_date_picker').length && $.fn.datepicker) {
                $('#end_date_picker').datepicker('setDate', new Date(pullEnd));
            }
        }
        if (pullHip && $('select[name="hipdata"]').length) {
            $('select[name="hipdata"]').val(pullHip);
        }

        // Case 1: If on check/eclaim_status page with DataTable #list
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#list')) {
            $('#list').DataTable().ajax.reload(null, false);
            return;
        }

        // Case 2: If on claim pages (claim_op/*, claim_ip/*) having loadDashboard()
        if (typeof loadDashboard === 'function') {
            const bYear = $('#form_budget_year select[name="budget_year"]').val() || '';
            const sDate = pullStart || $('#start_date').val() || '';
            const eDate = pullEnd || $('#end_date').val() || '';
            loadDashboard({
                budget_year: bYear,
                start_date: sDate,
                end_date: eDate,
                skip_chart: 0
            });
            return;
        }
    } else if (typeof loadDashboard === 'function') {
        loadDashboard({
            start_date: pullStart,
            end_date: pullEnd,
            skip_chart: 0
        });
        return;
    }

    // Case 3: Fallback for all other pages -> Reload page to get fresh database data
    window.location.reload();
}

document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery) {
        // Init Modal Datepickers if datepicker plugin is loaded
        if ($.fn.datepicker) {
            $('#pull_start_date_picker, #pull_end_date_picker').datepicker({
                format: 'd M yyyy',
                todayBtn: "linked",
                todayHighlight: true,
                autoclose: true,
                language: 'th-th',
                thaiyear: true,
                zIndexOffset: 1100
            });

            $('#pull_start_date_picker, #pull_end_date_picker').on('changeDate', function(e) {
                var date = e.date;
                var targetId = $(this).attr('id').replace('_picker', '');
                var hiddenInput = $('#' + targetId);
                if(date) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear();
                    hiddenInput.val(year + "-" + month + "-" + day);
                } else {
                    hiddenInput.val('');
                }
            });
        }

        // On Modal Show -> Reset and Prepopulate
        $('#EclaimAutoPullModal').on('show.bs.modal', function () {
            if (document.getElementById('pullFormState')) document.getElementById('pullFormState').style.display = 'block';
            if (document.getElementById('pullProgressState')) document.getElementById('pullProgressState').style.display = 'none';
            if (document.getElementById('pullResultState')) document.getElementById('pullResultState').style.display = 'none';
            if (document.getElementById('pullFooterActions')) document.getElementById('pullFooterActions').style.display = 'block';
            if (document.getElementById('pullCompletedFooterActions')) document.getElementById('pullCompletedFooterActions').style.display = 'none';
            if (document.getElementById('btnPullCancel')) document.getElementById('btnPullCancel').disabled = false;
            if (document.getElementById('btnAutoPullCloseX')) document.getElementById('btnAutoPullCloseX').disabled = false;

            // Sync current dates if present
            var curStart = $('#start_date').val();
            var curEnd = $('#end_date').val();
            var curHip = $('select[name="hipdata"]').val() || '';

            // Auto-detect benefit scheme from page URL if not specified
            if (!curHip) {
                const path = window.location.pathname.toLowerCase();
                if (path.includes('ofc')) curHip = 'OFC';
                else if (path.includes('ucs')) curHip = 'UCS';
                else if (path.includes('sss')) curHip = 'SSS';
                else if (path.includes('lgo')) curHip = 'LGO';
                else if (path.includes('stp')) curHip = 'STP';
                else if (path.includes('bkk')) curHip = 'BKK';
                else if (path.includes('bmt')) curHip = 'BMT';
                else if (path.includes('srt')) curHip = 'SRT';
                else if (path.includes('pvt')) curHip = 'PVT';
            }

            if (curStart) {
                $('#pull_start_date').val(curStart);
                if ($.fn.datepicker) {
                    $('#pull_start_date_picker').datepicker('setDate', new Date(curStart));
                }
            }
            if (curEnd) {
                $('#pull_end_date').val(curEnd);
                if ($.fn.datepicker) {
                    $('#pull_end_date_picker').datepicker('setDate', new Date(curEnd));
                }
            }
            if (curHip) {
                $('#pull_hipdata').val(curHip);
            }

            // Auto-detect patient type based on OPD vs IPD url
            const path = window.location.pathname.toLowerCase();
            if (path.includes('claim_op')) {
                $('#pull_pt_opd').prop('checked', true);
            } else if (path.includes('claim_ip')) {
                $('#pull_pt_ipd').prop('checked', true);
            } else {
                $('#pull_pt_all').prop('checked', true);
            }

            refreshPullModalBotStatus();
        });

        // Auto reload table or dashboard when modal is closed after successful pull
        $('#EclaimAutoPullModal').on('hidden.bs.modal', function () {
            if (hasPulledSuccessfully) {
                hasPulledSuccessfully = false;
                reloadEclaimTable();
            }
        });
    }
});
</script>
