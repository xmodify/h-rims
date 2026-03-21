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
                                        <div class="small text-muted lh-1" style="font-size: 0.75rem;">{{ $row->subInsclName ?: $row->subInscl }}</div>
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
                        <table id="list_pending" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">ลำดับ</th>               
                                    <th class="text-center">ชื่อ-นามสกุล</th>
                                    <th class="text-center">CID</th>
                                    <th class="text-center">สิทธิ (HOSxP)</th> 
                                    <th class="text-center">วัน-เวลาที่รับบริการ</th>
                                    <th class="text-center">Authen (HOSxP)</th>          
                                </tr>     
                            </thead> 
                            <tbody> 
                                @foreach($pending as $index => $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $index + 1 }}</td>                 
                                    <td class="text-start fw-bold text-dark small">{{ $row->firstName }} {{ $row->lastName }}</td>
                                    <td class="text-center small text-muted">{{ $row->cid }}</td>
                                    <td class="text-start">
                                        <div class="small text-muted lh-1" style="font-size: 0.75rem;">{{ $row->subInsclName }}</div>
                                    </td>  
                                    <td class="text-center small">{{ DatetimeThai($row->serviceDateTime) }}</td>
                                    <td class="text-center">
                                        @if($row->claimCode)
                                            <span class="badge bg-info-soft text-info">{{ $row->claimCode }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
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
<div class="modal fade" id="nhsoModal" tabindex="-1" aria-labelledby="nhsoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-header">
        <h5>เลือกวันที่เข้ารับบริการ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="nhsoForm">
        <div class="modal-body">         
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
            <input type="hidden" id="vstdate" name="vstdate" value="{{ date('Y-m-d') }}">
            <input type="text" id="vstdate_picker" class="form-control datepicker_th text-center" readonly style="cursor: pointer;">
          </div>

          <div id="loadingSpinner" class="mt-4 d-none">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">กำลังดึงข้อมูลจาก สปสช....</p>
          </div>

          <div id="resultMessage" class="mt-3 d-none"></div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">ดึงข้อมูล</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

<script>
  document.addEventListener("DOMContentLoaded", function () {
      const form = document.getElementById("nhsoForm");
      const spinner = document.getElementById("loadingSpinner");
      const resultMessage = document.getElementById("resultMessage");
      const nhsoModal = document.getElementById('nhsoModal');

      form.addEventListener("submit", function (e) {
          e.preventDefault();
          spinner.classList.remove("d-none");
          resultMessage.classList.add("d-none");
          resultMessage.innerHTML = "";

          const formData = new FormData(form);

          fetch("{{ url('api/nhso_endpoint_pull') }}", {
              method: "POST",
              headers: {
                  "X-CSRF-TOKEN": "{{ csrf_token() }}",
                  "Accept": "application/json"
              },
              body: formData
          })
          .then(response => {
              spinner.classList.add("d-none");
              if (!response.ok) throw new Error("โหลดล้มเหลว");
              return response.json();
          })
          .then(data => {
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

      nhsoModal.addEventListener('hide.bs.modal', function () {
          // ✅ Redirect ไปหน้า /home เมื่อปิด Modal
          window.location.href = "{{ url('check/nhso_endpoint') }}";
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
      var vstdate_val = "{{ date('Y-m-d') }}";
      
      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
      }
      if(vstdate_val) {
          $('#vstdate_picker').datepicker('setDate', new Date(vstdate_val));
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
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel (รอปิดสิทธิ)',
              className: 'btn btn-danger btn-sm',
              title: 'รายชื่อผู้มารับบริการ รอปิดสิทธิ สปสช. วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
            }
        ]
      });
    });
  </script>
@endpush