@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center">      
    <div class="col-md-12">
        <form method="POST" enctype="multipart/form-data">
        @csrf
          <div class="row">                          
              <div class="col-md-9" align="left"></div>
              <div class="col-md-2" align="right">     
                  <select class="form-select my-1" name="budget_year">
                  @foreach ($budget_year_select as $row)
                  <option value="{{$row->LEAVE_YEAR_ID}}" @if ($budget_year == "$row->LEAVE_YEAR_ID") selected="selected"  @endif>{{$row->LEAVE_YEAR_NAME}}</option>
                  @endforeach 
                  </select>                        
              </div>
              <div class="col-md-1" align="right">  
                  <button type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button> 
              </div>
          </div>
        </form>
    </div>    
  </div>
</div>  
<!-- row -->
<div class="container-fluid">
  <div class="row justify-content-center">  
    <div class="col-md-6">
      <div class="card border-info">
        <div class="card-header text-success"><strong>จำนวนผู้มารับบริการผู้ป่วยนอก ปีงบประมาณ {{$budget_year}} </strong></div>
        <canvas id="visit" style="width: 100%; height: 350px"></canvas>             
      </div>      
    </div>   
    <div class="col-md-6">
      <div class="card border-info">
        <div class="card-header text-success"><strong>จำนวนผู้มารับบริการผู้ป่วยนอกแยก OP-PP ปีงบประมาณ {{$budget_year}}</strong></div>
        <canvas id="visit_oppp" style="width: 100%; height: 350px"></canvas>             
      </div>      
    </div>      
  </div>
</div> 
<br>
<!-- row -->
<div class="container-fluid">   
  <div class="card border-info">
    <div class="card-header bg-success bg-opacity-75 text-white">จำนวนผู้มารับบริการผู้ป่วยนอกแยกกลุ่มสิทธิหลัก (ครั้ง) ปีงบประมาณ {{$budget_year}} </div>
    <div class="card-body" style="overflow-x:auto;">
      <table id="visit_pttype" class="table table-bordered table-striped my-3" width ="100%">
        <thead>
        <tr class="table-primary">
            <th class="text-center" rowspan="2">เดือน</th>
            <th class="text-center" colspan="2">ทั้งหมด</th>
            <th class="text-center" colspan="2">ประกันสุขภาพ</th>     
            <th class="text-center" colspan="2">ข้าราชการ</th>  
            <th class="text-center" colspan="2">ประกันสังคม</th>
            <th class="text-center" colspan="2">อปท.</th>
            <th class="text-center" colspan="2">ต่างด้าว</th>
            <th class="text-center" colspan="2">Stateless</th>
            <th class="text-center" colspan="2">ชำระเงิน/พรบ.</th>                 
        </tr>    
        <tr class="table-primary">            
            <th class="text-center">visit</th>
            <th class="text-center">income</th>
            <th class="text-center">visit</th>
            <th class="text-center">income</th>    
            <th class="text-center">visit</th>
            <th class="text-center">income</th> 
            <th class="text-center">visit</th>
            <th class="text-center">income</th>
            <th class="text-center">visit</th>
            <th class="text-center">income</th>
            <th class="text-center">visit</th>
            <th class="text-center">income</th>
            <th class="text-center">visit</th>
            <th class="text-center">income</th>
            <th class="text-center">visit</th>
            <th class="text-center">income</th>                 
        </tr>    
        </thead> 
        <?php $count = 1 ; ?> 
        <?php $sum_visit = 0 ; ?> 
        <?php $sum_income = 0 ; ?>   
        <?php $sum_ucs = 0 ; ?>  
        <?php $sum_ucs_income = 0 ; ?> 
        <?php $sum_ofc = 0 ; ?>  
        <?php $sum_ofc_income = 0 ; ?>
        <?php $sum_sss = 0 ; ?> 
        <?php $sum_sss_income = 0 ; ?>  
        <?php $sum_lgo = 0 ; ?>  
        <?php $sum_lgo_income = 0 ; ?>  
        <?php $sum_fss = 0 ; ?>  
        <?php $sum_fss_income = 0 ; ?>  
        <?php $sum_stp = 0 ; ?> 
        <?php $sum_stp_income = 0 ; ?>   
        <?php $sum_pay = 0 ; ?>  
        <?php $sum_pay_income = 0 ; ?>  
        @foreach($visit_month as $row)          
        <tr>
            <td align="center">{{ $row->month }}</td> 
            <td align="right">{{ number_format($row->visit) }}</td>
            <td align="right" class="text-success">{{ number_format($row->income,2) }}</td> 
            <td align="right">{{ number_format($row->ucs) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->ucs_income,2) }}</td> 
            <td align="right">{{ number_format($row->ofc) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->ofc_income,2) }}</td> 
            <td align="right">{{ number_format($row->sss) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->sss_income,2) }}</td> 
            <td align="right">{{ number_format($row->lgo) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->lgo_income,2) }}</td> 
            <td align="right">{{ number_format($row->fss) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->fss_income,2) }}</td>
            <td align="right">{{ number_format($row->stp) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->stp_income,2) }}</td> 
            <td align="right">{{ number_format($row->pay) }}</td>   
            <td align="right" class="text-success">{{ number_format($row->pay_income,2) }}</td>              
        </tr>                
        <?php $count++; ?>
        <?php $sum_visit += $row->visit ; ?>
        <?php $sum_income += $row->income ; ?>
        <?php $sum_ucs += $row->ucs ; ?>
        <?php $sum_ucs_income += $row->ucs_income ; ?>
        <?php $sum_ofc += $row->ofc ; ?>
        <?php $sum_ofc_income += $row->ofc_income ; ?>
        <?php $sum_sss += $row->sss ; ?>
        <?php $sum_sss_income += $row->sss_income ; ?>
        <?php $sum_lgo += $row->lgo ; ?>
        <?php $sum_lgo_income += $row->lgo_income ; ?>
        <?php $sum_fss += $row->fss ; ?>
        <?php $sum_fss_income += $row->fss_income ; ?>
        <?php $sum_stp += $row->stp ; ?>
        <?php $sum_stp_income += $row->stp_income ; ?>
        <?php $sum_pay += $row->pay ; ?>
        <?php $sum_pay_income += $row->pay_income ; ?>
        @endforeach     
        <tr>
            <td align="right"><strong>รวม</strong></td>
            <td align="right"><strong>{{number_format($sum_visit)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_income,2)}}</strong></td>
            <td align="right"><strong>{{number_format($sum_ucs)}}</strong></td>     
            <td align="right" class="text-success"><strong>{{number_format($sum_ucs_income,2)}}</strong></td>   
            <td align="right"><strong>{{number_format($sum_ofc)}}</strong></td>  
            <td align="right" class="text-success"><strong>{{number_format($sum_ofc_income,2)}}</strong></td> 
            <td align="right"><strong>{{number_format($sum_sss)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_sss_income,2)}}</strong></td>
            <td align="right"><strong>{{number_format($sum_lgo)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_lgo_income,2)}}</strong></td>
            <td align="right"><strong>{{number_format($sum_fss)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_fss_income,2)}}</strong></td>
            <td align="right"><strong>{{number_format($sum_stp)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_stp_income,2)}}</strong></td>
            <td align="right"><strong>{{number_format($sum_pay)}}</strong></td>   
            <td align="right" class="text-success"><strong>{{number_format($sum_pay_income,2)}}</strong></td>                
        </tr>   
      </table>  
    </div>         
  </div>
</div>     
<br>
<!-- row -->
<div class="container-fluid">   
  <div class="card border-info">
    <div class="card-header bg-success bg-opacity-75 text-white">จำนวนผู้มารับบริการผู้ป่วยนอกแยกหมวดค่าใช้จ่าย ปีงบประมาณ {{$budget_year}} </div>
    <div class="card-body" style="overflow-x:auto;">
      <table id="visit_pttype" class="table table-bordered table-striped my-3" width ="100%">
        <thead>
        <tr class="table-primary">
            <th class="text-center" rowspan="2">เดือน</th>
            <th class="text-center" colspan="2">ทั้งหมด</th>
            <th class="text-center" colspan="2">ประกันสุขภาพ</th>     
            <th class="text-center" colspan="2">ข้าราชการ</th>  
            <th class="text-center" colspan="2">ประกันสังคม</th>
            <th class="text-center" colspan="2">อปท.</th>
            <th class="text-center" colspan="2">ต่างด้าว</th>
            <th class="text-center" colspan="2">Stateless</th>
            <th class="text-center" colspan="2">ชำระเงิน/พรบ.</th>                 
        </tr>    
        <tr class="table-primary">            
            <th class="text-center">drug</th>
            <th class="text-center">lab</th>
            <th class="text-center">drug</th>
            <th class="text-center">lab</th>    
            <th class="text-center">drug</th>
            <th class="text-center">lab</th> 
            <th class="text-center">drug</th>
            <th class="text-center">lab</th>
            <th class="text-center">drug</th>
            <th class="text-center">lab</th>
            <th class="text-center">drug</th>
            <th class="text-center">lab</th>
            <th class="text-center">drug</th>
            <th class="text-center">lab</th>
            <th class="text-center">drug</th>
            <th class="text-center">lab</th>               
        </tr>    
        </thead> 
        <?php $count = 1 ; ?> 
        <?php $sum_inc_drug = 0 ; ?> 
        <?php $sum_inc_lab = 0 ; ?>   
        <?php $sum_ucs_inc_drug = 0 ; ?>  
        <?php $sum_ucs_inc_lab = 0 ; ?> 
        <?php $sum_ofc_inc_drug = 0 ; ?>  
        <?php $sum_ofc_inc_lab = 0 ; ?>
        <?php $sum_sss_inc_drug = 0 ; ?> 
        <?php $sum_sss_inc_lab = 0 ; ?>  
        <?php $sum_lgo_inc_drug = 0 ; ?>  
        <?php $sum_lgo_inc_lab = 0 ; ?>  
        <?php $sum_fss_inc_drug = 0 ; ?>  
        <?php $sum_fss_inc_lab = 0 ; ?>  
        <?php $sum_stp_inc_drug = 0 ; ?> 
        <?php $sum_stp_inc_lab = 0 ; ?>   
        <?php $sum_pay_inc_drug = 0 ; ?>  
        <?php $sum_pay_inc_lab = 0 ; ?>  
        @foreach($visit_month as $row)          
        <tr>
            <td align="center">{{ $row->month }}</td> 
            <td align="right" class="text-primary">{{ number_format($row->inc_drug) }}</td>
            <td align="right" class="text-success">{{ number_format($row->inc_lab,2) }}</td> 
            <td align="right" class="text-primary">{{ number_format($row->ucs_inc_drug) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->ucs_inc_lab,2) }}</td> 
            <td align="right" class="text-primary">{{ number_format($row->ofc_inc_drug) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->ofc_inc_lab,2) }}</td> 
            <td align="right" class="text-primary">{{ number_format($row->sss_inc_drug) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->sss_inc_lab,2) }}</td> 
            <td align="right" class="text-primary">{{ number_format($row->lgo_inc_drug) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->lgo_inc_lab,2) }}</td> 
            <td align="right" class="text-primary">{{ number_format($row->fss_inc_drug) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->fss_inc_lab,2) }}</td>
            <td align="right" class="text-primary">{{ number_format($row->stp_inc_drug) }}</td> 
            <td align="right" class="text-success">{{ number_format($row->stp_inc_lab,2) }}</td> 
            <td align="right" class="text-primary">{{ number_format($row->pay_inc_drug) }}</td>   
            <td align="right" class="text-success">{{ number_format($row->pay_inc_lab,2) }}</td>              
        </tr>                
        <?php $count++; ?>
        <?php $sum_inc_drug += $row->inc_drug ; ?>
        <?php $sum_inc_lab += $row->inc_lab ; ?>
        <?php $sum_ucs_inc_drug += $row->ucs_inc_drug ; ?>
        <?php $sum_ucs_inc_lab += $row->ucs_inc_lab ; ?>
        <?php $sum_ofc_inc_drug += $row->ofc_inc_drug ; ?>
        <?php $sum_ofc_inc_lab += $row->ofc_inc_lab ; ?>
        <?php $sum_sss_inc_drug += $row->sss_inc_drug ; ?>
        <?php $sum_sss_inc_lab += $row->sss_inc_lab ; ?>
        <?php $sum_lgo_inc_drug += $row->lgo_inc_drug ; ?>
        <?php $sum_lgo_inc_lab += $row->lgo_inc_lab ; ?>
        <?php $sum_fss_inc_drug += $row->fss_inc_drug ; ?>
        <?php $sum_fss_inc_lab += $row->fss_inc_lab ; ?>
        <?php $sum_stp_inc_drug += $row->stp_inc_drug ; ?>
        <?php $sum_stp_inc_lab += $row->stp_inc_lab ; ?>
        <?php $sum_pay_inc_drug += $row->pay_inc_drug ; ?>
        <?php $sum_pay_inc_lab += $row->pay_inc_lab ; ?>
        @endforeach     
        <tr>
            <td align="right"><strong>รวม</strong></td>
            <td align="right" class="text-primary"><strong>{{number_format($sum_inc_drug,2)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_inc_lab,2)}}</strong></td>
            <td align="right" class="text-primary"><strong>{{number_format($sum_ucs_inc_drug,2)}}</strong></td>     
            <td align="right" class="text-success"><strong>{{number_format($sum_ucs_inc_lab,2)}}</strong></td>   
            <td align="right" class="text-primary"><strong>{{number_format($sum_ofc_inc_drug,2)}}</strong></td>  
            <td align="right" class="text-success"><strong>{{number_format($sum_ofc_inc_lab,2)}}</strong></td> 
            <td align="right" class="text-primary"><strong>{{number_format($sum_sss_inc_drug,2)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_sss_inc_lab,2)}}</strong></td>
            <td align="right" class="text-primary"><strong>{{number_format($sum_lgo_inc_drug,2)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_lgo_inc_lab,2)}}</strong></td>
            <td align="right" class="text-primary"><strong>{{number_format($sum_fss_inc_drug,2)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_fss_inc_lab,2)}}</strong></td>
            <td align="right" class="text-primary"><strong>{{number_format($sum_stp_inc_drug,2)}}</strong></td>
            <td align="right" class="text-success"><strong>{{number_format($sum_stp_inc_lab,2)}}</strong></td>
            <td align="right" class="text-primary"><strong>{{number_format($sum_pay_inc_drug,2)}}</strong></td>   
            <td align="right" class="text-success"><strong>{{number_format($sum_pay_inc_lab,2)}}</strong></td>                
        </tr>   
      </table>  
    </div>         
  </div>
</div>     
<br>

@endsection

@push('scripts')
  <script>
    $(document).ready(function () {
      $('#visit_pttype').DataTable({
        dom: '<"d-flex justify-content-end align-items-center gap-2 mb-3"fB>' +
             'rt',
        buttons: [
          {
            extend: 'excelHtml5',
            text: 'Excel',
            className: 'btn btn-success',
            title: 'จำนวนผู้มารับบริการผู้ป่วยนอกแยกกลุ่มสิทธิหลัก (ครั้ง) ปีงบประมาณ {{$budget_year}} '
          }
        ],
        ordering: false,     // ❌ ปิดการกดเรียงหัวคอลัมน์
        paging: false,       // ❌ ปิดการแบ่งหน้า
        info: false,         // ❌ ไม่แสดง "แสดงกี่รายการ"
        lengthChange: false, // ❌ ไม่ให้เลือกจำนวนรายการ
        language: {
          search: "ค้นหา:",
        }
      });
    });
  </script>
@endpush


<!-- Vendor JS Files -->
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<!-- Bar Chart -->
<script>
  document.addEventListener("DOMContentLoaded", () => {
    // ฟังก์ชันสร้างกราฟ (reuse ได้)
    function createBarChart(selector, labels, datasets) {
      new Chart(document.querySelector(selector), {
        type: 'bar',
        data: {
          labels: labels,
          datasets: datasets
        },
        options: {
          plugins: {
            datalabels: {
              anchor: 'end',
              align: 'top',
              formatter: (value) => Number(value).toLocaleString(),
              font: {
                weight: 'bold'
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (value) => Number(value).toLocaleString()
              }
            }
          }
        },
        plugins: [ChartDataLabels]
      });
    }

    // ✅ กราฟแรก (#visit)
    createBarChart('#visit', <?php echo json_encode($month); ?>, [
      {
        label: 'ครั้ง',
        data: <?php echo json_encode($visit); ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgb(54, 162, 235)',
        borderWidth: 1
      },
      {
        label: 'คน',
        data: <?php echo json_encode($hn); ?>,
        backgroundColor: 'rgba(153, 102, 255, 0.2)',
        borderColor: 'rgb(153, 102, 255)',
        borderWidth: 1
      }
    ]);

    // ✅ กราฟสอง (#visit_oppp)
    createBarChart('#visit_oppp', <?php echo json_encode($month); ?>, [
      {
        label: 'OP',
        data: <?php echo json_encode($visit_op); ?>,
        backgroundColor: 'rgba(255, 159, 64, 0.2)',
        borderColor: 'rgb(255, 159, 64)',
        borderWidth: 1
      },
      {
        label: 'PP',
        data: <?php echo json_encode($visit_pp); ?>,
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderColor: 'rgb(75, 192, 192)',
        borderWidth: 1
      }
    ]);
  });
</script>
<!-- End Bar CHart -->

