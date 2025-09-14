@extends('layouts.app')

@section('content')

<div class="container-fluid">  
  <div class="alert alert-success text-primary" role="alert"><strong>สิทธิการรักษา สปสช</strong></div>
  
  <div class="card-body">
    <div style="overflow-x:auto;">            
      <table id="subinscl" class="table table-striped table-bordered" width = "100%">
        <thead>
          <tr class="table-primary">
              <th class="text-center" colspan="3">สปสช</th>
              <th class="text-center" colspan="3" style="background-color: #b3e5fc">HOSxP</th>                
          </tr>
          <tr class="table-primary">
              <th class="text-center">CODE</th>  
              <th class="text-center">NAME</th>
              <th class="text-center">MAININSCL</th>  
              <th class="text-center" style="background-color: #b3e5fc">PTTYPE</th>     
              <th class="text-center" style="background-color: #b3e5fc">PTTYPE_NAME</th>
              <th class="text-center" style="background-color: #b3e5fc">Hipdata_code</th> 
          </tr>
        </thead> 
        <tbody> 
          @foreach($subinscl as $row) 
          <tr>
              <td align="center">{{$row->code}}</td> 
              <td align="left">{{$row->name}}</td>                                
              <td align="left">{{$row->maininscl}}</td>            
              <td align="center">{{ $row->pttype }}</td>
              <td align="left">{{$row->pttype_name}}</td>
              <td align="left">{{$row->hipdata_code}}</td>             
          </tr>
          @endforeach                 
        </tbody>
      </table>         
    </div>
  </div> 
</div> 
<br>   

@endsection

@push('scripts')  
  <script>
    $(document).ready(function () {
      $('#subinscl').DataTable({
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
              title: 'สิทธิการรักษา สปสช'
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


