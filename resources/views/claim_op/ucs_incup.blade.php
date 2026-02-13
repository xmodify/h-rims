@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                Claim Dashboard
            </h4>
            <div class="text-muted small mt-1">
                รายชื่อผู้มารับบริการ UC-OP ใน CUP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0">
                    @csrf
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="start_date" value="{{ $start_date }}">
                        <input type="hidden" name="end_date" value="{{ $end_date }}">
                        <span class="input-group-text bg-light text-muted fw-bold">1. ชุดข้อมูลกราฟ</span>
                        <select class="form-select" name="budget_year" style="width: 160px;">
                            @foreach ($budget_year_select as $row)
                              <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                {{ $row->LEAVE_YEAR_NAME }}
                              </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-graph-up me-1"></i> โหลดกราฟ
                        </button>
                    </div>
                </form>
            </div>

            <div class="vr text-muted opacity-25" style="height: 30px;"></div>

            <!-- Filter Section 2: Table Data (Date Range) -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0">
                    @csrf            
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="budget_year" value="{{ $budget_year }}">
                        <span class="input-group-text bg-light text-muted fw-bold">2. ชุดข้อมูล indiv</span>
                        <input type="date" name="start_date" class="form-control" value="{{ $start_date }}" style="width: 130px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>
                        <input type="date" name="end_date" class="form-control" value="{{ $end_date }}" style="width: 130px;">
                        <button onclick="fetchData()" type="submit" class="btn btn-success px-3 shadow-sm">
                            <i class="bi bi-table me-1"></i> โหลด indiv
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
                สถิติการเรียกเก็บและชดเชยรายเดือน (ปีงบประมาณ {{ $budget_year }})
            </h6>
            <div style="height: 300px; width: 100%;">
                <canvas id="sum_month"></canvas>
            </div>
        </div>

        <!-- Section 2: Tabs & Tables -->
        <div class="card-header bg-transparent border-0 pt-2 px-4 pb-0">
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> รอส่ง Claim
                    </button>
                </li>       
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab">
                        <i class="bi bi-send-check me-1"></i> ส่ง Claim แล้ว
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: Waiting for Claim -->
                <div class="tab-pane fade show active" id="search" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_search" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th> 
                                    <th class="text-center">Action</th> 
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">วัน-เวลา | Q</th>     
                                    <th class="text-center">HN</th>    
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="15%">อาการสำคัญ</th>
                                    <th class="text-center">PDX | ICD9</th>
                                    <th class="text-center">ค่ารักษา</th> 
                                    <th class="text-center">ชำระเอง</th>
                                    <th class="text-center text-primary">เรียกเก็บ</th> 
                                    <th class="text-center">Project</th>  
                                    <th class="text-center" width="15%">รายการเรียกเก็บ</th>  
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_claim_price = 0; 
                                @endphp
                                @foreach($search as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-success px-2 py-0 border-2 fw-bold" style="font-size: 0.7rem;" onclick="checkFdh('{{ $row->hn }}','{{ $row->seq }}')">FDH</button>
                                    </td>    
                                    <td class="text-start small">
                                        <div class="d-flex flex-column gap-1">
                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                <span class="text-muted" style="font-size: 0.65rem;">Authen:</span>
                                                <span class="badge {{ $row->auth_code == 'Y' ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }} py-0 px-1">{{ $row->auth_code }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                <span class="text-muted" style="font-size: 0.65rem;">Endpoint:</span>
                                                <span class="badge {{ $row->endpoint == 'Y' ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }} py-0 px-1">{{ $row->endpoint }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                <span class="text-muted" style="font-size: 0.65rem;">พร้อมส่ง:</span>
                                                <span class="badge {{ $row->confirm_and_locked == 'Y' ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }} py-0 px-1">{{ $row->confirm_and_locked }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                    </td> 
                                    <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td> 
                                    <td class="text-center small text-muted">{{ $row->project }}</td>                   
                                    <td class="text-start small text-muted text-wrap">{{ $row->claim_list }}</td>   
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_claim_price += $row->claim_price; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                    <th colspan="8" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end fw-bold text-primary">{{ number_format($sum_claim_price,2) }}</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div>  
                <!-- Tab 2: Claims Sent -->
                <div class="tab-pane fade" id="claim" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_claim" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" rowspan="2">#</th>  
                                    <th class="text-center" rowspan="2">Action</th>
                                    <th class="text-center" rowspan="2">FDH Status</th>                     
                                    <th class="text-center" rowspan="2">วัน-เวลา | Q</th>     
                                    <th class="text-center" rowspan="2">HN</th> 
                                    <th class="text-center" rowspan="2">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" rowspan="2" width="10%">อาการสำคัญ</th>
                                    <th class="text-center" rowspan="2">PDX | ICD9</th>
                                    <th class="text-center" colspan="3">ค่ารักษา</th> 
                                    <th class="text-center" colspan="3">รายการเรียกเก็บพิเศษ</th>
                                    <th class="text-center bg-primary-soft" colspan="5">ข้อมูลการชดเชย (NHSO)</th>
                                </tr>
                                <tr>
                                    <th class="text-center small">รวม</th>
                                    <th class="text-center small">ชำระเอง</th>
                                    <th class="text-center small" width="10%">รายการ</th>
                                    
                                    <th class="text-center small">บริการเฉพาะ</th>
                                    <th class="text-center small">PPFS</th>
                                    <th class="text-center small">สมุนไพร</th>

                                    <th class="text-center bg-primary-soft small">Rep NHSO</th> 
                                    <th class="text-center bg-primary-soft small text-danger">Error</th> 
                                    <th class="text-center bg-primary-soft small">STM ชดเชย</th> 
                                    <th class="text-center bg-primary-soft small">ผลต่าง</th> 
                                    <th class="text-center bg-primary-soft small">REP No.</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_uc_cr = 0; 
                                    $sum_ppfs = 0; 
                                    $sum_herb = 0; 
                                    $sum_rep_nhso = 0; 
                                    $sum_receive_total = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-success px-2 py-0 border-2 fw-bold" style="font-size: 0.7rem;" onclick="checkFdh('{{ $row->hn }}','{{ $row->seq }}')">FDH</button>
                                    </td>    
                                    <td class="text-start small text-muted text-truncate" style="max-width: 120px;" title="{{ $row->fdh_status }}">{{ $row->fdh_status }}</td>                    
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                    </td> 
                                    <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-start small text-muted text-wrap">{{ $row->claim_list }}</td>  
                                    <td class="text-end small">{{ number_format($row->uc_cr,2) }}</td> 
                                    <td class="text-end small">{{ number_format($row->ppfs,2) }}</td> 
                                    <td class="text-end small">{{ number_format($row->herb,2) }}</td> 
                                    <td class="text-end small fw-bold text-primary">{{ number_format($row->rep_nhso,2) }}</td>
                                    <td class="text-center px-1">
                                        @if($row->rep_error)
                                            <span class="badge bg-danger-soft text-danger" style="font-size: 0.6rem;">{{ $row->rep_error }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    @php $diff = $row->receive_total - $row->uc_cr - $row->ppfs - $row->herb; @endphp
                                    <td class="text-end small fw-bold {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($diff, 2) }}
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->repno }}</td>
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_uc_cr += $row->uc_cr; 
                                    $sum_ppfs += $row->ppfs; 
                                    $sum_herb += $row->herb; 
                                    $sum_rep_nhso += $row->rep_nhso; 
                                    $sum_receive_total += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="8" class="text-end text-muted small px-3">รวมงบประมาณที่ส่งเบิก:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th></th>
                                    <th class="text-end small">{{ number_format($sum_uc_cr,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_ppfs,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_herb,2) }}</th>
                                    <th class="text-end small text-primary">{{ number_format($sum_rep_nhso,2) }}</th>
                                    <th></th>
                                    <th class="text-end small fw-bold {{ $sum_receive_total > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sum_receive_total,2) }}</th>
                                    @php $total_diff = $sum_receive_total - $sum_uc_cr - $sum_ppfs - $sum_herb; @endphp
                                    <th class="text-end small fw-bold {{ $total_diff > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($total_diff, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
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

{{-- ✅ FDH Check Claim ------------------------------------------------------------ --}}
<script>
  function checkFdh(hn, seq) {

      Swal.fire({
          title: 'กำลังตรวจสอบสถานะ...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
      });

      $.ajax({
          url: "{{ url('/api/fdh/check-claim-indiv') }}",
          type: "POST",
          data: {
              hn: hn,
              seq: seq,
              _token: "{{ csrf_token() }}"
          },
          success: function (res) {

              // ------------------------------
              // ✔ FDH ตอบสำเร็จ (200)
              // ------------------------------
              if (res.status === 200) {
                  Swal.fire({
                      icon: 'success',
                      title: 'ตรวจสอบสำเร็จ',
                      text: 'พบข้อมูลในระบบ FDH',
                      timer: 1500,
                      showConfirmButton: false
                  }).then(() => location.reload());
                  return;
              }

              // ------------------------------
              // ✔ ไม่พบข้อมูล FDH (404)
              // ------------------------------
              if (res.status === 404 || res.status === 500) {
                  Swal.fire({
                      icon: 'warning',
                      title: 'ไม่พบข้อมูลในระบบ FDH',
                      text: res.body?.message_th ?? "ไม่มีรายการนี้ส่ง"
                  });
                  return;
              }

              // ------------------------------
              // ✔ ปัญหาฝั่งระบบ หรือ token/validate
              // ------------------------------
              if (res.status === 400) {
                  Swal.fire({
                      icon: 'error',
                      title: 'เกิดข้อผิดพลาด',
                      text: res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้'
                  });
                  return;
              }
          },

          error: function () {
              Swal.fire({
                  icon: 'error',
                  title: 'การเชื่อมต่อล้มเหลว',
                  text: 'ไม่สามารถเรียก API ได้ (Network Error)'
              });
          }
      });
  }
</script>

@endsection

@push('scripts')
  <script>
    $(document).ready(function () {
      // Table 1: Waiting for Claim
      $('#t_search').DataTable({
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ใน CUP รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

      // Table 2: Sent Claim
      $('#t_claim').DataTable({
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ใน CUP ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
        maintainAspectRatio: false,
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
            grace: '20%',
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
