@extends('layouts.app')
 
@section('content')
<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card accent-9">
                <div class="card-body">
                    <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/stm_pvt_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-excel me-2 text-success"></i> นำเข้าไฟล์ STM สิทธิ์ครูเอกชน PVT (Excel Only)</h6>
                            <p class="text-muted small">เลือกไฟล์ Excel (.xlsx, .xls) ได้ไม่จำกัดจำนวนไฟล์</p>
                        </div>
                        
                        <div class="input-group mb-3">
                            <input class="form-control" id="formFile" type="file" name="files[]" multiple accept=".xlsx,.xls" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-success px-4" type="submit" style="border-radius: 0;">
                                <i class="bi bi-cloud-upload me-1.5"></i> นำเข้าข้อมูล
                            </button>
                            <button type="button" class="btn btn-primary px-3.5 shadow-sm text-nowrap fw-bold" data-bs-toggle="modal" data-bs-target="#eclaimStmPvtBotModal" style="border-radius: 0 10px 10px 0; background: linear-gradient(135deg, #0284c7, #0369a1); border: none;">
                                <i class="bi bi-cloud-arrow-down-fill me-1.5"></i> ดึงจาก e-Claim
                            </button>
                        </div>

                        @if ($message = Session::get('stm_success'))
                            <div class="alert alert-success border-0 shadow-sm py-2 mb-0">
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
                ข้อมูล Statement เบิกจ่ายตรง สิทธิ์ครูเอกชน PVT
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณประจำปัจจุบัน: {{ $budget_year }}</div>
            <div class="mt-2 d-flex gap-2">
                <a href="{{ url('/import/stm_pvt_detail_opd') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-person-badge me-1"></i> รายละเอียด OPD
                </a>
                <a href="{{ url('/import/stm_pvt_detail_ipd') }}" class="btn btn-danger btn-sm rounded-pill px-3">
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
                <table id="stm_pvt" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">Filename</th> 
                            <th class="text-center">Dep</th>                                
                            <th class="text-center">จำนวน REP</th> 
                            <th class="text-center">จำนวนราย</th> 
                            <th class="text-center">AdjRW</th>         
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
                        @foreach($stm_pvt as $row)
                        <tr>
                            <td class="small fw-bold text-dark">{{ $row->stm_filename }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->dep }}</span></td>
                            <td class="text-end fw-bold">{{ number_format($row->repno) }}</td>
                            <td class="text-end fw-bold">{{ number_format($row->count_cid) }}</td>
                            <td class="text-end small">{{ number_format($row->sum_adjrw,4) }}</td>
                            <td class="text-end text-muted">{{ number_format($row->sum_charge,2) }}</td>   
                            <td class="text-end text-success fw-bold">{{ number_format($row->sum_receive_total,2) }}</td>  
                            <td class="text-center text-primary fw-bold">{{ $row->round_no }}</td>
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
                                                    data-type="stm_pvt"
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
                        <div class="text-muted small" id="db_subtitle">ยอดชดเชยสุทธิรายเดือน Statement เบิกจ่ายตรง สิทธิ์ครูเอกชน PVT</div>
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

<!-- SweetAlert: Success -->
@if (session('stm_success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'นำเข้าสำเร็จ',
                text: "{!! session('stm_success') !!}",
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
                confirmButtonText: 'ปิด',
                confirmButtonColor: '#673ab7',
                customClass: {
                    confirmButton: 'btn btn-primary btn-sm px-4'
                }
            });
        });
    </script>
@endif


<!-- Modal: e-Claim Statement PVT (ครูเอกชน) Bot Automation -->
<div class="modal fade" id="eclaimStmPvtBotModal" tabindex="-1" aria-labelledby="eclaimStmPvtBotModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header text-white p-3 px-4 border-0" style="background: linear-gradient(135deg, #0f172a 0%, #0369a1 100%) !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-white bg-opacity-10 text-white shadow-sm">
                        <i class="bi bi-robot fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="eclaimStmPvtBotModalLabel">
                            ดึงข้อมูล Statement ครูเอกชน (PVT) อัตโนมัติจาก e-Claim
                        </h5>
                        <div class="small opacity-75 mt-0.5 d-flex align-items-center gap-2">
                            <span>เชื่อมต่อระบบ e-Claim สปสช. (ระบบรายงานสิทธิครูเอกชน) เพื่อค้นหาและนำเข้า Statement อัตโนมัติ</span>
                            <span class="badge rounded-pill bg-white text-dark py-1 px-2 fw-medium" style="font-size: 10.5px;">
                                <i class="bi bi-shield-check text-primary me-1"></i> ThaiD SSO Ready
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
                                <div id="eclaimStmPvtAuthStatusIcon" class="badge rounded-circle p-2 bg-warning-subtle text-warning">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" id="eclaimStmPvtAuthStatusText">ยังไม่ได้เชื่อมต่อกับระบบ e-Claim</div>
                                    <div class="text-muted small" id="eclaimStmPvtAuthStatusSub">เปิดเว็บ e-Claim ใน Chrome แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" ใน Extension เพื่อเชื่อมต่อ</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" id="btnEclaimStmPvtLoginPopup">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบ e-Claim (ThaiD)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="showEclaimExtensionGuide()">
                                    <i class="bi bi-download me-1"></i> ส่วนเสริม Chrome
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-none" id="btnEclaimStmPvtLogout">
                                    <i class="bi bi-box-arrow-right me-1"></i> ตัดการเชื่อมต่อ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Filter & Search in e-Claim -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">ปี (พ.ศ.)</label>
                                <select id="botStmPvtBudgetYear" class="form-select form-select-sm rounded-3">
                                    @php
                                        $currentYear = date('Y') + 543;
                                    @endphp
                                    @for($y = $currentYear + 1; $y >= $currentYear - 4; $y--)
                                        <option value="{{ $y }}" {{ $y == $budget_year ? 'selected' : '' }}>ปีงบประมาณ {{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">งวดเดือน</label>
                                <select id="botStmPvtMonth" class="form-select form-select-sm rounded-3">
                                    <option value="">ทุกเดือน</option>
                                    @php
                                        $months = [
                                            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                                            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                                            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                                        ];
                                        $currM = (int)date('m');
                                    @endphp
                                    @foreach($months as $num => $mname)
                                        <option value="{{ $num }}" {{ $num == $currM ? 'selected' : '' }}>{{ $mname }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">ประเภทผู้ป่วย</label>
                                <select id="botStmPvtPersonType" class="form-select form-select-sm rounded-3">
                                    <option value="1">ผู้ป่วยนอก (OPD)</option>
                                    <option value="2">ผู้ป่วยใน (IPD)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="btnBotStmPvtSearch" class="btn btn-primary btn-sm w-100 rounded-3 fw-bold py-2 shadow-sm" disabled>
                                    <i class="bi bi-search me-1"></i> ค้นหาใน e-Claim
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: List of Statements found in e-Claim -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i> รายการ Statement ครูเอกชน ที่พบบนระบบ e-Claim
                        </h6>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2" id="botStmPvtFoundBadge">0 รายการ</span>
                    </div>
                    <div class="table-responsive" style="max-height: 380px;">
                        <table class="table table-hover align-middle mb-0" id="botStmPvtTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="40" class="text-center">
                                        <input class="form-check-input" type="checkbox" id="checkAllBotStmPvt">
                                    </th>
                                    <th>statement no</th>
                                    <th>งวดเดือน</th>
                                    <th>ปีงบ</th>
                                    <th>รอบ</th>
                                    <th>ประเภท</th>
                                    <th class="text-center">สถานะในระบบ</th>
                                </tr>
                            </thead>
                            <tbody id="botStmPvtTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>
                                        กดปุ่ม "ค้นหาใน e-Claim" เพื่อดึงรายการ Statement ครูเอกชน
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-top px-4 py-3 justify-content-between">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> เลือกรายการที่ต้องการแล้วกด "นำเข้า Statement ที่เลือก"
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" id="btnImportSelectedBotStmPvt" disabled>
                        <i class="bi bi-cloud-arrow-down me-1.5"></i> นำเข้า Statement ที่เลือก (<span id="selectedBotStmPvtCount">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            /* ===== เปิด modal (ออกใหม่ / แก้ไข) ===== */
            document.querySelectorAll('.btn-new-receipt, .btn-edit-receipt')
                .forEach(btn => {
                    btn.addEventListener('click', function () {
                        document.getElementById('round_no').value = this.dataset.round;
                        document.getElementById('receive_no').value = this.dataset.receive ?? '';
                        document.getElementById('receipt_date').value = this.dataset.date ?? '';
                        
                        if(this.dataset.date) {
                            $('#receipt_date_display').datepicker('setDate', new Date(this.dataset.date));
                        } else {
                            $('#receipt_date_display').datepicker('clearDates');
                        }
                    });
                });

            /* ===== บันทึก (AJAX) ===== */
            document.getElementById('btnSaveReceipt')
                .addEventListener('click', function () {
                    let round_no     = document.getElementById('round_no').value;
                    let receive_no   = document.getElementById('receive_no').value;
                    let receipt_date = document.getElementById('receipt_date').value;
                    if (!receive_no || !receipt_date) {
                        Swal.fire('แจ้งเตือน','กรุณากรอกข้อมูลให้ครบ','warning');
                        return;
                    }
                    fetch("{{ url('import/stm_pvt_updateReceipt') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
        });

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

        function simulateProcess(event) {
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
            if (fileInput.files.length > 5) {
                Swal.fire({
                    title: 'แจ้งเตือน',
                    text: 'เลือกไฟล์ได้ไม่จำกัดจำนวนไฟล์',
                    icon: 'error',
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
        }

        $(document).ready(function () {
            // Initialize Datepicker Thai
            $('.datepicker_th').datepicker({
                format: 'd M yyyy',
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
                    var year = date.getFullYear();
                    hiddenInput.val(year + "-" + month + "-" + day);
                } else {
                    hiddenInput.val('');
                }
            });

            $('#stm_pvt').DataTable({
                ordering: false,
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
                    title: 'ข้อมูล Statement เบิกจ่ายตรง สิทธิ์ครูเอกชน PVT'
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

                $('#db_subtitle').text(`ยอดชดเชยสุทธิรายงวด Statement เบิกจ่ายตรง สิทธิ์ครูเอกชน PVT ปีงบประมาณ: ${budgetYearText}`);

                $('#chart_container').addClass('d-none');
                $('#loading_spinner').removeClass('d-none');

                $.ajax({
                    url: "{{ route('import.stm_pvt.chart-data') }}",
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
        // e-Claim Statement PVT Automation (stm_pvt)
        // ==========================================
        function checkEclaimStmPvtStatus() {
            $('#eclaimStmPvtAuthStatusIcon').removeClass('bg-success-subtle text-success bg-warning-subtle text-warning').addClass('bg-secondary-subtle text-secondary')
                .html('<span class="spinner-border spinner-border-sm" role="status"></span>');
            $('#eclaimStmPvtAuthStatusText').text('กำลังตรวจสอบสถานะการเชื่อมต่อ e-Claim...');
            $('#eclaimStmPvtAuthStatusSub').text('ระบบกำลังทดสอบ Session กับ eclaim.nhso.go.th');
            $('#btnBotStmPvtSearch').prop('disabled', true);

            $.ajax({
                url: "{{ route('import.eclaim-bot.status') }}",
                method: "POST",
                data: { _token: "{{ csrf_token() }}" },
                success: function(res) {
                    if (res.connected) {
                        if (window.eclaimRetryTimer_checkEclaimStmPvtStatus) {
                            clearInterval(window.eclaimRetryTimer_checkEclaimStmPvtStatus);
                            window.eclaimRetryTimer_checkEclaimStmPvtStatus = null;
                        }

        $(window).on('focus', function () {
            if ($('#eclaimStmPvtBotModal').hasClass('show')) {
                checkEclaimStmPvtStatus();
            }
        });

        $('#eclaimStmPvtBotModal').on('hidden.bs.modal', function () {
            if (window.eclaimRetryTimer_checkEclaimStmPvtStatus) {
                clearInterval(window.eclaimRetryTimer_checkEclaimStmPvtStatus);
                window.eclaimRetryTimer_checkEclaimStmPvtStatus = null;
            }
        });
                        $('#eclaimStmPvtAuthStatusIcon').removeClass('bg-warning-subtle text-warning bg-secondary-subtle text-secondary').addClass('bg-success-subtle text-success')
                            .html('<i class="bi bi-check-circle-fill fs-5"></i>');
                        $('#eclaimStmPvtAuthStatusText').html('เชื่อมต่อสำเร็จ: <span class="text-primary">' + res.user + '</span>');
                        $('#eclaimStmPvtAuthStatusSub').html('สถานะ: ออนไลน์พร้อมดึงข้อมูล | เชื่อมต่อเมื่อ: ' + res.connected_at);
                        $('#btnEclaimStmPvtLogout').removeClass('d-none');
                        $('#btnBotStmPvtSearch').prop('disabled', false);
                    } else {
                        $('#eclaimStmPvtAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary').addClass('bg-warning-subtle text-warning')
                            .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                        $('#eclaimStmPvtAuthStatusText').text('ยังไม่ได้เชื่อมต่อกับระบบ e-Claim หรือ Session หมดอายุ');
                        $('#eclaimStmPvtAuthStatusSub').text(res.message || 'เปิดเว็บ e-Claim ใน Chrome แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" ใน Extension เพื่อเริ่มดึงข้อมูล');
                        $('#btnEclaimStmPvtLogout').addClass('d-none');
                        $('#btnBotStmPvtSearch').prop('disabled', true);
                    }
                

                        // Auto-retry polling every 3s while modal is open
                        if (!window.eclaimRetryTimer_checkEclaimStmPvtStatus && $('#eclaimStmPvtBotModal').hasClass('show')) {
                            window.eclaimRetryTimer_checkEclaimStmPvtStatus = setInterval(function() {
                                if ($('#eclaimStmPvtBotModal').hasClass('show')) {
                                    $.ajax({
                                        url: "{{ route('import.eclaim-bot.status') }}",
                                        method: "POST",
                                        data: { _token: "{{ csrf_token() }}" },
                                        success: function(r) {
                                            if (r && r.connected) {
                                                clearInterval(window.eclaimRetryTimer_checkEclaimStmPvtStatus);
                                                window.eclaimRetryTimer_checkEclaimStmPvtStatus = null;
                                                checkEclaimStmPvtStatus();
                                            }
                                        }
                                    });
                                } else {
                                    clearInterval(window.eclaimRetryTimer_checkEclaimStmPvtStatus);
                                    window.eclaimRetryTimer_checkEclaimStmPvtStatus = null;
                                }
                            }, 3000);
                        }
                    },
                error: function() {
                    $('#eclaimStmPvtAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary').addClass('bg-warning-subtle text-warning')
                        .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                    $('#eclaimStmPvtAuthStatusText').text('ไม่สามารถตรวจสอบสถานะการเชื่อมต่อ e-Claim ได้');
                    $('#eclaimStmPvtAuthStatusSub').text('กรุณาเปิดหน้า e-Claim ใน Chrome แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" ใหม่อีกครั้ง');
                    $('#btnEclaimStmPvtLogout').addClass('d-none');
                    $('#btnBotStmPvtSearch').prop('disabled', true);
                }
            });
        }

        window.showEclaimExtensionGuide = function() {
            var apiUrl = "{{ url('/api') }}";
            var zipUrl = "{{ url('downloads/eclaim_sync.zip') }}";
            
            Swal.fire({
                title: '<div class="d-flex align-items-center justify-content-center gap-2 text-primary fs-5 fw-bold"><i class="bi bi-puzzle-fill"></i> คู่มือการติดตั้ง RiMS Chrome Extension</div>',
                html: `
                    <div class="text-start small text-secondary mb-3" style="line-height: 1.6;">
                        <b class="text-dark">ขั้นตอนการใช้งาน:</b>
                        <ol class="ps-3 mb-3 mt-1">
                            <li>คลิกปุ่มสีน้ำเงินด้านล่างเพื่อ <b>ดาวน์โหลดไฟล์ eclaim_sync.zip</b></li>
                            <li>แตกไฟล์ zip ไว้ในโฟลเดอร์ที่ต้องการในเครื่อง</li>
                            <li>เปิด Chrome ไปที่ <code>chrome://extensions</code> เปิด <b>Developer mode</b> แล้วกด <b>Load unpacked</b> เลือกโฟลเดอร์ที่แตกไว้</li>
                            <li>เปิดส่วนเสริม นำ <b>Server API URL</b> ด้านล่างนี้ไปวางในช่อง URL แล้วกดบันทึก:</li>
                        </ol>
                    </div>
                    <div class="card bg-light border-0 p-3 mb-3 rounded-3 text-start shadow-sm">
                        <label class="form-label small fw-bold text-dark mb-1"><i class="bi bi-link-45deg"></i> Server API URL สำหรับคัดลอกไปวางใน Extension:</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm font-monospace bg-white fw-bold text-primary" id="rimsApiUrlInput" value="${apiUrl}" readonly>
                            <button class="btn btn-sm btn-primary px-3" type="button" id="btnCopyApiUrl" onclick="navigator.clipboard.writeText('${apiUrl}'); this.innerHTML='<i class=\\'bi bi-check-lg me-1\\'></i> คัดลอกแล้ว!'; setTimeout(() => { this.innerHTML='<i class=\\'bi bi-clipboard me-1\\'></i> คัดลอก'; }, 2000);">
                                <i class="bi bi-clipboard me-1"></i> คัดลอก
                            </button>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="${zipUrl}" class="btn btn-primary rounded-pill py-2 shadow-sm fw-bold" download>
                            <i class="bi bi-download me-1"></i> ดาวน์โหลดไฟล์ส่วนเสริม (eclaim_sync.zip)
                        </a>
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'ปิดหน้าต่าง',
                confirmButtonColor: '#6c757d',
                customClass: {
                    popup: 'rounded-4 shadow-lg'
                }
            });
        };

        $('#eclaimStmPvtBotModal').on('show.bs.modal', function () {
            checkEclaimStmPvtStatus();
        });

        $('#btnEclaimStmPvtLoginPopup').on('click', function () {
            var loginUrl = 'https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do';
            var popup = window.open(loginUrl, 'EclaimLoginPopup', 'width=850,height=850,menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=yes');
            
            if (!popup || popup.closed || typeof popup.closed == 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'เบราว์เซอร์บล็อก Popup',
                    text: 'กรุณาอนุญาต (Allow Pop-up) ในเบราว์เซอร์เพื่อเปิดหน้าต่างเข้าสู่ระบบ e-Claim สปสช.',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            var checkCount = 0;
            var checkInterval = setInterval(function () {
                checkCount++;
                if (popup.closed) {
                    clearInterval(checkInterval);
                    checkEclaimStmPvtStatus();
                    return;
                }

                if (checkCount % 3 === 0) {
                    $.ajax({
                        url: "{{ route('import.eclaim-bot.status') }}",
                        method: "POST",
                        data: { _token: "{{ csrf_token() }}" },
                        success: function (res) {
                            if (res && res.connected) {
                                clearInterval(checkInterval);
                                try { popup.close(); } catch(e) {}
                                Swal.fire({
                                    icon: 'success',
                                    title: 'เข้าสู่ระบบสำเร็จ!',
                                    html: 'เชื่อมต่อกับ e-Claim สปสช. สำเร็จแล้ว: <b class="text-primary">' + res.user + '</b>',
                                    timer: 2500,
                                    showConfirmButton: false
                                });
                                checkEclaimStmPvtStatus();
                            }
                        }
                    });
                }

                if (checkCount > 120) {
                    clearInterval(checkInterval);
                }
            }, 1000);
        });

        $('#btnEclaimStmPvtLogout').on('click', function () {
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
                            checkEclaimStmPvtStatus();
                            $('#botStmPvtTableBody').html('<tr><td colspan="7" class="text-center py-5 text-muted"><div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>กดปุ่ม "ค้นหาใน e-Claim" เพื่อดึงรายการ Statement ครูเอกชน</td></tr>');
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

        var currentBotStmPvtData = [];

        $('#btnBotStmPvtSearch').on('click', function () {
            var budgetYear = $('#botStmPvtBudgetYear').val();
            var month = $('#botStmPvtMonth').val();
            var personType = $('#botStmPvtPersonType').val();

            $('#botStmPvtTableBody').html('<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border text-primary mb-2" role="status"></div><div>กำลังค้นหา Statement ครูเอกชน จาก e-Claim...</div></td></tr>');
            $('#btnBotStmPvtSearch').prop('disabled', true);
            $('#btnImportSelectedBotStmPvt').prop('disabled', true);
            $('#selectedBotStmPvtCount').text(0);
            $('#checkAllBotStmPvt').prop('checked', false);

            $.ajax({
                url: "{{ route('import.eclaim-bot.stm-pvt-search') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    budget_year: budgetYear,
                    month: month,
                    person_type: personType
                },
                success: function (res) {
                    $('#btnBotStmPvtSearch').prop('disabled', false);

                    if (res.status === 'success') {
                        currentBotStmPvtData = res.data;
                        $('#botStmPvtFoundBadge').text(res.count + ' รายการ');

                        if (res.data.length === 0) {
                            $('#botStmPvtTableBody').html('<tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-folder-x fs-2 d-block mb-2 text-warning"></i>ไม่พบ Statement ครูเอกชน ในช่วงเวลาที่เลือก</td></tr>');
                            return;
                        }

                        var html = '';
                        $.each(res.data, function (idx, item) {
                            var statusBadge = item.is_imported 
                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i> นำเข้าแล้ว (' + item.imported_count + ' รายการ)</span>'
                                : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-clock me-1"></i> ยังไม่นำเข้า</span>';

                            var isChecked = !item.is_imported ? 'checked' : '';

                            html += '<tr>';
                            html += '<td class="text-center"><input type="checkbox" class="form-check-input bot-stm-pvt-item-check" data-index="' + idx + '" ' + isChecked + '></td>';
                            html += '<td class="fw-bold text-dark font-monospace">' + item.statement_no + '</td>';
                            html += '<td>' + item.month + '</td>';
                            html += '<td>' + item.year + '</td>';
                            html += '<td><span class="badge bg-info-subtle text-info">' + item.round + '</span></td>';
                            html += '<td><span class="badge bg-primary-subtle text-primary">' + item.person_type + '</span></td>';
                            html += '<td class="text-center">' + statusBadge + '</td>';
                            html += '</tr>';
                        });

                        $('#botStmPvtTableBody').html(html);
                        updateSelectedBotStmPvtCount();
                    } else {
                        $('#botStmPvtTableBody').html('<tr><td colspan="7" class="text-center py-4 text-danger">' + (res.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล') + '</td></tr>');
                        Swal.fire('ผิดพลาด', res.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
                    }
                },
                error: function (xhr) {
                    $('#btnBotStmPvtSearch').prop('disabled', false);
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'ไม่สามารถเชื่อมต่อ e-Claim ได้';
                    $('#botStmPvtTableBody').html('<tr><td colspan="7" class="text-center py-4 text-danger">' + msg + '</td></tr>');
                    Swal.fire('ข้อผิดพลาด', msg, 'error');
                }
            });
        });

        $(document).on('change', '#checkAllBotStmPvt', function () {
            var isChecked = $(this).is(':checked');
            $('.bot-stm-pvt-item-check').prop('checked', isChecked);
            updateSelectedBotStmPvtCount();
        });

        $(document).on('change', '.bot-stm-pvt-item-check', function () {
            updateSelectedBotStmPvtCount();
        });

        function updateSelectedBotStmPvtCount() {
            var count = $('.bot-stm-pvt-item-check:checked').length;
            $('#selectedBotStmPvtCount').text(count);
            $('#btnImportSelectedBotStmPvt').prop('disabled', count === 0);
        }

        $('#btnImportSelectedBotStmPvt').on('click', function () {
            var selectedItems = [];
            $('.bot-stm-pvt-item-check:checked').each(function () {
                var idx = $(this).data('index');
                if (currentBotStmPvtData[idx]) {
                    selectedItems.push(currentBotStmPvtData[idx]);
                }
            });

            if (selectedItems.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกอย่างน้อย 1 รายการเพื่อนำเข้า', 'warning');
                return;
            }

            Swal.fire({
                title: 'ยืนยันการนำเข้า?',
                html: 'ระบบจะดาวน์โหลด Statement ครูเอกชน จาก e-Claim จำนวน <b>' + selectedItems.length + '</b> ไฟล์ และประมวลผลเข้าฐานข้อมูล RiMS อัตโนมัติ',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-cloud-arrow-down-fill me-1"></i> เริ่มดาวน์โหลดและนำเข้า',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังดึงและนำเข้าข้อมูล...',
                        html: '<div class="mb-2">กำลังดาวน์โหลดและประมวลผล Statement ครูเอกชน จาก e-Claim (' + selectedItems.length + ' ไฟล์)</div><div class="small text-muted">ขั้นตอนนี้อาจใช้เวลาสักครู่ กรุณาอย่าปิดหน้าต่างนี้</div>',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('import.eclaim-bot.stm-pvt-import') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            items: selectedItems
                        },
                        success: function (res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'นำเข้าสำเร็จ!',
                                    text: res.message,
                                    confirmButtonText: 'ตกลง',
                                    confirmButtonColor: '#10b981'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('ผิดพลาด', res.message || 'เกิดข้อผิดพลาดในการนำเข้า', 'error');
                            }
                        },
                        error: function (xhr) {
                            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล';
                            Swal.fire('ข้อผิดพลาด', msg, 'error');
                        }
                    });
                }
            });
        });
    </script>
@endpush
