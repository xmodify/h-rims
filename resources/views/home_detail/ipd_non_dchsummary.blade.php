@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-lg-4">
  <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white pt-3 pb-0 border-0">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
          <h5 class="card-title text-primary fw-bold mb-0">
            <i class="bi bi-file-earmark-medical me-2"></i> รายงานผู้ป่วยในรอดำเนินการ (Chart รอแพทย์สรุป & บันทึก ICD10)
          </h5>
          <small class="text-muted d-block mt-1" style="font-size: 0.8rem;">
            ข้อมูลผู้ป่วยในจำหน่ายแล้วแต่ยังรอดำเนินการสรุปเวชระเบียน
          </small>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button type="button" onclick="location.reload()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
            <i class="bi bi-arrow-clockwise me-1"></i> รีเฟรช
          </button>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <ul class="nav nav-tabs card-header-tabs" id="ipdTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active fw-semibold" id="diag-tab" data-bs-toggle="tab" data-bs-target="#diag" type="button" role="tab" aria-controls="diag" aria-selected="true">
            <i class="bi bi-hourglass-split me-1 text-danger"></i> รอแพทย์สรุป Chart 
            <span class="badge bg-danger rounded-pill ms-1">{{ count($non_diagtext_list) }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-semibold" id="icd10-tab" data-bs-toggle="tab" data-bs-target="#icd10" type="button" role="tab" aria-controls="icd10" aria-selected="false">
            <i class="bi bi-code-square me-1 text-warning"></i> รอบันทึก ICD10 
            <span class="badge bg-warning text-dark rounded-pill ms-1">{{ count($non_icd10_list) }}</span>
          </button>
        </li>
      </ul>
    </div>

    <div class="card-body">  
      <!-- Stacked Bar Chart Overview -->
      <div class="card border-0 bg-light p-3 mb-4 rounded-3 shadow-none">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold text-dark small"><i class="bi bi-bar-chart-fill me-1 text-primary"></i> สรุปจำนวน Chart รอดำเนินการแยกตามแพทย์</span>
        </div>
        <div id="non_dchsummary_sum" style="width: 100%; min-height: 380px;"></div>
      </div>
      
      <!-- Tab Content (DataTables) -->
      <div class="tab-content" id="ipdTabContent">
        
        <!-- Tab 1: รอสรุป Chart -->
        <div class="tab-pane fade show active" id="diag" role="tabpanel" aria-labelledby="diag-tab">
          <div class="table-responsive">
            <table id="table_diag" class="table table-hover table-bordered align-middle w-100" style="font-size: 0.85rem;">
              <thead class="table-danger">
                <tr>
                  <th class="text-center" style="width: 55px;">ลำดับ</th>           
                  <th class="text-start">Ward</th>              
                  <th class="text-center" style="width: 100px;">AN</th> 
                  <th class="text-start">แพทย์เจ้าของคนไข้</th>    
                  <th class="text-center" style="width: 110px;">วันที่จำหน่าย</th>  
                  <th class="text-center" style="width: 80px;">จำนวนวัน</th> 
                  <th class="text-center" style="width: 140px;">สถานะ</th>      
                </tr>
              </thead> 
              <tbody> 
                @foreach($non_diagtext_list as $index => $row) 
                <tr>
                  <td class="text-center text-muted">{{ $index + 1 }}</td>
                  <td class="text-start fw-medium">{{ $row->ward }}</td> 
                  <td class="text-center fw-bold text-primary">{{ $row->an }}</td> 
                  <td class="text-start">{{ $row->owner_doctor_name }}</td> 
                  <td class="text-center">{{ DateThai($row->dchdate) }}</td>
                  <td class="text-center text-danger fw-bold">{{ $row->dch_day }}</td> 
                  <td class="text-center">
                    <span class="badge bg-danger rounded-pill px-2.5 py-1">รอแพทย์สรุป Chart</span>
                  </td> 
                </tr>
                @endforeach                 
              </tbody>
            </table>   
          </div>
        </div>

        <!-- Tab 2: รอบันทึก ICD10 -->
        <div class="tab-pane fade" id="icd10" role="tabpanel" aria-labelledby="icd10-tab">
          <div class="table-responsive">
            <table id="table_icd10" class="table table-hover table-bordered align-middle w-100" style="font-size: 0.85rem;">
              <thead class="table-warning">
                <tr>
                  <th class="text-center" style="width: 55px;">ลำดับ</th>           
                  <th class="text-start">Ward</th>              
                  <th class="text-center" style="width: 100px;">AN</th> 
                  <th class="text-start">แพทย์เจ้าของคนไข้</th>    
                  <th class="text-center" style="width: 110px;">วันที่จำหน่าย</th>  
                  <th class="text-center" style="width: 80px;">จำนวนวัน</th> 
                  <th class="text-center" style="width: 140px;">สถานะ</th>      
                </tr>
              </thead> 
              <tbody> 
                @foreach($non_icd10_list as $index => $row) 
                <tr>
                  <td class="text-center text-muted">{{ $index + 1 }}</td>
                  <td class="text-start fw-medium">{{ $row->ward }}</td> 
                  <td class="text-center fw-bold text-primary">{{ $row->an }}</td> 
                  <td class="text-start">{{ $row->owner_doctor_name }}</td> 
                  <td class="text-center">{{ DateThai($row->dchdate) }}</td>
                  <td class="text-center text-warning fw-bold">{{ $row->dch_day }}</td> 
                  <td class="text-center">
                    <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1">รอบันทึก ICD10</span>
                  </td> 
                </tr>
                @endforeach                 
              </tbody>
            </table>   
          </div>
        </div>

      </div>
    </div>      
  </div>          
</div>      
@endsection

@push('scripts')
  <!-- Vendor ApexCharts -->
  <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>

  <script>
    $(document).ready(function () {
      const dtConfig = {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + 
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + 
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + 
                '<"col-md-6"p>' + 
              '>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "ค้นหาข้อมูล...",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            infoEmpty: "ไม่พบรายการ",
            zeroRecords: "ไม่พบข้อมูลที่ตรงกับคำค้นหา",
            paginate: {
              previous: '<i class="bi bi-chevron-left"></i>',
              next: '<i class="bi bi-chevron-right"></i>'
            }
        }
      };

      $('#table_diag').DataTable({
        ...dtConfig,
        order: [[5, 'desc']], // เรียงตามจำนวนวันมากไปน้อย
        buttons: [
          {
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel (รอสรุป Chart)',
            className: 'btn btn-danger btn-sm shadow-sm rounded-pill px-3',
            title: 'IPD_Pending_Chart_Summary_{{ date("Y-m-d") }}'
          }
        ]
      });

      $('#table_icd10').DataTable({
        ...dtConfig,
        order: [[5, 'desc']], // เรียงตามจำนวนวันมากไปน้อย
        buttons: [
          {
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel (รอบันทึก ICD10)',
            className: 'btn btn-warning btn-sm shadow-sm rounded-pill px-3 text-dark',
            title: 'IPD_Pending_ICD10_{{ date("Y-m-d") }}'
          }
        ]
      });

      // ปรับขนาดคอลัมน์ DataTable อัตโนมัติเมื่อสลับแท็บ
      $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
      });

      // ApexCharts Stacked Bar
      const options = {
        series: [
          {
            name: 'รอสรุป Chart',
            data: @json($chart_data['non_diagtext'])
          },
          {
            name: 'รอบันทึก ICD10',
            data: @json($chart_data['non_icd10'])
          }
        ],
        chart: {
          type: 'bar',
          height: 380,
          stacked: true,
          toolbar: { show: false }
        },
        colors: ['#dc3545', '#ffc107'],
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 4,
            dataLabels: {
              total: {
                enabled: true,
                offsetX: 10,
                style: {
                  fontSize: '12px',
                  fontWeight: 800
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
          categories: @json($chart_data['doctors']),
          labels: {
            formatter: function (val) {
              return val;
            }
          }
        },
        yaxis: {
          labels: {
            style: {
              fontSize: '12px',
              fontWeight: 600
            }
          }
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + " ราย";
            }
          }
        },
        fill: {
          opacity: 1
        },
        legend: {
          position: 'top',
          horizontalAlign: 'left',
          offsetX: 20
        }
      };

      const chart = new ApexCharts(document.querySelector("#non_dchsummary_sum"), options);
      chart.render();
    });
  </script>
@endpush