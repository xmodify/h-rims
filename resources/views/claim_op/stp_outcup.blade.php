@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ STP-OP นอก CUP
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกปีงบประมาณ</span>
                    <div class="input-group input-group-sm">
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
                สถิติการเรียกเก็บและชดเชยรายเดือน ปีงบประมาณ {{ $budget_year }}
            </h6>
            <div style="height: 300px; width: 100%;">
                <canvas id="sum_month"></canvas>
            </div>
        </div>

        <!-- Section 2: Tabbed Data Table -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <ul class="nav nav-tabs nav-tabs-modern border-0" id="claimTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="search-tab" data-bs-toggle="tab" data-bs-target="#search-pane" type="button" role="tab">
                            <i class="bi bi-hourglass-split me-2"></i>รอส่ง Claim
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent-pane" type="button" role="tab">
                            <i class="bi bi-send-check-fill me-2"></i>ส่ง Claim แล้ว
                        </button>
                    </li>
                </ul>
                
                <div class="filter-group mb-2">
                    <form id="form_indiv" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                        @csrf            
                        <span class="fw-bold text-muted small text-nowrap me-2">เลือกวันที่รับบริการ</span>
                        <div class="input-group input-group-sm">
                            <input type="hidden" name="budget_year" value="{{ $budget_year }}">
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

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="claimTabContent">
                <!-- Tab 1: Waiting for Claim -->
                <div class="tab-pane fade show active" id="search-pane" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_search" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th> 
                                    <th class="text-center small">ACTION</th>
                                    <th class="text-center small">สถานะ</th>
                                    <th class="text-center">วัน-เวลา | Q</th>  
                                    <th class="text-center">HN</th>    
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="10%">อาการสำคัญ</th>
                                    <th class="text-center">PDX | ICD9</th>
                                    <th class="text-center small">ค่ารักษา</th> 
                                    <th class="text-center small">ชำระเอง</th>
                                    <th class="text-center small">ค่ารถ Refer</th>
                                    <th class="text-center small">ER | Proj | AE</th>
                                    <th class="text-center small">เรียกเก็บ</th> 
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income_s = 0; 
                                    $sum_rcpt_money_s = 0;  
                                    $sum_claim_price_s = 0; 
                                @endphp
                                @foreach($search as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm py-0 px-2" 
                                            title="Check FDH" onclick="checkFdh('{{ $row->hn }}','{{ $row->seq }}')">
                                            <i class="bi bi-shield-check me-1"></i> FDH
                                        </button>
                                    </td>
                                    <td class="text-start small" style="font-size: 0.65rem;">
                                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                            <span>Authen:</span>
                                            <span class="fw-bold @if($row->auth_code == 'Y') text-success @else text-danger @endif">{{ $row->auth_code }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                            <span>Endpoint:</span>
                                            <span class="fw-bold @if($row->endpoint == 'Y') text-success @else text-danger @endif">{{ $row->endpoint }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>พร้อมส่ง:</span>
                                            <span class="fw-bold @if($row->confirm_and_locked == 'Y') text-success @else text-danger @endif">{{ $row->confirm_and_locked }}</span>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}} [{{$row->hospmain}}]">
                                            {{$row->pttype}} [{{$row->hospmain}}]
                                        </div>
                                    </td> 
                                    <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->refer,2) }}</td>
                                    <td class="text-center small">
                                        <div class="badge bg-light text-dark fw-normal">{{ $row->er }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{ $row->project }} | {{ $row->ae }}</div>
                                    </td>
                                    <td class="text-end small fw-bold text-primary">{{ number_format($row->income-$row->rcpt_money,2) }}</td> 
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income_s += $row->income; 
                                    $sum_rcpt_money_s += $row->rcpt_money; 
                                    $sum_claim_price_s += ($row->income-$row->rcpt_money); 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="10" class="text-end text-muted small px-3">รวมที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income_s,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money_s,2) }}</th>
                                    <th></th>
                                    <th></th>
                                    <th class="text-end small fw-bold text-primary">{{ number_format($sum_claim_price_s,2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Claims Sent -->
                <div class="tab-pane fade" id="sent-pane" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_claim" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>                      
                                    <th class="text-center">Action</th>
                                    <th class="text-center">FDH Status</th>
                                    <th class="text-center">วัน-เวลา | Q</th>  
                                    <th class="text-center">HN</th> 
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="10%">อาการสำคัญ</th>
                                    <th class="text-center">PDX | ICD9</th>
                                    <th class="text-center small">ค่ารักษา</th> 
                                    <th class="text-center small">ชำระเอง</th>
                                    <th class="text-center small">เรียกเก็บ</th>
                                    <th class="text-center small">Refer | Proj | AE</th>
                                    <th class="text-center text-primary bg-primary-soft small">STM ชดเชย</th> 
                                    <th class="text-center text-primary bg-primary-soft small">ผลต่าง</th> 
                                    <th class="text-center text-primary bg-primary-soft small">REP</th> 
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income_c = 0; 
                                    $sum_rcpt_money_c = 0;
                                    $sum_claim_price_c = 0;
                                    $sum_receive_total_c = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>                   
                                    <td class="text-center">
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
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}} [{{$row->hospmain}}]">
                                            {{$row->pttype}} [{{$row->hospmain}}]
                                        </div>
                                    </td> 
                                    <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small fw-bold">{{ number_format($row->income-$row->rcpt_money,2) }}</td> 
                                    <td class="text-center small">
                                        <div class="small">{{ number_format($row->refer,2) }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{ $row->project }} | {{ $row->ae }}</div>
                                    </td>
                                    <td class="text-end small fw-bold bg-primary-soft @if($row->receive_total > 0) text-success @elseif($row->receive_total < 0) text-danger @endif">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    <td class="text-end small fw-bold bg-primary-soft @if($row->receive_total-($row->income-$row->rcpt_money) > 0) text-success @elseif($row->receive_total-($row->income-$row->rcpt_money) < 0) text-danger @endif">
                                        {{ number_format($row->receive_total-($row->income-$row->rcpt_money),2) }}
                                    </td>
                                    <td class="text-center small text-primary bg-primary-soft">{{ $row->repno }}</td> 
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income_c += $row->income; 
                                    $sum_rcpt_money_c += $row->rcpt_money; 
                                    $sum_claim_price_c += ($row->income-$row->rcpt_money); 
                                    $sum_receive_total_c += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="8" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income_c,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money_c,2) }}</th>
                                    <th class="text-end small fw-bold">{{ number_format($sum_claim_price_c,2) }}</th>
                                    <th></th>
                                    <th class="text-end small fw-bold bg-primary-soft @if($sum_receive_total_c > 0) text-success @elseif($sum_receive_total_c < 0) text-danger @endif">
                                        {{ number_format($sum_receive_total_c,2) }}
                                    </th>
                                    <th class="text-end small fw-bold bg-primary-soft @if($sum_receive_total_c-$sum_claim_price_c > 0) text-success @elseif($sum_receive_total_c-$sum_claim_price_c < 0) text-danger @endif">
                                        {{ number_format($sum_receive_total_c-$sum_claim_price_c,2) }}
                                    </th>
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

  function checkFdh(hn, seq) {
    Swal.fire({
      title: 'กำลังตรวจสอบสถานะ...',
      text: 'กรุณารอสักครู่',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: "{{ url('/api/fdh/check-claim-indiv') }}",
      method: "POST",
      data: {
        _token: "{{ csrf_token() }}",
        hn: hn,
        seq: seq
      },
      success: function(res) {
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
          }).then(() => {
            showLoading();
            $('#form_indiv').submit();
          });
          return;
        }

        // ------------------------------
        // ✔ ไม่พบข้อมูล FDH (404) Or Server Error (500)
        // ------------------------------
        if (res.status === 404 || res.status === 500) {
          Swal.fire({
            icon: 'warning',
            title: 'ไม่พบข้อมูลในระบบ FDH',
            text: res.body?.message_th ?? "ไม่มีรายการนี้ส่ง",
            confirmButtonText: 'รับทราบ'
          });
          return;
        }

        // ------------------------------
        // ✔ ปัญหาฝั่งระบบ หรือ token/validate (400)
        // ------------------------------
        if (res.status === 400) {
          Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้',
            confirmButtonText: 'รับทราบ'
          });
          return;
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'การเชื่อมต่อล้มเหลว',
          text: 'ไม่สามารถเรียก API ได้ (Network Error)',
          confirmButtonText: 'รับทราบ'
        });
      }
    });
  }
</script>

@push('scripts')
  <script>
    $(document).ready(function () {
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
              className: 'btn btn-success btn-sm shadow-sm',
              title: 'รายชื่อผู้มารับบริการ STP-OP บุคคลที่มีปัญหาสถานะและสิทธิ นอก CUP รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
              className: 'btn btn-success btn-sm shadow-sm',
              title: 'รายชื่อผู้มารับบริการ STP-OP บุคคลที่มีปัญหาสถานะและสิทธิ นอก CUP ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
      plugins: [ChartDataLabels]
    });
  });
</script>

@endsection
