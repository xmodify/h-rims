<!-- Modal: F16 e-Claim Export Center (Reusable 16 Files Component) -->
<style>
    .f16-sortable-th {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.15s ease;
        white-space: nowrap;
        position: relative;
    }
    .f16-sortable-th:hover {
        background-color: #e2e8f0 !important;
    }
    .f16-table-container {
        max-height: 300px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #fff;
    }
    .f16-table-container thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        border-bottom: 2px solid #cbd5e1 !important;
        padding: 7px 10px !important;
    }
    .f16-table-container td {
        padding: 6px 10px !important;
        font-size: 0.8rem !important;
        white-space: nowrap;
    }
</style>

<div class="modal fade" id="f16EclaimExportModal" tabindex="-1" aria-labelledby="f16EclaimExportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <!-- Modal Header with e-Claim Teal Theme -->
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #0b7379 0%, #0e939a 50%, #17b7be 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="f16EclaimExportModalLabel">
                            ส่งออกข้อมูลมาตรฐาน 16 แฟ้ม (e-Claim)
                        </h5>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-white text-dark fw-bold" id="f16ModalClaimTitle">OFC (กรมบัญชีกลาง)</span>
                            <span class="badge text-white fw-bold" style="background-color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4);" id="f16ModalSelectedBadge">0 รายการที่เลือก</span>
                            <span class="text-white-50 small" id="f16ModalHcodeText">HCODE: {{ \App\Services\LicenseVerificationService::getHcode() }}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <!-- Loading State -->
                <div id="f16LoadingOverlay" class="text-center py-5">
                    <div class="spinner-border text-info mb-3" style="width: 3rem; height: 3rem; color: #0e939a !important;" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">กำลังประมวลผลและดึงข้อมูล 16 แฟ้มจาก HOSxP...</h6>
                    <p class="text-muted small">ระบบกำลังเตรียมไฟล์ INS, PAT, OPD, DRU, CHA, CHT, ADP ฯลฯ กรุณารอสักครู่</p>
                </div>

                <!-- Main Content Area (Hidden while loading) -->
                <div id="f16MainContent" style="display: none;">
                    <!-- 11 OP Tabs Bar -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 bg-white rounded">
                            <ul class="nav nav-pills nav-fill flex-wrap gap-1" id="f16Tabs" role="tablist">
                                @php
                                    $fileTabs = [
                                        [
                                            'key' => 'INS', 
                                            'name' => 'INS', 
                                            'desc' => 'สิทธิการรักษาพยาบาล',
                                            'headers' => ['HN', 'INSCL', 'SUBTYPE', 'CID', 'HCODE', 'DATEIN', 'DATEEXP', 'HOSPMAIN', 'HOSPSUB', 'GOVCODE', 'GOVNAME', 'PERMITNO', 'DOCNO', 'OWNRPID', 'OWNNAME', 'AN', 'SEQ', 'SUBINSCL', 'RELINSCL', 'HTYPE']
                                        ],
                                        [
                                            'key' => 'PAT', 
                                            'name' => 'PAT', 
                                            'desc' => 'ข้อมูลผู้ป่วย',
                                            'headers' => ['HCODE', 'HN', 'CHANGWAT', 'AMPHUR', 'DOB', 'SEX', 'MARRIAGE', 'OCCUPA', 'NATION', 'PERSON_ID', 'NAMEPAT', 'TITLE', 'FNAME', 'LNAME', 'IDTYPE']
                                        ],
                                        [
                                            'key' => 'OPD', 
                                            'name' => 'OPD', 
                                            'desc' => 'ข้อมูลผู้ป่วยนอก',
                                            'headers' => ['HN', 'CLINIC', 'DATEOPD', 'TIMEOPD', 'SEQ', 'UUC']
                                        ],
                                        [
                                            'key' => 'ORF', 
                                            'name' => 'ORF', 
                                            'desc' => 'ส่งต่อผู้ป่วยนอก',
                                            'headers' => ['HN', 'DATEOPD', 'CLINIC', 'REFER', 'REFERTYPE', 'SEQ']
                                        ],
                                        [
                                            'key' => 'ODX', 
                                            'name' => 'ODX', 
                                            'desc' => 'วินิจฉัยโรค OPD',
                                            'headers' => ['HN', 'DATEDX', 'CLINIC', 'DIAG', 'DXTYPE', 'DRDX', 'PERSON_ID', 'SEQ']
                                        ],
                                        [
                                            'key' => 'OOP', 
                                            'name' => 'OOP', 
                                            'desc' => 'หัตถการ OPD',
                                            'headers' => ['HN', 'DATEOPD', 'CLINIC', 'OPER', 'DROPID', 'PERSON_ID', 'SEQ']
                                        ],
                                        [
                                            'key' => 'DRU', 
                                            'name' => 'DRU', 
                                            'desc' => 'รายการสั่งใช้ยา',
                                            'headers' => ['HCODE', 'HN', 'AN', 'CLINIC', 'PERSON_ID', 'DATE_SERV', 'DID', 'DIDNAME', 'AMOUNT', 'DRUGPRICE', 'DRUGCOST', 'DIDSTD', 'UNIT', 'UNIT_PACK', 'SEQ', 'DRUGTYPE', 'DRUGREMARK', 'PA_NO', 'TOTCOPAY', 'USE_STATUS', 'TOTAL']
                                        ],
                                        [
                                            'key' => 'CHA', 
                                            'name' => 'CHA', 
                                            'desc' => 'ค่าบริการ 16 หมวด สปสช.',
                                            'headers' => ['HN', 'AN', 'DATE', 'CHRGITEM', 'AMOUNT', 'PERSON_ID', 'SEQ']
                                        ],
                                        [
                                            'key' => 'CHT', 
                                            'name' => 'CHT', 
                                            'desc' => 'สรุปยอดรวมค่าใช้จ่ายและใบเสร็จ',
                                            'headers' => ['HN', 'AN', 'DATE', 'TOTAL', 'PAID', 'PTTYPE', 'PERSON_ID', 'SEQ']
                                        ],
                                        [
                                            'key' => 'AER', 
                                            'name' => 'AER', 
                                            'desc' => 'อุบัติเหตุและฉุกเฉิน',
                                            'headers' => ['HN', 'AN', 'DATEOPD', 'AUTHAE', 'AEDATE', 'AETIME', 'AETYPE', 'REFER_NO', 'REFMAINI', 'IREFTYPE', 'REFMAINO', 'OREFTYPE', 'UCAE', 'EMTYPE', 'SEQ', 'AESTATUS', 'DALERT', 'TALERT']
                                        ],
                                        [
                                            'key' => 'ADP', 
                                            'name' => 'ADP', 
                                            'desc' => 'บริการเสริม/อุปกรณ์/PPFS',
                                            'headers' => ['HN', 'AN', 'DATEOPD', 'TYPE', 'CODE', 'QTY', 'RATE', 'SEQ', 'CAGCODE', 'DOSE', 'CA_TYPE', 'SERIALNO', 'TOTCOPAY', 'USE_STATUS', 'TOTAL', 'QTYDAY', 'TMLTCODE', 'STATUS1', 'BI', 'CLINIC', 'ITEMSRC', 'PROVIDER', 'GRAVIDA', 'GA_WEEK', 'DCIP/E_SCREEN', 'LMP', 'SP_ITEM', 'CHECK_KEY', 'GUID']
                                        ],
                                    ];
                                @endphp

                                @foreach($fileTabs as $index => $tab)
                                <li class="nav-item f16-tab-item" role="presentation">
                                    <button class="nav-link text-center px-1 py-1 {{ $index === 0 ? 'active' : '' }}" 
                                            id="f16-tab-{{ $tab['key'] }}" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#f16-pane-{{ $tab['key'] }}" 
                                            type="button" 
                                            role="tab"
                                            style="font-size: 0.78rem; min-width: 65px;">
                                        <div class="fw-bold">{{ $tab['name'] }}</div>
                                        <span class="badge rounded-pill bg-secondary text-white f16-badge-count mt-1" id="badge-count-{{ $tab['key'] }}" style="font-size: 0.68rem;">0</span>
                                    </button>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Tab Contents / Table Views & Raw Text -->
                    <div class="tab-content" id="f16TabPanes">
                        @foreach($fileTabs as $index => $tab)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="f16-pane-{{ $tab['key'] }}" role="tabpanel">
                            <!-- Card Container -->
                            <div class="card border shadow-sm" style="border-color: #dee2e6; border-radius: 8px; overflow: hidden;">
                                <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-table text-primary"></i>
                                        <span class="fw-bold text-dark">{{ $tab['name'] }}.txt</span>
                                        <span class="text-muted small">({{ $tab['desc'] }})</span>
                                        <span class="text-muted small ms-2"><i class="bi bi-info-circle me-1"></i>คลิกที่หัวคอลัมน์เพื่อเรียงลำดับ (Sort)</span>
                                    </div>
                                    <span class="badge bg-light text-secondary border px-2 py-1" id="pane-count-{{ $tab['key'] }}">0 แถว</span>
                                </div>
                                <div class="card-body p-0 bg-white">
                                    <!-- Table Area with Sortable Headers -->
                                    <div class="f16-table-container">
                                        <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-f16-{{ $tab['key'] }}">
                                            <thead>
                                                <tr>
                                                    @foreach($tab['headers'] as $colIdx => $headerTitle)
                                                    <th class="f16-sortable-th" onclick="sortF16Table('{{ $tab['key'] }}', {{ $colIdx }})" title="คลิกเพื่อเรียงตาม {{ $headerTitle }}">
                                                        <div class="d-flex align-items-center justify-content-between gap-1">
                                                            <span>{{ $headerTitle }}</span>
                                                            <span class="sort-icon text-muted small"><i class="bi bi-arrow-down-up"></i></span>
                                                        </div>
                                                    </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody id="table-tbody-{{ $tab['key'] }}">
                                                <tr>
                                                    <td colspan="{{ count($tab['headers']) }}" class="text-center text-muted py-4">
                                                        <i class="bi bi-inbox me-1"></i> ไม่มีข้อมูลสำหรับแฟ้มนี้
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Collapsible Raw Text Section (Like SSOP) -->
                                <div class="card-footer bg-light border-top p-0">
                                    <button class="btn btn-sm btn-light w-100 text-start d-flex justify-content-between align-items-center py-2 px-3 text-secondary border-0" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#raw-{{ $tab['key'] }}-collapse" 
                                            aria-expanded="false" 
                                            aria-controls="raw-{{ $tab['key'] }}-collapse">
                                        <span class="fw-bold">
                                            <i class="bi bi-file-earmark-code text-primary me-1"></i> ดูไฟล์ข้อความดิบ {{ $tab['name'] }}.txt (Raw Text)
                                        </span>
                                        <i class="bi bi-chevron-down text-muted"></i>
                                    </button>
                                    <div class="collapse" id="raw-{{ $tab['key'] }}-collapse">
                                        <div class="p-3 position-relative bg-white border-top">
                                            <button class="btn btn-xs btn-outline-secondary position-absolute end-0 top-0 m-3 shadow-sm" 
                                                    type="button"
                                                    onclick="copyF16RawText('{{ $tab['key'] }}')" 
                                                    style="font-size: 0.75rem; z-index: 10;">
                                                <i class="bi bi-clipboard me-1"></i> คัดลอก Raw Text
                                            </button>
                                            <textarea class="form-control text-monospace bg-light text-dark p-3 small" 
                                                      id="preview-raw-{{ $tab['key'] }}" 
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

                    <!-- Export Folder Options -->
                    <div class="card border-0 shadow-sm mt-3 bg-white">
                        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                            <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="f16CreateSubfolderSwitch" checked>
                                <label class="form-check-label fw-bold text-dark small cursor-pointer" for="f16CreateSubfolderSwitch">
                                    <i class="bi bi-folder-plus text-primary me-1"></i>สร้างโฟลเดอร์ย่อยตามสิทธิและวันเวลาอัตโนมัติ 
                                    <span class="text-muted fw-normal" id="f16SubfolderPreviewText">(เช่น F16_OFC_25690825_1800)</span>
                                </label>
                            </div>
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>เขียนไฟล์ .txt ทั้ง 11 แฟ้มลงโฟลเดอร์โดยตรง
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <span id="f16ExportProgressText" class="fw-bold text-primary small"></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
                    </button>
                    <button type="button" class="btn text-white px-4 fw-bold shadow-sm" id="btnExecuteF16Export" onclick="executeF16DirectoryExport()" style="background: linear-gradient(135deg, #0e939a 0%, #15b7bd 100%); border: none;">
                        <i class="bi bi-folder-check me-1"></i> <span id="btnExecuteF16ExportText">เลือกโฟลเดอร์และส่งออก (11 แฟ้ม OP .txt)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Global State for F16 e-Claim Export
    window._f16ExportState = {
        vns: [],
        claimCode: 'OFC',
        claimTitle: 'OP-OFC (ข้าราชการ)',
        headers: {},
        tables: {},
        rawFiles: {},
        counts: {},
        subfolderName: ''
    };

    window._f16SortState = {};

    function escapeHtmlF16(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * ฟังก์ชัน Render หัวคอลัมน์ (Thead) ตาม Headers ของแต่ละแฟ้มจริง
     */
    window.renderF16TableHead = function(key) {
        const headers = window._f16ExportState.headers[key] || [];
        const thead = document.querySelector(`#table-f16-${key} thead`);
        if (!thead || headers.length === 0) return;

        let html = '<tr>';
        for (let colIdx = 0; colIdx < headers.length; colIdx++) {
            const h = headers[colIdx];
            html += `<th class="f16-sortable-th" onclick="sortF16Table('${key}', ${colIdx})" title="คลิกเพื่อเรียงตาม ${escapeHtmlF16(h)}">
                <div class="d-flex align-items-center justify-content-between gap-1">
                    <span>${escapeHtmlF16(h)}</span>
                    <span class="sort-icon text-muted small"><i class="bi bi-arrow-down-up"></i></span>
                </div>
            </th>`;
        }
        html += '</tr>';
        thead.innerHTML = html;
    };

    /**
     * ฟังก์ชัน Render แถวใน Table Body ของแต่ละแฟ้ม
     */
    window.renderF16TableBody = function(key) {
        const rows = window._f16ExportState.tables[key] || [];
        const tbody = document.getElementById(`table-tbody-${key}`);
        if (!tbody) return;

        if (rows.length === 0) {
            const colCount = $(`#table-f16-${key} thead th`).length || 1;
            tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i> ไม่มีข้อมูลสำหรับแฟ้มนี้</td></tr>`;
            return;
        }

        let html = '';
        for (let r = 0; r < rows.length; r++) {
            const row = rows[r];
            html += '<tr>';
            for (let c = 0; c < row.length; c++) {
                const cell = row[c] !== null && row[c] !== undefined ? row[c] : '';
                html += `<td>${escapeHtmlF16(cell)}</td>`;
            }
            html += '</tr>';
        }
        tbody.innerHTML = html;
    };

    /**
     * ฟังก์ชันเรียงลำดับคอลัมน์ของ Table ในแต่ละแฟ้ม
     */
    window.sortF16Table = function(key, colIdx) {
        const tableData = window._f16ExportState.tables[key];
        if (!tableData || tableData.length === 0) return;

        const currentSort = window._f16SortState[key] || { col: -1, dir: 'asc' };
        let newDir = 'asc';
        if (currentSort.col === colIdx) {
            newDir = currentSort.dir === 'asc' ? 'desc' : 'asc';
        }
        window._f16SortState[key] = { col: colIdx, dir: newDir };

        // ทำการ Sort ข้อมูล
        tableData.sort(function(a, b) {
            let valA = (a[colIdx] !== undefined && a[colIdx] !== null) ? a[colIdx].toString().trim() : '';
            let valB = (b[colIdx] !== undefined && b[colIdx] !== null) ? b[colIdx].toString().trim() : '';

            // ตรวจสอบว่าเป็นตัวเลขหรือไม่
            const numA = parseFloat(valA);
            const numB = parseFloat(valB);
            if (!isNaN(numA) && !isNaN(numB) && valA === numA.toString() && valB === numB.toString()) {
                return newDir === 'asc' ? numA - numB : numB - numA;
            }

            // เปรียบเทียบแบบข้อความ/วันที่
            const cmp = valA.localeCompare(valB, 'th', { numeric: true, sensitivity: 'base' });
            return newDir === 'asc' ? cmp : -cmp;
        });

        // ปรับแต่ง Icon บน Header
        $(`#table-f16-${key} th.f16-sortable-th`).each(function(idx) {
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

        renderF16TableBody(key);
    };

    /**
     * คัดลอก Raw Text
     */
    window.copyF16RawText = function(key) {
        const textarea = document.getElementById('preview-raw-' + key);
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
     * ฟังก์ชันเปิด Modal ส่งออก 16 แฟ้ม (e-Claim)
     * @param {Object} config { vns: ['690701130818', ...], claimCode: 'OFC', claimTitle: 'OP-OFC (ข้าราชการ)' }
     */
    window.openF16EclaimExportModal = function(config) {
        config = config || {};
        const vns = config.vns || [];
        const claimCode = config.claimCode || 'OFC';
        const claimTitle = config.claimTitle || 'OP-OFC (ข้าราชการ)';

        if (!vns || vns.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้เลือกรายการ',
                    text: 'กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#0e939a'
                });
            } else {
                alert('กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม');
            }
            return;
        }

        // Reset State
        window._f16ExportState.vns = vns;
        window._f16ExportState.claimCode = claimCode;
        window._f16ExportState.claimTitle = claimTitle;
        window._f16ExportState.tables = {};
        window._f16ExportState.rawFiles = {};
        window._f16ExportState.counts = {};
        window._f16SortState = {};

        // Update Header
        $('#f16ModalClaimTitle').text(claimTitle);
        $('#f16ModalSelectedBadge').text(vns.length + ' รายการที่เลือก');
        $('#f16ExportProgressText').text('');

        // Reset UI to Loading State
        $('#f16LoadingOverlay').show();
        $('#f16MainContent').hide();
        $('#btnExecuteF16Export').prop('disabled', true);

        // Show Modal (Compatible with both Bootstrap 4 and Bootstrap 5)
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('f16EclaimExportModal')).show();
            } catch (e) {
                $('#f16EclaimExportModal').modal('show');
            }
        } else {
            $('#f16EclaimExportModal').modal('show');
        }

        // AJAX Request for Preview & Generation
        $.ajax({
            url: '{{ route("f16_eclaim_export.preview") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                vns: vns,
                claim_code: claimCode
            },
            success: function(res) {
                $('#f16LoadingOverlay').hide();
                $('#f16MainContent').show();
                $('#btnExecuteF16Export').prop('disabled', false);

                if (res.status === 'success') {
                    const counts = res.counts || {};
                    const headers = res.headers || {};
                    const tables = res.tables || {};
                    const rawFiles = res.raw_files || {};

                    window._f16ExportState.headers = headers;
                    window._f16ExportState.tables = tables;
                    window._f16ExportState.rawFiles = rawFiles;
                    window._f16ExportState.counts = counts;

                    // Update Tab Badges, Tables, and Raw Text (11 OP Files)
                    const keys = ['INS', 'PAT', 'OPD', 'ORF', 'ODX', 'OOP', 'DRU', 'CHA', 'CHT', 'AER', 'ADP'];
                    keys.forEach(function(k) {
                        const count = counts[k] || 0;
                        const badgeEl = $('#badge-count-' + k);
                        const paneCountEl = $('#pane-count-' + k);
                        const rawTextarea = $('#preview-raw-' + k);

                        badgeEl.text(count);
                        paneCountEl.text(count + ' แถว');

                        if (count > 0) {
                            badgeEl.removeClass('bg-secondary').addClass('text-white').css('background-color', '#0e939a');
                            rawTextarea.val(rawFiles[k] || '');
                        } else {
                            badgeEl.removeClass('text-white').addClass('bg-secondary').css('background-color', '');
                            rawTextarea.val('(ไม่มีข้อมูลสำหรับแฟ้มนี้)');
                        }

                        // Render Dynamic Table Header and Body
                        renderF16TableHead(k);
                        renderF16TableBody(k);
                    });

                    // Activate First Tab
                    $('#f16-tab-INS').trigger('click');
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('เกิดข้อผิดพลาด', res.message || 'ไม่สามารถประมวลผล 16 แฟ้มได้', 'error');
                    } else {
                        alert(res.message || 'ไม่สามารถประมวลผล 16 แฟ้มได้');
                    }
                }
            },
            error: function(xhr) {
                $('#f16LoadingOverlay').hide();
                let errMsg = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('ข้อผิดพลาด', errMsg, 'error');
                } else {
                    alert(errMsg);
                }
            }
        });
    };

    /**
     * บันทึกไฟล์ .txt ทั้ง 11 แฟ้ม OP ลงโฟลเดอร์โดยตรงผ่าน File System Access API
     */
    window.executeF16DirectoryExport = async function() {
        const state = window._f16ExportState;
        if (!state.vns || state.vns.length === 0) {
            alert('ไม่พบรายการที่เลือก');
            return;
        }

        // Check Browser Support for Directory Picker
        if (!('showDirectoryPicker' in window)) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'เบราว์เซอร์ไม่รองรับ Directory Picker',
                    html: 'ฟังก์ชันบันทึกลงโฟลเดอร์โดยตรงรองรับบน <b>Google Chrome</b> หรือ <b>Microsoft Edge</b> กรุณาเปิดใช้งานผ่าน Chrome / Edge เพื่อความสะดวกในการใช้งานครับ',
                    confirmButtonText: 'เข้าใจแล้ว'
                });
            } else {
                alert('ฟังก์ชันบันทึกลงโฟลเดอร์โดยตรงรองรับบน Google Chrome หรือ Microsoft Edge');
            }
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
            // User cancelled folder selection
            if (err.name !== 'AbortError') {
                console.error('Directory Picker Error:', err);
            }
            return;
        }

        // Show Exporting Indicator
        const btn = $('#btnExecuteF16Export');
        const originalBtnHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>กำลังบันทึกไฟล์...');
        $('#f16ExportProgressText').text('⏳ กำลังดึงข้อมูลเนื้อหาเต็มทั้ง 11 แฟ้ม...');

        // 2. Fetch Full File Content from Server
        $.ajax({
            url: '{{ route("f16_eclaim_export.export_data") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                vns: state.vns,
                claim_code: state.claimCode
            },
            success: async function(res) {
                if (res.status === 'success') {
                    const files = res.files || {};
                    const counts = res.counts || {};
                    const subfolderName = res.subfolder_name || ('F16_' + state.claimCode + '_' + Date.now());
                    const createSubfolder = $('#f16CreateSubfolderSwitch').is(':checked');

                    try {
                        // 3. Determine Target Directory
                        let targetDir = dirHandle;
                        if (createSubfolder) {
                            targetDir = await dirHandle.getDirectoryHandle(subfolderName, { create: true });
                        }

                        $('#f16ExportProgressText').text('⏳ กำลังเขียนไฟล์ .txt ทั้ง 11 แฟ้มลงโฟลเดอร์...');

                        // 4. Write each of the 11 OP .txt files
                        const fileKeys = ['INS', 'PAT', 'OPD', 'ORF', 'ODX', 'OOP', 'DRU', 'CHA', 'CHT', 'AER', 'ADP'];
                        let writtenFiles = 0;

                        for (const k of fileKeys) {
                            const fileName = k + '.txt';
                            const fileContent = files[k] || '';
                            
                            // Create or overwrite file
                            const fileHandle = await targetDir.getFileHandle(fileName, { create: true });
                            const writable = await fileHandle.createWritable();
                            
                            // Write UTF-8 string
                            await writable.write(fileContent);
                            await writable.close();
                            writtenFiles++;
                        }

                        btn.prop('disabled', false).html(originalBtnHtml);
                        $('#f16ExportProgressText').html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>ส่งออกสำเร็จครบ 11 แฟ้ม</span>');

                        // 5. Show Success Notification
                        const folderDisplay = createSubfolder ? subfolderName : dirHandle.name;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ส่งออก 16 แฟ้ม (e-Claim) สำเร็จเรียบร้อย!',
                                html: `
                                    <div class="text-start p-3 bg-light rounded small mt-2">
                                        <div class="mb-1"><b>📁 โฟลเดอร์:</b> <code class="text-primary fs-6">${folderDisplay}</code></div>
                                        <div class="mb-1"><b>📄 จำนวนไฟล์:</b> ครบ 11 แฟ้มผู้ป่วยนอก (.txt)</div>
                                        <div class="mb-0"><b>👥 ผู้รับบริการ:</b> ${state.vns.length} รายการ</div>
                                    </div>
                                    <div class="mt-3 text-muted small">
                                        เปิดหน้า <b>e-Claim (import16)</b> แล้วกดปุ่ม <b>[ แนบไฟล์ ]</b> เพื่อเลือกไฟล์ทั้งหมดไปนำเข้าได้ทันที
                                    </div>
                                `,
                                confirmButtonText: 'รับทราบ',
                                confirmButtonColor: '#0e939a'
                            });
                        } else {
                            alert('ส่งออกสำเร็จเรียบร้อยที่โฟลเดอร์: ' + folderDisplay);
                        }

                    } catch (writeErr) {
                        console.error('File write error:', writeErr);
                        btn.prop('disabled', false).html(originalBtnHtml);
                        $('#f16ExportProgressText').text('');
                        alert('เกิดข้อผิดพลาดในการเขียนไฟล์: ' + writeErr.message);
                    }
                } else {
                    btn.prop('disabled', false).html(originalBtnHtml);
                    $('#f16ExportProgressText').text('');
                    alert(res.message || 'ไม่สามารถส่งออกข้อมูลได้');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalBtnHtml);
                $('#f16ExportProgressText').text('');
                let errMsg = 'เกิดข้อผิดพลาดในการส่งออกข้อมูล';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                alert(errMsg);
            }
        });
    };
</script>
