@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                รายชื่อผู้มารับบริการ OP WALKIN
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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ OP WALKIN
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
                            <button onclick="checkFdhBulk(event)" type="button" class="btn btn-info text-white px-3 shadow-sm" title="ดึงสถานะ FDH ตามช่วงเวลาที่เลือก (ทีละ 1 วัน)">
                                <i class="bi bi-arrow-repeat me-1"></i> ดึง FDH
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
                                    <th class="text-center">ตรวจสอบ</th>
                                    <th class="text-center">ส่ง Claim</th>
                                    <th class="text-center" width="8%">วันที่รับบริการ</th>
                                    <th class="text-center">Queue</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-start" width="12%">ชื่อ-สกุล</th>
                                    <th class="text-start" width="15%">สิทธิการรักษา</th>
                                    <th class="text-end">ค่ารักษาทั้งหมด</th>
                                    <th class="text-end">ชำระเอง</th>
                                    <th class="text-end text-primary">เรียกเก็บ</th>
                                    <th class="text-end text-success">ชดเชย</th>
                                    <th class="text-end">ส่วนต่าง</th>
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
                                    <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ $row->endpoint_valid ? 1 : 0 }}">
                                        @if($row->endpoint_valid)
                                            <button class="btn btn-sm btn-outline-primary px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ยังไม่ปิดสิทธิ | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center" data-order="{{ $row->claim == 'Y' ? 1 : 0 }}">
                                        @if($row->claim && $row->claim == 'Y')
                                            <span class="badge bg-success-soft text-success p-2 rounded-circle" title="ส่งเคลมแล้ว"><i class="bi bi-check-circle-fill" style="font-size: 0.9rem;"></i></span>
                                        @else
                                            <span class="badge bg-secondary-soft text-secondary p-2 rounded-circle" title="ยังไม่ได้ส่ง"><i class="bi bi-dash-circle-fill" style="font-size: 0.9rem;"></i></span>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        {{ DateThai($row->vstdate) }}<br>
                                        <span class="text-muted" style="font-size: 0.75rem;">{{$row->vsttime}}</span>
                                    </td>
                                    <td class="text-center small">{{ $row->oqueue }}</td>
                                    <td class="text-center small text-primary fw-bold">{{$row->hn}}</td>
                                    <td class="text-start text-dark fw-bold small">{{$row->ptname}}</td>
                                    <td class="text-start small text-muted">
                                        <div class="text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                        <div style="font-size: 0.7rem;">[{{$row->hospmain}}]</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td>
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    <td class="text-end small fw-bold {{ ($row->receive_total-$row->claim_price) > 0 ? 'text-success' : (($row->receive_total-$row->claim_price) < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($row->receive_total-$row->claim_price,2) }}
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
<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>
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
        // Initialize Thai Datepicker
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
              title: 'รายชื่อผู้มารับบริการ OP WALKIN วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
            backgroundColor: 'rgba(185, 28, 28, 0.75)',
            borderColor: 'rgb(185, 28, 28)',
            borderWidth: 1,
            borderRadius: 4
          },
{
  label: 'ส่งเคลม',
  data: <?php echo json_encode($claim_sent_price); ?>,
  backgroundColor: 'rgba(234, 179, 8, 0.6)',
  borderColor: 'rgb(234, 179, 8)',
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

function showDetails(vn) {
        const body = document.getElementById('detailsModalBody');
        if (!body) return;
        body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
        $('#detailsModal').modal('show');

        $.get("{{ url('mishos/ucs_ppfs/visit_details') }}", { vn: vn })
            .done(function(data) {
                const visit = data.visit;
                const items = data.items;
                const v     = data.validation;

                const statusBadge = v.is_valid
                    ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> ผ่านเงื่อนไข</span>'
                    : '<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle-fill"></i> ไม่ผ่าน ' + v.errors.length + ' รายการ</span>';

                const isEndpointDone = v.endpoint_valid === true;
                const hasWarnings    = v.warnings && v.warnings.length > 0;

                // Update cell status in background (Yellow/Blue for non-validate views)
                function makeCellHtml(isValid, epDone, warn) {
                    if (epDone) {
                        return `<button class="btn btn-sm btn-outline-primary px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    } else {
                        return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ยังไม่ปิดสิทธิ | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    }
                }
                const dataOrder = isEndpointDone ? '1' : '0';
                const searchRow = document.getElementById(`td-status-search-${vn}`);
                if (searchRow) {
                    searchRow.innerHTML = makeCellHtml(v.is_valid, isEndpointDone, hasWarnings);
                    searchRow.setAttribute('data-order', dataOrder);
                    $('#t_search').DataTable().cell(searchRow).invalidate().draw(false);
                }

                let endpointBtn = '';
                if (v.endpoint_valid) {
                    endpointBtn = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`;
                } else {
                    endpointBtn = `<button onclick="pullNhsoData('${visit.vstdate}', '${visit.cid}', '${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;
                }

                let fdhBtn = '';
                if (visit.fdh_status) {
                    fdhBtn = `
                        <div class="d-inline-flex gap-2 align-items-center">
                            <span class="badge bg-success py-1 px-2 text-wrap" style="max-width:180px;">${visit.fdh_status}</span>
                            <button onclick="checkFdh('${visit.hn}', '${vn}')" class="btn btn-outline-success btn-sm py-0 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>ดึงอีกครั้ง</button>
                        </div>`;
                } else {
                    fdhBtn = `
                        <div class="d-inline-flex gap-2 align-items-center">
                            <span class="badge bg-secondary py-1 px-2">ยังไม่ได้ส่งเคลม</span>
                            <button onclick="checkFdh('${visit.hn}', '${vn}')" class="btn btn-outline-info btn-sm py-0 px-2 fw-bold text-dark" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>ดึง/ส่ง FDH</button>
                        </div>`;
                }

                let html = `
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="card border-0 bg-light-soft h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                        <table class="table table-sm table-borderless mb-0 small">
                          <tr><th class="text-muted" style="width:35%">HN</th><td class="fw-bold">${visit.hn}</td></tr>
                          <tr><th class="text-muted">ชื่อ-สกุล</th><td>${visit.ptname}</td></tr>
                          <tr><th class="text-muted">สิทธิ์</th><td>${visit.pttype ?? '-'}</td></tr>
                          <tr><th class="text-muted">เพศ/อายุ</th><td>${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : visit.sex)} / ${visit.age_y ?? '-'} ปี</td></tr>
                          <tr><th class="text-muted">ประสงค์เบิก</th><td>${visit.request_funds === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill"></i>N</span>'}</td></tr>
                          <tr><th class="text-muted">พร้อมส่ง</th><td>${visit.confirm_and_locked === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill"></i>N</span>'}</td></tr>
                          <tr><th class="text-muted">สถานะปิดสิทธิ</th><td>${endpointBtn}</td></tr>
                          <tr><th class="text-muted">สถานะ FDH</th><td>${fdhBtn}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="card border-0 bg-light-soft h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก</div>
                        <table class="table table-sm table-borderless mb-0 small">
                          <tr><th class="text-muted" style="width:35%">วันที่</th><td>${visit.vstdate} ${visit.vsttime}</td></tr>
                          <tr><th class="text-muted">CC</th><td>${visit.cc ?? '-'}</td></tr>
                          <tr><th class="text-muted">PDX</th><td class="fw-bold text-danger">${visit.pdx ?? '-'}</td></tr>
                          <tr><th class="text-muted">SDX</th><td>${data.sec_diags.join(', ') || '-'}</td></tr>
                          <tr><th class="text-muted">ICD-9</th><td>${data.procedures.join(', ') || '-'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>`;

                // Validation errors
                if (!v.is_valid) {
                    html += `
                  <div class="col-12">
                    <div class="alert alert-danger py-2 mb-0 small">
                      <strong><i class="bi bi-x-octagon-fill me-1"></i>เงื่อนไขที่ไม่ผ่าน:</strong>
                      <ul class="mb-0 mt-1 ps-3">`;
                    v.errors.forEach(function(err) { html += `<li>${err}</li>`; });
                    html += `</ul></div></div>`;
                }

                // Warnings
                if (v.warnings && v.warnings.length > 0) {
                    html += `
                  <div class="col-12">
                    <div class="alert alert-warning py-2 mb-0 small">
                      <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>คำเตือน Instrument ไม่อยู่ในประกาศ UCS (${v.warnings.length} รายการ):</strong>
                      <ul class="mb-0 mt-1 ps-3">`;
                    v.warnings.forEach(function(w) { html += `<li>${w}</li>`; });
                    html += `</ul></div></div>`;
                }

                // Items table
                html += `
                  <div class="col-12">
                    <div class="fw-bold small text-dark mb-2"><i class="bi bi-list-check me-1"></i>รายการเรียกเก็บ ${statusBadge}${
                        (v.warnings && v.warnings.length > 0)
                        ? '<span class="badge bg-warning text-dark ms-2"><i class="bi bi-exclamation-triangle-fill"></i>' + v.warnings.length + ' คำเตือน</span>'
                        : ''
                    }</div>
                    <div class="table-responsive">
                      <table class="table table-sm table-hover small mb-0">
                        <thead class="table-light">
                          <tr>
                            <th>icode</th><th>รายการ</th><th>ประเภท</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-end">ราคา/หน่วย</th>
                            <th class="text-end">รวม</th>
                          </tr>
                        </thead><tbody>`;

                items.forEach(function(item) {
                    let type = '';
                    if (item.ppfs  === 'Y') type += '<span class="badge-type badge-ppfs me-1">PPFS</span>';
                    if (item.uc_cr === 'Y') type += '<span class="badge-type badge-uc_cr me-1">UC_CR</span>';
                    if (item.herb32=== 'Y') type += '<span class="badge-type badge-herb me-1">Herb</span>';
                    const insWarn = (item.uc_cr === 'Y' && item.ins_ucs !== undefined && item.ins_ucs !== 'Y' && item.nhso_adp_code)
                        ? `<span class="badge bg-warning text-dark ms-1" title="ADP ${item.nhso_adp_code} ไม่อยู่ในประกาศ UCS"><i class="bi bi-exclamation-triangle-fill"></i></span>`
                        : '';
                    html += `<tr class="${(item.uc_cr === 'Y' && item.ins_ucs !== undefined && item.ins_ucs !== 'Y' && item.nhso_adp_code) ? 'table-warning' : ''}">
                        <td class="text-muted">${item.icode}</td>
                        <td>${item.name ?? '-'}${insWarn}</td>
                        <td>${type}</td>
                        <td class="text-center">${item.qty}</td>
                        <td class="text-end">${parseFloat(item.unitprice).toLocaleString('th-TH',{minimumFractionDigits:2})}</td>
                        <td class="text-end fw-bold">${parseFloat(item.sum_price).toLocaleString('th-TH',{minimumFractionDigits:2})}</td>
                    </tr>`;
                });

                html += `</tbody></table></div></div></div>`;
                body.innerHTML = html;
            })
            .fail(function() {
                body.innerHTML = '<div class="alert alert-warning">ไม่สามารถโหลดข้อมูลได้</div>';
            });
    }

    function pullNhsoData(vstdate, cid, vn) {
        Swal.fire({
            title: 'กำลังดึงข้อมูล...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() }
        });
        fetch("{{ url('api/nhso_endpoint_pull_indiv') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({ vstdate: vstdate, cid: cid })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || "ล้มเหลว");
            }
            return data;
        })
        .then(data => {
            if (data.found) {
                Swal.fire({
                    icon: 'success',
                    title: 'พบข้อมูลปิดสิทธิ',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    showDetails(vn);
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบการปิดสิทธิจากระบบอื่น',
                    text: 'ยังไม่มีการปิดสิทธิสำหรับรายการนี้ใน สปสช. ต้องการปิดสิทธิด้วยระบบ RiMS หรือไม่?',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'ปิดสิทธิเลย',
                    cancelButtonText: 'ยกเลิก'
                }).then(result => {
                    if (result.isConfirmed) {
                        pushNhsoData(cid, vstdate, vn);
                    }
                });
            }
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'ดึงข้อมูลล้มเหลว', text: err.message });
        });
    }

    function pushNhsoData(cid, vstdate, vn) {
        Swal.fire({
            title: 'ยืนยันการส่งข้อมูล?',
            text: "ระบบจะดึงข้อมูลจาก HOSxP และส่งไปปิดสิทธิที่ สปสช.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ตกลง, ส่งข้อมูล!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังดำเนินการ...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });

                $.ajax({
                    url: "{{ route('api.nhso.push_indiv') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        cid: cid,
                        vstdate: vstdate
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'ปิดสิทธิเรียบร้อยแล้ว',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                showDetails(vn);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'ไม่สำเร็จ',
                                text: response.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล'
                            });
                        }
                    },
                    error: function(xhr) {
                        let msg = 'ไม่สามารถเชื่อมต่อกับระบบได้';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: msg
                        });
                    }
                });
            }
        });
    }

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
            data: { hn: hn, seq: seq, _token: "{{ csrf_token() }}" },
            success: function (res) {
                if (res.status === 200) {
                    Swal.fire({ icon: 'success', title: 'ตรวจสอบสำเร็จ', text: 'พบข้อมูลในระบบ FDH', timer: 1500, showConfirmButton: false })
                    .then(() => { showDetails(seq); });
                } else if (res.status === 404 || res.status === 500) {
                    const statusText = res.body?.message_th ?? "ไม่มีรายการนี้ส่ง";
                    Swal.fire({ icon: 'warning', title: 'ไม่พบข้อมูลในระบบ FDH', text: statusText })
                    .then(() => { showDetails(seq); });
                } else if (res.status === 400) {
                    const statusText = res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้';
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: statusText })
                    .then(() => { showDetails(seq); });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'การเชื่อมต่อล้มเหลว', text: 'ไม่สามารถเรียก API ได้ (Network Error)' });
            }
        });
    }

        async function checkFdhBulk(e) {
        e.preventDefault();
        const items = {!! json_encode(array_map(function($row) {
            return [
                'hn' => $row->hn,
                'seq' => $row->seq,
                'an' => ''
            ];
        }, $search)) !!};

        if (!items || items.length === 0) {
            Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
            return;
        }

        await runFdhBulkCheck(items, "{{ csrf_token() }}", "{{ url('/api/fdh/check-chunk') }}", function() {
            fetchData();
            $('#form_indiv').submit();
        });
    }
</script>
@endpush
