@extends('layouts.app')

@section('content')

<div class="container-fluid"> 
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
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ UCS ฟอกไต วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
  <div class="card-body">
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
              <th class="text-center">เรียกเก็บ</th> 
              <th class="text-center text-primary">ชดเชย</th> 
              <th class="text-center text-primary">ผลต่าง</th> 
              <th class="text-center text-primary">REP</th>  
          </tr>
        </thead> 
        <tbody> 
          <?php $count = 1 ; ?>
          <?php $sum_income = 0 ; ?>  
          <?php $sum_rcpt_money = 0 ; ?>  
          <?php $sum_claim_price = 0 ; ?>           
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
              <td align="left" width = "10%">{{ $row->claim_list }}</td>  
              <td align="right">{{ number_format($row->claim_price,2) }}</td>
              <td align="right" @if($row->receive_total > 0) style="color:green" 
                  @elseif($row->receive_total < 0) style="color:red" @endif>
                  {{ number_format($row->receive_total,2) }}</td>
              <td align="right" @if($row->receive_total-$row->claim_price > 0) style="color:green" 
                  @elseif($row->receive_total-$row->claim_price < 0) style="color:red" @endif>
                  {{ number_format($row->receive_total-$row->claim_price,2) }}</td>
              <td align="right">{{ $row->repno }}</td> 
          </tr>
          <?php $count++; ?>
          <?php $sum_income += $row->income ; ?>
          <?php $sum_rcpt_money += $row->rcpt_money ; ?>
          <?php $sum_claim_price += $row->claim_price ; ?>
          <?php $sum_receive_total += $row->receive_total ; ?>
          @endforeach                 
        </tbody>
      </table>
      <div>
        <h5 class="text-primary text-center">
        รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
        ชำระเอง: <strong>{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
        เรียกเก็บ: <strong>{{ number_format($sum_claim_price,2)}}</strong> บาท |       
        ชดเชย: <strong  @if($sum_receive_total > 0) style="color:green" 
                  @elseif($sum_receive_total < 0) style="color:red" @endif>
                  {{ number_format($sum_receive_total,2)}}</strong> บาท |
        ผลต่าง: <strong  @if($sum_receive_total-$sum_claim_price > 0) style="color:green" 
                  @elseif($sum_receive_total-$sum_claim_price < 0) style="color:red" @endif>
                  {{ number_format($sum_receive_total-$sum_claim_price,2)}}</strong> บาท
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
              title: 'รายชื่อผู้มารับบริการ UCS ฟอกไต วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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