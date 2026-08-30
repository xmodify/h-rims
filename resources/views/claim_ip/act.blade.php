@extends('layouts.app')

@section('content')

<style>
/* Custom pastel background for main tabs in claim_ip */
#search-tab {
    background-color: #fef2f2 !important; /* Soft pastel red/pink */
    color: #dc2626 !important;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}
#search-tab.active {
    background-color: #dc2626 !important;
    color: #fff !important;
}

#claim-tab {
    background-color: #f0fdf4 !important; /* Soft pastel green */
    color: #166534 !important;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}
#claim-tab.active {
    background-color: #166534 !important;
    color: #fff !important;
}
</style>

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ IP-พรบ. คุ้มครองผู้ประสบภัยจากรถ
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form id="form_budget_year" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
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
                        <button type="submit" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-graph-up me-1"></i> โหลดกราฟ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div id="data-container">
        <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
            <div class="card-body py-5 text-center">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mt-3 fw-bold text-secondary">กำลังประมวลผลข้อมูลการเรียกเก็บและชดเชย...</h5>
                <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาย้อนหลังทั้งปีงบประมาณและเชื่อมสถานะส่งเคลม อาจใช้เวลา 5-15 วินาที โปรดรอสักครู่</p>
            </div>
        </div>
    </div>

    <x-import_hub_modal module="act" title="นำเข้าข้อมูล IP-พรบ. คุ้มครองผู้ประสบภัยจากรถ" />

    <!-- Modal รายละเอียดการรับบริการผู้ป่วยใน (IPD) -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
          <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
            <div class="d-flex align-items-center gap-2">
              <div class="p-2 bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="bi bi-hospital-fill text-white fs-5"></i>
              </div>
              <div>
                <h6 class="modal-title fw-bold text-white mb-0" id="detailsModalLabel">รายละเอียดการรับบริการผู้ป่วยใน (IPD)</h6>
                <div class="text-white-50 small" style="font-size: 0.75rem;">ตรวจสอบความพร้อมข้อมูล 16 แฟ้ม และสถานะการเรียกเก็บ</div>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 bg-light" id="detailsModalBody">
            <!-- Content loaded via AJAX -->
          </div>
          <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between">
            <button type="button" class="btn btn-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-1"></i> ปิดหน้าต่าง
            </button>
          </div>
        </div>
      </div>
    </div>

@endsection

@push('scripts')
  <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>
  <script>
    const VISIT_DETAILS_URL = "{{ url('claim_ip/act/visit_details') }}";
    let currentModalAn = null;
    window.currentChartData = null;
    window.patientItems = [];

    function formatDateThai(dateStr) {
        if (!dateStr || dateStr === '0000-00-00') return '-';
        const parts = String(dateStr).trim().split(' ')[0].split('-');
        if (parts.length < 3) return dateStr;
        const year = parseInt(parts[0], 10) + 543;
        const monthIndex = parseInt(parts[1], 10);
        const day = parseInt(parts[2], 10);
        const months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        return `${day} ${months[monthIndex] || ''} ${year}`;
    }

    // Global DrawChart function
    function drawChart(labels, claim_price, claim_sent_price, receive_total) {
      const canvas = document.querySelector('#sum_month');
      if (!canvas) return;

      const existingChart = Chart.getChart(canvas);
      if (existingChart) {
          existingChart.destroy();
      }

      new Chart(canvas, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'เรียกเก็บ',
              data: claim_price,
              backgroundColor: 'rgba(185, 28, 28, 0.75)',
              borderColor: 'rgb(185, 28, 28)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'ส่งเคลม',
              data: claim_sent_price,
              backgroundColor: 'rgba(234, 179, 8, 0.6)',
              borderColor: 'rgb(234, 179, 8)',
              borderWidth: 1,
              borderRadius: 4
            },
            {
              label: 'ชดเชย',
              data: receive_total,
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
              formatter: (value) => value > 0 ? value.toLocaleString() : ''
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
    }

    function fetchData() {
        // Fallback for legacy handlers
    }

    function showDetails(an) {
        currentModalAn = an;
        const body = document.getElementById('detailsModalBody');
        if (body) {
            body.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                    <div class="mt-2 text-muted fw-bold">กำลังดึงข้อมูลและตรวจสอบความพร้อม 16 แฟ้ม IPD (AN: ${an})...</div>
                </div>
            `;
        }
        $('#detailsModal').modal('show');

        $.ajax({
            url: VISIT_DETAILS_URL,
            type: 'GET',
            data: { an: an }
        })
        .done(function(data) {
            const visit = data.visit;
            const items = data.items;
            const v     = data.validation;

            const isAuthDone  = v.auth_valid === true || visit.auth_code === 'Y';
            const hasWarnings = v.warnings && v.warnings.filter(w => !w.includes('Authen Code')).length > 0;

            function makeCellHtml(isValid, authDone) {
                if (!isValid) {
                    return `<button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${an}')" title="ไม่ผ่านเงื่อนไข 16 แฟ้ม IPD | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                } else if (authDone) {
                    return `<button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${an}')" title="ผ่านเงื่อนไข 16 แฟ้ม + มี Authen Code แล้ว | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                } else {
                    return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${an}')" title="ข้อมูลครบ แต่ยังไม่มี Authen Code | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                }
            }

            const dataOrder = !v.is_valid ? '0' : (isAuthDone ? '2' : '1');
            const searchRow = document.getElementById(`td-status-search-${an}`);
            if (searchRow) {
                searchRow.innerHTML = makeCellHtml(v.is_valid, isAuthDone);
                searchRow.setAttribute('data-order', dataOrder);
                if ($.fn.DataTable.isDataTable('#t_search')) {
                    $('#t_search').DataTable().cell(searchRow).invalidate().draw(false);
                }
            }
            const claimRow = document.getElementById(`td-status-claim-${an}`);
            if (claimRow) {
                claimRow.innerHTML = makeCellHtml(v.is_valid, isAuthDone);
                claimRow.setAttribute('data-order', dataOrder);
                if ($.fn.DataTable.isDataTable('#t_claim')) {
                    $('#t_claim').DataTable().cell(claimRow).invalidate().draw(false);
                }
            }

            let statusHtml = '';
            if (!v.is_valid) {
                statusHtml = `
                <div class="col-12 mb-3">
                  <div class="alert alert-danger py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #dc2626 !important; border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.1rem; color: #dc2626;"></i>
                    <div>
                      <div class="fw-bold mb-1 text-dark">สถานะ: ไม่ผ่านเกณฑ์ส่งออก 16 แฟ้ม IPD (มีข้อผิดพลาดที่ต้องแก้ไข)</div>
                      <ul class="mb-0 ps-3 text-danger">${v.errors.map(err => `<li>${err}</li>`).join('')}</ul>
                    </div>
                  </div>
                </div>`;
            } else if (hasWarnings || !isAuthDone) {
                statusHtml = `
                <div class="col-12 mb-3">
                  <div class="alert alert-warning py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fffbeb; color: #92400e; border-left: 5px solid #f59e0b !important; border-radius: 8px;">
                    <i class="bi bi-exclamation-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #f59e0b;"></i>
                    <div>
                      <div class="fw-bold mb-1 text-dark">สถานะ: ผ่านเกณฑ์พื้นฐาน แต่มีคำเตือน / ข้อควรตรวจสอบ</div>
                      <ul class="mb-0 ps-3 text-warning-emphasis">
                        ${!isAuthDone ? '<li>ยังไม่มีรหัสขออนุมัติเบิก (Authen Code)</li>' : ''}
                        ${v.warnings ? v.warnings.filter(w => !w.includes('Authen Code')).map(w => `<li>${w}</li>`).join('') : ''}
                      </ul>
                    </div>
                  </div>
                </div>`;
            } else {
                statusHtml = `
                <div class="col-12 mb-3">
                  <div class="alert alert-success py-2 px-3 border-0 shadow-sm d-flex align-items-center small mb-0" style="background-color: #f0fdf4; color: #166534; border-left: 5px solid #16a34a !important; border-radius: 8px;">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 1.1rem; color: #16a34a;"></i>
                    <div class="fw-bold">สถานะ: ข้อมูลครบถ้วนสมบูรณ์</div>
                  </div>
                </div>`;
            }

            function groupItemsByIcode(itemsList) {
                const map = new Map();
                itemsList.forEach(item => {
                    const key = String(item.icode || '').trim();
                    if (!map.has(key)) {
                        map.set(key, {
                            icode: item.icode,
                            name: item.name || '-',
                            qty: 0,
                            sum_price: 0,
                            paids_name: item.paids_name || item.paids || '-',
                            pttype_name: item.pttype_name || item.pttype || '-',
                            tmt_code: item.tmt_code || item.tmtid || item.sks_drug_code || '',
                            nhso_adp_code: item.nhso_adp_code || ''
                        });
                    }
                    const obj = map.get(key);
                    obj.qty += parseFloat(item.qty || 0);
                    obj.sum_price += parseFloat(item.sum_price || 0);
                });
                return Array.from(map.values());
            }

            const rawDrugs = items.filter(d => String(d.icode).startsWith('1'));
            const drugsList = groupItemsByIcode(rawDrugs);

            const rawServices = items.filter(d => !String(d.icode).startsWith('1'));
            const servicesList = groupItemsByIcode(rawServices);

            let drugsRows = '';
            if (drugsList.length === 0) {
                drugsRows = '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการสั่งยาใน AN นี้</td></tr>';
            } else {
                drugsRows = drugsList.map(d => {
                    let tmtDisplay = d.tmt_code
                        ? `<span class="badge bg-success fw-bold">${d.tmt_code}</span>`
                        : `<span class="badge bg-secondary-soft text-secondary">ไม่มีรหัส TMT</span>`;
                    return `<tr>
                      <td>
                        <div class="fw-bold text-dark">${d.name}</div>
                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                      </td>
                      <td class="text-center fw-bold">${Number.isInteger(d.qty) ? d.qty : d.qty.toFixed(2)}</td>
                      <td class="text-end font-monospace">${d.sum_price.toFixed(2)}</td>
                      <td class="text-center">${d.paids_name}</td>
                      <td class="text-center">${d.pttype_name}</td>
                      <td class="text-center">${tmtDisplay}</td>
                    </tr>`;
                }).join('');
            }

            let servicesRows = '';
            if (servicesList.length === 0) {
                servicesRows = '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการค่ารักษาพยาบาลใน AN นี้</td></tr>';
            } else {
                servicesRows = servicesList.map(s => {
                    let adpDisplay = s.nhso_adp_code ? `<span class="badge bg-info text-dark fw-bold">${s.nhso_adp_code}</span>` : `<span class="text-muted">-</span>`;
                    return `<tr>
                      <td>
                        <div class="fw-bold text-dark">${s.name}</div>
                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${s.icode}</div>
                      </td>
                      <td class="text-center fw-bold">${Number.isInteger(s.qty) ? s.qty : s.qty.toFixed(2)}</td>
                      <td class="text-end font-monospace">${s.sum_price.toFixed(2)}</td>
                      <td class="text-center">${s.paids_name}</td>
                      <td class="text-center">${s.pttype_name}</td>
                      <td class="text-center">${adpDisplay}</td>
                    </tr>`;
                }).join('');
            }

            body.innerHTML = `
              <div class="row g-3">
                ${statusHtml}

                <div class="col-md-4">
                  <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: #ffffff;">
                    <div class="card-body py-2 px-3">
                      <div class="fw-bold text-primary mb-2 small pb-1 border-bottom"><i class="bi bi-person-badge-fill me-1"></i>ข้อมูลผู้ป่วย & ความพร้อม</div>
                      <table class="table table-sm table-borderless mb-0 small compact-info-table">
                        <tr><th class="text-muted" style="width:35%">HN / AN</th><td class="fw-bold text-primary">${visit.hn} / ${visit.an}</td></tr>
                        <tr><th class="text-muted">CID (13 หลัก)</th><td class="font-monospace">${visit.cid || '-'}</td></tr>
                        <tr><th class="text-muted">ชื่อ-สกุล</th><td class="fw-bold text-dark">${visit.ptname}</td></tr>
                        <tr><th class="text-muted">เพศ / อายุ</th><td>${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : '-')} / ${visit.age_y} ปี</td></tr>
                        <tr><th class="text-muted">สิทธิการรักษา</th><td><span class="badge bg-primary-soft text-primary fw-bold text-wrap text-start" title="${visit.pttype || ''}" style="max-width: 210px; display: inline-block; font-size: 0.72rem; line-height: 1.3;">${visit.pttype || '-'}</span></td></tr>
                        <tr><th class="text-muted">รพ.หลัก</th><td>${visit.hospmain || '-'}</td></tr>
                        <tr><th class="text-muted">Authen Code</th><td>${visit.auth_code == 'Y' ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>มี Authen Code</span>' : '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>ไม่มี Authen Code</span>'}</td></tr>
                        <tr><th class="text-muted">สรุป Chart</th><td>${visit.dch_sum == 'Y' ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>สรุป Chart แล้ว (Y)</span>' : '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>ยังไม่สรุป Chart (N)</span>'}</td></tr>
                        <tr><th class="text-muted">Audit Chart</th><td>${visit.audit_status == 'Y' ? '<span class="badge bg-success" title="ผู้ Audit: ' + (visit.audit_doctor_name || '') + '"><i class="bi bi-check-circle me-1"></i>Audit แล้ว (Y)' + (visit.audit_doctor_name ? ' (' + visit.audit_doctor_name + ')' : '') + '</span>' : '<span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>ยังไม่ออดิท (N)</span>'}</td></tr>
                        <tr><th class="text-muted">สถานะเรียกเก็บ</th><td>${visit.coll_status ? '<span class="badge bg-info text-dark fw-bold">' + visit.coll_status + '</span>' : '<span class="text-muted">-</span>'}</td></tr>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: #ffffff;">
                    <div class="card-body py-2 px-3">
                      <div class="fw-bold text-primary mb-2 small pb-1 border-bottom"><i class="bi bi-clipboard2-pulse-fill me-1"></i>ข้อมูลการ Admit</div>
                      <table class="table table-sm table-borderless mb-0 small compact-info-table">
                        <tr><th class="text-muted" style="width:35%">วัน Admit</th><td>${formatDateThai(visit.regdate)} ${visit.regtime ? visit.regtime.substring(0,5) : ''} น.</td></tr>
                        <tr><th class="text-muted">วัน D/C</th><td>${formatDateThai(visit.dchdate)} ${visit.dchtime ? visit.dchtime.substring(0,5) : ''} น. (นอน ${visit.los} วัน)</td></tr>
                        <tr><th class="text-muted">หอผู้ป่วย (Ward)</th><td>${visit.ward_name || visit.ward || '-'}</td></tr>
                        <tr><th class="text-muted">สถานะ/ประเภท D/C</th><td>${visit.dchstts_name || visit.dchstts || '-'} / ${visit.dchtype_name || visit.dchtype || '-'}</td></tr>
                        <tr><th class="text-muted">น้ำหนักแรกรับ</th><td class="fw-bold">${visit.adm_w ? visit.adm_w + ' กก.' : '<span class="text-danger">ไม่ได้ระบุ</span>'}</td></tr>
                        <tr><th class="text-muted">แพทย์ผู้รักษา</th><td>${visit.doctor_name || '-'}</td></tr>
                        <tr><th class="text-muted">แพทย์จำหน่าย</th><td>${visit.dch_doctor_name || '-'}</td></tr>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="card border-0 shadow-sm h-100" style="border-radius: 10px; background: #ffffff;">
                    <div class="card-body py-2 px-3">
                      <div class="fw-bold text-primary mb-2 small pb-1 border-bottom"><i class="bi bi-currency-dollar me-1"></i>การเงิน & การวินิจฉัย</div>
                      <table class="table table-sm table-borderless mb-0 small compact-info-table">
                        <tr><th class="text-muted" style="width:38%">รวมค่ารักษา</th><td class="fw-bold text-dark">${parseFloat(visit.income || 0).toFixed(2)} บาท</td></tr>
                        <tr><th class="text-muted">ชำระเงินสด</th><td>${parseFloat(visit.rcpt_money || 0).toFixed(2)} บาท</td></tr>
                        <tr><th class="text-muted">ยอดเรียกเก็บ</th><td class="fw-bold text-primary">${parseFloat(visit.uc_money || (visit.income - visit.rcpt_money) || 0).toFixed(2)} บาท</td></tr>
                        <tr><th class="text-muted">PDX (โรคหลัก)</th><td>${visit.pdx ? '<span class="badge bg-danger-soft text-danger fw-bold">' + visit.pdx + '</span>' : '<span class="badge bg-danger">ยังไม่ระบุ PDX</span>'}</td></tr>
                        <tr><th class="text-muted">SDX (โรครอง)</th><td style="word-break: break-all;">${data.sec_diags.join(', ') || '<span class="text-muted">-</span>'}</td></tr>
                        <tr><th class="text-muted">ICD-9 (หัตถการ)</th><td style="word-break: break-all;">${data.procedures.join(', ') || '<span class="text-muted">-</span>'}</td></tr>
                        <tr><th class="text-muted">DRG / AdjRW</th><td><span class="badge bg-info text-dark fw-bold">${visit.drg || '-'}</span> / <span class="badge bg-secondary fw-bold">${visit.adjrw ? parseFloat(visit.adjrw).toFixed(4) : '0.0000'}</span></td></tr>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="col-12 mt-2">
                  <ul class="nav nav-tabs nav-tabs-modern mb-2" id="ipModalTabs" role="tablist">
                    <li class="nav-item">
                      <button class="nav-link active fw-bold text-primary" id="ip-drugs-tab" data-bs-toggle="tab" data-bs-target="#ip-drugs-panel" type="button" role="tab"><i class="bi bi-capsule me-1"></i>รายการยา</button>
                    </li>
                    <li class="nav-item">
                      <button class="nav-link fw-bold text-success" id="ip-services-tab" data-bs-toggle="tab" data-bs-target="#ip-services-panel" type="button" role="tab"><i class="bi bi-list-check me-1"></i>ค่ารักษาพยาบาล</button>
                    </li>
                  </ul>
                  <div class="tab-content bg-white p-3 rounded shadow-sm border" id="ipModalTabsContent">
                    <div class="tab-pane fade show active" id="ip-drugs-panel" role="tabpanel">
                      <table id="ip-drugs-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                        <thead class="table-dark">
                          <tr>
                            <th>ชื่อยา/เวชภัณฑ์</th>
                            <th class="text-center" width="10%">จำนวน</th>
                            <th class="text-end" width="12%">ราคารวม (บาท)</th>
                            <th class="text-center" width="15%">ประเภทการชำระ</th>
                            <th class="text-center" width="15%">สิทธิการรักษา</th>
                            <th class="text-center" width="15%">รหัสมาตรฐาน TMT</th>
                          </tr>
                        </thead>
                        <tbody>
                          ${drugsRows}
                        </tbody>
                      </table>
                    </div>
                    <div class="tab-pane fade" id="ip-services-panel" role="tabpanel">
                      <table id="ip-services-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                        <thead class="table-dark">
                          <tr>
                            <th>ชื่อบริการ/ค่ารักษาพยาบาล</th>
                            <th class="text-center" width="10%">จำนวน</th>
                            <th class="text-end" width="12%">ราคารวม (บาท)</th>
                            <th class="text-center" width="15%">ประเภทการชำระ</th>
                            <th class="text-center" width="15%">สิทธิการรักษา</th>
                            <th class="text-center" width="15%">รหัส ADP</th>
                          </tr>
                        </thead>
                        <tbody>
                          ${servicesRows}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            `;

            if ($.fn.DataTable.isDataTable('#ip-drugs-table')) {
                $('#ip-drugs-table').DataTable().destroy();
            }
            if ($.fn.DataTable.isDataTable('#ip-services-table')) {
                $('#ip-services-table').DataTable().destroy();
            }

            if (drugsList.length > 0) {
                $('#ip-drugs-table').DataTable({
                    pageLength: 5,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    }
                });
            }

            if (servicesList.length > 0) {
                $('#ip-services-table').DataTable({
                    pageLength: 5,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    }
                });
            }

            $('#ipModalTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });
        })
        .fail(function(xhr) {
            let msg = 'ไม่สามารถดึงข้อมูลการรับบริการได้';
            if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
            body.innerHTML = `<div class="alert alert-danger text-center my-4">${msg}</div>`;
        });
    }

    // AJAX Dashboard Loader
    function loadDashboard(dataParams) {
      const container = document.getElementById('data-container');
      if (!container) return;

      if (dataParams.skip_chart) {
          const tabContent = document.getElementById('myTabContent');
          if (tabContent) {
              tabContent.innerHTML = `
                  <div class="text-center py-5">
                      <div class="d-flex justify-content-center mb-3">
                          <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                      </div>
                      <h6 class="fw-bold text-secondary">กำลังอัปเดตตารางข้อมูลคนไข้...</h6>
                  </div>
              `;
          }
      } else {
          container.innerHTML = `
            <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
                <div class="card-body py-5 text-center">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                    </div>
                    <h5 class="fw-bold text-secondary">กำลังประมวลผลข้อมูลการเรียกเก็บและชดเชย...</h5>
                    <p class="text-muted small mb-0">ระบบกำลังสแกนประวัติการรักษาย้อนหลังทั้งปีงบประมาณและเชื่อมสถานะส่งเคลม อาจใช้เวลา 5-15 วินาที โปรดรอสักครู่</p>
                </div>
            </div>
          `;
      }

      $.ajax({
        url: "{{ url('claim_ip/act') }}",
        type: 'POST',
        data: Object.assign({}, dataParams, { _token: '{{ csrf_token() }}' }),
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            if (dataParams.skip_chart) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = response.table_html;
                const newTabContent = tempDiv.querySelector('#myTabContent');
                const targetTabContent = document.getElementById('myTabContent');
                
                if (newTabContent && targetTabContent) {
                    targetTabContent.innerHTML = newTabContent.innerHTML;
                }
                
                const newPillsTab = tempDiv.querySelector('#pills-tab');
                const targetPillsTab = document.getElementById('pills-tab');
                if (newPillsTab && targetPillsTab) {
                    targetPillsTab.innerHTML = newPillsTab.innerHTML;
                }

                initDataTables();
                initDatepickers();
            } else {
                container.innerHTML = response.table_html;
                initDataTables();
                initDatepickers();
                
                if (response.chart_data && response.chart_data.month && response.chart_data.month.length > 0) {
                  window.currentChartData = response.chart_data;
                  drawChart(
                    response.chart_data.month,
                    response.chart_data.claim_price,
                    response.chart_data.claim_sent_price,
                    response.chart_data.receive_total
                  );
                }
            }

            window.patientItems = response.patient_items || [];
          } else {
            container.innerHTML = `<div class="alert alert-danger m-4 text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>`;
          }
        },
        error: function(xhr, status, error) {
          container.innerHTML = `<div class="alert alert-danger m-4 text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล: ${error}</div>`;
        }
      });
    }

    function initDataTables() {
      if ($.fn.DataTable.isDataTable('#t_search')) {
        $('#t_search').DataTable().destroy();
      }
      if ($.fn.DataTable.isDataTable('#t_claim')) {
        $('#t_claim').DataTable().destroy();
      }

      const commonOptions = {
        dom: '<"row mb-3 align-items-center"<"col-md-6 d-flex align-items-center gap-2"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "ทั้งหมด"]],
        language: {
          search: "ค้นหา:",
          lengthMenu: "แสดง _MENU_ รายการ",
          info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
          paginate: {
            previous: "ก่อนหน้า",
            next: "ถัดไป"
          }
        },
        buttons: [
          {
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
            className: 'btn btn-success btn-sm px-3 shadow-sm',
            title: 'รายชื่อผู้มารับบริการ IP-พรบ.'
          }
        ],
        drawCallback: function () {
          $('.dataTables_paginate > .pagination').addClass('pagination-modern');
        }
      };

      $('#t_search').DataTable(commonOptions);
      $('#t_claim').DataTable(commonOptions);
    }

    function initDatepickers() {
      const dpOptions = {
        format: 'd M yyyy',
        autoclose: true,
        todayHighlight: true,
        language: 'th',
        thaiyear: true,
        clearBtn: false
      };

      $('#start_date_picker').datepicker(dpOptions).on('changeDate', function(e) {
        if (e.date) {
          const y = e.date.getFullYear();
          const m = String(e.date.getMonth() + 1).padStart(2, '0');
          const d = String(e.date.getDate()).padStart(2, '0');
          $('#start_date').val(`${y}-${m}-${d}`);
        }
      });

      $('#end_date_picker').datepicker(dpOptions).on('changeDate', function(e) {
        if (e.date) {
          const y = e.date.getFullYear();
          const m = String(e.date.getMonth() + 1).padStart(2, '0');
          const d = String(e.date.getDate()).padStart(2, '0');
          $('#end_date').val(`${y}-${m}-${d}`);
        }
      });

      const sDate = $('#start_date').val();
      if (sDate) {
        const parts = sDate.split('-');
        if (parts.length === 3) {
          $('#start_date_picker').datepicker('setDate', new Date(parts[0], parts[1] - 1, parts[2]));
        }
      }
      const eDate = $('#end_date').val();
      if (eDate) {
        const parts = eDate.split('-');
        if (parts.length === 3) {
          $('#end_date_picker').datepicker('setDate', new Date(parts[0], parts[1] - 1, parts[2]));
        }
      }
    }

    $(document).ready(function() {
      loadDashboard({
        budget_year: $('#form_budget_year select[name="budget_year"]').val(),
        start_date: '{{ $start_date }}',
        end_date: '{{ $end_date }}'
      });

      $('#form_budget_year').on('submit', function(e) {
        e.preventDefault();
        loadDashboard({
          budget_year: $(this).find('select[name="budget_year"]').val(),
          start_date: $('#start_date').val() || '{{ $start_date }}',
          end_date: $('#end_date').val() || '{{ $end_date }}'
        });
      });

      $(document).on('submit', '#form_indiv', function(e) {
        e.preventDefault();
        loadDashboard({
          budget_year: $('#form_budget_year select[name="budget_year"]').val(),
          start_date: $('#start_date').val(),
          end_date: $('#end_date').val(),
          skip_chart: 1
        });
      });

      $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
      });
    });
  </script>
@endpush