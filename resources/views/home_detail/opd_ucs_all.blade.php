@extends('layouts.app')

@section('content')

<div class="container-fluid"> 
  <form  method="POST" enctype="multipart/form-data">
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
              <button type="submit" class="btn btn-primary my-1">{{ __('ค้นหา') }}</button>
          </div>
      </div>
  </form> 
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการสิทธิประกันสุขภาพ UCS วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
  <div class="card-body">
    <div class="row">        
      <div class="col-md-12"> 
        <div style="overflow-x:auto;">            
          <table id="t_search" class="table table-striped table-bordered" width = "100%">
            <thead>
              <tr class="table-primary">
                  <th class="text-center">ลำดับ</th>
                  <th class="text-center">Authen</th>  
                  <th class="text-center">ปิดสิทธิ</th>
                  <th class="text-center">ชื่อ-สกุล</th>    
                  <th class="text-center">CID</th>   
                  <th class="text-center">เบอร์โทร</th>        
                  <th class="text-center">วันที่รับบริการ</th> 
                  <th class="text-center">เวลา</th>                                      
                  <th class="text-center">PDX</th>
                  <th class="text-center">ค่าบริการที่เบิกได้</th>
                  <th class="text-center">สิทธิการรักษา</th> 
                  <th class="text-center">Hmain</th>                   
                  <th class="text-center text-success" width="4%">ประสงค์เบิก</th>
                  <th class="text-center text-success" width="4%">พร้อมส่ง</th>
                  <th class="text-center text-success" width="6%">Upload FDH</th>  
              </tr>
            </thead> 
            <tbody> 
              <?php $count = 1 ; ?>
              @foreach($sql as $row) 
              <tr>
                <td align="center">{{ $count }}</td>
                <td align="center" @if($row->auth_code == 'Y') style="color:green"
                  @elseif($row->auth_code == 'N') style="color:red" @endif>
                  <strong>{{ $row->auth_code }}</strong></td>               
                <td align="center" @if($row->endpoint == 'Y') style="color:green"
                  @elseif($row->endpoint == 'N') style="color:red" @endif>
                  <strong>{{ $row->endpoint }}</strong></td>                
                <td align="left">{{$row->ptname}}</td> 
                <td align="center">{{$row->cid}}</td> 
                <td align="center">{{$row->informtel}}</td> 
                <td align="left">{{ DateThai($row->vstdate) }}</td>             
                <td align="rigth">{{$row->vsttime}}</td>                
                <td align="rigth">{{$row->pdx}}</td>
                <td align="right">{{ number_format($row->debtor,2) }}</td>
                <td align="left">{{$row->pttype}}</td>
                <td align="center">{{$row->hospmain}}</td> 
                <td align="center" @if($row->request_funds == 'Y') style="color:green"
                  @elseif($row->request_funds == 'N') style="color:red" @endif>
                  <strong>{{ $row->request_funds }}</strong></td>  
                <td align="center" @if($row->confirm_and_locked == 'Y') style="color:green"
                  @elseif($row->confirm_and_locked == 'N') style="color:red" @endif>
                  <strong>{{ $row->confirm_and_locked }}</strong></td>  
                <td align="center">{{ DateTimeThai($row->fdh) }}</td>                  
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

@endsection

@push('scripts')
  <script>
    $(document).ready(function () {
      $('#t_search').DataTable({
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
              title: 'รายชื่อผู้มารับบริการสิทธิประกันสุขภาพ UCS วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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