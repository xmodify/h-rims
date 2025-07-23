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
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ IP-STP บุคคลที่มีปัญหาสถานะและสิทธิ วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
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
                <th class="text-center" width = "5%">ตึกผู้ป่วย</th>
                <th class="text-center" width = "5%">วันที่ Admit</th>
                <th class="text-center" width = "5%">วันที่ Discharge</th>
                <th class="text-center">HN</th>
                <th class="text-center">AN</th>
                <th class="text-center" width = "10%">ชื่อ-สกุล</th>
                <th class="text-center">อายุ</th>
                <th class="text-center" width = "10%">สิทธิ</th>
                <th class="text-center" width = "10%">วินิจฉัยแพทย์</th>
                <th class="text-center">รหัสโรค</th>
                <th class="text-center">หัตถการ</th>
                <th class="text-center">ค่ารักษา</th>  
                <th class="text-center">ชำระเอง</th>
                <th class="text-center">เรียกเก็บ</th>
                <th class="text-center">Refer</th>  
                <th class="text-center">AdjRW</th>
                <th class="text-center" width = "5%">สถานะ</th>
                <th class="text-center">Authen</th>      
                <th class="text-center">สรุป Chart</th>
                <th class="text-center">พร้อมส่ง</th>
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
                <td align="right" width = "5%">{{$row->ward}}</td>
                <td align="center" width = "5%">{{ DateThai($row->regdate) }}</td>
                <td align="center" width = "5%">{{ DateThai($row->dchdate) }}</td>
                <td align="center">{{ $row->hn }}</td>
                <td align="center">{{ $row->an }}</td>
                <td align="left" width = "10%">{{ $row->ptname }}</td>
                <td align="center">{{ $row->age_y }}</td>
                <td align="left" width = "10%">{{ $row->pttype }}</td>
                <td align="left" width = "10%">{{ $row->diag_text_list }}</td>
                <td align="right">{{ $row->icd10 }}</td>
                <td align="right">{{ $row->icd9 }}</td>
                <td align="right">{{ number_format($row->income,2) }}</td>
                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                <td align="right">{{ number_format($row->claim_price,2) }}</td> 
                <td align="right">{{ $row->refer }}</td>
                <td align="center">{{ $row->adjrw }}</td>
                <td align="left" width = "5%">{{ $row->ipt_coll_status_type_name }}</td>
                <td align="center" @if($row->auth_code == 'Y') style="color:green"
                  @elseif($row->auth_code == 'N') style="color:red" @endif>
                  <strong>{{ $row->auth_code }}</strong>
                </td>     
                <td align="center" @if($row->dch_sum == 'Y') style="color:green"
                  @elseif($row->dch_sum == 'N') style="color:red" @endif>
                  <strong>{{ $row->dch_sum }}</strong>
                </td>  
                <td align="center" @if($row->data_ok == 'Y') style="color:green"
                  @elseif($row->data_ok == 'N') style="color:red" @endif>
                  <strong>{{ $row->data_ok }}</strong>
                </td>
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
                  <th class="text-center" width = "5%">ตึกผู้ป่วย</th>
                  <th class="text-center" width = "5%">วันที่ Admit</th>
                  <th class="text-center" width = "5%">วันที่ Discharge</th>
                  <th class="text-center">HN</th>
                  <th class="text-center">AN</th>
                  <th class="text-center" width = "10%">ชื่อ-สกุล</th>
                  <th class="text-center">อายุ</th>
                  <th class="text-center" width = "10%">สิทธิ</th>
                  <th class="text-center" width = "10%">วินิจฉัยแพทย์</th>
                  <th class="text-center">รหัสโรค</th>
                  <th class="text-center">หัตถการ</th>
                  <th class="text-center">ค่ารักษา</th>  
                  <th class="text-center">ชำระเอง</th>
                  <th class="text-center">เรียกเก็บ</th>
                  <th class="text-center">Refer</th>  
                  <th class="text-center">AdjRW</th>
                  <th class="text-center">สถานะ</th>
                  <th class="text-center">ส่ง Claim</th>
                  <th class="text-center" width = "5%">Error</th>
                  <th class="text-center">อัตราจ่าย/Rw</th> 
                  <th class="text-center">ชดเชย Rw</th>
                  <th class="text-center">ชดเชย Other</th>
                  <th class="text-center">ชดเชยทั้งหมด</th> 
                  <th class="text-center">ส่วนต่าง</th> 
                  <th class="text-center">REP No.</th> 
                </tr>
              </thead> 
              <tbody> 
                <?php $count = 1 ; ?>
                <?php $sum_income = 0 ; ?>  
                <?php $sum_rcpt_money = 0 ; ?>
                <?php $sum_claim_price = 0 ; ?>  
                <?php $sum_receive_rw = 0 ; ?> 
                <?php $sum_receive_total = 0 ; ?>
                @foreach($claim as $row) 
                <tr>
                  <td align="center">{{ $count }}</td>                                  
                  <td align="right" width = "5%">{{$row->ward}}</td>
                  <td align="center" width = "5%">{{ DateThai($row->regdate) }}</td>
                  <td align="center" width = "5%">{{ DateThai($row->dchdate) }}</td>
                  <td align="center">{{ $row->hn }}</td>
                  <td align="center">{{ $row->an }}</td>
                  <td align="left" width = "10%">{{ $row->ptname }}</td>
                  <td align="center">{{ $row->age_y }}</td>
                  <td align="left" width = "10%">{{ $row->pttype }}</td>
                  <td align="left" width = "10%">{{ $row->diag_text_list }}</td>
                  <td align="right">{{ $row->icd10 }}</td>
                  <td align="right">{{ $row->icd9 }}</td>
                  <td align="right">{{ number_format($row->income,2) }}</td>
                  <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                  <td align="right">{{ number_format($row->claim_price,2) }}</td> 
                  <td align="right">{{ $row->refer }}</td>
                  <td align="center">{{ $row->adjrw }}</td>
                  <td align="left" width = "5%">{{ $row->ipt_coll_status_type_name }}</td>
                  <td align="center">{{ DateThai($row->fdh) }}</td>
                  <td align="center">{{ $row->rep_error }}</td>
                  <td align="right">{{ number_format($row->fund_ip_payrate,2) }}</td>
                  <td align="right">{{ number_format($row->receive_ip_compensate_pay,2) }}</td>
                  <td align="right">{{ number_format($row->receive_total-$row->receive_ip_compensate_pay,2) }}</td>
                  <td align="right">{{ number_format($row->receive_total,2) }}</td>
                  <td align="right" @if($row->receive_total-$row->claim_price > 0) style="color:green"
                    @elseif($row->receive_total-$row->claim_price <0) style="color:red" @endif>
                    {{ number_format($row->receive_total-$row->claim_price,2) }}
                  </td>
                  <td align="center">{{ $row->repno }}</td>
                </tr>
                <?php $count++; ?>
                <?php $sum_income += $row->income ; ?>
                <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                <?php $sum_claim_price += $row->claim_price ; ?>
                <?php $sum_receive_rw += $row->receive_ip_compensate_pay ; ?>
                <?php $sum_receive_total += $row->receive_total ; ?>
                @endforeach                 
              </tbody>
            </table>
            <div>
              <h5 class="text-primary text-center">
              รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
              ชำระเอง: <strong>{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
              เรียกเก็บ: <strong>{{ number_format($sum_claim_price,2)}}</strong> บาท | 
              ชดเชย Rw: <strong>{{ number_format($sum_receive_rw,2)}}</strong> บาท |
              ชดเชย Other: <strong>{{ number_format($sum_receive_total-$sum_receive_rw,2)}}</strong> บาท | 
              ชดเชยทั้งหมด: <strong  @if($sum_receive_total > 0) style="color:green" 
                        @elseif($sum_receive_total < 0) style="color:red" @endif>
                        {{ number_format($sum_receive_total,2)}}</strong> บาท |
              ผลต่าง: <strong  @if($sum_receive_total-$sum_claim_price> 0) style="color:green" 
                        @elseif($sum_receive_total-$sum_claim_price< 0) style="color:red" @endif>
                        {{ number_format($sum_receive_total-$sum_claim_price,2)}}</strong> บาท              
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
              title: 'รายชื่อผู้มารับบริการ IP-STP บุคคลที่มีปัญหาสถานะและสิทธิ รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
              title: 'รายชื่อผู้มารับบริการ IP-STP บุคคลที่มีปัญหาสถานะและสิทธิ ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

