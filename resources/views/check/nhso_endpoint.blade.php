@extends('layouts.app')

@section('content')

<div class="container-fluid">  
  <form method="POST" enctype="multipart/form-data">
      @csrf            
      <div class="row" >
              <label class="col-md-3 col-form-label text-md-end my-1">{{ __('วันที่') }}</label>
          <div class="col-md-2">
              <input type="date" name="start_date" class="form-control my-1" placeholder="Date" value="{{ $start_date }}" > 
          </div>
              <label class="col-md-1 col-form-label text-md-end my-1">{{ __('ถึง') }}</label>
          <div class="col-md-2">
              <input type="date" name="end_date" class="form-control my-1" placeholder="Date" value="{{ $end_date }}" > 
          </div>                     
          <div class="col-md-2" >                            
              <button type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button>
              <!-- ปุ่มเรียก Modal -->
              <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#nhsoModal">
                ดึงปิดสิทธิ สปสช.
              </button>
          </div>
          
      </div>
  </form> 
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการที่ ดึงข้อมูลปิดสิทธิจาก สปสช. วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>

  <div class="card-body">
    <div class="row">        
      <div class="col-md-12"> 
        <div style="overflow-x:auto;">            
          <table id="list" class="table table-striped table-bordered" width = "100%">
            <thead>
              <tr class="table-primary">
                <th class="text-center">ลำดับ</th>               
                <th class="text-center">CID</th>
                <th class="text-center">ชื่อ-สกุล</th> 
                <th class="text-center">สิทธิ</th> 
                <th class="text-center">วัน-เวลาที่รับบริการ</th>
                <th class="text-center">claimCode</th>          
                <th class="text-center">claimType</th>   
              </tr>     
            </thead> 
            <tbody> 
              <?php $count = 1 ; ?>
              @foreach($sql as $row) 
              <tr>
                <td align="center">{{ $count }}</td>                 
                <td align="center">{{ $row->cid }}</td>
                <td align="left">{{ $row->firstName }} {{ $row->lastName }}</td>
                <td align="left">{{ $row->subInscl }} {{ $row->subInsclName }}</td>  
                <td align="left">{{ DatetimeThai($row->serviceDateTime) }}</td>
                <td align="center">{{ $row->claimCode }}</td>
                <td align="center">{{ $row->claimType }}</td>
              </tr>
              <?php $count++; ?>
              @endforeach                 
            </tbody>
          </table> 
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