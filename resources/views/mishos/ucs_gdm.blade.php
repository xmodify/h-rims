@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                รายชื่อผู้รับบริการในกลุ่ม GDM
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกปีงบประมาณ</span>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="start_date" value="{{ $start_date }}">
                        <input type="hidden" name="end_date" value="{{ $end_date }}">
                        <select class="form-select" name="budget_year" style="width: 160px;">
                            @foreach ($budget_year_select as $row)
                              <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                {{ $row->LEAVE_YEAR_NAME }}
                              </option>
                            @endforeach
                        </select>
                        <button type="submit" onclick="fetchData()" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-graph-up me-1"></i> โหลดกราฟ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        <!-- Section 1: Chart -->
        <div class="px-4 pt-2 pb-0 border-bottom">
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                สถิติการรับบริการรายเดือน
            </h6>
            <div style="height: 300px; width: 100%;">
                <canvas id="sum_month"></canvas>
            </div>
        </div>

        <!-- Section 2: Tabs & Tables -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้รับบริการในกลุ่ม GDM
                    </h6>
                    <span class="text-muted small">
                        วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}
                    </span>
                </div>
                
                <div class="filter-group">
                    <form id="form_indiv" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                        @csrf            
                        <span class="fw-bold text-muted small text-nowrap me-2">เลือกวันที่รับบริการ</span>
                        <div class="input-group input-group-sm">
                            <input type="hidden" name="budget_year" value="{{ $budget_year }}">
                            <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                            <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                            
                            <input type="text" id="start_date_picker" class="form-control datepicker_th" value="{{ $start_date }}" style="width: 120px;" readonly>
                            <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>
                            <input type="text" id="end_date_picker" class="form-control datepicker_th" value="{{ $end_date }}" style="width: 120px;" readonly>
                            <button onclick="fetchData()" type="submit" class="btn btn-success px-3 shadow-sm">
                                <i class="bi bi-table me-1"></i> โหลด indiv
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> รายการรับบริการ
                    </button>
                </li>       
            </ul>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: Search -->
                <div class="tab-pane fade show active" id="search" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_search" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center" width="8%">วันที่รับบริการ</th>
                                    <th class="text-center">Queue</th>
                                    <th class="text-start" width="12%">ชื่อ-สกุล</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-start" width="15%">สิทธิการรักษา</th>
                                    <th class="text-center">PDX</th>
                                    <th class="text-center">ICD10 Claim</th>
                                    <th class="text-end">ค่ารักษาทั้งหมด</th> 
                                    <th class="text-end">ชำระเอง</th>
                                    <th class="text-end text-primary">เรียกเก็บ</th>
                                    <th class="text-end text-success">ชดเชย</th>
                                    <th class="text-end">ส่วนต่าง</th>
                                    <th class="text-start">รายการเรียกเก็บ</th>
                                    <th class="text-center">ส่ง Claim</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_claim_price = 0; 
                                    $sum_receive_total = 0;
                                @endphp
                                @foreach($search as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>   
                                    <td class="text-center small">
                                        {{ DateThai($row->vstdate) }}<br>
                                        <span class="text-muted" style="font-size: 0.75rem;">{{$row->vsttime}}</span>
                                    </td>
                                    <td class="text-center small">{{ $row->oqueue }}</td>   
                                    <td class="text-start text-dark fw-bold small">{{$row->ptname}}</td> 
                                    <td class="text-center small text-primary fw-bold">{{$row->hn}}</td> 
                                    <td class="text-start small text-muted">
                                        <div class="text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                        <div style="font-size: 0.7rem;">[{{$row->hospmain}}]</div>
                                    </td> 
                                    <td class="text-center small">{{ $row->pdx }}</td>
                                    <td class="text-center small fw-bold">{{ $row->icd10 }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td>
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    <td class="text-end small fw-bold {{ ($row->receive_total-$row->claim_price) > 0 ? 'text-success' : (($row->receive_total-$row->claim_price) < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($row->receive_total-$row->claim_price,2) }}
                                    </td>
                                    <td class="text-start small text-muted">{{$row->claim_list}}</td>
                                    <td class="text-center">
                                       <span class="badge {{ $row->claim ? 'bg-success-soft text-success' : 'bg-secondary-soft text-secondary' }}">
                                            {{$row->claim ?? 'None'}}
                                       </span>
                                    </td>
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_claim_price += $row->claim_price; 
                                    $sum_receive_total += $row->receive_total;
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="8" class="text-end small text-muted px-3">รวมทั้งหมด:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2)}}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2)}}</th>
                                    <th class="text-end small fw-bold text-primary">{{ number_format($sum_claim_price,2)}}</th>
                                    <th class="text-end small fw-bold {{ $sum_receive_total > 0 ? 'text-success' : ($sum_receive_total < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($sum_receive_total,2)}}
                                    </th>
                                    <th class="text-end small fw-bold {{ ($sum_receive_total-$sum_claim_price) > 0 ? 'text-success' : (($sum_receive_total-$sum_claim_price) < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($sum_receive_total-$sum_claim_price,2)}}
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div> 
            </div>
        </div>
    </div>
</div>      

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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

  $(document).ready(function () {

      // Initialize Datepicker Thai
      $('.datepicker_th').datepicker({
          format: 'd M yyyy', // Matches DateThai() helper output
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th',
          thaiyear: true,
          zIndexOffset: 1050
      });

      // Set initial values (ensures calendar is synced)
      var start_date_val = "{{ $start_date }}";
      var end_date_val = "{{ $end_date }}";
      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
      }

      // Sync Changes to Hidden Inputs for Backend (YYYY-MM-DD)
      $('.datepicker_th').on('changeDate', function(e) {
          var date = e.date;
          var targetId = $(this).attr('id').replace('_picker', '');
          var hiddenInput = $('#' + targetId);
          
          if(date) {
              var day = ("0" + date.getDate()).slice(-2);
              var month = ("0" + (date.getMonth() + 1)).slice(-2);
              var year = date.getFullYear(); // Gregorian
              hiddenInput.val(year + "-" + month + "-" + day);
          } else {
              hiddenInput.val('');
          }
      });
      $('#t_search').DataTable({
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + // Show รายการ
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + // Search + Export
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + // Info
                '<"col-md-6 d-flex justify-content-end"p>' + // Pagination
              '>',
        buttons: [
            {
              extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
              className: 'btn btn-success btn-sm shadow-sm',
              title: 'รายชื่อผู้รับบริการในกลุ่ม GDM วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

      // Chart.js
      new Chart(document.querySelector('#sum_month'), {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($month); ?>,
        datasets: [
          {
            label: 'เรียกเก็บ',
            data: <?php echo json_encode($claim_price); ?>,
            backgroundColor: 'rgba(249, 115, 22, 0.6)',
            borderColor: 'rgb(249, 115, 22)',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'ชดเชย',
            data: <?php echo json_encode($receive_total); ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.6)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 1,
            borderRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            align: 'center',
            labels: {
              usePointStyle: true,
              boxWidth: 6
            }
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
            align: 'top',
            color: '#000',
            font: {
              weight: 'bold',
              size: 10
            },
            formatter: (value) => value.toLocaleString()
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grace: '20%',
            ticks: {
              callback: function(value) {
                return value.toLocaleString();
              }
            }
          }
        }
      },
      plugins: [ChartDataLabels]
    });
  });
</script>
@endpush
