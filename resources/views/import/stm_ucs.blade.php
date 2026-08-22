@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card accent-9">
                <div class="card-body">
                    <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/stm_ucs_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-excel me-2 text-success"></i> นำเข้าไฟล์ STM (Excel Only)</h6>
                            <p class="text-muted small">เลือกไฟล์ Excel (.xlsx, .xls) ได้ไม่จำกัดจำนวนไฟล์</p>
                        </div>
                        
                        <div class="input-group mb-0">
                            <input class="form-control" id="formFile" type="file" name="files[]" multiple accept=".xlsx,.xls" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-success px-3.5" type="submit" style="border-radius: 0;">
                                <i class="bi bi-cloud-upload me-1.5"></i> นำเข้าข้อมูล
                            </button>
                            <button type="button" class="btn btn-primary px-3.5 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#eclaimBotModal" style="border-radius: 0 10px 10px 0; background: linear-gradient(135deg, #0284c7, #0369a1); border: none;">
                                <i class="bi bi-cloud-arrow-down-fill me-1.5"></i> ดึงจาก e-Claim
                            </button>
                        </div>

                        @if ($message = Session::get('stm_success'))
                            <div class="alert alert-success border-0 shadow-sm py-2 mb-0 mt-3">
                                <i class="bi bi-check-circle-fill me-2"></i> <strong>{{ $message }}</strong>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header & Search -->
    <div class="page-header-box">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-cloud-arrow-down-fill text-success me-2"></i>
                ข้อมูล Statement ประกันสุขภาพ UCS [OP-IP]
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณประจำปัจจุบัน: {{ $budget_year }}</div>
            <div class="mt-2 d-flex gap-2">
                <a href="{{ url('/import/stm_ucs_detail_opd') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-person-badge me-1"></i> รายละเอียด OPD
                </a>
                <a href="{{ url('/import/stm_ucs_detail_ipd') }}" class="btn btn-danger btn-sm rounded-pill px-3">
                    <i class="bi bi-hospital me-1"></i> รายละเอียด IPD
                </a>
                <button type="button" class="btn btn-info btn-sm rounded-pill px-3 text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#chartModal" id="btnShowChart">
                    <i class="bi bi-bar-chart-fill me-1"></i> กราฟสรุปรายเดือน
                </button>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">ปีงบประมาณ:</span>
                <select class="form-select form-select-sm" name="budget_year" style="width: 160px; border-radius: 8px;">
                    @foreach ($budget_year_select as $row)
                        <option value="{{ $row->LEAVE_YEAR_ID }}"
                            {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                            {{ $row->LEAVE_YEAR_NAME }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="stm_ucs" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" width="20%">ชื่อ File</th>
                            <th class="text-center">Dep</th>
                            <th class="text-center">จำนวน REP</th>
                            <th class="text-center">จำนวนราย</th>
                            <th class="text-center">เรียกเก็บ</th>
                            <th class="text-center">ชดเชยสุทธิ</th>
                            <th class="text-center">เลขงวด</th>
                                <th class="text-center">เลขที่ใบเสร็จ</th>
                                <th class="text-center">วันที่ออกใบเสร็จ</th>
                                <th class="text-center">ผู้ออกใบเสร็จ</th>
                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_receipt == 'Y')
                                <th class="text-center" width="15%">การจัดการ</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stm_ucs as $row)
                        <tr>
                            <td class="small fw-bold text-dark">{{ $row->stm_filename }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->dep }}</span></td>
                            <td class="text-center">{{ $row->repno }}</td>
                            <td class="text-end fw-bold">{{ number_format($row->count_cid) }}</td>
                            <td class="text-end text-muted">{{ number_format($row->charge,2) }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($row->receive_total,2) }}</td>
                            <td class="text-end text-primary fw-bold">{{ $row->round_no }}</td>
                                <td class="text-center text-primary fw-bold">{{ $row->receive_no }}</td>
                                <td class="text-center small">{{ $row->receipt_date }}</td>
                                <td class="text-center small text-muted">{{ $row->receipt_by }}</td>
                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_receipt == 'Y')
                                <td class="text-center text-nowrap">
                                    <div class="d-flex justify-content-center gap-2">
                                        @if(!empty($row->round_no))
                                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_receipt == 'Y')
                                                <button type="button"
                                                    class="btn btn-xs {{ $row->receive_no ? 'btn-outline-warning btn-edit-receipt' : 'btn-outline-success btn-new-receipt' }} rounded-pill px-2"
                                                    data-round="{{ $row->round_no }}"
                                                    data-receive="{{ $row->receive_no }}"
                                                    data-date="{{ $row->receipt_date }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#receiptModal"
                                                    title="{{ $row->receive_no ? 'แก้ไข' : 'ออกใบเสร็จ' }}">
                                                    <i class="bi {{ $row->receive_no ? 'bi-pencil-square' : 'bi-plus-circle' }} me-1"></i>
                                                    {{ $row->receive_no ? 'แก้ไข' : 'ออกใบเสร็จ' }}
                                                </button>
                                            @endif
                                            
                                            @if(Auth::user()->status == 'admin')
                                                <button type="button"
                                                    class="btn btn-xs btn-outline-danger rounded-pill px-2 btn-action-delete"
                                                    data-filename="{{ $row->stm_filename }}"
                                                    data-type="stm_ucs"
                                                    title="ลบข้อมูลนำเข้า">
                                                    <i class="bi bi-trash-fill me-1"></i> ลบ
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
 


{{-- Modal: Monthly Summary Chart --}}
<div class="modal fade" id="chartModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon-box icon-bg-1 mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background-color: #0284c7; border-radius: 12px; color: white;">
                        <i class="bi bi-bar-chart-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="db_title">Dashboard</h5>
                        <div class="text-muted small" id="db_subtitle">ยอดชดเชยสุทธิรายเดือน Statement ประกันสุขภาพ UCS</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-4">
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                            <i class="bi bi-calendar3 me-1"></i> ข้อมูลรายงวดรับเงิน
                        </span>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small text-nowrap">ปีงบประมาณ:</span>
                                <select class="form-select shadow-sm text-center" id="modal_filter_budget_year" style="width: 170px; border-radius: 8px;">
                                    @foreach ($budget_year_select as $row)
                                        <option value="{{ $row->LEAVE_YEAR_ID }}"
                                            {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                            {{ $row->LEAVE_YEAR_NAME }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="loading_spinner" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
                </div>
                <div style="height: 450px; width: 100%;" id="chart_container">
                    <div id="monthlySummaryChart" style="height: 100%; width: 100%;"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal ออกใบเสร็จ --}}
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalTitle">
                    ออกใบเสร็จรับเงิน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="round_no">
                <div class="mb-2">
                    <label class="form-label">เลขที่ใบเสร็จ</label>
                    <input type="text" class="form-control" id="receive_no">
                </div>
                <div class="mb-2">
                    <label class="form-label">วันที่ออกใบเสร็จ</label>
                    <input type="hidden" id="receipt_date" name="receipt_date">
                    <input type="text" class="form-control datepicker_th" id="receipt_date_display" style="width: 120px;" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnSaveReceipt">
                    บันทึก
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>
{{-- End Modal --}}

<!-- Modal: e-Claim Automation Bot (ThaiD SSO & Direct Import) -->
<div class="modal fade" id="eclaimBotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0284c7, #0369a1); border-radius: 14px; color: white; box-shadow: 0 4px 12px rgba(2,132,199,0.4);">
                        <i class="bi bi-robot fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">ดึงข้อมูล Statement (STM UCS) จาก e-Claim อัตโนมัติ</h5>
                        <div class="text-light-50 small mt-0.5 d-flex align-items-center gap-2">
                            <span>ระบบเชื่อมต่อตรง eclaim.nhso.go.th</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 10px;">
                                <i class="bi bi-shield-lock-fill me-1"></i> ThaiD SSO Ready
                            </span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Section 1: e-Claim Session Connection (Cookie / Token) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div id="eclaimAuthStatusIcon" class="badge rounded-circle p-2 bg-warning-subtle text-warning">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" id="eclaimAuthStatusText">ยังไม่ได้เชื่อมต่อกับระบบ e-Claim</div>
                                    <div class="text-muted small" id="eclaimAuthStatusSub">ระบุ e-Claim Session Cookie (JSESSIONID) หรือกดซิงก์จาก Extension เพื่อเชื่อมต่อ</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none" id="btnToggleTokenInput">
                                    <i class="bi bi-pencil-square me-1"></i> เปลี่ยน Token
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-none" id="btnEclaimLogout">
                                    <i class="bi bi-box-arrow-right me-1"></i> ตัดการเชื่อมต่อ
                                </button>
                            </div>
                        </div>

                        <!-- Session Input Form (Shows when disconnected or editing) -->
                        <div id="sessionInputContainer" class="pt-3 mt-2 border-top">
                            <label class="form-label fw-bold small text-dark mb-1">
                                <i class="bi bi-key-fill text-primary me-1"></i> e-Claim Session Cookie (JSESSIONID):
                            </label>

                            <div class="input-group input-group-sm mt-1">
                                <span class="input-group-text bg-white"><i class="bi bi-cookie text-muted"></i></span>
                                <input type="text" class="form-control form-control-sm" id="eclaimTokenInput" placeholder="วางค่า e-Claim Session Cookie (JSESSIONID)">
                                <button class="btn btn-primary px-3 fw-bold" type="button" id="btnSaveToken">
                                    <i class="bi bi-link-45deg me-1"></i> เชื่อมต่อ Session
                                </button>
                            </div>
                            <div class="d-flex align-items-center gap-3 mt-2 text-muted small" style="font-size: 11.5px;">
                                <span><i class="bi bi-info-circle text-primary me-1"></i><strong>วิธีเชื่อมต่อ:</strong></span>
                                <span>1. ล็อกอินเข้าเว็บ e-Claim ผ่าน ThaiD ในเบราว์เซอร์</span>
                                <span>2. คัดลอกค่า <code>JSESSIONID</code> มาวางที่นี่ (หรือกดปุ่ม "ซิงก์ Session" ใน Extension)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Search & Filter Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">ปีงบประมาณ</label>
                                <select class="form-select form-select-sm rounded-3" id="botBudgetYear">
                                    @foreach ($budget_year_select as $row)
                                        <option value="{{ $row->LEAVE_YEAR_ID }}" {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                            {{ $row->LEAVE_YEAR_NAME }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">งวดเดือน</label>
                                <select class="form-select form-select-sm rounded-3" id="botMonth">
                                    <option value="10">ตุลาคม (ต้นปีงบ)</option>
                                    <option value="11">พฤศจิกายน</option>
                                    <option value="12">ธันวาคม</option>
                                    <option value="01">มกราคม</option>
                                    <option value="02">กุมภาพันธ์</option>
                                    <option value="03">มีนาคม</option>
                                    <option value="04">เมษายน</option>
                                    <option value="05">พฤษภาคม</option>
                                    <option value="06">มิถุนายน</option>
                                    <option value="07">กรกฎาคม</option>
                                    <option value="08" selected>สิงหาคม</option>
                                    <option value="09">กันยายน (สิ้นปีงบ)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">ประเภทสิทธิ์</label>
                                <input type="text" class="form-control form-control-sm rounded-3 bg-light" value="UCS (ประกันสุขภาพถ้วนหน้า)" readonly>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm rounded-pill w-100 shadow-sm py-1.5" id="btnBotSearch">
                                    <i class="bi bi-search me-1"></i> ค้นหาใน e-Claim
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Statement List Table Preview -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-list-check text-success me-2"></i> รายการ Statement ที่พบใน e-Claim
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border px-2.5 py-1" id="botResultBadge">พบ 0 รายการ</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="botStatementTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="5%">
                                        <input class="form-check-input" type="checkbox" id="checkAllBot">
                                    </th>
                                    <th class="text-center">งวดที่ (Round)</th>
                                    <th>ชื่อไฟล์ STM</th>
                                    <th class="text-center">ประเภท</th>
                                    <th class="text-center">วันที่ออก</th>
                                    <th class="text-end">จำนวนราย</th>
                                    <th class="text-end">เรียกเก็บ</th>
                                    <th class="text-end">ชดเชยสุทธิ</th>
                                    <th class="text-center">สถานะใน RIMS</th>
                                </tr>
                            </thead>
                            <tbody id="botStatementTableBody">
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                        กดปุ่ม "ค้นหาใน e-Claim" เพื่อดึงรายการ Statement
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-0 p-3 px-4 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <span id="selectedCountText" class="fw-bold text-primary">เลือก 0 รายการ</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 border" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" id="btnStartImportBot" disabled>
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> เริ่มนำเข้า RIMS ทันที
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert: Success -->
@if (session('stm_success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            title: 'นำเข้าสำเร็จ!',
            text: "{!! session('stm_success') !!}",
            icon: 'success',
            confirmButtonText: 'ปิด',
            confirmButtonColor: '#673ab7',
            customClass: {
                confirmButton: 'btn btn-primary btn-sm px-4'
            },
            allowOutsideClick: false
        });
    });
</script>
@endif
<!-- SweetAlert: Error -->
@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'ผิดพลาด',
                text: @json(session('error')),
                confirmButtonText: 'ปิด'
            });
        });
    </script>
@endif
    
@endsection

@push('scripts')
  <script>
        // Global helper for file upload form
        function showLoadingAlert() {
            Swal.fire({
                title: 'กำลังนำเข้าข้อมูล...',
                text: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
        }

        window.simulateProcess = function(event) {
            event.preventDefault(); 
            const fileInput = document.querySelector('input[type="file"]');
            if (!fileInput.files || fileInput.files.length === 0) {
                Swal.fire({
                    title: 'แจ้งเตือน',
                    text: 'กรุณาเลือกไฟล์ก่อนนำเข้า',
                    icon: 'warning',
                    confirmButtonText: 'ปิด',
                    confirmButtonColor: '#673ab7',
                    customClass: {
                        confirmButton: 'btn btn-primary btn-sm px-4'
                    }
                });
                return;
            }
            
            showLoadingAlert();
            document.getElementById('importForm').submit();
        };

        $(document).ready(function () {
            // Initialize Datepicker Thai
            $('.datepicker_th').datepicker({
                format: 'd M yyyy', // Matches DateThai() helper output
                todayBtn: "linked",
                todayHighlight: true,
                autoclose: true,
                language: 'th-th',
                thaiyear: true,
                zIndexOffset: 1050
            });

            // Sync Changes to Hidden Inputs for Backend (YYYY-MM-DD)
            $('.datepicker_th').on('changeDate', function(e) {
                var date = e.date;
                var targetId = $(this).attr('id').replace('_display', '');
                var hiddenInput = $('#' + targetId);
                
                if(date) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear(); // Gregorian
                    hiddenInput.val(year + "-" + month + "-" + day);
                } else {
                    hiddenInput.val('');
                }
            });

            // Event delegation for receipt modal buttons (needed because DataTable pagination redraws the DOM)
            $(document).on('click', '.btn-new-receipt, .btn-edit-receipt', function () {
                $('#round_no').val($(this).data('round'));
                $('#receive_no').val($(this).data('receive') || '');
                $('#receipt_date').val($(this).data('date') || '');
                
                var rDate = $(this).data('date');
                if(rDate) {
                    $('#receipt_date_display').datepicker('setDate', new Date(rDate));
                } else {
                    $('#receipt_date_display').datepicker('clearDates');
                }
            });

            // AJAX Save Receipt
            $('#btnSaveReceipt').on('click', function () {
                let round_no     = $('#round_no').val();
                let receive_no   = $('#receive_no').val();
                let receipt_date = $('#receipt_date').val();
                if (!receive_no || !receipt_date) {
                    Swal.fire('แจ้งเตือน','กรุณากรอกข้อมูลให้ครบ','warning');
                    return;
                }
                fetch("{{ url('import/stm_ucs_updateReceipt') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        round_no: round_no,
                        receive_no: receive_no,
                        receipt_date: receipt_date
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกสำเร็จ',
                            html: `
                                <p><strong>เลขที่ใบเสร็จ:</strong> ${res.receive_no}</p>
                                <p><strong>วันที่ออก:</strong> ${res.receipt_date}</p>
                            `,
                            confirmButtonText: 'ปิด'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                });
            });

            $('#stm_ucs').DataTable({
                ordering: false,   // 🔥 ปิด sorting
                dom: '<"row mb-3"' +
                        '<"col-md-6"l>' +
                        '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' +
                    '>' +
                    'rt' +
                    '<"row mt-3"' +
                        '<"col-md-6"i>' +
                        '<"col-md-6"p>' +
                    '>',
                buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'ข้อมูล Statement ประกันสุขภาพ UCS [OP-IP]'
                }
                ],
                language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                paginate: {
                    previous: "ก่อนหน้า",
                    next: "ถัดไป"
                }
                }
            });

            // --- Chart Modal Handling ---
            let monthlyChart = null;

            // Load ApexCharts CDN dynamically if it isn't loaded
            if (typeof ApexCharts === 'undefined') {
                const chartScript = document.createElement('script');
                chartScript.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                chartScript.onload = function() {
                    initChartEvent();
                };
                document.head.appendChild(chartScript);
            } else {
                initChartEvent();
            }

            function initChartEvent() {
                $('#chartModal').on('shown.bs.modal', function () {
                    loadChartData();
                });

                $('#modal_filter_budget_year').on('change', function () {
                    loadChartData();
                });
            }

            function loadChartData() {
                const budgetYear = $('#modal_filter_budget_year').val();
                const budgetYearText = $('#modal_filter_budget_year option:selected').text().trim();

                $('#db_subtitle').text(`ยอดชดเชยสุทธิรายงวด Statement ประกันสุขภาพ UCS ปีงบประมาณ: ${budgetYearText}`);

                $('#chart_container').addClass('d-none');
                $('#loading_spinner').removeClass('d-none');

                $.ajax({
                    url: "{{ route('import.stm_ucs.chart-data') }}",
                    method: "GET",
                    data: {
                        budget_year: budgetYear
                    },
                    success: function (res) {
                        $('#loading_spinner').addClass('d-none');
                        $('#chart_container').removeClass('d-none');
                        renderChart(res.labels, res.op_totals, res.ip_totals);
                    },
                    error: function () {
                        $('#loading_spinner').addClass('d-none');
                        $('#chart_container').removeClass('d-none');
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถดึงข้อมูลกราฟได้', confirmButtonColor: '#d33' });
                    }
                });
            }

            function renderChart(labels, opTotals, ipTotals) {
                if (monthlyChart) {
                    monthlyChart.destroy();
                }

                const options = {
                    series: [
                        {
                            name: 'OP (ผู้ป่วยนอก)',
                            data: opTotals
                        },
                        {
                            name: 'IP (ผู้ป่วยใน)',
                            data: ipTotals
                        }
                    ],
                    chart: {
                        height: 430,
                        type: 'area',
                        toolbar: { show: false }
                    },
                    markers: { size: 4 },
                    colors: ['#10b981', '#ef4444'],
                    fill: {
                        type: "gradient",
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.3,
                            opacityTo: 0.05,
                            stops: [0, 90, 100]
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val ? new Intl.NumberFormat('th-TH').format(val) : '';
                        },
                        style: {
                            fontSize: '11px',
                            fontWeight: 'bold'
                        }
                    },
                    stroke: { 
                        curve: 'smooth', 
                        width: 2 
                    },
                    xaxis: {
                        categories: labels
                    },
                    yaxis: {
                        labels: {
                            formatter: function (value) {
                                return value.toLocaleString('th-TH') + ' ฿';
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return new Intl.NumberFormat('th-TH').format(val) + ' บาท';
                            }
                        }
                    }
                };

                monthlyChart = new ApexCharts(document.querySelector("#monthlySummaryChart"), options);
                monthlyChart.render();
            }
        });
    
        // Deletion handler for STM index
        $(document).on('click', '.btn-action-delete', function () {
            var btn = $(this);
            var filename = btn.data('filename');
            var type = btn.data('type');

            Swal.fire({
                title: 'ยืนยันการลบข้อมูล?',
                text: 'คุณต้องการลบข้อมูลนำเข้าของไฟล์ ' + filename + ' ใช่หรือไม่? การลบนี้ไม่สามารถย้อนกลับได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังลบข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('import.stm.delete') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            filename: filename,
                            type: type
                        },
                        success: function (res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: res.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ผิดพลาด',
                                    text: res.message || 'เกิดข้อผิดพลาดในการลบข้อมูล',
                                    confirmButtonText: 'ปิด',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        },
                        error: function (xhr) {
                            var errMsg = 'เกิดข้อผิดพลาดในการลบข้อมูล';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errMsg = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'ผิดพลาด',
                                text: errMsg,
                                confirmButtonText: 'ปิด',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });

        // ==========================================
        // e-Claim Automation (Session Cookie / Direct Sync)
        // ==========================================
        // 2) Check e-Claim Authentication Status
        // ==========================================
        function checkEclaimStatus() {
            $.ajax({
                url: "{{ route('import.eclaim-bot.status') }}",
                method: "POST",
                data: { _token: "{{ csrf_token() }}" },
                success: function(res) {
                    if (res.connected) {
                        $('#eclaimAuthStatusIcon').removeClass('bg-warning-subtle text-warning').addClass('bg-success-subtle text-success')
                            .html('<i class="bi bi-check-circle-fill fs-5"></i>');
                        $('#eclaimAuthStatusText').html('เชื่อมต่อสำเร็จ: <span class="text-primary">' + res.user + '</span>');
                        $('#eclaimAuthStatusSub').html('สถานะ: ออนไลน์พร้อมดึงข้อมูล | เชื่อมต่อเมื่อ: ' + res.connected_at);
                        $('#btnEclaimLogout').removeClass('d-none');
                        $('#btnToggleTokenInput').removeClass('d-none');
                        $('#sessionInputContainer').addClass('d-none');
                        $('#btnBotSearch').prop('disabled', false);
                    } else {
                        var savedToken = localStorage.getItem('eclaim_session_token');
                        if (savedToken) {
                            $.ajax({
                                url: "{{ route('import.eclaim-bot.save-token') }}",
                                method: "POST",
                                data: { _token: "{{ csrf_token() }}", token: savedToken },
                                success: function(saveRes) {
                                    if (saveRes.status === 'success') {
                                        checkEclaimStatus();
                                    }
                                }
                            });
                            return;
                        }

                        $('#eclaimAuthStatusIcon').removeClass('bg-success-subtle text-success').addClass('bg-warning-subtle text-warning')
                            .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                        $('#eclaimAuthStatusText').text('ยังไม่ได้เชื่อมต่อกับระบบ e-Claim');
                        $('#eclaimAuthStatusSub').text('ระบุ e-Claim Session Cookie (JSESSIONID) หรือกดซิงก์จาก Extension เพื่อเริ่มดึงข้อมูล');
                        $('#btnEclaimLogout').addClass('d-none');
                        $('#btnToggleTokenInput').addClass('d-none');
                        $('#sessionInputContainer').removeClass('d-none');
                    }
                }
            });
        }

        // When eclaimBotModal opens
        $('#eclaimBotModal').on('show.bs.modal', function () {
            checkEclaimStatus();
        });

        // Toggle Token Input field
        $('#btnToggleTokenInput').on('click', function () {
            $('#sessionInputContainer').toggleClass('d-none');
        });

        // Save Session Token
        $('#btnSaveToken').on('click', function () {
            var token = $('#eclaimTokenInput').val();
            if (!token) {
                Swal.fire('แจ้งเตือน', 'กรุณากรอก Session Token / Cookie', 'warning');
                return;
            }

            localStorage.setItem('eclaim_session_token', token);

            $.ajax({
                url: "{{ route('import.eclaim-bot.save-token') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    token: token
                },
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'เชื่อมต่อสำเร็จ!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        checkEclaimStatus();
                    }
                }
            });
        });

        // Disconnect / Logout e-Claim
        $('#btnEclaimLogout').on('click', function () {
            Swal.fire({
                title: 'ยืนยันตัดการเชื่อมต่อ?',
                text: 'ระบบจะล้าง Session e-Claim ออกจากระบบทุกหน้าจอ',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ตัดการเชื่อมต่อ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.removeItem('eclaim_session_token');
                    $.ajax({
                        url: "{{ route('import.eclaim-bot.logout') }}",
                        method: "POST",
                        data: { _token: "{{ csrf_token() }}" },
                        success: function () {
                            checkEclaimStatus();
                            $('#eclaimTokenInput').val('');
                            $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-muted">กดปุ่ม "ค้นหาใน e-Claim" เพื่อดึงรายการ Statement</td></tr>');
                            Swal.fire({
                                icon: 'success',
                                title: 'ตัดการเชื่อมต่อแล้ว',
                                text: 'ตัดการเชื่อมต่อกับ e-Claim เรียบร้อย',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    });
                }
            });
        });

        // Auto-connect if URL has ?eclaim_token=...
        var urlParams = new URLSearchParams(window.location.search);
        var urlToken = urlParams.get('eclaim_token');
        if (urlToken) {
            $.ajax({
                url: "{{ route('import.eclaim-bot.save-token') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    token: urlToken
                },
                success: function (res) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                    $('#eclaimBotModal').modal('show');
                    Swal.fire({
                        icon: 'success',
                        title: 'เชื่อมต่อสำเร็จ!',
                        text: 'เชื่อมต่อกับระบบ e-Claim เรียบร้อยแล้ว พร้อมค้นหาและนำเข้าข้อมูลได้ทันที',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    checkEclaimStatus();
                }
            });
        }

        // 5) Search Statements in e-Claim
        $('#btnBotSearch').on('click', function () {
            var btn = $(this);
            var year = $('#botBudgetYear').val();
            var month = $('#botMonth').val();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> กำลังค้นหา...');
            $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>กำลังดึงข้อมูลจาก e-Claim...</td></tr>');

            $.ajax({
                url: "{{ route('import.eclaim-bot.search') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    budget_year: year,
                    month: month,
                    claim_type: 'stm_ucs'
                },
                success: function (res) {
                    btn.prop('disabled', false).html('<i class="bi bi-search me-1"></i> ค้นหาใน e-Claim');
                    if (res.status === 'success') {
                        $('#botResultBadge').text('พบ ' + res.count + ' รายการ');
                        renderBotStatementTable(res.data);
                    }
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="bi bi-search me-1"></i> ค้นหาใน e-Claim');
                    $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-danger"><i class="bi bi-exclamation-octagon fs-4 d-block mb-1"></i>เกิดข้อผิดพลาดในการดึงข้อมูลจาก e-Claim</td></tr>');
                }
            });
        });

        function renderBotStatementTable(data) {
            if (!data || data.length === 0) {
                $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-muted"><i class="bi bi-info-circle fs-4 d-block mb-1"></i>ไม่พบข้อมูล Statement ในงวดเดือนที่เลือกจาก e-Claim</td></tr>');
                $('#btnStartImportBot').prop('disabled', true);
                return;
            }

            var html = '';
            data.forEach(function (row, idx) {
                var badgeStatus = row.is_imported
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1"><i class="bi bi-check-circle-fill me-1"></i>นำเข้าแล้ว (' + Number(row.imported_count).toLocaleString() + ' ราย)</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1"><i class="bi bi-clock-history me-1"></i>ยังไม่เคยนำเข้า</span>';

                var chargeDisplay = row.charge_total > 0 ? Number(row.charge_total).toLocaleString(undefined, {minimumFractionDigits: 2}) : '-';
                var receiveDisplay = row.receive_total > 0 ? Number(row.receive_total).toLocaleString(undefined, {minimumFractionDigits: 2}) : '-';
                var countDisplay = row.count_cid && row.count_cid !== '-' ? Number(row.count_cid).toLocaleString() : '-';

                var itemJson = JSON.stringify(row).replace(/"/g, '&quot;');

                html += '<tr>' +
                    '<td class="text-center"><input class="form-check-input bot-row-check" type="checkbox" value="' + row.round_no + '" data-item="' + itemJson + '"></td>' +
                    '<td class="text-center fw-bold text-primary">' + row.round_no + '</td>' +
                    '<td class="fw-bold small text-dark">' + row.filename + '</td>' +
                    '<td class="text-center"><span class="badge ' + (row.type === 'OPD' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger') + ' rounded-pill px-2">' + row.type + '</span></td>' +
                    '<td class="text-center small text-muted">' + row.issue_date + '</td>' +
                    '<td class="text-end fw-bold">' + countDisplay + '</td>' +
                    '<td class="text-end text-muted">' + chargeDisplay + '</td>' +
                    '<td class="text-end text-success fw-bold">' + receiveDisplay + '</td>' +
                    '<td class="text-center">' + badgeStatus + '</td>' +
                '</tr>';
            });

            $('#botStatementTableBody').html(html);
            updateSelectedCount();
        }

        // Checkbox events
        $('#checkAllBot').on('change', function () {
            $('.bot-row-check').prop('checked', $(this).prop('checked'));
            updateSelectedCount();
        });

        $(document).on('change', '.bot-row-check', function () {
            updateSelectedCount();
        });

        function updateSelectedCount() {
            var selected = $('.bot-row-check:checked').length;
            $('#selectedCountText').text('เลือก ' + selected + ' รายการ');
            $('#btnStartImportBot').prop('disabled', selected === 0);
        }

        // 6) Trigger Auto Import from Bot
        $('#btnStartImportBot').on('click', function () {
            var selectedItems = [];
            $('.bot-row-check:checked').each(function () {
                var itemData = $(this).data('item');
                if (typeof itemData === 'string') {
                    try { itemData = JSON.parse(itemData); } catch(e) {}
                }
                selectedItems.push(itemData);
            });

            if (selectedItems.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกไฟล์ Statement อย่างน้อย 1 รายการ', 'warning');
                return;
            }

            Swal.fire({
                title: 'ยืนยันการนำเข้าข้อมูล?',
                text: 'ระบบจะทำการดาวน์โหลดไฟล์จาก e-Claim และนำเข้า Statement จำนวน ' + selectedItems.length + ' งวดเข้าสู่ฐานข้อมูล RIMS ทันที',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '🚀 เริ่มนำเข้าทันที',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    var total = selectedItems.length;
                    var successCount = 0;
                    var failedCount = 0;

                    Swal.fire({
                        title: 'กำลังดาวน์โหลดและนำเข้าข้อมูล...',
                        html: `
                            <div class="my-3 text-start">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span id="importStmProgressStatusText" class="small fw-bold text-dark text-truncate" style="max-width: 250px;">กำลังเริ่มต้น...</span>
                                    <span id="importStmProgressPercentText" class="small fw-bold text-success">0%</span>
                                </div>
                                <div class="progress" style="height: 22px; border-radius: 11px; background-color: #e2e8f0;">
                                    <div id="importStmProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; font-size: 11.5px; font-weight: bold;">0%</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 text-muted small">
                                    <span id="importStmTimer"><i class="bi bi-clock-history me-1"></i> เวลา: 0 วิ</span>
                                    <span id="importStmProgressDetail">สำเร็จ <b id="importStmSuccessCount" class="text-success">0</b> / ${total} ไฟล์</span>
                                </div>
                                <div class="alert alert-light border py-1.5 px-2 mt-2 mb-0 small text-muted" style="font-size: 11px;">
                                    <i class="bi bi-info-circle text-primary me-1"></i> ระบบกำลังดาวน์โหลด Statement Excel จาก e-Claim และประมวลผลเข้าฐานข้อมูล
                                </div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: async () => {
                            var elapsedSec = 0;
                            var timerInterval = setInterval(function () {
                                elapsedSec++;
                                $('#importStmTimer').html('<i class="bi bi-clock-history me-1"></i> เวลา: ' + elapsedSec + ' วิ');
                            }, 1000);

                            for (var i = 0; i < total; i++) {
                                var item = selectedItems[i];
                                var itemLabel = item.round_no ? ('งวดที่ ' + item.round_no) : (item.filename ? item.filename : ('ไฟล์ที่ ' + (i + 1)));
                                
                                $('#importStmProgressStatusText').text(`กำลังดึง ${itemLabel} (${i + 1}/${total})`);
                                
                                try {
                                    var res = await $.ajax({
                                        url: "{{ route('import.eclaim-bot.import') }}",
                                        method: "POST",
                                        data: {
                                            _token: "{{ csrf_token() }}",
                                            items: [item]
                                        }
                                    });
                                    
                                    if (res && res.status === 'success') {
                                        successCount++;
                                    } else {
                                        failedCount++;
                                    }
                                } catch (err) {
                                    console.error("Error importing STM item: ", item, err);
                                    failedCount++;
                                }
                                
                                var percent = Math.round(((i + 1) / total) * 100);
                                $('#importStmProgressBar').css('width', percent + '%').text(percent + '%');
                                $('#importStmProgressPercentText').text(percent + '%');
                                $('#importStmSuccessCount').text(successCount);
                            }

                            clearInterval(timerInterval);
                            
                            if (successCount > 0) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'นำเข้าสำเร็จ!',
                                    html: `นำเข้าข้อมูล Statement UCS จาก e-Claim สำเร็จรวม <b>${successCount}</b> ไฟล์ ${failedCount > 0 ? `<br><span class="text-danger small">(ไม่สำเร็จ ${failedCount} ไฟล์)</span>` : ''}`,
                                    confirmButtonText: 'ตกลง',
                                    confirmButtonColor: '#10b981'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ไม่สำเร็จ',
                                    text: 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement ที่เลือกได้ กรุณาตรวจสอบ Session e-Claim',
                                    confirmButtonText: 'ปิด',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        }
                    });
                }
            });
        });
    </script>
@endpush
