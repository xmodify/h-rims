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
<div class="modal fade" id="FdhModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">ดึงข้อมูลจาก FDH</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <!-- 🔥 FORM เริ่มตรงนี้ -->
      <form id="fdhForm">
        <div class="modal-body">
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

            <div id="resultMessage" class="mt-2 d-none"></div>
            <div id="loadingSpinner" class="text-center d-none">
                <div class="spinner-border text-success"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-success" id="FdhBtn">ดึงข้อมูล</button>
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

      // ✔ กดปุ่ม "ส่งข้อมูล" → submit form
      form.addEventListener("submit", function (e) {
          e.preventDefault();

          spinner.classList.remove("d-none");
          resultMessage.classList.add("d-none");

          const formData = new FormData(form);

          fetch("{{ route('api.fdh.check_claim') }}", {
              method: "POST",
              headers: {
                  "X-CSRF-TOKEN": "{{ csrf_token() }}"
              },
              body: formData
          })
          .then(response => {
              spinner.classList.add("d-none");
              if (!response.ok) throw new Error("โหลดล้มเหลว");
              return response.json();
          })
          .then(data => {
              const start_date_val = "{{ $start_date }}";
              const end_date_val = "{{ $end_date }}";
              document.getElementById("dateStart").value = start_date_val;
              document.getElementById("dateEnd").value = end_date_val;
              
              resultMessage.classList.remove("d-none");
              resultMessage.classList.add("text-success");
              resultMessage.innerHTML = "✅ " + (data.message || "ดึงข้อมูลสำเร็จ");
          })
          .catch(err => {
              resultMessage.classList.remove("d-none");
              resultMessage.classList.add("text-danger");
              resultMessage.innerHTML = "❌ ดึงข้อมูลล้มเหลว";
          });
      });

      // ✔ ปิด Modal → Redirect
      const modalEl = document.getElementById('FdhModal');
      modalEl.addEventListener('hidden.bs.modal', function () {
          window.location.href = "{{ url('check/fdh_claim_status') }}";
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