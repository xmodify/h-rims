@extends('layouts.app')
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
<style>
    table {
    border-collapse: collapse;
    border-spacing: 0;
    width: 100%;
    border: 1px solid #ddd;
    }
    th, td {
    padding: 8px;
    }
</style> 

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
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ UC-OP Anywhere วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
  <div class="card-body">
    <!-- Pills Tabs -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab" aria-controls="search" aria-selected="false">รอส่ง Claim</button>
        </li>       
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="claim_fdh-tab" data-bs-toggle="pill" data-bs-target="#claim_fdh" type="button" role="tab" aria-controls="claim_fdh" aria-selected="false">ส่ง FDH</button>
        </li>
    </ul>
    <div class="tab-content pt-2" id="myTabContent">
      <div class="tab-pane fade show active" id="search" role="tabpanel" aria-labelledby="search-tab">
        <div style="overflow-x:auto;">            
          <table id="t_search" class="table table-striped table-bordered" width = "100%">
            <thead>
              <tr class="table-primary">
                  <th class="text-center">ลำดับ</th>
                  <th class="text-center">Authen</th>  
                  <th class="text-center">ปิดสิทธิ</th>
                  <th class="text-center">Action</th>
                  <th class="text-center">Q</th>     
                  <th class="text-center">ชื่อ-สกุล</th>    
                  <th class="text-center">CID</th>           
                  <th class="text-center">วันที่รับบริการ</th> 
                  <th class="text-center">เวลา</th>                                      
                  <th class="text-center">เบอร์โทร</th>
                  <th class="text-center">สิทธิการรักษา</th>
                  <th class="text-center">PDX</th>
                  <th class="text-center">ER</th>
                  <th class="text-center">Project</th>                  
                  <th class="text-center">ค่าบริการที่เบิกได้</th>                 
                  <th class="text-center">Department</th> 
                  <th class="text-center text-success" width="4%">UCAE</th>               
                  <th class="text-center text-success" width="4%">ประสงค์เบิก</th>
                  <th class="text-center text-success" width="4%">พร้อมส่ง</th>
              </tr>
            </thead> 
            <tbody> 
              <?php $count = 1 ; ?>
              @foreach($search as $row) 
              <tr>
                <td align="center">{{ $count }}</td>
                <td align="center" @if($row->auth_code == 'Y') style="color:green"
                  @elseif($row->auth_code == 'N') style="color:red" @endif>
                  <strong>{{ $row->auth_code }}</strong></td>               
                <td align="center" @if($row->endpoint == 'Y') style="color:green"
                  @elseif($row->endpoint == 'N') style="color:red" @endif>
                  <strong>{{ $row->endpoint }}</strong></td> 
                <td align="center"><a class="btn btn-outline-info btn-sm" href="{{ url('nhso_endpoint_pull'.$row->vstdate,$row->cid) }}" >Pull Endpoint</a></td> 
                <td align="center">{{ $row->oqueue }}</td>   
                <td align="left">{{$row->ptname}}</td> 
                <td align="center">{{$row->cid}}</td> 
                <td align="left">{{ DateThai($row->vstdate) }}</td>             
                <td align="rigth">{{$row->vsttime}}</td>                
                <td align="center">{{$row->informtel}}</td>
                <td align="left">{{$row->pttype}} [{{$row->hospmain}}]</td> 
                <td align="right">{{ $row->pdx }}</td>
                <td align="center">{{$row->ucae}}</td>     
                <td align="right">{{ $row->project }}</td>                    
                <td align="right">{{ number_format($row->debtor,2) }}</td>              
                <td align="left">{{ $row->department }}</td>
                <td class= "text-primary" align="center">{{ $row->nhso_ucae_type_code }}</td>  
                <td align="center" @if($row->request_funds == 'Y') style="color:green"
                  @elseif($row->request_funds == 'N') style="color:red" @endif>
                  <strong>{{ $row->request_funds }}</strong></td>  
                <td align="center" @if($row->confirm_and_locked == 'Y') style="color:green"
                  @elseif($row->confirm_and_locked == 'N') style="color:red" @endif>
                  <strong>{{ $row->confirm_and_locked }}</strong></td>                            
              </tr>
              <?php $count++; ?>
              @endforeach                 
            </tbody>
          </table>   
        </div>          
      </div>      
      <div class="tab-pane fade" id="claim_fdh" role="tabpanel" aria-labelledby="claim_fdh-tab">
        <div style="overflow-x:auto;">            
            <table id="t_claim_fdh" class="table table-striped table-bordered" width = "100%">
              <thead>
                <tr class="table-primary">
                    <th class="text-center">ลำดับ</th>
                    <th class="text-center">Authen</th>  
                    <th class="text-center">ปิดสิทธิ</th> 
                    <th class="text-center">ประสงค์เบิก</th> 
                    <th class="text-center">พร้อมส่ง</th>                 
                    <th class="text-center">Q</th>     
                    <th class="text-center">ชื่อ-สกุล</th>    
                    <th class="text-center">CID</th>           
                    <th class="text-center">วันที่รับบริการ</th> 
                    <th class="text-center">เวลา</th> 
                    <th class="text-center">สิทธิการรักษา</th>
                    <th class="text-center">PDX</th>
                    <th class="text-center">ER</th>
                    <th class="text-center">Project</th>                   
                    <th class="text-center">ค่าบริการที่เบิกได้</th>
                    <th class="text-center text-success">UCAE</th>
                    <th class="text-center text-success" width="6%">Upload FDH</th>
                    <th class="text-center text-success" width="6%">Rep NHSO</th> 
                    <th class="text-center text-success" width="6%">Error</th> 
                    <th class="text-center text-success" width="6%">STM ชดเชย</th> 
                    <th class="text-center text-success" width="6%">ผลต่าง</th> 
                    <th class="text-center text-success" width="6%">REP</th> 
                </tr>
              </thead> 
              <tbody> 
                <?php $count = 1 ; ?>
                @foreach($claim_fdh as $row) 
                <tr>
                  <td align="center">{{ $count }}</td>
                  <td align="center" @if($row->auth_code == 'Y') style="color:green"
                    @elseif($row->auth_code == 'N') style="color:red" @endif>
                    <strong>{{ $row->auth_code }}</strong></td>               
                  <td align="center" @if($row->endpoint == 'Y') style="color:green"
                    @elseif($row->endpoint == 'N') style="color:red" @endif>
                    <strong>{{ $row->endpoint }}</strong></td> 
                  <td align="center" @if($row->request_funds == 'Y') style="color:green"
                    @elseif($row->request_funds == 'N') style="color:red" @endif>
                    <strong>{{ $row->request_funds }}</strong></td>  
                  <td align="center" @if($row->confirm_and_locked == 'Y') style="color:green"
                    @elseif($row->confirm_and_locked == 'N') style="color:red" @endif>
                    <strong>{{ $row->confirm_and_locked }}</strong></td>                   
                  <td align="center">{{ $row->oqueue }}</td>   
                  <td align="left">{{$row->ptname}}</td> 
                  <td align="center">{{$row->cid}}</td> 
                  <td align="left">{{ DateThai($row->vstdate) }}</td>             
                  <td align="rigth">{{$row->vsttime}}</td>
                  <td align="left">{{$row->pttype}} [{{$row->hospmain}}]</td>    
                  <td align="right">{{ $row->pdx }}</td>   
                  <td align="center">{{$row->ucae}}</td>   
                  <td align="right">{{ $row->project }}</td>                         
                  <td align="right">{{ number_format($row->debtor,2) }}</td>  
                  <td class= "text-primary" align="center">{{ $row->nhso_ucae_type_code }}</td>             
                  <td class= "text-primary" align="center">{{ DateTimeThai($row->fdh) }}</td>
                  <td class= "text-primary" align="right">{{ number_format($row->rep_nhso,2) }}</td>
                  <td class= "text-primary" align="center">{{ $row->rep_error }}</td>
                  <td align="center">{{ number_format($row->receive_total,2) }}</td>
                  <td align="center" @if($row->receive_total-$row->rep_nhso > 0) style="color:green" 
                    @elseif($row->receive_total-$row->rep_nhso < 0) style="color:red" @endif>
                    {{ number_format($row->receive_total-$row->rep_nhso,2) }}</td>
                  <td class= "text-primary" align="center">{{ $row->repno }}</td>             
                </tr>
                <?php $count++; ?>
                @endforeach                 
              </tbody>
            </table>   
          </div>          
        </div> 
      </div>
    </div>
    <!-- Pills Tabs -->
  </div> 
</div>      
@endsection
<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" class="init">
    $(document).ready(function () {
        $('#t_search').DataTable();
    });
</script>
<script type="text/javascript" class="init">
    $(document).ready(function () {
        $('#t_claim_fdh').DataTable();
    });
</script>