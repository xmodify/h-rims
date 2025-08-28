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
                <strong>Statement เบิกจ่ายตรงกรมบัญชีกลาง OFC | กทม.BKK | ขสมก.BMT [OP-IP] รายละเอียด</strong>
            </div> 
            <div class="card-body">
                <div style="overflow-x:auto;"> 
                    <strong class="text-primary">ผู้ป่วยนอก OP</strong>      
                    <table id="stm_ofc_list" class="table table-bordered table-striped my-3">
                        <thead>
                            <tr class="table-primary">
                                <th class="text-center">Dep</th>
                                <th class="text-center">Filename</th> 
                                <th class="text-center">REP</th> 
                                <th class="text-center">HN</th> 
                                <th class="text-center">AN</th>
                                <th class="text-center">ชื่อ-สกุล</th>
                                <th class="text-center">วันเข้ารักษา</th>
                                <th class="text-center">วันจำหน่าย</th>
                                <th class="text-center">AdjRW</th>                      
                                <th class="text-center">เรียกเก็บ</th>
                                <th class="text-center">พรบ.</th>                    
                                <th class="text-center">ค่าห้อง</th>
                                <th class="text-center">ค่าอวัยวะ</th>                  
                                <th class="text-center">ค่ายา</th> 
                                <th class="text-center">ค่ารักษา</th>
                                <th class="text-center">ค่ารถ</th>                  
                                <th class="text-center">ค่าบริการอื่นๆ</th>
                                <th class="text-center">พึงรับทั้งหมด</th>
                            </tr>     
                            </thead> 
                            <?php $count = 1 ; ?>  
                            @foreach($stm_ofc_list as $row)          
                            <tr>
                                <td align="center">{{ $row->dep }}</td> 
                                <td align="right">{{ $row->stm_filename }}</td>
                                <td align="right">{{ $row->repno }}</td>
                                <td align="right">{{ $row->hn }}</td>
                                <td align="right">{{ $row->an }}</td>
                                <td align="left">{{ $row->pt_name }}</td>
                                <td align="right">{{ $row->datetimeadm }}</td>
                                <td align="right">{{ $row->datetimedch }}</td>
                                <td align="right">{{ number_format($row->adjrw,4) }}</td>  
                                <td align="right">{{ number_format($row->charge,2) }}</td> 
                                <td align="right">{{ number_format($row->act,2) }}</td>  
                                <td align="right">{{ number_format($row->receive_room,2) }}</td>  
                                <td align="right">{{ number_format($row->receive_instument,2) }}</td>  
                                <td align="right">{{ number_format($row->receive_drug,2) }}</td> 
                                <td align="right">{{ number_format($row->receive_treatment,2) }}</td>   
                                <td align="right">{{ number_format($row->receive_car,2) }}</td>  
                                <td align="right">{{ number_format($row->receive_other,2) }}</td>   
                                <td align="right">{{ number_format($row->receive_total,2) }}</td>  
                            </tr>                
                            <?php $count++; ?>  
                            @endforeach   
                    </table>
                </div> 
            </div>  
            <hr>
            <div class="card-body">
                <div style="overflow-x:auto;"> 
                    <strong class="text-primary">ผู้ป่วยใน IP</strong>       
                    <table id="stm_ofc_list_ip" class="table table-bordered table-striped my-3">
                        <thead>
                            <tr class="table-primary">
                                <th class="text-center">Dep</th>
                                <th class="text-center">Filename</th> 
                                <th class="text-center">REP</th> 
                                <th class="text-center">HN</th> 
                                <th class="text-center">AN</th>
                                <th class="text-center">ชื่อ-สกุล</th>
                                <th class="text-center">วันเข้ารักษา</th>
                                <th class="text-center">วันจำหน่าย</th>
                                <th class="text-center">AdjRW</th>                      
                                <th class="text-center">เรียกเก็บ</th>
                                <th class="text-center">พรบ.</th>                    
                                <th class="text-center">ค่าห้อง</th>
                                <th class="text-center">ค่าอวัยวะ</th>                  
                                <th class="text-center">ค่ายา</th> 
                                <th class="text-center">ค่ารักษา</th>
                                <th class="text-center">ค่ารถ</th>                  
                                <th class="text-center">ค่าบริการอื่นๆ</th>
                                <th class="text-center">พึงรับทั้งหมด</th>
                            </tr>     
                            </thead> 
                            <?php $count = 1 ; ?>  
                            @foreach($stm_ofc_list_ip as $row)          
                            <tr>
                                <td align="center">{{ $row->dep }}</td> 
                                <td align="right">{{ $row->stm_filename }}</td>
                                <td align="right">{{ $row->repno }}</td>
                                <td align="right">{{ $row->hn }}</td>
                                <td align="right">{{ $row->an }}</td>
                                <td align="left">{{ $row->pt_name }}</td>
                                <td align="right">{{ $row->datetimeadm }}</td>
                                <td align="right">{{ $row->datetimedch }}</td>
                                <td align="right">{{ number_format($row->adjrw,4) }}</td>  
                                <td align="right">{{ number_format($row->charge,2) }}</td> 
                                <td align="right">{{ number_format($row->act,2) }}</td>  
                                <td align="right">{{ number_format($row->receive_room,2) }}</td>  
                                <td align="right">{{ number_format($row->receive_instument,2) }}</td>  
                                <td align="right">{{ number_format($row->receive_drug,2) }}</td> 
                                <td align="right">{{ number_format($row->receive_treatment,2) }}</td>   
                                <td align="right">{{ number_format($row->receive_car,2) }}</td>  
                                <td align="right">{{ number_format($row->receive_other,2) }}</td>   
                                <td align="right">{{ number_format($row->receive_total,2) }}</td>  
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
      $('#stm_ofc_list').DataTable({
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
              title: 'Statement เบิกจ่ายตรงกรมบัญชีกลาง OFC | กทม.BKK | ขสมก.BMT [OP-IP] รายละเอียด OPD'
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
      $('#stm_ofc_list_ip').DataTable({
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
              title: 'Statement เบิกจ่ายตรงกรมบัญชีกลาง OFC | กทม.BKK | ขสมก.BMT [OP-IP] รายละเอียด IPD'
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
