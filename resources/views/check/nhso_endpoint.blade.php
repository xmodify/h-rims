@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
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
                    <input type="date" name="start_date" class="form-control border-start-0" value="{{ $start_date }}" style="width: 140px;">
                    <span class="input-group-text bg-white">ถึง</span>
                    <input type="date" name="end_date" class="form-control" value="{{ $end_date }}" style="width: 140px;">
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

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0">
        <div class="card-body p-4">
            <div class="table-responsive">            
                <table id="list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">ลำดับ</th>               
                            <th class="text-center">ชื่อ-นามสกุล</th>
                            <th class="text-center">CID</th>
                            <th class="text-center">สปสช (SubInscl)</th> 
                            <th class="text-center">วัน-เวลาที่รับบริการ</th>
                            <th class="text-center">Claim Type</th>
                            <th class="text-center">Claim Code</th>          
                        </tr>     
                    </thead> 
                    <tbody> 
                        @php $count = 1; @endphp
                        @foreach($sql as $row) 
                        <tr>
                            <td class="text-center text-muted small">{{ $count }}</td>                 
                            <td class="text-start fw-bold text-dark small">{{ $row->firstName }} {{ $row->lastName }}</td>
                            <td class="text-center small text-muted">{{ $row->cid }}</td>
                            <td class="text-start">
                                <span class="badge bg-light text-dark border px-2 py-1 mb-1" style="font-size: 0.7rem;">{{ $row->subInscl }}</span>
                                <div class="small text-muted lh-1" style="font-size: 0.75rem;">{{ $row->subInsclName }}</div>
                            </td>  
                            <td class="text-center small">{{ DatetimeThai($row->serviceDateTime) }}</td>
                            <td class="text-center">
                                <div class="small text-primary fw-bold">{{ $row->claimType }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-soft text-success">{{ $row->claimCode }}</span>
                            </td>
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
<div class="modal fade" id="nhsoModal" tabindex="-1" aria-labelledby="nhsoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-header">
        <h5>เลือกวันที่เข้ารับบริการ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="nhsoForm">
        <div class="modal-body">         
          <input type="date" id="vstdate" name="vstdate" class="form-control"  value="{{ date('Y-m-d') }}" required>

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

          fetch("{{ url('nhso_endpoint_pull') }}", {
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