<!-- Modal: F16 KTB Health Platform Export Center -->
<style>
    .f16-ktb-sortable-th {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.15s ease;
        white-space: nowrap;
        position: relative;
    }
    .f16-ktb-sortable-th:hover {
        background-color: #e2e8f0 !important;
    }
    .f16-ktb-table-container {
        max-height: 320px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
    }
    .f16-ktb-table-container thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8fafc !important;
        color: #0f172a !important;
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        border-bottom: 2px solid #94a3b8 !important;
        padding: 7px 10px !important;
    }
    .f16-ktb-table-container td {
        padding: 6px 10px !important;
        font-size: 0.8rem !important;
        white-space: nowrap;
    }
    .nav-pills-ktb .nav-link {
        font-size: 0.74rem;
        padding: 4px 4px;
        border-radius: 6px;
        color: #334155;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
        min-width: 52px;
        text-align: center;
    }
    .nav-pills-ktb .nav-link:hover {
        background-color: #f1f5f9;
    }
    .nav-pills-ktb .nav-link.active {
        background-color: #0284c7 !important;
        color: #fff !important;
        font-weight: 700;
        border-color: #0284c7 !important;
        box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3);
    }
    .f16-badge-count {
        font-size: 0.65rem !important;
        padding: 2px 6px !important;
        font-weight: 700;
        display: inline-block;
    }
    .badge-count-success {
        background-color: #10b981 !important;
        color: #ffffff !important;
    }
    .badge-count-zero {
        background-color: #f8fafc !important;
        color: #64748b !important;
        border: 1px solid #cbd5e1 !important;
    }
</style>

<div class="modal fade" id="f16KtbExportModal" tabindex="-1" aria-labelledby="f16KtbExportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <!-- Modal Header with KTB Gradient Theme -->
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #034488 0%, #0077b6 50%, #00a2e8 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-cloud-arrow-up-fill fs-4" style="color: #0077b6;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="f16KtbExportModalLabel">
                            ส่งออกข้อมูลมาตรฐาน 16 แฟ้ม (Krungthai Digital Health Platform)
                        </h5>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-white text-primary fw-bold" id="f16KtbModalActivityTitle">[S01] คัดกรองสุขภาพกาย/จิต (SCR)</span>
                            <span class="badge text-white fw-bold" style="background-color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4);" id="f16KtbModalSelectedBadge">0 รายการที่เลือก</span>
                            <span class="text-white-50 small">HCODE: {{ \App\Services\LicenseVerificationService::getHcode() }}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <!-- Loading State -->
                <div id="f16KtbLoadingOverlay" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3.2rem; height: 3.2rem;" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">กำลังประมวลผลและเตรียมข้อมูล 16 แฟ้มจาก HOSxP ตามสเปก KTB...</h6>
                    <p class="text-muted small">ระบบกำลังจัดเตรียมโครงสร้าง 6 แฟ้มหลักของ KTB (INS, PAT, OPD, ODX, ADP, DRU) กรุณารอสักครู่</p>
                </div>

                <!-- Main Content Area -->
                <div id="f16KtbMainContent" style="display: none;">
                    <!-- 6 Tabs Bar -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 bg-white rounded">
                            <ul class="nav nav-pills nav-pills-ktb nav-fill flex-wrap gap-1" id="f16KtbTabs" role="tablist">
                                @php
                                    $fileTabs = [
                                        ['key' => 'INS', 'name' => 'INS', 'desc' => 'ผู้มีสิทธิการรักษาพยาบาล (INS) *'],
                                        ['key' => 'PAT', 'name' => 'PAT', 'desc' => 'ข้อมูลผู้ป่วยกลาง (PAT) *'],
                                        ['key' => 'OPD', 'name' => 'OPD', 'desc' => 'การมารับบริการผู้ป่วยนอก (OPD)'],
                                        ['key' => 'ODX', 'name' => 'ODX', 'desc' => 'วินิจฉัยโรคผู้ป่วยนอก (ODX)'],
                                        ['key' => 'ADP', 'name' => 'ADP', 'desc' => 'ค่าใช้จ่ายเพิ่ม และบริการที่ยังไม่ได้จัดหมวด (ADP) *'],
                                        ['key' => 'DRU', 'name' => 'DRU', 'desc' => 'การใช้ยา (DRU)']
                                    ];
                                @endphp
                                @foreach($fileTabs as $tab)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link text-center {{ $tab['key'] === 'ADP' ? 'active' : '' }}" 
                                                id="f16-ktb-tab-{{ $tab['key'] }}" 
                                                data-bs-toggle="pill" 
                                                data-bs-target="#f16-ktb-pane-{{ $tab['key'] }}" 
                                                type="button" 
                                                role="tab"
                                                onclick="switchKtbFileTab('{{ $tab['key'] }}')">
                                            <div class="fw-bold">{{ $tab['name'] }}</div>
                                            <span class="badge rounded-pill f16-badge-count badge-count-zero mt-1" id="f16-ktb-badge-{{ $tab['key'] }}">0</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Tab Contents Container -->
                    <div class="tab-content" id="f16KtbTabContent">
                        @foreach($fileTabs as $tab)
                            <div class="tab-pane fade {{ $tab['key'] === 'ADP' ? 'show active' : '' }}" 
                                 id="f16-ktb-pane-{{ $tab['key'] }}" 
                                 role="tabpanel">
                                <!-- Card Container for each file -->
                                <div class="card border shadow-sm" style="border-color: #cbd5e1; border-radius: 8px; overflow: hidden;">
                                    <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-table text-primary"></i>
                                            <span class="badge bg-primary fs-6 fw-bold">{{ $tab['name'] }}.txt</span>
                                            <span class="text-muted small">({{ $tab['desc'] }})</span>
                                            <span class="text-muted small ms-2"><i class="bi bi-info-circle me-1"></i>คลิกที่หัวคอลัมน์เพื่อเรียงลำดับ (Sort)</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-light text-secondary border px-2 py-1" id="pane-ktb-count-{{ $tab['key'] }}">0 แถว</span>
                                            <div class="input-group input-group-sm" style="width: 200px;">
                                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                                <input type="text" class="form-control" id="f16KtbSearchInput-{{ $tab['key'] }}" placeholder="ค้นหาในตาราง..." onkeyup="filterKtbTable('{{ $tab['key'] }}')">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-0 bg-white">
                                        <div class="f16-ktb-table-container">
                                            <table class="table table-sm table-hover table-striped mb-0 text-nowrap small w-100" id="f16-ktb-table-{{ $tab['key'] }}">
                                                <thead>
                                                    <tr id="f16-ktb-thead-{{ $tab['key'] }}">
                                                        <th>กำลังโหลด...</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="f16-ktb-tbody-{{ $tab['key'] }}">
                                                    <tr>
                                                        <td class="text-center text-muted py-4">ไม่มีข้อมูล</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Collapsible Raw Text Section -->
                                    <div class="card-footer bg-light border-top p-0">
                                        <button class="btn btn-sm btn-light w-100 text-start d-flex justify-content-between align-items-center py-2 px-3 text-secondary border-0" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#raw-ktb-{{ $tab['key'] }}-collapse" 
                                                aria-expanded="false" 
                                                aria-controls="raw-ktb-{{ $tab['key'] }}-collapse">
                                            <span class="fw-bold">
                                                <i class="bi bi-file-earmark-code text-primary me-1"></i> ดูไฟล์ข้อความดิบ {{ $tab['name'] }}.txt (Raw Text)
                                            </span>
                                            <i class="bi bi-chevron-down text-muted"></i>
                                        </button>
                                        <div class="collapse" id="raw-ktb-{{ $tab['key'] }}-collapse">
                                            <div class="p-3 position-relative bg-white border-top">
                                                <button class="btn btn-xs btn-outline-secondary position-absolute end-0 top-0 m-3 shadow-sm" 
                                                        type="button"
                                                        onclick="copyF16KtbRawText('{{ $tab['key'] }}')" 
                                                        style="font-size: 0.75rem; z-index: 10;">
                                                    <i class="bi bi-clipboard me-1"></i> คัดลอก Raw Text
                                                </button>
                                                <textarea class="form-control text-monospace bg-light text-dark p-3 small" 
                                                          id="preview-raw-ktb-{{ $tab['key'] }}" 
                                                          rows="6" 
                                                          readonly 
                                                          style="font-family: 'Consolas', 'Courier New', monospace; font-size: 0.8rem; line-height: 1.4; white-space: pre;">(ไม่มีข้อมูล)</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Export Folder Options (Switch style like e-Claim / FDH) -->
                    <div class="card border-0 shadow-sm mt-3 bg-white">
                        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                            <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="f16KtbCreateSubfolderSwitch" checked>
                                <label class="form-check-label fw-bold text-dark small cursor-pointer" for="f16KtbCreateSubfolderSwitch">
                                    <i class="bi bi-folder-plus text-primary me-1"></i>สร้างโฟลเดอร์ย่อยตามสิทธิและวันเวลาอัตโนมัติ 
                                    <span class="text-muted fw-normal" id="f16KtbSubfolderPreviewText">(เช่น F16_KTB_S01_25690902_1118)</span>
                                </label>
                            </div>
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>เขียนไฟล์ .txt ทั้ง 6 แฟ้ม KTB ลงโฟลเดอร์โดยตรง
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Exact layout like FDH / e-Claim) -->
            <div class="modal-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <span id="f16KtbExportProgressText" class="fw-bold text-primary small"></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-3 fw-bold" id="btnExecuteF16KtbExport" onclick="executeF16KtbDirectoryExport()">
                        <i class="bi bi-folder-check me-1"></i> <span id="btnExecuteF16KtbExportText">ส่งออกโฟลเดอร์ (.txt)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Global State for F16 KTB Export
    window._f16KtbExportState = {
        keys: [],
        activityCode: 'S01',
        activityTitle: '',
        headers: {},
        tables: {},
        rawFiles: {},
        counts: {},
        subfolderName: ''
    };

    window._f16KtbSortState = {};

    function escapeHtmlKtb(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Render thead for table
     */
    window.renderF16KtbTableHead = function(key) {
        const headers = window._f16KtbExportState.headers[key] || [];
        const thead = document.querySelector(`#f16-ktb-thead-${key}`);
        if (!thead || headers.length === 0) return;

        let html = '';
        for (let colIdx = 0; colIdx < headers.length; colIdx++) {
            const h = headers[colIdx];
            html += `<th class="f16-ktb-sortable-th" onclick="sortF16KtbTable('${key}', ${colIdx})" title="คลิกเพื่อเรียงตาม ${escapeHtmlKtb(h)}">
                <div class="d-flex align-items-center justify-content-between gap-1">
                    <span>${escapeHtmlKtb(h)}</span>
                    <span class="sort-icon text-muted small"><i class="bi bi-arrow-down-up"></i></span>
                </div>
            </th>`;
        }
        thead.innerHTML = html;
    };

    /**
     * Render tbody for table
     */
    window.renderF16KtbTableBody = function(key) {
        const rows = window._f16KtbExportState.tables[key] || [];
        const tbody = document.getElementById(`f16-ktb-tbody-${key}`);
        if (!tbody) return;

        if (rows.length === 0) {
            const colCount = $(`#f16-ktb-table-${key} thead th`).length || 1;
            tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i> ไม่มีรายการในแฟ้มนี้</td></tr>`;
            return;
        }

        let html = '';
        const maxRows = Math.min(rows.length, 300);
        for (let r = 0; r < maxRows; r++) {
            const row = rows[r];
            html += '<tr>';
            for (let c = 0; c < row.length; c++) {
                const cell = row[c] !== null && row[c] !== undefined ? row[c] : '';
                html += `<td>${escapeHtmlKtb(cell)}</td>`;
            }
            html += '</tr>';
        }
        tbody.innerHTML = html;
    };

    /**
     * Sort table column
     */
    window.sortF16KtbTable = function(key, colIdx) {
        const tableData = window._f16KtbExportState.tables[key];
        if (!tableData || tableData.length === 0) return;

        const currentSort = window._f16KtbSortState[key] || { col: -1, dir: 'asc' };
        let newDir = 'asc';
        if (currentSort.col === colIdx) {
            newDir = currentSort.dir === 'asc' ? 'desc' : 'asc';
        }
        window._f16KtbSortState[key] = { col: colIdx, dir: newDir };

        tableData.sort(function(a, b) {
            let valA = (a[colIdx] !== undefined && a[colIdx] !== null) ? a[colIdx].toString().trim() : '';
            let valB = (b[colIdx] !== undefined && b[colIdx] !== null) ? b[colIdx].toString().trim() : '';

            const numA = parseFloat(valA);
            const numB = parseFloat(valB);
            if (!isNaN(numA) && !isNaN(numB) && valA === numA.toString() && valB === numB.toString()) {
                return newDir === 'asc' ? numA - numB : numB - numA;
            }

            const cmp = valA.localeCompare(valB, 'th', { numeric: true, sensitivity: 'base' });
            return newDir === 'asc' ? cmp : -cmp;
        });

        $(`#f16-ktb-table-${key} th.f16-ktb-sortable-th`).each(function(idx) {
            const iconEl = $(this).find('.sort-icon i');
            if (idx === colIdx) {
                iconEl.removeClass('bi-arrow-down-up bi-sort-down bi-sort-up text-muted')
                      .addClass(newDir === 'asc' ? 'bi-sort-up text-primary fw-bold' : 'bi-sort-down text-primary fw-bold');
                $(this).addClass('bg-primary-subtle');
            } else {
                iconEl.removeClass('bi-sort-down bi-sort-up text-primary fw-bold')
                      .addClass('bi-arrow-down-up text-muted');
                $(this).removeClass('bg-primary-subtle');
            }
        });

        renderF16KtbTableBody(key);
    };

    /**
     * Copy Raw Text
     */
    window.copyF16KtbRawText = function(key) {
        const textarea = document.getElementById('preview-raw-ktb-' + key);
        if (!textarea || !textarea.value) return;

        navigator.clipboard.writeText(textarea.value).then(() => {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1800,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'success',
                    title: 'คัดลอก ' + key + '.txt สำเร็จ'
                });
            } else {
                alert('คัดลอก ' + key + '.txt สำเร็จ');
            }
        }).catch(() => {
            textarea.select();
            document.execCommand('copy');
            alert('คัดลอก ' + key + '.txt สำเร็จ');
        });
    };

    /**
     * Switch File Tab
     */
    window.switchKtbFileTab = function(key) {
        // Tab bootstrap handles pane visibility
    };

    /**
     * Filter table search
     */
    window.filterKtbTable = function(key) {
        const query = ($('#f16KtbSearchInput-' + key).val() || '').toLowerCase();
        const rows = $(`#f16-ktb-tbody-${key} tr`);
        rows.each(function () {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(query) > -1);
        });
    };

    /**
     * Open F16 KTB Modal
     */
    window.openF16KtbModal = function(keys, activityCode, activityTitle) {
        activityCode = activityCode || 'S01';
        activityTitle = activityTitle || '';

        if (!keys || keys.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาเลือกรายการ',
                    text: 'กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม KTB',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#0077b6'
                });
            } else {
                alert('กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม KTB');
            }
            return;
        }

        const state = window._f16KtbExportState;
        state.keys = keys;
        state.activityCode = activityCode;
        state.activityTitle = activityTitle || ('[' + activityCode + ']');
        state.headers = {};
        state.tables = {};
        state.rawFiles = {};
        state.counts = {};
        state.subfolderName = 'F16_KTB_' + activityCode.toUpperCase() + '_' + Date.now();
        window._f16KtbSortState = {};

        $('#f16KtbModalActivityTitle').text(state.activityTitle);
        $('#f16KtbModalSelectedBadge').text(keys.length + ' รายการที่เลือก');
        $('#f16KtbExportProgressText').text('');

        // Reset UI State
        $('#f16KtbLoadingOverlay').show();
        $('#f16KtbMainContent').hide();
        $('#btnExecuteF16KtbExport').prop('disabled', true);

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('f16KtbExportModal')).show();
        } else {
            $('#f16KtbExportModal').modal('show');
        }

        // Fetch Preview Data
        $.ajax({
            url: "{{ url('ktb/f16_preview') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                keys: state.keys,
                activity_code: state.activityCode
            },
            success: function (res) {
                $('#f16KtbLoadingOverlay').hide();
                $('#f16KtbMainContent').fadeIn(200);
                $('#btnExecuteF16KtbExport').prop('disabled', false);

                if (res.success) {
                    state.headers = res.headers || {};
                    state.tables = res.tables || {};
                    state.rawFiles = res.raw_files || {};
                    state.counts = res.counts || {};
                    state.subfolderName = res.subfolder_name || ('F16_KTB_' + activityCode.toUpperCase() + '_' + Date.now());

                    $('#f16KtbSubfolderPreviewText').text('(เช่น ' + state.subfolderName + ')');

                    // Update 6 Tabs
                    const fileKeys = ['INS', 'PAT', 'OPD', 'ODX', 'ADP', 'DRU'];
                    fileKeys.forEach(function(k) {
                        const count = state.counts[k] || 0;
                        const badgeEl = $('#f16-ktb-badge-' + k);
                        const paneCountEl = $('#pane-ktb-count-' + k);
                        const rawTextarea = $('#preview-raw-ktb-' + k);

                        badgeEl.text(count);
                        paneCountEl.text(count + ' แถว');

                        if (count > 0) {
                            badgeEl.removeClass('badge-count-zero').addClass('badge-count-success');
                            rawTextarea.val(state.rawFiles[k] || '');
                        } else {
                            badgeEl.removeClass('badge-count-success').addClass('badge-count-zero');
                            rawTextarea.val('(ไม่มีข้อมูลสำหรับแฟ้มนี้)');
                        }

                        renderF16KtbTableHead(k);
                        renderF16KtbTableBody(k);
                    });

                    // Activate ADP tab by default
                    $('#f16-ktb-tab-ADP').trigger('click');
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('เกิดข้อผิดพลาด', res.message || 'ไม่สามารถประมวลผล 16 แฟ้ม KTB ได้', 'error');
                    } else {
                        alert(res.message || 'ไม่สามารถประมวลผล 16 แฟ้ม KTB ได้');
                    }
                }
            },
            error: function (xhr) {
                $('#f16KtbLoadingOverlay').hide();
                const errMsg = xhr.responseJSON?.message || 'ไม่สามารถเชื่อมต่อ Server ได้';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('ข้อผิดพลาด', errMsg, 'error');
                } else {
                    alert(errMsg);
                }
            }
        });
    };

    /**
     * บันทึกไฟล์ .txt ทั้ง 6 แฟ้ม KTB ลงโฟลเดอร์โดยตรงผ่าน File System Access API
     */
    window.executeF16KtbDirectoryExport = async function() {
        const state = window._f16KtbExportState;
        if (!state.keys || state.keys.length === 0) {
            alert('ไม่พบรายการที่เลือก');
            return;
        }

        // Check Browser Support for Directory Picker
        if (!('showDirectoryPicker' in window)) {
            executeKtbZipExport();
            return;
        }

        let dirHandle;
        try {
            // 1. Open Native Folder Selection Dialog
            dirHandle = await window.showDirectoryPicker({
                mode: 'readwrite',
                startIn: 'downloads'
            });
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Directory Picker Error:', err);
            }
            return;
        }

        // Show Exporting Indicator
        const btn = $('#btnExecuteF16KtbExport');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>กำลังส่งออกไฟล์ .txt...');
        $('#f16KtbExportProgressText').text('⏳ กำลังเตรียมไฟล์ .txt ทั้ง 6 แฟ้ม KTB...');

        const fileKeys = ['INS', 'PAT', 'OPD', 'ODX', 'ADP', 'DRU'];

        try {
            const createSubfolder = $('#f16KtbCreateSubfolderSwitch').is(':checked');
            let targetDir = dirHandle;
            if (createSubfolder) {
                targetDir = await dirHandle.getDirectoryHandle(state.subfolderName, { create: true });
            }

            for (const k of fileKeys) {
                const fileName = k + '.txt';
                const fileContent = state.rawFiles[k] || '';

                const fileHandle = await targetDir.getFileHandle(fileName, { create: true });
                const writable = await fileHandle.createWritable();
                await writable.write(fileContent);
                await writable.close();
            }

            btn.prop('disabled', false).html(originalHtml);
            $('#f16KtbExportProgressText').html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>ส่งออกสำเร็จครบ 6 แฟ้ม KTB (.txt)</span>');

            const folderDisplay = createSubfolder ? state.subfolderName : dirHandle.name;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'ส่งออก 16 แฟ้ม (KTB .txt) สำเร็จเรียบร้อย!',
                    html: `
                        <div class="text-start p-3 bg-light rounded small mt-2">
                            <div class="mb-1"><b>📁 โฟลเดอร์:</b> <code class="text-primary fs-6">${folderDisplay}</code></div>
                            <div class="mb-1"><b>📄 จำนวนไฟล์:</b> ครบ 6 แฟ้มหลัก KTB (.txt)</div>
                            <div class="mb-0"><b>👥 ผู้รับบริการ:</b> ${state.keys.length} รายการ</div>
                        </div>
                        <div class="mt-3 text-muted small">
                            เปิดหน้าเว็บ <b>Krungthai Digital Health Platform (healthplatform.krungthai.com)</b> เลือก <b>Text Format (.TXT)</b> แล้วเลือกไฟล์ .txt จากโฟลเดอร์นี้เพื่อนำเข้าได้ทันที
                        </div>
                    `,
                    confirmButtonText: 'รับทราบ',
                    confirmButtonColor: '#0077b6'
                });
            } else {
                alert('ส่งออกสำเร็จเรียบร้อยที่โฟลเดอร์: ' + folderDisplay);
            }
        } catch (writeErr) {
            btn.prop('disabled', false).html(originalHtml);
            $('#f16KtbExportProgressText').text('');
            console.error('File write error:', writeErr);
            if (typeof Swal !== 'undefined') {
                Swal.fire('เกิดข้อผิดพลาดในการเขียนไฟล์', writeErr.message, 'error');
            } else {
                alert('เกิดข้อผิดพลาดในการเขียนไฟล์: ' + writeErr.message);
            }
        }
    };

    /**
     * สั่งสร้างและดาวน์โหลดไฟล์ 16 แฟ้ม .ZIP (คลิกเดียวจบ โหลดไฟล์ Zip ลงเครื่องทันที)
     */
    window.executeKtbZipExport = function() {
        const state = window._f16KtbExportState;
        if (!state.keys || state.keys.length === 0) {
            alert('ไม่พบรายการที่เลือก');
            return;
        }

        const btn = $('#btnKtbDownloadZip');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>กำลังบีบอัดไฟล์ Zip...');
        $('#f16KtbExportProgressText').text('⏳ กำลังสร้างไฟล์ Zip 16 แฟ้ม KTB...');

        $.ajax({
            url: "{{ url('ktb/f16_export') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                keys: state.keys,
                activity_code: state.activityCode
            },
            success: function (res) {
                btn.prop('disabled', false).html(originalHtml);
                $('#f16KtbExportProgressText').html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>ดาวน์โหลดไฟล์ Zip สำเร็จ</span>');
                if (res.success && res.download_url) {
                    window.location.href = res.download_url;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ส่งออกไฟล์ 16 แฟ้ม (.ZIP) สำเร็จ!',
                            html: `
                                <div class="text-start p-3 bg-light rounded small mt-2">
                                    <div class="mb-1"><b>📦 ชื่อไฟล์:</b> <code class="text-primary fs-6">${res.zip_file_name}</code></div>
                                    <div class="mb-1"><b>📄 เนื้อหา:</b> 6 แฟ้มมาตรฐาน KTB (.txt)</div>
                                    <div class="mb-0"><b>👥 ผู้รับบริการ:</b> ${state.keys.length} รายการ</div>
                                </div>
                                <div class="mt-3 text-muted small">
                                    ไฟล์ .zip ถูกดาวน์โหลดลงเครื่องเรียบร้อยแล้ว ท่านสามารถแตกไฟล์ (Extract) แล้วนำไฟล์ .txt ทั้ง 6 แฟ้มไปอัปโหลดขึ้นระบบ <b>Krungthai Digital Health Platform</b> ได้ทันที
                                </div>
                            `,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#0077b6'
                        });
                    }
                } else {
                    alert(res.message || 'ไม่สามารถส่งออกไฟล์ได้');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html(originalHtml);
                $('#f16KtbExportProgressText').text('');
                alert(xhr.responseJSON?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ Server');
            }
        });
    };
</script>
