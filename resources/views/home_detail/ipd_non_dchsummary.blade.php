<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('/images/favicon.ico') }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title >ผู้ป่วยรอสรุป Chart</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <!-- <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

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
</head>
<body>
<!-- row -->
<div class="container mt-4">
  <div class="card">
    <div class="card-header bg-white pt-3">
        <h5 class="card-title text-primary"><i class="bi bi-file-earmark-medical"></i> รายงานผู้ป่วยในรอดำเนินการ</h5>
        <ul class="nav nav-tabs card-header-tabs" id="ipdTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="diag-tab" data-bs-toggle="tab" data-bs-target="#diag" type="button" role="tab" aria-controls="diag" aria-selected="true">
                    รอสรุป Chart <span class="badge bg-danger">{{ count($non_diagtext_list) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="icd10-tab" data-bs-toggle="tab" data-bs-target="#icd10" type="button" role="tab" aria-controls="icd10" aria-selected="false">
                    รอบันทึก ICD10 <span class="badge bg-warning text-dark">{{ count($non_icd10_list) }}</span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">  
      <div class="row">        
        <div class="col-md-12"> 
          <div id="non_dchsummary_sum" class="mb-4" style="width: 100%; height: 400px"></div>
          
          <div class="tab-content" id="ipdTabContent">
            <!-- Tab รอสรุป Chart -->
            <div class="tab-pane fade show active" id="diag" role="tabpanel" aria-labelledby="diag-tab">
              <div style="overflow-x:auto;">
                <table class="table table-hover table-bordered">
                  <thead class="table-danger">
                    <tr>
                        <th class="text-center">ลำดับ</th>           
                        <th class="text-center">Ward</th>              
                        <th class="text-center">AN</th> 
                        <th class="text-center">แพทย์เจ้าของคนไข้</th>    
                        <th class="text-center">วันที่จำหน่าย</th>  
                        <th class="text-center">จำนวนวัน</th> 
                        <th class="text-center">สถานะ</th>      
                    </tr>
                  </thead> 
                  <tbody> 
                    @forelse($non_diagtext_list as $index => $row) 
                    <tr>
                      <td align="center">{{ $index + 1 }}</td>
                      <td align="left">{{$row->ward}}</td> 
                      <td align="center">{{$row->an}}</td> 
                      <td align="left">{{$row->owner_doctor_name}}</td> 
                      <td align="left">{{DateThai($row->dchdate)}}</td>
                      <td align="center" class="text-danger fw-bold">{{$row->dch_day}}</td> 
                      <td align="center"><span class="badge bg-danger">รอแพทย์สรุป Chart</span></td> 
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">ไม่พบข้อมูล</td></tr>
                    @endforelse                 
                  </tbody>
                </table>   
              </div>
            </div>

            <!-- Tab รอบันทึก ICD10 -->
            <div class="tab-pane fade" id="icd10" role="tabpanel" aria-labelledby="icd10-tab">
              <div style="overflow-x:auto;">
                <table class="table table-hover table-bordered">
                  <thead class="table-warning">
                    <tr>
                        <th class="text-center">ลำดับ</th>           
                        <th class="text-center">Ward</th>              
                        <th class="text-center">AN</th> 
                        <th class="text-center">แพทย์เจ้าของคนไข้</th>    
                        <th class="text-center">วันที่จำหน่าย</th>  
                        <th class="text-center">จำนวนวัน</th> 
                        <th class="text-center">สถานะ</th>      
                    </tr>
                  </thead> 
                  <tbody> 
                    @forelse($non_icd10_list as $index => $row) 
                    <tr>
                      <td align="center">{{ $index + 1 }}</td>
                      <td align="left">{{$row->ward}}</td> 
                      <td align="center">{{$row->an}}</td> 
                      <td align="left">{{$row->owner_doctor_name}}</td> 
                      <td align="left">{{DateThai($row->dchdate)}}</td>
                      <td align="center" class="text-warning fw-bold">{{$row->dch_day}}</td> 
                      <td align="center"><span class="badge bg-warning text-dark">รอลงรหัสวินิจฉัยโรค</span></td> 
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">ไม่พบข้อมูล</td></tr>
                    @endforelse                 
                  </tbody>
                </table>   
              </div>
            </div>
          </div>
        </div>  
      </div>             
    </div>      
  </div>          
</div>      
</body>
</html>
 <!-- Vendor JS Files -->
 <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
 <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
 <script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
 <!-- Bootstrap Bundle with Popper -->
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Bar Chart -->
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const options = {
      series: [
        {
          name: 'รอสรุป Chart',
          data: <?php echo json_encode($chart_data['non_diagtext']); ?>
        },
        {
          name: 'รอบันทึก ICD10',
          data: <?php echo json_encode($chart_data['non_icd10']); ?>
        }
      ],
      chart: {
        type: 'bar',
        height: 400,
        stacked: true,
      },
      colors: ['#dc3545', '#ffc107'],
      plotOptions: {
        bar: {
          horizontal: true,
          dataLabels: {
            total: {
              enabled: true,
              offsetX: 10,
              style: {
                fontSize: '13px',
                fontWeight: 900
              }
            }
          }
        },
      },
      stroke: {
        width: 1,
        colors: ['#fff']
      },
      xaxis: {
        categories: <?php echo json_encode($chart_data['doctors']); ?>,
        labels: {
          formatter: function (val) {
            return val
          }
        }
      },
      yaxis: {
        title: {
          text: undefined
        },
      },
      tooltip: {
        y: {
          formatter: function (val) {
            return val + " ราย"
          }
        }
      },
      fill: {
        opacity: 1
      },
      legend: {
        position: 'top',
        horizontalAlign: 'left',
        offsetX: 40
      }
    };

    const chart = new ApexCharts(document.querySelector("#non_dchsummary_sum"), options);
    chart.render();
  });
</script>
<!-- End Bar Chart -->