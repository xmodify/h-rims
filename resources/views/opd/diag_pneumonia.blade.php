@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Filter -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-lungs-fill text-danger me-2"></i>
                รายชื่อผู้ป่วยนอกโรค Pneumonia
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณ {{ $budget_year }}</div>
        </div>
        
        <div class="d-flex align-items-center">
            <form method="POST" enctype="multipart/form-data" class="m-0">
                @csrf
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-check"></i></span>
                    <select class="form-select border-start-0" name="budget_year" style="min-width: 150px;">
                        @foreach ($budget_year_select as $row)
                            <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                ปีงบประมาณ {{ $row->LEAVE_YEAR_NAME }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary px-3">
                        <i class="bi bi-search me-1"></i> {{ __('ค้นหา') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Chart Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card dash-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-bar-chart-line text-info me-2"></i>
                        สถิติรายเดือน (ครั้ง/คน/Admit/Refer)
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="diag_month" style="width: 100%; height: 350px"></canvas>             
                </div>      
            </div>      
        </div>   
        <div class="col-lg-5">
            <div class="card dash-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-graph-up text-warning me-2"></i>
                        แนวโน้ม 5 ปีงบประมาณย้อนหลัง
                    </h6>
                </div>
                <div class="card-body">
                    <div id="diag_year" style="width: 100%; height: 350px"></div>             
                </div>      
            </div>      
        </div>      
    </div>

    <!-- Patient List Card -->
    <div class="card dash-card border-top-0">
        <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-person-lines-fill text-primary me-2"></i>
                รายชื่อผู้ป่วยนอกโรค Pneumonia ปีงบประมาณ {{ $budget_year }}
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">            
                <table id="diag_list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">ลำดับ</th>
                            <th class="text-center">วัน-เวลาที่รับบริการ</th>     
                            <th class="text-center">HN</th>
                            <th class="text-center">ชื่อ-สกุล | อายุ</th>
                            <th class="text-center">สิทธิ์การรักษา</th>
                            <th class="text-center" width="20%">อาการสำคัญ</th>
                            <th class="text-center">PDX | DX</th> 
                            <th class="text-center">Admit/Refer</th>  
                            <th class="text-center">ยา/Lab</th>          
                        </tr>     
                    </thead> 
                    <tbody> 
                        @php $count = 1 ; @endphp
                        @foreach($diag_list as $row)          
                        <tr>
                            <td class="text-center text-muted small">{{ $count }}</td> 
                            <td class="text-start">
                                <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">เวลา {{ $row->vsttime }} | Q: {{ $row->oqueue }}</div>
                            </td> 
                            <td class="text-center">
                                <span class="fw-bold text-primary">{{ $row->hn }}</span>
                            </td> 
                            <td class="text-start">
                                <div class="text-dark fw-bold">{{ $row->ptname }}</div>
                                <div class="badge bg-info-soft text-info" style="font-size: 0.7rem;">อายุ {{ $row->age_y }} ปี</div>
                            </td>
                            <td class="text-start">
                                <div class="small text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</div>
                            </td>
                            <td class="text-start">
                                <div class="small text-muted lh-1" style="font-size: 0.75rem;">{{ $row->cc }}</div>
                            </td>
                            <td class="text-start">
                                <div class="badge bg-danger-soft text-danger mb-1">{{ $row->pdx }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 150px;">{{ $row->dx }}</div>
                            </td>
                            <td class="text-center">
                                @if($row->admit == 'Y') <span class="badge bg-warning text-dark me-1">Admit</span> @endif
                                @if($row->refer == 'Y') <span class="badge bg-danger text-white">Refer</span> @endif
                                @if($row->admit != 'Y' && $row->refer != 'Y') - @endif
                            </td>      
                            <td class="text-end">
                                <div class="small text-primary">ยา: {{ number_format($row->inc_drug,2) }}</div>
                                <div class="small text-success">Lab: {{ number_format($row->inc_lab,2) }}</div>
                            </td>                 
                        </tr>                
                        @php $count++; @endphp
                        @endforeach                
                    </tbody>
                </table>  
            </div>         
        </div> 
    </div>  
</div>
<br>

@endsection

@push('scripts')
  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
  
  <script>
    $(document).ready(function () {
      $('#diag_list').DataTable({
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + 
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + 
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + 
                '<"col-md-6"p>' + 
              '>',
        buttons: [
            {
              extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
              className: 'btn btn-success btn-sm',
              title: 'รายชื่อผู้ป่วยนอกโรค Pneumonia ปีงบประมาณ {{$budget_year}}'
            }
        ],
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
        }
      });
    });

    document.addEventListener("DOMContentLoaded", () => {
      // Monthly Bar Chart
      new Chart(document.querySelector('#diag_month'), {
        type: 'bar',
        data: {
          labels: @json($diag_m),
          datasets: [
            {
              label: 'ครั้ง (Visits)',
              data: @json($diag_visit_m),
              backgroundColor: 'rgba(54, 162, 235, 0.6)',
              borderColor: 'rgb(54, 162, 235)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'คน (Patients)',
              data: @json($diag_hn_m),
              backgroundColor: 'rgba(153, 102, 255, 0.6)',
              borderColor: 'rgb(153, 102, 255)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'Admit',
              data: @json($diag_admit_m),
              backgroundColor: 'rgba(255, 205, 86, 0.6)',
              borderColor: 'rgb(255, 205, 86)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'Refer',
              data: @json($diag_refer_m),
              backgroundColor: 'rgba(255, 99, 132, 0.6)',
              borderColor: 'rgb(255, 99, 132)',
              borderWidth: 1,
              borderRadius: 4
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } }
          },
          scales: {
            y: { beginAtZero: true, ticks: { callback: (v) => v.toLocaleString() } }
          }
        }
      });

      // Yearly Area Chart
      new ApexCharts(document.querySelector("#diag_year"), {
          series: [
            { name: 'ครั้ง', data: @json($diag_visit_y) },
            { name: 'คน', data: @json($diag_hn_y) },
            { name: 'Admit', data: @json($diag_admit_y) },
            { name: 'Refer', data: @json($diag_refer_y) }
          ],
          chart: {
              height: 350,
              type: 'area',
              toolbar: { show: false },
          },
          markers: { size: 4 },
          colors: [ '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444' ],
          fill: {
              type: "gradient",
              gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100]
              }
          },
          dataLabels: { enabled: false },
          stroke: { curve: 'smooth', width: 3 },
          xaxis: {
              categories: @json($diag_y),
              labels: { style: { fontSize: '12px' } }
          }
      }).render();
    });
  </script>  
@endpush

