@extends('layouts.app')

@section('content')

<style>
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.badge-type { font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
.badge-ppfs  { background:#fff3cd; color:#856404; }
.badge-uc_cr { background:#cce5ff; color:#004085; }
.badge-herb  { background:#d4edda; color:#155724; }

/* Custom pastel background for main tabs in ucs_outprovince */
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
                สถิติการชดเชยค่าบริการ UC-OP ต่างจังหวัด
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
                <h5 class="fw-bold text-secondary">กำลังประมวลผลข้อมูลการเรียกเก็บและชดเชย...</h5>
                <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาย้อนหลังทั้งปีงบประมาณและเชื่อมสถานะส่งเคลม อาจใช้เวลา 5-15 วินาที โปรดรอสักครู่</p>
            </div>
        </div>
    </div>

    {{-- ── Details Modal ──────────────────────────────────────── --}}
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-clipboard2-pulse-fill me-2"></i>รายละเอียดการรับบริการ</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
  <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>
  <script src="{{ asset('assets/js/claim_bulk_helper.js') }}"></script>

  <script>
    const VISIT_DETAILS_URL = "{{ url('claim_op/ucs_outprovince/visit_details') }}";
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
                        <h6 class="fw-bold text-secondary">กำลังอัปเดตตารางข้อมูลคนไข้...</h6>
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

        // Cache the active tab so we can restore it after reload
        const activeTabBtn = document.querySelector('.nav-tabs-modern .nav-link.active');
        const currentActiveTab = activeTabBtn ? activeTabBtn.getAttribute('data-bs-target') : '#search';

        $.ajax({
            url: "{{ url('claim_op/ucs_outprovince') }}",
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
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้มารับบริการ UC-OP ต่างจังหวัด รอส่ง Claim'
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
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้มารับบริการ UC-OP ต่างจังหวัด ส่ง Claim แล้ว'
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

    // Details Modal and status handlers
    function showDetails(vn) {
        const body = document.getElementById('detailsModalBody');
        body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
        $('#detailsModal').modal('show');

        $.get(VISIT_DETAILS_URL, { vn: vn })
            .done(function(data) {
                const visit = data.visit;
                const items = data.items;
                const v     = data.validation;

                const isEndpointDone = v.endpoint_valid === true;
                const hasWarnings    = v.warnings && v.warnings.length > 0;

                function makeCellHtml(epDone, warn) {
                    if (warn) {
                        return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem;height:26px;margin:0 auto;" onclick="showDetails('${vn}')" title="มี Instrument ไม่อยู่ในประกาศ UCS | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    } else if (epDone) {
                        return `<button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem;height:26px;margin:0 auto;" onclick="showDetails('${vn}')" title="ปิดสิทธิแล้ว"><i class="bi bi-eye-fill"></i></button>`;
                    } else {
                        return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem;height:26px;margin:0 auto;" onclick="showDetails('${vn}')" title="ยังไม่ปิดสิทธิ สปสช."><i class="bi bi-eye-fill"></i></button>`;
                    }
                }
                const dataOrder = (isEndpointDone && !hasWarnings) ? '2' : '1';
                const searchRow = document.getElementById(`td-status-search-${vn}`);
                const claimRow  = document.getElementById(`td-status-claim-${vn}`);
                if (searchRow) { searchRow.innerHTML = makeCellHtml(isEndpointDone, hasWarnings); searchRow.setAttribute('data-order', dataOrder); }
                if (claimRow)  { claimRow.innerHTML  = makeCellHtml(isEndpointDone, hasWarnings); claimRow.setAttribute('data-order', dataOrder); }

                let endpointBtn = isEndpointDone
                    ? `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`
                    : `<button onclick="pullNhsoData('${visit.vstdate}','${visit.cid}','${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;

                let fdhBtn = visit.fdh_status
                    ? `<div class="d-inline-flex gap-2 align-items-center"><span class="badge bg-success py-1 px-2 text-wrap" style="max-width:180px;">${visit.fdh_status}</span><button onclick="checkFdh('${visit.hn}','${vn}')" class="btn btn-outline-success btn-sm py-0 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>ดึงอีกครั้ง</button></div>`
                    : `<div class="d-inline-flex gap-2 align-items-center"><span class="badge bg-secondary py-1 px-2">ยังไม่ได้ส่งเคลม</span><button onclick="checkFdh('${visit.hn}','${vn}')" class="btn btn-outline-info btn-sm py-0 px-2 fw-bold text-dark" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>ดึง/ส่ง FDH</button></div>`;

                let statusHtml = '';
                const warningsList = [];
                if (!isEndpointDone) {
                    warningsList.push("สิทธิ์การรักษายังไม่ได้ปิดสิทธิ์ในระบบ สปสช. (กรุณากดดึงข้อมูลหรือปิดสิทธิ์)");
                }
                if (hasWarnings) {
                    v.warnings.forEach(w => warningsList.push(w));
                }
                
                if (warningsList.length > 0) {
                    statusHtml = `
                    <div class="col-12">
                      <div class="alert alert-warning py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fffbeb; color: #92400e; border-left: 5px solid #d97706 !important;">
                        <i class="bi bi-exclamation-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #d97706;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลผ่านเกณฑ์ แต่ยังไม่ปิดสิทธิ หรือมีคำเตือน</div>
                          <ul class="mb-0 ps-3 text-warning" style="color: #92400e !important;">${warningsList.map(w => `<li>${w}</li>`).join('')}</ul>
                        </div>
                      </div>
                    </div>`;
                } else {
                    statusHtml = `
                    <div class="col-12">
                      <div class="alert alert-success py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #f0fdf4; color: #166534; border-left: 5px solid #16a34a !important;">
                        <i class="bi bi-check-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #16a34a;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลพร้อมส่งออก (ผ่านเกณฑ์และปิดสิทธิเรียบร้อย)</div>
                          <div class="text-muted">ข้อมูลถูกต้องครบถ้วนและทำการปิดสิทธิเรียบร้อยแล้ว</div>
                        </div>
                      </div>
                    </div>`;
                }

                let html = `
                <div class="row g-3">
                  ${statusHtml}
                  <div class="col-md-4">
                    <div class="card border-0 bg-light-soft h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                        <table class="table table-sm table-borderless mb-0 small compact-info-table">
                          <tr><th class="text-muted" style="width:40%">HN</th><td class="fw-bold">${visit.hn}</td></tr>
                          <tr><th class="text-muted">CID</th><td>${visit.cid ?? '-'}</td></tr>
                          <tr><th class="text-muted">ชื่อ-สกุล</th><td>${visit.ptname}</td></tr>
                          <tr><th class="text-muted">สิทธิ์</th><td>${visit.pttype ?? '-'}</td></tr>
                          <tr><th class="text-muted">เพศ/อายุ</th><td>${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : visit.sex)} / ${visit.age_y ?? '-'} ปี</td></tr>
                          <tr><th class="text-muted">ประสงค์เบิก</th><td>${visit.request_funds === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill me-1"></i>N</span>'}</td></tr>
                          <tr><th class="text-muted">พร้อมส่ง</th><td>${visit.confirm_and_locked === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill me-1"></i>N</span>'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card border-0 bg-light-soft h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก</div>
                        <table class="table table-sm table-borderless mb-0 small compact-info-table">
                          <tr><th class="text-muted" style="width:35%">วันที่</th><td>${visit.vstdate} ${visit.vsttime}</td></tr>
                          <tr><th class="text-muted">CC</th><td style="word-break: break-all;">${visit.cc ?? '-'}</td></tr>
                          <tr><th class="text-muted">PDX</th><td class="fw-bold text-danger">${visit.pdx ?? '-'}</td></tr>
                          <tr><th class="text-muted">SDX</th><td style="word-break: break-all;">${data.sec_diags.join(', ') || '-'}</td></tr>
                          <tr><th class="text-muted">ICD-9</th><td style="word-break: break-all;">${data.procedures.join(', ') || '-'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card border-0 bg-light-soft h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-currency-dollar me-1"></i>ข้อมูลการเงิน</div>
                        <table class="table table-sm table-borderless mb-0 small compact-info-table">
                          <tr><th class="text-muted" style="width:40%">เลขใบแจ้งหนี้</th><td class="fw-bold ${visit.debt_id_list ? 'text-success' : 'text-danger'}">${visit.debt_id_list ? visit.debt_id_list : 'ไม่มี (VN: ' + vn + ')'}</td></tr>
                          <tr><th class="text-muted">รวมค่ารักษา</th><td>${parseFloat(visit.income || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ชำระเงินสด</th><td>${parseFloat(visit.rcpt_money || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ยอดเรียกเก็บ</th><td>${parseFloat(visit.claim_price || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">สถานะปิดสิทธิ</th><td>${endpointBtn}</td></tr>
                          <tr><th class="text-muted">สถานะ FDH</th><td>${fdhBtn}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 mt-3">
                    <ul class="nav nav-tabs nav-tabs-custom mb-2" id="modalDetailTabs" role="tablist" style="font-size: 0.85rem;">
                      <li class="nav-item">
                        <button class="nav-link active fw-bold text-primary" id="modal-drugs-tab" data-bs-toggle="tab" data-bs-target="#modal-drugs-panel" type="button" role="tab"><i class="bi bi-capsule me-1"></i>รายการยา</button>
                      </li>
                      <li class="nav-item">
                        <button class="nav-link fw-bold text-success" id="modal-services-tab" data-bs-toggle="tab" data-bs-target="#modal-services-panel" type="button" role="tab"><i class="bi bi-list-check me-1"></i>ค่ารักษาพยาบาล</button>
                      </li>
                    </ul>
                    <div class="tab-content" id="modalDetailTabsContent">
                      <div class="tab-pane fade show active" id="modal-drugs-panel" role="tabpanel" style="font-size: 12px;">
                        <table id="modal-drugs-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                          <thead class="table-dark">
                            <tr>
                              <th>ชื่อยา/เวชภัณฑ์</th>
                              <th class="text-center" width="10%">จำนวน</th>
                              <th class="text-end" width="12%">ราคารวม (บาท)</th>
                              <th class="text-center" width="15%">ประเภทการชำระ</th>
                              <th class="text-center" width="15%">สิทธิการรักษา</th>
                              <th>รหัสมาตรฐาน TMT</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${(function() {
                                let drugsList = items.filter(d => d.icode.startsWith('1'));
                                if (drugsList.length === 0) {
                                    return '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการสั่งยาใน Visit นี้</td></tr>';
                                }
                                return drugsList.map(d => {
                                    let tmtDisplay = d.tmtid 
                                        ? `<span class="badge bg-success fw-bold">${d.tmtid}</span>`
                                        : `<span class="badge bg-secondary-soft text-secondary">ไม่มีรหัส TMT</span>`;
                                    return `<tr>
                                      <td>
                                        <div class="fw-bold text-dark">${d.name}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                      </td>
                                      <td class="text-center fw-bold">${d.qty}</td>
                                      <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                                      <td class="text-center">${d.paids_name || d.paids || '-'}</td>
                                      <td class="text-center">${d.pttype_name || d.pttype || '-'}</td>
                                      <td>${tmtDisplay}</td>
                                    </tr>`;
                                }).join('');
                            })()}
                          </tbody>
                        </table>
                      </div>
                      <div class="tab-pane fade" id="modal-services-panel" role="tabpanel" style="font-size: 12px;">
                        <table id="modal-services-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                          <thead class="table-dark">
                            <tr>
                              <th>ชื่อบริการ/ค่ารักษาพยาบาล</th>
                              <th class="text-center" width="10%">จำนวน</th>
                              <th class="text-end" width="12%">ราคารวม (บาท)</th>
                              <th class="text-center" width="15%">ประเภทการชำระ</th>
                              <th class="text-center" width="15%">สิทธิการรักษา</th>
                              <th>ADP / Kidney / EMS</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${(function() {
                                let servicesList = items.filter(d => !d.icode.startsWith('1'));
                                if (servicesList.length === 0) {
                                    return '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการค่าบริการ/รักษาพยาบาลใน Visit นี้</td></tr>';
                                }
                                return servicesList.map(d => {
                                    let type = '';
                                    if (d.ppfs === 'Y') type += '<span class="badge-type badge-ppfs me-1">PPFS</span>';
                                    if (d.uc_cr === 'Y') type += '<span class="badge-type badge-uc_cr me-1">UC_CR</span>';
                                    if (d.herb32 === 'Y') type += '<span class="badge-type badge-herb me-1">HERB</span>';
                                    if (d.kidney === 'Y') type += '<span class="badge bg-danger text-white ms-1 fw-bold" style="font-size:0.6rem;">ฟอกไต</span>';
                                    if (d.ems === 'Y') type += '<span class="badge bg-warning text-dark ms-1 fw-bold" style="font-size:0.6rem;">EMS</span>';

                                    let adpDisplay = d.nhso_adp_code 
                                        ? `<div class="d-flex align-items-center gap-1"><span class="badge bg-primary fw-bold" style="font-size:0.75rem;">${d.nhso_adp_code}</span>${d.ins_ucs ? `<span class="badge bg-info text-white" style="font-size:0.7rem;">${d.ins_ucs}</span>` : ''}</div>`
                                        : `<span class="badge bg-secondary-soft text-secondary">ไม่มีรหัส ADP</span>`;

                                    return `<tr>
                                      <td>
                                        <div class="fw-bold text-dark">${d.name}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode} ${type}</div>
                                      </td>
                                      <td class="text-center fw-bold">${d.qty}</td>
                                      <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                                      <td class="text-center">${d.paids_name || d.paids || '-'}</td>
                                      <td class="text-center">${d.pttype_name || d.pttype || '-'}</td>
                                      <td>${adpDisplay}</td>
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
            })
            .fail(function() {
                body.innerHTML = '<div class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            });
    }

    function pullNhsoData(vstdate, cid, vn) {
        Swal.fire({
            title: 'กำลังตรวจสอบสิทธิ สปสช....',
            text: 'ระบบกำลังดึงข้อมูลปิดสิทธิ',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
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
            if (!response.ok) throw new Error(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
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
                    if (vn) showDetails(vn);
                    else loadDashboard({ budget_year: $('#form_budget_year select[name="budget_year"]').val(), start_date: $('#start_date').val(), end_date: $('#end_date').val(), skip_chart: 1 });
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
                    if (result.isConfirmed) pushNhsoData(cid, vstdate, vn);
                });
            }
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error.message || 'ไม่สามารถเชื่อมต่อกับระบบได้' });
        });
    }

    function pushNhsoData(cid, vstdate, vn) {
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
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: "{{ route('api.nhso.push_indiv') }}",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}", cid: cid, vstdate: vstdate },
                    success: function(response) {
                        if (response.status == 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'ปิดสิทธิเรียบร้อยแล้ว',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                if (vn) showDetails(vn);
                                else loadDashboard({ budget_year: $('#form_budget_year select[name="budget_year"]').val(), start_date: $('#start_date').val(), end_date: $('#end_date').val(), skip_chart: 1 });
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: response.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล' });
                        }
                    },
                    error: function(xhr) {
                        let msg = 'ไม่สามารถเชื่อมต่อกับระบบได้';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: msg });
                    }
                });
            }
        });
    }

    function checkFdh(hn, seq) {
        Swal.fire({
            title: 'กำลังตรวจสอบสถานะ...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: "{{ url('/api/fdh/check-claim-indiv') }}",
            type: "POST",
            data: { hn: hn, seq: seq, _token: "{{ csrf_token() }}" },
            success: function (res) {
                const isSearchTab = $(`#td-status-search-${seq}`).length > 0;

                if (res.status === 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ตรวจสอบสำเร็จ',
                        text: 'พบข้อมูลในระบบ FDH',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        if (isSearchTab) {
                            localStorage.setItem('active_tab', '#claim');
                            loadDashboard({
                                budget_year: $('#form_budget_year select[name="budget_year"]').val(),
                                start_date: $('#start_date').val(),
                                end_date: $('#end_date').val(),
                                skip_chart: 1
                            });
                        } else {
                            showDetails(seq);
                        }
                    });
                    return;
                }

                if (res.status === 404 || res.status === 500) {
                    const statusText = res.body?.message_th ?? "ไม่มีรายการนี้ส่ง";
                    Swal.fire({ icon: 'warning', title: 'ไม่พบข้อมูลในระบบ FDH', text: statusText }).then(() => { showDetails(seq); });
                    return;
                }

                if (res.status === 400) {
                    const statusText = res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้';
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: statusText }).then(() => { showDetails(seq); });
                    return;
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'การเชื่อมต่อล้มเหลว', text: 'ไม่สามารถเรียก API ได้ (Network Error)' });
            }
        });
    }

    async function checkFdhBulk(e) {
        e.preventDefault();
        const items = window.patientItems || [];
        if (!items || items.length === 0) {
            Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
            return;
        }

        await runFdhBulkCheck(items, "{{ csrf_token() }}", "{{ url('/api/fdh/check-chunk') }}", function() {
            localStorage.setItem('active_tab', '#search');
            loadDashboard({
                budget_year: $('#form_budget_year select[name="budget_year"]').val(),
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                skip_chart: 1
            });
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