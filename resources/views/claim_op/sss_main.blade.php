@php
    $is_ssop_licensed = \App\Services\LicenseService::isLicensed();
@endphp
@extends('layouts.app')

@section('content')

<style>
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Fix DataTables duplicated header/thin blue row bug when scrollX/scrollY is used */
.dataTables_scrollBody table thead tr {
    visibility: collapse !important;
    height: 0 !important;
}
.dataTables_scrollBody table thead tr th {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    border: none !important;
    height: 0 !important;
}
</style>

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ SS-OP ประกันสังคม เครือข่าย
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form id="form_budget_year" method="POST" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกปีงบประมาณ</span>
                    <div class="input-group input-group-sm">
                        <select class="form-select" name="budget_year" style="width: 160px;">
                            @foreach ($budget_year_select as $row)
                              <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                {{ $row->LEAVE_YEAR_NAME }}
                              </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-graph-up me-1"></i> โหลดกราฟ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div id="data-container">
        <div class="card shadow-sm border-0 m-3" style="border-radius: 12px; overflow: hidden;">
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
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>รายละเอียดการรับบริการและตรวจสอบสิทธิ์
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="detailsModalBody">
                    <!-- Dynamic Content -->
                </div>
                <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                    <div id="detailsModalFooterSummary" class="text-start d-flex gap-3 text-muted small" style="font-size: 11.5px;">
                        <!-- Will be populated dynamically in JS -->
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>ปิดหน้าต่าง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal นำเข้าและตรวจสอบผลตอบกลับ -->
    <div class="modal fade" id="importFeedbackModal" tabindex="-1" aria-labelledby="importFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title font-weight-bold" id="importFeedbackModalLabel">
                        <i class="bi bi-file-earmark-zip me-2"></i>นำเข้าและตรวจสอบผลตอบกลับ สกส. (SSOP / โรคเรื้อรัง / STM)
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- ส่วนหัวการนำเข้าและผลตอบกลับแบบกระชับ -->
                    <div class="p-3 mb-4 bg-light rounded border shadow-sm">
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center justify-content-md-start">
                            <!-- ปุ่มที่ 1: นำเข้าข้อมูล REP -->
                            <div>
                                <input type="file" id="zip_file_rep" style="display: none;" accept=".zip" multiple onchange="uploadSssZip('rep')">
                                <button type="button" class="btn btn-primary px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_rep').click()">
                                    <i class="bi bi-file-earmark-arrow-up fs-5"></i> นำเข้าข้อมูล REP
                                </button>
                            </div>
                            <!-- ปุ่มที่ 2: นำเข้าข้อมูล STM -->
                            <div>
                                <input type="file" id="zip_file_stm" style="display: none;" accept=".zip" multiple onchange="uploadSssZip('stm')">
                                <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_stm').click()">
                                    <i class="bi bi-file-earmark-check fs-5"></i> นำเข้าข้อมูล STM
                                </button>
                            </div>
                            <!-- ปุ่มที่ 3: นำเข้าข้อมูลโรคเรื้อรัง -->
                            <div>
                                <input type="file" id="zip_file_chronic" style="display: none;" accept=".zip" multiple onchange="uploadSssZip('chronic')">
                                <button type="button" class="btn btn-info text-white px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_chronic').click()">
                                    <i class="bi bi-file-medical fs-5"></i> นำเข้าข้อมูลโรคเรื้อรัง
                                </button>
                            </div>
                            <!-- ปุ่มที่ 4: นำเข้าบัญชีโรคเรื้อรัง -->
                            <div>
                                <input type="file" id="zip_file_chronic_reg" style="display: none;" accept=".zip" multiple onchange="uploadSssZip('chronic_reg')">
                                <button type="button" class="btn btn-warning text-dark px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_chronic_reg').click()">
                                    <i class="bi bi-journal-medical fs-5"></i> นำเข้าบัญชีโรคเรื้อรัง
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <ul class="nav nav-tabs" id="feedbackTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-danger" id="tab21-tab" data-bs-toggle="tab" data-bs-target="#tab21-panel" type="button" role="tab" aria-controls="tab21-panel" aria-selected="true">
                                ตอนที่ 2.1 ผู้ป่วยอยู่ในบัญชีโรคเรื้อรังแล้ว (Dx หรือ Drug ไม่ตรง)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-warning" id="tab22-tab" data-bs-toggle="tab" data-bs-target="#tab22-panel" type="button" role="tab" aria-controls="tab22-panel" aria-selected="false">
                                ตอนที่ 2.2 ผู้ป่วยยังไม่อยู่ในบัญชีโรคเรื้อรัง
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content border border-top-0 p-3 bg-white rounded-bottom" id="feedbackTabsContent">
                        <!-- Tab 2.1 -->
                        <div class="tab-pane fade show active" id="tab21-panel" role="tabpanel" aria-labelledby="tab21-tab">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle" id="table-feedback-21">
                                    <thead class="table-dark small">
                                        <tr>
                                            <th>วันที่รับบริการ</th>
                                            <th>HN</th>
                                            <th>ชื่อ-สกุล</th>
                                            <th>เลขบัตรประชาชน</th>
                                            <th>รหัสวินิจฉัย (Dx)</th>
                                            <th>รหัสยา (Drug)</th>
                                            <th>ไฟล์อ้างอิง</th>
                                        </tr>
                                    </thead>
                                    <tbody id="feedback-21-body" class="small">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">กำลังโหลดข้อมูล...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 2.2 -->
                        <div class="tab-pane fade" id="tab22-panel" role="tabpanel" aria-labelledby="tab22-tab">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle" id="table-feedback-22">
                                    <thead class="table-dark small">
                                        <tr>
                                            <th>วันที่รับบริการ</th>
                                            <th>HN</th>
                                            <th>ชื่อ-สกุล</th>
                                            <th>เลขบัตรประชาชน</th>
                                            <th>รหัสวินิจฉัย (Dx)</th>
                                            <th>รหัสยา (Drug)</th>
                                            <th>ไฟล์อ้างอิง</th>
                                        </tr>
                                    </thead>
                                    <tbody id="feedback-22-body" class="small">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">กำลังโหลดข้อมูล...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SSOP Export Conditions Modal -->
    <div class="modal fade" id="ssopExportModal" tabindex="-1" aria-labelledby="ssopExportModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="ssopExportModalLabel">
                        <i class="bi bi-box-arrow-up-fill me-2"></i> เงื่อนไขการส่งออกข้อมูล SSOP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="export_session_id" class="form-label fw-bold">เลขรอบการส่งออก (Session ID)</label>
                        <input type="number" class="form-control form-control-lg fw-bold text-center" id="export_session_id" placeholder="ตัวอย่าง 6906" min="1" max="99999">
                        <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle-fill me-1"></i> ระบบจะสุ่มเลขรอบเริ่มต้นให้ แนะนำให้ปรับแต่งไม่ให้ซ้ำกับรอบที่ส่งไปแล้ว</div>
                    </div>
                    <div class="mb-3">
                        <label for="export_station_id" class="form-label fw-bold">รหัสเครื่องส่ง (Station ID)</label>
                        <input type="text" class="form-control form-control-lg fw-bold text-center" id="export_station_id" value="01" placeholder="ตัวอย่าง 01" maxlength="5">
                        <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle-fill me-1"></i> โดยทั่วไปใช้รหัสเครื่องหลักคือ 01</div>
                    </div>
                    <div class="mb-3">
                        <label for="export_tflag" class="form-label fw-bold">ประเภทการนำส่ง (Transaction Flag)</label>
                        <select class="form-select form-select-lg fw-bold" id="export_tflag">
                            <option value="A" selected>A - ขอเบิกใหม่ (ค่าเริ่มต้น)</option>
                            <option value="E">E - แก้ไขรายการ</option>
                            <option value="D">D - ยกเลิกรายการ</option>
                        </select>
                        <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle-fill me-1"></i> โดยทั่วไปเลือก A สำหรับการขอเบิกใหม่ หรือ E เมื่อต้องการส่งข้อมูลแก้ไขรายการเดิม</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-success px-4" onclick="previewSSOPExport()">
                        <i class="bi bi-eye me-1"></i> ดำเนินการและพรีวิวข้อมูล
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SSOP Export Preview Modal -->
    <div class="modal fade" id="ssopPreviewModal" tabindex="-1" aria-labelledby="ssopPreviewModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="ssopPreviewModalLabel">
                        <i class="bi bi-file-earmark-check-fill me-2"></i> ตรวจสอบความถูกต้องของข้อมูลก่อนส่งออก SSOP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Tabs Header -->
                    <ul class="nav nav-tabs mb-3" id="previewTab" role="tablist" style="font-size: 0.85rem;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-danger" id="prev-audit-tab" data-bs-toggle="tab" data-bs-target="#prev-audit" type="button" role="tab" aria-controls="prev-audit" aria-selected="true">
                                <i class="bi bi-shield-fill-exclamation me-1"></i> ผลตรวจสอบ (Pre-Audit)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-primary" id="prev-billtran-tab" data-bs-toggle="tab" data-bs-target="#prev-billtran" type="button" role="tab" aria-controls="prev-billtran" aria-selected="false">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> BILLTRAN
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-primary" id="prev-billitems-tab" data-bs-toggle="tab" data-bs-target="#prev-billitems-panel" type="button" role="tab" aria-controls="prev-billitems-panel" aria-selected="false">
                                <i class="bi bi-list-stars me-1"></i> BillItems
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-success" id="prev-billdisp-tab" data-bs-toggle="tab" data-bs-target="#prev-billdisp" type="button" role="tab" aria-controls="prev-billdisp" aria-selected="false">
                                <i class="bi bi-capsule me-1"></i> BILLDISP
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-success" id="prev-dispenseditems-tab" data-bs-toggle="tab" data-bs-target="#prev-dispenseditems-panel" type="button" role="tab" aria-controls="prev-dispenseditems-panel" aria-selected="false">
                                <i class="bi bi-capsules me-1"></i> DispensedItems
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-info" id="prev-opservices-tab" data-bs-toggle="tab" data-bs-target="#prev-opservices" type="button" role="tab" aria-controls="prev-opservices" aria-selected="false">
                                <i class="bi bi-clipboard-pulse me-1"></i> OPServices
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-info" id="prev-opdiagnoses-tab" data-bs-toggle="tab" data-bs-target="#prev-opdiagnoses" type="button" role="tab" aria-controls="prev-opdiagnoses" aria-selected="false">
                                <i class="bi bi-clipboard-pulse me-1"></i> OPDiagnoses
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="previewTabContent" style="font-size: 0.82rem;">
                        <!-- Pre-Audit Tab -->
                        <div class="tab-pane fade show active" id="prev-audit" role="tabpanel" aria-labelledby="prev-audit-tab">
                            <div class="alert alert-warning d-flex align-items-center mb-3">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                                <div>กรุณาตรวจสอบและแก้ไขรายการที่มีสีแดง (Errors) ก่อนที่จะทำการดาวน์โหลด ZIP นำส่งระบบ สกส. เพื่อป้องกันการติด C (Denied Claim)</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle" id="table-prev-audit">
                                    <thead class="table-danger">
                                        <tr>
                                            <th class="text-center" width="5%">#</th>
                                            <th width="15%">ผู้ป่วย (HN / ชื่อ-สกุล)</th>
                                            <th width="15%">วันที่รับบริการ</th>
                                            <th>ประเด็นที่พบ (Audit Issues)</th>
                                            <th class="text-center" width="10%">ระดับความรุนแรง</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prev-audit-body">
                                        <!-- Populated via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- BILLTRAN Tab -->
                        <div class="tab-pane fade" id="prev-billtran" role="tabpanel" aria-labelledby="prev-billtran-tab">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="table-prev-billtran">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Station</th><th>InvNo</th><th>HN</th><th>MemberNo</th><th>Amount</th><th>Paid</th><th>Claim</th><th>Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prev-billtran-body"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- BillItems Tab -->
                        <div class="tab-pane fade" id="prev-billitems-panel" role="tabpanel" aria-labelledby="prev-billitems-tab">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="table-prev-billitems">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>InvNo</th><th>ItemSeq</th><th>BillGr</th><th>LCode</th><th>Qty</th><th>Charge</th><th>Claim</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prev-billitems-body"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- BILLDISP Tab -->
                        <div class="tab-pane fade" id="prev-billdisp" role="tabpanel" aria-labelledby="prev-billdisp-tab">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="table-prev-billdisp">
                                    <thead class="table-success">
                                        <tr>
                                            <th>DispID</th><th>PrescID</th><th>InvNo</th><th>DispDate</th><th>HN</th><th>Name</th><th>Amount</th><th>Reimb</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prev-billdisp-body"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- DispensedItems Tab -->
                        <div class="tab-pane fade" id="prev-dispenseditems-panel" role="tabpanel" aria-labelledby="prev-dispenseditems-tab">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="table-prev-dispenseditems">
                                    <thead class="table-success">
                                        <tr>
                                            <th>DispID</th><th>PrescID</th><th>ItemSeq</th><th>LocalCd</th><th>StdCd</th><th>Qty</th><th>PrdCat</th><th>Reimb</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prev-dispenseditems-body"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- OPServices Tab -->
                        <div class="tab-pane fade" id="prev-opservices" role="tabpanel" aria-labelledby="prev-opservices-tab">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="table-prev-opservices">
                                    <thead class="table-info">
                                        <tr>
                                            <th>HN</th><th>SvDate</th><th>Class</th><th>CareType</th><th>InvNo</th><th>PrePay</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prev-opservices-body"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- OPDiagnoses Tab -->
                        <div class="tab-pane fade" id="prev-opdiagnoses" role="tabpanel" aria-labelledby="prev-opdiagnoses-tab">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="table-prev-opdiagnoses">
                                    <thead class="table-info">
                                        <tr>
                                            <th>HN</th><th>SvDate</th><th>DiagType</th><th>DiagCode</th>
                                        </tr>
                                    </thead>
                                    <tbody id="prev-opdiagnoses-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" onclick="$('#ssopPreviewModal').modal('hide'); $('#ssopExportModal').modal('show');">
                        <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                    </button>
                    <button type="button" class="btn btn-success px-4" id="btnDownloadSSOP" onclick="downloadSSOPExportZip()">
                        <i class="bi bi-download me-1"></i> ยืนยันการดาวน์โหลด SSOP (.zip)
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
  <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>

  <script>
    let myChart = null;
    let dt_claim = null;

    function fetchData() {
        // Fallback for legacy handlers
    }

    // Filter Rep Error, Has/No Invoice function
    window.applyRepFilter = function() {
        if (!dt_claim) return;
        dt_claim.draw();
    };

    // AJAX Dashboard Loader
    function loadDashboard(dataParams) {
        const container = document.getElementById('data-container');
        if (!container) return;

        if (dataParams.skip_chart) {
            const tableContainer = document.querySelector('.table-responsive');
            if (tableContainer) {
                tableContainer.innerHTML = `
                    <div class="text-center py-5">
                        <div class="d-flex justify-content-center mb-3">
                            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                        </div>
                        <h6 class="fw-bold text-secondary">กำลังอัปเดตตารางข้อมูลผู้ป่วย...</h6>
                    </div>
                `;
            }
        } else {
            container.innerHTML = `
                <div class="card shadow-sm border-0 m-3" style="border-radius: 12px; overflow: hidden;">
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
            url: "{{ url('claim_op/sss_main') }}",
            type: "POST",
            data: $.extend({ _token: "{{ csrf_token() }}" }, dataParams)
        })
        .done(function(res) {
            if (res.success) {
                container.innerHTML = res.table_html;
                window.patientItems = res.patient_items || [];

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

                $('#start_date_picker').on('changeDate', function(e) {
                    var date = e.date;
                    if(date) {
                      var day = ("0" + date.getDate()).slice(-2);
                      var month = ("0" + (date.getMonth() + 1)).slice(-2);
                      var year = date.getFullYear();
                      $('#start_date').val(year + "-" + month + "-" + day);
                    }
                });

                $('#end_date_picker').on('changeDate', function(e) {
                    var date = e.date;
                    if(date) {
                      var day = ("0" + date.getDate()).slice(-2);
                      var month = ("0" + (date.getMonth() + 1)).slice(-2);
                      var year = date.getFullYear();
                      $('#end_date').val(year + "-" + month + "-" + day);
                    }
                });

                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        const filterVal = $('input[name="rep_filter"]:checked').val() || 'all';
                        if (filterVal === 'all') return true;

                        const rowNode = dt_claim.row(dataIndex).node();
                        if (!rowNode) return true;

                        const hasError = rowNode.getAttribute('data-has-error') === 'true';
                        const hasInvoice = rowNode.getAttribute('data-has-invoice') === 'true';

                        if (filterVal === 'error') return hasError;
                        if (filterVal === 'has_invoice') return hasInvoice;
                        if (filterVal === 'no_invoice') return !hasInvoice;

                        return true;
                    }
                );

                dt_claim = $('#t_claim').DataTable({
                    autoWidth: false,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>><rt><"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้มารับบริการ SS-OP ประกันสังคม เครือข่าย'
                    }],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    }
                });

                $(document).on('change', '#select_all_claims', function() {
                    const checked = this.checked;
                    $('.claim-select-check').each(function() {
                        this.checked = checked;
                    });
                });

                if (res.chart_data) {
                    window.currentChartData = res.chart_data;
                }
                if (window.currentChartData) {
                    drawChart(window.currentChartData);
                }
            }
        })
        .fail(function() {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถอัปเดตข้อมูลตารางผ่านระบบ AJAX ได้' });
        });
    }

    // Chart Drawer
    function drawChart(chartData) {
        const ctx = document.querySelector('#sum_month');
        if (!ctx) return;

        if (myChart) {
            myChart.destroy();
        }

        myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.months || chartData.month || [],
                datasets: [
                    {
                        label: 'เรียกเก็บ',
                        data: chartData.claim_price || [],
                        backgroundColor: 'rgba(185, 28, 28, 0.75)',
                        borderColor: 'rgb(185, 28, 28)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'ส่งเคลม',
                        data: chartData.claim_sent_price || [],
                        backgroundColor: 'rgba(234, 179, 8, 0.6)',
                        borderColor: 'rgb(234, 179, 8)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'ชดเชย',
                        data: chartData.receive_total || [],
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
                        labels: { usePointStyle: true, boxWidth: 6 }
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
                        font: { weight: 'bold', size: 10 },
                        formatter: (value) => value > 0 ? value.toLocaleString() : ''
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(value) { return value.toLocaleString(); } }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    // Custom showDetails function to display SSOP validation checks on the eye button
    window.showDetails = function(vn) {
        const body = document.getElementById('detailsModalBody');
        if (!body) return;
        body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
        
        // Clear footer summary immediately to prevent showing previous patient's details
        const footerSummary = document.getElementById('detailsModalFooterSummary');
        if (footerSummary) {
            footerSummary.innerHTML = '';
        }
        
        $('#detailsModal').modal('show');

        $.get("{{ url('claim_op/sss_detail') }}", { vn: vn })
            .done(function(data) {
                const visit = data.visit;
                const diagnoses = data.diagnoses;
                const drugs = data.drugs;
                const feedbacks = data.rep_feedbacks || [];

                let pdx = visit.pdx || '-';
                let sec_diags = [];
                let procedures = [];
                let has_pdx = false;

                diagnoses.forEach(function(d) {
                    if (d.diagtype == '2') {
                        procedures.push(d.icd10);
                    } else if (d.diagtype != '1') {
                        sec_diags.push(d.icd10);
                    }
                    if (d.diagtype == '1') {
                        has_pdx = true;
                    }
                });

                // NHSO Endpoint privilege status button
                let endpointBtn = '';
                if (visit.endpoint === 'Y') {
                    endpointBtn = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`;
                } else {
                    endpointBtn = `<button onclick="pullNhsoData('${visit.vstdate}', '${visit.cid}', '${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;
                }

                let receiptText = parseFloat(visit.rcpt_money) > 0 && visit.rcpno_list 
                    ? ` (${visit.rcpno_list})` 
                    : '';

                let invoice_no = visit.sss_invno && visit.sss_invno !== '0' ? visit.sss_invno : (visit.debt_id_list && visit.debt_id_list !== '0' ? visit.debt_id_list : '');

                // Validation errors
                const errors = [];
                if (!invoice_no || invoice_no === '0' || invoice_no === 0) {
                    errors.push("ไม่พบเลขใบแจ้งหนี้ (InvoiceNo) กรุณากดออกใบแจ้งหนี้ใน HOSxP");
                }
                if (!visit.cid || visit.cid.length !== 13) {
                    errors.push("เลขบัตรประชาชน (CID) ว่างหรือความยาวไม่ครบ 13 หลัก");
                }
                if (!visit.hn) {
                    errors.push("ไม่พบ HN");
                }
                if (!has_pdx) {
                    errors.push("ไม่พบรหัสวินิจฉัยโรคหลัก (PDX) กรุณาบันทึกแพทย์ผู้ตรวจโรค");
                }
                const uc_money = parseFloat(visit.uc_money || 0);
                if (uc_money <= 0) {
                    errors.push("ยอดเงินเรียกเก็บ (uc_money) น้อยกว่าหรือเท่ากับ 0 บาท");
                }

                // Add backend pre-audit checks to errors/warnings
                const preAudits = data.pre_audits || [];
                const warnings = [];
                
                preAudits.forEach(audit => {
                    let msg = '';
                    if (audit.code) {
                        msg += `[${audit.code}] `;
                    }
                    if (audit.title) {
                        msg += `${audit.title}: `;
                    }
                    msg += audit.desc;

                    if (audit.status === 'danger') {
                        errors.push(msg);
                    } else if (audit.status === 'warning') {
                        warnings.push(msg);
                    }
                });

                // Determine validation status alert banner
                let statusHtml = '';
                if (errors.length > 0) {
                    // RED Status Alert
                    statusHtml = `
                    <div class="col-12 mb-2">
                      <div class="alert alert-danger py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #dc2626 !important;">
                        <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.1rem; color: #dc2626;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ไม่ผ่านเกณฑ์ส่งออก (มีข้อผิดพลาดที่ต้องแก้ไข)</div>
                          <ul class="mb-0 ps-3 text-danger">
                            ${errors.map(err => `<li>${err}</li>`).join('')}
                          </ul>
                        </div>
                      </div>
                    </div>`;
                } else if (visit.endpoint !== 'Y' || warnings.length > 0) {
                    // YELLOW Status Alert
                    const allWarnings = [...warnings];
                    if (visit.endpoint !== 'Y') {
                        allWarnings.push("สิทธิ์การรักษายังไม่ได้ปิดสิทธิ์ในระบบ สปสช. (กรุณากดดึงข้อมูลหรือปิดสิทธิ์)");
                    }
                    statusHtml = `
                    <div class="col-12 mb-2">
                      <div class="alert alert-warning py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #fffbeb; color: #92400e; border-left: 5px solid #d97706 !important;">
                        <i class="bi bi-exclamation-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #d97706;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลผ่านเกณฑ์ แต่มีข้อแนะนำ/ยังไม่ได้ปิดสิทธิ (สปสช.)</div>
                          <ul class="mb-0 ps-3 text-warning" style="color: #92400e !important;">
                            ${allWarnings.map(warn => `<li>${warn}</li>`).join('')}
                          </ul>
                        </div>
                      </div>
                    </div>`;
                } else {
                    // GREEN Status Alert
                    statusHtml = `
                    <div class="col-12 mb-2">
                      <div class="alert alert-success py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #f0fdf4; color: #166534; border-left: 5px solid #16a34a !important;">
                        <i class="bi bi-check-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #16a34a;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลพร้อมส่งออก (ผ่านเกณฑ์และปิดสิทธิเรียบร้อย)</div>
                          <div class="text-muted">ข้อมูลการรับบริการถูกต้อง ครบถ้วน และปิดสิทธิเรียบร้อยแล้ว</div>
                        </div>
                      </div>
                    </div>`;
                }

                let html = `
                <style>
                  .compact-info-table th, .compact-info-table td {
                      font-size: 12px !important;
                      padding: 6px 12px !important;
                      border-bottom: 1px solid #dee2e6 !important;
                  }
                  #modal-drugs-table th, #modal-drugs-table td,
                  #modal-services-table th, #modal-services-table td {
                      font-size: 12px !important;
                      padding: 6px 8px !important;
                  }
                  .dataTables_wrapper, .dataTables_info, .dataTables_paginate, .dataTables_length, .dataTables_filter {
                      font-size: 12px !important;
                  }
                </style>
                <div class="row g-3">
                  <!-- Validation Status Banner -->
                  ${statusHtml}

                  <!-- Column 1: Patient Info -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light h-100">
                      <div class="card-body py-2 px-3" style="font-size: 11px;">
                        <div class="fw-bold text-primary mb-2 small" style="font-size: 12px;"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                        <table class="table table-sm table-borderless mb-0 w-100 compact-info-table">
                          <tr><th class="text-muted" style="width:43%;">HN</th><td class="fw-bold text-dark" >${visit.hn}</td></tr>
                          <tr><th class="text-muted" >CID</th><td class="text-dark" >${visit.cid ?? '-'}</td></tr>
                          <tr><th class="text-muted" >ชื่อ-สกุล</th><td class="text-dark" >${visit.ptname}</td></tr>
                          <tr><th class="text-muted" >เพศ/อายุ</th><td class="text-dark" >${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : (visit.sex ?? '-'))} / ${visit.age_y ?? '-'} ปี</td></tr>
                          <tr><th class="text-muted" >สิทธิ์การรักษา</th><td class="text-dark" >${visit.pttype_name ?? '-'}</td></tr>
                          <tr><th class="text-muted" >รพ.หลัก (HMAIN)</th><td class="text-dark fw-bold text-danger" >${visit.hospmain ?? '-'}</td></tr>
                          <tr><th class="text-muted" >Hipdata Code</th><td class="text-dark" >${visit.hipdata_code ?? '-'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- Column 2: Clinical Info -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light h-100">
                      <div class="card-body py-2 px-3" style="font-size: 11px;">
                        <div class="fw-bold text-primary mb-2 small" style="font-size: 12px;"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก</div>
                        <table class="table table-sm table-borderless mb-0 w-100 compact-info-table" style="table-layout: fixed;">
                          <tr><th class="text-muted" style="width:40%;">วันที่รับบริการ</th><td class="text-dark" style="word-break: break-all;">${visit.vstdate} ${visit.vsttime}</td></tr>
                          <tr><th class="text-muted" >CC</th><td class="text-dark" style="word-break: break-all;">${visit.cc ?? '-'}</td></tr>
                          <tr><th class="text-muted" >PDX</th><td class="fw-bold text-danger" style="word-break: break-all;">${pdx}</td></tr>
                          <tr><th class="text-muted" >SDX</th><td class="text-dark" style="word-break: break-all;">${sec_diags.join(', ') || '-'}</td></tr>
                          <tr><th class="text-muted" >ICD-9</th><td class="text-dark" style="word-break: break-all;">${procedures.join(', ') || '-'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- Column 3: Financial Info -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light h-100">
                      <div class="card-body py-2 px-3" style="font-size: 11px;">
                        <div class="fw-bold text-primary mb-2 small" style="font-size: 12px;"><i class="bi bi-currency-dollar me-1"></i>ข้อมูลการเงิน</div>
                        <table class="table table-sm table-borderless mb-0 w-100 compact-info-table" style="table-layout: fixed;">
                          <tr><th class="text-muted" style="width:40%;">เลขใบแจ้งหนี้</th><td class="fw-bold ${invoice_no && invoice_no !== '0' ? 'text-success' : 'text-danger'}" style="word-break: break-all;">${invoice_no && invoice_no !== '0' ? invoice_no : 'ไม่มี (VN: ' + vn + ')'}</td></tr>
                          <tr><th class="text-muted" >รวมค่ารักษา</th><td class="text-dark" style="word-break: break-all;">${parseFloat(visit.income).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted" >ชำระเงินสด</th><td class="text-dark" style="word-break: break-all;">${parseFloat(visit.rcpt_money).toFixed(2)} บาท${receiptText}</td></tr>
                          <tr><th class="text-muted" >ยอดเรียกเก็บ</th><td class="text-dark" style="word-break: break-all;">${parseFloat(visit.uc_money || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted" >สถานะปิดสิทธิ</th><td >${endpointBtn}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- Split Tabs for Drugs and Services -->
                  <div class="col-12 mt-3">
                    <ul class="nav nav-tabs nav-tabs-custom mb-2" id="modalDetailTabs" role="tablist" style="font-size: 0.85rem;">
                      <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-primary" id="modal-drugs-tab" data-bs-toggle="tab" data-bs-target="#modal-drugs-panel" type="button" role="tab" aria-controls="modal-drugs-panel" aria-selected="true">
                          <i class="bi bi-capsule me-1"></i>รายการยา
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-success" id="modal-services-tab" data-bs-toggle="tab" data-bs-target="#modal-services-panel" type="button" role="tab" aria-controls="modal-services-panel" aria-selected="false">
                          <i class="bi bi-list-check me-1"></i>ค่ารักษาพยาบาล
                        </button>
                      </li>
                    </ul>
                    <div class="tab-content" id="modalDetailTabsContent">
                      <!-- Drugs Panel -->
                      <div class="tab-pane fade show active" id="modal-drugs-panel" role="tabpanel" aria-labelledby="modal-drugs-tab" style="font-size: 12px;">
                        <table id="modal-drugs-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                          <thead class="table-dark">
                            <tr>
                              <th>ชื่อยา/เวชภัณฑ์</th>
                              <th class="text-center" width="10%">จำนวน</th>
                              <th class="text-end" width="12%">ราคารวม (บาท)</th>
                              <th class="text-center" width="15%">ประเภทการชำระ</th>
                              <th class="text-center" width="15%">สิทธิการรักษา</th>
                              <th width="18%">รหัสมาตรฐาน TMT</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${(function() {
                                let drugsList = drugs.filter(d => d.icode.startsWith('1'));
                                if (drugsList.length === 0) {
                                    return '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการสั่งยาใน Visit นี้</td></tr>';
                                }
                                return drugsList.map(d => {
                                    let tmtDisplay = d.tmtid 
                                        ? `<span class="badge bg-success fw-bold">${d.tmtid}</span>`
                                        : `<span class="badge bg-secondary-soft text-secondary">ไม่มีรหัส TMT</span>`;
                                    let sigtext = d.drugusage_text ? d.drugusage_text.trim() : '';
                                    let prdcatInt = parseInt(d.sks_product_category_id);
                                    if (prdcatInt >= 1 && prdcatInt <= 5) {
                                        if (!sigtext) sigtext = 'ตามแพทย์สั่ง';
                                    }
                                    let paids_display = d.paids_name || d.paids || '-';
                                    let pttype_display = d.pttype_name || d.pttype || '-';
                                    return `<tr>
                                      <td>
                                        <div class="fw-bold text-dark">${d.name}</div>
                                        <div class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="bi bi-info-circle me-1"></i>วิธีใช้: ${sigtext || '-'}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                      </td>
                                      <td class="text-center fw-bold">${d.qty}</td>
                                      <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                                      <td class="text-center">${paids_display}</td>
                                      <td class="text-center">${pttype_display}</td>
                                      <td>${tmtDisplay}</td>
                                    </tr>`;
                                }).join('');
                            })()}
                          </tbody>
                        </table>
                      </div>
                      <!-- Services Panel -->
                      <div class="tab-pane fade" id="modal-services-panel" role="tabpanel" aria-labelledby="modal-services-tab" style="font-size: 12px;">
                        <table id="modal-services-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                          <thead class="table-dark">
                            <tr>
                              <th>ชื่อบริการ/ค่ารักษาพยาบาล</th>
                              <th class="text-center" width="10%">จำนวน</th>
                              <th class="text-end" width="12%">ราคารวม (บาท)</th>
                              <th class="text-center" width="15%">ประเภทการชำระ</th>
                              <th class="text-center" width="15%">สิทธิการรักษา</th>
                              <th width="18%">ADP</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${(function() {
                                let servicesList = drugs.filter(d => !d.icode.startsWith('1'));
                                if (servicesList.length === 0) {
                                    return '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการค่าบริการ/รักษาพยาบาลใน Visit นี้</td></tr>';
                                }
                                return servicesList.map(d => {
                                    let paids_display = d.paids_name || d.paids || '-';
                                    let pttype_display = d.pttype_name || d.pttype || '-';
                                    return `<tr>
                                      <td>
                                        <div class="fw-bold text-dark">${d.name}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                      </td>
                                      <td class="text-center fw-bold">${d.qty}</td>
                                      <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                                      <td class="text-center">${paids_display}</td>
                                      <td class="text-center">${pttype_display}</td>
                                      <td><span class="badge bg-secondary-soft text-secondary fw-bold">${d.nhso_adp_code ?? '-'}</span></td>
                                    </tr>`;
                                }).join('');
                            })()}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>`;

                body.innerHTML = html;

                // Update footer financial summary dynamically
                const footerSummary = document.getElementById('detailsModalFooterSummary');
                if (footerSummary) {
                    const inc = parseFloat(visit.income || 0);
                    const paid = parseFloat(visit.rcpt_money || 0);
                    const claim = parseFloat(visit.uc_money || 0);
                    const remain = parseFloat(visit.paid_money || 0);
                    
                    footerSummary.innerHTML = `
                        <div class="d-flex align-items-center gap-3">
                            <span><i class="bi bi-wallet2 me-1 text-primary"></i> ค่ารักษาทั้งหมด: <strong class="text-dark">${inc.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> บาท</span>
                            <span class="text-muted">|</span>
                            <span><i class="bi bi-hourglass-split me-1 text-danger"></i> ต้องชำระ: <strong class="text-danger">${remain.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> บาท</span>
                            <span class="text-muted">|</span>
                            <span><i class="bi bi-cash-coin me-1 text-success"></i> ชำระแล้ว: <strong class="text-success">${paid.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> บาท</span>
                            <span class="text-muted">|</span>
                            <span><i class="bi bi-file-earmark-medical me-1 text-info"></i> ลูกหนี้สิทธิ: <strong class="text-info">${claim.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> บาท</span>
                        </div>
                    `;
                }

                // Destroy existing DataTables if already initialized to prevent error
                if ($.fn.DataTable.isDataTable('#modal-drugs-table')) {
                    $('#modal-drugs-table').DataTable().destroy();
                }
                if ($.fn.DataTable.isDataTable('#modal-services-table')) {
                    $('#modal-services-table').DataTable().destroy();
                }

                // Initialize DataTable for Drugs
                if (drugs.filter(d => d.icode.startsWith('1')).length > 0) {
                    $('#modal-drugs-table').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                            paginate: {
                                previous: "ก่อนหน้า",
                                next: "ถัดไป"
                            }
                        }
                    });
                }

                // Initialize DataTable for Services
                if (drugs.filter(d => !d.icode.startsWith('1')).length > 0) {
                    $('#modal-services-table').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                            paginate: {
                                previous: "ก่อนหน้า",
                                next: "ถัดไป"
                            }
                        }
                    });
                }

                // Adjust column headers on tab change to prevent distorted columns
                $('#modalDetailTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });
            })
            .fail(function(xhr) {
                body.innerHTML = `<div class="alert alert-danger mb-0">เกิดข้อผิดพลาดในการโหลดรายละเอียด: ${xhr.statusText}</div>`;
            });
    };

    // NHSO Endpoint pull/push functions
    window.pullNhsoData = function(vstdate, cid, vn) {
        Swal.fire({
            title: 'กำลังดึงข้อมูล...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading()
            }
        });

        fetch("{{ url('api/nhso_endpoint_pull_indiv') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({ vstdate: vstdate, cid: cid })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
            }
            return data;
        })
        .then(data => {
            if (data.found) {
                Swal.fire({
                    icon: 'success',
                    title: 'พบข้อมูลปิดสิทธิ',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    if (vn) {
                        showDetails(vn);
                    } else {
                        location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบการปิดสิทธิจากระบบอื่น',
                    text: 'ยังไม่มีการปิดสิทธิสำหรับรายการนี้ใน สปสช. ต้องการปิดสิทธิด้วยระบบ RiMS หรือไม่?',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'ปิดสิทธิเลย',
                    cancelButtonText: 'ยกเลิก'
                }).then(result => {
                    if (result.isConfirmed) {
                        pushNhsoData(cid, vstdate, vn);
                    }
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: error.message || 'ไม่สามารถเชื่อมต่อกับระบบได้',
            });
        });
    };

    window.pushNhsoData = function(cid, vstdate, vn) {
        Swal.fire({
            title: 'ยืนยันการส่งข้อมูล?',
            text: "ระบบจะดึงข้อมูลจาก HOSxP และส่งไปปิดสิทธิที่ สปสช.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ตกลง, ส่งข้อมูล!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังดำเนินการ...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });

                $.ajax({
                    url: "{{ route('api.nhso.push_indiv') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        cid: cid,
                        vstdate: vstdate
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'ปิดสิทธิเรียบร้อยแล้ว',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                if (vn) {
                                    showDetails(vn);
                                } else {
                                    location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'ไม่สำเร็จ',
                                text: response.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล'
                            });
                        }
                    },
                    error: function(xhr) {
                        let msg = 'ไม่สามารถเชื่อมต่อกับระบบได้';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: msg
                        });
                    }
                });
            }
        });
    };

    // Show REP errors and warnings details via Swal.fire
    window.showRepDetails = function(vn) {
        Swal.fire({
            title: 'กำลังโหลดข้อมูลผลตอบกลับ...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.get("{{ url('claim_op/sss_detail') }}", { vn: vn })
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
                    title: 'รายละเอียดผลตอบกลับ (REP Feedbacks)',
                    html: html,
                    width: '650px',
                    confirmButtonText: 'ปิด'
                });
            })
            .fail(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถดึงข้อมูลผลตอบกลับได้'
                });
            });
    };

    // feedback lists & zip uploads
    window.loadFeedbackList = function() {
        const body21 = document.getElementById('feedback-21-body');
        const body22 = document.getElementById('feedback-22-body');
        
        body21.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">กำลังโหลดข้อมูล...</td></tr>';
        body22.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">กำลังโหลดข้อมูล...</td></tr>';
        
        $.get("{{ url('api/sss_ssop_feedback_list') }}")
            .done(function(data) {
                // Populate Tab 2.1
                let h21 = '';
                const items21 = data.feedback_21 || [];
                if (items21.length === 0) {
                    h21 = '<tr><td colspan="7" class="text-center text-muted py-3">ไม่พบรายการผลตอบกลับ ตอนที่ 2.1</td></tr>';
                } else {
                    items21.forEach(row => {
                        h21 += `<tr>
                            <td>${row.vstdate}</td>
                            <td class="fw-bold text-primary">${row.hn}</td>
                            <td>${row.ptname}</td>
                            <td>${row.cid}</td>
                            <td class="${row.diag_mismatch ? 'text-danger fw-bold' : ''}">${row.diag_code || '-'}</td>
                            <td class="${row.drug_mismatch ? 'text-danger fw-bold' : ''}">${row.drug_code || '-'}</td>
                            <td class="small text-muted">${row.source_filename}</td>
                        </tr>`;
                    });
                }
                body21.innerHTML = h21;

                // Populate Tab 2.2
                let h22 = '';
                const items22 = data.feedback_22 || [];
                if (items22.length === 0) {
                    h22 = '<tr><td colspan="7" class="text-center text-muted py-3">ไม่พบรายการผลตอบกลับ ตอนที่ 2.2</td></tr>';
                } else {
                    items22.forEach(row => {
                        h22 += `<tr>
                            <td>${row.vstdate}</td>
                            <td class="fw-bold text-primary">${row.hn}</td>
                            <td>${row.ptname}</td>
                            <td>${row.cid}</td>
                            <td class="text-danger fw-bold">${row.diag_code || '-'}</td>
                            <td class="text-danger fw-bold">${row.drug_code || '-'}</td>
                            <td class="small text-muted">${row.source_filename}</td>
                        </tr>`;
                    });
                }
                body22.innerHTML = h22;
            })
            .fail(function() {
                body21.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">ผิดพลาดในการเชื่อมต่อข้อมูล</td></tr>';
                body22.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">ผิดพลาดในการเชื่อมต่อข้อมูล</td></tr>';
            });
    };

    window.uploadSssZip = function(type) {
        const inputId = type === 'rep' ? 'zip_file_rep' : (type === 'stm' ? 'zip_file_stm' : (type === 'chronic' ? 'zip_file_chronic' : 'zip_file_chronic_reg'));
        const input = document.getElementById(inputId);
        if (!input || input.files.length === 0) return;

        const formData = new FormData();
        formData.append('_token', "{{ csrf_token() }}");
        formData.append('type', type);
        for(let i=0; i<input.files.length; i++) {
            formData.append('zip_files[]', input.files[i]);
        }

        Swal.fire({
            title: 'กำลังอัปโหลดและประมวลผลไฟล์...',
            text: 'กรุณารอสักครู่ ห้ามปิดหน้าต่างนี้',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "{{ url('api/import_sss_zip') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'นำเข้าสำเร็จ!',
                        html: `นำเข้าข้อมูลเรียบร้อยแล้ว<br>จำนวนข้อมูล: ${res.inserted_count} แถว<br>${res.message || ''}`,
                    }).then(() => {
                        input.value = '';
                        loadFeedbackList();
                        // Reload main dashboard tables to see latest feedback/stm status
                        loadDashboard({
                            budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
                            start_date: $('#start_date').val(),
                            end_date: $('#end_date').val(),
                            skip_chart: 1
                        });
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ไม่สำเร็จ',
                        text: res.message || 'เกิดข้อผิดพลาดในการประมวลผลไฟล์ ZIP'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการนำเข้า',
                    text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'ไม่สามารถสื่อสารกับเซิร์ฟเวอร์ได้'
                });
            }
        });
    };

    // export SSOP functions
    let selectedVnsForExport = [];
    window.exportSelectedSSOP = function() {
        selectedVnsForExport = [];
        $('.claim-select-check:checked').each(function() {
            selectedVnsForExport.push(this.value);
        });

        if (selectedVnsForExport.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกรายการ',
                text: 'กรุณาติ๊กเลือกผู้ป่วยอย่างน้อย 1 รายการก่อนทำการส่งออก SSOP'
            });
            return;
        }

        // Random Session ID setup
        const minVal = 1000;
        const maxVal = 9999;
        const randomSession = Math.floor(Math.random() * (maxVal - minVal + 1)) + minVal;
        document.getElementById('export_session_id').value = randomSession;
        document.getElementById('export_station_id').value = '01';
        document.getElementById('export_tflag').value = 'A';

        $('#ssopExportModal').modal('show');
    };

    window.previewSSOPExport = function() {
        const sessionId = document.getElementById('export_session_id').value;
        const stationId = document.getElementById('export_station_id').value;
        const tflag = document.getElementById('export_tflag').value;

        if (!sessionId) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอก Session ID' });
            return;
        }

        Swal.fire({
            title: 'กำลังเตรียมและประมวลผลข้อมูลส่งออก...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "{{ url('api/ssop_export_preview') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                vns: selectedVnsForExport,
                session_id: sessionId,
                station_id: stationId,
                tflag: tflag
            },
            success: function(res) {
                Swal.close();
                if (res.success) {
                    $('#ssopExportModal').modal('hide');
                    $('#ssopPreviewModal').modal('show');

                    // Pre-Audit Tab population
                    let hAudit = '';
                    const auditIssues = res.audit_issues || [];
                    if (auditIssues.length === 0) {
                        hAudit = '<tr><td colspan="5" class="text-center text-success fw-bold py-3"><i class="bi bi-patch-check-fill me-1"></i> ผ่านการตรวจสอบ ไม่พบข้อผิดพลาด Pre-Audit (พร้อมนำส่ง 100%)</td></tr>';
                    } else {
                        auditIssues.forEach((issue, idx) => {
                            const badge = issue.severity === 'error' ? 'bg-danger' : 'bg-warning text-dark';
                            const rowClass = issue.severity === 'error' ? 'table-danger-light' : 'table-warning-light';
                            hAudit += `<tr class="${rowClass}">
                                <td class="text-center">${idx + 1}</td>
                                <td><div class="fw-bold">${issue.hn}</div><div>${issue.ptname}</div></td>
                                <td>${issue.vstdate}</td>
                                <td class="fw-bold text-dark">${issue.message}</td>
                                <td class="text-center"><span class="badge ${badge}">${issue.severity.toUpperCase()}</span></td>
                            </tr>`;
                        });
                    }
                    document.getElementById('prev-audit-body').innerHTML = hAudit;

                    // BILLTRAN Tab population
                    let hBill = '';
                    (res.billtran || []).forEach(row => {
                        hBill += `<tr>
                            <td>${row.Station || ''}</td><td>${row.InvNo || ''}</td><td>${row.HN || ''}</td><td>${row.MemberNo || ''}</td>
                            <td>${row.Amount || ''}</td><td>${row.Paid || ''}</td><td>${row.Claim || ''}</td><td>${row.Name || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-billtran-body').innerHTML = hBill;

                    // BillItems Tab population
                    let hItems = '';
                    (res.billitems || []).forEach(row => {
                        hItems += `<tr>
                            <td>${row.InvNo || ''}</td><td>${row.ItemSeq || ''}</td><td>${row.BillGr || ''}</td><td>${row.LCode || ''}</td>
                            <td>${row.Qty || ''}</td><td>${row.Charge || ''}</td><td>${row.Claim || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-billitems-body').innerHTML = hItems;

                    // BILLDISP Tab population
                    let hDisp = '';
                    (res.billdisp || []).forEach(row => {
                        hDisp += `<tr>
                            <td>${row.DispID || ''}</td><td>${row.PrescID || ''}</td><td>${row.InvNo || ''}</td><td>${row.DispDate || ''}</td>
                            <td>${row.HN || ''}</td><td>${row.Name || ''}</td><td>${row.Amount || ''}</td><td>${row.Reimb || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-billdisp-body').innerHTML = hDisp;

                    // DispensedItems Tab population
                    let hDispItems = '';
                    (res.dispenseditems || []).forEach(row => {
                        hDispItems += `<tr>
                            <td>${row.DispID || ''}</td><td>${row.PrescID || ''}</td><td>${row.ItemSeq || ''}</td><td>${row.LocalCd || ''}</td>
                            <td>${row.StdCd || ''}</td><td>${row.Qty || ''}</td><td>${row.PrdCat || ''}</td><td>${row.Reimb || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-dispenseditems-body').innerHTML = hDispItems;

                    // OPServices Tab population
                    let hOps = '';
                    (res.opservices || []).forEach(row => {
                        hOps += `<tr>
                            <td>${row.HN || ''}</td><td>${row.SvDate || ''}</td><td>${row.Class || ''}</td><td>${row.CareType || ''}</td>
                            <td>${row.InvNo || ''}</td><td>${row.PrePay || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-opservices-body').innerHTML = hOps;

                    // OPDiagnoses Tab population
                    let hDiag = '';
                    (res.opdiagnoses || []).forEach(row => {
                        hDiag += `<tr>
                            <td>${row.HN || ''}</td><td>${row.SvDate || ''}</td><td>${row.DiagType || ''}</td><td>${row.DiagCode || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-opdiagnoses-body').innerHTML = hDiag;

                    // If errors exist, disable download button
                    const errorCount = auditIssues.filter(i => i.severity === 'error').length;
                    const btnDownload = document.getElementById('btnDownloadSSOP');
                    if (errorCount > 0) {
                        btnDownload.disabled = true;
                        btnDownload.innerHTML = `<i class="bi bi-x-circle me-1"></i> กรุณาแก้ไขข้อผิดพลาด (${errorCount} รายการ)`;
                        btnDownload.className = 'btn btn-danger px-4';
                    } else {
                        btnDownload.disabled = false;
                        btnDownload.innerHTML = `<i class="bi bi-download me-1"></i> ยืนยันการดาวน์โหลด SSOP (.zip)`;
                        btnDownload.className = 'btn btn-success px-4';
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด',
                        text: res.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูลพรีวิว'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'ผิดพลาด',
                    text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'ไม่สามารถประมวลผลคำขอได้'
                });
            }
        });
    };

    window.downloadSSOPExportZip = function() {
        const sessionId = document.getElementById('export_session_id').value;
        const stationId = document.getElementById('export_station_id').value;
        const tflag = document.getElementById('export_tflag').value;

        // Redirect/Download trigger
        const queryParams = $.param({
            vns: selectedVnsForExport,
            session_id: sessionId,
            station_id: stationId,
            tflag: tflag
        });
        window.location.href = "{{ url('api/ssop_export_download') }}?" + queryParams;

        $('#ssopPreviewModal').modal('hide');
        Swal.fire({
            icon: 'success',
            title: 'สร้างไฟล์นำส่งเรียบร้อยแล้ว!',
            text: 'ดาวน์โหลดไฟล์นำส่ง SSOP สำเร็จแล้ว',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            // Refresh tables
            loadDashboard({
                budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                skip_chart: 1
            });
        });
    };

    $(document).ready(function () {
        loadDashboard({
            budget_year: "{{ $budget_year }}",
            start_date: "{{ $start_date }}",
            end_date: "{{ $end_date }}"
        });

        $(document).on('submit', '#form_budget_year', function(e) {
            e.preventDefault();
            loadDashboard({
                budget_year: $(this).find('select[name="budget_year"]').val()
            });
        });

        $(document).on('submit', '#form_indiv', function(e) {
            e.preventDefault();
            loadDashboard({
                budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
                start_date: $(this).find('#start_date').val(),
                end_date: $(this).find('#end_date').val(),
                skip_chart: 1
            });
        });
    });
  </script>
@endpush
