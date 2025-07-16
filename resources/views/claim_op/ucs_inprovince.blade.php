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
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ UC-OP ในจังหวัด วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
  <div class="card-body">
    <!-- Pills Tabs -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab" aria-controls="search" aria-selected="false">รอส่ง Claim</button>
        </li>       
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab" aria-controls="claim" aria-selected="false">ส่ง Claim</button>
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
                  <th class="text-center">ประสงค์เบิก</th> 
                  <th class="text-center">พร้อมส่ง</th>   
                  <th class="text-center" width = "5%">วันที่รับบริการ</th>  
                  <th class="text-center">Queue</th>     
                  <th class="text-center" width = "10%">ชื่อ-สกุล</th>
                  <th class="text-center">HN</th>    
                  <th class="text-center" width = "10%">สิทธิการรักษา</th>
                  <th class="text-center" width = "10%">อาการสำคัญ</th>
                  <th class="text-center">PDX</th>
                  <th class="text-center" width = "5%">ICD9</th> 
                  <th class="text-center">ค่ารักษาทั้งหมด</th> 
                  <th class="text-center">ชำระเอง</th>
                  <th class="text-center" width = "10%">รายการที่เรียกเก็บ</th>  
                  <th class="text-center">เรียกเก็บ</th> 
                  <th class="text-center">Project</th> 
              </tr>
            </thead> 
            <tbody> 
              <?php $count = 1 ; ?>
              <?php $sum_income = 0 ; ?>  
              <?php $sum_rcpt_money  = 0 ; ?>  
              <?php $sum_claim_price = 0 ; ?> 
              @foreach($search as $row) 
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
                <td align="left" width = "5%">{{ DateThai($row->vstdate) }} {{$row->vsttime}}</td>            
                <td align="center">{{ $row->oqueue }}</td>   
                <td align="left" width = "10%">{{$row->ptname}}</td> 
                <td align="center">{{$row->hn}}</td> 
                <td align="left" width = "10%">{{$row->pttype}} [{{$row->hospmain}}]</td> 
                <td align="left" width = "10%">{{ $row->cc }}</td>
                <td align="right">{{ $row->pdx }}</td>
                <td align="right" width = "5%">{{$row->icd9}}</td>
                <td align="right">{{ number_format($row->income,2) }}</td>              
                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                <td align="right" width = "10%">{{ $row->claim_list }}</td>   
                <td align="right">{{ number_format($row->claim_price,2) }}</td> 
                <td align="right">{{ $row->project }}</td>         
              </tr>
              <?php $count++; ?>
              <?php $sum_income += $row->income ; ?>
              <?php $sum_rcpt_money += $row->rcpt_money ; ?>
              <?php $sum_claim_price += $row->claim_price ; ?>
              @endforeach                 
            </tbody>
          </table>
          <div>
            <h5 class="text-primary text-center">
              รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
              ชำระเอง: <strong>{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
              เรียกเก็บ: <strong>{{ number_format($sum_claim_price,2)}}</strong> บาท
              </h5>
          </div>  
        </div>          
      </div>      
      <div class="tab-pane fade" id="claim" role="tabpanel" aria-labelledby="claim-tab">
        <div style="overflow-x:auto;">            
            <table id="t_claim" class="table table-striped table-bordered" width = "100%">
              <thead>
                <tr class="table-primary">
                    <th class="text-center">ลำดับ</th>                      
                    <th class="text-center" width = "5%">วันที่รับบริการ</th>  
                    <th class="text-center">Queue</th>     
                    <th class="text-center" width = "10%">ชื่อ-สกุล</th>
                    <th class="text-center">HN</th> 
                    <th class="text-center" width = "10%">สิทธิการรักษา</th>
                    <th class="text-center" width = "10%">อาการสำคัญ</th>
                    <th class="text-center">PDX</th>
                    <th class="text-center" width = "5%">ICD9</th> 
                    <th class="text-center">ค่ารักษาทั้งหมด</th> 
                    <th class="text-center">ชำระเอง</th>
                    <th class="text-center" width = "10%">รายการที่เรียกเก็บ</th>                    
                    <th class="text-center">บริการเฉพาะ</th>
                    <th class="text-center">PPFS</th>
                    <th class="text-center">สมุนไพร</th>
                    <th class="text-center">Project</th>
                    <th class="text-center text-primary">Rep NHSO</th> 
                    <th class="text-center text-primary">Error</th> 
                    <th class="text-center text-primary">STM ชดเชย</th> 
                    <th class="text-center text-primary">ผลต่าง</th> 
                    <th class="text-center text-primary">REP</th>  
                </tr>
              </thead> 
              <tbody> 
                <?php $count = 1 ; ?>
                <?php $sum_income = 0 ; ?>  
                <?php $sum_rcpt_money = 0 ; ?>  
                <?php $sum_uc_cr = 0 ; ?> 
                <?php $sum_ppfs = 0 ; ?> 
                <?php $sum_herb = 0 ; ?> 
                <?php $sum_rep_nhso = 0 ; ?>  
                <?php $sum_receive_total = 0 ; ?> 
                @foreach($claim as $row) 
                <tr>
                    <td align="center">{{ $count }}</td>                   
                    <td align="left" width = "5%">{{ DateThai($row->vstdate) }} {{$row->vsttime}}</td>            
                    <td align="center">{{ $row->oqueue }}</td>   
                    <td align="left" width = "10%">{{$row->ptname}}</td> 
                    <td align="center">{{$row->hn}}</td>
                    <td align="left" width = "10%">{{$row->pttype}} [{{$row->hospmain}}]</td> 
                    <td align="left" width = "10%">{{ $row->cc }}</td>
                    <td align="right">{{ $row->pdx }}</td>                  
                    <td align="right" width = "5%">{{$row->icd9}}</td> 
                    <td align="right">{{ number_format($row->income,2) }}</td>              
                    <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                    <td align="right" width = "10%">{{ $row->claim_list }}</td>  
                    <td align="right">{{ number_format($row->uc_cr,2) }}</td> 
                    <td align="right">{{ number_format($row->ppfs,2) }}</td> 
                    <td align="right">{{ number_format($row->herb,2) }}</td> 
                    <td align="right">{{ $row->project }}</td>  
                    <td align="right" class="text-primary">{{ number_format($row->rep_nhso,2) }}</td>
                    <td align="center">{{ $row->rep_error }}</td>
                    <td align="right" @if($row->receive_total > 0) style="color:green" 
                        @elseif($row->receive_total < 0) style="color:red" @endif>
                        {{ number_format($row->receive_total,2) }}</td>
                    <td align="right" @if($row->receive_total-$row->uc_cr-$row->ppfs-$row->herb > 0) style="color:green" 
                        @elseif($row->receive_total-$row->uc_cr-$row->ppfs-$row->herb < 0) style="color:red" @endif>
                        {{ number_format($row->receive_total-$row->uc_cr-$row->ppfs-$row->herb,2) }}</td>
                    <td align="right">{{ $row->repno }}</td> 
                </tr>
                <?php $count++; ?>
                <?php $sum_income += $row->income ; ?>
                <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                <?php $sum_uc_cr += $row->uc_cr ; ?>
                <?php $sum_ppfs += $row->ppfs ; ?>
                <?php $sum_herb += $row->herb ; ?>
                <?php $sum_rep_nhso += $row->rep_nhso ; ?>
                <?php $sum_receive_total += $row->receive_total ; ?>
                @endforeach                 
              </tbody>
            </table>
            <div>
              <h5 class="text-primary text-center">
              รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
              ชำระเอง: <strong>{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
              บริการเฉพาะ: <strong>{{ number_format($sum_uc_cr,2)}}</strong> บาท |
              PPFS: <strong>{{ number_format($sum_ppfs,2)}}</strong> บาท |
              สมุนไพร: <strong>{{ number_format($sum_herb,2)}}</strong> บาท |
              ชดเชย: <strong  @if($sum_receive_total > 0) style="color:green" 
                        @elseif($sum_receive_total < 0) style="color:red" @endif>
                        {{ number_format($sum_receive_total,2)}}</strong> บาท |
              ผลต่าง: <strong  @if($sum_receive_total-$sum_uc_cr-$sum_ppfs-$sum_herb > 0) style="color:green" 
                        @elseif($sum_receive_total-$sum_uc_cr-$sum_ppfs-$sum_herb < 0) style="color:red" @endif>
                        {{ number_format($sum_receive_total-$sum_uc_cr-$sum_ppfs-$sum_herb,2)}}</strong> บาท
              </h5>
            </div>     
          </div>          
        </div> 
      </div>
    </div>
    <!-- Pills Tabs -->
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ในจังหวัด รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
      $('#t_claim').DataTable({
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ในจังหวัด ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

