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
</div>  
<!-- row -->
<div class="container-fluid">
  <div class="row justify-content-center">  
    <div class="col-md-7">
      <div class="card border-info">
        <div class="card-header text-success"><strong>จำนวนผู้ป่วยนอกโรค Stemi ปีงบประมาณ {{$budget_year}} </strong></div>
        <canvas id="diag_month" style="width: 100%; height: 350px"></canvas>             
      </div>      
    </div>   
    <div class="col-md-5">
      <div class="card border-info">
        <div class="card-header text-success"><strong>จำนวนผู้ป่วยนอกโรค Stemi 5 ปีงบประมาณย้อนหลัง </strong></div>
        <div id="diag_year" style="width: 100%; height: 350px"></div>             
      </div>      
    </div>      
  </div>
</div> 
<br>
<!-- row -->
<div class="container-fluid">   
  <div class="card border-info">
    <div class="card-header bg-success bg-opacity-75 text-white">รายชื่อผู้ป่วยนอกโรค Stemi ปีงบประมาณ {{$budget_year}} </div>
    <div class="card-body" style="overflow-x:auto;">
      <table id="diag_list" class="table table-bordered table-striped my-3">
        <thead>
        <tr class="table-primary">
            <th class="text-center">ลำดับ</th>
            <th class="text-center">วัน-เวลาที่มารับบริการ</th>     
            <th class="text-center">Queue</th>  
            <th class="text-center">HN</th>
            <th class="text-center">ชื่อ-สกุล</th>
            <th class="text-center">อายุ</th>
            <th class="text-center">สิทธิ</th>
            <th class="text-center">อาการสำคัญ</th>
            <th class="text-center">โรคหลัก</th> 
            <th class="text-center">โรคร่วม</th>
            <th class="text-center">หัถการ</th>
            <th class="text-center">แพทย์ผู้ตรวจ</th>
            <th class="text-center">Admit</th>
            <th class="text-center">Refer</th>  
            <th class="text-center">มูลค่ายา</th>
            <th class="text-center">มูลค่า Lab</th>          
        </tr>     
        </thead> 
        <?php $count = 1 ; ?> 
        @foreach($diag_list as $row)          
        <tr>
            <td align="center">{{ $count }}</td> 
            <td align="right">{{ DateThai($row->vstdate) }} เวลา {{ $row->vsttime }}</td> 
            <td align="center">{{ $row->oqueue }}</td> 
            <td align="center">{{ $row->hn }}</td>
            <td align="left">{{ $row->ptname }}</td>
            <td align="left">{{ $row->age_y }}</td>
            <td align="left">{{ $row->pttype }}</td>
            <td align="left">{{ $row->cc }}</td>
            <td align="right">{{ $row->pdx }}</td>
            <td align="right">{{ $row->dx }}</td>
            <td align="right">{{ $row->icd9 }}</td>
            <td align="left">{{ $row->dx_doctor }}</td>              
            <td align="left">{{ $row->admit }}</td>      
            <td align="left">{{ $row->refer }}</td>    
            <td align="left">{{ $row->inc_drug }}</td>  
            <td align="left">{{ $row->inc_lab }}</td>                 
        </tr>                
        <?php $count++; ?>
        @endforeach                
      </table>  
    </div>         
  </div>
</div>     
<br>

@endsection


@push('scripts')
  <script>
    $(document).ready(function () {
      $('#diag_list').DataTable({
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
              title: 'รายชื่อผู้ป่วยนอกโรค Sepsis ปีงบประมาณ {{$budget_year}}'
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

<!-- Vendor JS Files -->
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
<!-- Bar Chart -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
      new Chart(document.querySelector('#diag_month'), {
        type: 'bar',
        data: {
          labels: <?php echo json_encode($diag_m); ?>,
          datasets: [{
            label: 'ครั้ง',
            data: <?php echo json_encode($diag_visit_m); ?>,
            backgroundColor: [
              'rgba(54, 162, 235, 0.2)'
            ],
            borderColor: [
              'rgb(54, 162, 235)'
            ],
            borderWidth: 1
          },{
            label: 'คน',
            data: <?php echo json_encode($diag_hn_m); ?>,
            backgroundColor: [
              'rgba(153, 102, 255, 0.2)'
            ],
            borderColor: [
              'rgb(153, 102, 255)'
            ],
            borderWidth: 1
          },{
            label: 'Admit',
            data: <?php echo json_encode($diag_admit_m); ?>,
            backgroundColor: [
              'rgba(255, 205, 86, 0.2)',
            ],
            borderColor: [
              'rgb(255, 205, 86)',
            ],
            borderWidth: 1
          },{
            label: 'Refer',
            data: <?php echo json_encode($diag_refer_m); ?>,
            backgroundColor: [
             'rgba(255, 99, 132, 0.2)',
            ],
            borderColor: [
              'rgb(255, 99, 132)',
            ],
            borderWidth: 1
          }]
        },
        
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    });
  </script>
<!-- End Bar CHart -->
<!-- Line Chart -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        new ApexCharts(document.querySelector("#diag_year"), {
            
            series: [{
                name: 'ครั้ง',
                data: <?php echo json_encode($diag_visit_y); ?>,
                    },
                    {
                name: 'คน',
                data: <?php echo json_encode($diag_hn_y); ?>,
                    },
                    {
                name: 'Admit',
                data: <?php echo json_encode($diag_admit_y); ?>,
                    },
                    {
                name: 'Refer',
                data: <?php echo json_encode($diag_refer_y); ?>,
                    }],
          
            chart: {
                height: 300,
                type: 'area',
                toolbar: {
                show: false
                },
            },
            markers: {
                size: 4
            },
            colors: [ '#0099FF','#ab47bc','#FF9900','#ec407a',],
            fill: {
                type: "gradient",
                gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.3,
                opacityTo: 0.4,
                stops: [0, 90, 100]
                }
            },
            dataLabels: {
                enabled: true
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            xaxis: {
                type: 'text',
                categories: <?php echo json_encode($diag_y); ?>,
            }
            }).render();
        });
</script>
<!-- End Line Chart -->

