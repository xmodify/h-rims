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
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ Admit Homeward วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>          

  <div class="card-body">
    <div class="row">        
      <div class="col-md-12"> 
        <div style="overflow-x:auto;">            
          <table id="list" class="table table-striped table-bordered" width = "100%">
            <thead>
              <tr class="table-primary">
                <th class="text-center">ลำดับ</th>
                <th class="text-center">AuthenCode</th>
                <th class="text-center">Action</th>
                <th class="text-center">วัน-เวลาที่รับบริการ</th>
                <th class="text-center">Q</th>
                <th class="text-center">HN</th>
                <th class="text-center">CID</th>
                <th class="text-center">ชื่อ-สกุล</th>                    
                <th class="text-center">อายุ</th>
                <th class="text-center">สิทธิ</th>
                <th class="text-center">เบอร์มือถือ</th>
                <th class="text-center">เบอร์บ้าน</th>
                <th class="text-center">จุดบริการ</th>            
              </tr>     
            </thead> 
            <tbody> 
              <?php $count = 1 ; ?>
              @foreach($sql as $row) 
              <tr>
                <td align="center">{{ $count }}</td> 
                <td align="center">{{ $row->claimCode }}</td> 
                <td align="center"><a class="btn btn-outline-info btn-sm" href="{{ url('nhso_endpoint_pull'.$row->vstdate,$row->cid) }}" >Pull Authen</a></td> 
                <td align="center">{{ DateThai($row->vstdate) }} เวลา {{ $row->vsttime }}</td>
                <td align="center">{{ $row->oqueue }}</td>
                <td align="center">{{ $row->hn }}</td>
                <td align="center">{{ $row->cid }}</td>
                <td align="left">{{ $row->ptname }}</td>
                <td align="center">{{ $row->age_y }}</td>
                <td align="left">{{ $row->pttype }}</td> 
                <td align="center">{{ $row->mobile_phone_number }}</td>
                <td align="center">{{ $row->hometel }}</td>  
                <td align="right">{{ $row->department }}</td> 
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

<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" class="init">
    $(document).ready(function () {
        $('#list').DataTable();
    });
</script>

</html>