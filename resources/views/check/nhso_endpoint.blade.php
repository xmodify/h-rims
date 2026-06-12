@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="row">
        <div class="col-12 px-3">
            <div class="page-header-box mt-3 mb-4">
                <div>
                    <h5 class="text-dark mb-0 fw-bold">
                        <i class="bi bi-hospital-fill text-danger me-2"></i>
                        ดึงข้อมูลปิดสิทธิจาก สปสช. (NHSO Endpoint Pull)
                    </h5>
                    <div class="text-muted small mt-1">วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</div>
                </div>
                
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 m-0">
                        @csrf
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-event"></i></span>
                            <input type="hidden" id="start_date" name="start_date" value="{{ $start_date }}">
                            <input type="text" id="start_date_picker" class="form-control datepicker_th border-start-0 text-center" readonly style="width: 140px; cursor: pointer;">
                            
                            <span class="input-group-text bg-white">ถึง</span>
                            
                            <input type="hidden" id="end_date" name="end_date" value="{{ $end_date }}">
                            <input type="text" id="end_date_picker" class="form-control datepicker_th text-center" readonly style="width: 140px; cursor: pointer;">
                            <button type="submit" class="btn btn-primary px-3">
                                <i class="bi bi-search me-1"></i> ค้นหา
                            </button>
                        </div>
                    </form>
                    
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#nhsoModal">
                        <i class="bi bi-download me-1"></i> ดึงปิดสิทธิ สปสช.
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .custom-pills .nav-link {
            background: #fff;
            color: #64748b;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .custom-pills .nav-link.active {
            background: #3b82f6 !important;
            color: #fff !important;
            border-color: #3b82f6 !important;
        }
        .custom-pills .nav-link:hover:not(.active) {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .badge-pending { background-color: #ef4444; }
        .badge-closed { background-color: #3b82f6; }
    </style>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills custom-pills mb-3 gap-2" id="endpointTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 shadow-sm" id="closed-tab" data-bs-toggle="tab" data-bs-target="#closed" type="button" role="tab" aria-controls="closed" aria-selected="true">
                <i class="bi bi-check-circle-fill me-2"></i> ปิดสิทธิ สปสช. แล้ว 
                <span class="badge bg-white text-primary ms-1">{{ count($closed) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 shadow-sm" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">
                <i class="bi bi-clock-history me-2"></i> รอปิดสิทธิ สปสช.
                <span class="badge bg-white text-danger ms-1">{{ count($pending) }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="endpointTabContent">
        <!-- Tab 1: Closed Records -->
        <div class="tab-pane fade show active" id="closed" role="tabpanel" aria-labelledby="closed-tab">
            <div class="card dash-card border-top-0 shadow-sm" style="height: auto !important;">
                <div class="card-body p-4">
                    <div class="table-responsive">            
                        <table id="list_closed" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">ลำดับ</th>               
                                    <th class="text-center">ชื่อ-นามสกุล</th>
                                    <th class="text-center">CID</th>
                                    <th class="text-center">สิทธิ สปสช.</th> 
                                    <th class="text-center">วัน-เวลาที่รับบริการ</th>
                                    <th class="text-center">Claim Type</th>
                                    <th class="text-center">Claim Code</th>          
                                </tr>     
                            </thead> 
                            <tbody> 
                                @foreach($closed as $index => $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $index + 1 }}</td>                 
                                    <td class="text-start fw-bold text-dark small">{{ $row->firstName }} {{ $row->lastName }}</td>
                                    <td class="text-center small text-muted">{{ $row->cid }}</td>
                                    <td class="text-start">
                                        <div class="small text-muted lh-1" style="font-size: 0.75rem;">{{ $row->subInsclName ?: ($row->subInscl ?? '-') }}</div>
                                    </td>  
                                    <td class="text-center small">{{ DatetimeThai($row->serviceDateTime) }}</td>
                                    <td class="text-center">
                                        <div class="small text-primary fw-bold">{{ $row->claimType }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-soft text-success">{{ $row->claimCode }}</span>
                                    </td>
                                </tr>
                                @endforeach                 
                            </tbody>
                        </table> 
                    </div>          
                </div> 
            </div>
        </div>

        <!-- Tab 2: Pending Records -->
        <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
            <div class="card dash-card border-top-0 shadow-sm" style="height: auto !important;">
                <div class="card-body p-4">
                    <div class="table-responsive">            
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" id="btn_bulk_push" disabled>
                                <i class="bi bi-send-check-fill me-1"></i> ส่งปิดสิทธิรายการที่เลือก (<span id="selected_count">0</span>)
                            </button>
                            <div class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i> เลือกรายการที่ต้องการแล้วกดปุ่มเพื่อส่งปิดสิทธิทีละรายการ
                            </div>
                        </div>
                        <table id="list_pending" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 30px;">
                                        <input type="checkbox" class="form-check-input" id="check_all_pending">
                                    </th>
                                    <th class="text-center">ลำดับ</th>
                                    <th class="text-center">AUTHEN</th>
                                    <th class="text-center">วันที่รับบริการ/เวลา</th>
                                    <th class="text-center">QUEUE</th>
                                    <th class="text-center">ชื่อ-สกุล | CID | HN</th>
                                    <th class="text-center">การติดต่อ</th>
                                    <th class="text-center">สิทธิ | HMAIN</th>
                                    <th class="text-center">PDX</th>
                                    <th class="text-center">ค่ารักษาทั้งหมด</th>
                                    <th class="text-center">ต้องชำระ</th>
                                    <th class="text-center">ชำระเอง</th>
                                    <th class="text-center">ที่เบิกได้</th>
                                </tr>     
                            </thead> 
                            <tbody> 
                                @foreach($pending as $index => $row) 
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input pending-checkbox" 
                                               data-cid="{{ $row->cid }}" 
                                               data-vstdate="{{ date('Y-m-d', strtotime($row->vstdate)) }}"
                                               data-name="{{ $row->ptname }}">
                                    </td>
                                    <td class="text-center text-muted small">{{ $index + 1 }}</td>
                                    <td class="text-center">
                                        @if($row->claimCode)
                                            <span class="badge bg-info-soft text-info">{{ $row->claimCode }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        <div class="fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ $row->vsttime }}</div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->oqueue }}</span></td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark small">{{ $row->ptname }}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">CID: {{ $row->cid }} | HN: {{ $row->hn }}</div>
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->mobile_phone_number ?: '-' }}</td>
                                    <td class="text-start">
                                        <small class="text-truncate d-block" style="max-width: 150px;">{{ $row->subInsclName }}</small>
                                        <span class="badge bg-secondary-soft text-secondary" style="font-size: 0.65rem;">H: {{ $row->hospmain }}</span>
                                    </td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->pdx ?: '-' }}</span></td>
                                    <td class="text-end fw-bold small">{{ number_format($row->income, 2) }}</td>
                                    <td class="text-end text-dark small">{{ number_format($row->paid_money, 2) }}</td>
                                    <td class="text-end text-danger small">{{ number_format($row->rcpt_money, 2) }}</td>
                                    <td class="text-end text-primary fw-bold small">{{ number_format($row->debtor, 2) }}</td>
                                </tr>
                                @endforeach                 
                            </tbody>
                        </table> 
                    </div>          
                </div> 
            </div>
        </div>
    </div>
</div>     

<!-- Modal -->
<div class="modal fade" id="nhsoModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="nhsoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5>เลือกช่วงวันที่เข้ารับบริการ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="nhsoForm">
        <div class="modal-body text-start">         
          <div id="inputsContainer">
              <div class="mb-3">
                  <label for="nhsoDateStart" class="form-label fw-bold">วันที่เริ่มต้น</label>
                  <div class="input-group">
                      <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                      <input type="hidden" id="nhsoDateStart" name="date_start" value="{{ $start_date }}">
                      <input type="text" id="nhsoDateStart_picker" class="form-control datepicker_th text-center" readonly style="cursor: pointer;">
                  </div>
              </div>
              <div class="mb-3">
                  <label for="nhsoDateEnd" class="form-label fw-bold">วันที่สิ้นสุด</label>
                  <div class="input-group">
                      <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                      <input type="hidden" id="nhsoDateEnd" name="date_end" value="{{ $end_date }}">
                      <input type="text" id="nhsoDateEnd_picker" class="form-control datepicker_th text-center" readonly style="cursor: pointer;">
                  </div>
              </div>
          </div>

          <div id="progressContainer" class="mt-3 d-none text-center">
              <div class="progress mb-2" style="height: 20px;">
                  <div id="nhsoProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%">0%</div>
              </div>
              <small id="progressText" class="text-muted d-block text-center fw-bold">กำลังเริ่มดึงข้อมูล...</small>
          </div>

          <div id="resultMessage" class="mt-3 d-none"></div>
        </div>

        <div class="modal-footer" id="modalFooter">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modalCancelBtn">ยกเลิก</button>
          <button type="submit" class="btn btn-danger" id="nhsoBtn">ดึงข้อมูล</button>
          <button type="button" class="btn btn-danger d-none" data-bs-dismiss="modal" id="modalOkBtn">ตกลง</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

<script>
  document.addEventListener("DOMContentLoaded", function () {
      const form = document.getElementById("nhsoForm");
      const resultMessage = document.getElementById("resultMessage");
      const nhsoModal = document.getElementById('nhsoModal');
      const inputsContainer = document.getElementById("inputsContainer");
      const progressContainer = document.getElementById("progressContainer");
      const progressBar = document.getElementById("nhsoProgressBar");
      const progressText = document.getElementById("progressText");
      const modalCloseHeaderBtn = nhsoModal.querySelector(".btn-close");
      const modalCancelBtn = document.getElementById("modalCancelBtn");
      const nhsoBtn = document.getElementById("nhsoBtn");
      const modalOkBtn = document.getElementById("modalOkBtn");

      let isProcessing = false;

      // Prevent closing modal when processing
      nhsoModal.addEventListener('hide.bs.modal', function (e) {
          if (isProcessing) {
              e.preventDefault();
          }
      });

      // Reset modal state when shown
      nhsoModal.addEventListener('show.bs.modal', function () {
          isProcessing = false;
          inputsContainer.classList.remove("d-none");
          progressContainer.classList.add("d-none");
          progressBar.style.width = "0%";
          progressBar.textContent = "0%";
          progressText.innerHTML = "กำลังเริ่มดึงข้อมูล...";
          resultMessage.classList.add("d-none");
          resultMessage.className = "mt-3 d-none";
          resultMessage.innerHTML = "";
          modalCloseHeaderBtn.classList.remove("d-none");
          modalCancelBtn.classList.remove("d-none");
          nhsoBtn.classList.remove("d-none");
          modalOkBtn.classList.add("d-none");
      });

      // ✔ ปิด Modal → Redirect
      nhsoModal.addEventListener('hidden.bs.modal', function () {
          window.location.href = "{{ url('check/nhso_endpoint') }}";
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

          const dateStartVal = document.getElementById("nhsoDateStart").value;
          const dateEndVal = document.getElementById("nhsoDateEnd").value;

          if (!dateStartVal || !dateEndVal) {
              alert("กรุณาเลือกวันที่เริ่มต้นและสิ้นสุด");
              return;
          }

          const dates = getDatesInRange(dateStartVal, dateEndVal);
          if (dates.length === 0) {
              alert("ไม่พบช่วงวันที่ถูกต้อง");
              return;
          }

          // Start processing
          isProcessing = true;
          inputsContainer.classList.add("d-none");
          progressContainer.classList.remove("d-none");
          modalCloseHeaderBtn.classList.add("d-none");
          modalCancelBtn.classList.add("d-none");
          nhsoBtn.classList.add("d-none");
          resultMessage.classList.add("d-none");

          let successCount = 0;
          let failCount = 0;
          const totalDays = dates.length;

          for (let i = 0; i < totalDays; i++) {
              const currentDate = dates[i];
              
              // format date for display in Thai Buddhist Era
              const parts = currentDate.split('-');
              const thaiDisplayYear = parseInt(parts[0]) + 543;
              const displayDateStr = `${parts[2]}/${parts[1]}/${thaiDisplayYear}`;

              // Update progress bar
              const percent = Math.round((i / totalDays) * 100);
              progressBar.style.width = percent + "%";
              progressBar.textContent = percent + "%";
              progressText.innerHTML = `กำลังดึงข้อมูลวันที่ ${displayDateStr} (${i + 1}/${totalDays} วัน)...`;

              // Send AJAX request for the current single day
              try {
                  const formData = new FormData();
                  formData.append("vstdate", currentDate);

                  const response = await fetch("{{ url('api/nhso_endpoint_pull') }}", {
                      method: "POST",
                      headers: {
                          "X-CSRF-TOKEN": "{{ csrf_token() }}",
                          "Accept": "application/json"
                      },
                      body: formData
                  });

                  if (!response.ok) {
                      throw new Error("HTTP error " + response.status);
                  }

                  const data = await response.json();
                  if (data.status === 'success') {
                      successCount++;
                  } else {
                      failCount++;
                  }
              } catch (err) {
                  console.error(err);
                  failCount++;
              }
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
          resultMessage.innerHTML = `<strong>ดึงข้อมูลสำเร็จ:</strong> ${successCount} วัน | <strong>ล้มเหลว:</strong> ${failCount} วัน`;
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
          $('#nhsoDateStart_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
          $('#nhsoDateEnd_picker').datepicker('setDate', new Date(end_date_val));
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
      const commonConfig = {
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + // Show รายการ
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + // Search + Export
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + // Info
                '<"col-md-6"p>' + // Pagination
              '>',
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
      };

      $('#list_closed').DataTable({
        ...commonConfig,
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel (ปิดสิทธิแล้ว)',
              className: 'btn btn-success btn-sm',
              title: 'รายชื่อผู้มารับบริการ ปิดสิทธิ สปสช. แล้ว วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
            }
        ]
      });

      $('#list_pending').DataTable({
        ...commonConfig,
        columnDefs: [
            { orderable: false, targets: 0 }
        ],
        order: [[1, 'asc']],
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel (รอปิดสิทธิ)',
              className: 'btn btn-danger btn-sm',
              title: 'รายชื่อผู้มารับบริการ รอปิดสิทธิ สปสช. วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
            }
        ]
      });

      // --- Bulk Action Logic ---
      const checkAll = $('#check_all_pending');
      const btnBulk = $('#btn_bulk_push');
      const selectedCount = $('#selected_count');

      function updateSelectedCount() {
          const count = $('.pending-checkbox:checked').length;
          selectedCount.text(count);
          btnBulk.prop('disabled', count === 0);
      }

      checkAll.on('change', function() {
          $('.pending-checkbox').prop('checked', this.checked);
          updateSelectedCount();
      });

      $(document).on('change', '.pending-checkbox', function() {
          updateSelectedCount();
          if(!this.checked) checkAll.prop('checked', false);
          if($('.pending-checkbox:checked').length === $('.pending-checkbox').length) checkAll.prop('checked', true);
      });

      btnBulk.on('click', async function() {
          const selected = $('.pending-checkbox:checked');
          if (selected.length === 0) return;

          const result = await Swal.fire({
              title: 'ยืนยันการส่งปิดสิทธิ?',
              text: `ระบบจะดำเนินการส่งข้อมูลปิดสิทธิทีละรายการ จำนวน ${selected.length} รายการ`,
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'ยืนยัน',
              cancelButtonText: 'ยกเลิก',
              confirmButtonColor: '#3b82f6'
          });

          if (!result.isConfirmed) return;

          // Process Sequential
          Swal.fire({
              title: 'กำลังดำเนินการ...',
              html: 'รายการที่ <b id="current_idx">1</b> จาก <b>' + selected.length + '</b><br><small id="current_name"></small>',
              allowOutsideClick: false,
              didOpen: () => { Swal.showLoading(); }
          });

          let successCount = 0;
          let failCount = 0;

          for (let i = 0; i < selected.length; i++) {
              const item = $(selected[i]);
              const cid = item.data('cid');
              const vstdate = item.data('vstdate');
              const name = item.data('name');

              $('#current_idx').text(i + 1);
              $('#current_name').text(name);

              try {
                  const res = await fetch("{{ url('api/nhso_endpoint_push_indiv') }}", {
                      method: "POST",
                      headers: {
                          "X-CSRF-TOKEN": "{{ csrf_token() }}",
                          "Content-Type": "application/json",
                          "Accept": "application/json"
                      },
                      body: JSON.stringify({ cid, vstdate })
                  });
                  const data = await res.json();
                  if (data.status === 'success') successCount++; else failCount++;
              } catch (e) {
                  failCount++;
              }
              // Small delay to avoid hammering
              await new Promise(r => setTimeout(r, 200));
          }

          Swal.fire({
              title: 'ดำเนินการเสร็จสิ้น',
              text: `สำเร็จ ${successCount} รายการ, ล้มเหลว ${failCount} รายการ`,
              icon: successCount > 0 ? 'success' : 'error',
              confirmButtonText: 'ตกลง'
          }).then(() => {
              window.location.reload();
          });
      });
    });
  </script>
@endpush