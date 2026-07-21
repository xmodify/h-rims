@extends('layouts.app')

@section('content')

<style>
/* Custom pastel background for main tabs in claim_ip */
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
                สถิติการชดเชยค่าบริการ IP-SSS ประกันสังคม
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form id="form_budget_year" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกปีงบประมาณ</span>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="start_date" value="{{ $start_date }}">
                        <input type="hidden" name="end_date" value="{{ $end_date }}">
                        <select class="form-select" name="budget_year" style="width: 160px;">
                            @foreach ($budget_year_select as $row)
                              <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                {{ $row->LEAVE_YEAR_NAME }}
                              </option>
                            @endforeach
                        </select>
                        <button type="submit"  class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-graph-up me-1"></i> โหลดกราฟ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal นำเข้าและตรวจสอบผลตอบกลับ (AIPN: REP / STM) -->
    <div class="modal fade" id="importFeedbackModal" tabindex="-1" aria-labelledby="importFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title font-weight-bold" id="importFeedbackModalLabel">
                        <i class="bi bi-file-earmark-zip me-2"></i>นำเข้าและตรวจสอบผลตอบกลับ ประกันสังคม IP (AIPN: REP / STM)
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 mb-4 bg-light rounded border shadow-sm">
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center">
                            <!-- ปุ่มที่ 1: นำเข้าข้อมูล REP (สีน้ำเงิน) -->
                            <div>
                                <input type="file" id="zip_file_rep" style="display: none;" accept=".zip" multiple onchange="uploadAipnZip('rep')">
                                <button type="button" class="btn btn-primary px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_rep').click()">
                                    <i class="bi bi-file-earmark-arrow-up fs-5"></i> นำเข้าข้อมูล REP
                                </button>
                            </div>
                            <!-- ปุ่มที่ 2: นำเข้าข้อมูล STM (สีเขียว) -->
                            <div>
                                <input type="file" id="zip_file_stm" style="display: none;" accept=".zip" multiple onchange="uploadAipnZip('stm')">
                                <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_stm').click()">
                                    <i class="bi bi-file-earmark-check fs-5"></i> นำเข้าข้อมูล STM
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ส่วนแสดงตารางรายชื่อคนไข้ที่ติด C แยกตามแท็บ -->
                    <div class="mt-4 pt-3 border-top">
                        <ul class="nav nav-pills mb-3 gap-2" id="modal-pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active btn-sm fw-bold px-3 py-2 shadow-sm" id="modal-errors-tab" data-bs-toggle="pill" data-bs-target="#modal-errors-pane" type="button" role="tab">
                                    <i class="bi bi-exclamation-circle me-1"></i> ติด C (Error)
                                    <span class="badge bg-danger text-white ms-1 rounded-pill" id="modal-errors-count">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link btn-sm fw-bold px-3 py-2 shadow-sm" id="modal-warnings-tab" data-bs-toggle="pill" data-bs-target="#modal-warnings-pane" type="button" role="tab">
                                    <i class="bi bi-exclamation-triangle me-1"></i> เฉพาะที่มีรหัสเตือน (Warning)
                                    <span class="badge bg-warning text-dark ms-1 rounded-pill" id="modal-warnings-count">0</span>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="modal-pills-tabContent">
                            <!-- แท็บย่อย 1: Errors -->
                            <div class="tab-pane fade show active" id="modal-errors-pane" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="t_modal_errors" class="table table-bordered table-striped align-middle w-100" style="font-size: 0.82rem;">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th width="12%">AN</th>
                                                <th width="12%">HN</th>
                                                <th>ชื่อ-สกุลผู้ป่วย</th>
                                                <th>เลขตอบรับ / ไฟล์</th>
                                                <th width="25%">รหัสผิดพลาด (Error Code)</th>
                                                <th width="8%">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- AJAX Loaded -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- แท็บย่อย 2: Warnings -->
                            <div class="tab-pane fade" id="modal-warnings-pane" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="t_modal_warnings" class="table table-bordered table-striped align-middle w-100" style="font-size: 0.82rem;">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th width="12%">AN</th>
                                                <th width="12%">HN</th>
                                                <th>ชื่อ-สกุลผู้ป่วย</th>
                                                <th>เลขตอบรับ / ไฟล์</th>
                                                <th width="25%">รหัสเตือนภัย (Warning Code)</th>
                                                <th width="8%">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- AJAX Loaded -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div id="data-container">
        <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
            <div class="card-body py-5 text-center">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mt-3 fw-bold text-secondary">กำลังประมวลผลข้อมูลการเรียกเก็บและชดเชย...</h5>
                <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาย้อนหลังทั้งปีงบประมาณและเชื่อมสถานะส่งเคลม อาจใช้เวลา 5-15 วินาที โปรดรอสักครู่</p>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
  <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>
  <script>
    window.currentChartData = null;
    window.patientItems = [];

    window.uploadAipnZip = function(type) {
        const inputId = type === 'rep' ? 'zip_file_rep' : 'zip_file_stm';
        const input = document.getElementById(inputId);
        if (!input || input.files.length === 0) return;

        const files = Array.from(input.files);
        const uploadUrl = type === 'rep' ? "{{ url('claim_ip/sss_aipn_rep_import') }}" : "{{ url('claim_ip/sss_aipn_stm_import') }}";

        let currentIdx = 0;
        let successCount = 0;
        let failCount = 0;
        let summaryHtml = '';

        function processNextFile() {
            if (currentIdx >= files.length) {
                Swal.fire({
                    icon: successCount > 0 ? 'success' : 'error',
                    title: successCount > 0 ? 'นำเข้าสำเร็จ!' : 'นำเข้าไม่สำเร็จ',
                    html: `<b>ประมวลผลเสร็จสิ้นทั้งหมด ${files.length} ไฟล์</b><br>` +
                          `<span class="text-success">สำเร็จ: ${successCount} ไฟล์</span> | ` +
                          `<span class="text-danger">ล้มเหลว: ${failCount} ไฟล์</span><br><br>` +
                          `<div class="text-start small p-2 bg-light border rounded" style="max-height: 150px; overflow-y: auto;">${summaryHtml}</div>`
                }).then(() => {
                    input.value = '';
                    loadDashboard({
                        budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        skip_chart: 1
                    });
                    loadModalErrors();
                });
                return;
            }

            const currentFile = files[currentIdx];
            const percent = Math.round((currentIdx / files.length) * 100);

            if (currentIdx === 0) {
                Swal.fire({
                    title: 'กำลังอัปโหลดและประมวลผลไฟล์...',
                    html: `<b>ไฟล์ที่ ${currentIdx + 1} จากทั้งหมด ${files.length} (${percent}%)</b><br>` +
                          `<span class="text-muted small" style="word-break: break-all;">กำลังดำเนินการ: ${currentFile.name}</span><br><br>` +
                          `<div class="progress" style="height: 10px; background-color: #e9ecef; border-radius: 5px; overflow: hidden;">` +
                          `  <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: ${percent}%; height: 100%;"></div>` +
                          `</div>`,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            } else {
                Swal.update({
                    html: `<b>ไฟล์ที่ ${currentIdx + 1} จากทั้งหมด ${files.length} (${percent}%)</b><br>` +
                          `<span class="text-muted small" style="word-break: break-all;">กำลังดำเนินการ: ${currentFile.name}</span><br><br>` +
                          `<div class="progress" style="height: 10px; background-color: #e9ecef; border-radius: 5px; overflow: hidden;">` +
                          `  <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: ${percent}%; height: 100%;"></div>` +
                          `</div>`
                });
            }

            const formData = new FormData();
            formData.append('_token', "{{ csrf_token() }}");
            formData.append('zip_file', currentFile);

            $.ajax({
                url: uploadUrl,
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        successCount++;
                        summaryHtml += `<span class="text-success">✔ [${currentFile.name}]</span> ${res.message || 'สำเร็จ'}<br>`;
                        currentIdx++;
                        processNextFile();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'นำเข้าไม่สำเร็จ',
                            html: `<b>พบข้อผิดพลาดที่ไฟล์: ${currentFile.name}</b><br>` +
                                  `<span class="text-danger">${res.message || 'เลือกประเภทไฟล์ไม่ถูกต้อง'}</span><br><br>` +
                                  `ระบบได้หยุดการทำงานเพื่อไม่ให้นำเข้าไฟล์ที่เหลือ`
                        });
                        input.value = '';
                    }
                },
                error: function(xhr) {
                    const errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'ไม่สามารถสื่อสารกับเซิร์ฟเวอร์ได้';
                    Swal.fire({
                        icon: 'error',
                        title: 'นำเข้าไม่สำเร็จ',
                        html: `<b>พบข้อผิดพลาดที่ไฟล์: ${currentFile.name}</b><br>` +
                              `<span class="text-danger">${errMsg}</span><br><br>` +
                              `ระบบได้หยุดการทำงานเพื่อไม่ให้นำเข้าไฟล์ที่เหลือ`
                    });
                    input.value = '';
                }
            });
        }

        processNextFile();
    };

    // Global DrawChart function
    function drawChart(labels, claim_price, claim_sent_price, receive_total) {
      const canvas = document.querySelector('#sum_month');
      if (!canvas) return;

      // Destroy old chart instance if exists
      const existingChart = Chart.getChart(canvas);
      if (existingChart) {
          existingChart.destroy();
      }

      new Chart(canvas, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'เรียกเก็บ',
              data: claim_price,
              backgroundColor: 'rgba(185, 28, 28, 0.75)',
              borderColor: 'rgb(185, 28, 28)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'ส่งเคลม',
              data: claim_sent_price,
              backgroundColor: 'rgba(234, 179, 8, 0.6)',
              borderColor: 'rgb(234, 179, 8)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'ชดเชย',
              data: receive_total,
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
              align: 'center',
              labels: {
                usePointStyle: true,
                boxWidth: 6
              }
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
              font: {
                weight: 'bold',
                size: 10
              },
              formatter: (value) => value > 0 ? value.toLocaleString() : ''
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grace: '20%',
              ticks: {
                callback: function(value) {
                  return value.toLocaleString();
                }
              }
            }
          }
        },
        plugins: [ChartDataLabels] 
      });
    }

    function fetchData() {
        // Fallback for legacy handlers
    }

    // Individual FDH Check
    function checkFdh(hn, an) {
        Swal.fire({
            title: 'กำลังตรวจสอบสถานะ...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: "{{ url('/api/fdh/check-claim-indiv') }}",
            type: "POST",
            data: {
                hn: hn,
                an: an,
                _token: "{{ csrf_token() }}"
            },
            success: function (res) {
                if (res.status === 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ตรวจสอบสำเร็จ',
                        text: 'พบข้อมูลในระบบ FDH',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                         loadDashboard({
                             budget_year: $('#form_budget_year select[name="budget_year"]').val(),
                             start_date: $('#start_date').val(),
                             end_date: $('#end_date').val(),
                             skip_chart: 1
                         });
                    });
                    return;
                }
                if (res.status === 404 || res.status === 500) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไม่พบข้อมูลในระบบ FDH',
                        text: res.body?.message_th ?? "ไม่มีรายการนี้ส่ง"
                    });
                    return;
                }
                if (res.status === 400) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้'
                    });
                    return;
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'การเชื่อมต่อล้มเหลว',
                    text: 'ไม่สามารถเรียก API ได้ (Network Error)'
                });
            }
        });
    }

    // FDH Bulk Check
    async function checkFdhBulk(e) {
        e.preventDefault();
        const items = window.patientItems || [];

        if (!items || items.length === 0) {
            Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
            return;
        }

        await runFdhBulkCheck(items, "{{ csrf_token() }}", "{{ url('/api/fdh/check-chunk') }}", function() {
            loadDashboard({
                budget_year: $('#form_budget_year select[name="budget_year"]').val(),
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                skip_chart: 1
            });
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
                      <h6 class="fw-bold text-secondary">กำลังอัปเดตตารางข้อมูลคนไข้...</h6>
                  </div>
              `;
          }
      } else {
          container.innerHTML = `
              <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
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
          url: "{{ url('claim_ip/sss') }}",
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

              var start_date_val = $('#start_date').val();
              var end_date_val = $('#end_date').val();
              if(start_date_val) {
                  $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
              }
              if(end_date_val) {
                  $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
              }

              // Bind Datepicker change
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

              // Re-initialize Datatables (support both standard search/claim and stp/others)
              var dt_search = $('#t_search').DataTable({
                  autoWidth: false,
                  dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                  buttons: [
                      {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้ป่วย รอส่ง Claim วันที่ ' + start_date_val + ' ถึง ' + end_date_val
                      }
                  ],
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
                  buttons: [
                      {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้ป่วย ส่ง Claim แล้ว วันที่ ' + start_date_val + ' ถึง ' + end_date_val
                      }
                  ],
                  language: {
                      search: "ค้นหา:",
                      lengthMenu: "แสดง _MENU_ รายการ",
                      info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                      paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                  }
              });

              var dt_warning = $('#t_warning').DataTable({
                  autoWidth: false,
                  dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                  buttons: [
                      {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้ป่วย ส่ง Claim แล้ว (มีรหัสเตือน) วันที่ ' + start_date_val + ' ถึง ' + end_date_val
                      }
                  ],
                  language: {
                      search: "ค้นหา:",
                      lengthMenu: "แสดง _MENU_ รายการ",
                      info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                      paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                  }
              });

              var dt_visits = $('#t_visits').DataTable({
                  autoWidth: false,
                  dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                  buttons: [
                      {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                        className: 'btn btn-success btn-sm shadow-sm',
                        title: 'รายชื่อผู้มารับบริการ วันที่ ' + start_date_val + ' ถึง ' + end_date_val
                      }
                  ],
                  language: {
                      search: "ค้นหา:",
                      lengthMenu: "แสดง _MENU_ รายการ",
                      info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                      paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                  }
              });

              // Adjust columns on tab change
              $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill', function () {
                  if (typeof dt_search !== 'undefined' && dt_search) dt_search.columns.adjust().draw(false);
                  if (typeof dt_claim !== 'undefined' && dt_claim) dt_claim.columns.adjust().draw(false);
                  if (typeof dt_warning !== 'undefined' && dt_warning) dt_warning.columns.adjust().draw(false);
                  if (typeof dt_visits !== 'undefined' && dt_visits) dt_visits.columns.adjust().draw(false);
              });

              var activeTab = localStorage.getItem('active_tab');
              if (activeTab) {
                  var tabEl = document.querySelector(`button[data-bs-target="${activeTab}"]`);
                  if (tabEl) {
                      tabEl.click();
                  }
                  localStorage.removeItem('active_tab');
              }

                            // Update global chart data
              if (res.chart_data && (res.chart_data.month && res.chart_data.month.length > 0 || !window.currentChartData)) {
                  window.currentChartData = res.chart_data;
              }

              // Draw chart (even if empty)
              if (window.currentChartData) {
                  drawChart(
                      window.currentChartData.month || [],
                      window.currentChartData.claim_price || [],
                      window.currentChartData.claim_sent_price || [],
                      window.currentChartData.receive_total || []
                  );
              }

              // Cache patient items list for FDH bulk checker
              window.patientItems = res.patient_items || [];
          } else {
              container.innerHTML = '<div class="alert alert-danger text-center">ไม่สามารถโหลดข้อมูลได้: ' + (res.message || 'โครงสร้างข้อมูลไม่ถูกต้อง') + '</div>';
          }
      })
      .fail(function() {
          container.innerHTML = '<div class="alert alert-danger text-center">ไม่สามารถโหลดข้อมูลได้</div>';
      });
    }


    // App Initialization & Form binding
    $(document).ready(function () {
      // First load: full dashboard
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

      // Load C-Code errors inside modal when it is opened
      $('#importFeedbackModal').on('shown.bs.modal', function () {
          loadModalErrors();
      });
    });

    // Show REP errors and warnings details via Swal.fire
    window.showRepDetails = function(an) {
        Swal.fire({
            title: 'กำลังโหลดข้อมูลผลตอบกลับ...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.get("{{ url('claim_ip/sss_detail') }}", { an: an })
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
                    title: `ผลตอบกลับ REP (AN: ${an})`,
                    html: html,
                    width: '650px',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#3085d6'
                });
            })
            .fail(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'ดึงข้อมูลล้มเหลว',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
                });
            });
    }

    // Load C-Code errors inside modal
    window.loadModalErrors = function() {
        if ($.fn.DataTable.isDataTable('#t_modal_errors')) {
            $('#t_modal_errors').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#t_modal_warnings')) {
            $('#t_modal_warnings').DataTable().destroy();
        }

        const loadingHtml = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm text-primary me-2"></span>กำลังโหลดข้อมูล...
                </td>
            </tr>
        `;
        $('#t_modal_errors tbody').html(loadingHtml);
        $('#t_modal_warnings tbody').html(loadingHtml);

        $.get("{{ url('claim_ip/sss_rep_errors') }}")
            .done(function(res) {
                if (res.success) {
                    let errorsHtml = '';
                    let warningsHtml = '';
                    let errorsCount = 0;
                    let warningsCount = 0;

                    res.data.forEach(row => {
                        let rowAllBadgesHtml = '';
                        let rowWarningsHtml = '';
                        let hasError = false;
                        let hasWarning = false;

                        if (row.error_codes) {
                            let codes = row.error_codes.split(',');
                            codes.forEach(c => {
                                let cleanC = c.split(':')[0].trim();
                                let isWarn = cleanC.toUpperCase().startsWith('W') || cleanC.startsWith('8');
                                let badgeClass = isWarn ? 'bg-warning text-dark' : 'bg-danger text-white';
                                let badgeHtml = `<span class="badge ${badgeClass} me-1 pointer p-2" onclick="showRepDetails('${row.an}')" style="cursor:pointer; font-size: 0.75rem;" title="คลิกดูรายละเอียด">${c}</span>`;
                                
                                rowAllBadgesHtml += badgeHtml;
                                if (isWarn) {
                                    hasWarning = true;
                                    rowWarningsHtml += badgeHtml;
                                } else {
                                    hasError = true;
                                }
                            });
                        }

                        // Build table row HTML helper
                        let repDisplay = row.rep_file || '-';
                        if (row.repno) {
                            let dateText = row.rep_date ? row.rep_date : '';
                            // simple formatting if date is YYYY-MM-DD
                            if (dateText.includes('-')) {
                                let parts = dateText.split('-');
                                if (parts.length === 3) {
                                    let thYear = parseInt(parts[0], 10) + 543;
                                    dateText = `${parts[2]}/${parts[1]}/${thYear}`;
                                }
                            }
                            let repDateStr = dateText ? `<br><span class="badge bg-light text-dark border mt-1" style="font-size:0.68rem;"><i class="bi bi-calendar-event me-1"></i>${dateText}</span>` : '';
                            repDisplay = `<div class="fw-bold text-dark">#${row.repno}</div><div class="small text-muted text-truncate" style="max-width:180px; font-size:0.75rem;" title="${row.rep_file}">${repDisplay}</div>${repDateStr}`;
                        } else {
                            repDisplay = `<div class="small text-muted text-truncate" style="max-width:180px;" title="${row.rep_file}">${repDisplay}</div>`;
                        }

                        const makeRow = (badges) => `
                            <tr>
                                <td class="text-center fw-bold">${row.an}</td>
                                <td class="text-center">${row.hn}</td>
                                <td class="fw-bold">${row.ptname || '-'}</td>
                                <td class="align-middle">${repDisplay}</td>
                                <td class="text-center">${badges}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary px-2 py-1" onclick="showRepDetails('${row.an}')" title="ดูคำอธิบาย">
                                        <i class="bi bi-search me-1"></i>ดูรายละเอียด
                                    </button>
                                </td>
                            </tr>
                        `;

                        // Tab 1: Show only cases with tcode = C (Reject)
                        if (row.tcode === 'C') {
                            errorsHtml += makeRow(rowAllBadgesHtml);
                            errorsCount++;
                        }

                        // Tab 2: Show cases with warning codes but no C status
                        if (row.tcode !== 'C') {
                            warningsHtml += makeRow(rowAllBadgesHtml);
                            warningsCount++;
                        }
                    });

                    // Update UI counters and table content
                    $('#modal-errors-count').text(errorsCount);
                    $('#modal-warnings-count').text(warningsCount);

                    $('#t_modal_errors tbody').html(errorsHtml || '<tr><td colspan="6" class="text-center text-muted py-4">ไม่มีข้อมูลผู้ป่วยที่ติด C (Error)</td></tr>');
                    $('#t_modal_warnings tbody').html(warningsHtml || '<tr><td colspan="6" class="text-center text-muted py-4">ไม่มีข้อมูลผู้ป่วยที่ติด C (Warning)</td></tr>');

                    // Initialize DataTables
                    const dtConfig = {
                        destroy: true,
                        autoWidth: false,
                        pageLength: 5,
                        lengthMenu: [5, 10, 25, 50],
                        language: {
                            search: "ค้นหา AN/HN/ชื่อ:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                        }
                    };

                    let dt_m_errors, dt_m_warnings;
                    if (errorsCount > 0) {
                        dt_m_errors = $('#t_modal_errors').DataTable(dtConfig);
                    }
                    if (warningsCount > 0) {
                        dt_m_warnings = $('#t_modal_warnings').DataTable(dtConfig);
                    }

                    // Resize tables on modal pill tab changes to prevent squashed columns
                    $('button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill', function () {
                        if (dt_m_errors) dt_m_errors.columns.adjust().draw(false);
                        if (dt_m_warnings) dt_m_warnings.columns.adjust().draw(false);
                    });
                }
            })
            .fail(function() {
                $('#t_modal_errors tbody').html('<tr><td colspan="6" class="text-center text-danger py-4">โหลดข้อมูลล้มเหลว</td></tr>');
                $('#t_modal_warnings tbody').html('<tr><td colspan="6" class="text-center text-danger py-4">โหลดข้อมูลล้มเหลว</td></tr>');
            });
    }
  </script>
@endpush