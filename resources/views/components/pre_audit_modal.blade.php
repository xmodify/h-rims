<!-- Modal Pre-Audit ตาสีแดง 👁️ -->
<div class="modal fade" id="preAuditModal" tabindex="-1" aria-labelledby="preAuditModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-gradient text-white py-3 px-4" id="preAuditModalHeader" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4" id="preAuditHeaderIcon">👁️</span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="preAuditModalLabel" style="font-size: 1.15rem;">
                            ผลการตรวจสอบก่อนส่งเบิก (Pre-Audit / ตาสีแดง)
                        </h5>
                        <small class="text-white-50" id="preAuditSubtitle">จำลองการตรวจสอบเงื่อนไข e-Claim / FDH / กองทุน สปสช.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light" id="preAuditModalBody" style="min-height: 280px;">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted fw-semibold">กำลังตรวจสอบข้อมูลและจำลองเงื่อนไข C-Codes...</div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold" id="btnRecheckAudit">
                    <i class="bi bi-arrow-repeat me-1"></i> ตรวจสอบซ้ำ (Re-check)
                </button>
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">
                    ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAuditVn = null;
let currentAuditAn = null;

/**
 * Open Pre-Audit Modal for OPD Visit
 */
function openPreAuditModal(vn) {
    currentAuditVn = vn;
    currentAuditAn = null;
    loadAuditData({ vn: vn });
}

/**
 * Open Pre-Audit Modal for IPD Admission
 */
function openPreAuditModalIpd(an) {
    currentAuditVn = null;
    currentAuditAn = an;
    loadAuditData({ an: an });
}

function loadAuditData(params) {
    const body = document.getElementById('preAuditModalBody');
    const header = document.getElementById('preAuditModalHeader');
    const headerIcon = document.getElementById('preAuditHeaderIcon');
    
    body.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 text-muted fw-semibold">กำลังตรวจสอบข้อมูลและจำลองเงื่อนไข C-Codes...</div>
        </div>
    `;

    $('#preAuditModal').modal('show');

    const url = params.vn ? "{{ url('claim/audit/visit_details') }}" : "{{ url('claim/audit/admission_details') }}";

    $.get(url, params)
    .done(function(res) {
        if (!res.success || !res.data) {
            body.innerHTML = `
                <div class="alert alert-danger py-3 text-center rounded-3">
                    <i class="bi bi-exclamation-octagon-fill fs-3 d-block mb-2"></i>
                    ไม่สามารถดึงข้อมูลผลการตรวจสอบได้
                </div>
            `;
            return;
        }

        const data = res.data;
        const v = data.visit_info || {};
        const summary = data.summary || { errors: 0, warnings: 0 };
        const issues = data.issues || [];

        // Dynamic Header background
        if (data.status === 'FAIL') {
            header.style.background = 'linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%)';
            headerIcon.textContent = '🔴';
        } else if (data.status === 'WARN') {
            header.style.background = 'linear-gradient(135deg, #d97706 0%, #b45309 100%)';
            headerIcon.textContent = '🟡';
        } else {
            header.style.background = 'linear-gradient(135deg, #059669 0%, #047857 100%)';
            headerIcon.textContent = '🟢';
        }

        let html = '';

        // Patient Summary Card
        html += `
        <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="fw-bold fs-6 text-dark">${v.ptname || '-'}</div>
                        <div class="text-muted small">
                            HN: <span class="fw-semibold text-dark">${v.hn || '-'}</span> | 
                            ${params.vn ? 'VN' : 'AN'}: <span class="fw-semibold text-dark">${v.vn || '-'}</span> | 
                            CID: <span class="fw-semibold text-dark">${v.cid || '-'}</span>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill mb-1">
                            ${v.pttype_name || v.pttype || 'ไม่ระบุสิทธิ'}
                        </span>
                        <div class="text-muted small">
                            วันที่รับบริการ: <span class="fw-semibold text-dark">${v.vstdate || '-'}</span> 
                            ${v.vsttime ? `เวลา ${v.vsttime} น.` : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;

        // Status Summary Banner
        if (data.status === 'FAIL') {
            html += `
            <div class="alert alert-danger border-0 shadow-sm rounded-3 p-3 mb-3 d-flex align-items-start" style="background-color: #fef2f2; border-left: 5px solid #dc2626 !important;">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-3 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold text-danger fs-6 mb-1">
                        🔴 ตาสีแดง: พบ ${summary.errors} ข้อผิดพลาดที่อาจทำให้ติด C (ถูกปฏิเสธการจ่าย/Reject)
                    </div>
                    <div class="text-secondary small">
                        กรุณาดำเนินการแก้ไขข้อมูลในโปรแกรม HOSxP ตามจุดที่ระบุในรายการด้านล่าง แล้วกดตรวจสอบซ้ำก่อนส่งออก e-Claim / FDH
                    </div>
                </div>
            </div>
            `;
        } else if (data.status === 'WARN') {
            html += `
            <div class="alert alert-warning border-0 shadow-sm rounded-3 p-3 mb-3 d-flex align-items-start" style="background-color: #fffbeb; border-left: 5px solid #f59e0b !important;">
                <i class="bi bi-info-circle-fill text-warning fs-3 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark fs-6 mb-1">
                        🟡 ข้อควรระวัง: พบ ${summary.warnings} รายการที่ควรตรวจสอบเพิ่มเติม
                    </div>
                    <div class="text-secondary small">
                        ข้อมูลผ่านเกณฑ์เบื้องต้น แต่มีข้อสังเกตหรือเงื่อนไขสิทธิที่แนะนำให้ดำเนินการเพื่อความสมบูรณ์
                    </div>
                </div>
            </div>
            `;
        } else {
            html += `
            <div class="alert alert-success border-0 shadow-sm rounded-3 p-3 mb-3 d-flex align-items-center" style="background-color: #f0fdf4; border-left: 5px solid #16a34a !important;">
                <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                <div>
                    <div class="fw-bold text-success fs-6 mb-0">
                        🟢 ผ่านเกณฑ์สมบูรณ์: ไม่พบความเสี่ยงติด C-Code
                    </div>
                    <div class="text-secondary small">ข้อมูลโครงสร้าง 16 แฟ้มและเงื่อนไขการเบิกจ่ายครบถ้วน พร้อมสำหรับการส่งออก</div>
                </div>
            </div>
            `;
        }

        // Issues List
        if (issues.length > 0) {
            html += `<h6 class="fw-bold text-dark mt-3 mb-2 ps-1"><i class="bi bi-list-check me-1"></i> รายการที่ตรวจพบ (${issues.length} รายการ)</h6>`;
            html += `<div class="d-flex flex-column gap-2">`;

            issues.forEach((iss, idx) => {
                const isDanger = iss.severity === 'danger';
                const cardBorder = isDanger ? 'border-danger-subtle' : 'border-warning-subtle';
                const badgeColor = isDanger ? 'bg-danger text-white' : 'bg-warning text-dark';
                const fileBadge = iss.file ? `<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">${iss.file}.txt</span>` : '';

                html += `
                <div class="card ${cardBorder} shadow-sm rounded-3 overflow-hidden bg-white">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge ${badgeColor} px-2 py-1 rounded-pill fw-bold">
                                    <i class="bi ${isDanger ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill'} me-1"></i>
                                    รหัส ${iss.code}
                                </span>
                                ${fileBadge}
                            </div>
                            <span class="badge ${isDanger ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-dark'} small">
                                ${isDanger ? 'เสี่ยงติด C แน่นอน' : 'ข้อควรระวัง'}
                            </span>
                        </div>
                        <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">
                            ${iss.title}
                        </div>
                        ${iss.description ? `<div class="text-muted small mb-2"><i class="bi bi-card-text me-1"></i> ${iss.description}</div>` : ''}
                        
                        <!-- Guide / แก้ไข -->
                        <div class="p-2 rounded-2 small mt-2" style="background-color: #f8fafc; border: 1px dashed #cbd5e1;">
                            <div class="text-primary fw-bold mb-1">
                                <i class="bi bi-lightbulb-fill text-warning me-1"></i> คำแนะนำแนวทางแก้ไข:
                            </div>
                            <div class="text-dark ps-3 mb-1">${iss.guide || '-'}</div>
                            ${iss.location ? `
                            <div class="text-muted ps-3 small mt-1">
                                <span class="fw-semibold text-danger"><i class="bi bi-geo-alt-fill me-1"></i>จุดที่ต้องไปแก้ไขใน HOSxP:</span> 
                                <span class="text-dark bg-white px-2 py-0.5 border rounded">${iss.location}</span>
                            </div>` : ''}
                        </div>
                    </div>
                </div>
                `;
            });

            html += `</div>`;
        }

        body.innerHTML = html;

        // Bind Re-check button
        document.getElementById('btnRecheckAudit').onclick = function() {
            loadAuditData(params);
        };
    })
    .fail(function(err) {
        body.innerHTML = `
            <div class="alert alert-danger py-3 text-center rounded-3">
                <i class="bi bi-wifi-off fs-3 d-block mb-2"></i>
                เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์
            </div>
        `;
    });
}
</script>
