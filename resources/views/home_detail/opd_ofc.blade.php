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
          <div class="col-md-1" >                            
              <button type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button>
          </div>
      </div>
  </form> 
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการสิทธิข้าราชการ OFC วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
  <div class="card-body">
    <div class="row">        
      <div class="col-md-12"> 
        <div style="overflow-x:auto;">            
          <table id="list" class="table table-striped table-bordered" width = "100%">
            <thead>
              <tr class="table-primary">
                  <th class="text-center">ลำดับ</th>
                  <th class="text-center" width="6%">Action</th>
                  <th class="text-center">Authen</th>  
                  <th class="text-center">ปิดสิทธิ</th>
                  <th class="text-center">PPFS</th>
                  <th class="text-center">EDC</th>
                  <th class="text-center">ชื่อ-สกุล</th>    
                  <th class="text-center">CID</th> 
                  <th class="text-center">เบอร์โทร</th>          
                  <th class="text-center">วันที่รับบริการ</th> 
                  <th class="text-center">เวลา</th>                                      
                  <th class="text-center">PDX</th>
                  <th class="text-center">ค่าบริการที่เบิกได้</th>
                  <th class="text-center">สิทธิการรักษา</th>      
              </tr>
            </thead> 
            <tbody> 
              <?php $count = 1 ; ?>
              @foreach($sql as $row) 
              <tr>
                <td align="center">{{ $count }}</td>
                <td align="center" width="6%">
                   @if($row->ppfs == 'Y')                  
                    <button onclick="pullNhsoData('{{ $row->vstdate }}', '{{ $row->cid }}')" class="btn btn-outline-info btn-sm w-100">
                        ดึงปิดสิทธิ
                    </button>
                  @endif
                </td> 
                <td align="center" @if($row->auth_code == 'Y') style="color:green"
                  @elseif($row->auth_code == 'N') style="color:red" @endif>
                  <strong>{{ $row->auth_code }}</strong></td>               
                <td align="center" @if($row->endpoint == 'Y') style="color:green"
                  @elseif($row->endpoint == 'N') style="color:red" @endif>
                  <strong>{{ $row->endpoint }}</strong></td> 
                <td align="center" @if($row->ppfs == 'Y') style="color:green"
                  @elseif($row->ppfs == 'N') style="color:red" @endif>
                  <strong>{{ $row->ppfs }}</strong></td> 
                <td align="center">{{$row->edc}}</td>                
                <td align="left">{{$row->ptname}}</td> 
                <td align="center">{{$row->cid}}</td> 
                <td align="center">{{$row->mobile_phone_number}}</td>
                <td align="left">{{ DateThai($row->vstdate) }}</td>             
                <td align="rigth">{{$row->vsttime}}</td>                
                <td align="center">{{$row->pdx}}</td>
                <td align="right">{{ number_format($row->debtor,2) }}</td>
                <td align="left">{{$row->pttype}}</td>            
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
<script>
  function pullNhsoData(vstdate, cid) {
      Swal.fire({
          title: 'กำลังดึงข้อมูล...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading()
          }
      });

      fetch("{{ url('nhso_endpoint_pull') }}/" + vstdate + "/" + cid)
          .then(async response => {
              const data = await response.json();
              if (!response.ok) {
                  throw new Error(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
              }
              return data;
          })
          .then(data => {
              Swal.fire({
                  icon: 'success',
                  title: 'ดึงข้อมูลสำเร็จ',
                  text: data.message || 'ข้อมูลถูกบันทึกเรียบร้อยแล้ว',
                  timer: 2000,
                  showConfirmButton: false
              }).then(() => {
                  location.reload();
              });
          })
          .catch(error => {
              Swal.fire({
                  icon: 'error',
                  title: 'เกิดข้อผิดพลาด',
                  text: error.message || 'ไม่สามารถเชื่อมต่อกับระบบได้',
              });
          });
  }
</script>

@endsection

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
              title: 'รายชื่อผู้มารับบริการสิทธิข้าราชการ OFC วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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