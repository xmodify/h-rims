@extends('layouts.app')

@section('content')

<style>
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.badge-type { font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
.badge-ppfs  { background:#fff3cd; color:#856404; }
.badge-uc_cr { background:#cfe2ff; color:#084298; }

/* Custom pastel background for main tabs in pvt */
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
                สถิติการชดเชยค่าบริการ OP-PVT บริษัทคู่สัญญา
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
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>รายละเอียดการรับบริการและตรวจสอบสิทธิ์
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

    <!-- Modal Extension Info -->
    <div class="modal fade" id="ExtensionInfoModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow bg-white">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title fw-bold"><i class="bi bi-puzzle-fill text-warning me-2"></i> วิธีติดตั้งและใช้งาน Chrome Extension</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4 text-start">
              <h6 class="fw-bold mb-3 text-primary border-bottom pb-2"><i class="bi bi-1-circle"></i> ขั้นตอนที่ 1 : ติดตั้งส่วนเสริม (ทำครั้งเดียว)</h6>
              <ol class="mb-4 text-muted small lh-lg">
                  <li>ดาวน์โหลดไฟล์ส่วนเสริมลงในเครื่องคอมพิวเตอร์ของคุณ <br/><a href="{{ url('downloads/eclaim_sync.zip') }}" class="btn btn-sm btn-outline-primary mt-1 mb-2"><i class="bi bi-download"></i> ดาวน์โหลด eclaim_sync.zip (เวอร์ชั่นล่าสุด)</a><br/> จากนั้น<b>แตกไฟล์ (Extract / Unzip)</b> ลงในโฟลเดอร์ให้เรียบร้อย (เช่น สร้างโฟลเดอร์ชื่อ <code>eclaim_sync</code> บน Desktop)</li>
                  <li>เปิด Google Chrome และพิมพ์ที่ช่อง URL ด้านบน: <code class="bg-light p-1 text-primary">chrome://extensions/</code> แล้วกด Enter</li>
                  <li>ที่มุมขวาบนของหน้าจอ ให้คลิกเปิดสวิตช์ <b>โหมดนักพัฒนาซอฟต์แวร์ (Developer mode)</b></li>
                  <li>คลิกปุ่ม <b>โหลดส่วนขยายที่ยังไม่ได้แพ็ก (Load unpacked)</b> (มุมซ้ายบน) แล้วคลิกเลือกโฟลเดอร์ <code>eclaim_sync</code> ที่แตกไฟล์ไว้</li>
              </ol>

              <h6 class="fw-bold mb-3 text-warning border-bottom pb-2"><i class="bi bi-gear-fill me-1"></i> ขั้นตอนที่ 2 : ตั้งค่าการส่งข้อมูล (ทำครั้งเดียว)</h6>
              <div class="mb-4 text-muted small">
                  <p class="mb-1">เมื่อติดตั้งแล้ว ให้ตั้งค่าที่อยู่ในการส่งข้อมูล (API URL) ดังนี้:</p>
                  <div class="bg-light p-3 rounded-3 border">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                           <span class="fw-bold text-dark">URL ที่ต้องคัดลอก:</span>
                           <button class="btn btn-xs btn-primary py-0" onclick="copyToClipboard('{{ url('api') }}')">คัดลอก</button>
                      </div>
                      <code id="apiUrlPath" class="text-break text-danger fw-bold">{{ url('api') }}</code>
                  </div>
                  <ol class="mt-2 lh-lg">
                      <li>คลิกที่ไอคอน Extension <b>"RiMS E-Claim Sync"</b></li>
                      <li>คลิกที่ไอคอน <b>⚙️ (ฟันเฟือง)</b> มุมขวาบนของหน้าต่างป๊อปอัป</li>
                      <li><b>คัดลอก URL ด้านบนไปวาง</b> ในช่อง RiMS API URL จากนั้นกด <b>บันทึกการตั้งค่า</b></li>
                  </ol>
              </div>
              
              <h6 class="fw-bold mb-3 text-success border-bottom pb-2"><i class="bi bi-2-circle"></i> ขั้นตอนที่ 3 : วิธีการดึงข้อมูล (ทำรายวัน)</h6>
              <ol class="mb-4 text-muted small lh-lg">
                  <li>ให้ใช้ Google Chrome เปิดหน้าเว็บ <a href="https://eclaim.nhso.go.th/Client" target="_blank" class="text-decoration-underline fw-bold">E-Claim สปสช.</a> และ Login เข้าสู่ระบบ</li>
                  <li>เปิดเข้าสู่หน้าระบบและค้นหาช่วงวันที่ต้องการ เมื่อมีข้อมูลรายชื่อผู้ป่วยแสดงในตารางเรียบร้อยแล้ว</li>
                  <li>คลิกที่ <b>ไอคอนส่วนขยาย "RiMS E-Claim Sync"</b> แล้วกดปุ่ม <b>"ดึงข้อมูลเข้าสู่ RiMS"</b> </li>
                  <li>รอให้ระบบทำการกวาดตารางและส่งข้อมูลเข้าฐานข้อมูลของโรงพยาบาลจนขึ้นข้อความสำเร็จ</li>
              </ol>

              <div class="alert alert-warning py-2 mb-0" style="font-size: 0.85rem">
                 <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <b>หมายเหตุ:</b> หากข้อมูลมีหลายหน้า ต้องคลิกเปลี่ยนหน้า และกดปุ่มซิงค์ทีละหน้า
              </div>
          </div>
          <div class="modal-footer border-0 bg-light">
              <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
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
    const VISIT_DETAILS_URL = "{{ url('claim_op/pvt/visit_details') }}";

    function fetchData() {
        // Fallback for legacy handlers
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showSuccessAlert();
            }).catch(err => {
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    }

    function fallbackCopyToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showSuccessAlert();
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'คัดลอกไม่สำเร็จ',
                text: 'กรุณาคัดลอกด้วยตนเอง: ' + text
            });
        }
        document.body.removeChild(textArea);
    }

    function showSuccessAlert() {
        Swal.fire({
            icon: 'success',
            title: 'คัดลอกแล้ว!',
            text: 'นำไปวางในช่อง RiMS API URL ในหน้าตั้งค่า of Extension ได้เลย',
            timer: 2000,
            showConfirmButton: false
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

        const activeTabBtn = document.querySelector('.nav-tabs-modern .nav-link.active');
        const currentActiveTab = activeTabBtn ? activeTabBtn.getAttribute('data-bs-target') : '#search';

        $.ajax({
            url: "{{ url('claim_op/pvt') }}",
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

                var dt_search = $('#t_search').DataTable({
                    autoWidth: false,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>><rt><"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้มารับบริการ OP-PVT รอส่ง Claim'
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
                        title: 'รายชื่อผู้มารับบริการ OP-PVT ส่ง Claim แล้ว'
                    }],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    }
                });

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

                $('button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill', function () {
                    dt_search.columns.adjust().draw(false);
                    dt_claim.columns.adjust().draw(false);
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

                function makeCellHtml(isValid, epDone, warn) {
                    if (!isValid) {
                        return `<button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    } else if (epDone) {
                        return `<button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    } else {
                        return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    }
                }

                const dataOrder = !v.is_valid ? 0 : (isEndpointDone && !hasWarnings ? 2 : 1);

                const searchRow = document.getElementById(`td-status-search-${vn}`);
                const claimRow  = document.getElementById(`td-status-claim-${vn}`);
                if (searchRow) {
                    searchRow.innerHTML = makeCellHtml(v.is_valid, isEndpointDone, hasWarnings);
                    searchRow.setAttribute('data-order', dataOrder);
                }
                if (claimRow) {
                    claimRow.innerHTML = makeCellHtml(v.is_valid, isEndpointDone, hasWarnings);
                    claimRow.setAttribute('data-order', dataOrder);
                }

                let endpointBtn = '';
                if (v.endpoint_valid) {
                    endpointBtn = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`;
                } else {
                    endpointBtn = `<button onclick="pullNhsoData('${visit.vstdate}', '${visit.cid}', '${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;
                }

                let statusHtml = '';
                if (!v.is_valid) {
                    statusHtml = `
                    <div class="col-12">
                      <div class="alert alert-danger py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #dc2626 !important;">
                        <i class="bi bi-x-octagon-fill me-2 mt-1" style="font-size: 1.1rem; color: #dc2626;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ไม่ผ่านเกณฑ์ส่งออก (มีข้อผิดพลาดที่ต้องแก้ไข)</div>
                          <ul class="mb-0 ps-3 text-danger" style="color: #991b1b !important;">${v.errors.map(err => `<li>${err}</li>`).join('')}</ul>
                        </div>
                      </div>
                    </div>`;
                } else if (!isEndpointDone) {
                    statusHtml = `
                    <div class="col-12">
                      <div class="alert alert-warning py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fffbeb; color: #92400e; border-left: 5px solid #d97706 !important;">
                        <i class="bi bi-exclamation-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #d97706;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลผ่านเกณฑ์ แต่ยังไม่ปิดสิทธิ (สปสช.)</div>
                          <div class="text-muted">ข้อมูลผ่านเกณฑ์การตรวจสอบแล้ว แต่กรุณากดดึงข้อมูลหรือปิดสิทธิเพื่อส่งออก</div>
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
                          <tr><th class="text-muted" style="width:40%">ยอดค่ารักษา</th><td>${parseFloat(visit.income || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ต้องชำระ/เอง</th><td>${parseFloat(visit.paid_money || 0).toFixed(2)} / ${parseFloat(visit.rcpt_money || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ยอดเรียกเก็บ</th><td class="fw-bold text-primary">${parseFloat(visit.income - visit.rcpt_money).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ชดเชย PVT</th><td class="text-success fw-bold">${parseFloat(visit.receive_total || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ชดเชย PP</th><td class="text-info fw-bold">${parseFloat(visit.receive_pp || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">สถานะปิดสิทธิ์</th><td>${endpointBtn}</td></tr>
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
                                    let tmtDisplay = d.tmt_code 
                                        ? `<span class="badge bg-success fw-bold">${d.tmt_code}</span>`
                                        : `<span class="badge bg-secondary-soft text-secondary">ไม่มีรหัส TMT</span>`;
                                    return `<tr>
                                      <td>
                                        <div class="fw-bold text-dark">${d.name}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                      </td>
                                      <td class="text-center fw-bold">${parseFloat(d.qty).toFixed(0)}</td>
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
                              <th>ADP Code</th>
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
                                    if (d.ppfs  === 'Y') type += '<span class="badge-type badge-ppfs me-1">PPFS</span>';
                                    if (d.ems === 'Y') type += '<span class="badge-type me-1" style="background:#cfe2ff;color:#084298;">EMS</span>';
                                    
                                    return `<tr>
                                      <td>
                                        <div class="fw-bold text-dark">${d.name ?? '-'} ${type}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                      </td>
                                      <td class="text-center fw-bold">${parseFloat(d.qty).toFixed(0)}</td>
                                      <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                                      <td class="text-center">${d.paids_name || d.paids || '-'}</td>
                                      <td class="text-center">${d.pttype_name || d.pttype || '-'}</td>
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

                if ($.fn.DataTable.isDataTable('#modal-drugs-table')) {
                    $('#modal-drugs-table').DataTable().destroy();
                }
                if ($.fn.DataTable.isDataTable('#modal-services-table')) {
                    $('#modal-services-table').DataTable().destroy();
                }

                if (items.filter(d => d.icode.startsWith('1')).length > 0) {
                    $('#modal-drugs-table').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                        }
                    });
                }

                if (items.filter(d => !d.icode.startsWith('1')).length > 0) {
                    $('#modal-services-table').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                        }
                    });
                }

                $('#modalDetailTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });
            })
            .fail(function(xhr) {
                body.innerHTML = `<div class="text-danger py-4 text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i>ไม่สามารถดึงข้อมูลได้: ${xhr.responseJSON?.error ?? 'ข้อผิดพลาดระบบ'}</div>`;
            });
    }

    function pullNhsoData(vstdate, cid, vn) {
        Swal.fire({
            title: 'กำลังดึงข้อมูล...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() }
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
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    showDetails(vn);
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
                    title: 'กำลังส่งข้อมูล...',
                    text: 'กรุณารอสักครู่',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading() }
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
                                if (vn) {
                                    showDetails(vn);
                                } else {
                                    loadDashboard({
                                        budget_year: $('#form_budget_year select[name="budget_year"]').val(),
                                        start_date: $('#start_date').val(),
                                        end_date: $('#end_date').val(),
                                        skip_chart: 1
                                    });
                                }
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
