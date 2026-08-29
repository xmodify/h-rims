<!-- Modal: F16 FDH Export Center (Reusable 16/17 Files Component for FDH) -->
<style>
    .f16-fdh-sortable-th {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.15s ease;
        white-space: nowrap;
        position: relative;
    }
    .f16-fdh-sortable-th:hover {
        background-color: #e2e8f0 !important;
    }
    .f16-fdh-table-container {
        max-height: 300px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #fff;
    }
    .f16-fdh-table-container thead th {
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
    .f16-fdh-table-container td {
        padding: 6px 10px !important;
        font-size: 0.8rem !important;
        white-space: nowrap;
    }
    .f16-fdh-tab-item .nav-link {
        border-radius: 6px;
        font-size: 0.76rem;
        min-width: 58px;
        transition: all 0.2s ease;
        color: #475569;
    }
    .f16-fdh-tab-item .nav-link.active {
        background-color: #0d6efd !important;
        color: #fff !important;
    }
    .f16-fdh-tab-item .nav-link.active .badge {
        background-color: rgba(255,255,255,0.3) !important;
        color: #fff !important;
        border-color: rgba(255,255,255,0.5) !important;
    }
</style>

<div class="modal fade" id="f16FdhExportModal" tabindex="-1" aria-labelledby="f16FdhExportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <!-- Modal Header with e-Claim / FDH Teal Theme -->
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #0b7379 0%, #0e939a 50%, #17b7be 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="f16FdhExportModalLabel">
                            ส่งออกข้อมูลมาตรฐาน 16/17 แฟ้ม (FDH)
                        </h5>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-white text-dark fw-bold" id="f16FdhModalClaimTitle">UCS (สิทธิหลักประกันสุขภาพ)</span>
                            <span class="badge text-white fw-bold" style="background-color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4);" id="f16FdhModalSelectedBadge">0 รายการที่เลือก</span>
                            <span class="text-white-50 small" id="f16FdhModalHcodeText">HCODE: {{ \App\Services\LicenseVerificationService::getHcode() }}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" onclick="closeFdhModal()" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <!-- Loading State -->
                <div id="f16FdhLoadingOverlay" class="text-center py-5">
                    <div class="spinner-border text-info mb-3" style="width: 3rem; height: 3rem; color: #0e939a !important;" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">กำลังประมวลผลและดึงข้อมูล 17 แฟ้ม FDH จาก HOSxP...</h6>
                    <p class="text-muted small">ระบบกำลังเตรียมไฟล์ INS, PAT, OPD, IPD, DRU, CHA, CHT, ADP, AER, LVD, LABFU ฯลฯ กรุณารอสักครู่</p>
                </div>

                <!-- Main Content Area (Hidden while loading) -->
                <div id="f16FdhMainContent" style="display: none;">
                    <!-- 17 Tabs Bar (FDH Standard) -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 bg-white rounded">
                            <ul class="nav nav-pills nav-fill flex-wrap gap-1" id="f16FdhTabs" role="tablist">
                                @php
                                    $fileTabs = [
                                        [
                                            'key' => 'INS', 
                                            'name' => 'INS', 
                                            'desc' => 'สิทธิการรักษาพยาบาล',
                                            'headers' => ['HN', 'INSCL', 'SUBTYPE', 'CID', 'HCODE', 'DATEEXP', 'HOSPMAIN', 'HOSPSUB', 'GOVCODE', 'GOVNAME', 'PERMITNO', 'DOCNO', 'OWNRPID', 'OWNNAME', 'AN', 'SEQ', 'SUBINSCL', 'RELINSCL', 'HTYPE']
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
                                            'desc' => 'ข้อมูลผู้ป่วยนอกและ Vital Signs',
                                            'headers' => ['HN', 'CLINIC', 'DATEOPD', 'TIMEOPD', 'SEQ', 'UUC', 'DETAIL', 'BTEMP', 'SBP', 'DBP', 'PR', 'RR', 'OPTYPE', 'TYPEIN', 'TYPEOUT']
                                        ],
                                        [
                                            'key' => 'ORF', 
                                            'name' => 'ORF', 
                                            'desc' => 'ส่งต่อผู้ป่วยนอก',
                                            'headers' => ['HN', 'DATEOPD', 'CLINIC', 'REFER', 'REFERTYPE', 'SEQ', 'REFERDATE']
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
                                            'desc' => 'หัตถการและราคา OPD',
                                            'headers' => ['HN', 'DATEOPD', 'CLINIC', 'OPER', 'DROPID', 'PERSON_ID', 'SEQ', 'SERVPRICE']
                                        ],
                                        [
                                            'key' => 'IPD', 
                                            'name' => 'IPD', 
                                            'desc' => 'ข้อมูลผู้ป่วยใน',
                                            'headers' => ['HN', 'AN', 'DATEADM', 'TIMEADM', 'DATEDSC', 'TIMEDSC', 'DISCHS', 'DISCHT', 'WARDDSC', 'DEPT', 'ADM_W', 'UUC', 'SVCTYPE']
                                        ],
                                        [
                                            'key' => 'IRF', 
                                            'name' => 'IRF', 
                                            'desc' => 'ส่งต่อผู้ป่วยใน',
                                            'headers' => ['AN', 'REFER', 'REFERTYPE']
                                        ],
                                        [
                                            'key' => 'IDX', 
                                            'name' => 'IDX', 
                                            'desc' => 'วินิจฉัยโรค IPD',
                                            'headers' => ['AN', 'DIAG', 'DXTYPE', 'DRDX']
                                        ],
                                        [
                                            'key' => 'IOP', 
                                            'name' => 'IOP', 
                                            'desc' => 'หัตถการ IPD',
                                            'headers' => ['AN', 'OPER', 'OPTYPE', 'DROPID', 'DATEIN', 'TIMEIN', 'DATEOUT', 'TIMEOUT']
                                        ],
                                        [
                                            'key' => 'CHT', 
                                            'name' => 'CHT', 
                                            'desc' => 'สรุปยอดรวมค่าใช้จ่ายและใบเสร็จ',
                                            'headers' => ['HN', 'AN', 'DATE', 'TOTAL', 'PAID', 'PTTYPE', 'PERSON_ID', 'SEQ', 'OPD_MEMO', 'INVOICE_NO', 'INVOICE_LT']
                                        ],
                                        [
                                            'key' => 'CHA', 
                                            'name' => 'CHA', 
                                            'desc' => 'ค่าบริการ 16 หมวด FDH',
                                            'headers' => ['HN', 'AN', 'DATE', 'CHRGITEM', 'AMOUNT', 'PERSON_ID', 'SEQ']
                                        ],
                                        [
                                            'key' => 'AER', 
                                            'name' => 'AER', 
                                            'desc' => 'อุบัติเหตุ ฉุกเฉิน และรับส่งต่อ',
                                            'headers' => ['HN', 'AN', 'DATEOPD', 'AUTHAE', 'AEDATE', 'AETIME', 'AETYPE', 'REFER_NO', 'REFMAINI', 'IREFTYPE', 'REFMAINO', 'OREFTYPE', 'UCAE', 'EMTYPE', 'SEQ', 'AESTATUS', 'DALERT', 'TALERT']
                                        ],
                                        [
                                            'key' => 'ADP', 
                                            'name' => 'ADP', 
                                            'desc' => 'บริการเสริม/อุปกรณ์/PPFS',
                                            'headers' => ['HN', 'AN', 'DATEOPD', 'TYPE', 'CODE', 'QTY', 'RATE', 'SEQ', 'CAGCODE', 'DOSE', 'CA_TYPE', 'SERIALNO', 'TOTCOPAY', 'USE_STATUS', 'TOTAL', 'QTYDAY', 'TMLTCODE', 'STATUS1', 'BI', 'CLINIC', 'ITEMSRC', 'PROVIDER', 'GRAVIDA', 'GA_WEEK', 'DCIP/E_SCREEN', 'LMP', 'SP_ITEM', 'CHECK_KEY', 'GUID']
                                        ],
                                        [
                                            'key' => 'LVD', 
                                            'name' => 'LVD', 
                                            'desc' => 'วันลากลับบ้าน (Leave Day)',
                                            'headers' => ['SEQLVD', 'AN', 'DATEOUT', 'TIMEOUT', 'DATEIN', 'TIMEIN', 'QTYDAY']
                                        ],
                                        [
                                            'key' => 'DRU', 
                                            'name' => 'DRU', 
                                            'desc' => 'รายการสั่งใช้ยา FDH',
                                            'headers' => ['HCODE', 'HN', 'AN', 'CLINIC', 'PERSON_ID', 'DATE_SERV', 'DID', 'DIDNAME', 'AMOUNT', 'DRUGPRICE', 'DRUGCOST', 'DIDSTD', 'UNIT', 'UNIT_PACK', 'SEQ', 'DRUGREMARK', 'PA_NO', 'TOTCOPAY', 'USE_STATUS', 'TOTAL', 'SIGCODE', 'SIGTEXT', 'PROVIDER', 'SP_ITEM']
                                        ],
                                        [
                                            'key' => 'LABFU', 
                                            'name' => 'LAB', 
                                            'desc' => 'ผลตรวจทางห้องปฏิบัติการ (LABFU)',
                                            'headers' => ['HCODE', 'HN', 'PERSON_ID', 'DATESERV', 'SEQ', 'LABTEST', 'LABRESULT']
                                        ],
                                    ];
                                @endphp

                                @foreach($fileTabs as $index => $tab)
                                <li class="nav-item f16-fdh-tab-item" role="presentation">
                                    <button class="nav-link text-center px-1 py-1 {{ $index === 0 ? 'active' : '' }}" 
                                            id="f16-fdh-tab-{{ $tab['key'] }}" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#f16-fdh-pane-{{ $tab['key'] }}" 
                                            data-toggle="pill" 
                                            data-target="#f16-fdh-pane-{{ $tab['key'] }}" 
                                            onclick="switchFdhTab('{{ $tab['key'] }}')"
                                            type="button" 
                                            role="tab"
                                            style="font-size: 0.76rem; min-width: 58px;">
                                        <div class="fw-bold">{{ $tab['name'] }}</div>
                                        <span class="badge rounded-pill bg-light text-secondary border f16-fdh-badge-count mt-1" id="f16-fdh-badge-{{ $tab['key'] }}" style="font-size: 0.68rem;">0</span>
                                    </button>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Tab Contents / Table Views & Raw Text -->
                    <div class="tab-content" id="f16FdhTabContent">
                        @foreach($fileTabs as $index => $tab)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="f16-fdh-pane-{{ $tab['key'] }}" role="tabpanel">
                            <!-- Card Container -->
                            <div class="card border shadow-sm" style="border-color: #dee2e6; border-radius: 8px; overflow: hidden;">
                                <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-table text-primary"></i>
                                        <span class="fw-bold text-dark">{{ $tab['name'] }}.txt</span>
                                        <span class="text-muted small">({{ $tab['desc'] }})</span>
                                        <span class="text-muted small ms-2"><i class="bi bi-info-circle me-1"></i>คลิกที่หัวคอลัมน์เพื่อเรียงลำดับ (Sort)</span>
                                    </div>
                                    <span class="badge bg-light text-secondary border px-2 py-1" id="f16-fdh-pane-count-{{ $tab['key'] }}">0 แถว</span>
                                </div>
                                <div class="card-body p-0 bg-white">
                                    <!-- Table Area with Sortable Headers -->
                                    <div class="f16-fdh-table-container">
                                        <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-f16-fdh-{{ $tab['key'] }}">
                                            <thead>
                                                <tr>
                                                    @foreach($tab['headers'] as $colIdx => $headerTitle)
                                                    <th class="f16-fdh-sortable-th" onclick="sortF16FdhTable('{{ $tab['key'] }}', {{ $colIdx }})" title="คลิกเพื่อเรียงตาม {{ $headerTitle }}">
                                                        <div class="d-flex align-items-center justify-content-between gap-1">
                                                            <span>{{ $headerTitle }}</span>
                                                            <span class="sort-icon text-muted small"><i class="bi bi-arrow-down-up"></i></span>
                                                        </div>
                                                    </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody id="table-tbody-fdh-{{ $tab['key'] }}">
                                                <tr>
                                                    <td colspan="{{ count($tab['headers']) }}" class="text-center text-muted py-4">
                                                        <i class="bi bi-inbox me-1"></i> ไม่มีข้อมูลสำหรับแฟ้มนี้
                                                    </td>
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
                                            data-bs-target="#raw-fdh-{{ $tab['key'] }}-collapse" 
                                            data-toggle="collapse" 
                                            data-target="#raw-fdh-{{ $tab['key'] }}-collapse" 
                                            aria-expanded="false" 
                                            aria-controls="raw-fdh-{{ $tab['key'] }}-collapse">
                                        <span class="fw-bold">
                                            <i class="bi bi-file-earmark-code text-primary me-1"></i> ดูไฟล์ข้อความดิบ {{ $tab['name'] }}.txt (Raw Text)
                                        </span>
                                        <i class="bi bi-chevron-down text-muted"></i>
                                    </button>
                                    <div class="collapse" id="raw-fdh-{{ $tab['key'] }}-collapse">
                                        <div class="p-3 position-relative bg-white border-top">
                                            <button class="btn btn-xs btn-outline-secondary position-absolute end-0 top-0 m-3 shadow-sm" 
                                                    type="button"
                                                    onclick="copyF16FdhRawText('{{ $tab['key'] }}')" 
                                                    style="font-size: 0.75rem; z-index: 10;">
                                                <i class="bi bi-clipboard me-1"></i> คัดลอก Raw Text
                                            </button>
                                            <textarea class="form-control text-monospace bg-light text-dark p-3 small" 
                                                      id="preview-raw-fdh-{{ $tab['key'] }}" 
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

                    <!-- Export Folder Options (Switch style like OFC) -->
                    <div class="card border-0 shadow-sm mt-3 bg-white">
                        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                            <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="f16FdhCreateSubfolderSwitch" checked>
                                <label class="form-check-label fw-bold text-dark small cursor-pointer" for="f16FdhCreateSubfolderSwitch">
                                    <i class="bi bi-folder-plus text-primary me-1"></i>สร้างโฟลเดอร์ย่อยตามสิทธิและวันเวลาอัตโนมัติ 
                                    <span class="text-muted fw-normal" id="f16FdhSubfolderPreviewText">(เช่น FDH16_UCS_INCUP_25690825_1800)</span>
                                </label>
                            </div>
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>เขียนไฟล์ .txt ทั้ง 17 แฟ้ม FDH ลงโฟลเดอร์โดยตรง
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <span id="f16FdhExportProgressText" class="fw-bold text-primary small"></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" data-dismiss="modal" onclick="closeFdhModal()">
                        <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
                    </button>
                    <button type="button" class="btn text-white px-4 fw-bold shadow-sm" id="btnExecuteF16FdhExport" onclick="executeF16FdhDirectoryExport()" style="background: linear-gradient(135deg, #0e939a 0%, #15b7bd 100%); border: none;">
                        <i class="bi bi-folder-check me-1"></i> <span id="btnExecuteF16FdhExportText">เลือกโฟลเดอร์และส่งออก (17 แฟ้ม FDH .txt)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Global State for F16 FDH Export
    window._f16FdhExportState = {
        keys: [],
        vns: [],
        ans: [],
        isIp: false,
        type: 'op',
        claimCode: 'UCS',
        claimTitle: 'UCS (สิทธิหลักประกันสุขภาพ)',
        headers: {},
        tables: {},
        rawFiles: {},
        counts: {},
        subfolderName: '',
        hcode: '{{ \App\Services\LicenseVerificationService::getHcode() }}',
        totalVisits: 0
    };

    window._f16FdhSortState = {};

    function escapeHtmlF16Fdh(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * สลับแท็บ 17 แฟ้ม FDH
     */
    function switchFdhTab(key) {
        $('#f16FdhTabs .nav-link').removeClass('active');
        $('#f16FdhTabContent .tab-pane').removeClass('show active');
        $(`#f16-fdh-tab-${key}`).addClass('active');
        $(`#f16-fdh-pane-${key}`).addClass('show active');
    }

    /**
     * ปิด Modal ส่งออก 16 แฟ้ม FDH
     */
    function closeFdhModal() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                const m = bootstrap.Modal.getInstance(document.getElementById('f16FdhExportModal'));
                if (m) m.hide();
            } catch(e) {}
        }
        if (window.$ && typeof $.fn.modal !== 'undefined') {
            $('#f16FdhExportModal').modal('hide');
        }
        const m = document.getElementById('f16FdhExportModal');
        if (m) {
            m.style.display = 'none';
            m.classList.remove('show');
        }
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
    }

    /**
     * Render หัวคอลัมน์ Thead
     */
    function renderF16FdhTableHead(key) {
        const headers = window._f16FdhExportState.headers[key] || [];
        const thead = document.querySelector(`#table-f16-fdh-${key} thead`);
        if (!thead || headers.length === 0) return;

        let html = '<tr>';
        for (let colIdx = 0; colIdx < headers.length; colIdx++) {
            const h = headers[colIdx];
            html += `<th class="f16-fdh-sortable-th" onclick="sortF16FdhTable('${key}', ${colIdx})" title="คลิกเพื่อเรียงตาม ${escapeHtmlF16Fdh(h)}">
                <div class="d-flex align-items-center justify-content-between gap-1">
                    <span>${escapeHtmlF16Fdh(h)}</span>
                    <span class="sort-icon text-muted small"><i class="bi bi-arrow-down-up"></i></span>
                </div>
            </th>`;
        }
        html += '</tr>';
        thead.innerHTML = html;
    }

    /**
     * Render Body
     */
    function renderF16FdhTableBody(key) {
        const rows = window._f16FdhExportState.tables[key] || [];
        const tbody = document.getElementById(`table-tbody-fdh-${key}`);
        if (!tbody) return;

        if (rows.length === 0) {
            const colCount = $(`#table-f16-fdh-${key} thead th`).length || 1;
            tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center text-muted py-4"><i class="bi bi-inbox me-1"></i> ไม่มีข้อมูลสำหรับแฟ้มนี้</td></tr>`;
            return;
        }

        let html = '';
        for (let r = 0; r < rows.length; r++) {
            const row = rows[r];
            html += '<tr>';
            for (let c = 0; c < row.length; c++) {
                const cell = row[c] !== null && row[c] !== undefined ? row[c] : '';
                html += `<td>${escapeHtmlF16Fdh(cell)}</td>`;
            }
            html += '</tr>';
        }
        tbody.innerHTML = html;
    }

    /**
     * Sort Function
     */
    function sortF16FdhTable(key, colIdx) {
        const tableData = window._f16FdhExportState.tables[key];
        if (!tableData || tableData.length === 0) return;

        const currentSort = window._f16FdhSortState[key] || { col: -1, dir: 'asc' };
        let newDir = 'asc';
        if (currentSort.col === colIdx) {
            newDir = currentSort.dir === 'asc' ? 'desc' : 'asc';
        }
        window._f16FdhSortState[key] = { col: colIdx, dir: newDir };

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

        $(`#table-f16-fdh-${key} th.f16-fdh-sortable-th`).each(function(idx) {
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

        renderF16FdhTableBody(key);
    }

    /**
     * คัดลอก Raw Text
     */
    function copyF16FdhRawText(key) {
        const textarea = document.getElementById('preview-raw-fdh-' + key);
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
    }

    /**
     * ฟังก์ชันเปิด Modal ส่งออก 16 แฟ้ม (FDH)
     */
    window.openF16FdhExportModal = function(options) {
        options = options || {};
        const keys = options.keys || options.vns || options.ans || [];
        const isIp = !!options.isIp || options.type === 'ip';
        const claimCode = options.claimCode || 'UCS';
        const claimTitle = options.claimTitle || 'UCS (สิทธิหลักประกันสุขภาพ)';

        if (!keys || keys.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้เลือกรายการ',
                    text: 'กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม FDH',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#0e939a'
                });
            } else {
                alert('กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม FDH');
            }
            return;
        }

        // Reset State
        window._f16FdhExportState.keys = keys;
        window._f16FdhExportState.vns = isIp ? [] : keys;
        window._f16FdhExportState.ans = isIp ? keys : [];
        window._f16FdhExportState.isIp = isIp;
        window._f16FdhExportState.type = isIp ? 'ip' : 'op';
        window._f16FdhExportState.claimCode = claimCode;
        window._f16FdhExportState.claimTitle = claimTitle;
        window._f16FdhExportState.tables = {};
        window._f16FdhExportState.rawFiles = {};
        window._f16FdhExportState.counts = {};
        window._f16FdhSortState = {};

        // Update Header
        $('#f16FdhModalClaimTitle').text(claimTitle);
        $('#f16FdhModalSelectedBadge').text(keys.length + ' รายการที่เลือก');
        $('#f16FdhExportProgressText').text('');

        // Reset UI to Loading State
        $('#f16FdhLoadingOverlay').show();
        $('#f16FdhMainContent').hide();
        $('#btnExecuteF16FdhExport').prop('disabled', true);

        // Show Modal (Compatible with Bootstrap 4 & 5)
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('f16FdhExportModal')).show();
            } catch (e) {
                $('#f16FdhExportModal').modal('show');
            }
        } else if (window.$ && typeof $.fn.modal !== 'undefined') {
            $('#f16FdhExportModal').modal('show');
        } else {
            const m = document.getElementById('f16FdhExportModal');
            if (m) {
                m.style.display = 'block';
                m.classList.add('show');
            }
        }

        const postData = {
            _token: '{{ csrf_token() }}',
            type: isIp ? 'ip' : 'op',
            is_ip: isIp ? 1 : 0,
            claim_code: claimCode
        };
        if (isIp) {
            postData.ans = keys;
        } else {
            postData.vns = keys;
        }

        // AJAX Request for Preview & Generation
        $.ajax({
            url: '{{ route("f16_fdh_export.preview") }}',
            type: 'POST',
            data: postData,
            success: function(res) {
                $('#f16FdhLoadingOverlay').hide();
                $('#f16FdhMainContent').show();
                $('#btnExecuteF16FdhExport').prop('disabled', false);

                if (res.status === 'success') {
                    const counts = res.counts || {};
                    const headers = res.headers || {};
                    const tables = res.tables || {};
                    const rawFiles = res.raw_files || {};

                    window._f16FdhExportState.headers = headers;
                    window._f16FdhExportState.tables = tables;
                    window._f16FdhExportState.rawFiles = rawFiles;
                    window._f16FdhExportState.counts = counts;
                    window._f16FdhExportState.subfolderName = res.subfolder_name || ('F16_FDH_' + (isIp ? 'IP' : 'OP') + '_' + claimCode + '_' + Date.now());

                    $('#f16FdhSubfolderPreviewText').text('(เช่น ' + window._f16FdhExportState.subfolderName + ')');

                    // Update Tab Badges, Tables, and Raw Text (All 17 FDH Files)
                    const keysList = ['INS', 'PAT', 'OPD', 'ORF', 'ODX', 'OOP', 'IPD', 'IRF', 'IDX', 'IOP', 'CHT', 'CHA', 'AER', 'ADP', 'LVD', 'DRU', 'LABFU'];
                    keysList.forEach(function(k) {
                        const count = counts[k] || 0;
                        const badgeEl = $('#f16-fdh-badge-' + k);
                        const paneCountEl = $('#f16-fdh-pane-count-' + k);
                        const rawTextarea = $('#preview-raw-fdh-' + k);

                        badgeEl.text(count);
                        paneCountEl.text(count + ' แถว');

                        if (count > 0) {
                            badgeEl.removeClass('bg-light text-secondary border').addClass('text-white border-0').css('background-color', '#0e939a');
                            rawTextarea.val(rawFiles[k] || '');
                        } else {
                            badgeEl.removeClass('text-white border-0').addClass('bg-light text-secondary border').css('background-color', '');
                            rawTextarea.val('(ไม่มีข้อมูลสำหรับแฟ้มนี้)');
                        }

                        // Render Dynamic Table Header and Body
                        renderF16FdhTableHead(k);
                        renderF16FdhTableBody(k);
                    });

                    // Activate First Tab
                    switchFdhTab('INS');
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('เกิดข้อผิดพลาด', res.message || 'ไม่สามารถประมวลผล 17 แฟ้ม FDH ได้', 'error');
                    } else {
                        alert(res.message || 'ไม่สามารถประมวลผล 17 แฟ้ม FDH ได้');
                    }
                }
            },
            error: function(xhr) {
                $('#f16FdhLoadingOverlay').hide();
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
     * บันทึกไฟล์ .txt ทั้ง 17 แฟ้ม FDH ลงโฟลเดอร์โดยตรงผ่าน File System Access API
     */
    window.executeF16FdhDirectoryExport = async function() {
        const state = window._f16FdhExportState;
        if (!state.keys || state.keys.length === 0) {
            alert('ไม่พบรายการที่เลือก');
            return;
        }

        // Check Browser Support for Directory Picker
        if (!('showDirectoryPicker' in window)) {
            // Fallback to ZIP download
            await createAndDownloadFdhZip(state.rawFiles, state.subfolderName);
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
        const btn = $('#btnExecuteF16FdhExport');
        const originalBtnHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>กำลังบันทึกไฟล์...');
        $('#f16FdhExportProgressText').text('⏳ กำลังเขียนไฟล์ .txt ทั้ง 17 แฟ้ม FDH ลงโฟลเดอร์...');

        const writeFilesToDirectory = async function(files, subfolderName) {
            const createSubfolder = $('#f16FdhCreateSubfolderSwitch').is(':checked');
            try {
                // Determine Target Directory
                let targetDir = dirHandle;
                if (createSubfolder) {
                    targetDir = await dirHandle.getDirectoryHandle(subfolderName, { create: true });
                }

                // Write each of the 17 .txt files
                const fileKeys = ['INS', 'PAT', 'OPD', 'ORF', 'ODX', 'OOP', 'IPD', 'IRF', 'IDX', 'IOP', 'CHT', 'CHA', 'AER', 'ADP', 'LVD', 'DRU', 'LABFU'];
                let writtenFiles = 0;

                for (const k of fileKeys) {
                    const fileName = k + '.txt';
                    const fileContent = files[k] || '';
                    
                    const fileHandle = await targetDir.getFileHandle(fileName, { create: true });
                    const writable = await fileHandle.createWritable();
                    await writable.write(fileContent);
                    await writable.close();
                    writtenFiles++;
                }

                btn.prop('disabled', false).html(originalBtnHtml);
                $('#f16FdhExportProgressText').html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>ส่งออกสำเร็จครบ 17 แฟ้ม FDH</span>');

                // Show Success Notification
                const folderDisplay = createSubfolder ? subfolderName : dirHandle.name;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'ส่งออก 16/17 แฟ้ม (FDH) สำเร็จเรียบร้อย!',
                        html: `
                            <div class="text-start p-3 bg-light rounded small mt-2">
                                <div class="mb-1"><b>📁 โฟลเดอร์:</b> <code class="text-primary fs-6">${folderDisplay}</code></div>
                                <div class="mb-1"><b>📄 จำนวนไฟล์:</b> ครบ 17 แฟ้มมาตรฐาน FDH (.txt)</div>
                                <div class="mb-0"><b>👥 ผู้รับบริการ:</b> ${state.keys.length} รายการ</div>
                            </div>
                            <div class="mt-3 text-muted small">
                                เปิดหน้า <b>FDH (Financial Data Hub)</b> เพื่อนำเข้าข้อมูลและติดตามสถานะการเคลมได้ทันที
                            </div>
                        `,
                        confirmButtonText: 'รับทราบ',
                        confirmButtonColor: '#0e939a'
                    });
                } else {
                    alert('ส่งออกสำเร็จเรียบร้อยที่โฟลเดอร์: ' + folderDisplay);
                }
            } catch (writeErr) {
                btn.prop('disabled', false).html(originalBtnHtml);
                $('#f16FdhExportProgressText').text('');
                console.error('File Writing Error:', writeErr);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('เกิดข้อผิดพลาดในการเขียนไฟล์', writeErr.message, 'error');
                } else {
                    alert('เกิดข้อผิดพลาดในการเขียนไฟล์: ' + writeErr.message);
                }
            }
        };

        // If raw files are already loaded in memory, write immediately
        if (state.rawFiles && Object.keys(state.rawFiles).length > 0) {
            const subfolderName = state.subfolderName || ('F16_FDH_' + (state.isIp ? 'IP' : 'OP') + '_' + state.claimCode + '_' + Date.now());
            await writeFilesToDirectory(state.rawFiles, subfolderName);
            return;
        }

        // Otherwise, fetch from server as fallback
        $.ajax({
            url: '{{ route("f16_fdh_export.export_data") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                vns: state.isIp ? [] : state.keys,
                ans: state.isIp ? state.keys : [],
                is_ip: state.isIp ? 1 : 0,
                claim_code: state.claimCode
            },
            success: async function(res) {
                if (res.status === 'success') {
                    const files = res.files || {};
                    const subfolderName = res.subfolder_name || ('F16_FDH_' + (state.isIp ? 'IP' : 'OP') + '_' + state.claimCode + '_' + Date.now());
                    await writeFilesToDirectory(files, subfolderName);
                } else {
                    btn.prop('disabled', false).html(originalBtnHtml);
                    $('#f16FdhExportProgressText').text('');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('เกิดข้อผิดพลาด', res.message || 'ไม่สามารถส่งออกข้อมูลได้', 'error');
                    } else {
                        alert(res.message || 'ไม่สามารถส่งออกข้อมูลได้');
                    }
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalBtnHtml);
                $('#f16FdhExportProgressText').text('');
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
     * Fallback ดาวน์โหลดเป็น ZIP ถ้าเบราว์เซอร์ไม่รองรับ Directory Picker
     */
    async function createAndDownloadFdhZip(files, folderName) {
        folderName = folderName || ('FDH16_EXPORT_' + Date.now());
        if (typeof JSZip === 'undefined') {
            alert('ไม่พบ Library JSZip ในระบบ');
            return;
        }

        const zip = new JSZip();
        const folder = zip.folder(folderName);

        for (const [filename, content] of Object.entries(files)) {
            folder.file(`${filename}.txt`, content);
        }

        const blob = await zip.generateAsync({ type: 'blob' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${folderName}.zip`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'ดาวน์โหลดสำเร็จ!',
                text: `ดาวน์โหลดไฟล์ ZIP: ${folderName}.zip เรียบร้อยแล้ว`,
                confirmButtonColor: '#0e939a'
            });
        }
    }
</script>
