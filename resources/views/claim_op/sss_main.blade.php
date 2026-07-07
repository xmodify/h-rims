@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ SS-OP ประกันสังคม เครือข่าย
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
                สถิติการเรียกเก็บและชดเชยรายเดือน ปีงบประมาณ {{ $budget_year }}
            </h6>
            <div style="height: 300px; width: 100%;">
                <canvas id="sum_month"></canvas>
            </div>
        </div>

        <!-- Section 2: Tables -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ SS-OP ประกันสังคม เครือข่าย
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
                            <!-- Start Date -->
                            <input type="hidden" id="start_date" name="start_date" value="{{ $start_date }}">
                            <input type="text" id="start_date_picker" class="form-control datepicker_th text-center" readonly style="width: 120px; cursor: pointer;">
                            
                            <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>

                            <!-- End Date -->
                            <input type="hidden" id="end_date" name="end_date" value="{{ $end_date }}">
                            <input type="text" id="end_date_picker" class="form-control datepicker_th text-center" readonly style="width: 120px; cursor: pointer;">

                            <button onclick="fetchData()" type="submit" class="btn btn-success px-3 shadow-sm">
                                <i class="bi bi-table me-1"></i> โหลด indiv
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="table-responsive">            
                <table id="t_claim" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>                      
                            <th class="text-center">สถานะ</th>                      
                            <th class="text-center">วัน-เวลา | Q</th>     
                            <th class="text-center">HN</th>    
                            <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                            <th class="text-center" width="15%">อาการสำคัญ</th>
                            <th class="text-center" width="8%">โรคเรื้อรัง</th>
                            <th class="text-center" width="8%">ยาโรคเรื้อรัง</th>
                            <th class="text-end px-3" width="6%">PDX</th>
                            <th class="text-start px-3" width="10%">SDX | ICD9</th>
                            <th class="text-center">ค่ารักษา</th> 
                            <th class="text-center">ชำระเอง</th>                               
                            <th class="text-center text-primary">เรียกเก็บ</th> 
                        </tr>
                    </thead> 
                    <tbody> 
                        @php 
                            $count = 1; 
                            $sum_income = 0; 
                            $sum_rcpt_money = 0; 
                            $sum_claim_price = 0; 
                        @endphp
                        @foreach($claim as $row) 
                        <tr>
                            <td class="text-center text-muted small">{{ $count }}</td>
                            <td class="text-center" data-status="{{ $row->chronic_status }}" data-order="{{ $row->chronic_status === 'red' ? '2' : ($row->chronic_status === 'green' ? '1' : '0') }}">
                                @if($row->chronic_status === 'green')
                                    <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')" title="ผ่านเกณฑ์: มีรหัสโรคและยาเรื้อรังคู่กัน">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                @elseif($row->chronic_status === 'red')
                                    <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')" title="ไม่สอดคล้อง: ขาดรหัสโรคหรือยาโรคเรื้อรัง">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')" title="ดูรายละเอียด">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                @endif
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
                            <td class="text-center">
                                @if($row->is_ncd)
                                    <span class="d-none">Y</span><i class="bi bi-check-circle-fill text-success" title="เป็นโรคเรื้อรัง"></i>
                                @else
                                    <span class="d-none"></span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->has_chronic_drug)
                                    <span class="d-none">Y</span><i class="bi bi-check-circle-fill text-success" title="ได้รับยาโรคเรื้อรัง"></i>
                                @else
                                    <span class="d-none"></span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-dark small px-3">{{ $row->pdx }}</td>
                            <td class="text-start small px-3">
                                <div class="text-dark">{{ $row->sdx }}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">{{ $row->icd9 }}</div>
                            </td>
                            <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                            <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td> 
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
                        <tr>
                            <th colspan="10" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                            <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                            <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                            <th class="text-end fw-bold text-primary">{{ number_format($sum_claim_price,2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>          
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>รายละเอียดการรับบริการและตรวจสอบสิทธิ์
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="detailsModalBody">
                    <!-- Dynamic Content -->
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

@endsection

@push('scripts')  
  <script>
    $(document).ready(function () {

      // Initialize Datepicker Thai
      $('.datepicker_th').datepicker({
          format: 'd M yyyy',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th', 
          thaiyear: true,
          zIndexOffset: 1050
      });

      // Set initial values for Datepickers
      var start_date_val = "{{ $start_date }}";
      var end_date_val = "{{ $end_date }}";

      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
      }

      // Sync Changes from Picker to Hidden Input
      $('#start_date_picker').on('changeDate', function(e) {
          var date = e.date;
          if(date) {
            var day = ("0" + date.getDate()).slice(-2);
            var month = ("0" + (date.getMonth() + 1)).slice(-2);
            var year = date.getFullYear();
            $('#start_date').val(year + "-" + month + "-" + day);
          }
      });

      $('#end_date_picker').on('changeDate', function(e) {
          var date = e.date;
          if(date) {
            var day = ("0" + date.getDate()).slice(-2);
            var month = ("0" + (date.getMonth() + 1)).slice(-2);
            var year = date.getFullYear();
            $('#end_date').val(year + "-" + month + "-" + day);
          }
      });

      $.fn.dataTable.ext.order['dom-status'] = function (settings, col) {
          return this.api().column(col, {order:'index'}).nodes().map(function (td, i) {
              var status = $(td).attr('data-status');
              var sort = settings.aaSorting[0];
              var dir = (sort && sort[0] === col) ? sort[1] : 'desc';
              
              if (status === 'red') {
                  return dir === 'desc' ? 2 : -1;
              } else if (status === 'green') {
                  return dir === 'desc' ? 1 : -2;
              } else {
                  return 0;
              }
          });
      };

      $('#t_claim').DataTable({
        order: [[1, 'desc']],
        columnDefs: [
            { targets: 1, orderDataType: 'dom-status', orderSequence: ['desc', 'asc'] }
        ],
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
              title: 'รายชื่อผู้มารับบริการ SS-OP ประกันสังคม เครือข่าย วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}',
              exportOptions: {
                  columns: [0, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                  format: {
                      body: function (data, row, column, node) {
                          var $cell = $(node);
                          if (column === 5 || column === 6) {
                              return $cell.find('.d-none').text().trim() === 'Y' ? 'Y' : '';
                          }
                          var cleanText = $cell.text().replace(/\s+/g, ' ').trim();
                          return cleanText;
                      }
                  }
              }
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

    function showDetails(vn) {
        const body = document.getElementById('detailsModalBody');
        body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
        $('#detailsModal').modal('show');

        $.get("{{ url('claim_op/sss_detail') }}", { vn: vn })
            .done(function(data) {
                const visit = data.visit;
                const drugs = data.drugs;
                const diagnoses = data.diagnoses;
                
                let pdxBadge = visit.is_pdx_ncd 
                    ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> เป็นโรคเรื้อรัง (NCD)</span>' 
                    : '<span class="badge bg-secondary ms-2"><i class="bi bi-info-circle"></i> ไม่ใช่โรคเรื้อรัง</span>';

                let hasChronicDrug = drugs.some(d => d.is_chronic);
                let drugBadge = hasChronicDrug
                    ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> ได้รับยาโรคเรื้อรัง</span>'
                    : '<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle-fill"></i> ไม่พบยาโรคเรื้อรัง</span>';

                let validationStatusHtml = '';
                if (visit.is_ncd && hasChronicDrug) {
                    validationStatusHtml = '<div class="alert alert-success d-flex align-items-center mb-3"><i class="bi bi-check-circle-fill me-2 fs-5"></i> ข้อมูลสอดคล้อง (มีรหัสวินิจฉัยและยาโรคเรื้อรังครบถ้วน)</div>';
                } else if (visit.is_ncd || hasChronicDrug) {
                    validationStatusHtml = '<div class="alert alert-danger d-flex align-items-center mb-3"><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> ข้อมูลไม่สอดคล้อง (กรุณาตรวจสอบการบันทึกรหัสโรคและการจ่ายยา)</div>';
                } else {
                    validationStatusHtml = '<div class="alert alert-secondary d-flex align-items-center mb-3"><i class="bi bi-info-circle-fill me-2 fs-5"></i> ทั่วไป (ไม่เข้าเกณฑ์โรคเรื้อรัง ปกส.)</div>';
                }

                let pdxList = [];
                let sdxList = [];
                let icd9List = [];

                diagnoses.forEach(d => {
                    let badge = d.is_chronic 
                        ? '<span class="badge bg-success ms-1 small"><i class="bi bi-check-circle-fill me-1"></i>โรคเรื้อรัง</span>' 
                        : '';
                    if (d.diagtype == '1') {
                        pdxList.push(`<strong class="text-danger">${d.icd10}</strong>${badge}`);
                    } else if (d.diagtype == '2') {
                        icd9List.push(`<span class="text-dark">${d.icd10}</span>`);
                    } else {
                        sdxList.push(`<span class="text-dark fw-bold">${d.icd10}</span>${badge}`);
                    }
                });

                let pdxHtml = pdxList.join(', ') || '-';
                let sdxHtml = sdxList.join(', ') || '-';
                let icd9Html = icd9List.join(', ') || '-';

                let html = `
                    ${validationStatusHtml}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-person-fill me-1"></i> ข้อมูลผู้ป่วย</h6>
                            <table class="table table-sm table-bordered">
                                <tr><th class="bg-light" width="30%">HN</th><td>${visit.hn}</td></tr>
                                <tr><th class="bg-light">ชื่อ-สกุล</th><td>${visit.ptname}</td></tr>
                                <tr><th class="bg-light">เลขบัตร</th><td>${visit.cid}</td></tr>
                                <tr><th class="bg-light">สิทธิการรักษา</th><td>${visit.pttype_name}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-clipboard-pulse me-1"></i> ข้อมูลบริการ</h6>
                            <table class="table table-sm table-bordered">
                                <tr><th class="bg-light" width="30%">วันที่รับบริการ</th><td>${visit.vstdate} ${visit.vsttime}</td></tr>
                                <tr><th class="bg-light">อาการสำคัญ</th><td>${visit.cc || '-'}</td></tr>
                                <tr><th class="bg-light">PDX</th><td>${pdxHtml}</td></tr>
                                <tr><th class="bg-light">SDX</th><td>${sdxHtml}</td></tr>
                                <tr><th class="bg-light">ICD-9</th><td>${icd9Html}</td></tr>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h6 class="fw-bold text-primary mb-2 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-capsule-therapeutic me-1"></i> รายการยาที่ได้รับและตรวจสอบ TMT</span>
                            ${drugBadge}
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>ชื่อยา</th>
                                        <th class="text-center" width="10%">จำนวน</th>
                                        <th class="text-end" width="15%">ราคา</th>
                                        <th class="text-center" width="20%">รหัส TMT</th>
                                        <th class="text-center" width="15%">สถานะ ปกส.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${drugs.map(d => `
                                        <tr class="${d.is_chronic ? 'table-success-soft' : ''}">
                                            <td>${d.name}</td>
                                            <td class="text-center">${d.qty}</td>
                                            <td class="text-end">${parseFloat(d.sum_price).toFixed(2)}</td>
                                            <td class="text-center"><code>${d.tmtid || '-'}</code></td>
                                            <td class="text-center">
                                                ${d.is_chronic 
                                                    ? '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> ยาโรคเรื้อรัง</span>' 
                                                    : '<span class="text-muted small">-</span>'}
                                            </td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="5" class="text-muted text-center py-3">ไม่พบรายการจ่ายยา</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                body.innerHTML = html;
            })
            .fail(function(xhr) {
                body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>เกิดข้อผิดพลาด: ${xhr.responseJSON?.error || 'ไม่สามารถโหลดข้อมูลได้'}</div>`;
            });
    }
  </script>
@endpush

<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>
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
            backgroundColor: 'rgba(185, 28, 28, 0.75)',
            borderColor: 'rgb(185, 28, 28)',
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
            formatter: (value) => value > 0 ? value.toLocaleString() : ''
          }
        },
        scales: {
          y: {
            beginAtZero: true,
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
