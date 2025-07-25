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
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ IP-ชำระเงิน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
  <div class="card-body">
    <!-- Pills Tabs -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab" aria-controls="search" aria-selected="false">รอชำระเงิน</button>
        </li>       
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab" aria-controls="claim" aria-selected="false">ชำระเงิน</button>
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
                <th class="text-center">AdjRW</th>
                <th class="text-center">ค่ารักษา</th>  
                <th class="text-center">ต้องชำระ</th> 
                <th class="text-center">ชำระแล้ว</th>
                <th class="text-center">ต้องเรียกเก็บ</th>
                <th class="text-center">ค้างชำระ</th>  
                <th class="text-center">ฝากมัดจำ</th>
                <th class="text-center">ถอนมัดจำ</th> 
                <th class="text-center" width = "5%">สถานะ</th>
                <th class="text-center">สรุป Chart</th>
              </tr>
            </thead> 
            <tbody> 
              <?php $count = 1 ; ?>
              <?php $sum_income = 0 ; ?>  
              <?php $sum_paid_money  = 0 ; ?>  
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
                <td align="center">{{ $row->adjrw }}</td>
                <td align="right">{{ number_format($row->income,2) }}</td>
                <td align="right">{{ number_format($row->paid_money,2) }}</td>
                <td align="right" @if($row->rcpt_money >0) style="color:green" @endif>
                  {{ number_format($row->rcpt_money,2) }} [{{ $row->rcpno }}]
                </td>
                <td align="right" style="color:red">{{ number_format($row->claim_price,2) }}</td> 
                <td align="right">{{ number_format($row->arrear_amount,2) }}</td>               
                <td align="right">{{ number_format($row->deposit_amount,2) }}</td>    
                <td align="right">{{ number_format($row->debit_amount,2) }}</td>   
                <td align="left" width = "5%">{{ $row->ipt_coll_status_type_name }}</td>
                <td align="center" @if($row->dch_sum == 'Y') style="color:green"
                  @elseif($row->dch_sum == 'N') style="color:red" @endif>
                  <strong>{{ $row->dch_sum }}</strong>
                </td> 
              </tr>
              <?php $count++; ?>
              <?php $sum_income += $row->income ; ?>
              <?php $sum_paid_money += $row->paid_money ; ?>
              <?php $sum_rcpt_money += $row->rcpt_money ; ?>
              <?php $sum_claim_price += $row->claim_price ; ?>
              @endforeach                 
            </tbody>
          </table>
          <div>
            <h5 class="text-primary text-center">
              รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
              ต้องชำระ: <strong>{{ number_format($sum_paid_money,2)}}</strong> บาท |
              ชำระแล้ว: <strong style="color:green">{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
              ต้องเรียกเก็บ: <strong style="color:red">{{ number_format($sum_claim_price,2)}}</strong> บาท
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
                  <th class="text-center">AdjRW</th>
                  <th class="text-center">ค่ารักษา</th>  
                  <th class="text-center">ต้องชำระ</th> 
                  <th class="text-center">ชำระแล้ว</th>
                  <th class="text-center">ค้างชำระ</th>  
                  <th class="text-center">ฝากมัดจำ</th>
                  <th class="text-center">ถอนมัดจำ</th> 
                  <th class="text-center" width = "5%">สถานะ</th>
                  <th class="text-center">สรุป Chart</th>
                </tr>
              </thead> 
              <tbody> 
                <?php $count = 1 ; ?>
                <?php $sum_income = 0 ; ?>  
                <?php $sum_paid_money  = 0 ; ?>  
                <?php $sum_rcpt_money  = 0 ; ?>
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
                  <td align="center">{{ $row->adjrw }}</td>
                  <td align="right">{{ number_format($row->income,2) }}</td>
                  <td align="right">{{ number_format($row->paid_money,2) }}</td>
                  <td align="right" style="color:green">{{ number_format($row->rcpt_money,2) }} [{{ $row->rcpno }}]</td>
                  <td align="right">{{ number_format($row->arrear_amount,2) }}</td>               
                  <td align="right">{{ number_format($row->deposit_amount,2) }}</td>    
                  <td align="right">{{ number_format($row->debit_amount,2) }}</td>   
                  <td align="left" width = "5%">{{ $row->ipt_coll_status_type_name }}</td>
                  <td align="center" @if($row->dch_sum == 'Y') style="color:green"
                    @elseif($row->dch_sum == 'N') style="color:red" @endif>
                    <strong>{{ $row->dch_sum }}</strong>
                  </td> 
                </tr>
                <?php $count++; ?>
                <?php $sum_income += $row->income ; ?>
                <?php $sum_paid_money += $row->paid_money ; ?>
                <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                @endforeach                 
              </tbody>
            </table>
            <div>
              <h5 class="text-primary text-center">
                รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
                ต้องชำระ: <strong>{{ number_format($sum_paid_money,2)}}</strong> บาท |
                ชำระแล้ว: <strong style="color:green">{{ number_format($sum_rcpt_money,2)}}</strong> บาท 
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
              title: 'รายชื่อผู้มารับบริการ IP-รอชำระเงิน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
              title: 'รายชื่อผู้มารับบริการ IP-ชำระเงิน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

