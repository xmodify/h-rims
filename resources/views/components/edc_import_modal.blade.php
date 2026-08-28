@props([
    'modalId' => 'importEdcModal',
    'title' => 'นำเข้าและซิงค์ไฟล์เลขอนุมัติ EDC (ธนาคารกรุงไทย)',
    'onSuccessCallback' => 'loadDashboard'
])

<!-- Modal Import EDC (KTB Corporate Online & Manual File) -->
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header bg-success text-white py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-credit-card-2-front-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="{{ $modalId }}Label">{{ $title }}</h5>
                        <span class="small text-white-50">KTB Corporate Online : Medical Welfare Healthcare Download</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                
                <!-- Nav Tabs (Sync KTB vs Manual Upload) -->
                <ul class="nav nav-pills nav-fill mb-3 bg-white p-1 rounded-pill shadow-sm border" id="edcSourceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-bold py-2" id="tab-ktb-sync" data-bs-toggle="pill" data-bs-target="#content-ktb-sync" type="button" role="tab">
                            <i class="bi bi-arrow-repeat me-1 text-primary"></i> ซิงค์จาก KTB Corporate อัตโนมัติ
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold py-2" id="tab-manual-upload" data-bs-toggle="pill" data-bs-target="#content-manual-upload" type="button" role="tab">
                            <i class="bi bi-folder2-open me-1 text-warning"></i> เลือกไฟล์ ZIP / TXT จากเครื่อง
                        </button>
                    </li>
                </ul>

                <!-- Tab Contents -->
                <div class="tab-content mb-3" id="edcSourceTabsContent">
                    
                    <!-- TAB 1: KTB Sync -->
                    <div class="tab-pane fade show active" id="content-ktb-sync" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted mb-1">
                                        <i class="bi bi-calendar3 me-1"></i> จากวันที่ (From Date)
                                    </label>
                                    <input type="hidden" id="edc_from_date" name="from_date" value="">
                                    <input type="text" id="edc_from_date_picker" class="form-control form-control-sm datepicker_th text-center" readonly style="cursor: pointer; background-color: #fff;">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted mb-1">
                                        <i class="bi bi-calendar3 me-1"></i> ถึงวันที่ (To Date)
                                    </label>
                                    <input type="hidden" id="edc_to_date" name="to_date" value="">
                                    <input type="text" id="edc_to_date_picker" class="form-control form-control-sm datepicker_th text-center" readonly style="cursor: pointer; background-color: #fff;">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted mb-1">ช่วงวันด่วน</label>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2" onclick="setEdcDateRange(7)">7 วันล่าสุด</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2" onclick="setEdcDateRange(1)">วันนี้</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2" onclick="setEdcDateRange(2)">เมื่อวาน</button>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm py-2 fw-bold" id="btnSyncKtb">
                                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> ซิงค์ไฟล์จาก KTB
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                <span class="badge bg-light text-secondary border rounded-pill fw-normal" style="font-size: 0.75rem;">
                                    <i class="bi bi-info-circle me-1"></i> ระบบ KTB Corporate กำหนดให้เลือกช่วงค้นหาได้ครั้งละไม่เกิน 7 วัน
                                </span>
                                <div id="ktbCredentialStatus" class="small text-muted">
                                    <span class="spinner-border spinner-border-sm text-secondary me-1"></span> กำลังตรวจสอบการตั้งค่า KTB...
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Manual Upload -->
                    <div class="tab-pane fade" id="content-manual-upload" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                            <form id="edcManualUploadForm" enctype="multipart/form-data" class="row g-3 align-items-center">
                                <div class="col-md-9">
                                    <label class="form-label small fw-bold text-muted mb-1">
                                        <i class="bi bi-file-earmark-zip me-1"></i> เลือกไฟล์ ZIP หรือไฟล์ Text (.txt) จากเครื่อง
                                    </label>
                                    <input type="file" class="form-control form-control-sm" id="manual_edc_file" name="zip_file[]" accept=".zip,.txt" multiple>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-warning text-dark btn-sm w-100 rounded-pill shadow-sm py-2 fw-bold" id="btnUploadManualEdc">
                                        <i class="bi bi-search me-1"></i> ตรวจสอบไฟล์
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <!-- Import Mode Bar -->
                <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fw-bold text-dark small"><i class="bi bi-gear-fill me-1 text-secondary"></i> โหมดการนำเข้า:</span>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="edc_import_mode" id="mode_skip" value="skip_existing" checked>
                                <label class="form-check-label small fw-semibold text-success" for="mode_skip" title="ข้ามรายการเดิมที่มีในฐานข้อมูลแล้ว นำเข้าเฉพาะรายการใหม่">
                                    <i class="bi bi-plus-circle me-1"></i> เพิ่มเฉพาะรายการใหม่ (Skip Duplicates)
                                </label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="edc_import_mode" id="mode_overwrite" value="overwrite">
                                <label class="form-check-label small fw-semibold text-primary" for="mode_overwrite" title="อัปเดตข้อมูลทับรายการเดิมตามเลขอนุมัติ">
                                    <i class="bi bi-arrow-repeat me-1"></i> เขียนทับข้อมูลเดิม (Overwrite / Update)
                                </label>
                            </div>
                        </div>
                        <div id="previewSummaryBadge" class="small fw-bold text-muted"></div>
                    </div>
                </div>

                <!-- Preview Table Container -->
                <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white" id="edcPreviewContainer" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-table me-1 text-primary"></i> รายการไฟล์รายงาน EDC ที่พร้อมนำเข้า
                        </h6>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-light text-dark border rounded-pill" id="fileCountBadge">0 ไฟล์</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-0" style="font-size: 0.75rem;" onclick="selectAllEdcFiles(true)">เลือกทั้งหมด</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-0" style="font-size: 0.75rem;" onclick="selectAllEdcFiles(false)">ปลดเลือก</button>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle mb-0" id="tableEdcFiles" style="font-size: 0.85rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="checkAllFiles" onchange="toggleCheckAllFiles(this.checked)">
                                    </th>
                                    <th>วันที่รับบริการ</th>
                                    <th>วันที่รูดบัตร</th>
                                    <th>ชื่อไฟล์รายงาน</th>
                                    <th class="text-center">จำนวนรายการ</th>
                                    <th>สถานะในฐานข้อมูล (edc_approve_list)</th>
                                    <th class="text-end" style="width: 170px;">ดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyEdcFiles">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Progress Area -->
                <div id="edc-import-progress-area" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div id="edc-progress-text" class="small fw-bold text-muted">กำลังเตรียมนำเข้า...</div>
                        <div id="edc-progress-percent" class="small fw-bold text-primary">0%</div>
                    </div>
                    <div class="progress mb-2 rounded-pill shadow-sm" style="height: 14px;">
                        <div id="edc-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success rounded-pill" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div id="edc-details-log" class="text-start bg-dark text-light p-2.5 rounded-3 border" style="max-height: 110px; overflow-y: auto; font-family: monospace; font-size: 11px; line-height: 1.5;"></div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-0 py-2.5 px-4 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-pill btn-sm" data-bs-dismiss="modal" id="cancelImportBtn">
                        <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success px-4 rounded-pill shadow-sm btn-sm fw-bold" id="btnImportSelectedEdc" disabled>
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> 🚀 นำเข้าไฟล์ที่เลือกทั้งหมด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Standalone Detail Modal for File Records (High z-index to float above parent modal) -->
<div class="modal fade" id="edcRecordDetailModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light py-2 px-3">
                <h6 class="modal-title fw-bold text-dark mb-0" id="edcDetailModalTitle">
                    <i class="bi bi-list-check me-1 text-primary"></i> รายชื่อผู้ป่วยในไฟล์
                </h6>
                <button type="button" class="btn-close" onclick="hideEdcRecordDetailModal()" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>CID</th>
                                <th>ชื่อ-สกุล</th>
                                <th>วันรับบริการ</th>
                                <th>วันรูดบัตร</th>
                                <th class="text-end">ยอดเงิน</th>
                                <th>เลขอนุมัติ (Approve)</th>
                                <th class="text-center">สถานะ DB</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyEdcRecordDetails"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" onclick="hideEdcRecordDetailModal()" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentEdcUniqueId = '';
let currentEdcFiles = [];

// Format Date YYYY-MM-DD
function formatIsoDate(d) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Initialize Thai Datepicker for EDC Modal
function initEdcDatepickers() {
    if (typeof $.fn.datepicker !== 'undefined') {
        $('#edc_from_date_picker, #edc_to_date_picker').datepicker({
            format: 'd M yyyy',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            zIndexOffset: 1060
        });

        $('#edc_from_date_picker').on('changeDate', function(e) {
            if (e.date) {
                $('#edc_from_date').val(formatIsoDate(e.date));
            }
        });

        $('#edc_to_date_picker').on('changeDate', function(e) {
            if (e.date) {
                $('#edc_to_date').val(formatIsoDate(e.date));
            }
        });
    }
}

// Quick Date Range Setter
function setEdcDateRange(days) {
    const today = new Date();
    let fromDate = new Date();
    let toDate = new Date();
    
    if (days === 1) {
        // Today
        fromDate = today;
        toDate = today;
    } else if (days === 2) {
        // Yesterday
        fromDate.setDate(today.getDate() - 1);
        toDate.setDate(today.getDate() - 1);
    } else {
        // e.g. 7 days
        fromDate.setDate(today.getDate() - (days - 1));
        toDate = today;
    }

    const fromIso = formatIsoDate(fromDate);
    const toIso = formatIsoDate(toDate);

    document.getElementById('edc_from_date').value = fromIso;
    document.getElementById('edc_to_date').value = toIso;

    if (typeof $.fn.datepicker !== 'undefined') {
        $('#edc_from_date_picker').datepicker('setDate', fromDate);
        $('#edc_to_date_picker').datepicker('setDate', toDate);
    } else {
        document.getElementById('edc_from_date_picker').value = fromIso;
        document.getElementById('edc_to_date_picker').value = toIso;
    }
}

// Check KTB Main Setting & Playwright Status
async function checkKtbSetupStatus() {
    const statusDiv = document.getElementById('ktbCredentialStatus');
    if (!statusDiv) return;

    try {
        const res = await fetch("{{ route('api.check_ktb_status') }}");
        const data = await res.json();

        if (data.success) {
            if (data.has_credentials) {
                statusDiv.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> บัญชี KTB: <b>${data.user_id}</b> (${data.company_id})</span>`;
            } else {
                statusDiv.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> ยังไม่ได้ตั้งค่าบัญชี KTB ใน <a href="{{ route('admin.main_setting') }}" target="_blank" class="text-primary text-decoration-underline">Main Setting</a></span>`;
            }
        }
    } catch (e) {
        statusDiv.innerHTML = `<span class="text-muted">ไม่สามารถตรวจสอบสถานะ KTB ได้</span>`;
    }
}

// Render Preview Files Table
function renderEdcFilesTable(files, uniqueId) {
    currentEdcUniqueId = uniqueId;
    currentEdcFiles = files;

    const tbody = document.getElementById('tbodyEdcFiles');
    const container = document.getElementById('edcPreviewContainer');
    const importBtn = document.getElementById('btnImportSelectedEdc');
    const badge = document.getElementById('fileCountBadge');
    const summaryBadge = document.getElementById('previewSummaryBadge');

    if (!tbody || !container) return;

    tbody.innerHTML = '';
    container.style.display = 'block';

    if (!files || files.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">ไม่พบไฟล์รายงาน EDC</td></tr>`;
        if (importBtn) importBtn.disabled = true;
        if (badge) badge.innerText = '0 ไฟล์';
        return;
    }

    let totalRecords = 0;
    let totalNew = 0;

    files.forEach((file, index) => {
        totalRecords += file.total_records || 0;
        totalNew += file.new_records || 0;

        // Determine if checked by default (uncheck if full existing)
        const isFullExisting = (file.status === 'full_existing');
        const checkedAttr = isFullExisting ? '' : 'checked';
        const opacityStyle = isFullExisting ? 'opacity: 0.85;' : '';

        const tr = document.createElement('tr');
        tr.id = `edc_file_row_${index}`;
        tr.style = opacityStyle;
        tr.innerHTML = `
            <td class="text-center">
                <input type="checkbox" class="form-check-input edc-file-checkbox" data-index="${index}" data-filename="${file.file_name}" ${checkedAttr}>
            </td>
            <td><span class="badge bg-light text-dark border">${file.vstdate || '-'}</span></td>
            <td><span class="badge bg-light text-secondary border">${file.post_date || '-'}</span></td>
            <td>
                <span class="fw-semibold text-dark">${file.file_name}</span>
            </td>
            <td class="text-center">
                <span class="fw-bold">${file.total_records}</span>
                ${file.new_records > 0 ? `<small class="text-success ms-1">(ใหม่ ${file.new_records})</small>` : ''}
            </td>
            <td>
                <span class="badge bg-${file.status_color} text-${file.status_color === 'warning' ? 'dark' : 'white'} px-2 py-1 rounded-pill">
                    ${file.status_text}
                </span>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-outline-info btn-sm rounded-pill py-0 px-2 me-1" style="font-size: 0.75rem;" onclick="viewEdcFileDetails(${index})">
                    <i class="bi bi-eye"></i> ดู
                </button>
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill py-0 px-2" style="font-size: 0.75rem;" onclick="importSingleEdcFile(${index})">
                    <i class="bi bi-cloud-arrow-up"></i> นำเข้า
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if (badge) badge.innerText = `${files.length} ไฟล์`;
    if (summaryBadge) {
        summaryBadge.innerHTML = `พบทั้งหมด <span class="text-primary">${files.length}</span> ไฟล์ | รวม <span class="text-dark">${totalRecords}</span> รายการ (<span class="text-success font-monospace">+${totalNew} ใหม่</span>)`;
    }
    if (importBtn) importBtn.disabled = false;
}

// Select All / Deselect All Checkboxes
function selectAllEdcFiles(selectAll) {
    const checkboxes = document.querySelectorAll('.edc-file-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll);
    const masterCb = document.getElementById('checkAllFiles');
    if (masterCb) masterCb.checked = selectAll;
}

function toggleCheckAllFiles(checked) {
    selectAllEdcFiles(checked);
}

// View Record Details in a file
function viewEdcFileDetails(index) {
    const file = currentEdcFiles[index];
    if (!file) return;

    document.getElementById('edcDetailModalTitle').innerHTML = `
        <i class="bi bi-file-earmark-text-fill me-1 text-primary"></i> ${file.file_name} 
        <span class="badge bg-${file.status_color} text-${file.status_color === 'warning' ? 'dark' : 'white'} ms-2 rounded-pill">${file.status_text}</span>
    `;

    const tbody = document.getElementById('tbodyEdcRecordDetails');
    tbody.innerHTML = '';

    if (!file.records || file.records.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">ไม่มีรายการผู้ป่วย</td></tr>`;
    } else {
        file.records.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="font-monospace">${r.cid}</td>
                <td>${r.ptname}</td>
                <td>${r.vstdate || '-'} <small class="text-muted">${r.vsttime || ''}</small></td>
                <td>${r.post_date || '-'} <small class="text-muted">${r.post_time || ''}</small></td>
                <td class="text-end fw-bold font-monospace">${(r.amount || 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })}</td>
                <td class="font-monospace text-primary fw-bold">${r.approve_code}</td>
                <td class="text-center">
                    ${r.is_existing 
                        ? `<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1"><i class="bi bi-check-circle me-1"></i> มีใน DB แล้ว</span>` 
                        : `<span class="badge bg-info text-dark rounded-pill px-2 py-1"><i class="bi bi-plus-circle me-1"></i> รายการใหม่</span>`}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    showEdcRecordDetailModal();
}

function showEdcRecordDetailModal() {
    const el = document.getElementById('edcRecordDetailModal');
    if (!el) return;
    if (window.jQuery && typeof $(el).modal === 'function') {
        $(el).modal('show');
        return;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        if (typeof bootstrap.Modal.getOrCreateInstance === 'function') {
            bootstrap.Modal.getOrCreateInstance(el).show();
            return;
        }
        new bootstrap.Modal(el).show();
        return;
    }
    el.style.display = 'block';
    el.classList.add('show');
}

function hideEdcRecordDetailModal() {
    const el = document.getElementById('edcRecordDetailModal');
    if (!el) return;
    if (window.jQuery && typeof $(el).modal === 'function') {
        $(el).modal('hide');
        return;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        if (typeof bootstrap.Modal.getInstance === 'function') {
            const inst = bootstrap.Modal.getInstance(el);
            if (inst) { inst.hide(); return; }
        }
    }
    el.style.display = 'none';
    el.classList.remove('show');
    const bd = document.querySelector('.modal-backdrop');
    if (bd) bd.remove();
}

// Import Single File
async function importSingleEdcFile(index) {
    const file = currentEdcFiles[index];
    if (!file || !currentEdcUniqueId) return;

    const mode = document.querySelector('input[name="edc_import_mode"]:checked')?.value || 'skip_existing';
    const progressArea = document.getElementById('edc-import-progress-area');
    const logDiv = document.getElementById('edc-details-log');
    const progressText = document.getElementById('edc-progress-text');
    const progressBar = document.getElementById('edc-progress-bar');
    const progressPercent = document.getElementById('edc-progress-percent');

    if (progressArea) progressArea.style.display = 'block';
    if (logDiv) logDiv.innerHTML = `<div>🚀 เริ่มนำเข้าไฟล์ ${file.file_name} (โหมด: ${mode === 'overwrite' ? 'เขียนทับ' : 'ข้ามที่ซ้ำ'})...</div>`;
    if (progressText) progressText.innerText = `กำลังนำเข้า ${file.file_name}...`;
    if (progressBar) {
        progressBar.style.width = '50%';
        progressBar.setAttribute('aria-valuenow', 50);
    }
    if (progressPercent) progressPercent.innerText = '50%';

    try {
        const formData = new FormData();
        formData.append('unique_id', currentEdcUniqueId);
        formData.append('file_name', file.file_name);
        formData.append('import_mode', mode);

        const res = await fetch("{{ route('api.import_edc_file') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        if (progressBar) {
            progressBar.style.width = '100%';
            progressBar.setAttribute('aria-valuenow', 100);
        }
        if (progressPercent) progressPercent.innerText = '100%';

        if (res.ok && data.success) {
            if (logDiv) logDiv.innerHTML += `<div class="text-success">✔ ${data.message}</div>`;
            if (progressText) progressText.innerText = 'นำเข้าสำเร็จ!';
            
            // Update local file data
            if (data.file_info) {
                currentEdcFiles[index] = data.file_info;
                renderEdcFilesTable(currentEdcFiles, currentEdcUniqueId);
            }

            Swal.fire({
                icon: 'success',
                title: 'นำเข้าไฟล์สำเร็จ!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });

            // Trigger dashboard refresh if available
            if (typeof window['{{ $onSuccessCallback }}'] === 'function') {
                window['{{ $onSuccessCallback }}']({ skip_chart: 1 });
            }
        } else {
            if (logDiv) logDiv.innerHTML += `<div class="text-danger">❌ ล้มเหลว: ${data.message || 'เกิดข้อผิดพลาด'}</div>`;
            if (progressText) progressText.innerText = 'เกิดข้อผิดพลาด';
            Swal.fire({ icon: 'error', title: 'นำเข้าล้มเหลว', text: data.message });
        }
    } catch (e) {
        if (logDiv) logDiv.innerHTML += `<div class="text-danger">❌ ข้อผิดพลาด: ${e.message}</div>`;
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: e.message });
    }
}

// Import All Selected Files
async function importAllSelectedEdcFiles() {
    const selectedCbs = Array.from(document.querySelectorAll('.edc-file-checkbox:checked'));
    if (selectedCbs.length === 0) {
        Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์ที่ต้องการนำเข้าอย่างน้อย 1 ไฟล์', confirmButtonText: 'ตกลง' });
        return;
    }

    const mode = document.querySelector('input[name="edc_import_mode"]:checked')?.value || 'skip_existing';
    const totalFiles = selectedCbs.length;

    const progressArea = document.getElementById('edc-import-progress-area');
    const logDiv = document.getElementById('edc-details-log');
    const progressText = document.getElementById('edc-progress-text');
    const progressBar = document.getElementById('edc-progress-bar');
    const progressPercent = document.getElementById('edc-progress-percent');
    const importBtn = document.getElementById('btnImportSelectedEdc');
    const cancelBtn = document.getElementById('cancelImportBtn');

    if (progressArea) progressArea.style.display = 'block';
    if (logDiv) logDiv.innerHTML = `<div>🚀 เริ่มต้นการนำเข้า ${totalFiles} ไฟล์ (โหมด: ${mode === 'overwrite' ? 'เขียนทับ' : 'เพิ่มเฉพาะใหม่'})...</div>`;
    if (importBtn) importBtn.disabled = true;
    if (cancelBtn) cancelBtn.disabled = true;

    let successCount = 0;
    let grandImported = 0;
    let grandUpdated = 0;
    let grandSkipped = 0;

    for (let i = 0; i < totalFiles; i++) {
        const cb = selectedCbs[i];
        const fileIndex = parseInt(cb.getAttribute('data-index'));
        const fileName = cb.getAttribute('data-filename');

        const currentPct = Math.round(((i) / totalFiles) * 100);
        if (progressBar) {
            progressBar.style.width = `${currentPct}%`;
            progressBar.setAttribute('aria-valuenow', currentPct);
        }
        if (progressPercent) progressPercent.innerText = `${currentPct}%`;
        if (progressText) progressText.innerText = `[${i + 1}/${totalFiles}] กำลังนำเข้า: ${fileName}`;
        if (logDiv) {
            logDiv.innerHTML += `<div>📦 [${i + 1}/${totalFiles}] ประมวลผล ${fileName}...</div>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        try {
            const formData = new FormData();
            formData.append('unique_id', currentEdcUniqueId);
            formData.append('file_name', fileName);
            formData.append('import_mode', mode);

            const res = await fetch("{{ route('api.import_edc_file') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                }
            });

            const data = await res.json();
            if (res.ok && data.success) {
                successCount++;
                grandImported += (data.imported_count || 0);
                grandUpdated += (data.updated_count || 0);
                grandSkipped += (data.skipped_count || 0);

                if (logDiv) logDiv.innerHTML += `<div class="text-success" style="margin-left: 10px;">✔ ${data.message}</div>`;
                if (data.file_info) {
                    currentEdcFiles[fileIndex] = data.file_info;
                }
            } else {
                if (logDiv) logDiv.innerHTML += `<div class="text-danger" style="margin-left: 10px;">❌ ล้มเหลว: ${data.message || 'เกิดข้อผิดพลาด'}</div>`;
            }
        } catch (err) {
            if (logDiv) logDiv.innerHTML += `<div class="text-danger" style="margin-left: 10px;">❌ เกิดข้อผิดพลาด: ${err.message}</div>`;
        }

        if (logDiv) logDiv.scrollTop = logDiv.scrollHeight;
    }

    // Finalize
    if (progressBar) {
        progressBar.style.width = `100%`;
        progressBar.setAttribute('aria-valuenow', 100);
    }
    if (progressPercent) progressPercent.innerText = `100%`;
    if (progressText) progressText.innerText = 'นำเข้าข้อมูลเสร็จสิ้น!';
    if (importBtn) importBtn.disabled = false;
    if (cancelBtn) cancelBtn.disabled = false;

    // Refresh UI table
    renderEdcFilesTable(currentEdcFiles, currentEdcUniqueId);

    Swal.fire({
        icon: 'success',
        title: 'นำเข้าข้อมูลเสร็จสิ้น!',
        html: `
            <div class="text-start small lh-lg">
                <div>✔ นำเข้าสำเร็จ: <b>${successCount} / ${totalFiles}</b> ไฟล์</div>
                <div>➕ เพิ่มรายการใหม่: <b class="text-success">${grandImported}</b> รายการ</div>
                <div>🔄 ปรับปรุงข้อมูลเดิม: <b class="text-primary">${grandUpdated}</b> รายการ</div>
                <div>⏭ ข้ามรายการที่ซ้ำ: <b class="text-muted">${grandSkipped}</b> รายการ</div>
            </div>
        `,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#198754'
    }).then(() => {
        if (typeof window['{{ $onSuccessCallback }}'] === 'function') {
            window['{{ $onSuccessCallback }}']({ skip_chart: 1 });
        }
    });
}

// Document Ready Initialization
document.addEventListener('DOMContentLoaded', function () {
    initEdcDatepickers();
    // Set default date range to last 7 days
    setEdcDateRange(7);

    // Check KTB Setup Status when modal is shown
    const modalEl = document.getElementById('{{ $modalId }}');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            initEdcDatepickers();
            checkKtbSetupStatus();
        });
    }

    // Sync KTB Button Handler
    const btnSyncKtb = document.getElementById('btnSyncKtb');
    if (btnSyncKtb) {
        btnSyncKtb.addEventListener('click', async function () {
            const fromDate = document.getElementById('edc_from_date').value;
            const toDate = document.getElementById('edc_to_date').value;

            if (!fromDate || !toDate) {
                Swal.fire({ icon: 'warning', title: 'กรุณาระบุช่วงวันที่ให้ครบถ้วน', confirmButtonText: 'ตกลง' });
                return;
            }

            // Check 7 days limit
            const d1 = new Date(fromDate);
            const d2 = new Date(toDate);
            const diffDays = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
            if (diffDays > 7) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ช่วงวันเกิน 7 วัน',
                    text: `คุณเลือกช่วงวันที่ ${diffDays} วัน ระบบธนาคารกรุงไทยกำหนดให้ค้นหาได้ครั้งละไม่เกิน 7 วัน กรุณาปรับช่วงวันที่ให้อยู่ใน 7 วัน`,
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            const originalHtml = btnSyncKtb.innerHTML;
            btnSyncKtb.disabled = true;
            btnSyncKtb.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> กำลังดึงจาก KTB...`;

            const progressArea = document.getElementById('edc-import-progress-area');
            const logDiv = document.getElementById('edc-details-log');
            const progressText = document.getElementById('edc-progress-text');
            const progressBar = document.getElementById('edc-progress-bar');
            const progressPercent = document.getElementById('edc-progress-percent');

            if (progressArea) progressArea.style.display = 'block';
            if (logDiv) logDiv.innerHTML = `<div>🔄 กำลังเชื่อมต่อไปยัง KTB Corporate Online (${fromDate} ถึง ${toDate})...</div>`;
            if (progressText) progressText.innerText = 'กำลังล็อกอินและค้นหารายงาน EDC...';
            if (progressBar) {
                progressBar.style.width = '30%';
                progressBar.setAttribute('aria-valuenow', 30);
            }
            if (progressPercent) progressPercent.innerText = '30%';

            try {
                const formData = new FormData();
                formData.append('from_date', fromDate);
                formData.append('to_date', toDate);

                const res = await fetch("{{ route('api.sync_edc_ktb') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();

                if (progressBar) {
                    progressBar.style.width = '100%';
                    progressBar.setAttribute('aria-valuenow', 100);
                }
                if (progressPercent) progressPercent.innerText = '100%';

                if (res.ok && data.success) {
                    if (logDiv) logDiv.innerHTML += `<div class="text-success">✔ ${data.message}</div>`;
                    if (progressText) progressText.innerText = 'ดึงข้อมูลสำเร็จ พร้อมตรวจสอบ!';
                    renderEdcFilesTable(data.files, data.unique_id);
                } else {
                    if (logDiv) logDiv.innerHTML += `<div class="text-danger">❌ ล้มเหลว: ${data.message || 'เกิดข้อผิดพลาด'}</div>`;
                    if (progressText) progressText.innerText = 'การซิงค์ล้มเหลว';
                    Swal.fire({ icon: 'error', title: 'ซิงค์ข้อมูลล้มเหลว', text: data.message });
                }
            } catch (err) {
                if (logDiv) logDiv.innerHTML += `<div class="text-danger">❌ ข้อผิดพลาด: ${err.message}</div>`;
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err.message });
            } finally {
                btnSyncKtb.disabled = false;
                btnSyncKtb.innerHTML = originalHtml;
            }
        });
    }

    // Manual Upload Button Handler
    const btnUploadManual = document.getElementById('btnUploadManualEdc');
    if (btnUploadManual) {
        btnUploadManual.addEventListener('click', async function () {
            const fileInput = document.getElementById('manual_edc_file');
            if (!fileInput || fileInput.files.length === 0) {
                Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์ ZIP หรือ TXT', confirmButtonText: 'ตกลง' });
                return;
            }

            const originalHtml = btnUploadManual.innerHTML;
            btnUploadManual.disabled = true;
            btnUploadManual.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> กำลังตรวจไฟล์...`;

            const progressArea = document.getElementById('edc-import-progress-area');
            const logDiv = document.getElementById('edc-details-log');
            const progressText = document.getElementById('edc-progress-text');
            const progressBar = document.getElementById('edc-progress-bar');
            const progressPercent = document.getElementById('edc-progress-percent');

            if (progressArea) progressArea.style.display = 'block';
            if (logDiv) logDiv.innerHTML = `<div>📦 กำลังอัปโหลดและวิเคราะห์ไฟล์...</div>`;
            if (progressText) progressText.innerText = 'กำลังตรวจสอบไฟล์...';
            if (progressBar) {
                progressBar.style.width = '40%';
                progressBar.setAttribute('aria-valuenow', 40);
            }
            if (progressPercent) progressPercent.innerText = '40%';

            try {
                const formData = new FormData(document.getElementById('edcManualUploadForm'));
                formData.append('_token', "{{ csrf_token() }}");

                const res = await fetch("{{ route('api.import_edc_zip') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (progressBar) {
                    progressBar.style.width = '100%';
                    progressBar.setAttribute('aria-valuenow', 100);
                }
                if (progressPercent) progressPercent.innerText = '100%';

                if (res.ok && data.success) {
                    if (logDiv) logDiv.innerHTML += `<div class="text-success">✔ ${data.message}</div>`;
                    if (progressText) progressText.innerText = 'ตรวจสอบไฟล์สำเร็จ!';
                    renderEdcFilesTable(data.files, data.unique_id);
                } else {
                    if (logDiv) logDiv.innerHTML += `<div class="text-danger">❌ ล้มเหลว: ${data.message || 'เกิดข้อผิดพลาด'}</div>`;
                    Swal.fire({ icon: 'error', title: 'ตรวจสอบไฟล์ล้มเหลว', text: data.message });
                }
            } catch (err) {
                if (logDiv) logDiv.innerHTML += `<div class="text-danger">❌ ข้อผิดพลาด: ${err.message}</div>`;
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err.message });
            } finally {
                btnUploadManual.disabled = false;
                btnUploadManual.innerHTML = originalHtml;
            }
        });
    }

    // Import Selected Button Handler
    const btnImportSelected = document.getElementById('btnImportSelectedEdc');
    if (btnImportSelected) {
        btnImportSelected.addEventListener('click', importAllSelectedEdcFiles);
    }
});
</script>
