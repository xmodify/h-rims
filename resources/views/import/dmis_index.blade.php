@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card accent-9" style="border-radius: 15px; border-left: 5px solid #198754; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div class="card-body p-4">
                    <form id="importForm" onsubmit="simulateProcess(event)" action="{{ route('import.dmis.save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-excel me-2 text-success"></i> นำเข้าไฟล์ Seamless For DMIS (Excel Only)</h6>
                            <p class="text-muted small">เลือกไฟล์ Excel (.xlsx, .xls) ได้ไม่จำกัดจำนวนไฟล์</p>
                        </div>
                        
                        <div class="input-group mb-0">
                            <input class="form-control" id="formFile" type="file" name="files[]" multiple accept=".xlsx,.xls" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-success px-4" type="submit" style="border-radius: 0 10px 10px 0;">
                                <i class="bi bi-cloud-upload me-2"></i> นำเข้าข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header & Actions -->
    <div class="page-header-box mt-2 mb-3">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-puzzle-fill text-warning me-2"></i>
                ระบบนำเข้าข้อมูลและติดตาม Seamless For DMIS
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณประจำปัจจุบัน: {{ $budget_year }}</div>
            <div class="mt-2 d-flex gap-2">
                <a href="{{ route('import.dmis.detail') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> รายละเอียดข้อมูลธุรกรรม
                </a>
                <button type="button" class="btn btn-info btn-sm rounded-pill px-3 text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#chartModal" id="btnShowChart">
                    <i class="bi bi-bar-chart-fill me-1"></i> กราฟสรุปรายเดือน
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('import.dmis') }}" class="m-0" id="filterForm">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small text-nowrap">ปีงบประมาณ:</span>
                <select class="form-select form-select-sm text-center" name="budget_year" id="budget_year_select" style="width: 180px; border-radius: 8px;">
                    @foreach ($budget_year_select as $row)
                        <option value="{{ $row->LEAVE_YEAR_ID }}"
                            {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                            {{ $row->LEAVE_YEAR_NAME }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-search me-1"></i> ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="t_dmis_list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">ชื่อไฟล์นำเข้า</th>
                            <th class="text-center">กองทุน</th>
                            <th class="text-center">จำนวนคิวส่ง</th>
                            <th class="text-center">จำนวนราย</th>
                            <th class="text-center">ยอดขอเบิก (บาท)</th>
                            <th class="text-center">ยอดชดเชยจริง (บาท)</th>
                            <th class="text-center">งวด/เลขที่เบิกจ่าย</th>
                            <th class="text-center">เลขที่ใบเสร็จ</th>
                            <th class="text-center">วันที่ออกใบเสร็จ</th>
                            <th class="text-center">ผู้ออกใบเสร็จ</th>
                            @if(Auth::user()->allow_receipt == 'Y')
                                <th class="text-center" width="10%">การจัดการ</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stm_dmis as $row)
                        <tr data-group="{{ $row->claim_type_name }}">
                            <td class="small fw-bold text-dark">{{ $row->excel_filename }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-primary border">
                                    {{ $row->claim_type_name }}
                                </span>
                            </td>
                            <td class="text-center fw-bold text-muted">{{ number_format($row->rep_count) }}</td>
                            <td class="text-end fw-bold">{{ number_format($row->count_rows) }}</td>
                            <td class="text-end text-muted">{{ number_format($row->claim_price, 2) }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($row->receive_total, 2) }}</td>
                            <td class="text-center text-primary fw-bold">{{ $row->round_no }}</td>
                            <td class="text-center text-primary fw-bold receive-no-val">{{ $row->receive_no }}</td>
                            <td class="text-center small receipt-date-val">{{ $row->receipt_date }}</td>
                            <td class="text-center small text-muted receipt-by-val">{{ $row->receipt_by }}</td>
                            @if(Auth::user()->allow_receipt == 'Y')
                                <td class="text-center">
                                    @if(!empty($row->round_no))
                                        <button type="button"
                                            class="btn btn-xs {{ $row->receive_no ? 'btn-outline-warning' : 'btn-outline-danger' }} rounded-pill px-3 btn-action-receipt"
                                            data-round="{{ $row->round_no }}"
                                            data-receive="{{ $row->receive_no }}"
                                            data-date="{{ $row->receipt_date }}"
                                            title="{{ $row->receive_no ? 'แก้ไขใบเสร็จ' : 'ออกใบเสร็จ' }}">
                                            <i class="bi {{ $row->receive_no ? 'bi-pencil-square' : 'bi-plus-circle' }} me-1"></i>
                                            {{ $row->receive_no ? 'แก้ไข' : 'ออกใบเสร็จ' }}
                                        </button>
                                    @endif
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


<!-- Modal: Monthly Summary Chart -->
<div class="modal fade" id="chartModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon-box icon-bg-1 mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background-color: #3b82f6; border-radius: 12px; color: white;">
                        <i class="bi bi-bar-chart-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="db_title">Dashboard</h5>
                        <div class="text-muted small" id="db_subtitle">ระบบนำเข้าข้อมูลและติดตาม Seamless For DMIS</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-4">
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                            <i class="bi bi-calendar3 me-1"></i> ข้อมูลรายเดือน
                        </span>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small text-nowrap">กองทุน:</span>
                                <select class="form-select shadow-sm" id="modal_filter_claim_type" style="width: 350px; border-radius: 8px;">
                                    <option value="">-- ทั้งหมด --</option>
                                    @foreach($claim_types as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small text-nowrap">ปีงบประมาณ:</span>
                                <select class="form-select shadow-sm text-center" id="modal_filter_budget_year" style="width: 170px; border-radius: 8px;">
                                    @foreach ($budget_year_select as $row)
                                        <option value="{{ $row->LEAVE_YEAR_ID }}"
                                            {{ (int)$budget_year_now === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
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
                    <canvas id="monthlySummaryChart"></canvas>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Issue Receipt -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="receiptModalTitle">ออกใบเสร็จรับเงิน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="modal_round_no">
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">เลขที่งวด / เลขที่เบิกจ่าย</label>
                    <input type="text" class="form-control" id="modal_round_no_display" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">เลขที่ใบเสร็จ</label>
                    <input type="text" class="form-control" id="modal_receive_no" placeholder="กรอกเลขที่ใบเสร็จ">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">วันที่ออกใบเสร็จ</label>
                    <input type="text" id="modal_receipt_date_picker" class="form-control datepicker_th text-center" readonly style="cursor: pointer;">
                    <input type="hidden" id="modal_receipt_date">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btnSaveReceipt">บันทึกข้อมูล</button>
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
            confirmButtonColor: '#198754'
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
            text: "{!! session('error') !!}",
            confirmButtonText: 'ปิด',
            confirmButtonColor: '#d33'
        });
    });
</script>
@endif

@endsection

@push('scripts')
<script>
    // Filename validation pattern, allowing suffixes like (1)
    const dmisPattern = /^\d{5}_[A-Z]{4}([A-Z0-9]{10})\s*\(?\d*\)?\.xlsx?$/i;

    function showLoadingAlert() {
        Swal.fire({
            title: 'กำลังนำเข้าข้อมูล...',
            text: 'กรุณารอสักครู่ ระบบกำลังอ่านไฟล์ Excel ขนาดใหญ่',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    window.simulateProcess = async function(event) {
        event.preventDefault(); 
        const fileInput = document.getElementById('formFile');
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                title: 'แจ้งเตือน',
                text: 'กรุณาเลือกไฟล์ก่อนนำเข้า',
                icon: 'warning',
                confirmButtonText: 'ปิด',
                confirmButtonColor: '#d33'
            });
            return;
        }

        const rawFiles = Array.from(fileInput.files);
        const invalidFileNames = [];

        // Validate all files first
        rawFiles.forEach(file => {
            if (!dmisPattern.test(file.name)) {
                invalidFileNames.push(file.name);
            }
        });

        // Warn user if there are invalid files
        if (invalidFileNames.length > 0) {
            Swal.fire({
                title: 'ตรวจพบไฟล์ไม่ถูกต้อง',
                html: `ระบบไม่อนุญาตให้นำเข้าไฟล์ที่ไม่ใช่รูปแบบ By Period ดังนี้:<br><ul class="text-start mt-2 text-danger small" style="max-height: 150px; overflow-y: auto;">` + 
                      invalidFileNames.map(f => `<li>${f}</li>`).join('') + `</ul>กรุณาตรวจสอบชื่อไฟล์อีกครั้ง`,
                icon: 'error',
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#d33'
            });
            return;
        }

        // Show loading alert
        showLoadingAlert();

        // Submit form natively
        document.getElementById('importForm').submit();
    };

    $(document).ready(function () {
        // Initialize DataTable
        var table = $('#t_dmis_list').DataTable({
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>t<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'รายงานผลการจ่ายชดเชย DMIS ปีงบประมาณ {{ $budget_year }}'
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

        // --- Receipt Modal handling ---
        var currentBtn = null;
        $(document).on('click', '.btn-action-receipt', function () {
            currentBtn = $(this);
            var round = $(this).data('round');
            var receive = $(this).data('receive');
            var date = $(this).data('date');

            $('#modal_round_no').val(round);
            $('#modal_round_no_display').val(round);
            $('#modal_receive_no').val(receive);
            
            if (date) {
                $('#modal_receipt_date_picker').datepicker('setDate', new Date(date));
                $('#modal_receipt_date').val(date);
            } else {
                $('#modal_receipt_date_picker').datepicker('setDate', new Date());
                $('#modal_receipt_date').val('{{ date("Y-m-d") }}');
            }

            $('#receiptModal').modal('show');
        });

        $('#modal_receipt_date_picker').on('changeDate', function(e) {
            var date = e.date;
            if(date) {
                var day = ("0" + date.getDate()).slice(-2);
                var month = ("0" + (date.getMonth() + 1)).slice(-2);
                var year = date.getFullYear();
                $('#modal_receipt_date').val(year + "-" + month + "-" + day);
            }
        });

        $('#btnSaveReceipt').on('click', function () {
            var round = $('#modal_round_no').val();
            var receive = $('#modal_receive_no').val();
            var date = $('#modal_receipt_date').val();

            if (!receive) {
                Swal.fire({ icon: 'warning', title: 'กรุณากรอกเลขที่ใบเสร็จ', confirmButtonColor: '#d33' });
                return;
            }

            $.ajax({
                url: "{{ route('import.dmis.updateReceipt') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    round_no: round,
                    receive_no: receive,
                    receipt_date: date
                },
                success: function (res) {
                    $('#receiptModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: 'บันทึกเลขที่ใบเสร็จเรียบร้อยแล้ว',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (currentBtn) {
                            var tr = currentBtn.closest('tr');
                            tr.find('.receive-no-val').text(receive);
                            tr.find('.receipt-date-val').text(date);
                            tr.find('.receipt-by-val').text('{{ auth()->user()->name ?? "system" }}');
                            
                            currentBtn.removeClass('btn-outline-danger').addClass('btn-outline-warning').text('แก้ไข');
                            currentBtn.data('receive', receive);
                            currentBtn.data('date', date);
                        }
                    });
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถออกใบเสร็จได้', confirmButtonColor: '#d33' });
                }
            });
        });

        // --- Chart Modal Handling ---
        let monthlyChart = null;

        // Load Chart.js CDN dynamically
        if (typeof Chart === 'undefined') {
            const chartScript = document.createElement('script');
            chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
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

            $('#modal_filter_claim_type, #modal_filter_budget_year').on('change', function () {
                loadChartData();
            });
        }

        function loadChartData() {
            const claimType = $('#modal_filter_claim_type').val();
            const budgetYear = $('#modal_filter_budget_year').val();
            const budgetYearText = $('#modal_filter_budget_year option:selected').text().trim();

            const claimText = claimType ? claimType : 'ทั้งหมด';
            $('#db_subtitle').text(`ระบบนำเข้าข้อมูลและติดตาม Seamless For DMIS กองทุน: ${claimText} ${budgetYearText}`);

            $('#chart_container').addClass('d-none');
            $('#loading_spinner').removeClass('d-none');

            $.ajax({
                url: "{{ route('import.dmis.chart-data') }}",
                method: "GET",
                data: {
                    budget_year: budgetYear,
                    claim_type: claimType
                },
                success: function (res) {
                    $('#loading_spinner').addClass('d-none');
                    $('#chart_container').removeClass('d-none');
                    renderChart(res.labels, res.claim_prices, res.receive_totals);
                },
                error: function () {
                    $('#loading_spinner').addClass('d-none');
                    $('#chart_container').removeClass('d-none');
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถดึงข้อมูลกราฟได้', confirmButtonColor: '#d33' });
                }
            });
        }

        function renderChart(labels, claimPrices, receiveTotals) {
            const ctx = document.getElementById('monthlySummaryChart').getContext('2d');
            
            if (monthlyChart) {
                monthlyChart.destroy();
            }

            monthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'เรียกเก็บ (Debtor)',
                            data: claimPrices,
                            backgroundColor: 'rgba(185, 28, 28, 0.75)',
                            borderColor: 'rgb(185, 28, 28)',
                            borderWidth: 1,
                            borderRadius: 8,
                            barPercentage: 0.8,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'ชดเชย (Receive)',
                            data: receiveTotals,
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 1,
                            borderRadius: 8,
                            barPercentage: 0.8,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                plugins: [{
                    id: 'barLabels',
                    afterDatasetsDraw(chart, args, options) {
                        const { ctx } = chart;
                        ctx.save();
                        ctx.font = 'bold 9px sans-serif';
                        ctx.fillStyle = '#333';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';

                        chart.data.datasets.forEach((dataset, i) => {
                            chart.getDatasetMeta(i).data.forEach((bar, index) => {
                                const value = dataset.data[index];
                                if (value > 0) {
                                    const formattedValue = new Intl.NumberFormat('th-TH').format(value);
                                    ctx.fillText(formattedValue, bar.x, bar.y - 4);
                                }
                            });
                        });
                        ctx.restore();
                    }
                }],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('th-TH') + ' ฿';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += new Intl.NumberFormat('th-TH').format(context.raw);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
