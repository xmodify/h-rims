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
              <button onclick="fetchData()" type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button>
          </div>
      </div>
  </form> 
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ UC-OP ในจังหวัด VA วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
  <div class="card-body">
      <table id="t_sum" class="table table-striped table-bordered" width = "100%">
        <thead>          
          <tr class="table-primary"> 
              <th class="text-center" rowspan="2" width = "15%">Hmain</th> 
              <th class="text-center" rowspan="2">Visit ทั้งหมด</th> 
              <th class="text-center" rowspan="2">ค่ารักษาทั้งหมด</th> 
              <th class="text-center" rowspan="2">ชำระเอง</th>
              <th class="text-center" rowspan="2">กองทุนอื่น</th>
              <th class="text-center" rowspan="2">เรียกเก็บทั้งหมด</th> 
              <th class="text-center" colspan="2" style="background-color: #b3e5fc">อุบัติเหตุฉุกเฉิน</th>
              <th class="text-center" colspan="2" style="background-color: #d0d9ff">ผู้ป่วยทั่วไป</th>   
          </tr>
          <tr class="table-primary"> 
              <th class="text-center" style="background-color: #b3e5fc">Visit</th>
              <th class="text-center" style="background-color: #b3e5fc">เรียกเก็บ</th> 
              <th class="text-center" style="background-color: #d0d9ff">Visit</th>
              <th class="text-center" style="background-color: #d0d9ff">เรียกเก็บ</th>   
          </tr>
        </thead> 
        <tbody>          
          <?php $sum_income = 0 ; ?>  
          <?php $sum_rcpt_money  = 0 ; ?>  
          <?php $sum_other_price = 0 ; ?>
          <?php $sum_claim_price = 0 ; ?> 
          @foreach($sum as $row) 
          <tr>
            <td align="left" width = "15%">{{$row->hospmain}}</td>
            <td align="center">{{$row->visit}}</td>
            <td align="right">{{ number_format($row->income,2) }}</td>              
            <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
            <td align="right">{{ number_format($row->other_price,2) }}</td>
            <td align="right">{{ number_format($row->claim_price,2) }}</td> 
            <td align="center">{{ number_format($row->er_visit) }}</td> 
            <td align="right">{{ number_format($row->er_price,2) }}</td> 
            <td align="center">{{ number_format($row->normal_visit) }}</td> 
            <td align="right">{{ number_format($row->normal_price,2) }}</td> 
          </tr>
          <?php $sum_income += $row->income ; ?>
          <?php $sum_rcpt_money += $row->rcpt_money ; ?>
          <?php $sum_other_price += $row->other_price ; ?>
          <?php $sum_claim_price += $row->claim_price ; ?>
          @endforeach                 
        </tbody>
      </table>
      <br>
      <hr>
    <div style="overflow-x:auto;">            
      <table id="t_search" class="table table-striped table-bordered" width = "100%">
        <thead>
          <tr class="table-info"> 
              <th class="text-center" width = "13%">Hmain</th> 
              <th class="text-center" width = "5%">ประเภทผู้ป่วย</th>               
              <th class="text-center" width = "5%">วันที่รับบริการ</th>  
              <th class="text-center">Queue</th>     
              <th class="text-center" width = "10%">ชื่อ-สกุล</th>
              <th class="text-center">HN</th>    
              <th class="text-center" width = "5%">สิทธิการรักษา</th>
              <th class="text-center" width = "10%">อาการสำคัญ</th>
              <th class="text-center">PDX</th>
              <th class="text-center" width = "5%">ICD9</th> 
              <th class="text-center">ค่ารักษาทั้งหมด</th> 
              <th class="text-center">ชำระเอง</th>
              <th class="text-center">กองทุนอื่น</th>
              <th class="text-center">เรียกเก็บ</th> 
              <th class="text-center" width = "10%">รายการกองทุนอื่น</th>                
          </tr>
        </thead> 
        <tbody>          
          <?php $sum_income = 0 ; ?>  
          <?php $sum_rcpt_money  = 0 ; ?>  
          <?php $sum_other_price = 0 ; ?>
          <?php $sum_claim_price = 0 ; ?> 
          @foreach($search as $row) 
          <tr>
            <td align="left" width = "13%">{{$row->hospmain}}</td>
            <td align="left" width = "5%">{{$row->pt_status}}</td>                     
            <td align="left" width = "5%">{{ DateThai($row->vstdate) }} {{$row->vsttime}}</td>            
            <td align="center">{{ $row->oqueue }}</td>   
            <td align="left" width = "10%">{{$row->ptname}}</td> 
            <td align="center">{{$row->hn}}</td> 
            <td align="left" width = "5%">{{$row->pttype}}</td> 
            <td align="left" width = "10%">{{ $row->cc }}</td>
            <td align="right">{{ $row->pdx }}</td>
            <td align="left" width = "5%">{{$row->icd9}}</td>
            <td align="right">{{ number_format($row->income,2) }}</td>              
            <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
            <td align="right">{{ number_format($row->other_price,2) }}</td>
            <td align="right">{{ number_format($row->claim_price,2) }}</td> 
            <td align="left" width = "10%">{{ $row->other_list }}</td>      
          </tr>
          <?php $sum_income += $row->income ; ?>
          <?php $sum_rcpt_money += $row->rcpt_money ; ?>
          <?php $sum_other_price += $row->other_price ; ?>
          <?php $sum_claim_price += $row->claim_price ; ?>
          @endforeach                 
        </tbody>
      </table>
      <div>
        <h5 class="text-primary text-center">
          รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
          ชำระเอง: <strong>{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
          กองทุนอื่น: <strong>{{ number_format($sum_other_price,2)}}</strong> บาท |
          เรียกเก็บ: <strong>{{ number_format($sum_claim_price,2)}}</strong> บาท
          </h5>
      </div>  
    </div>
  </div> 

</div>      

<script>
  function showLoading() {
      Swal.fire({
          title: 'กำลังโหลด...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });
  }
  function fetchData() {
      showLoading();
  }
</script>

@endsection

@push('scripts')
  <script>
  $(document).ready(function () {
    $('#t_sum').DataTable({
      paging: false,
      searching: false,
      info: false,
      lengthChange: false,
      dom: '<"d-flex justify-content-end mb-2"B>t',  // ปุ่ม Excel ชิดขวา
      buttons: [
        {
          extend: 'excelHtml5',
          text: 'Excel',
          className: 'btn btn-success',
          title: 'สรุปผู้มารับบริการ UC-OP ในจังหวัด VA แยกสถานพยาบาลหลัก วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
        }
      ]
    });
  });
</script>
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ในจังหวัด VA วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

