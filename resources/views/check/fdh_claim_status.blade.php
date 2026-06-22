@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-file-earmark-medical-fill text-success me-2"></i>
                ตรวจสอบสถานะการเคลม FDH
            </h5>
            <div class="text-muted small mt-1">วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</div>
        </div>
        
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
            <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 m-0">
                @csrf
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3"></i></span>
                    <input type="hidden" id="start_date" name="start_date" value="{{ $start_date }}">
                    <input type="text" id="start_date_picker" class="form-control datepicker_th border-start-0 text-center" readonly style="width: 130px; cursor: pointer;">
                    
                    <span class="input-group-text bg-white">ถึง</span>
                    
                    <input type="hidden" id="end_date" name="end_date" value="{{ $end_date }}">
                    <input type="text" id="end_date_picker" class="form-control datepicker_th text-center" readonly style="width: 130px; cursor: pointer;">

                    <button type="submit" class="btn btn-primary px-3 shadow-sm hover-scale">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                </div>
            </form>
            
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#FdhModal">
                <i class="bi bi-cloud-arrow-down-fill me-1"></i> ดึงข้อมูลจาก FDH
            </button>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0 shadow-sm" style="height: auto !important;">
        <div class="card-body p-4">
            <div class="table-responsive">            
                <table id="list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">ลำดับ</th>               
                            <th class="text-center">HN / SEQ / AN</th>
                            <th class="text-center">STATUS</th> 
                            <th class="text-center">PROCESS</th>
                            <th class="text-center">MESSAGE (TH)</th>          
                            <th class="text-center">STM PERIOD</th>   
                        </tr>     
                    </thead> 
                    <tbody> 
                        @php $count = 1; @endphp
                        @foreach($sql as $row) 
                        <tr>
                            <td class="text-center text-muted small">{{ $count }}</td>                 
                            <td class="text-start">
                                <div class="fw-bold text-dark">{{ $row->hn }}</div>
                                <div class="small text-muted">SEQ: {{ $row->seq }} | AN: {{ $row->an }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $row->status == 'ACTIVE' ? 'bg-success-soft text-success' : 'bg-light text-dark border' }} px-2 py-1">
                                    {{ $row->status }}
                                </span>
                            </td>
                            <td class="text-center small">{{ $row->process_status }}</td>
                            <td class="text-start small">{{ $row->status_message_th }}</td>
                            <td class="text-center"><span class="badge bg-primary-soft text-primary">{{ $row->stm_period }}</span></td>
                        </tr>
                        @php $count++; @endphp
                        @endforeach                 
                    </tbody>
                </table> 
            </div>          
        </div> 
    </div>  
</div>     

<!-- Modal -->
<div class="modal fade" id="FdhModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">ดึงข้อมูลจาก FDH</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" id="modalCloseHeaderBtn"></button>
      </div>
      
      <!-- 🔥 FORM เริ่มตรงนี้ -->
      <form id="fdhForm">
        <div class="modal-body">
            <div id="inputsContainer">
                <div class="mb-3">
                    <label for="dateStart" class="form-label">วันที่เริ่มต้น</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="hidden" name="date_start" id="dateStart" value="{{ $start_date }}">
                        <input type="text" id="dateStart_picker" class="form-control datepicker_th text-center" readonly style="cursor: pointer;">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="dateEnd" class="form-label">วันที่สิ้นสุด</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="hidden" name="date_end" id="dateEnd" value="{{ $end_date }}">
                        <input type="text" id="dateEnd_picker" class="form-control datepicker_th text-center" readonly style="cursor: pointer;">
                    </div>
                </div>
            </div>

            <div id="progressContainer" class="mt-3 d-none">
                <div class="progress mb-2" style="height: 20px;">
                    <div id="fdhProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%">0%</div>
                </div>
                <small id="progressText" class="text-muted d-block text-center fw-bold">กำลังเริ่มดึงข้อมูล...</small>
                
                <!-- Real-time log box -->
                <div class="mt-3 border rounded p-2 bg-light" style="max-height: 180px; overflow-y: auto; font-size: 0.75rem; font-family: monospace; line-height: 1.4;" id="fdhLogsBox">
                    <div class="text-muted">เตรียมการดึงข้อมูล...</div>
                </div>
            </div>

            <div id="resultMessage" class="mt-2 d-none"></div>
            <div id="loadingSpinner" class="text-center d-none">
                <div class="spinner-border text-success"></div>
            </div>
        </div>
        <div class="modal-footer" id="modalFooter">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modalCancelBtn">ยกเลิก</button>
            <button type="submit" class="btn btn-success" id="FdhBtn">ดึงข้อมูล</button>
            <button type="button" class="btn btn-success d-none" data-bs-dismiss="modal" id="modalOkBtn">ตกลง</button>
        </div>
      </form>
      <!-- 🔥 FORM จบตรงนี้ -->
    </div>
  </div>
</div>

@endsection

<script>
  document.addEventListener("DOMContentLoaded", function () {
      const form = document.getElementById("fdhForm");
      const spinner = document.getElementById("loadingSpinner");
      const resultMessage = document.getElementById("resultMessage");
      const inputsContainer = document.getElementById("inputsContainer");
      const progressContainer = document.getElementById("progressContainer");
      const progressBar = document.getElementById("fdhProgressBar");
      const progressText = document.getElementById("progressText");
      const modalCloseHeaderBtn = document.getElementById("modalCloseHeaderBtn");
      const modalCancelBtn = document.getElementById("modalCancelBtn");
      const FdhBtn = document.getElementById("FdhBtn");
      const modalOkBtn = document.getElementById("modalOkBtn");

      let isProcessing = false;

      // Prevent closing modal when processing
      const modalEl = document.getElementById('FdhModal');
      modalEl.addEventListener('hide.bs.modal', function (e) {
          if (isProcessing) {
              e.preventDefault();
          }
      });

      // Reset modal state when shown
      modalEl.addEventListener('show.bs.modal', function () {
          isProcessing = false;
          inputsContainer.classList.remove("d-none");
          progressContainer.classList.add("d-none");
          progressBar.style.width = "0%";
          progressBar.textContent = "0%";
          progressText.innerHTML = "กำลังเริ่มดึงข้อมูล...";
          const logsBox = document.getElementById("fdhLogsBox");
          if (logsBox) logsBox.innerHTML = '<div class="text-muted">เตรียมการดึงข้อมูล...</div>';
          resultMessage.classList.add("d-none");
          resultMessage.className = "mt-2 d-none";
          resultMessage.innerHTML = "";
          modalCloseHeaderBtn.classList.remove("d-none");
          modalCancelBtn.classList.remove("d-none");
          FdhBtn.classList.remove("d-none");
          modalOkBtn.classList.add("d-none");
      });

      // ✔ ปิด Modal → Redirect
      modalEl.addEventListener('hidden.bs.modal', function () {
          window.location.href = "{{ url('check/fdh_claim_status') }}";
      });

      function getDatesInRange(startDateStr, endDateStr) {
          const dates = [];
          let currentDate = new Date(startDateStr);
          const endDate = new Date(endDateStr);
          
          currentDate.setHours(0,0,0,0);
          endDate.setHours(0,0,0,0);
          
          while (currentDate <= endDate) {
              const year = currentDate.getFullYear();
              const month = String(currentDate.getMonth() + 1).padStart(2, '0');
              const day = String(currentDate.getDate()).padStart(2, '0');
              dates.push(`${year}-${month}-${day}`);
              currentDate.setDate(currentDate.getDate() + 1);
          }
          return dates;
      }

      form.addEventListener("submit", async function (e) {
          e.preventDefault();

          const dateStartVal = document.getElementById("dateStart").value;
          const dateEndVal = document.getElementById("dateEnd").value;

          if (!dateStartVal || !dateEndVal) {
              alert("กรุณาเลือกวันที่เริ่มต้นและสิ้นสุด");
              return;
          }

          // Start processing
          isProcessing = true;
          inputsContainer.classList.add("d-none");
          progressContainer.classList.remove("d-none");
          modalCloseHeaderBtn.classList.add("d-none");
          modalCancelBtn.classList.add("d-none");
          FdhBtn.classList.add("d-none");
          resultMessage.classList.add("d-none");

          progressBar.style.width = "0%";
          progressBar.textContent = "0%";
          progressText.innerHTML = "กำลังดึงรายชื่อคนไข้จากระบบ...";
          const logsBox = document.getElementById("fdhLogsBox");
          if (logsBox) logsBox.innerHTML = '<div class="text-muted">กำลังดึงรายชื่อคนไข้จากระบบ...</div>';

          // Step 1: Get all check items
          let items = [];
          try {
              const res = await fetch(`{{ url('api/fdh/get-check-list') }}?date_start=${dateStartVal}&date_end=${dateEndVal}`);
              if (!res.ok) throw new Error("HTTP error " + res.status);
              const data = await res.json();
              items = data.items || [];
          } catch (err) {
              console.error(err);
              isProcessing = false;
              inputsContainer.classList.remove("d-none");
              progressContainer.classList.add("d-none");
              modalCloseHeaderBtn.classList.remove("d-none");
              modalCancelBtn.classList.remove("d-none");
              FdhBtn.classList.remove("d-none");
              alert("ไม่สามารถดึงรายชื่อคนไข้ได้: " + err.message);
              return;
          }

          const totalPatients = items.length;
          if (totalPatients === 0) {
              isProcessing = false;
              inputsContainer.classList.remove("d-none");
              progressContainer.classList.add("d-none");
              modalCloseHeaderBtn.classList.remove("d-none");
              modalCancelBtn.classList.remove("d-none");
              FdhBtn.classList.remove("d-none");
              alert("ไม่พบข้อมูลคนไข้ในช่วงเวลาที่เลือก");
              return;
          }

          let successCount = 0;
          let failCount = 0;
          let totalUpdated = 0;
          let totalErrors = 0;

          const chunkSize = 50;

          // Step 2: Loop and check chunk
          for (let i = 0; i < totalPatients; i += chunkSize) {
              const chunk = items.slice(i, i + chunkSize);
              const currentProgress = i + chunk.length;
              const percent = Math.round((currentProgress / totalPatients) * 100);

              progressBar.style.width = percent + "%";
              progressBar.textContent = percent + "%";
              progressText.innerHTML = `กำลังดึงข้อมูลคนไข้ <b>${currentProgress}/${totalPatients}</b> ราย...`;

              try {
                  const response = await fetch("{{ url('api/fdh/check-chunk') }}", {
                      method: "POST",
                      headers: {
                          "Content-Type": "application/json",
                          "X-CSRF-TOKEN": "{{ csrf_token() }}"
                      },
                      body: JSON.stringify({ items: chunk })
                  });

                  if (!response.ok) {
                      throw new Error("HTTP error " + response.status);
                  }

                  const data = await response.json();
                  if (data.success) {
                      successCount++;
                      totalUpdated += parseInt(data.updated_count) || 0;
                      totalErrors += parseInt(data.errors_count) || 0;

                      // แสดงผล Log รายคนไข้ในกล่องข้อความ
                      if (data.details && data.details.length > 0) {
                          data.details.forEach(detail => {
                              const ident = detail.an ? `AN: ${detail.an}` : `SEQ: ${detail.seq}`;
                              let logClass = "text-success";
                              let prefix = "✔ [สำเร็จ]";
                              if (detail.status != 200) {
                                  logClass = detail.status == 404 ? "text-warning" : "text-danger";
                                  prefix = detail.status == 404 ? "⚠ [ไม่พบ]" : `❌ [Error ${detail.status}]`;
                              }
                              const msg = detail.status_message_th || detail.error || '';
                              logsBox.innerHTML += `<div class="${logClass}">${prefix} HN: ${detail.hn} (${ident}) - ${msg}</div>`;
                          });
                          logsBox.scrollTop = logsBox.scrollHeight;
                      }
                  } else {
                      failCount++;
                      totalErrors += chunk.length;
                      logsBox.innerHTML += `<div class="text-danger">❌ [ล้มเหลว] เกิดข้อผิดพลาดในการดึงข้อมูลทั้งกลุ่ม (Chunk)</div>`;
                      logsBox.scrollTop = logsBox.scrollHeight;
                  }
              } catch (err) {
                  console.error(err);
                  failCount++;
                  totalErrors += chunk.length;
                  logsBox.innerHTML += `<div class="text-danger">❌ [ล้มเหลว] เชื่อมต่อหลังบ้านล้มเหลว: ${err.message}</div>`;
                  logsBox.scrollTop = logsBox.scrollHeight;
              }

              // หน่วงเวลาฝั่ง Client 300ms ระหว่างเรียก Chunk ถัดไป เพื่อลดภาระของ Server FDH
              await new Promise(resolve => setTimeout(resolve, 300));
          }

          // Completed
          isProcessing = false;
          progressBar.style.width = "100%";
          progressBar.textContent = "100%";
          progressText.innerHTML = "ดึงข้อมูลเสร็จสิ้น";

          modalCloseHeaderBtn.classList.remove("d-none");
          modalOkBtn.classList.remove("d-none");

          resultMessage.classList.remove("d-none");
          resultMessage.className = "mt-3 alert alert-success text-center";
          resultMessage.innerHTML = `
              <strong>ตรวจสอบเสร็จสิ้น:</strong> ดึงข้อมูลคนไข้ครบถ้วน<br>
              <div class="mt-2 small text-muted">
                  พบข้อมูลคนไข้ทั้งหมด: <b>${totalPatients}</b> ราย | 
                  ดึงสำเร็จ: <span class="badge bg-success text-white">${totalUpdated}</span> ราย | 
                  เกิดข้อผิดพลาด: <span class="badge bg-danger text-white">${totalErrors}</span> ราย
              </div>
          `;
      });
  });
</script>

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

      // Set initial values
      var start_date_val = "{{ $start_date }}";
      var end_date_val = "{{ $end_date }}";
      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
          $('#dateStart_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
          $('#dateEnd_picker').datepicker('setDate', new Date(end_date_val));
      }

      // Sync Changes to Hidden Inputs
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

      $('#list').DataTable({
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + // Show รายการ
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + // Search + Export
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + // Info
                '<"col-md-6"p>' + // Pagination
              '>',
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel',
              className: 'btn btn-success',
              title: 'รายชื่อผู้มารับบริการ ที่ปิดสิทธิ สปสช. วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
            }
        ],
        lengthMenu: [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"] ],
        pageLength: 10,
        stateSave: false,
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
    });
  </script>
@endpush