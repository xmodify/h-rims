@extends('layouts.app')

@section('content')
<style>

  .report-card {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    overflow: hidden;
    position: relative;
  }
  .report-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -6px rgba(139, 92, 246, 0.15);
    border-color: #c4b5fd;
  }
  .report-card .card-icon-wrapper {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.25s ease;
  }
  .report-card:hover .card-icon-wrapper {
    transform: scale(1.08);
  }

  /* Table Style */
  .table-service-report {
    border: 1px solid #cbd5e1;
    font-size: 0.92rem;
    border-collapse: separate;
    border-spacing: 0;
  }
  .table-service-report thead th {
    background-color: #3b6287 !important;
    color: #ffffff !important;
    font-weight: 700;
    padding: 10px 14px;
    border-bottom: 2px solid #28445e;
    white-space: nowrap;
  }
  .table-service-report tbody td {
    padding: 7px 14px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
  }
  .table-service-report tbody tr:nth-child(even) {
    background-color: #f8fafc;
  }
  .table-service-report tbody tr:hover {
    background-color: #f1f5f9;
  }
  .table-service-report .input-amt {
    font-size: 0.95rem;
    font-weight: 700;
    border: 1.5px solid #94a3b8;
    border-radius: 4px;
    padding: 4px 10px;
    background-color: #ffffff;
    transition: all 0.2s;
  }
  .table-service-report .input-amt:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
    background-color: #eff6ff;
  }
  .btn-copy-mini {
    padding: 3px 8px;
    font-size: 0.75rem;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    transition: all 0.15s ease;
  }
  .btn-copy-mini:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #94a3b8;
  }
  .datepicker {
    z-index: 1600 !important;
    font-family: inherit;
  }
</style>

<div class="container-fluid pt-2 pb-5 px-lg-5" style="background-color: #f8fafc; min-height: 90vh;">
    <div class="row">
        <!-- Back Button -->
        <div class="col-12 px-3 mb-3">
            <a href="{{ url('hosfin') }}" class="btn btn-outline-secondary btn-sm rounded-pill shadow-sm px-3 d-inline-flex align-items-center gap-2" style="font-size: 0.85rem; border-color: #cbd5e1; color: #475569; background-color: #fff;">
                <i class="bi bi-arrow-left"></i> ย้อนกลับ HosFin Dashboard
            </a>
        </div>

        <!-- Reports Grid Catalog -->
        <div class="col-12 px-3">
            <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-grid-fill text-primary" style="font-size: 1.05rem;"></i> เลือกรายงานที่ต้องการ
            </h5>

            <div class="row g-3">
                <!-- Report Card: รายงานข้อมูลบริการประกอบงบ -> Triggers Modal -->
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card h-100 report-card p-3 shadow-xs" 
                         style="border-top: 4px solid #3b82f6 !important;"
                         data-bs-toggle="modal" 
                         data-bs-target="#serviceDataModal">
                        <div class="d-flex align-items-start gap-3 mb-2">
                            <div class="card-icon-wrapper" style="background: #eff6ff; color: #2563eb;">
                                <i class="bi bi-clipboard2-data-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-end align-items-center mb-1">
                                    <span class="text-muted small" style="font-size: 0.72rem;">คลิกเพื่อเปิด</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1 fs-6">รายงานข้อมูลบริการประกอบงบ</h6>
                                <p class="text-muted small mb-0" style="font-size: 0.82rem; line-height: 1.45;">
                                    รหัสบัญชี 11-45 ข้อมูลผู้ป่วยนอก (OPD), ผู้ป่วยใน (IPD), วันนอน, เบาหวาน-ความดัน และ SumAdjRW
                                </p>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 mt-auto border-top" style="font-size: 0.8rem;">
                            <span class="text-secondary"><i class="bi bi-window-stack me-1 text-primary"></i> เปิดหน้าต่าง Modal</span>
                            <span class="fw-bold text-primary d-inline-flex align-items-center gap-1">
                                คลิกเปิดรายงาน <i class="bi bi-arrow-right-short fs-6"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: รายงานข้อมูลบริการประกอบงบ (ดึงจาก HOSxP) -->
<!-- ========================================================================= -->
<div class="modal fade" id="serviceDataModal" tabindex="-1" aria-labelledby="serviceDataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #2b4c7e 0%, #1e3a5f 100%);">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="serviceDataModalLabel">
                        <i class="bi bi-clipboard-data-fill text-warning"></i> 
                        รายงานข้อมูลบริการประกอบงบ
                    </h5>
                    <small class="text-white-50" style="font-size: 0.82rem;">
                        ประมวลผลดึงข้อมูลจาก HOSxP
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Filter Section: ช่วงวันที่ และ ไตรมาส + ปุ่มประมวลผล -->
                <div class="card border-0 shadow-sm rounded-3 mb-3 p-3" style="background: #ffffff; border-left: 4px solid #3b82f6 !important;">
                    <div class="row g-3 align-items-end">
                        <!-- Date Range (Monthly) -->
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.84rem;">
                                <i class="bi bi-calendar-range text-primary me-1"></i> ช่วงวันที่ (ข้อมูลประจำเดือน):
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-secondary" style="font-size: 0.8rem;"><i class="bi bi-calendar-event me-1 text-primary"></i>จาก</span>
                                <input type="hidden" id="filter_start_date" value="{{ $defaultStartDate }}">
                                <input type="text" class="form-control datepicker_th text-center fw-bold" id="filter_start_date_picker" readonly style="background-color: #fff; cursor: pointer;" placeholder="วว/ดด/ปปปป">
                                <span class="input-group-text bg-light text-secondary" style="font-size: 0.8rem;">ถึง</span>
                                <input type="hidden" id="filter_end_date" value="{{ $defaultEndDate }}">
                                <input type="text" class="form-control datepicker_th text-center fw-bold" id="filter_end_date_picker" readonly style="background-color: #fff; cursor: pointer;" placeholder="วว/ดด/ปปปป">
                            </div>
                        </div>

                        <!-- Budget Year & Quarter -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.84rem;">
                                <i class="bi bi-pie-chart text-primary me-1"></i> ไตรมาส (ยอดสะสมไตรมาส):
                            </label>
                            <div class="input-group input-group-sm">
                                <select class="form-select fw-bold" id="filter_budget_year" style="max-width: 105px;">
                                    @foreach($budgetYearChoices as $by)
                                        <option value="{{ $by }}" {{ $budgetYear == $by ? 'selected' : '' }}>{{ $by }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select fw-bold" id="filter_quarter">
                                    <option value="1" {{ $quarter == 1 ? 'selected' : '' }}>ไตรมาส 1 (ต.ค.-ธ.ค.)</option>
                                    <option value="2" {{ $quarter == 2 ? 'selected' : '' }}>ไตรมาส 2 (ม.ค.-มี.ค.)</option>
                                    <option value="3" {{ $quarter == 3 ? 'selected' : '' }}>ไตรมาส 3 (เม.ย.-มิ.ย.)</option>
                                    <option value="4" {{ $quarter == 4 ? 'selected' : '' }}>ไตรมาส 4 (ก.ค.-ก.ย.)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Process Button -->
                        <div class="col-12 col-md-3 text-end">
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm w-100 d-flex align-items-center justify-content-center gap-1.5" 
                                    id="btnProcessHosxp" 
                                    style="height: 36px; font-size: 0.88rem; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;">
                                <i class="bi bi-play-circle-fill fs-6"></i> ประมวลผล
                            </button>
                        </div>
                    </div>

                    <!-- Quarter Info Hint -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 pt-2 border-top" style="font-size: 0.76rem;">
                        <span class="text-muted">
                            <i class="bi bi-info-circle me-1 text-primary"></i> 
                            รายการ 11-15, 21-25, 31, 41-45 คำนวณจาก <strong>ช่วงวันที่</strong> | รายการ 16, 32 คำนวณจาก <strong>ไตรมาส</strong>
                        </span>
                        <span id="quarterInfoBadge" class="badge bg-light text-secondary border">
                            {{ $quarterDates['label'] ?? '' }}
                        </span>
                    </div>
                </div>

                <!-- Table Form Container -->
                <div id="modalTableContainer">
                    <div class="table-responsive rounded shadow-xs mb-3 bg-white" style="border: 1px solid #cbd5e1;">
                        <table class="table table-hover table-service-report align-middle mb-0" id="tblServiceData">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 110px;">รหัสบัญชี</th>
                                    <th>ชื่อบัญชี</th>
                                    <th class="text-center" style="width: 260px;">จำนวน</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accounts as $code => $acc)
                                    <tr id="row_{{ $code }}">
                                        <td class="text-center fw-bold text-secondary" style="font-family: monospace; font-size: 0.95rem;">
                                            {{ $code }}
                                        </td>
                                        <td class="fw-bold text-dark" style="font-size: 0.88rem;">
                                            {{ $acc['name'] }}
                                            @if($acc['type'] === 'quarterly')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.2 ms-1" style="font-size: 0.68rem;">ไตรมาส</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center gap-1.5 justify-content-end">
                                                <input type="text" 
                                                       class="form-control text-end input-amt" 
                                                       id="amt_{{ $code }}" 
                                                       placeholder="0"
                                                       style="height: 32px;"
                                                       onfocus="this.select();">
                                                <button type="button" 
                                                        class="btn btn-copy-mini shadow-xs" 
                                                        onclick="copySingleValue('{{ $code }}')"
                                                        title="คัดลอกตัวเลข">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <!-- Status on the left -->
                <div>
                    <span class="text-muted small" id="lastUpdatedStatus">
                        <i class="bi bi-info-circle me-1"></i> กดปุ่ม "ประมวลผล" เพื่อดึงข้อมูลสดจาก HOSxP
                    </span>
                </div>

                <!-- Buttons on the right -->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnPrintReport" style="height: 36px;">
                        <i class="bi bi-printer me-1"></i> พิมพ์รายงาน
                    </button>

                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnExportExcel" style="height: 36px;">
                        <i class="bi bi-file-earmark-excel me-1"></i> ส่งออก Excel
                    </button>

                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3 fw-bold" data-bs-dismiss="modal" style="height: 36px;">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    
    // -------------------------------------------------------------
    // Initialize Thai Datepicker (ปฏิทินไทย / พ.ศ.)
    // -------------------------------------------------------------
    if (typeof $.fn.datepicker !== 'undefined') {
        $('.datepicker_th').datepicker({
            format: 'd M yyyy',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            zIndexOffset: 1600
        });

        function syncDatepickers() {
            var startVal = $('#filter_start_date').val();
            var endVal = $('#filter_end_date').val();
            if (startVal) {
                var sParts = startVal.split('-');
                if (sParts.length === 3) {
                    $('#filter_start_date_picker').datepicker('setDate', new Date(parseInt(sParts[0]), parseInt(sParts[1]) - 1, parseInt(sParts[2])));
                }
            }
            if (endVal) {
                var eParts = endVal.split('-');
                if (eParts.length === 3) {
                    $('#filter_end_date_picker').datepicker('setDate', new Date(parseInt(eParts[0]), parseInt(eParts[1]) - 1, parseInt(eParts[2])));
                }
            }
        }

        syncDatepickers();

        $('#serviceDataModal').on('shown.bs.modal', function () {
            syncDatepickers();
        });

        $('.datepicker_th').on('changeDate', function(e) {
            var date = e.date;
            var targetId = $(this).attr('id').replace('_picker', '');
            var hiddenInput = $('#' + targetId);
            if (date) {
                var day = ("0" + date.getDate()).slice(-2);
                var month = ("0" + (date.getMonth() + 1)).slice(-2);
                var year = date.getFullYear(); // คริสต์ศักราช สำหรับ Query Backend
                hiddenInput.val(year + "-" + month + "-" + day);
            } else {
                hiddenInput.val('');
            }
        });
    }

    // -------------------------------------------------------------
    // Calculate quarter start/end dates when quarter or year changes
    // -------------------------------------------------------------
    function updateQuarterDates() {
        const by = parseInt(document.getElementById('filter_budget_year').value);
        const q = parseInt(document.getElementById('filter_quarter').value);
        const ceYear = by - 543;
        
        let qStart = '', qEnd = '', qLabel = '';
        if (q === 1) {
            qStart = (ceYear - 1) + '-10-01';
            qEnd = (ceYear - 1) + '-12-31';
            qLabel = `ไตรมาส 1 (1 ต.ค. - 31 ธ.ค. ${by - 1})`;
        } else if (q === 2) {
            qStart = ceYear + '-01-01';
            qEnd = ceYear + '-03-31';
            qLabel = `ไตรมาส 2 (1 ม.ค. - 31 มี.ค. ${by})`;
        } else if (q === 3) {
            qStart = ceYear + '-04-01';
            qEnd = ceYear + '-06-30';
            qLabel = `ไตรมาส 3 (1 เม.ย. - 30 มิ.ย. ${by})`;
        } else {
            qStart = ceYear + '-07-01';
            qEnd = ceYear + '-09-30';
            qLabel = `ไตรมาส 4 (1 ก.ค. - 30 ก.ย. ${by})`;
        }

        const badge = document.getElementById('quarterInfoBadge');
        if (badge) {
            badge.textContent = qLabel;
        }
        return { qStart, qEnd, qLabel };
    }

    document.getElementById('filter_quarter').addEventListener('change', updateQuarterDates);
    document.getElementById('filter_budget_year').addEventListener('change', updateQuarterDates);

    // -------------------------------------------------------------
    // Process from HOSxP (ดึงข้อมูลและแสดงผลค้างที่ Modal ทันที)
    // -------------------------------------------------------------
    document.getElementById('btnProcessHosxp').addEventListener('click', function() {
        const btn = this;
        const startDate = document.getElementById('filter_start_date').value;
        const endDate = document.getElementById('filter_end_date').value;
        const budgetYear = document.getElementById('filter_budget_year').value;
        const quarter = document.getElementById('filter_quarter').value;
        const { qStart, qEnd } = updateQuarterDates();

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาระบุช่วงวันที่',
                text: 'กรุณาเลือกวันที่เริ่มต้นและสิ้นสุดสำหรับคำนวณข้อมูลประจำเดือน',
                confirmButtonColor: '#3b82f6'
            });
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังประมวลผล...';
        document.getElementById('lastUpdatedStatus').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> กำลังดึงข้อมูลจาก HOSxP...';

        fetch('{{ url("hosfin/reports/process_service_data") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                start_date: startDate,
                end_date: endDate,
                budget_year: budgetYear,
                quarter: quarter,
                quarter_start: qStart,
                quarter_end: qEnd
            })
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill fs-6"></i> ประมวลผล';

            if (res.success) {
                const data = res.data;

                // Populate each account code input
                for (const [code, val] of Object.entries(data)) {
                    const inputEl = document.getElementById(`amt_${code}`);
                    if (inputEl) {
                        if (code.startsWith('4')) {
                            inputEl.value = parseFloat(val).toFixed(4);
                        } else {
                            inputEl.value = parseInt(val).toLocaleString();
                        }
                        // Subtle highlight
                        inputEl.classList.add('bg-warning-subtle');
                        setTimeout(() => inputEl.classList.remove('bg-warning-subtle'), 1000);
                    }
                }

                const now = new Date();
                const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                const localTimestamp = `${now.getDate()} ${thaiMonths[now.getMonth()]} ${now.getFullYear() + 543} เวลา ${now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' })} น.`;
                const fetchTime = res.fetch_datetime || localTimestamp;

                document.getElementById('lastUpdatedStatus').innerHTML = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ดึงข้อมูลสำเร็จ:</span> <span class="text-dark fw-bold ms-1"><i class="bi bi-clock-history text-secondary me-1"></i>${fetchTime}</span>`;

                Swal.fire({
                    icon: 'success',
                    title: 'ประมวลผลสำเร็จ',
                    html: `ดึงข้อมูลเรียบร้อยแล้ว (${startDate} ถึง ${endDate})<div class="mt-2 pt-2 border-top text-muted small"><i class="bi bi-clock-history me-1 text-primary"></i> วันที่และเวลาที่ดึง: <strong class="text-dark">${fetchTime}</strong></div>`,
                    timer: 2200,
                    showConfirmButton: false
                });

            } else {
                document.getElementById('lastUpdatedStatus').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> ประมวลผลไม่สำเร็จ`;
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: res.message || 'ไม่สามารถประมวลผลข้อมูลได้',
                    confirmButtonColor: '#3b82f6'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill fs-6"></i> ประมวลผล';
            document.getElementById('lastUpdatedStatus').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> การเชื่อมต่อขัดข้อง`;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                text: err.message,
                confirmButtonColor: '#3b82f6'
            });
        });
    });

    // -------------------------------------------------------------
    // Copy Single Value
    // -------------------------------------------------------------
    window.copySingleValue = function(code) {
        const inp = document.getElementById(`amt_${code}`);
        if (inp && inp.value !== '') {
            const rawVal = inp.value.replace(/,/g, '').trim();
            navigator.clipboard.writeText(rawVal).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `คัดลอกรหัส ${code}: ${rawVal}`,
                    showConfirmButton: false,
                    timer: 1500
                });
            });
        }
    };

    // -------------------------------------------------------------
    // Print Report (พิมพ์รายงาน)
    // -------------------------------------------------------------
    document.getElementById('btnPrintReport').addEventListener('click', function() {
        const hospitalName = `{{ DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?? 'โรงพยาบาล' }}`.replace(/^"|"$/g, '');
        const startPicker = document.getElementById('filter_start_date_picker');
        const endPicker = document.getElementById('filter_end_date_picker');
        const startDisplay = startPicker ? startPicker.value : (document.getElementById('filter_start_date')?.value || '');
        const endDisplay = endPicker ? endPicker.value : (document.getElementById('filter_end_date')?.value || '');
        const quarterInfo = document.getElementById('quarterInfoBadge') ? document.getElementById('quarterInfoBadge').innerText : '';
        const now = new Date();
        const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const printTimestamp = `${now.getDate()} ${thaiMonths[now.getMonth()]} ${now.getFullYear() + 543} เวลา ${now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' })} น.`;

        let tableRowsHtml = '';
        @foreach($accounts as $code => $acc)
            {
                const inp = document.getElementById('amt_{{ $code }}');
                const amtVal = inp ? (inp.value || '0') : '0';
                tableRowsHtml += `
                    <tr>
                        <td style="text-align: center; font-family: monospace; font-weight: bold; font-size: 13px;">{{ $code }}</td>
                        <td style="text-align: left; font-size: 13px;">{{ $acc['name'] }}</td>
                        <td style="text-align: right; font-weight: bold; font-size: 13px;">${amtVal}</td>
                    </tr>
                `;
            }
        @endforeach

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            window.print();
            return;
        }

        printWindow.document.open();
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="th">
            <head>
                <meta charset="UTF-8">
                <title>รายงานข้อมูลบริการประกอบงบ - ${hospitalName}</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
                    @page {
                        size: A4 portrait;
                        margin: 12mm 15mm 15mm 15mm;
                    }
                    html, body {
                        height: 100%;
                    }
                    body {
                        font-family: 'Sarabun', sans-serif;
                        color: #1e293b;
                        margin: 0;
                        padding: 12px;
                        font-size: 13px;
                        display: flex;
                        flex-direction: column;
                        min-height: 100vh;
                        box-sizing: border-box;
                    }
                    .content-wrapper {
                        flex: 1 0 auto;
                    }
                    .page-footer {
                        flex-shrink: 0;
                        margin-top: auto;
                        padding-top: 6px;
                        border-top: 1px dotted #94a3b8;
                        font-size: 11px;
                        color: #64748b;
                        text-align: left;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 16px;
                        border-bottom: 2px solid #334155;
                        padding-bottom: 10px;
                    }
                    .header h2 {
                        margin: 0 0 4px 0;
                        font-size: 20px;
                        font-weight: 700;
                        color: #0f172a;
                    }
                    .header h3 {
                        margin: 0 0 6px 0;
                        font-size: 16px;
                        font-weight: 600;
                        color: #334155;
                    }
                    .header .meta {
                        font-size: 12.5px;
                        color: #475569;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    th, td {
                        border: 1px solid #94a3b8;
                        padding: 6px 10px;
                        font-size: 12.5px;
                    }
                    th {
                        background-color: #f1f5f9;
                        font-weight: 700;
                        text-align: center;
                        color: #0f172a;
                    }
                    tr:nth-child(even) td {
                        background-color: #f8fafc;
                    }
                    .signature-section {
                        margin-top: 40px;
                        display: flex;
                        justify-content: space-between;
                        page-break-inside: avoid;
                    }
                    .signature-box {
                        text-align: center;
                        width: 240px;
                    }
                    .signature-box p {
                        margin: 4px 0;
                    }
                    .no-print-bar {
                        text-align: center;
                        margin-bottom: 15px;
                    }
                    @media print {
                        .no-print-bar { display: none !important; }
                        body {
                            padding: 0;
                            padding-bottom: 25px;
                        }
                        .page-footer {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            margin-top: 0;
                            background: #ffffff;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="no-print-bar">
                    <button onclick="window.print()" style="padding: 7px 20px; font-size: 14px; font-weight: bold; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer;">พิมพ์รายงาน (Print)</button>
                    <button onclick="window.close()" style="padding: 7px 18px; font-size: 14px; background: #64748b; color: #fff; border: none; border-radius: 6px; cursor: pointer; margin-left: 8px;">ปิดหน้าต่าง</button>
                </div>

                <div class="content-wrapper">
                    <div class="header">
                        <h2>${hospitalName}</h2>
                        <h3>รายงานข้อมูลบริการประกอบงบ</h3>
                        <div class="meta">
                            ช่วงวันที่: <strong>${startDisplay}</strong> ถึง <strong>${endDisplay}</strong> 
                            ${quarterInfo ? ` | ${quarterInfo}` : ''}
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th style="width: 15%;">รหัสบัญชี</th>
                                <th style="width: 55%; text-align: left;">ชื่อบัญชี</th>
                                <th style="width: 30%; text-align: right;">จำนวน</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRowsHtml}
                        </tbody>
                    </table>

                    <div class="signature-section">
                        <div class="signature-box">
                            <br><br>
                            <p>ลงชื่อ........................................................</p>
                            <p>(........................................................)</p>
                            <p style="font-weight: 500;">ผู้จัดทำรายงาน</p>
                            <p>วันที่ ......./......./.......</p>
                        </div>
                        <div class="signature-box">
                            <br><br>
                            <p>ลงชื่อ........................................................</p>
                            <p>(........................................................)</p>
                            <p style="font-weight: 500;">ผู้ตรวจสอบ / รับรอง</p>
                            <p>วันที่ ......./......./.......</p>
                        </div>
                    </div>
                </div>

                <div class="page-footer">
                    วันที่พิมพ์รายงาน: <strong>${printTimestamp}</strong>
                </div>

                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                        }, 400);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    });

    // -------------------------------------------------------------
    // Export Excel
    // -------------------------------------------------------------
    document.getElementById('btnExportExcel').addEventListener('click', function() {
        const startDate = document.getElementById('filter_start_date').value;
        const endDate = document.getElementById('filter_end_date').value;

        const params = new URLSearchParams();
        params.append('start_date', startDate);
        params.append('end_date', endDate);

        const inputs = document.querySelectorAll('.input-amt');
        inputs.forEach(inp => {
            const code = inp.id.replace('amt_', '');
            const rawVal = inp.value.replace(/,/g, '').trim();
            if (rawVal !== '') {
                params.append(`items[${code}]`, rawVal);
            }
        });

        window.location.href = '{{ url("hosfin/reports/export_excel") }}?' + params.toString();
    });
});
</script>
@endsection
