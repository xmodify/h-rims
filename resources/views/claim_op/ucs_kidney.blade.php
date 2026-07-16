@extends('layouts.app')

@section('content')

<style>
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Custom pastel background for main tabs in ucs_kidney */
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
                สถิติการชดเชยค่าบริการ UCS ฟอกไต
            </h4>
        </div>

        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form id="form_budget_year" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
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
                <h5 class="fw-bold text-secondary">กำลังประมวลผลข้อมูลการฟอกไต...</h5>
                <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาและเชื่อมสถานะชดเชย อาจใช้เวลา 5-15 วินาที โปรดรอสักครู่</p>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
  <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>

  <script>
    let myChart = null;

    function fetchData() {
        // Fallback for any legacy onclick handlers
    }

    // Load Dashboard Content via AJAX
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
                        <h5 class="fw-bold text-secondary">กำลังประมวลผลข้อมูลการฟอกไต...</h5>
                        <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาและเชื่อมสถานะชดเชย อาจใช้เวลา 5-15 วินาที โปรดรอสักครู่</p>
                    </div>
                </div>
            `;
        }

        // Cache the active tab so we can restore it after reload
        const activeTabBtn = document.querySelector('.nav-tabs-modern .nav-link.active');
        const currentActiveTab = activeTabBtn ? activeTabBtn.getAttribute('data-bs-target') : '#search';

        $.ajax({
            url: "{{ url('claim_op/ucs_kidney') }}",
            type: "POST",
            data: $.extend({ _token: "{{ csrf_token() }}" }, dataParams)
        })
        .done(function(res) {
            if (res.success) {
                container.innerHTML = res.table_html;

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

                // Sync picker values
                var start_date_val = $('#start_date').val();
                var end_date_val = $('#end_date').val();
                if(start_date_val) {
                    $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
                }
                if(end_date_val) {
                    $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
                }

                // Sync Changes from Picker to Hidden Input
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

                // Initialize Datatables
                var dt_search = $('#t_search').DataTable({
                    autoWidth: false,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>><rt><"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้มารับบริการ UCS ฟอกไต รอชดเชย'
                    }],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    }
                });

                var dt_claim = $('#t_claim').DataTable({
                    autoWidth: false,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>><rt><"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้มารับบริการ UCS ฟอกไต ชดเชยแล้ว'
                    }],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    }
                });

                // Restore active tab
                const restoredTab = localStorage.getItem('active_tab') || currentActiveTab;
                if (restoredTab) {
                    const tabBtn = document.querySelector(`button[data-bs-target="${restoredTab}"]`);
                    if (tabBtn) {
                        document.querySelectorAll('.nav-tabs-modern .nav-link').forEach(btn => {
                            btn.classList.remove('active');
                            const target = document.querySelector(btn.getAttribute('data-bs-target'));
                            if (target) target.classList.remove('show', 'active');
                        });
                        tabBtn.classList.add('active');
                        const target = document.querySelector(restoredTab);
                        if (target) target.classList.add('show', 'active');
                    }
                    localStorage.removeItem('active_tab');
                }

                // Adjust column headers on tab switch
                $('button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill', function () {
                    dt_search.columns.adjust().draw(false);
                    dt_claim.columns.adjust().draw(false);
                });

                // Draw/Update Chart
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

    // Draw Chart using Chart.js
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

    $(document).ready(function () {
        // First load
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
    });
  </script>
@endpush
