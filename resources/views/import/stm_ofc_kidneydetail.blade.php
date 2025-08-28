@extends('layouts.app')

@section('content')
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
<div class="container-fluid">
    <div class="row justify-content-center">  
        <div class="col-md-12"> 
            <div class="alert alert-success text-primary" role="alert">
                <strong>Statement เบิกจ่ายตรงกรมบัญชีกลาง OFC [ฟอกไต] รายละเอียด</strong>
            </div>
            <div class="card-body">
                <div style="overflow-x:auto;">                           
                    <table id="stm_ofc_kidney_list" class="table table-bordered table-striped my-3">
                        <thead>
                            <tr class="table-primary">
                                <th class="text-center">Hcode</th>
                                <th class="text-center">Hname</th> 
                                <th class="text-center">FileName</th> 
                                <th class="text-center">Station</th> 
                                <th class="text-center">Hreg</th>                      
                                <th class="text-center">HN</th>
                                <th class="text-center">InvNo</th>                    
                                <th class="text-center">วันที่รับบริการ</th>
                                <th class="text-center">เอกสารตอบรับ</th>                  
                                <th class="text-center">ค่ารักษาพยาบาลที่เบิก</th> 
                                <th class="text-center">HDFlag</th>
                            </tr>     
                            </thead> 
                            <?php $count = 1 ; ?>  
                            @foreach($stm_ofc_kidney_list as $row)          
                            <tr>
                                <td align="center">{{ $row->hcode }}</td> 
                                <td align="right">{{ $row->hname }}</td>
                                <td align="right">{{ $row->stmdoc }}</td>
                                <td align="right">{{ $row->station }}</td>
                                <td align="right">{{ $row->hreg }}</td>
                                <td align="left">{{ $row->hn }}</td>
                                <td align="right">{{ $row->invno }}</td>
                                <td align="right">{{ $row->dttran }}</td>
                                <td align="right">{{ $row->rid }}</td>
                                <td align="right">{{ number_format($row->amount,2) }}</td> 
                                <td align="right">{{ $row->hdflag }}</td>
                            </tr>                
                            <?php $count++; ?>  
                            @endforeach   
                    </table>
                </div> 
            </div> 
        </div> 
    </div> 
</div> 
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
      $('#stm_ofc_kidney_list').DataTable({
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
              title: 'Statement เบิกจ่ายตรงกรมบัญชีกลาง OFC [ฟอกไต] รายละเอียด'
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
