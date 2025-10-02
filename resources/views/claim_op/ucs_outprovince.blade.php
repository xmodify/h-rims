@extends('layouts.app')

@section('content')

<div class="container-fluid"> 
  <div class="row justify-content-center">      
    <div class="col-md-12">
        <form method="POST" enctype="multipart/form-data">
        @csrf
          <div class="row">                          
              <div class="col-md-9" align="left"></div>
              <div class="col-lg-3 d-flex justify-content-lg-end">
                <div class="d-flex align-items-center gap-2">
                  <select class="form-select" name="budget_year">
                    @foreach ($budget_year_select as $row)
                      <option value="{{ $row->LEAVE_YEAR_ID }}"
                        {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                        {{ $row->LEAVE_YEAR_NAME }}
                      </option>
                    @endforeach
                  </select>
                  <button type="submit" class="btn btn-primary">{{ __('ค้นหา') }}</button>
                </div>
              </div>
          </div>
        </form>
    </div>    
  </div>
  <canvas id="sum_month" style="max-height: 400px;"></canvas> 
  <hr>
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
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ UC-OP ต่างจังหวัด วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
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
                  <th class="text-center">ค่ารถ Refer</th>
                  <th class="text-center">ER Type</th> 
                  <th class="text-center">Project</th>                  
                  <th class="text-center">Claim AE</th> 
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
                <td align="right">{{ number_format($row->refer,2) }}</td>
                <td align="center">{{ $row->er }}</td>
                <td align="center">{{ $row->project }}</td>
                <td align="center">{{ $row->ae }}</td>         
              </tr>
              <?php $count++; ?>
              <?php $sum_income += $row->income ; ?>
              <?php $sum_rcpt_money += $row->rcpt_money ; ?>              
              @endforeach                 
            </tbody>
          </table>
          <div>
            <h5 class="text-primary text-center">
              รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
              ชำระเอง: <strong>{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
              เรียกเก็บ: <strong>{{ number_format($sum_income-$sum_rcpt_money,2)}}</strong> บาท
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
                    <th class="text-center">ค่ารถ Refer</th>
                    <th class="text-center">Project</th> 
                    <th class="text-center">Claim AE</th> 
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
                    <td align="right">{{ number_format($row->refer,2) }}</td>
                    <td align="center">{{ $row->project }}</td>   
                    <td align="center">{{ $row->ae }}</td> 
                    <td align="right" class="text-primary">{{ number_format($row->rep_nhso,2) }}</td>
                    <td align="center">{{ $row->rep_error }}</td>
                    <td align="right" @if($row->receive_total > 0) style="color:green" 
                        @elseif($row->receive_total < 0) style="color:red" @endif>
                        {{ number_format($row->receive_total,2) }}</td>
                    <td align="right" @if($row->receive_total-$row->income-$row->rcpt_money > 0) style="color:green" 
                        @elseif($row->receive_total-$row->income-$row->rcpt_money < 0) style="color:red" @endif>
                        {{ number_format($row->receive_total-$row->income-$row->rcpt_money,2) }}</td>
                    <td align="right">{{ $row->repno }}</td> 
                </tr>
                <?php $count++; ?>
                <?php $sum_income += $row->income ; ?>
                <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                <?php $sum_rep_nhso += $row->rep_nhso ; ?>
                <?php $sum_receive_total += $row->receive_total ; ?>
                @endforeach                 
              </tbody>
            </table>
            <div>
              <h5 class="text-primary text-center">
              รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
              ชำระเอง: <strong>{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
              เรียกเก็บ: <strong>{{ number_format($sum_income-$sum_rcpt_money,2)}}</strong> บาท |
              ชดเชย: <strong  @if($sum_receive_total > 0) style="color:green" 
                        @elseif($sum_receive_total < 0) style="color:red" @endif>
                        {{ number_format($sum_receive_total,2)}}</strong> บาท |
              ผลต่าง: <strong  @if($sum_receive_total-$sum_income-$sum_rcpt_money > 0) style="color:green" 
                        @elseif($sum_receive_total-$sum_income-$sum_rcpt_money < 0) style="color:red" @endif>
                        {{ number_format($sum_receive_total-$sum_income-$sum_rcpt_money,2)}}</strong> บาท
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ต่างจังหวัด รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ต่างจังหวัด ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    new Chart(document.querySelector('#sum_month'), {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($month); ?>,
        datasets: [
          {
            label: 'เรียกเก็บ',
            data: <?php echo json_encode($claim_price); ?>,
            backgroundColor: 'rgba(255, 159, 64, 0.2)',
            borderColor: 'rgb(255, 159, 64)',
            borderWidth: 1
          },
          {
            label: 'ชดเชย',
            data: <?php echo json_encode($receive_total); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgb(75, 192, 192)',
            borderWidth: 1
          }
        ]
      }, 
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': ' + context.formattedValue + ' บาท';
              }
            }
          },
          datalabels: {
            anchor: 'end',
            align: 'end',
            color: '#000',
            font: {
              weight: 'bold',
              size: 10
            },
            formatter: (value) => value.toLocaleString() + ' บาท'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return value.toLocaleString() + ' บาท';
              }
            }
          }
        }
      },
      plugins: [ChartDataLabels] // ✅ เปิดใช้งาน plugin datalabels ตรงนี้
    });
  });
</script>