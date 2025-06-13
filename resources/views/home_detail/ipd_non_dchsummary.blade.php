<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
<div class="container">
  <div class="card">
    <div class="card-body">  
      <div class="row">        
        <div class="col-md-12"> 
          <div style="overflow-x:auto;">
            <div id="non_dchsummary_sum" style="width: 100%; height: 350px"></div>
            <table class="table table-striped table-bordered">
              <thead>
                <tr class="table-success">
                  <th class="text-center" colspan="10">ผู้ป่วยในสถานะจำหน่าย รอสรุป Chart</th>    
                </tr>
                <tr class="table-primary">
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
                <?php $count = 1 ; ?>
                @foreach($non_dchsummary as $row) 
                <tr>
                  <td align="center">{{ $count }}</td>
                  <td align="left">{{$row->ward}}</td> 
                  <td align="center">{{$row->an}}</td> 
                  <td align="left">{{$row->owner_doctor_name}}</td> 
                  <td align="left">{{DateThai($row->dchdate)}}</td>
                  <td align="center">{{$row->dch_day}}</td> 
                  <td align="center">{{$row->diag_status}}</td> 
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
</div>      
</body>
</html>
 <!-- Vendor JS Files -->
 <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
 <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
 <script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
<!-- Bar Chart -->
<script>
  document.addEventListener("DOMContentLoaded", () => {
    new ApexCharts(document.querySelector("#non_dchsummary_sum"), {
      series: [{
        data: <?php echo json_encode($owner_doctor_total); ?>
      }],
      chart: {
        type: 'bar',
        height: 350
      },
      plotOptions: {
        bar: {
          borderRadius: 4,
          horizontal: true,
        }
      },
      dataLabels: {
        enabled: true
      },
      xaxis: {
        categories: <?php echo json_encode($owner_doctor_name); ?>,
      }
    }).render();
  });
</script>
<!-- End Bar Chart -->