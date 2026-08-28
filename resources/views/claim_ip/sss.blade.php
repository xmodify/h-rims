@extends('layouts.app')

@section('content')

<style>
/* Custom pastel background for main tabs in claim_ip */
#search-tab {
    background-color: #fef2f2 !important; /* Soft pastel red/pink */
    color: #dc2626 !important;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}
#search-tab.active {
    background-color: #dc2626 !important;
    color: #fff !important;
}

#claim-tab {
    background-color: #f0fdf4 !important; /* Soft pastel green */
    color: #166534 !important;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}
#claim-tab.active {
    background-color: #166534 !important;
    color: #fff !important;
}
</style>

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ IP-SSS ประกันสังคม
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form id="form_budget_year" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกปีงบประมาณ</span>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="start_date" value="{{ $start_date }}">
                        <input type="hidden" name="end_date" value="{{ $end_date }}">
                        <select class="form-select" name="budget_year" style="width: 160px;">
                            @foreach ($budget_year_select as $row)
                              <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                {{ $row->LEAVE_YEAR_NAME }}
                              </option>
                            @endforeach
                        </select>
                        <button type="submit"  class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-graph-up me-1"></i> โหลดกราฟ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal นำเข้าและตรวจสอบผลตอบกลับ (AIPN: REP / STM) -->
    <div class="modal fade" id="importFeedbackModal" tabindex="-1" aria-labelledby="importFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title font-weight-bold" id="importFeedbackModalLabel">
                        <i class="bi bi-file-earmark-zip me-2"></i>นำเข้าและตรวจสอบผลตอบกลับ ประกันสังคม IP (AIPN: REP / STM)
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 mb-4 bg-light rounded border shadow-sm">
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center">
                            <!-- ปุ่มที่ 1: นำเข้าข้อมูล REP (สีน้ำเงิน) -->
                            <div>
                                <input type="file" id="zip_file_rep" style="display: none;" accept=".zip" multiple onchange="uploadAipnZip('rep')">
                                <button type="button" class="btn btn-primary px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_rep').click()">
                                    <i class="bi bi-file-earmark-arrow-up fs-5"></i> นำเข้าข้อมูล REP
                                </button>
                            </div>
                            <!-- ปุ่มที่ 2: นำเข้าข้อมูล STM (สีเขียว) -->
                            <div>
                                <input type="file" id="zip_file_stm" style="display: none;" accept=".zip" multiple onchange="uploadAipnZip('stm')">
                                <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_stm').click()">
                                    <i class="bi bi-file-earmark-check fs-5"></i> นำเข้าข้อมูล STM
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ส่วนแสดงตารางรายชื่อคนไข้ที่ติด C แยกตามแท็บ -->
                    <div class="mt-4 pt-3 border-top">
                        <ul class="nav nav-pills mb-3 gap-2" id="modal-pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active btn-sm fw-bold px-3 py-2 shadow-sm" id="modal-errors-tab" data-bs-toggle="pill" data-bs-target="#modal-errors-pane" type="button" role="tab">
                                    <i class="bi bi-exclamation-circle me-1"></i> ติด C (Error)
                                    <span class="badge bg-danger text-white ms-1 rounded-pill" id="modal-errors-count">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link btn-sm fw-bold px-3 py-2 shadow-sm" id="modal-warnings-tab" data-bs-toggle="pill" data-bs-target="#modal-warnings-pane" type="button" role="tab">
                                    <i class="bi bi-exclamation-triangle me-1"></i> เฉพาะที่มีรหัสเตือน (Warning)
                                    <span class="badge bg-warning text-dark ms-1 rounded-pill" id="modal-warnings-count">0</span>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="modal-pills-tabContent">
                            <!-- แท็บย่อย 1: Errors -->
                            <div class="tab-pane fade show active" id="modal-errors-pane" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="t_modal_errors" class="table table-bordered table-striped align-middle w-100" style="font-size: 0.82rem;">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th width="12%">AN</th>
                                                <th width="12%">HN</th>
                                                <th>ชื่อ-สกุลผู้ป่วย</th>
                                                <th>เลขตอบรับ / ไฟล์</th>
                                                <th width="25%">รหัสผิดพลาด (Error Code)</th>
                                                <th width="8%">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- AJAX Loaded -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- แท็บย่อย 2: Warnings -->
                            <div class="tab-pane fade" id="modal-warnings-pane" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="t_modal_warnings" class="table table-bordered table-striped align-middle w-100" style="font-size: 0.82rem;">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th width="12%">AN</th>
                                                <th width="12%">HN</th>
                                                <th>ชื่อ-สกุลผู้ป่วย</th>
                                                <th>เลขตอบรับ / ไฟล์</th>
                                                <th width="25%">รหัสเตือนภัย (Warning Code)</th>
                                                <th width="8%">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- AJAX Loaded -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AIPN Export Conditions Modal -->
    <div class="modal fade" id="aipnExportModal" tabindex="-1" aria-labelledby="aipnExportModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="aipnExportModalLabel">
                        <i class="bi bi-box-arrow-up-fill me-2"></i> เงื่อนไขการส่งออกข้อมูล AIPN
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="export_session_no" class="form-label fw-bold">เลขงวดส่ง (Session No)</label>
                        <input type="number" class="form-control form-control-lg fw-bold text-center" id="export_session_no" placeholder="ตัวอย่าง 10001" min="10000" max="99999">
                        <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle-fill me-1"></i> ระบุเลขงวดส่ง 5 หลัก เริ่มด้วย 10000 และไม่ซ้ำกับงวดเดิมที่เคยส่งไปแล้ว</div>
                    </div>
                    <div class="mb-3">
                        <label for="export_tcode" class="form-label fw-bold">ประเภทการส่งแก้ไข (TCODE)</label>
                        <select class="form-select form-select-lg fw-bold" id="export_tcode">
                            <option value="" selected>ส่งครั้งแรก / ปกติ (AIPN)</option>
                            <option value="ADD">ส่งแก้ไขตามที่ระบบตรวจรับเรียก (ADD)</option>
                            <option value="AUD">ส่งแก้ไขตามที่ auditor เรียก (AUD)</option>
                            <option value="ADJ">ส่งแก้ไขเอง ไม่ได้ถูกเรียก (ADJ)</option>
                            <option value="ADDX">ส่งแก้ไขตามที่ระบบตรวจรับเรียก - ยกเลิกธุรกรรม (ADDX)</option>
                            <option value="AUDX">ส่งแก้ไขตามที่ auditor เรียก - ยกเลิกธุรกรรม (AUDX)</option>
                            <option value="ADJX">ส่งแก้ไขเอง ไม่ได้ถูกเรียก - ยกเลิกธุรกรรม (ADJX)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-success px-4" onclick="previewAIPNExport()">
                        <i class="bi bi-eye me-1"></i> ดำเนินการและพรีวิวข้อมูล
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AIPN Export Preview Modal -->
    <div class="modal fade" id="aipnPreviewModal" tabindex="-1" aria-labelledby="aipnPreviewModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="aipnPreviewModalLabel">
                        <i class="bi bi-file-earmark-check-fill me-2"></i> ตรวจสอบความถูกต้องของข้อมูลก่อนส่งออก AIPN
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Tabs Header -->
                    <ul class="nav nav-tabs mb-3" id="aipnPreviewTab" role="tablist" style="font-size: 0.85rem;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-danger" id="aipn-prev-audit-tab" data-bs-toggle="tab" data-bs-target="#aipn-prev-audit" type="button" role="tab">
                                <i class="bi bi-shield-fill-exclamation me-1"></i> ผลตรวจสอบ (Pre-Audit)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-primary" id="aipn-prev-ipadt-tab" data-bs-toggle="tab" data-bs-target="#aipn-prev-ipadt" type="button" role="tab">
                                <i class="bi bi-person-bounding-box me-1"></i> IPADT
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-primary" id="aipn-prev-ipdx-tab" data-bs-toggle="tab" data-bs-target="#aipn-prev-ipdx" type="button" role="tab">
                                <i class="bi bi-activity me-1"></i> IPDx
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-primary" id="aipn-prev-ipop-tab" data-bs-toggle="tab" data-bs-target="#aipn-prev-ipop" type="button" role="tab">
                                <i class="bi bi-heart-pulse me-1"></i> IPOp
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-primary" id="aipn-prev-billitems-tab" data-bs-toggle="tab" data-bs-target="#aipn-prev-billitems" type="button" role="tab">
                                <i class="bi bi-list-stars me-1"></i> BillItems
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tabs Content -->
                    <div class="tab-content" id="aipnPreviewTabContent" style="font-size: 0.82rem;">
                        <!-- Pre-Audit Tab -->
                        <div class="tab-pane fade show active" id="aipn-prev-audit" role="tabpanel">
                            <div class="alert alert-warning d-flex align-items-center mb-3">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                                <div>กรุณาตรวจสอบและแก้ไขรายการที่มีสีแดง (Errors) ก่อนที่จะทำการดาวน์โหลด ZIP นำส่งระบบ สกส. เพื่อป้องกันการติด C (Denied Claim)</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle w-100" id="table-aipn-prev-audit" style="width: 100%;">
                                    <thead class="table-danger">
                                        <tr>
                                            <th class="text-center" width="5%">#</th>
                                            <th width="15%">ผู้ป่วย (HN / AN)</th>
                                            <th width="15%">ชื่อ-สกุล</th>
                                            <th>ประเด็นที่พบ (Audit Issues)</th>
                                            <th class="text-center" width="10%">ระดับความรุนแรง</th>
                                        </tr>
                                    </thead>
                                    <tbody id="aipn-prev-audit-body">
                                        <!-- Populated via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab: IPADT -->
                        <div class="tab-pane fade" id="aipn-prev-ipadt" role="tabpanel">
                            <div class="table-responsive mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-aipn-prev-ipadt">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>AN</th>
                                            <th>HN</th>
                                            <th>IDTYPE</th>
                                            <th>PIDPAT</th>
                                            <th>PATNAME</th>
                                            <th>DOB</th>
                                            <th>SEX</th>
                                            <th>ADMTYPE</th>
                                            <th>ADMSOURCE</th>
                                            <th>DTADM</th>
                                            <th>DTDISCH</th>
                                            <th>DISCHSTAT</th>
                                            <th>DISCHTYPE</th>
                                            <th>ADMWT</th>
                                        </tr>
                                    </thead>
                                    <tbody id="aipn-prev-ipadt-tbody"></tbody>
                                </table>
                            </div>
                            <div class="card border-0 bg-light">
                                <div class="card-header border-0 bg-light p-0">
                                    <button class="btn btn-sm btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#raw-aipn-xml-collapse">
                                        <span><i class="bi bi-file-earmark-code me-1"></i> ดูไฟล์ข้อความดิบ XML ตัวเต็ม (Raw XML/Text) - AN: <span id="active-preview-an" class="fw-bold text-primary">-</span></span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="raw-aipn-xml-collapse">
                                    <div class="card-body p-2 position-relative">
                                        <button class="btn btn-xs btn-secondary position-absolute end-0 top-0 m-2 btn-copy-xml" data-target="preview-aipn-xml-textarea" style="font-size: 0.7rem; z-index:10;"><i class="bi bi-clipboard"></i> Copy</button>
                                        <textarea class="form-control text-monospace bg-dark text-light p-3 small" id="preview-aipn-xml-textarea" rows="18" readonly style="font-family: Consolas, monospace; font-size:0.75rem;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: IPDx -->
                        <div class="tab-pane fade" id="aipn-prev-ipdx" role="tabpanel">
                            <div class="table-responsive mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-aipn-prev-ipdx">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>AN</th>
                                            <th>SEQ</th>
                                            <th>DXTYPE</th>
                                            <th>CODESYS</th>
                                            <th>CODE</th>
                                            <th>DIAGTERM</th>
                                            <th>DR</th>
                                            <th>DATEDIAG</th>
                                        </tr>
                                    </thead>
                                    <tbody id="aipn-prev-ipdx-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab: IPOp -->
                        <div class="tab-pane fade" id="aipn-prev-ipop" role="tabpanel">
                            <div class="table-responsive mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-aipn-prev-ipop">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>AN</th>
                                            <th>SEQ</th>
                                            <th>CODESYS</th>
                                            <th>CODE</th>
                                            <th>PROCTERM</th>
                                            <th>DR</th>
                                            <th>DATEIN</th>
                                            <th>DATEOUT</th>
                                            <th>LOCATION</th>
                                        </tr>
                                    </thead>
                                    <tbody id="aipn-prev-ipop-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab: BillItems -->
                        <div class="tab-pane fade" id="aipn-prev-billitems" role="tabpanel">
                            <div class="table-responsive mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-aipn-prev-billitems">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>AN</th>
                                            <th>SEQ</th>
                                            <th>SERVDATE</th>
                                            <th>BILLGR</th>
                                            <th>LCCODE</th>
                                            <th>DESCRIPT</th>
                                            <th class="text-center">QTY</th>
                                            <th class="text-end">UNITPRICE</th>
                                            <th class="text-end">CHARGEAMT</th>
                                            <th class="text-end">DISCOUNT</th>
                                            <th>CLAIMSYS</th>
                                            <th>BILLGRCS</th>
                                            <th>CSCODE</th>
                                            <th>CODESYS</th>
                                            <th>STDCODE</th>
                                            <th>CLAIMCAT</th>
                                            <th class="text-end">CLAIMUP</th>
                                            <th class="text-end">CLAIMAMT</th>
                                        </tr>
                                    </thead>
                                    <tbody id="aipn-prev-billitems-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" onclick="$('#aipnPreviewModal').modal('hide'); $('#aipnExportModal').modal('show');">
                        <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                    </button>
                    <button type="button" class="btn btn-success px-4" id="btn-download-aipn" onclick="downloadAIPNZip()">
                        <i class="bi bi-download me-1"></i> ยืนยันการดาวน์โหลด AIPN (.zip)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inpatient Details Modal -->
    <div class="modal fade" id="anDetailsModal" tabindex="-1" aria-labelledby="anDetailsModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="anDetailsModalLabel">
                        <i class="bi bi-file-earmark-medical-fill me-2"></i>รายละเอียดข้อมูลการรักษาผู้ป่วยใน (AN Details)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="anDetailsModalBody">
                    <!-- Dynamic Loader -->
                </div>
                <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                    <div id="anDetailsModalFooterSummary" class="text-start d-flex gap-3 text-muted small" style="font-size: 11.5px;">
                        <!-- Will be populated dynamically in JS -->
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>ปิดหน้าต่าง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div id="data-container">
        <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
            <div class="card-body py-5 text-center">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mt-3 fw-bold text-secondary">กำลังประมวลผลข้อมูลการเรียกเก็บและชดเชย...</h5>
                <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาย้อนหลังทั้งปีงบประมาณและเชื่อมสถานะส่งเคลม อาจใช้เวลา 5-15 วินาที โปรดรอสักครู่</p>
            </div>
        </div>
    </div>

    <!-- Modal ศูนย์รวมการนำเข้าข้อมูล (Import Hub) -->
    <x-import_hub_modal 
        :rep-url="url('import/rep_sss')" 
        :stm-url="url('import/stm_sss')" 
        :has-edc="false" 
        claim-title="สิทธิ IP-SSS (ประกันสังคม)" 
    />

    <!-- Modal Extension Info -->
    <x-extension_info_modal />

@endsection

@push('scripts')
  <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>
  <script>
    window.currentChartData = null;
    window.patientItems = [];

    window.uploadAipnZip = function(type) {
        const inputId = type === 'rep' ? 'zip_file_rep' : 'zip_file_stm';
        const input = document.getElementById(inputId);
        if (!input || input.files.length === 0) return;

        const files = Array.from(input.files);
        const uploadUrl = type === 'rep' ? "{{ url('claim_ip/rep_sss_aipn_import') }}" : "{{ url('claim_ip/stm_sss_aipn_import') }}";

        let currentIdx = 0;
        let successCount = 0;
        let failCount = 0;
        let summaryHtml = '';

        function processNextFile() {
            if (currentIdx >= files.length) {
                Swal.fire({
                    icon: successCount > 0 ? 'success' : 'error',
                    title: successCount > 0 ? 'นำเข้าสำเร็จ!' : 'นำเข้าไม่สำเร็จ',
                    html: `<b>ประมวลผลเสร็จสิ้นทั้งหมด ${files.length} ไฟล์</b><br>` +
                          `<span class="text-success">สำเร็จ: ${successCount} ไฟล์</span> | ` +
                          `<span class="text-danger">ล้มเหลว: ${failCount} ไฟล์</span><br><br>` +
                          `<div class="text-start small p-2 bg-light border rounded" style="max-height: 150px; overflow-y: auto;">${summaryHtml}</div>`
                }).then(() => {
                    input.value = '';
                    loadDashboard({
                        budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        skip_chart: 1
                    });
                    loadModalErrors();
                });
                return;
            }

            const currentFile = files[currentIdx];
            const percent = Math.round((currentIdx / files.length) * 100);

            if (currentIdx === 0) {
                Swal.fire({
                    title: 'กำลังอัปโหลดและประมวลผลไฟล์...',
                    html: `<b>ไฟล์ที่ ${currentIdx + 1} จากทั้งหมด ${files.length} (${percent}%)</b><br>` +
                          `<span class="text-muted small" style="word-break: break-all;">กำลังดำเนินการ: ${currentFile.name}</span><br><br>` +
                          `<div class="progress" style="height: 10px; background-color: #e9ecef; border-radius: 5px; overflow: hidden;">` +
                          `  <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: ${percent}%; height: 100%;"></div>` +
                          `</div>`,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            } else {
                Swal.update({
                    html: `<b>ไฟล์ที่ ${currentIdx + 1} จากทั้งหมด ${files.length} (${percent}%)</b><br>` +
                          `<span class="text-muted small" style="word-break: break-all;">กำลังดำเนินการ: ${currentFile.name}</span><br><br>` +
                          `<div class="progress" style="height: 10px; background-color: #e9ecef; border-radius: 5px; overflow: hidden;">` +
                          `  <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: ${percent}%; height: 100%;"></div>` +
                          `</div>`
                });
            }

            const formData = new FormData();
            formData.append('_token', "{{ csrf_token() }}");
            formData.append('zip_file', currentFile);

            $.ajax({
                url: uploadUrl,
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        successCount++;
                        summaryHtml += `<span class="text-success">✔ [${currentFile.name}]</span> ${res.message || 'สำเร็จ'}<br>`;
                        currentIdx++;
                        processNextFile();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'นำเข้าไม่สำเร็จ',
                            html: `<b>พบข้อผิดพลาดที่ไฟล์: ${currentFile.name}</b><br>` +
                                  `<span class="text-danger">${res.message || 'เลือกประเภทไฟล์ไม่ถูกต้อง'}</span><br><br>` +
                                  `ระบบได้หยุดการทำงานเพื่อไม่ให้นำเข้าไฟล์ที่เหลือ`
                        });
                        input.value = '';
                    }
                },
                error: function(xhr) {
                    const errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'ไม่สามารถสื่อสารกับเซิร์ฟเวอร์ได้';
                    Swal.fire({
                        icon: 'error',
                        title: 'นำเข้าไม่สำเร็จ',
                        html: `<b>พบข้อผิดพลาดที่ไฟล์: ${currentFile.name}</b><br>` +
                              `<span class="text-danger">${errMsg}</span><br><br>` +
                              `ระบบได้หยุดการทำงานเพื่อไม่ให้นำเข้าไฟล์ที่เหลือ`
                    });
                    input.value = '';
                }
            });
        }

        processNextFile();
    };

    // Global DrawChart function
    function drawChart(labels, claim_price, claim_sent_price, receive_total) {
      const canvas = document.querySelector('#sum_month');
      if (!canvas) return;

      // Destroy old chart instance if exists
      const existingChart = Chart.getChart(canvas);
      if (existingChart) {
          existingChart.destroy();
      }

      new Chart(canvas, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'เรียกเก็บ',
              data: claim_price,
              backgroundColor: 'rgba(185, 28, 28, 0.75)',
              borderColor: 'rgb(185, 28, 28)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'ส่งเคลม',
              data: claim_sent_price,
              backgroundColor: 'rgba(234, 179, 8, 0.6)',
              borderColor: 'rgb(234, 179, 8)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'ชดเชย',
              data: receive_total,
              backgroundColor: 'rgba(16, 185, 129, 0.6)',
              borderColor: 'rgb(16, 185, 129)',
              borderWidth: 1,
              borderRadius: 4
            }
          ]
        }, 
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              align: 'center',
              labels: {
                usePointStyle: true,
                boxWidth: 6
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': ' + context.formattedValue + ' บาท';
                }
              }
            },
            datalabels: {
              anchor: 'end',
              align: 'top',
              color: '#000',
              font: {
                weight: 'bold',
                size: 10
              },
              formatter: (value) => value > 0 ? value.toLocaleString() : ''
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grace: '20%',
              ticks: {
                callback: function(value) {
                  return value.toLocaleString();
                }
              }
            }
          }
        },
        plugins: [ChartDataLabels] 
      });
    }

    function fetchData() {
        // Fallback for legacy handlers
    }

    // Individual FDH Check
    function checkFdh(hn, an) {
        Swal.fire({
            title: 'กำลังตรวจสอบสถานะ...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: "{{ url('/api/fdh/check-claim-indiv') }}",
            type: "POST",
            data: {
                hn: hn,
                an: an,
                _token: "{{ csrf_token() }}"
            },
            success: function (res) {
                if (res.status === 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ตรวจสอบสำเร็จ',
                        text: 'พบข้อมูลในระบบ FDH',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                         loadDashboard({
                             budget_year: $('#form_budget_year select[name="budget_year"]').val(),
                             start_date: $('#start_date').val(),
                             end_date: $('#end_date').val(),
                             skip_chart: 1
                         });
                    });
                    return;
                }
                if (res.status === 404 || res.status === 500) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไม่พบข้อมูลในระบบ FDH',
                        text: res.body?.message_th ?? "ไม่มีรายการนี้ส่ง"
                    });
                    return;
                }
                if (res.status === 400) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้'
                    });
                    return;
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'การเชื่อมต่อล้มเหลว',
                    text: 'ไม่สามารถเรียก API ได้ (Network Error)'
                });
            }
        });
    }

    // FDH Bulk Check
    async function checkFdhBulk(e) {
        e.preventDefault();
        const items = window.patientItems || [];

        if (!items || items.length === 0) {
            Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
            return;
        }

        await runFdhBulkCheck(items, "{{ csrf_token() }}", "{{ url('/api/fdh/check-chunk') }}", function() {
            loadDashboard({
                budget_year: $('#form_budget_year select[name="budget_year"]').val(),
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                skip_chart: 1
            });
        });
    }

    // AJAX Dashboard Loader
    function loadDashboard(dataParams) {
      const container = document.getElementById('data-container');
      if (!container) return;

      if (dataParams.skip_chart) {
          const tabContent = document.getElementById('myTabContent');
          if (tabContent) {
              tabContent.innerHTML = `
                  <div class="text-center py-5">
                      <div class="d-flex justify-content-center mb-3">
                          <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                      </div>
                      <h6 class="fw-bold text-secondary">กำลังอัปเดตตารางข้อมูลคนไข้...</h6>
                  </div>
              `;
          }
      } else {
          container.innerHTML = `
              <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                  <div class="card-body py-5 text-center">
                      <div class="d-flex justify-content-center mb-3">
                          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                              <span class="visually-hidden">Loading...</span>
                          </div>
                      </div>
                      <h5 class="fw-bold text-secondary">กำลังประมวลผลข้อมูลการเรียกเก็บและชดเชย...</h5>
                      <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาย้อนหลังทั้งปีงบประมาณและเชื่อมสถานะส่งเคลม อาจใช้เวลา 5-15 วินาที โปรดรอสักครู่</p>
                  </div>
              </div>
          `;
      }

      $.ajax({
          url: "{{ url('claim_ip/sss') }}",
          type: "POST",
          data: $.extend({ _token: "{{ csrf_token() }}" }, dataParams)
      })
      .done(function(res) {
          if (res.success) {
              if (dataParams.skip_chart) {
                  const tempDiv = $('<div>').html(res.table_html);
                  $('#data-container .card-header').replaceWith(tempDiv.find('.card-header'));
                  $('#data-container .card-body').replaceWith(tempDiv.find('.card-body'));
              } else {
                  container.innerHTML = res.table_html;
              }

              // Re-initialize Datepicker Thai
              $('.datepicker_th').datepicker({
                  format: 'd M yyyy',
                  todayBtn: "linked",
                  todayHighlight: true,
                  autoclose: true,
                  language: 'th-th',
                  thaiyear: true,
                  zIndexOffset: 1050
              });

              var start_date_val = $('#start_date').val();
              var end_date_val = $('#end_date').val();
              if(start_date_val) {
                  $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
              }
              if(end_date_val) {
                  $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
              }

              // Bind Datepicker change
              $('.datepicker_th').on('changeDate', function(e) {
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

              // Re-initialize Datatables (support both standard search/claim and stp/others)
              var dt_search = $('#t_search').DataTable({
                  autoWidth: false,
                  dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                  buttons: [
                      {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้ป่วย รอส่ง Claim วันที่ ' + start_date_val + ' ถึง ' + end_date_val
                      }
                  ],
                  language: {
                      search: "ค้นหา:",
                      lengthMenu: "แสดง _MENU_ รายการ",
                      info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                      paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                  }
              });

              var dt_claim = $('#t_claim').DataTable({
                  autoWidth: false,
                  dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                  buttons: [
                      {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้ป่วย ส่ง Claim แล้ว วันที่ ' + start_date_val + ' ถึง ' + end_date_val
                      }
                  ],
                  language: {
                      search: "ค้นหา:",
                      lengthMenu: "แสดง _MENU_ รายการ",
                      info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                      paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                  }
              });

              var dt_warning = $('#t_warning').DataTable({
                  autoWidth: false,
                  dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                  buttons: [
                      {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้ป่วย ส่ง Claim แล้ว (มีรหัสเตือน) วันที่ ' + start_date_val + ' ถึง ' + end_date_val
                      }
                  ],
                  language: {
                      search: "ค้นหา:",
                      lengthMenu: "แสดง _MENU_ รายการ",
                      info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                      paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                  }
              });

              var dt_visits = $('#t_visits').DataTable({
                  autoWidth: false,
                  dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                  buttons: [
                      {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้มารับบริการ วันที่ ' + start_date_val + ' ถึง ' + end_date_val
                      }
                  ],
                  language: {
                      search: "ค้นหา:",
                      lengthMenu: "แสดง _MENU_ รายการ",
                      info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                      paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                  }
              });

              // Adjust columns on tab change
              $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill', function () {
                  if (typeof dt_search !== 'undefined' && dt_search) dt_search.columns.adjust().draw(false);
                  if (typeof dt_claim !== 'undefined' && dt_claim) dt_claim.columns.adjust().draw(false);
                  if (typeof dt_warning !== 'undefined' && dt_warning) dt_warning.columns.adjust().draw(false);
                  if (typeof dt_visits !== 'undefined' && dt_visits) dt_visits.columns.adjust().draw(false);
              });

              var activeTab = localStorage.getItem('active_tab');
              if (activeTab) {
                  var tabEl = document.querySelector(`button[data-bs-target="${activeTab}"]`);
                  if (tabEl) {
                      tabEl.click();
                  }
                  localStorage.removeItem('active_tab');
              }

                            // Update global chart data
              if (res.chart_data && (res.chart_data.month && res.chart_data.month.length > 0 || !window.currentChartData)) {
                  window.currentChartData = res.chart_data;
              }

              // Draw chart (even if empty)
              if (!dataParams.skip_chart && window.currentChartData) {
                  drawChart(
                      window.currentChartData.month || [],
                      window.currentChartData.claim_price || [],
                      window.currentChartData.claim_sent_price || [],
                      window.currentChartData.receive_total || []
                  );
              }

              // Cache patient items list for FDH bulk checker
              window.patientItems = res.patient_items || [];
          } else {
              container.innerHTML = '<div class="alert alert-danger text-center">ไม่สามารถโหลดข้อมูลได้: ' + (res.message || 'โครงสร้างข้อมูลไม่ถูกต้อง') + '</div>';
          }
      })
      .fail(function() {
          container.innerHTML = '<div class="alert alert-danger text-center">ไม่สามารถโหลดข้อมูลได้</div>';
      });
    }


    // App Initialization & Form binding
    $(document).ready(function () {
      // First load: full dashboard
      loadDashboard({
          budget_year: "{{ $budget_year }}",
          start_date: "{{ $start_date }}",
          end_date: "{{ $end_date }}"
      });

      // Intercept Budget Year Form submit
      $(document).on('submit', '#form_budget_year', function(e) {
          e.preventDefault();
          loadDashboard({
              budget_year: $(this).find('select[name="budget_year"]').val()
          });
      });

      // Intercept Indiv Date Form submit
      $(document).on('submit', '#form_indiv', function(e) {
          e.preventDefault();
          loadDashboard({
              budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
              start_date: $(this).find('#start_date').val(),
              end_date: $(this).find('#end_date').val(),
              skip_chart: 1
          });
      });

      // Load C-Code errors inside modal when it is opened
      $('#importFeedbackModal').on('shown.bs.modal', function () {
          loadModalErrors();
      });
    });

    // Show REP errors and warnings details via Swal.fire
    window.showRepDetails = function(an) {
        Swal.fire({
            title: 'กำลังโหลดข้อมูลผลตอบกลับ...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.get("{{ url('claim_ip/sss_detail') }}", { an: an })
            .done(function(data) {
                Swal.close();
                const feedbacks = data.rep_feedbacks || [];
                if (feedbacks.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'ไม่มีข้อมูลข้อผิดพลาด',
                        text: 'ไม่พบประวัติข้อผิดพลาดตอบกลับสำหรับรายการนี้'
                    });
                    return;
                }

                let html = '<div class="text-start" style="font-size:0.85rem; max-height:400px; overflow-y:auto;">';
                html += '<table class="table table-sm table-bordered align-middle">';
                html += '<thead><tr class="table-light"><th>รหัส</th><th>ประเภท</th><th>รายละเอียด</th></tr></thead>';
                html += '<tbody>';
                feedbacks.forEach(f => {
                    const badgeColor = f.type === 'error' ? 'danger' : 'warning';
                    const typeText = f.type === 'error' ? 'ข้อผิดพลาด (Error)' : 'ข้อแนะนำ (Warning)';
                    html += `<tr>
                        <td class="fw-bold text-${badgeColor}">${f.code}</td>
                        <td><span class="badge bg-${badgeColor}">${typeText}</span></td>
                        <td>${f.desc}</td>
                    </tr>`;
                });
                html += '</tbody></table></div>';

                Swal.fire({
                    title: `ผลตอบกลับ REP (AN: ${an})`,
                    html: html,
                    width: '650px',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#3085d6'
                });
            })
            .fail(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'ดึงข้อมูลล้มเหลว',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
                });
            });
    }

    // Load C-Code errors inside modal
    window.loadModalErrors = function() {
        if ($.fn.DataTable.isDataTable('#t_modal_errors')) {
            $('#t_modal_errors').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#t_modal_warnings')) {
            $('#t_modal_warnings').DataTable().destroy();
        }

        const loadingHtml = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm text-primary me-2"></span>กำลังโหลดข้อมูล...
                </td>
            </tr>
        `;
        $('#t_modal_errors tbody').html(loadingHtml);
        $('#t_modal_warnings tbody').html(loadingHtml);

        $.get("{{ url('claim_ip/sss_rep_errors') }}")
            .done(function(res) {
                if (res.success) {
                    let errorsHtml = '';
                    let warningsHtml = '';
                    let errorsCount = 0;
                    let warningsCount = 0;

                    res.data.forEach(row => {
                        let rowAllBadgesHtml = '';
                        let rowWarningsHtml = '';
                        let hasError = false;
                        let hasWarning = false;

                        if (row.error_codes) {
                            let codes = row.error_codes.split(',');
                            codes.forEach(c => {
                                let cleanC = c.split(':')[0].trim();
                                let isWarn = cleanC.toUpperCase().startsWith('W') || cleanC.startsWith('8');
                                let badgeClass = isWarn ? 'bg-warning text-dark' : 'bg-danger text-white';
                                let badgeHtml = `<span class="badge ${badgeClass} me-1 pointer p-2" onclick="showRepDetails('${row.an}')" style="cursor:pointer; font-size: 0.75rem;" title="คลิกดูรายละเอียด">${c}</span>`;
                                
                                rowAllBadgesHtml += badgeHtml;
                                if (isWarn) {
                                    hasWarning = true;
                                    rowWarningsHtml += badgeHtml;
                                } else {
                                    hasError = true;
                                }
                            });
                        }

                        // Build table row HTML helper
                        let repDisplay = row.rep_file || '-';
                        if (row.repno) {
                            let dateText = row.rep_date ? row.rep_date : '';
                            // simple formatting if date is YYYY-MM-DD
                            if (dateText.includes('-')) {
                                let parts = dateText.split('-');
                                if (parts.length === 3) {
                                    let thYear = parseInt(parts[0], 10) + 543;
                                    dateText = `${parts[2]}/${parts[1]}/${thYear}`;
                                }
                            }
                            let repDateStr = dateText ? `<br><span class="badge bg-light text-dark border mt-1" style="font-size:0.68rem;"><i class="bi bi-calendar-event me-1"></i>${dateText}</span>` : '';
                            repDisplay = `<div class="fw-bold text-dark">#${row.repno}</div><div class="small text-muted text-truncate" style="max-width:180px; font-size:0.75rem;" title="${row.rep_file}">${repDisplay}</div>${repDateStr}`;
                        } else {
                            repDisplay = `<div class="small text-muted text-truncate" style="max-width:180px;" title="${row.rep_file}">${repDisplay}</div>`;
                        }

                        const makeRow = (badges) => `
                            <tr>
                                <td class="text-center fw-bold">${row.an}</td>
                                <td class="text-center">${row.hn}</td>
                                <td class="fw-bold">${row.ptname || '-'}</td>
                                <td class="align-middle">${repDisplay}</td>
                                <td class="text-center">${badges}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary px-2 py-1" onclick="showRepDetails('${row.an}')" title="ดูคำอธิบาย">
                                        <i class="bi bi-search me-1"></i>ดูรายละเอียด
                                    </button>
                                </td>
                            </tr>
                        `;

                        // Tab 1: Show only cases with tcode = C (Reject)
                        if (row.tcode === 'C') {
                            errorsHtml += makeRow(rowAllBadgesHtml);
                            errorsCount++;
                        }

                        // Tab 2: Show cases with warning codes but no C status
                        if (row.tcode !== 'C') {
                            warningsHtml += makeRow(rowAllBadgesHtml);
                            warningsCount++;
                        }
                    });

                    // Update UI counters and table content
                    $('#modal-errors-count').text(errorsCount);
                    $('#modal-warnings-count').text(warningsCount);

                    $('#t_modal_errors tbody').html(errorsHtml || '<tr><td colspan="6" class="text-center text-muted py-4">ไม่มีข้อมูลผู้ป่วยที่ติด C (Error)</td></tr>');
                    $('#t_modal_warnings tbody').html(warningsHtml || '<tr><td colspan="6" class="text-center text-muted py-4">ไม่มีข้อมูลผู้ป่วยที่ติด C (Warning)</td></tr>');

                    // Initialize DataTables
                    const dtConfig = {
                        destroy: true,
                        autoWidth: false,
                        pageLength: 5,
                        lengthMenu: [5, 10, 25, 50],
                        language: {
                            search: "ค้นหา AN/HN/ชื่อ:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                        }
                    };

                    let dt_m_errors, dt_m_warnings;
                    if (errorsCount > 0) {
                        dt_m_errors = $('#t_modal_errors').DataTable(dtConfig);
                    }
                    if (warningsCount > 0) {
                        dt_m_warnings = $('#t_modal_warnings').DataTable(dtConfig);
                    }

                    // Resize tables on modal pill tab changes to prevent squashed columns
                    $('button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill', function () {
                        if (dt_m_errors) dt_m_errors.columns.adjust().draw(false);
                        if (dt_m_warnings) dt_m_warnings.columns.adjust().draw(false);
                    });
                }
            })
            .fail(function() {
                $('#t_modal_errors tbody').html('<tr><td colspan="6" class="text-center text-danger py-4">โหลดข้อมูลล้มเหลว</td></tr>');
                $('#t_modal_warnings tbody').html('<tr><td colspan="6" class="text-center text-danger py-4">โหลดข้อมูลล้มเหลว</td></tr>');
            });
    }

    // Select all checkboxes logic
    $(document).on('change', '.select_all_claims', function() {
        const checked = this.checked;
        $('.tab-pane.active .claim-select-check').prop('checked', checked);
    });

    let selectedAnsForExport = [];
    window.exportSelectedAIPN = function() {
        selectedAnsForExport = [];
        $('.tab-pane.active .claim-select-check:checked').each(function() {
            selectedAnsForExport.push(this.value);
        });

        if (selectedAnsForExport.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกรายการ',
                text: 'กรุณาติ๊กเลือกผู้ป่วยอย่างน้อย 1 รายการก่อนทำการส่งออก AIPN'
            });
            return;
        }

        // Randomize 5-digit Session No starting with 10000
        const minVal = 10000;
        const maxVal = 99999;
        const randomSession = Math.floor(Math.random() * (maxVal - minVal + 1)) + minVal;
        document.getElementById('export_session_no').value = randomSession;

        $('#aipnExportModal').modal('show');
    };

    window.previewAIPNExport = function() {
        const sessionNo = document.getElementById('export_session_no').value;

        if (!sessionNo || sessionNo < 10000 || sessionNo > 99999) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอกเลขงวดส่ง 5 หลัก (10000 - 99999)' });
            return;
        }

        Swal.fire({
            title: 'กำลังเตรียมและประมวลผลข้อมูลส่งออก...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // Destroy existing DataTables if initialized to prevent errors
        if ($.fn.DataTable.isDataTable('#table-aipn-prev-audit')) { $('#table-aipn-prev-audit').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-aipn-prev-ipadt')) { $('#table-aipn-prev-ipadt').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-aipn-prev-ipdx')) { $('#table-aipn-prev-ipdx').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-aipn-prev-ipop')) { $('#table-aipn-prev-ipop').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-aipn-prev-billitems')) { $('#table-aipn-prev-billitems').DataTable().destroy(); }

        $.ajax({
            url: "{{ url('claim_ip/sss_export_preview_aipn') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                ans: selectedAnsForExport,
                session_no: sessionNo,
                tcode: document.getElementById('export_tcode').value,
                care_as: 'AUTO'
            },
            success: function(res) {
                Swal.close();
                if (res.success) {
                    $('#aipnExportModal').modal('hide');
                    $('#aipnPreviewModal').modal('show');

                    // 1. Populate Pre-Audit
                    let auditHtml = '';
                    let errorCount = 0;
                    res.audit_results.forEach((item, idx) => {
                        const levelBadge = item.level === 'error' ? '<span class="badge bg-danger text-white">Error</span>' : '<span class="badge bg-warning text-dark">Warning</span>';
                        auditHtml += `<tr>
                            <td class="text-center">${idx + 1}</td>
                            <td class="text-center fw-bold">${item.an} <br><small class="text-muted">${item.hn}</small></td>
                            <td>${item.ptname}</td>
                            <td class="text-danger fw-bold">${item.message}</td>
                            <td class="text-center">${levelBadge}</td>
                        </tr>`;
                        if (item.level === 'error') errorCount++;
                    });
                    $('#aipn-prev-audit-body').html(auditHtml);

                     // 2. Populate IPADT
                     let ipadtHtml = '';
                     res.ipadt_data.forEach(row => {
                         ipadtHtml += `<tr>
                             <td><a href="javascript:void(0)" class="fw-bold text-primary btn-select-preview-an" data-an="${row.an}">${row.an}</a></td>
                             <td>${row.hn}</td>
                             <td>${row.idtype}</td>
                             <td>${row.pidpat}</td>
                             <td>${row.ptname}</td>
                             <td>${row.dob}</td>
                             <td>${row.sex}</td>
                             <td>${row.admtype}</td>
                             <td>${row.admsource}</td>
                             <td>${row.dtadm}</td>
                             <td>${row.dtdisch}</td>
                             <td>${row.dischstat}</td>
                             <td>${row.dischtype}</td>
                             <td>${row.admwt}</td>
                         </tr>`;
                     });
                     $('#aipn-prev-ipadt-tbody').html(ipadtHtml);

                     // 3. Populate IPDx
                     let ipdxHtml = '';
                     res.ipdx_data.forEach(row => {
                         ipdxHtml += `<tr>
                             <td>${row.an}</td>
                             <td>${row.seq}</td>
                             <td>${row.dxtype}</td>
                             <td>${row.codesys}</td>
                             <td>${row.code}</td>
                             <td>${row.diagterm}</td>
                             <td>${row.dr}</td>
                             <td>${row.datediag}</td>
                         </tr>`;
                     });
                     $('#aipn-prev-ipdx-tbody').html(ipdxHtml);

                     // 4. Populate IPOp
                     let ipopHtml = '';
                     res.ipop_data.forEach(row => {
                         ipopHtml += `<tr>
                             <td>${row.an}</td>
                             <td>${row.seq}</td>
                             <td>${row.codesys}</td>
                             <td>${row.code}</td>
                             <td>${row.procterm}</td>
                             <td>${row.dr}</td>
                             <td>${row.datein}</td>
                             <td>${row.dateout}</td>
                             <td>${row.location}</td>
                         </tr>`;
                     });
                     $('#aipn-prev-ipop-tbody').html(ipopHtml);

                     // 5. Populate BillItems
                     let billitemsHtml = '';
                     res.billitems_data.forEach(row => {
                         billitemsHtml += `<tr>
                             <td>${row.an}</td>
                             <td>${row.seq}</td>
                             <td>${row.servdate}</td>
                             <td>${row.billgr}</td>
                             <td>${row.lccode}</td>
                             <td>${row.descript}</td>
                             <td class="text-center">${row.qty}</td>
                             <td class="text-end">${row.unitprice}</td>
                             <td class="text-end">${row.chargeamt}</td>
                             <td class="text-end">${row.discount}</td>
                             <td>${row.claimsys}</td>
                             <td>${row.billgrcs}</td>
                             <td>${row.cscode}</td>
                             <td>${row.codesys}</td>
                             <td>${row.stdcode}</td>
                             <td>${row.claimcat}</td>
                             <td class="text-end">${row.claimup}</td>
                             <td class="text-end">${row.claimamt}</td>
                         </tr>`;
                     });
                     $('#aipn-prev-billitems-tbody').html(billitemsHtml);

                     // 6. Store XML Files globally and select the first AN
                     window.currentPreviewXmlFiles = res.xml_files || {};
                     if (res.ipadt_data && res.ipadt_data.length > 0) {
                         window.selectPreviewAn(res.ipadt_data[0].an);
                     }

                    // Initialize local Datatables
                    const dtConfigPrev = {
                        destroy: true,
                        pageLength: 5,
                        lengthMenu: [5, 10, 25, 50],
                        language: {
                            search: "ค้นหาข้อมูล:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" },
                            emptyTable: "ไม่มีข้อมูลในตาราง",
                            zeroRecords: "ไม่พบข้อมูลที่ค้นหา"
                        }
                    };

                    if (res.audit_results.length > 0) {
                        $('#table-aipn-prev-audit').DataTable(dtConfigPrev);
                    } else {
                        $('#aipn-prev-audit-body').html('<tr><td colspan="5" class="text-center text-success py-4"><i class="bi bi-check-circle-fill me-1"></i>ผ่านการตรวจสอบความสมบูรณ์ ไม่มีข้อผิดพลาด</td></tr>');
                    }

                    $('#table-aipn-prev-ipadt').DataTable(dtConfigPrev);
                    $('#table-aipn-prev-ipdx').DataTable(dtConfigPrev);
                    $('#table-aipn-prev-ipop').DataTable(dtConfigPrev);
                    $('#table-aipn-prev-billitems').DataTable(dtConfigPrev);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาดในการประมวลผล',
                        text: res.message || 'กรุณาลองใหม่อีกครั้ง'
                    });
                }
            },
            error: function(xhr) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'เชื่อมต่อเซิร์ฟเวอร์ล้มเหลว',
                    text: xhr.responseJSON?.message || 'ไม่สามารถดึงข้อมูลพรีวิวได้'
                });
            }
        });
    };

    window.downloadAIPNZip = function() {
        const sessionNo = document.getElementById('export_session_no').value;
        
        // Dynamic form submit for ZIP download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ url('claim_ip/sss_export_aipn') }}";
        form.style.display = 'none';

        const tokenInput = document.createElement('input');
        tokenInput.name = '_token';
        tokenInput.value = "{{ csrf_token() }}";
        form.appendChild(tokenInput);

        const sessionInput = document.createElement('input');
        sessionInput.name = 'session_no';
        sessionInput.value = sessionNo;
        form.appendChild(sessionInput);

        const tcodeInput = document.createElement('input');
        tcodeInput.name = 'tcode';
        tcodeInput.value = document.getElementById('export_tcode').value;
        form.appendChild(tcodeInput);

        const careAsInput = document.createElement('input');
        careAsInput.name = 'care_as';
        careAsInput.value = 'AUTO';
        form.appendChild(careAsInput);

        selectedAnsForExport.forEach(an => {
            const anInput = document.createElement('input');
            anInput.name = 'ans[]';
            anInput.value = an;
            form.appendChild(anInput);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        $('#aipnPreviewModal').modal('hide');
        
        // Show success alert after brief timeout
        setTimeout(function() {
            Swal.fire({
                icon: 'success',
                title: 'เริ่มการดาวน์โหลด ZIP',
                text: 'ระบบกำลังสร้างและบีบอัดไฟล์ AIPN ให้กรุณาเซฟและตรวจสอบไฟล์',
                timer: 3000,
                showConfirmButton: false
            });
        }, 1000);
    };

    window.showAnDetails = function(an) {
        const body = document.getElementById('anDetailsModalBody');
        if (!body) return;
        body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลดข้อมูลผู้ป่วยใน...</div>';
        
        // Clear footer summary immediately
        const footerSummary = document.getElementById('anDetailsModalFooterSummary');
        if (footerSummary) {
            footerSummary.innerHTML = '';
        }

        $('#anDetailsModal').modal('show');

        $.get("{{ url('claim_ip/sss_an_details') }}", { an: an })
            .done(function(res) {
                if (!res.success) {
                    body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${res.message}</div>`;
                    return;
                }
                const adm = res.admission;
                const items = res.items;
                const errors = res.errors || [];
                const warnings = res.warnings || [];

                // Determine validation status alert banner
                let statusHtml = '';
                if (errors.length > 0) {
                    statusHtml = `
                    <div class="col-12 mb-2">
                      <div class="alert alert-danger py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #dc2626 !important;">
                        <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.1rem; color: #dc2626;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ไม่ผ่านเกณฑ์ส่งออก (มีข้อผิดพลาดที่ต้องแก้ไขเพื่อป้องกันการติด C)</div>
                          <ul class="mb-0 ps-3 text-danger">
                            ${errors.map(err => `<li>${err}</li>`).join('')}
                          </ul>
                        </div>
                      </div>
                    </div>`;
                } else if (warnings.length > 0) {
                    statusHtml = `
                    <div class="col-12 mb-2">
                      <div class="alert alert-warning py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #fffbeb; color: #92400e; border-left: 5px solid #d97706 !important;">
                        <i class="bi bi-exclamation-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #d97706;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลทางคลินิกผ่านเกณฑ์ แต่มีข้อแนะนำความพร้อมการนำส่ง</div>
                          <ul class="mb-0 ps-3 text-warning" style="color: #92400e !important;">
                            ${warnings.map(warn => `<li>${warn}</li>`).join('')}
                          </ul>
                        </div>
                      </div>
                    </div>`;
                } else {
                    statusHtml = `
                    <div class="col-12 mb-2">
                      <div class="alert alert-success py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #f0fdf4; color: #166534; border-left: 5px solid #16a34a !important;">
                        <i class="bi bi-check-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #16a34a;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลพร้อมส่งออก (ผ่านเกณฑ์และโครงสร้างสมบูรณ์)</div>
                          <div class="text-muted small">ข้อมูลการจำหน่ายผู้ป่วยในถูกต้องและผ่านเกณฑ์ Pre-Audit ครบถ้วน</div>
                        </div>
                      </div>
                    </div>`;
                }

                let drugRows = '';
                let serviceRows = '';
                let totalCharge = 0;
                let totalDiscount = 0;
                
                let drugsCount = 0;
                let servicesCount = 0;

                items.forEach((item, i) => {
                    const price = parseFloat(item.sum_price) || (parseFloat(item.qty) * parseFloat(item.unitprice));
                    totalCharge += price;
                    totalDiscount += parseFloat(item.discount) || 0;
                    
                    const rowHtml = `<tr>
                        <td class="text-center small"><span class="badge bg-secondary">${item.income}</span></td>
                        <td class="text-center small fw-bold">${item.icode}</td>
                        <td>${item.item_name || '-'}</td>
                        <td class="text-end px-3">${parseFloat(item.qty).toFixed(1)}</td>
                        <td class="text-end px-3">${parseFloat(item.unitprice).toFixed(2)}</td>
                        <td class="text-end px-3 fw-bold">${price.toFixed(2)}</td>
                        <td class="text-end px-3 text-muted">${parseFloat(item.discount || 0).toFixed(2)}</td>
                    </tr>`;

                    if (item.icode.startsWith('1')) {
                        drugRows += rowHtml;
                        drugsCount++;
                    } else {
                        serviceRows += rowHtml;
                        servicesCount++;
                    }
                });

                // Set footer summary total
                if (footerSummary) {
                    footerSummary.innerHTML = `
                        <span><b>ค่ารักษาทั้งหมด:</b> <span class="text-primary fw-bold">${totalCharge.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span> บาท</span>
                        <span>|</span>
                        <span><b>ต้องชำระ:</b> <span class="text-danger fw-bold">0.00</span> บาท</span>
                        <span>|</span>
                        <span><b>ชำระแล้ว:</b> <span class="text-dark fw-bold">${parseFloat(adm.rcpt_money || 0).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span> บาท</span>
                        <span>|</span>
                        <span><b>ลูกหนี้สิทธิ์:</b> <span class="text-success fw-bold">${(totalCharge - parseFloat(adm.rcpt_money || 0)).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span> บาท</span>
                    `;
                }

                // Render Modal Body HTML matching SSOP Layout
                body.innerHTML = `
                <style>
                  .compact-info-table th, .compact-info-table td {
                      font-size: 12px !important;
                      padding: 6px 12px !important;
                      border-bottom: 1px solid #dee2e6 !important;
                  }
                  .modal-table th, .modal-table td {
                      font-size: 12px !important;
                      padding: 6px 8px !important;
                  }
                </style>
                <div class="row g-3">
                  <!-- Status alert banner -->
                  ${statusHtml}

                  <!-- Column 1: Patient Info -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light h-100 shadow-sm">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small" style="font-size: 12.5px;"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                        <table class="table table-sm table-borderless mb-0 w-100 compact-info-table">
                          <tr><th class="text-muted" style="width:40%;">HN / AN</th><td class="fw-bold text-dark">${adm.hn} / ${adm.an}</td></tr>
                          <tr><th class="text-muted">เลขบัตรประชาชน</th><td class="text-dark">${adm.cid ?? '-'}</td></tr>
                          <tr><th class="text-muted">ชื่อ-สกุล</th><td class="text-dark fw-bold">${adm.pname}${adm.fname} ${adm.lname}</td></tr>
                          <tr><th class="text-muted">เพศ/วันเกิด</th><td class="text-dark">${adm.sex == '1' ? 'ชาย' : (adm.sex == '2' ? 'หญิง' : '-')} / ${adm.birthday ? adm.birthday.split('-').reverse().join('/') : '-'}</td></tr>
                          <tr><th class="text-muted">สัญชาติ</th><td class="text-dark">${adm.nationality || '-'}</td></tr>
                          <tr><th class="text-muted">สิทธิ์การรักษา</th><td class="text-dark"><span class="badge bg-primary">${adm.pttype_name}</span></td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- Column 2: Admission Info -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light h-100 shadow-sm">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small" style="font-size: 12.5px;"><i class="bi bi-hospital me-1"></i>ข้อมูลแอดมิท</div>
                        <table class="table table-sm table-borderless mb-0 w-100 compact-info-table" style="table-layout: fixed;">
                          <tr><th class="text-muted" style="width:40%;">วันเวลา Admit</th><td class="text-dark" style="word-break: break-all;">${adm.regdate ? adm.regdate.split('-').reverse().join('/') : '-'} ${adm.regtime ? adm.regtime.substring(0,5) : ''} น.</td></tr>
                          <tr><th class="text-muted">วันเวลา D/C</th><td class="text-dark" style="word-break: break-all;">${adm.dchdate ? adm.dchdate.split('-').reverse().join('/') : '-'} ${adm.dchtime ? adm.dchtime.substring(0,5) : ''} น.</td></tr>
                          <tr><th class="text-muted">แผนก/หอผู้ป่วย</th><td class="text-dark" style="word-break: break-all;">${adm.spclty_name || '-'} / ${adm.ward_name || '-'}</td></tr>
                          <tr><th class="text-muted">แพทย์เจ้าของไข้</th><td class="text-dark" style="word-break: break-all;">${adm.doctor_name || '-'}</td></tr>
                          <tr><th class="text-muted">ข้อมูลแพทย์สรุป</th><td class="text-dark" style="word-break: break-all;">${adm.dch_sum === 'Y' ? '<span class="badge bg-success">สรุปแล้ว</span>' : '<span class="badge bg-danger">ยังไม่สรุป</span>'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- Column 3: Financial & Clinical Info -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light h-100 shadow-sm">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small" style="font-size: 12.5px;"><i class="bi bi-currency-dollar me-1"></i>ข้อมูลสรุปค่ารักษา & DRG</div>
                        <table class="table table-sm table-borderless mb-0 w-100 compact-info-table" style="table-layout: fixed;">
                          <tr><th class="text-muted" style="width:40%;">ค่ารักษาพยาบาล</th><td class="text-dark fw-bold" style="word-break: break-all;">${parseFloat(adm.income || 0).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท</td></tr>
                          <tr><th class="text-muted">ชำระแล้ว (เอง)</th><td class="text-dark" style="word-break: break-all;">${parseFloat(adm.rcpt_money || 0).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท</td></tr>
                          <tr><th class="text-muted">ยอดเงินเรียกเก็บ</th><td class="text-success fw-bold" style="word-break: break-all;">${(parseFloat(adm.income || 0) - parseFloat(adm.rcpt_money || 0)).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท</td></tr>
                          <tr><th class="text-muted">AdjRW</th><td class="text-primary fw-bold" style="word-break: break-all;">${parseFloat(adm.adjrw || 0).toFixed(4)}</td></tr>
                          <tr><th class="text-muted">ICD-10 (PDX)</th><td class="text-danger fw-bold" style="word-break: break-all;">${adm.pdx || '-'}</td></tr>
                          <tr><th class="text-muted">ICD-10 (วินิจฉัย)</th><td class="text-dark" style="word-break: break-all; white-space: normal;">${res.diags.map(d => d.icd10).join(', ') || '-'}</td></tr>
                          <tr><th class="text-muted">ICD-9 (หัตถการ)</th><td class="text-dark" style="word-break: break-all; white-space: normal;">${res.procs.map(p => p.icd9).join(', ') || '-'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- Split Tabs for Drugs and Charges -->
                  <div class="col-12 mt-3">
                    <ul class="nav nav-tabs nav-tabs-custom mb-2" id="modalDetailTabs" role="tablist" style="font-size: 0.85rem;">
                      <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-info" id="modal-drugs-tab" data-bs-toggle="tab" data-bs-target="#modal-drugs-panel" type="button" role="tab" aria-controls="modal-drugs-panel" aria-selected="true">
                          <i class="bi bi-capsule me-1"></i>รายการยา (${drugsCount})
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-warning" id="modal-charges-tab" data-bs-toggle="tab" data-bs-target="#modal-charges-panel" type="button" role="tab" aria-controls="modal-charges-panel" aria-selected="false">
                          <i class="bi bi-receipt me-1"></i>ค่ารักษาพยาบาล (${servicesCount})
                        </button>
                      </li>
                    </ul>

                    <div class="tab-content" id="modalDetailTabsContent">
                      <!-- Drugs Panel -->
                      <div class="tab-pane fade show active" id="modal-drugs-panel" role="tabpanel" aria-labelledby="modal-drugs-tab">
                        <div class="table-responsive">
                          <table id="modal-drugs-table" class="table table-bordered table-striped table-sm align-middle modal-table mb-0 w-100">
                            <thead class="table-secondary">
                              <tr>
                                <th class="text-center" width="10%">หมวด</th>
                                <th class="text-center" width="15%">รหัสบริการ</th>
                                <th>ชื่อยา/เวชภัณฑ์</th>
                                <th class="text-end px-3" width="12%">จำนวน</th>
                                <th class="text-end px-3" width="12%">ราคา/หน่วย</th>
                                <th class="text-end px-3" width="15%">ยอดเงินรวม</th>
                                <th class="text-end px-3" width="12%">ส่วนลด</th>
                              </tr>
                            </thead>
                            <tbody>${drugRows || '<tr><td colspan="7" class="text-center text-muted">ไม่พบข้อมูลรายการยา</td></tr>'}</tbody>
                          </table>
                        </div>
                      </div>

                      <!-- Charges Panel -->
                      <div class="tab-pane fade" id="modal-charges-panel" role="tabpanel" aria-labelledby="modal-charges-tab">
                        <div class="table-responsive">
                          <table id="modal-charges-table" class="table table-bordered table-striped table-sm align-middle modal-table mb-0 w-100">
                            <thead class="table-secondary">
                              <tr>
                                <th class="text-center" width="10%">หมวด</th>
                                <th class="text-center" width="15%">รหัสบริการ</th>
                                <th>รายการค่าบริการทางการแพทย์</th>
                                <th class="text-end px-3" width="12%">จำนวน</th>
                                <th class="text-end px-3" width="12%">ราคา/หน่วย</th>
                                <th class="text-end px-3" width="15%">ยอดเงินรวม</th>
                                <th class="text-end px-3" width="12%">ส่วนลด</th>
                              </tr>
                            </thead>
                            <tbody>${serviceRows || '<tr><td colspan="7" class="text-center text-muted">ไม่พบข้อมูลค่ารักษาเรียกเก็บ</td></tr>'}</tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                `;

                // Destroy old DataTables if any
                if ($.fn.DataTable.isDataTable('#modal-drugs-table')) $('#modal-drugs-table').DataTable().destroy();
                if ($.fn.DataTable.isDataTable('#modal-charges-table')) $('#modal-charges-table').DataTable().destroy();

                const dtLang = {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                };

                // Initialize DataTables
                if (drugsCount > 0) {
                    $('#modal-drugs-table').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                        language: dtLang
                    });
                }
                if (servicesCount > 0) {
                    $('#modal-charges-table').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                        language: dtLang
                    });
                }

                // Adjust column sizing on tab show
                $('#modalDetailTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });
            })
            .fail(function(xhr) {
                body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>เชื่อมต่อฐานข้อมูลล้มเหลว หรือคำขอหมดอายุ</div>`;
            });
    };

    // Copy XML to clipboard helper
    $(document).on('click', '.btn-copy-xml', function() {
        const targetId = $(this).data('target');
        const text = $('#' + targetId).val();
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกสำเร็จ',
                text: 'คัดลอกรหัส XML ลง Clipboard เรียบร้อยแล้ว',
                timer: 1500,
                showConfirmButton: false
            });
        });
    });

    // Dynamic XML selection by AN
    window.selectPreviewAn = function(an) {
        $('#active-preview-an').text(an);
        let files = window.currentPreviewXmlFiles || {};
        let targetFile = Object.keys(files).find(filename => filename.includes(`-AIPN-${an}-`));
        let xmlContent = targetFile ? files[targetFile] : '';
        $('#preview-aipn-xml-textarea').val(xmlContent);
    };

    $(document).on('click', '.btn-select-preview-an', function(e) {
        e.preventDefault();
        let an = $(this).data('an');
        window.selectPreviewAn(an);
    });
  </script>
@endpush