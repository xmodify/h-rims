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
              <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#FdhModal">
                  ดึงข้อมูลจาก FDH
              </button>
          </div>
          
      </div>
  </form> 
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการที่ส่ง FDH วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>

  <div class="card-body">
    <div class="row">        
      <div class="col-md-12"> 
        <div style="overflow-x:auto;">            
          <table id="list" class="table table-striped table-bordered" width = "100%">
            <thead>
              <tr class="table-primary">
                <th class="text-center">ลำดับ</th>               
                <th class="text-center">HN</th>
                <th class="text-center">SEQ</th>
                <th class="text-center">AN</th> 
                <th class="text-center">STATUS</th> 
                <th class="text-center">PROCESS</th>
                <th class="text-center">MASSAGE</th>          
                <th class="text-center">STM PERIOD</th>   
              </tr>     
            </thead> 
            <tbody> 
              <?php $count = 1 ; ?>
              @foreach($sql as $row) 
              <tr>
                <td align="center">{{ $count }}</td>                 
                <td align="center">{{ $row->hn }}</td>
                <td align="center">{{ $row->seq }}</td>
                <td align="center">{{ $row->an }}</td>
                <td align="left">{{ $row->status }}</td>
                <td align="center">{{ $row->process_status }}</td>
                <td align="left">{{ $row->status_message_th }}</td>
                <td align="left">{{ $row->stm_period }}</td>
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
                <input type="date" name="date_start" id="dateStart" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="dateEnd" class="form-label">วันที่สิ้นสุด</label>
                <input type="date" name="date_end" id="dateEnd" class="form-control" required>
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

          fetch("{{ url('/api/fdh/check-claim') }}", {
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