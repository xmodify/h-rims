@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                {{ $page_title }}
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form id="form_budget_year" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกปีงบประมาณ</span>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="start_date" id="chart_start_date" value="{{ $start_date }}">
                        <input type="hidden" name="end_date" id="chart_end_date" value="{{ $end_date }}">
                        <select class="form-select" name="budget_year" id="main_budget_year" style="width: 160px;">
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
    <div id="dashboard-container">
        <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
            <div class="card-body py-5 text-center">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mt-3 fw-bold text-secondary">กำลังประมวลผลข้อมูลการเรียกเก็บ...</h5>
                <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาย้อนหลังทั้งปีงบประมาณ อาจใช้เวลาสักครู่ โปรดรอสักครู่</p>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>รายละเอียดการรับบริการและสัญญาณชีพ
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>ปิดหน้าต่าง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reusable F16 KTB Export Modal -->
    <x-f16_ktb_export_modal />

<style>
/* Modal details styling */
.bg-light-soft {
    background-color: #f8fafc;
}
.compact-info-table th, .compact-info-table td {
    font-size: 0.78rem !important;
    padding: 4px 8px !important;
    vertical-align: middle;
}
.compact-info-table th {
    white-space: nowrap;
}
#modal-drugs-table th, #modal-drugs-table td,
#modal-services-table th, #modal-services-table td {
    font-size: 0.78rem !important;
    padding: 6px 10px !important;
}
.badge-type {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
}
.badge-ppfs {
    background-color: #e0e7ff;
    color: #3730a3;
}
.badge-uc_cr {
    background-color: #fef3c7;
    color: #92400e;
}
.badge-herb {
    background-color: #dcfce7;
    color: #166534;
}
</style>

<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>

<script>
    var myKtbChart = null;
    var ktbCurrentActivityCode = '{{ $activity_code ?? "S01" }}';
    var ktbCurrentPageTitle = '{{ $page_title }}';
    var ktbRouteUrl = "{{ url('ktb/' . $route_key) }}";

    function drawChart(labels, claim_price) {
        var canvas = document.querySelector('#sum_month');
        if (!canvas) return;

        if (typeof Chart === 'undefined') return;

        if (myKtbChart) {
            myKtbChart.destroy();
        }

        myKtbChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels || [],
                datasets: [
                    {
                        label: 'ยอดเรียกเก็บ (บาท)',
                        data: claim_price || [],
                        backgroundColor: 'rgba(14, 147, 154, 0.8)',
                        borderColor: 'rgb(14, 147, 154)',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 45
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + Number(context.raw || 0).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท';
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#0f172a',
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        formatter: function(value) {
                            return value > 0 ? Number(value).toLocaleString() : '';
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return Number(value).toLocaleString();
                            }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            },
            plugins: typeof ChartDataLabels !== 'undefined' ? [ChartDataLabels] : []
        });
    }

    function loadDashboard(params) {
        var container = document.getElementById('dashboard-container');
        if (!container) return;

        if (typeof Swal !== 'undefined') {
            Swal.close();
        }

        var dataParams = params || {};
        dataParams._token = "{{ csrf_token() }}";

        if (dataParams.skip_chart) {
            var cardBody = $('#dashboard-container .card-body');
            if (cardBody.length > 0) {
                cardBody.html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                        <h6 class="mt-2 fw-bold text-secondary">กำลังอัปเดตตารางข้อมูลคนไข้...</h6>
                    </div>
                `);
            }
        } else {
            container.innerHTML = `
                <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
                    <div class="card-body py-5 text-center">
                        <div class="d-flex justify-content-center mb-3">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <h5 class="mt-3 fw-bold text-secondary">กำลังประมวลผลข้อมูลการเรียกเก็บ...</h5>
                        <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาย้อนหลังทั้งปีงบประมาณ อาจใช้เวลาสักครู่ โปรดรอสักครู่</p>
                    </div>
                </div>
            `;
        }

        $.ajax({
            url: ktbRouteUrl,
            type: "GET",
            data: dataParams,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .done(function(res) {
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }

            if (res.success && res.table_html) {
                if (dataParams.skip_chart) {
                    var tempDiv = $('<div>').html(res.table_html);
                    $('#dashboard-container .card-header').replaceWith(tempDiv.find('.card-header'));
                    $('#dashboard-container .card-body').replaceWith(tempDiv.find('.card-body'));
                } else {
                    container.innerHTML = res.table_html;
                }

                // Re-initialize Datepicker TH
                if (typeof $.fn.datepicker !== 'undefined') {
                    $('.datepicker_th').datepicker({
                        format: 'd M yyyy',
                        language: 'th-th',
                        autoclose: true,
                        todayHighlight: true
                    });

                    var start_date_val = $('#start_date').val();
                    var end_date_val = $('#end_date').val();
                    if(start_date_val) {
                        $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
                    }
                    if(end_date_val) {
                        $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
                    }

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
                }

                // Re-initialize Datatable
                if ($.fn.DataTable) {
                    var dt_search = $('#t_search').DataTable({
                        autoWidth: false,
                        columnDefs: [{ orderable: false, targets: 0 }],
                        lengthMenu: [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "ทั้งหมด"]],
                        dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                        buttons: [
                            {
                                extend: 'excelHtml5',
                                text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                                className: 'btn btn-success btn-sm',
                                title: 'รายชื่อผู้รับบริการ ' + ktbCurrentPageTitle + ' วันที่ ' + ($('#start_date').val() || '') + ' ถึง ' + ($('#end_date').val() || '')
                            }
                        ],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                        }
                    });
                }

                // Update global chart data and render if not skip_chart
                if (!dataParams.skip_chart) {
                    window.currentChartData = res.chart_data || {
                        months: [],
                        claim_price: []
                    };
                    drawChart(
                        window.currentChartData.months || [],
                        window.currentChartData.claim_price || []
                    );
                }
            } else {
                container.innerHTML = '<div class="alert alert-danger text-center">ไม่สามารถโหลดข้อมูลได้: ' + (res.message || 'โครงสร้างข้อมูลไม่ถูกต้อง') + '</div>';
            }
        })
        .fail(function() {
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            container.innerHTML = '<div class="alert alert-danger text-center">ไม่สามารถโหลดข้อมูลได้</div>';
        });
    }

    // ฟังก์ชันรวบรวม VN ที่ถูกเลือกและเปิด Modal ส่งออก 16 แฟ้ม KTB
    function exportSelectedF16KTB() {
        var checkedVns = [];
        var activeTableId = '#t_search';

        if ($(activeTableId).length > 0 && $.fn.DataTable && $.fn.DataTable.isDataTable(activeTableId)) {
            var dt = $(activeTableId).DataTable();
            $(dt.$('.chk_f16_visit:checked')).each(function() {
                var vn = $(this).val();
                if (vn && !checkedVns.includes(vn)) {
                    checkedVns.push(vn);
                }
            });
        }
        
        if (checkedVns.length === 0) {
            $('.chk_f16_visit:checked').each(function() {
                var vn = $(this).val();
                if (vn && !checkedVns.includes(vn)) {
                    checkedVns.push(vn);
                }
            });
        }

        if (checkedVns.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้เลือกรายการ',
                    text: 'กรุณาติ๊กเลือก Checkbox รายการที่ต้องการส่งออก 16 แฟ้ม KTB',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#0e939a'
                });
            } else {
                alert('กรุณาติ๊กเลือก Checkbox รายการที่ต้องการส่งออก 16 แฟ้ม KTB');
            }
            return;
        }

        window.openF16KtbModal(checkedVns, ktbCurrentActivityCode, ktbCurrentPageTitle);
    }

    function showDetails(vn) {
        var body = document.getElementById('detailsModalBody');
        if (!body) return;
        body.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted fw-bold">กำลังดึงข้อมูลการรับบริการและสัญญาณชีพ...</div>
            </div>
        `;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('detailsModal')).show();
        } else if (typeof $ !== 'undefined') {
            $('#detailsModal').modal('show');
        }

        $.get("{{ url('ktb/visit_details') }}", { vn: vn })
        .done(function(data) {
            if (!data.success || !data.visit) {
                body.innerHTML = '<div class="alert alert-danger text-center my-4">ไม่พบข้อมูลการรับบริการของ SEQ นี้</div>';
                return;
            }

            const visit = data.visit;
            const items = data.items || [];
            const v     = data.validation || { is_valid: true, endpoint_valid: true, errors: [], warnings: [] };

            const isEndpointDone = v.endpoint_valid === true || visit.endpoint === 'Y';
            const hasWarnings    = v.warnings && v.warnings.length > 0;

            function makeCellHtml(isValid, epDone, warn) {
                if (!isValid) {
                    return `<button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                } else if (warn) {
                    return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="พบคำเตือน / ตรวจสอบ | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                } else if (epDone) {
                    return `<button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                } else {
                    return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                }
            }

            const dataOrder = !v.is_valid ? '0' : (isEndpointDone && !hasWarnings ? '2' : '1');
            const searchRow = document.getElementById(`td-status-search-${vn}`);
            if (searchRow) {
                searchRow.innerHTML = makeCellHtml(v.is_valid, isEndpointDone, hasWarnings);
                searchRow.setAttribute('data-order', dataOrder);
                if (typeof $ !== 'undefined' && $.fn.DataTable) {
                    if ($.fn.DataTable.isDataTable('#t_search')) {
                        $('#t_search').DataTable().cell(searchRow).invalidate().draw(false);
                    }
                    if ($.fn.DataTable.isDataTable('#t_claim')) {
                        $('#t_claim').DataTable().cell(searchRow).invalidate().draw(false);
                    }
                }
            }

            let endpointBtn = '';
            if (isEndpointDone) {
                endpointBtn = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`;
            } else {
                endpointBtn = `<button onclick="pullNhsoData('${visit.vstdate}', '${visit.cid}', '${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;
            }

            let statusHtml = '';
            if (!v.is_valid) {
                statusHtml = `
                <div class="col-12">
                  <div class="alert alert-danger py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #dc2626 !important;">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.1rem; color: #dc2626;"></i>
                    <div class="w-100">
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">สถานะ: ไม่ผ่านเกณฑ์ส่งออก (มีข้อผิดพลาดที่ต้องแก้ไข)</span>
                        <span class="badge bg-danger text-white">[${ktbCurrentActivityCode}] ${ktbCurrentPageTitle}</span>
                      </div>
                      <ul class="mb-0 ps-3 text-danger mt-1">${v.errors.map(err => `<li>${err}</li>`).join('')}</ul>
                    </div>
                  </div>
                </div>`;
            } else if (hasWarnings || !isEndpointDone) {
                const warningsList = [];
                if (!isEndpointDone) {
                    warningsList.push("สิทธิ์การรักษายังไม่ได้ปิดสิทธิ์ในระบบ สปสช. (กรุณากดดึงข้อมูลหรือปิดสิทธิ์)");
                }
                if (hasWarnings) {
                    v.warnings.forEach(w => warningsList.push(w));
                }
                statusHtml = `
                <div class="col-12">
                  <div class="alert alert-warning py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fffbeb; color: #92400e; border-left: 5px solid #d97706 !important;">
                    <i class="bi bi-exclamation-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #d97706;"></i>
                    <div class="w-100">
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">สถานะ: ข้อมูลผ่านเกณฑ์ แต่ยังไม่ปิดสิทธิ หรือมีคำเตือน</span>
                        <span class="badge bg-warning text-dark">[${ktbCurrentActivityCode}] ${ktbCurrentPageTitle}</span>
                      </div>
                      <ul class="mb-0 ps-3 text-warning mt-1" style="color: #92400e !important;">${warningsList.map(w => `<li>${w}</li>`).join('')}</ul>
                    </div>
                  </div>
                </div>`;
            } else {
                statusHtml = `
                <div class="col-12">
                  <div class="alert alert-success py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #f0fdf4; color: #166534; border-left: 5px solid #16a34a !important;">
                    <i class="bi bi-check-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #16a34a;"></i>
                    <div class="w-100">
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">สถานะ: ข้อมูลพร้อมส่งออก 16 แฟ้ม KTB (ผ่านเกณฑ์และปิดสิทธิเรียบร้อย)</span>
                        <span class="badge bg-success text-white">[${ktbCurrentActivityCode}] ${ktbCurrentPageTitle}</span>
                      </div>
                      <div class="text-muted small mt-1">ข้อมูลถูกต้องครบถ้วนตามสเปกและทำการปิดสิทธิเรียบร้อยแล้ว</div>
                    </div>
                  </div>
                </div>`;
            }

            const ucMoney = visit.uc_money !== undefined && visit.uc_money !== null
                ? parseFloat(visit.uc_money)
                : (parseFloat(visit.income || 0) - parseFloat(visit.rcpt_money || 0));

            let html = `
            <div class="row g-3">
              ${statusHtml}

              <!-- Col 1: ข้อมูลผู้ป่วย -->
              <div class="col-md-4">
                <div class="card border-0 bg-light-soft h-100 shadow-sm">
                  <div class="card-body py-2 px-3">
                    <div class="fw-bold text-primary mb-2 small"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                    <table class="table table-sm table-borderless mb-0 small compact-info-table">
                      <tr><th class="text-muted" style="width:40%">HN</th><td class="fw-bold text-dark">${visit.hn}</td></tr>
                      <tr><th class="text-muted">CID</th><td class="text-dark">${visit.cid || '-'}</td></tr>
                      <tr><th class="text-muted">ชื่อ-สกุล</th><td class="text-dark fw-bold">${visit.ptname || '-'}</td></tr>
                      <tr><th class="text-muted">สิทธิ์การรักษา</th><td class="text-dark">${visit.pttype || '-'}</td></tr>
                      <tr><th class="text-muted">เพศ / อายุ</th><td class="text-dark">${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : (visit.sex || '-'))} / ${visit.age_y ?? '-'} ปี</td></tr>
                      <tr><th class="text-muted">รพ.หลัก (Hospmain)</th><td class="text-dark fw-bold text-danger">${visit.hospmain || '-'}</td></tr>
                      <tr><th class="text-muted">ประสงค์เบิก</th><td>${visit.request_funds === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill me-1"></i>N</span>'}</td></tr>
                      <tr><th class="text-muted">พร้อมส่ง</th><td>${(visit.confirm_and_locked === 'Y' || visit.claim === 'Y' || visit.is_sent == 1) ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill me-1"></i>N</span>'}</td></tr>
                      <tr><th class="text-muted">Authen Code</th><td>${visit.auth_code ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>มี Authen</span>' : '<span class="badge bg-secondary py-0 px-2 text-white">ไม่มี Authen</span>'}</td></tr>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Col 2: ข้อมูลทางคลินิก & สัญญาณชีพ -->
              <div class="col-md-4">
                <div class="card border-0 bg-light-soft h-100 shadow-sm">
                  <div class="card-body py-2 px-3">
                    <div class="fw-bold text-primary mb-2 small"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก & สัญญาณชีพ</div>
                    <table class="table table-sm table-borderless mb-0 small compact-info-table">
                      <tr><th class="text-muted" style="width:35%">วัน-เวลา | Q</th><td class="text-dark">${visit.vstdate} ${visit.vsttime} (Q: ${visit.oqueue || '-'})</td></tr>
                      <tr><th class="text-muted">CC</th><td class="text-dark" style="word-break: break-all;">${visit.cc || '-'}</td></tr>
                      <tr>
                        <th class="text-muted">สัญญาณชีพ</th>
                        <td class="text-dark">
                          <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-info text-dark" title="ความดันโลหิต">BP: ${visit.bps || '-'}/${visit.bpd || '-'}</span>
                            <span class="badge bg-light text-dark border" title="ชีพจร">PR: ${visit.pulse || '-'}</span>
                            <span class="badge bg-light text-dark border" title="อัตราหายใจ">RR: ${visit.rr || '-'}</span>
                            <span class="badge bg-light text-dark border" title="อุณหภูมิ">BT: ${visit.temperature || '-'}°C</span>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <th class="text-muted">กายภาพ</th>
                        <td class="text-dark">
                          <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-light text-dark border" title="น้ำหนัก">BW: ${visit.bw || '-'} kg</span>
                            <span class="badge bg-light text-dark border" title="ส่วนสูง">Ht: ${visit.height || '-'} cm</span>
                            <span class="badge bg-primary text-white" title="ดัชนีมวลกาย">BMI: ${visit.bmi || '-'}</span>
                            <span class="badge bg-warning text-dark" title="รอบเอว">รอบเอว: ${visit.waist || '-'} cm</span>
                          </div>
                        </td>
                      </tr>
                      <tr><th class="text-muted">PDX</th><td class="fw-bold text-danger" style="word-break: break-all;">${visit.pdx || '-'} ${visit.pdx_name ? '<span class="small fw-normal text-muted">(' + visit.pdx_name + ')</span>' : ''}</td></tr>
                      <tr><th class="text-muted">SDX</th><td class="text-dark" style="word-break: break-all;">${data.sec_diags && data.sec_diags.length ? data.sec_diags.join(', ') : (visit.sdx || '-')}</td></tr>
                      <tr><th class="text-muted">ICD-9</th><td class="text-dark" style="word-break: break-all;">${data.procedures && data.procedures.length ? data.procedures.join(', ') : (visit.icd9 || '-')}</td></tr>
                      <tr><th class="text-muted">แพทย์</th><td class="text-dark">${visit.doctor_name || '-'} ${visit.doctor_license ? '<span class="text-muted small">(' + visit.doctor_license + ')</span>' : ''}</td></tr>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Col 3: ข้อมูลการเงิน & การส่งออก KTB -->
              <div class="col-md-4">
                <div class="card border-0 bg-light-soft h-100 shadow-sm">
                  <div class="card-body py-2 px-3">
                    <div class="fw-bold text-primary mb-2 small"><i class="bi bi-currency-dollar me-1"></i>ข้อมูลการเงิน & การส่งออก KTB</div>
                    <table class="table table-sm table-borderless mb-0 small compact-info-table">
                      <tr><th class="text-muted" style="width:40%">เลขใบแจ้งหนี้</th><td class="fw-bold ${visit.debt_id_list ? 'text-success' : 'text-muted'}">${visit.debt_id_list || 'VN: ' + vn}</td></tr>
                      <tr><th class="text-muted">รวมค่ารักษา</th><td class="text-dark">${parseFloat(visit.income || 0).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท</td></tr>
                      <tr><th class="text-muted">ชำระเงินสด</th><td class="text-dark">${parseFloat(visit.rcpt_money || 0).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท</td></tr>
                      <tr><th class="text-muted">ยอดเรียกเก็บ</th><td class="fw-bold text-primary font-monospace" style="font-size: 1.05rem;">${ucMoney.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท</td></tr>
                      <tr><th class="text-muted">สถานะปิดสิทธิ</th><td>${endpointBtn}</td></tr>
                      <tr><th class="text-muted">สถานะส่งออก KTB</th><td>${v.is_valid ? '<span class="badge bg-success text-white py-1 px-2"><i class="bi bi-check-circle-fill me-1"></i>พร้อมส่งออก 16 แฟ้ม</span>' : '<span class="badge bg-danger text-white py-1 px-2"><i class="bi bi-x-circle-fill me-1"></i>ข้อมูลไม่ครบถ้วน</span>'}</td></tr>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Bottom Tabs: Drugs & Services -->
              <div class="col-12 mt-3">
                <ul class="nav nav-tabs nav-tabs-custom mb-2" id="modalDetailTabs" role="tablist" style="font-size: 0.85rem;">
                  <li class="nav-item">
                    <button class="nav-link active fw-bold text-primary" id="modal-drugs-tab" data-bs-toggle="tab" data-bs-target="#modal-drugs-panel" type="button" role="tab">
                      <i class="bi bi-capsule me-1"></i>รายการยาและเวชภัณฑ์ (${items.filter(d => (d.icode || '').startsWith('1')).length})
                    </button>
                  </li>
                  <li class="nav-item">
                    <button class="nav-link fw-bold text-success" id="modal-services-tab" data-bs-toggle="tab" data-bs-target="#modal-services-panel" type="button" role="tab">
                      <i class="bi bi-list-check me-1"></i>ค่าบริการ / ตรวจคัดกรอง / Lab (${items.filter(d => !(d.icode || '').startsWith('1')).length})
                    </button>
                  </li>
                </ul>
                <div class="tab-content" id="modalDetailTabsContent">
                  <!-- Drugs Panel -->
                  <div class="tab-pane fade show active" id="modal-drugs-panel" role="tabpanel" style="font-size: 12px;">
                    <table id="modal-drugs-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                      <thead class="table-dark">
                        <tr>
                          <th>ชื่อยา/เวชภัณฑ์</th>
                          <th class="text-center" width="10%">จำนวน</th>
                          <th class="text-end" width="12%">ราคารวม (บาท)</th>
                          <th class="text-center" width="15%">ประเภทการชำระ</th>
                          <th class="text-center" width="15%">สิทธิการรักษา</th>
                          <th>รหัส TMT</th>
                        </tr>
                      </thead>
                      <tbody>
                        ${(function() {
                            let drugsList = items.filter(d => (d.icode || '').startsWith('1'));
                            if (drugsList.length === 0) {
                                return '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการสั่งยาใน Visit นี้</td></tr>';
                            }
                            return drugsList.map(d => {
                                let tmtDisplay = d.tmtid 
                                    ? `<span class="badge bg-success fw-bold">${d.tmtid}</span>`
                                    : `<span class="badge bg-secondary-soft text-secondary">ไม่มีรหัส TMT</span>`;
                                return `<tr>
                                  <td>
                                    <div class="fw-bold text-dark">${d.name || '-'}</div>
                                    <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                  </td>
                                  <td class="text-center fw-bold">${d.qty}</td>
                                  <td class="text-end font-monospace">${parseFloat(d.sum_price || 0).toFixed(2)}</td>
                                  <td class="text-center">${d.paids_name || d.paids || '-'}</td>
                                  <td class="text-center">${d.pttype_name || d.pttype || '-'}</td>
                                  <td>${tmtDisplay}</td>
                                </tr>`;
                            }).join('');
                        })()}
                      </tbody>
                    </table>
                  </div>

                  <!-- Services / Lab Panel -->
                  <div class="tab-pane fade" id="modal-services-panel" role="tabpanel" style="font-size: 12px;">
                    <table id="modal-services-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                      <thead class="table-dark">
                        <tr>
                          <th>ชื่อบริการ/ตรวจคัดกรอง/Lab</th>
                          <th class="text-center" width="10%">จำนวน</th>
                          <th class="text-end" width="12%">ราคารวม (บาท)</th>
                          <th class="text-center" width="15%">ประเภทการชำระ</th>
                          <th class="text-center" width="15%">สิทธิการรักษา</th>
                          <th class="text-center" width="15%">รหัส ADP (สเปก KTB)</th>
                        </tr>
                      </thead>
                      <tbody>
                        ${(function() {
                            let servicesList = items.filter(d => !(d.icode || '').startsWith('1'));
                            if (servicesList.length === 0) {
                                return '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการค่าบริการ/ตรวจคัดกรองใน Visit นี้</td></tr>';
                            }
                            return servicesList.map(d => {
                                let type = '';
                                if (d.ppfs  === 'Y') type += '<span class="badge-type badge-ppfs me-1">PPFS</span>';
                                if (d.uc_cr === 'Y') type += '<span class="badge-type badge-uc_cr me-1">UC_CR</span>';
                                if (d.herb32=== 'Y') type += '<span class="badge-type badge-herb me-1">Herb</span>';
                                
                                let adpDisplay = d.nhso_adp_code
                                    ? `<span class="badge bg-primary fw-bold">${d.nhso_adp_code}</span>`
                                    : `<span class="badge bg-light text-muted border">-</span>`;

                                return `<tr>
                                  <td>
                                    <div class="fw-bold text-dark">${d.name || '-'} ${type}</div>
                                    <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                  </td>
                                  <td class="text-center fw-bold">${d.qty}</td>
                                  <td class="text-end font-monospace">${parseFloat(d.sum_price || 0).toFixed(2)}</td>
                                  <td class="text-center">${d.paids_name || d.paids || '-'}</td>
                                  <td class="text-center">${d.pttype_name || d.pttype || '-'}</td>
                                  <td class="text-center">${adpDisplay}</td>
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

            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                if ($.fn.DataTable.isDataTable('#modal-drugs-table')) {
                    $('#modal-drugs-table').DataTable().destroy();
                }
                if ($.fn.DataTable.isDataTable('#modal-services-table')) {
                    $('#modal-services-table').DataTable().destroy();
                }

                if (items.filter(d => (d.icode || '').startsWith('1')).length > 0) {
                    $('#modal-drugs-table').DataTable({
                        dom: 'lfrtip',
                        autoWidth: false,
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                        }
                    });
                }

                if (items.filter(d => !(d.icode || '').startsWith('1')).length > 0) {
                    $('#modal-services-table').DataTable({
                        dom: 'lfrtip',
                        autoWidth: false,
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                        }
                    });
                }
            }
        })
        .fail(function(xhr) {
            let msg = 'เกิดข้อผิดพลาดในการดึงข้อมูลรายละเอียด';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
            }
            body.innerHTML = `<div class="alert alert-danger text-center my-4">${msg}</div>`;
        });
    }

    function pullNhsoData(vstdate, cid, vn) {
        if (typeof Swal === 'undefined') {
            alert('กำลังดึงข้อมูล...');
        } else {
            Swal.fire({
                title: 'กำลังดึงข้อมูล...',
                text: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }

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
                throw new Error(data.message || "ล้มเหลว");
            }
            return data;
        })
        .then(data => {
            if (data.found) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'พบข้อมูลปิดสิทธิ',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        showDetails(vn);
                    });
                } else {
                    alert('พบข้อมูลปิดสิทธิ: ' + data.message);
                    showDetails(vn);
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไม่พบการปิดสิทธิจากระบบอื่น',
                        text: 'ยังไม่มีการปิดสิทธิสำหรับรายการนี้ใน สปสช. ต้องการปิดสิทธิด้วยระบบ RiMS หรือไม่?',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ปิดสิทธิทันที',
                        cancelButtonText: 'ยกเลิก'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            pushNhsoData(vstdate, cid, vn);
                        }
                    });
                } else if (confirm('ยังไม่มีการปิดสิทธิสำหรับรายการนี้ใน สปสช. ต้องการปิดสิทธิด้วยระบบ RiMS หรือไม่?')) {
                    pushNhsoData(vstdate, cid, vn);
                }
            }
        })
        .catch(err => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: err.message
                });
            } else {
                alert('เกิดข้อผิดพลาด: ' + err.message);
            }
        });
    }

    function pushNhsoData(vstdate, cid, vn) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'กำลังส่งข้อมูลไปปิดสิทธิ...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }

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
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: 'ปิดสิทธิเรียบร้อยแล้ว',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            showDetails(vn);
                        });
                    } else {
                        alert('ปิดสิทธิเรียบร้อยแล้ว');
                        showDetails(vn);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่สำเร็จ',
                            text: response.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล'
                        });
                    } else {
                        alert(response.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล');
                    }
                }
            },
            error: function(xhr) {
                let msg = 'ไม่สามารถเชื่อมต่อกับระบบได้';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: msg });
                } else {
                    alert(msg);
                }
            }
        });
    }

    function initKtbPage() {
        if (typeof $ === 'undefined') {
            setTimeout(initKtbPage, 50);
            return;
        }

        // จัดการ Event Select All Checkboxes
        $(document).on('change', '.select_all_f16', function() {
            var isChecked = $(this).is(':checked');
            var table = $(this).closest('table');
            if (table.length > 0 && (table.attr('id') === 't_search' || table.attr('id') === 't_claim') && $.fn.DataTable && $.fn.DataTable.isDataTable(table)) {
                var dt = table.DataTable();
                $(dt.$('.chk_f16_visit', { page: 'current' })).prop('checked', isChecked);
            } else {
                table.find('.chk_f16_visit').prop('checked', isChecked);
            }
        });

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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKtbPage);
    } else {
        initKtbPage();
    }
</script>
@endsection
