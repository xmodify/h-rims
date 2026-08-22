@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card border-0 shadow-sm" style="border-top: 4px solid #fd7e14 !important; border-radius: 14px;">
                <div class="card-body p-4">
                    <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/rep_bmt_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-excel me-2 text-warning"></i> นำเข้าไฟล์ REP สิทธิ์เจ้าหน้าที่ ขสมก. (BMT) (Excel Only)</h6>
                            <p class="text-muted small">เลือกไฟล์ Excel (.xlsx, .xls) ได้ไม่จำกัดจำนวนไฟล์</p>
                        </div>
                        
                        <div class="input-group mb-3">
                            <input class="form-control" id="formFile" type="file" name="files[]" multiple accept=".xlsx,.xls" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-warning text-white px-4" type="submit" style="border-radius: 0 10px 10px 0;">
                                <i class="bi bi-cloud-upload me-2"></i> นำเข้าข้อมูล
                            </button>
                        </div>

                        @if ($message = Session::get('rep_success'))
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
    <div class="page-header-box mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 bg-white shadow-sm" style="border-radius: 14px;">
        <div>
            <h5 class="text-dark mb-0 fw-bold text-truncate" style="max-width: 500px;">
                <i class="bi bi-cloud-arrow-down-fill text-warning me-2"></i>
                ข้อมูลการตรวจสอบเบื้องต้น (REP) สิทธิ์เจ้าหน้าที่ ขสมก. (BMT) BMT [OP-IP]
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณประจำปัจจุบัน: {{ $budget_year }}</div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <a href="{{ url('/import/rep_bmt_detail_opd') }}" class="btn btn-warning text-white btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-person-badge me-1"></i> รายละเอียด OPD
                </a>
                <a href="{{ url('/import/rep_bmt_detail_ipd') }}" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-hospital me-1"></i> รายละเอียด IPD
                </a>
                <button type="button" class="btn btn-info btn-sm rounded-pill px-3 text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#chartModal" id="btnShowChart">
                    <i class="bi bi-bar-chart-fill me-1"></i> กราฟสรุปรายเดือน
                </button>
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#failChartModal" id="btnShowFailChart">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> วิเคราะห์รหัสข้อผิดพลาด C
                </button>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small text-nowrap">ปีงบประมาณ:</span>
                <select class="form-select form-select-sm" name="budget_year" style="width: 160px; border-radius: 8px;">
                    @foreach ($budget_year_select as $row)
                        <option value="{{ $row->LEAVE_YEAR_ID }}"
                            {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                            {{ $row->LEAVE_YEAR_NAME }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-warning text-white btn-sm rounded-pill px-3">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-0 shadow-sm mb-4" style="border-radius: 14px;">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="rep_bmt_table" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" width="25%">ชื่อ File</th>
                            <th class="text-center">Dep</th>
                            <th class="text-center">เลขที่ REP</th>
                            <th class="text-center">จำนวนรายทั้งหมด</th>
                            <th class="text-center">ผ่าน (ราย)</th>
                            <th class="text-center">ไม่ผ่าน (ราย)</th>
                            <th class="text-center">เรียกเก็บ</th>
                            <th class="text-center">ชดเชยสุทธิ</th>
                            @if(Auth::user()->status == 'admin')
                                <th class="text-center" width="10%">การจัดการ</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $total_cid = 0;
                            $total_pass = 0;
                            $total_fail = 0;
                            $total_resolved = 0;
                            $total_charge = 0;
                            $total_receive = 0;
                        @endphp
                        @foreach($rep_bmt as $row)
                        @php
                            $total_cid += $row->count_cid;
                            $total_pass += $row->count_pass;
                            $total_fail += $row->count_fail;
                            $total_resolved += $row->count_resolved;
                            $total_charge += $row->charge;
                            $total_receive += $row->receive_total;
                            
                            $unresolved = $row->count_fail - $row->count_resolved;
                            $resolved = $row->count_resolved;
                        @endphp
                        <tr>
                            <td class="small fw-bold text-dark">
                                {{ $row->rep_filename }}
                                @if($row->is_appeal)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 10px; padding: 3px 6px;">
                                        <i class="bi bi-shield-fill-exclamation me-1"></i> อุทธรณ์
                                    </span>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->dep }}</span></td>
                            <td class="text-center">{{ $row->repno }}</td>
                            <td class="text-end fw-bold">{{ number_format($row->count_cid) }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($row->count_pass) }}</td>
                            <td class="text-center">
                                @if($row->count_fail > 0)
                                    <a href="javascript:void(0)" class="fail-link text-decoration-none d-inline-flex align-items-center justify-content-center gap-1"
                                       data-filename="{{ $row->rep_filename }}"
                                       data-type="{{ $row->dep }}"
                                       data-repno="{{ $row->repno }}">
                                        @if($unresolved == 0)
                                            <span class="badge bg-success-soft text-success border border-success-subtle rounded-pill px-2.5 py-1" style="background-color: rgba(25, 135, 84, 0.08); border: 1px solid rgba(25, 135, 84, 0.2) !important; font-size: 11px; font-weight: bold; display: inline-flex; align-items: center;">
                                                <i class="bi bi-check-circle-fill me-1"></i> แก้ผ่านครบ {{ $resolved }}/{{ $row->count_fail }}
                                            </span>
                                        @else
                                            <span class="badge bg-danger rounded-pill px-2.5 py-1" style="font-size: 12px; font-weight: bold;">
                                                {{ $unresolved }}
                                            </span>
                                            @if($resolved > 0)
                                                <span class="badge bg-success-soft text-success border border-success-subtle rounded-pill px-2 py-0.5" style="background-color: rgba(25, 135, 84, 0.08); border: 1px solid rgba(25, 135, 84, 0.2) !important; font-size: 10px; font-weight: bold;">
                                                    <i class="bi bi-check-circle-fill"></i> แก้แล้ว {{ $resolved }}
                                                </span>
                                            @endif
                                        @endif
                                    </a>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end text-muted">{{ number_format($row->charge,2) }}</td>
                            <td class="text-end text-warning fw-bold">{{ number_format($row->receive_total,2) }}</td>
                            @if(Auth::user()->status == 'admin')
                                <td class="text-center text-nowrap">
                                    <button type="button"
                                        class="btn btn-xs btn-outline-danger rounded-pill px-3 btn-action-delete"
                                        data-filename="{{ $row->rep_filename }}"
                                        data-type="rep_bmt"
                                        title="ลบข้อมูลนำเข้า">
                                        <i class="bi bi-trash-fill me-1"></i> ลบ
                                    </button>
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold bg-light" style="border-top: 2px solid #dee2e6 !important;">
                            <td colspan="3" class="text-center text-dark">รวมทั้งหมดของปีงบประมาณ</td>
                            <td class="text-end text-dark">{{ number_format($total_cid) }}</td>
                            <td class="text-end text-success">{{ number_format($total_pass) }}</td>
                            <td class="text-center">
                                @php
                                    $total_unresolved = $total_fail - $total_resolved;
                                @endphp
                                @if($total_fail > 0)
                                    <span class="text-danger fw-bold" style="font-size: 14px;">{{ number_format($total_unresolved) }}</span>
                                    @if($total_resolved > 0)
                                        <span class="text-success small fw-normal ms-1" style="font-size: 11px;">(แก้แล้ว {{ number_format($total_resolved) }})</span>
                                    @endif
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end text-muted">{{ number_format($total_charge,2) }}</td>
                            <td class="text-end text-warning">{{ number_format($total_receive,2) }}</td>
                            @if(Auth::user()->status == 'admin')
                                <td></td>
                            @endif
                        </tr>
                    </tfoot>
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
                    <div class="icon-box bg-warning text-white mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                        <i class="bi bi-bar-chart-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="db_title">Dashboard</h5>
                        <div class="text-muted small" id="db_subtitle">ยอดชดเชยสุทธิรายเดือน REP สิทธิ์เจ้าหน้าที่ ขสมก. (BMT) BMT</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-4">
                        <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill">
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
                    <div class="spinner-border text-warning" role="status"></div>
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

{{-- Modal: Failed Details (Patient list only) --}}
<div class="modal fade" id="failDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-danger-soft text-danger mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background-color: rgba(239, 68, 68, 0.08); border-radius: 12px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="failModalTitle">รายละเอียดข้อมูลที่ไม่ผ่าน</h5>
                        <div class="text-muted small" id="failModalSubtitle">รายชื่อผู้ป่วยและสาเหตุข้อผิดพลาด (รหัส C)</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Loading Spinner -->
                <div id="fail_loading_spinner" class="text-center py-5 d-none">
                    <div class="spinner-border text-danger" role="status"></div>
                    <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
                </div>

                <!-- Patient List Table -->
                <div id="failTabContent">
                    <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                        <table class="table table-modern table-hover align-middle w-100">
                            <thead class="sticky-top bg-white" style="z-index: 1;">
                                <tr>
                                    <th class="text-center" width="5%">ลำดับ</th>
                                    <th class="text-center" width="10%">HN</th>
                                    <th class="text-center" width="10%">AN</th>
                                    <th>ชื่อ-สกุล</th>
                                    <th class="text-center" width="12%">วันที่รับบริการ</th>
                                    <th class="text-center text-danger" width="13%">รหัส C / Error Code</th>
                                    <th class="text-end" width="10%">เรียกเก็บ</th>
                                    <th class="text-end" width="10%">ชดเชย สปสช.</th>
                                    <th class="text-center" width="20%">สถานะปัจจุบัน</th>
                                </tr>
                            </thead>
                            <tbody id="failPatientsTableBody">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: C-Code Error Chart (Yearly Summary) --}}
<div class="modal fade" id="failChartModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-danger-soft text-danger mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background-color: rgba(220, 53, 69, 0.08); border-radius: 12px; color: #dc3545;">
                        <i class="bi bi-bar-chart-line-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="failChartModalTitle">วิเคราะห์รหัสข้อผิดพลาด (C Code)</h5>
                        <div class="text-muted small" id="failChartModalSubtitle">สรุปความถี่ของรหัสข้อผิดพลาดสะสมประจำปีงบประมาณ</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">
                            <i class="bi bi-exclamation-circle me-1"></i> รหัสข้อผิดพลาดที่พบสะสมในปีงบประมาณ: {{ $budget_year }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small text-nowrap">ประเภทการบริการ:</span>
                                <select class="form-select shadow-sm" id="fail_chart_filter_type" style="width: 160px; border-radius: 8px;">
                                    <option value="all" selected>ทั้งหมด (OP/IP)</option>
                                    <option value="OP">ผู้ป่วยนอก (OPD)</option>
                                    <option value="IP">ผู้ป่วยใน (IPD)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div id="c_code_loading_spinner" class="text-center py-5 d-none">
                    <div class="spinner-border text-danger" role="status"></div>
                    <div class="mt-2 text-muted">กำลังดึงข้อมูลและวิเคราะห์...</div>
                </div>

                <!-- Chart Container -->
                <div style="height: 430px; width: 100%;" id="c_code_chart_container">
                    <div id="yearlyCCodeChart" style="height: 100%; width: 100%;"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert: Success -->
@if (session('rep_success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            title: 'นำเข้าสำเร็จ!',
            text: "{!! session('rep_success') !!}",
            icon: 'success',
            confirmButtonText: 'ปิด',
            confirmButtonColor: '#fd7e14',
            customClass: {
                confirmButton: 'btn btn-warning text-white btn-sm px-4'
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
                    confirmButtonColor: '#fd7e14',
                    customClass: {
                        confirmButton: 'btn btn-warning text-white btn-sm px-4'
                    }
                });
                return;
            }
            
            showLoadingAlert();
            document.getElementById('importForm').submit();
        };

        $(document).ready(function () {

            $('#rep_bmt_table').DataTable({
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
                    title: 'ข้อมูล REP สิทธิ์เจ้าหน้าที่ ขสมก. (BMT) BMT [OP-IP]'
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

                $('#db_subtitle').text(`ยอดชดเชยสุทธิรายเดือน REP สิทธิ์เจ้าหน้าที่ ขสมก. (BMT) BMT ปีงบประมาณ: ${budgetYearText}`);

                $('#chart_container').addClass('d-none');
                $('#loading_spinner').removeClass('d-none');

                $.ajax({
                    url: "{{ route('import.rep_bmt.chart-data') }}",
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
                    colors: ['#fd7e14', '#ef4444'],
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

            // --- Failed Details Modal Handling ---
            $(document).on('click', '.fail-link', function () {
                const filename = $(this).data('filename');
                const type = $(this).data('type');
                const repno = $(this).data('repno');

                // Update modal texts
                $('#failModalTitle').text(`รายละเอียดข้อมูลที่ไม่ผ่าน [${type}]`);
                $('#failModalSubtitle').text(`ไฟล์: ${filename} | เลขที่ REP: ${repno}`);

                // Clear previous table content
                $('#failPatientsTableBody').empty();

                // Show spinner, hide table content
                $('#fail_loading_spinner').removeClass('d-none');
                $('#failTabContent').addClass('d-none');

                // Open modal
                $('#failDetailsModal').modal('show');

                // AJAX Request
                $.ajax({
                    url: "{{ route('import.rep_bmt.fail-details') }}",
                    method: "GET",
                    data: {
                        rep_filename: filename,
                        rep_type: type,
                        repno: repno
                    },
                    success: function (res) {
                        $('#fail_loading_spinner').addClass('d-none');
                        $('#failTabContent').removeClass('d-none');

                        // Populate patients table
                        if (res.patients && res.patients.length > 0) {
                            res.patients.forEach((p, idx) => {
                                const row = `
                                    <tr>
                                        <td class="text-center text-muted small">${idx + 1}</td>
                                        <td class="text-center fw-bold text-dark">${p.hn}</td>
                                        <td class="text-center">${p.an}</td>
                                        <td class="fw-bold">${p.pt_name}</td>
                                        <td class="text-center small">${p.service_date}</td>
                                        <td class="text-center"><span class="badge bg-danger-soft text-danger fw-bold fs-6" style="background-color: rgba(220, 53, 69, 0.08);">${p.error_code}</span></td>
                                        <td class="text-end text-muted">${p.charge_total}</td>
                                        <td class="text-end text-danger fw-bold">${p.net_compensate_nhso}</td>
                                        <td class="text-center">
                                            <span class="badge bg-${p.status_color}-soft text-${p.status_color} fw-bold" style="background-color: ${p.status_color == 'success' ? 'rgba(40, 167, 69, 0.08)' : 'rgba(220, 53, 69, 0.08)'}; border: 1px solid ${p.status_color == 'success' ? '#28a745' : '#dc3545'}; padding: 5px 10px; border-radius: 6px; font-size: 11px;">
                                                ${p.status_text}
                                            </span>
                                        </td>
                                    </tr>
                                `;
                                $('#failPatientsTableBody').append(row);
                            });
                        } else {
                            $('#failPatientsTableBody').append('<tr><td colspan="9" class="text-center text-muted py-4">ไม่พบข้อมูลรายชื่อที่ไม่ผ่าน</td></tr>');
                        }
                    },
                    error: function () {
                        $('#fail_loading_spinner').addClass('d-none');
                        $('#failTabContent').removeClass('d-none');
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถดึงข้อมูลรายละเอียดได้', confirmButtonColor: '#d33' });
                    }
                });
            });

            // --- Yearly C-Code Summary Chart Modal Handling ---
            let yearlyCCodeChart = null;

            $('#failChartModal').on('shown.bs.modal', function () {
                loadCCodeChartData();
            });

            $('#fail_chart_filter_type').on('change', function () {
                loadCCodeChartData();
            });

            function loadCCodeChartData() {
                const budgetYear = $('select[name="budget_year"]').val();
                const serviceType = $('#fail_chart_filter_type').val();

                $('#c_code_chart_container').addClass('d-none');
                $('#c_code_loading_spinner').removeClass('d-none');

                if (yearlyCCodeChart) {
                    yearlyCCodeChart.destroy();
                    yearlyCCodeChart = null;
                }

                $.ajax({
                    url: "{{ route('import.rep_bmt.c-code-chart-data') }}",
                    method: "GET",
                    data: {
                        budget_year: budgetYear,
                        type: serviceType
                    },
                    success: function (res) {
                        $('#c_code_loading_spinner').addClass('d-none');
                        $('#c_code_chart_container').removeClass('d-none');

                        if (res.labels && res.labels.length > 0) {
                            renderYearlyCCodeChart(res.labels, res.counts);
                        } else {
                            $('#yearlyCCodeChart').html('<div class="text-center text-muted py-5"><i class="bi bi-info-circle me-1"></i> ไม่มีข้อมูลรหัสข้อผิดพลาดสะสมในปีงบประมาณนี้</div>');
                        }
                    },
                    error: function () {
                        $('#c_code_loading_spinner').addClass('d-none');
                        $('#c_code_chart_container').removeClass('d-none');
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถดึงข้อมูลสถิติได้', confirmButtonColor: '#d33' });
                    }
                });
            }

            function renderYearlyCCodeChart(labels, counts) {
                const options = {
                    series: [{
                        name: 'พบสะสม (ราย)',
                        data: counts
                    }],
                    chart: {
                        type: 'bar',
                        height: 400,
                        toolbar: { show: true }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            horizontal: false,
                            columnWidth: '45%',
                            distributed: true,
                            dataLabels: {
                                position: 'top',
                            },
                        }
                    },
                    colors: ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#fd7e14', '#6610f2', '#d63384', '#00f5ff', '#32cd32', '#ba55d3'],
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val.toLocaleString('th-TH') + " ราย";
                        },
                        offsetY: -20,
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: labels,
                        title: {
                            text: 'รหัสข้อผิดพลาด (C Code)',
                            style: { fontWeight: 'bold' }
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'จำนวนผู้ป่วย (ราย)',
                            style: { fontWeight: 'bold' }
                        },
                        labels: {
                            formatter: function (val) {
                                return Math.round(val).toLocaleString('th-TH');
                            }
                        }
                    },
                    legend: {
                        show: false
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val.toLocaleString('th-TH') + " ราย";
                            }
                        }
                    }
                };

                yearlyCCodeChart = new ApexCharts(document.querySelector("#yearlyCCodeChart"), options);
                yearlyCCodeChart.render();
            }
        });
    
        // Deletion handler for REP index
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
                        url: "{{ route('import.rep.delete') }}",
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
    </script>
@endpush
