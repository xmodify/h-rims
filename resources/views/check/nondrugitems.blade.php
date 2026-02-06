@extends('layouts.app')

@section('content')

<div class="container-fluid">  
  <div class="alert alert-success text-primary" role="alert"><strong>ค่ารักษาพยาบาล ที่เปิดใช้งาน</strong></div>
  
  <div class="card-body"> 
    <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">          
      <table id="nondrugitems" class="table table-striped table-bordered" width = "100%">
        <thead>
          <tr class="table-primary">
              <th class="text-center">หมวดค่ารักษาพยาบาล</th>  
              <th class="text-center">รหัส</th>
              <th class="text-center">ชื่อ</th>  
              <th class="text-center">ราคา</th>     
              <th class="text-center">Billcode</th>
              <th class="text-center">ADP Code</th> 
              <th class="text-center">ADP Name</th>
              <th class="text-center">ADP Type</th>
          </tr>
        </thead> 
        <tbody> 
          @foreach($nondrugitems as $row) 
          <tr>
              <td align="left">{{$row->income}}</td> 
              <td align="left">{{$row->icode}}</td>                                
              <td align="left">{{$row->name}}</td>            
              <td align="right">{{number_format($row->price,2)}}</td>
              <td align="left">{{$row->billcode}}</td>
              <td align="left">{{$row->nhso_adp_code}}</td> 
              <td align="left">{{$row->nhso_adp_code_name}}</td>
              <td align="left">{{$row->nhso_adp_type_name}}</td>
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
  <div class="alert alert-secondary" role="alert"><strong>ค่ารักษาพยาบาล ที่ปิดใช้งาน</strong></div>
  
  <div class="card-body">
    <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">            
      <table id="nondrugitems_non" class="table table-striped table-bordered" width = "100%">
        <thead>
          <tr class="table-primary">
              <th class="text-center">หมวดค่ารักษาพยาบาล</th>  
              <th class="text-center">รหัส</th>
              <th class="text-center">ชื่อ</th>  
              <th class="text-center">ราคา</th>     
              <th class="text-center">Billcode</th>
              <th class="text-center">ADP Code</th> 
              <th class="text-center">ADP Name</th>
              <th class="text-center">ADP Type</th>
          </tr>
        </thead> 
        <tbody> 
          @foreach($nondrugitems_non as $row) 
          <tr>
              <td align="left">{{$row->income}}</td> 
              <td align="left">{{$row->icode}}</td>                                
              <td align="left">{{$row->name}}</td>            
              <td align="right">{{number_format($row->price,2)}}</td>
              <td align="left">{{$row->billcode}}</td>
              <td align="left">{{$row->nhso_adp_code}}</td> 
              <td align="left">{{$row->nhso_adp_code_name}}</td>
              <td align="left">{{$row->nhso_adp_type_name}}</td>
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
      $('#nondrugitems').DataTable({
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
              title: 'ค่ารักษาพยาบาล ที่เปิดใช้งาน'
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
      $('#nondrugitems_non').DataTable({
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
              title: 'ค่ารักษาพยาบาล ที่ปิดใช้งาน'
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

