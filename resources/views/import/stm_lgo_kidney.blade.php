@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card accent-9">
                <div class="card-body">
                    <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/stm_lgo_kidney_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-excel me-2 text-success"></i> นำเข้าไฟล์ STM (Excel Only)</h6>
                            <p class="text-muted small">เลือกไฟล์ Excel (.xlsx, .xls) ได้ไม่จำกัดจำนวนไฟล์</p>
                        </div>
                        
                        <div class="input-group mb-3">
                            <input class="form-control" id="formFile" type="file" name="files[]" multiple accept=".xlsx,.xls" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-success px-3.5" type="submit" style="border-radius: 0;">
                                <i class="bi bi-cloud-upload me-1.5"></i> นำเข้าข้อมูล
                            </button>
                            <button type="button" class="btn btn-primary px-3.5 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#smtKidneyModal" style="border-radius: 0 10px 10px 0; background: linear-gradient(135deg, #0284c7, #0369a1); border: none;">
                                <i class="bi bi-cloud-arrow-down-fill me-1.5"></i> ดึงจาก SMTF
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
                ข้อมูล Statement สิทธิเบิกจ่ายตรง อปท.LGO [ฟอกไต HD]
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณประจำปัจจุบัน: {{ $budget_year }}</div>
            <div class="mt-2">
                <a href="{{ url('/import/stm_lgo_kidneydetail') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-file-earmark-text me-1"></i> รายละเอียด
                </a>
                <button type="button" class="btn btn-info btn-sm rounded-pill px-3 text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#chartModal" id="btnShowChart">
                    <i class="bi bi-bar-chart-fill me-1"></i> กราฟสรุปรายเดือน
                </button>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="form-check form-switch me-1 mb-0 bg-light px-3 py-1.5 rounded-pill border d-flex align-items-center gap-2 shadow-sm" style="cursor: pointer;">
                    <input class="form-check-input ms-0" type="checkbox" id="filterUnreceiptedOnly" style="cursor: pointer;">
                    <label class="form-check-label small fw-bold text-danger mb-0 text-nowrap" for="filterUnreceiptedOnly" style="cursor: pointer;">
                        <i class="bi bi-funnel-fill text-danger me-1"></i> เฉพาะงวดที่ยังไม่ได้ออกใบเสร็จ
                    </label>
                </div>
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
                <table id="stm_lgo_kidney" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">Filename</th>
                            <th class="text-center">จำนวน</th> 
                            <th class="text-center">ชดเชยค่ารักษา</th> 
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
                        @foreach($stm_lgo_kidney as $row)
                        <tr data-receipt="{{ !empty($row->receive_no) ? '1' : '0' }}">
                            <td class="small fw-bold text-dark">{{ $row->stm_filename }}</td>                                                            
                            <td class="text-end fw-bold">{{ number_format($row->count_no) }}</td>                                   
                            <td class="text-end text-success fw-bold">{{ number_format($row->compensate_kidney,2) }}</td>
                            <td class="text-center text-primary fw-bold">{{ $row->round_no }}</td>
                            <td class="text-center">
                                @if(!empty($row->receive_no))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-check-circle-fill me-0.5"></i> {{ $row->receive_no }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-medium px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-clock-history me-0.5"></i> ยังไม่ออก
                                    </span>
                                @endif
                            </td>
                            <td class="text-center small">{{ !empty($row->receipt_date) ? DateThai($row->receipt_date) : '-' }}</td>
                            <td class="text-center small text-muted">{{ $row->receipt_by ?? '-' }}</td>
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
                                                    data-type="stm_lgo_kidney"
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
                        <div class="text-muted small" id="db_subtitle">ยอดชดเชยสุทธิรายเดือน Statement สิทธิเบิกจ่ายตรง อปท.LGO [ฟอกไต HD]</div>
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
{{-- End Modal ออกใบเสร็จ --}}

{{-- Modal ดึงข้อมูลจาก สปสช. (ThaiD SSO / Smart Money) - LGO HD --}}
<div class="modal fade" id="smtKidneyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 96vw; width: 1320px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #059669, #10b981); border-radius: 14px; color: white; box-shadow: 0 4px 12px rgba(16,185,129,0.4);">
                        <i class="bi bi-robot fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="smtKidneyModalLabel">
                            นำเข้า STM ฟอกไต
                        </h5>
                        <div class="text-light-50 small mt-0.5 d-flex align-items-center gap-2">
                            <span>ระบบเชื่อมต่อตรง smt.nhso.go.th</span>
                            <span class="badge rounded-pill bg-white text-dark py-1 px-2 fw-medium" style="font-size: 10.5px;">
                                <i class="bi bi-shield-check text-primary me-1"></i> ThaiD SSO Ready
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 ms-1" style="font-size: 10.5px;">
                                เฉพาะงวด LGO-HD% (อปท. ฟอกไต)
                            </span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Section 1: ThaiD Session Connection Status -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div id="smtAuthStatusIcon" class="badge rounded-circle p-2 bg-warning-subtle text-warning">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" id="smtAuthStatusText">ยังไม่ได้เชื่อมต่อกับระบบ ThaiD</div>
                                    <div class="text-muted small" id="smtAuthStatusSub">สแกน QR Code ด้วยแอป ThaiD เพื่อเข้าสู่ระบบ</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" id="btnSmtThaidLogin">
                                    <i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ (ThaiD)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-none" id="btnSmtLogout">
                                    <i class="bi bi-box-arrow-right me-1"></i> ตัดการเชื่อมต่อ
                                </button>
                                <a href="https://smt.nhso.go.th/smtf/#/home/budget/summary" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm" title="เปิดหน้าเว็บ Smart Money Transfer ในแท็บใหม่">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> เปิดเว็บ Smart Money Transfer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Search & Filter Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">
                                    <i class="bi bi-calendar-event text-primary me-1"></i> ตั้งแต่วันที่
                                </label>
                                <input type="hidden" id="bot_start_date" name="bot_start_date">
                                <input type="text" class="form-control form-control-sm rounded-3 datepicker_th" id="bot_start_date_picker" placeholder="วว/ดด/ปปปป" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">
                                    <i class="bi bi-calendar-event text-primary me-1"></i> ถึงวันที่
                                </label>
                                <input type="hidden" id="bot_end_date" name="bot_end_date">
                                <input type="text" class="form-control form-control-sm rounded-3 datepicker_th" id="bot_end_date_picker" placeholder="วว/ดด/ปปปป" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">เลข Batch / งวด / ผังบัญชี (ระบุหรือไม่ก็ได้)</label>
                                <input type="text" class="form-control form-control-sm rounded-3" id="botSmtKeywordFilter" placeholder="เช่น LGO-HD, 3150, ฟอกไต...">
                            </div>
                            <div class="col-md-3 d-flex align-items-end pt-3">
                                <button class="btn btn-primary btn-sm w-100 rounded-3 py-1.5 fw-bold shadow-sm opacity-50" type="button" id="btnBotSmtSearch" disabled title="กรุณาเข้าสู่ระบบด้วย ThaiD ก่อนค้นหาข้อมูล">
                                    <i class="bi bi-lock me-1"></i> ค้นหาใน Smart Money Transfer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Smart Money Results List Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center">
                        <div class="fw-bold small text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-list-task text-success"></i> รายการเงินโอน Smart Money Transfer ที่พบใน สปสช. (เปรียบเทียบกับฐานข้อมูล RiMS)
                        </div>
                        <span class="badge bg-light text-dark border px-2.5 py-1" id="botSmtCountBadge">พบ 0 รายการ</span>
                    </div>
                    <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-hover table-bordered align-middle mb-0 small" id="botSmtTable" style="width: 100%;">
                            <thead class="table-light sticky-top">
                                <tr class="text-muted small">
                                    <th class="text-center" style="width: 38px;">
                                        <input class="form-check-input" type="checkbox" id="checkAllBotSmt">
                                    </th>
                                    <th class="text-center text-nowrap" style="width: 95px;">วันที่โอน</th>
                                    <th class="text-center text-nowrap" style="width: 85px;">BatchNo.</th>
                                    <th class="text-center text-nowrap" style="width: 140px;">งวด / เลขที่เบิกจ่าย</th>
                                    <th class="text-center text-nowrap" style="width: 125px;">รหัสผังบัญชี</th>
                                    <th class="text-start">กองทุน / กองทุนย่อย</th>
                                    <th class="text-end text-nowrap" style="width: 135px;">เงินโอนเข้าบัญชี (บาท)</th>
                                    <th class="text-center text-nowrap" style="width: 165px;">สถานะใน RiMS</th>
                                </tr>
                            </thead>
                            <tbody id="botSmtTableBody">
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>
                                        กดปุ่ม "ค้นหาใน Smart Money Transfer" เพื่อดึงรายการเงินโอน
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer p-3 bg-white border-top d-flex justify-content-between">
                <div class="small fw-bold text-primary" id="selectedBotSmtCount">เลือก 0 รายการ</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-success px-4 rounded-pill fw-semibold shadow-sm" id="btnStartImportBotSmt" disabled>
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> เริ่มนำเข้า RiMS ทันที
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- End Modal Smart Money Transfer --}}

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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            /* ===== เปิด modal (ออกใหม่ / แก้ไข) ===== */
            document.querySelectorAll('.btn-new-receipt, .btn-edit-receipt')
                .forEach(btn => {
                    btn.addEventListener('click', function () {

                        document.getElementById('round_no').value =
                            this.dataset.round;

                        document.getElementById('receive_no').value =
                            this.dataset.receive ?? '';

                        document.getElementById('receipt_date').value =
                            this.dataset.date ?? '';

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
                    fetch("{{ url('import/stm_lgo_kidney_updateReceipt') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name=\"csrf-token\"]')
                                .getAttribute('content'),
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
    </script>

    <script>
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

                // ป้องกันฟอร์มส่งออกไปก่อนเวลา
            event.preventDefault(); 

            const fileInput = document.querySelector('input[type="file"]');
                    // ตรวจสอบว่าไม่ได้เลือกไฟล์
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
                return; // ❌ หยุดการทำงาน ไม่ส่งฟอร์ม
            }
                // ✅ ตรวจสอบจำนวนไฟล์เกิน 5
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
                return; // ❌ หยุดการทำงาน
            }

            showLoadingAlert();
            document.getElementById('importForm').submit();
        }
    </script>

@endsection

@push('scripts')
    <script>
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

            // Sync Changes to Hidden Inputs
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

            var stmTable = $('#stm_lgo_kidney').DataTable({
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
                    title: 'ข้อมูล Statement อปท. LGO ฟอกไต HD'
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

            // Filter only unreceipted files
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex, rowData, counter) {
                    if (settings.sTableId !== 'stm_lgo_kidney') return true;
                    if ($('#filterUnreceiptedOnly').is(':checked')) {
                        var rowNode = settings.aoData[dataIndex].nTr;
                        var isReceipted = $(rowNode).attr('data-receipt') === '1';
                        return !isReceipted;
                    }
                    return true;
                }
            );

            $('#filterUnreceiptedOnly').on('change', function() {
                stmTable.draw();
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

                $('#db_subtitle').text(`ยอดชดเชยสุทธิรายงวด Statement สิทธิเบิกจ่ายตรง อปท.LGO [ฟอกไต HD] ปีงบประมาณ: ${budgetYearText}`);

                $('#chart_container').addClass('d-none');
                $('#loading_spinner').removeClass('d-none');

                $.ajax({
                    url: "{{ route('import.stm_lgo_kidney.chart-data') }}",
                    method: "GET",
                    data: {
                        budget_year: budgetYear
                    },
                    success: function (res) {
                        $('#loading_spinner').addClass('d-none');
                        $('#chart_container').removeClass('d-none');
                        renderChart(res.labels, res.receive_totals);
                    },
                    error: function () {
                        $('#loading_spinner').addClass('d-none');
                        $('#chart_container').removeClass('d-none');
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถดึงข้อมูลกราฟได้', confirmButtonColor: '#d33' });
                    }
                });
            }

            function renderChart(labels, receiveTotals) {
                if (monthlyChart) {
                    monthlyChart.destroy();
                }

                const options = {
                    series: [{
                        name: 'ชดเชยสุทธิ',
                        data: receiveTotals
                    }],
                    chart: {
                        height: 430,
                        type: 'area',
                        toolbar: { show: false }
                    },
                    markers: { size: 4 },
                    colors: ['#10b981'],
                    fill: {
                        type: "gradient",
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.1,
                            stops: [0, 90, 100]
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return new Intl.NumberFormat('th-TH').format(val);
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
        // Smart Money Transfer (ThaiD) - LGO-HD
        // ==========================================
        var thaiMonthsShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        function toThaiDateString(d) {
            if (!d) return '';
            if (d instanceof Date) {
                var day = d.getDate();
                var month = thaiMonthsShort[d.getMonth() + 1] || '';
                var year = d.getFullYear() + 543;
                return day + ' ' + month + ' ' + year;
            }
            var str = d.toString().trim();
            if (str.indexOf('-') !== -1) {
                var p = str.split('-');
                if (p.length === 3) {
                    var y = parseInt(p[0], 10);
                    if (y < 2400) y += 543;
                    var m = parseInt(p[1], 10);
                    var dayNum = parseInt(p[2], 10);
                    return dayNum + ' ' + (thaiMonthsShort[m] || m) + ' ' + y;
                }
            }
            if (str.indexOf('/') !== -1) {
                var p = str.split('/');
                if (p.length === 3) {
                    var y = parseInt(p[2], 10);
                    if (y < 2400) y += 543;
                    var m = parseInt(p[0], 10);
                    var dayNum = parseInt(p[1], 10);
                    return dayNum + ' ' + (thaiMonthsShort[m] || m) + ' ' + y;
                }
            }
            return str;
        }

        function formatThaiDateTime(dateStr) {
            if (!dateStr) return '';
            var parts = dateStr.toString().trim().split(' ');
            var datePart = parts[0];
            var timePart = parts[1] || '';
            
            var dFormatted = datePart;
            if (datePart.indexOf('-') !== -1) {
                var p = datePart.split('-');
                if (p.length === 3) {
                    var y = parseInt(p[0], 10);
                    if (y < 2400) y += 543;
                    var m = parseInt(p[1], 10);
                    var dayNum = parseInt(p[2], 10);
                    dFormatted = dayNum + ' ' + (thaiMonthsShort[m] || m) + ' ' + y;
                }
            }
            return dFormatted + (timePart ? ' ' + timePart + ' น.' : '');
        }

        // Initialize Thai Datepicker for Modal
        try {
            $('#bot_start_date_picker, #bot_end_date_picker').datepicker('destroy');
        } catch(e) {}

        $('#bot_start_date_picker, #bot_end_date_picker').datepicker({
            format: 'd M yyyy',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            zIndexOffset: 1060
        });

        // Set default dates for ThaiD Bot modal (First day of current month to today)
        var botNow = new Date();
        var botFirstDayOfMonth = new Date(botNow.getFullYear(), botNow.getMonth(), 1);

        $('#bot_start_date_picker').datepicker('setDate', botFirstDayOfMonth);
        $('#bot_end_date_picker').datepicker('setDate', botNow);

        $('#bot_start_date_picker').val(toThaiDateString(botFirstDayOfMonth));
        $('#bot_end_date_picker').val(toThaiDateString(botNow));

        var startMonth = ("0" + (botFirstDayOfMonth.getMonth() + 1)).slice(-2);
        var endMonth = ("0" + (botNow.getMonth() + 1)).slice(-2);
        var endDay = ("0" + botNow.getDate()).slice(-2);

        $('#bot_start_date').val(botFirstDayOfMonth.getFullYear() + '-' + startMonth + '-01');
        $('#bot_end_date').val(botNow.getFullYear() + '-' + endMonth + '-' + endDay);

        $('#bot_start_date_picker').on('changeDate', function(e) {
            if(e.date) {
                var day = ("0" + e.date.getDate()).slice(-2);
                var month = ("0" + (e.date.getMonth() + 1)).slice(-2);
                var year = e.date.getFullYear();
                $('#bot_start_date').val(year + '-' + month + '-' + day);
                $(this).val(toThaiDateString(e.date));
            } else {
                $('#bot_start_date').val('');
            }
        });

        $('#bot_end_date_picker').on('changeDate', function(e) {
            if(e.date) {
                var day = ("0" + e.date.getDate()).slice(-2);
                var month = ("0" + (e.date.getMonth() + 1)).slice(-2);
                var year = e.date.getFullYear();
                $('#bot_end_date').val(year + '-' + month + '-' + day);
                $(this).val(toThaiDateString(e.date));
            } else {
                $('#bot_end_date').val('');
            }
        });

        // ThaiD Session Status Checker for Smart Money Transfer
        var smtIsConnected = false;
        var smtIsChecking = false;

        function checkSmtThaidStatus(silent = false) {
            if (smtIsChecking) return;
            smtIsChecking = true;

            if (!silent && !smtIsConnected) {
                $('#smtAuthStatusIcon').removeClass('bg-success-subtle text-success bg-warning-subtle text-warning')
                    .addClass('bg-secondary-subtle text-secondary')
                    .html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                $('#smtAuthStatusText').text('กำลังตรวจสอบสถานะการเชื่อมต่อ ThaiD...');
                $('#smtAuthStatusSub').text('ระบบกำลังทดสอบ Session ของผู้ใช้งาน');
                $('#btnBotSmtSearch').prop('disabled', true).addClass('opacity-50').attr('title', 'กรุณาเข้าสู่ระบบด้วย ThaiD ก่อนค้นหาข้อมูล').html('<i class="bi bi-lock me-1"></i> ค้นหาใน Smart Money Transfer');
            }

            $.ajax({
                url: "{{ route('import.eclaim-bot.status') }}",
                method: "POST",
                data: { _token: "{{ csrf_token() }}" },
                success: function(res) {
                    smtIsChecking = false;
                    if (res && res.connected) {
                        smtIsConnected = true;
                        if (window.smtRetryTimer) {
                            clearInterval(window.smtRetryTimer);
                            window.smtRetryTimer = null;
                        }

                        $('#smtAuthStatusIcon').removeClass('bg-warning-subtle text-warning bg-secondary-subtle text-secondary')
                            .addClass('bg-success-subtle text-success')
                            .html('<i class="bi bi-check-circle-fill fs-5"></i>');
                        $('#smtAuthStatusText').html('เชื่อมต่อสำเร็จ: <span class="text-primary fw-bold">' + (res.user || 'ผู้ใช้งาน ThaiD') + '</span>');
                        $('#smtAuthStatusSub').html('สถานะ: ออนไลน์พร้อมดึงข้อมูล | เชื่อมต่อเมื่อ: ' + (res.connected_at ? formatThaiDateTime(res.connected_at) : ''));
                        $('#btnSmtThaidLogin').html('<i class="bi bi-arrow-repeat me-1"></i> เชื่อมต่อใหม่');
                        $('#btnSmtLogout').removeClass('d-none');

                        // Enable Search Button
                        $('#btnBotSmtSearch').prop('disabled', false).removeClass('opacity-50').removeAttr('title').html('<i class="bi bi-search me-1"></i> ค้นหาใน Smart Money Transfer');
                    } else {
                        smtIsConnected = false;
                        $('#smtAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                            .addClass('bg-warning-subtle text-warning')
                            .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                        $('#smtAuthStatusText').text('ยังไม่ได้เชื่อมต่อกับระบบ ThaiD');
                        $('#smtAuthStatusSub').text('สแกน QR Code ด้วยแอป ThaiD เพื่อเข้าสู่ระบบ');
                        $('#btnSmtThaidLogin').html('<i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ (ThaiD)');
                        $('#btnSmtLogout').addClass('d-none');

                        // Disable Search Button
                        $('#btnBotSmtSearch').prop('disabled', true).addClass('opacity-50').attr('title', 'กรุณาเข้าสู่ระบบด้วย ThaiD ก่อนค้นหาข้อมูล').html('<i class="bi bi-lock me-1"></i> ค้นหาใน Smart Money Transfer');

                        if (!window.smtRetryTimer && $('#smtKidneyModal').hasClass('show')) {
                            window.smtRetryTimer = setInterval(function() {
                                if ($('#smtKidneyModal').hasClass('show') && !smtIsConnected) {
                                    checkSmtThaidStatus(true);
                                } else {
                                    clearInterval(window.smtRetryTimer);
                                    window.smtRetryTimer = null;
                                }
                            }, 3000);
                        }
                    }
                },
                error: function() {
                    smtIsChecking = false;
                    smtIsConnected = false;
                    $('#smtAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                        .addClass('bg-warning-subtle text-warning')
                        .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                    $('#smtAuthStatusText').text('ยังไม่ได้เชื่อมต่อ ThaiD');
                    $('#smtAuthStatusSub').text('กดปุ่ม "เข้าสู่ระบบ (ThaiD)" เพื่อเชื่อมต่อ');
                    $('#btnSmtLogout').addClass('d-none');

                    // Disable Search Button
                    $('#btnBotSmtSearch').prop('disabled', true).addClass('opacity-50').attr('title', 'กรุณาเข้าสู่ระบบด้วย ThaiD ก่อนค้นหาข้อมูล').html('<i class="bi bi-lock me-1"></i> ค้นหาใน Smart Money Transfer');
                }
            });
        }

        $('#smtKidneyModal').on('show.bs.modal', function () {
            if (!$('#bot_start_date_picker').val() || $('#bot_start_date_picker').val().indexOf('2026') !== -1 || $('#bot_start_date_picker').val().indexOf('/') !== -1) {
                var mNow = new Date();
                var mFirstDay = new Date(mNow.getFullYear(), mNow.getMonth(), 1);
                $('#bot_start_date_picker').val(toThaiDateString(mFirstDay));
                $('#bot_end_date_picker').val(toThaiDateString(mNow));
            }
            if (!smtIsConnected) {
                $('#btnBotSmtSearch').prop('disabled', true).addClass('opacity-50').attr('title', 'กรุณาเข้าสู่ระบบด้วย ThaiD ก่อนค้นหาข้อมูล').html('<i class="bi bi-lock me-1"></i> ค้นหาใน Smart Money Transfer');
            }
            checkSmtThaidStatus();
            $('#botSmtTableBody').html('<tr><td colspan="8" class="text-center py-5 text-muted"><div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>กดปุ่ม "ค้นหาใน Smart Money Transfer" เพื่อดึงรายการเงินโอน</td></tr>');
            $('#botSmtCountBadge').text('พบ 0 รายการ');
            $('#selectedBotSmtCount').text('เลือก 0 รายการ');
            $('#btnStartImportBotSmt').prop('disabled', true);
            $('#checkAllBotSmt').prop('checked', false);
        });

        $('#smtKidneyModal').on('hidden.bs.modal', function () {
            if (window.smtRetryTimer) {
                clearInterval(window.smtRetryTimer);
                window.smtRetryTimer = null;
            }
        });

        $('#btnSmtThaidLogin').on('click', function () {
            openEclaimThaidQrModal(checkSmtThaidStatus);
        });

        $('#btnSmtLogout').on('click', function () {
            Swal.fire({
                title: 'ยืนยันตัดการเชื่อมต่อ?',
                text: 'ระบบจะล้าง Session e-Claim / ThaiD ออกจากระบบ',
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
                            checkSmtThaidStatus();
                            $('#btnBotSmtSearch').prop('disabled', true).addClass('opacity-50').attr('title', 'กรุณาเข้าสู่ระบบด้วย ThaiD ก่อนค้นหาข้อมูล').html('<i class="bi bi-lock me-1"></i> ค้นหาใน Smart Money Transfer');
                            $('#botSmtTableBody').html('<tr><td colspan="8" class="text-center py-5 text-muted"><div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>กดปุ่ม "ค้นหาใน Smart Money Transfer" เพื่อดึงรายการเงินโอน</td></tr>');
                            $('#botSmtCountBadge').text('พบ 0 รายการ');
                            $('#selectedBotSmtCount').text('เลือก 0 รายการ');
                            $('#btnStartImportBotSmt').prop('disabled', true);
                            Swal.fire({
                                icon: 'success',
                                title: 'ตัดการเชื่อมต่อแล้ว',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    });
                }
            });
        });

        // Search in Smart Money
        $('#btnBotSmtSearch').on('click', function() {
            if (!smtIsConnected) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้เชื่อมต่อ ThaiD',
                    text: 'กรุณากดปุ่ม "เข้าสู่ระบบ (ThaiD)" เพื่อสแกน QR Code ก่อนค้นหาข้อมูล',
                    confirmButtonText: '<i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ (ThaiD)',
                    showCancelButton: true,
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#0d6efd'
                }).then((result) => {
                    if (result.isConfirmed) {
                        openEclaimThaidQrModal(checkSmtThaidStatus);
                    }
                });
                return;
            }

            var startDate = $('#bot_start_date').val() || $('#bot_start_date_picker').val();
            var endDate = $('#bot_end_date').val() || $('#bot_end_date_picker').val();
            var keyword = $('#botSmtKeywordFilter').val();

            $('#botSmtTableBody').html(`
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted fw-bold">กำลังดึงข้อมูลเงินโอน Smart Money Transfer (LGO-HD) จาก สปสช. แบบ Real-time ...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: "{{ route('import.stm_lgo_kidney.search_smt') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    start_date: startDate,
                    end_date: endDate,
                    keyword: keyword
                },
                success: function(res) {
                    if (res.status === 'success') {
                        renderLgoSmtTable(res.data, res.count);
                    } else {
                        $('#botSmtTableBody').html(`
                            <tr>
                                <td colspan="8" class="text-center py-5 text-danger">
                                    <i class="bi bi-exclamation-triangle-fill fs-3 mb-2 d-block"></i>
                                    <strong>${res.message || 'ไม่พบข้อมูล'}</strong>
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr) {
                    var msg = 'เกิดข้อผิดพลาดในการค้นหาข้อมูล';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (xhr.status === 401) {
                        checkSmtThaidStatus();
                        Swal.fire({
                            icon: 'warning',
                            title: 'ยังไม่ได้เชื่อมต่อ ThaiD',
                            text: msg,
                            confirmButtonText: 'เข้าสู่ระบบ (ThaiD)',
                            showCancelButton: true,
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#0d6efd'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                openEclaimThaidQrModal(checkSmtThaidStatus);
                            }
                        });
                    }
                    $('#botSmtTableBody').html(`
                        <tr>
                            <td colspan="8" class="text-center py-5 text-danger">
                                <i class="bi bi-x-circle-fill fs-3 mb-2 d-block"></i>
                                <strong>${msg}</strong>
                            </td>
                        </tr>
                    `);
                }
            });
        });

        // Render SMT Table
        function renderLgoSmtTable(items, count) {
            $('#botSmtCountBadge').html(`พบ <strong>${count || items.length}</strong> รายการ`);
            $('#checkAllBotSmt').prop('checked', false);
            updateSelectedSmtCount();

            if (!items || items.length === 0) {
                $('#botSmtTableBody').html(`
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-3 mb-2 d-block opacity-50"></i>
                            ไม่พบรายการเงินโอน LGO-HD ในช่วงวันที่ที่เลือก
                        </td>
                    </tr>
                `);
                return;
            }

            var html = '';
            items.forEach(function(item, idx) {
                var statusBadge = '';
                var isChecked = false;

                if (!item.is_imported) {
                    statusBadge = `<span class="badge bg-secondary-subtle text-secondary px-2 py-1"><i class="bi bi-dash-circle me-1"></i>ยังไม่นำเข้า</span>`;
                    isChecked = true;
                } else {
                    statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>นำเข้าแล้ว (${item.existing_count} คน)</span>`;
                    isChecked = false;
                }

                var itemJson = encodeURIComponent(JSON.stringify(item));

                html += `
                    <tr>
                        <td class="text-center">
                            <input class="form-check-input bot-smt-check" type="checkbox" data-item="${itemJson}" ${isChecked ? 'checked' : ''}>
                        </td>
                        <td class="text-center text-nowrap small">${item.transfer_date_thai || item.transfer_date}</td>
                        <td class="text-center text-nowrap fw-bold text-primary">${item.batch_no}</td>
                        <td class="text-center text-nowrap font-monospace small">${item.round_no}</td>
                        <td class="text-center text-nowrap font-monospace small text-secondary">${item.account_code || '-'}</td>
                        <td class="text-start">
                            <div class="fw-semibold text-dark lh-sm">${item.fund_main || '-'}</div>
                            <div class="text-muted small lh-1 mt-0.5" style="font-size: 11px;">${item.fund_sub || ''}</div>
                        </td>
                        <td class="text-end text-nowrap fw-bold text-success font-monospace">${item.net_amount_formatted}</td>
                        <td class="text-center text-nowrap">${statusBadge}</td>
                    </tr>
                `;
            });

            $('#botSmtTableBody').html(html);
            updateSelectedSmtCount();
        }

        // Toggle Select All
        $('#checkAllBotSmt').on('change', function () {
            var checked = $(this).prop('checked');
            $('.bot-smt-check').prop('checked', checked);
            updateSelectedSmtCount();
        });

        // Toggle Single Item
        $(document).on('change', '.bot-smt-check', function () {
            var total = $('.bot-smt-check').length;
            var checked = $('.bot-smt-check:checked').length;
            $('#checkAllBotSmt').prop('checked', total > 0 && total === checked);
            updateSelectedSmtCount();
        });

        // Update Counter
        function updateSelectedSmtCount() {
            var selected = $('.bot-smt-check:checked').length;
            $('#selectedBotSmtCount').text('เลือก ' + selected + ' รายการ');
            $('#btnStartImportBotSmt').prop('disabled', selected === 0);
        }

        // Import Selected Batches to RiMS
        $('#btnStartImportBotSmt').on('click', function () {
            var selectedItems = [];
            $('.bot-smt-check:checked').each(function () {
                var raw = $(this).attr('data-item');
                if (raw) {
                    try {
                        selectedItems.push(JSON.parse(decodeURIComponent(raw)));
                    } catch (e) {
                        console.error("Parse item error: ", e);
                    }
                }
            });

            if (selectedItems.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกอย่างน้อย 1 รายการเพื่อนำเข้า', 'warning');
                return;
            }

            Swal.fire({
                title: 'ยืนยันการนำเข้าข้อมูล STM ฟอกไต?',
                html: `
                    <div class="text-start p-3 bg-light rounded-4 mb-3 small">
                        <div class="mb-2">📄 <strong>จำนวนงวดที่เลือก:</strong> <span class="text-primary fw-bold fs-6">${selectedItems.length}</span> งวด</div>
                        <div class="text-muted">🛡️ ระบบจะดาวน์โหลดรายละเอียดและบันทึกผู้ป่วยรายคนลงตาราง <strong>stm_lgo_kidney</strong> ให้อัตโนมัติ</div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '🚀 เริ่มนำเข้าทันที',
                cancelButtonText: 'ยกเลิก',
                customClass: { popup: 'rounded-4' }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    var total = selectedItems.length;

                    Swal.fire({
                        title: 'กำลังนำเข้าข้อมูล STM ฟอกไต...',
                        html: `
                            <div class="text-center p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                    <span class="small text-muted fw-semibold" id="smtProgressStep">กำลังเตรียมข้อมูล...</span>
                                    <span class="badge bg-success fs-6 fw-bold px-3 py-1 shadow-sm" id="smtProgressPercent" style="border-radius: 12px;">0%</span>
                                </div>
                                <div class="progress mb-3 shadow-inner" style="height: 22px; border-radius: 12px; background-color: #e9ecef; overflow: hidden; padding: 2px;">
                                    <div id="smtProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; border-radius: 10px; font-weight: bold; font-size: 12px; transition: width 0.25s ease;">
                                    </div>
                                </div>
                                <div class="card border-0 bg-light-subtle rounded-3 p-2 text-start">
                                    <div class="small fw-semibold text-dark" id="smtProgressDetail"><i class="bi bi-arrow-repeat me-1 spinner-border spinner-border-sm"></i> กำลังเชื่อมต่อไปยัง สปสช. ...</div>
                                </div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-4 shadow' }
                    });

                    var successCount = 0;
                    var totalPatients = 0;
                    var errors = [];

                    for (var i = 0; i < total; i++) {
                        var item = selectedItems[i];
                        var pct = Math.round(((i) / total) * 100);
                        $('#smtProgressBar').css('width', pct + '%');
                        $('#smtProgressPercent').text(pct + '%');
                        $('#smtProgressStep').text(`งวดที่ ${i + 1} จาก ${total} งวด`);
                        $('#smtProgressDetail').html(`<i class="bi bi-arrow-repeat me-1 spinner-border spinner-border-sm"></i> กำลังประมวลผลงวด <strong>${item.round_no}</strong> (Batch ${item.batch_no})...`);

                        try {
                            var res = await $.ajax({
                                url: "{{ route('import.stm_lgo_kidney.import_smt') }}",
                                method: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    items: [item]
                                }
                            });

                            if (res.status === 'success') {
                                successCount++;
                                totalPatients += (res.inserted_details || 0);
                            } else {
                                errors.push(item.round_no + ': ' + (res.message || 'ไม่ทราบสาเหตุ'));
                            }
                        } catch (err) {
                            var errMsg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
                            errors.push(item.round_no + ': ' + errMsg);
                        }
                    }

                    $('#smtProgressBar').css('width', '100%');
                    $('#smtProgressPercent').text('100%');
                    $('#smtProgressStep').text('เสร็จสิ้นกระบวนการ');

                    if (errors.length === 0) {
                        Swal.fire({
                            icon: 'success',
                            title: 'นำเข้าข้อมูลสำเร็จ!',
                            html: `นำเข้าข้อมูลลง stm_lgo_kidney สำเร็จครบ <strong>${successCount}</strong> งวด<br>รวมผู้ป่วยทั้งหมด <strong>${totalPatients.toLocaleString('th-TH')}</strong> รายการ`,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#10b981'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: successCount > 0 ? 'warning' : 'error',
                            title: successCount > 0 ? 'นำเข้าสำเร็จบางส่วน' : 'เกิดข้อผิดพลาด',
                            html: `สำเร็จ ${successCount}/${total} งวด (${totalPatients} รายการ)<br><div class="text-danger small mt-2 text-start">${errors.join('<br>')}</div>`,
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            if (successCount > 0) {
                                location.reload();
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush
