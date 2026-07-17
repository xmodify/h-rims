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
                สถิติการชดเชยค่าบริการ IP-OFC กรมบัญชีกลาง
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
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
    window.currentChartData = null;
    window.patientItems = [];

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
          url: "{{ url('claim_ip/ofc') }}",
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
    });
  </script>
@endpush