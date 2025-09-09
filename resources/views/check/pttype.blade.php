@extends('layouts.app')

@section('content')

<div class="container-fluid">  
  <div class="alert alert-success text-primary" role="alert"><strong>สิทธิการรักษา ที่เปิดใช้งาน</strong></div>
  
  <div class="card-body">
    <div style="overflow-x:auto;">            
      <table id="pttype" class="table table-striped table-bordered" width = "100%">
        <thead>
          <tr class="table-primary">
              <th class="text-center" colspan="8">ตาราง pttype</th>
              <th class="text-center" colspan="2" style="background-color: #b3e5fc">ตาราง provis_instype</th>                
          </tr>
          <tr class="table-primary">
              <th class="text-center">Kiosk</th>  
              <th class="text-center">รหัส</th>
              <th class="text-center">ชื่อสิทธิ</th>  
              <th class="text-center">ประเภทการชำระ</th>     
              <th class="text-center">Ex_Eclaim</th>
              <th class="text-center">Hipdata_code</th> 
              <th class="text-center">รหัสส่งออก</th>
              <th class="text-center">กลุ่มค่าบริการ</th>
              <th class="text-center" style="background-color: #b3e5fc">ชื่อสิทธิ</th>
              <th class="text-center" style="background-color: #b3e5fc">รหัสส่งออก</th>
          </tr>
        </thead> 
        <tbody> 
          @foreach($pttype as $row) 
          <tr>
              <td align="center">{{$row->nhso_subinscl}}</td> 
              <td align="right">{{$row->pttype}}</td>                                
              <td align="left">{{$row->name}}</td>            
              <td align="left">{{ $row->paidst }}</td>
              <td align="center">{{$row->export_eclaim}}</td>
              <td align="left">{{$row->hipdata_code}}</td> 
              <td align="left">{{$row->pttype_std_code}}</td>
              <td align="left">{{$row->pttype_price_group_name}}</td>
              <td align="left">{{$row->pi_name}}</td>  
              <td align="left">{{$row->pi_pttype_std_code}}</td>
          </tr>
          @endforeach                 
        </tbody>
      </table>         
    </div>
  </div> 
</div> 
<br>     
<hr>
<br>
<div class="container-fluid">  
  <div class="alert alert-secondary" role="alert"><strong>สิทธิการรักษา ที่ปิดใช้งาน</strong></div>
  
  <div class="card-body">
    <div style="overflow-x:auto;">            
      <table id="pttype_close" class="table table-striped table-bordered" width = "100%">
        <thead>
          <tr class="table-secondary">
              <th class="text-center" colspan="8">ตาราง pttype</th>
              <th class="text-center" colspan="2" >ตาราง provis_instype</th>                
          </tr>
          <tr class="table-secondary">
              <th class="text-center">Kiosk</th>  
              <th class="text-center">รหัส</th>
              <th class="text-center">ชื่อสิทธิ</th>  
              <th class="text-center">ประเภทการชำระ</th>     
              <th class="text-center">Ex_Eclaim</th>
              <th class="text-center">Hipdata_code</th> 
              <th class="text-center">รหัสส่งออก</th>
              <th class="text-center">กลุ่มค่าบริการ</th>
              <th class="text-center">ชื่อสิทธิ</th>
              <th class="text-center">รหัสส่งออก</th>
          </tr>
        </thead> 
        <tbody> 
          @foreach($pttype_close as $row) 
          <tr>
              <td align="center">{{$row->nhso_subinscl}}</td> 
              <td align="right">{{$row->pttype}}</td>                                
              <td align="left">{{$row->name}}</td>            
              <td align="left">{{ $row->paidst }}</td>
              <td align="center">{{$row->export_eclaim}}</td>
              <td align="left">{{$row->hipdata_code}}</td> 
              <td align="left">{{$row->pttype_std_code}}</td>
              <td align="left">{{$row->pttype_price_group_name}}</td>
              <td align="left">{{$row->pi_name}}</td>  
              <td align="left">{{$row->pi_pttype_std_code}}</td>
          </tr>
          @endforeach                 
        </tbody>
      </table>         
    </div>
  </div> 
</div>  

@endsection

@push('scripts')  
  <script>
    $(document).ready(function () {
      $('#pttype').DataTable({
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
              title: 'สิทธการักษา ที่เปิดใช้งาน'
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
  <script>
    $(document).ready(function () {
      $('#pttype_close').DataTable({
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
              title: 'สิทธการักษา ที่ปิดใช้งาน'
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

