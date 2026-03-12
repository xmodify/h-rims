@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4 mt-3">
    <!-- Page Header -->
    <div class="page-header-box mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-primary mb-1">
                <i class="bi bi-journal-check me-2"></i> ทะเบียนคุมลูกหนี้ค่ารักษาพยาบาล
            </h4>
            <p class="text-muted small mb-0">{{ $hospital_name }} ({{ $hospital_code }})</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="filter-group d-flex align-items-center">
                <span class="fw-bold text-muted small text-nowrap me-2">เลือกปีงบประมาณ</span>
                <select class="form-select form-select-sm shadow-sm" id="budget_year_select" style="width: 150px;">
                    @foreach($budget_year_select as $row)
                        <option value="{{ $row->LEAVE_YEAR_ID }}" {{ $budget_year == $row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                            {{ $row->LEAVE_YEAR_NAME }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-outline-secondary btn-sm shadow-sm rounded-pill px-3 me-2" id="initRowsBtn">
                <i class="bi bi-list-task me-1"></i> เตรียมชื่อผังบัญชี
            </button>
            <button class="btn btn-success btn-sm shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#ProcessModal">
                <i class="bi bi-gear-fill me-1"></i> ประมวลผลข้อมูล
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card dash-card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-3 px-4 pb-0">
            <ul class="nav nav-tabs-modern" id="monthTabs" role="tablist">
                @php
                    $months = [
                        ['no' => 1, 'name' => 'ตุลาคม', 'short' => 'ต.ค.'],
                        ['no' => 2, 'name' => 'พฤศจิกายน', 'short' => 'พ.ย.'],
                        ['no' => 3, 'name' => 'ธันวาคม', 'short' => 'ธ.ค.'],
                        ['no' => 4, 'name' => 'มกราคม', 'short' => 'ม.ค.'],
                        ['no' => 5, 'name' => 'กุมภาพันธ์', 'short' => 'ก.พ.'],
                        ['no' => 6, 'name' => 'มีนาคม', 'short' => 'มี.ค.'],
                        ['no' => 7, 'name' => 'เมษายน', 'short' => 'เม.ย.'],
                        ['no' => 8, 'name' => 'พฤษภาคม', 'short' => 'พ.ค.'],
                        ['no' => 9, 'name' => 'มิถุนายน', 'short' => 'มิ.ย.'],
                        ['no' => 10, 'name' => 'กรกฎาคม', 'short' => 'ก.ค.'],
                        ['no' => 11, 'name' => 'สิงหาคม', 'short' => 'ส.ค.'],
                        ['no' => 12, 'name' => 'กันยายน', 'short' => 'ก.ย.'],
                    ];
                    
                    // หา month_no ปัจจุบันตามปีงบประมาณไทย (เริ่มต.ค.)
                    $curr_m = (int)date('n');
                    $curr_month_no = ($curr_m >= 10) ? $curr_m - 9 : $curr_m + 3;
                @endphp
                @foreach($months as $m)
                    @php
                        // Calculate year for each month
                        // For Oct(1), Nov(2), Dec(3) -> year = budget_year - 1
                        // For others -> year = budget_year
                        $year_for_label = ($m['no'] <= 3) ? ($budget_year - 1) : $budget_year;
                    @endphp
                    <li class="nav-item tab-item-shadow" role="presentation">
                        <button class="nav-link {{ $curr_month_no == $m['no'] ? 'active' : '' }}" 
                                id="tab-{{ $m['no'] }}" 
                                data-bs-toggle="pill" 
                                data-bs-target="#m-{{ $m['no'] }}" 
                                type="button" 
                                role="tab"
                                data-month-no="{{ $m['no'] }}">
                            {{ $m['short'] }} <span class="tab-year-label" data-month-no="{{ $m['no'] }}">{{ $year_for_label }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content">
                @foreach($months as $m)
                    <div class="tab-pane fade {{ $curr_month_no == $m['no'] ? 'show active' : '' }}" id="m-{{ $m['no'] }}" role="tabpanel">
                        @php
                            $year_for_label = ($m['no'] <= 3) ? ($budget_year - 1) : $budget_year;
                        @endphp
                        <div class="p-4 bg-light-soft border-bottom h6 mb-0 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-calendar-event me-2"></i>รายงานประจำเดือน {{ $m['name'] }} <span class="report-year-label" data-month-no="{{ $m['no'] }}">{{ $year_for_label }}</span></span>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-outline-danger btn-sm rounded-pill btn-export-pdf" data-month-no="{{ $m['no'] }}">
                                    <i class="bi bi-file-pdf me-1"></i> พิมพ์ PDF
                                </button>
                                <button class="btn btn-outline-success btn-sm rounded-pill btn-export-excel" data-month-no="{{ $m['no'] }}">
                                    <i class="bi bi-file-earmark-excel me-1"></i> ส่งออก Excel
                                </button>
                                <div id="status-{{ $m['no'] }}" class="small text-muted ms-2"></div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-modern table-hover mb-0 sticky-header" id="table-{{ $m['no'] }}">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 120px;">รหัสบัญชี</th>
                                        <th class="text-start">ชื่อผังบัญชี</th>
                                        <th class="text-end" style="width: 110px;">ยอดยกมา</th>
                                        <th class="text-end" style="width: 110px;">ตั้งหนี้</th>
                                        <th class="text-end" style="width: 110px;">ล้างหนี้/รับ</th>
                                        <th class="text-end" style="width: 110px;">ยอดปรับลด</th>
                                        <th class="text-end" style="width: 110px;">ยอดปรับเพิ่ม</th>
                                        <th class="text-end text-primary fw-bold" style="width: 130px;">คงเหลือยกไป</th>
                                        <th class="text-end text-success" style="width: 110px;">≤ 90 วัน</th>
                                        <th class="text-end text-warning" style="width: 110px;">91-365 วัน</th>
                                        <th class="text-end text-danger" style="width: 110px;">> 365 วัน</th>
                                        <th class="text-center" style="width: 60px;">#</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-{{ $m['no'] }}">
                                    <!-- AJAX Data -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Modal Adjustment -->
<div class="modal fade" id="AdjustmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="adjustmentForm" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            @csrf
            <div class="modal-header bg-primary text-white py-3 border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i> ปรับปรุงยอดบัญชี
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
                    <h6 class="fw-bold mb-1" id="adj_acc_name">-</h6>
                    <small id="adj_acc_code" class="text-muted">-</small>
                </div>

                <input type="hidden" name="budget_year" id="adj_budget_year">
                <input type="hidden" name="month_no" id="adj_month_no">
                <input type="hidden" name="acc_code" id="adj_acc_code_val">

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted">ยอดยกมา (Manual)</label>
                        <input type="number" step="0.01" class="form-control" name="balance_old" id="adj_balance_old">
                        <div class="form-text small">ใช้กรณีตั้งต้นยอดยกมาครั้งแรก</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted text-primary">ยอดล้างหนี้/รับชำระ</label>
                        <input type="number" step="0.01" class="form-control border-primary" name="debt_receive" id="adj_debt_receive">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold small text-muted text-success">ยอดปรับเพิ่ม (+)</label>
                        <input type="number" step="0.01" class="form-control border-success" name="debt_adj_inc" id="adj_debt_adj_inc">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold small text-muted text-danger">ยอดปรับลด (-)</label>
                        <input type="number" step="0.01" class="form-control border-danger" name="debt_adj_dec" id="adj_debt_adj_dec">
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-bold small text-muted">หมายเหตุการปรับปรุง</label>
                    <textarea class="form-control" name="adj_note" id="adj_note" rows="2" placeholder="ระบุเหตุผลในการปรับปรุงยอด..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 p-4">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary px-4 rounded-pill shadow">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Process Selection -->
<div class="modal fade" id="ProcessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="processForm" class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            @csrf
            <div class="modal-header bg-success text-white py-3 border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-cpu-fill me-2"></i> เลือกเดือนที่ต้องการประมวลผล
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted mb-4">ระบบจะทำการรวบรวมข้อมูลลูกหนี้จากตารางย่อยมาสรุปให้รายผังบัญชี</p>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted d-block text-start text-center">เลือกเดือนที่ต้องการสรุปยอด</label>
                        <select class="form-select form-select-lg border-success text-center fw-bold" name="month_no" id="proc_month_no" style="border-radius: 12px;">
                            @foreach($months as $m)
                                @php
                                    $year_label = ($m['no'] <= 3) ? ($budget_year - 1) : $budget_year;
                                @endphp
                                <option value="{{ $m['no'] }}" {{ $curr_month_no == $m['no'] ? 'selected' : '' }} data-month-name="{{ $m['name'] }}">
                                    {{ $m['name'] }} {{ $year_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 p-4 justify-content-center">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-success px-5 rounded-pill shadow fw-bold">เริ่มประมวลผลเดือนนี้</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let currentMonthNo = '{{ $curr_month_no }}';
        
        // โหลดข้อมูลเดือนแรกที่เลือก
        loadLedger(currentMonthNo);

        // เปลี่ยน Tab
        $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
            let monthNo = $(e.target).data('month-no');
            loadLedger(monthNo);
        });

        // เปลี่ยนปีงบประมาณ
        $('#budget_year_select').change(function() {
            let budgetYear = parseInt($(this).val());
            updateTabYearLabels(budgetYear);
            let activeTab = $('.nav-tabs-modern .nav-link.active').data('month-no');
            loadLedger(activeTab);
        });

        function updateTabYearLabels(budgetYear) {
            $('.tab-year-label, .report-year-label').each(function() {
                let monthNo = $(this).data('month-no');
                let year = (monthNo <= 3) ? (budgetYear - 1) : budgetYear;
                $(this).text(year);
            });

            // Update modal select options
            $('#proc_month_no option').each(function() {
                let monthNo = parseInt($(this).val());
                let monthName = $(this).data('month-name');
                let year = (monthNo <= 3) ? (budgetYear - 1) : budgetYear;
                $(this).text(monthName + ' ' + year);
            });
        }

        function loadLedger(monthNo) {
            let year = $('#budget_year_select').val();
            let tbody = $('#tbody-' + monthNo);
            
            tbody.html('<tr><td colspan="12" class="text-center py-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted">กำลังดึงข้อมูล...</div></td></tr>');

            $.get(`{{ url('debtor/acc_ledger_data') }}`, {
                budget_year: year,
                month_no: monthNo
            }, function(res) {
                renderTable(monthNo, res);
            });
        }

        function renderTable(monthNo, data) {
            let tbody = $('#tbody-' + monthNo);
            let html = '';
            
            if(data.length === 0) {
                tbody.html('<tr><td colspan="12" class="text-center py-5 text-muted">ยังไม่มีข้อมูลสำหรับเดือนนี้ <br> กรุณากดปุ่มประมวลผลข้อมูล</td></tr>');
                return;
            }

            // จัดการยอดรวมท้ายตาราง
            let sums = {
                old: 0, new: 0, receive: 0, dec: 0, inc: 0, total: 0, a90: 0, a365: 0, aover: 0
            };

            data.forEach(row => {
                sums.old += parseFloat(row.balance_old || 0);
                sums.new += parseFloat(row.debt_new || 0);
                sums.receive += parseFloat(row.debt_receive || 0);
                sums.dec += parseFloat(row.debt_adj_dec || 0);
                sums.inc += parseFloat(row.debt_adj_inc || 0);
                sums.total += parseFloat(row.balance_total || 0);
                sums.a90 += parseFloat(row.aging_90 || 0);
                sums.a365 += parseFloat(row.aging_365 || 0);
                sums.aover += parseFloat(row.aging_over || 0);

                html += `
                    <tr>
                        <td class="text-center small text-muted">${row.acc_code}</td>
                        <td class="text-start fw-bold small text-dark">${row.acc_name}</td>
                        <td class="text-end small">${formatNum(row.balance_old)}</td>
                        <td class="text-end small">${formatNum(row.debt_new)}</td>
                        <td class="text-end small">${formatNum(row.debt_receive)}</td>
                        <td class="text-end small text-danger">${formatNum(row.debt_adj_dec)}</td>
                        <td class="text-end small text-success">${formatNum(row.debt_adj_inc)}</td>
                        <td class="text-end fw-bold text-primary small">${formatNum(row.balance_total)}</td>
                        <td class="text-end small text-success">${formatNum(row.aging_90)}</td>
                        <td class="text-end small text-warning">${formatNum(row.aging_365)}</td>
                        <td class="text-end small text-danger">${formatNum(row.aging_over)}</td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-outline-warning border-0 p-0 px-1" onclick='openAdjModal(${JSON.stringify(row)})'>
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            // แถวผลรวม
            html += `
                <tr class="bg-light-soft fw-bold">
                    <td colspan="2" class="text-end">รวมทั้งหมด:</td>
                    <td class="text-end small">${formatNum(sums.old)}</td>
                    <td class="text-end small">${formatNum(sums.new)}</td>
                    <td class="text-end small">${formatNum(sums.receive)}</td>
                    <td class="text-end small text-danger">${formatNum(sums.dec)}</td>
                    <td class="text-end small text-success">${formatNum(sums.inc)}</td>
                    <td class="text-end text-primary small">${formatNum(sums.total)}</td>
                    <td class="text-end small text-success">${formatNum(sums.a90)}</td>
                    <td class="text-end small text-warning">${formatNum(sums.a365)}</td>
                    <td class="text-end small text-danger">${formatNum(sums.aover)}</td>
                    <td></td>
                </tr>
            `;

            tbody.html(html);
        }

        window.openAdjModal = function(row) {
            $('#adj_acc_name').text(row.acc_name);
            $('#adj_acc_code').text(row.acc_code);
            $('#adj_budget_year').val(row.budget_year);
            $('#adj_month_no').val(row.month_no);
            $('#adj_acc_code_val').val(row.acc_code);
            $('#adj_balance_old').val(row.balance_old);
            $('#adj_debt_receive').val(row.debt_receive);
            $('#adj_debt_adj_inc').val(row.debt_adj_inc);
            $('#adj_debt_adj_dec').val(row.debt_adj_dec);
            $('#adj_note').val(row.adj_note || '');
            $('#AdjustmentModal').modal('show');
        }

        $('#adjustmentForm').submit(function(e) {
            e.preventDefault();
            Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            $.post(`{{ url('debtor/acc_ledger_save_adj') }}`, $(this).serialize(), function(res) {
                Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1500 });
                $('#AdjustmentModal').modal('hide');
                loadLedger($('#adj_month_no').val());
            });
        });

        // เตรียมชื่อผังบัญชี
        $('#initRowsBtn').click(function() {
            let year = $('#budget_year_select').val();
            let activeTab = $('.nav-tabs-modern .active').data('month-no');

            Swal.fire({
                title: 'เตรียมรายชื่อผังบัญชี?',
                text: "ระบบจะเพิ่มรหัสและชื่อผังบัญชีที่ยังไม่มีในเดือนนี้ให้ครบถ้วน (ยอดยังเป็น 0)",
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'กำลังดำเนินการ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    $.post(`{{ url('debtor/acc_ledger_init') }}`, {
                        _token: '{{ csrf_token() }}',
                        budget_year: year,
                        month_no: activeTab
                    }, function(res) {
                        Swal.fire('สำเร็จ', 'เตรียมรายชื่อผังบัญชีเรียบร้อยแล้ว', 'success');
                        loadLedger(activeTab);
                    });
                }
            });
        });

        // ย้ายการประมวลผลมาอยู่ใน Form Submit
        $('#processForm').submit(function(e) {
            e.preventDefault();
            let monthNo = $('#proc_month_no').val();
            let monthName = $("#proc_month_no option:selected").text();
            let year = $('#budget_year_select').val();

            Swal.fire({
                title: 'ยืนยันการประมวลผล?',
                text: `คุณกำลังจะสรุปยอดบัญชีของเดือน ${monthName}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#ProcessModal').modal('hide');
                    Swal.fire({
                        title: 'กำลังประมวลผล...',
                        html: `ระบบกำลังสรุปข้อมูลของเดือน <b>${monthName}</b> <br> กรุณารอสักครู่...`,
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    $.post(`{{ url('debtor/acc_ledger_process') }}`, {
                        _token: '{{ csrf_token() }}',
                        budget_year: year,
                        month_no: monthNo
                    }, function(res) {
                        Swal.fire('ประมวลผลสำเร็จ', `ดำเนินการสรุปข้อมูลเรียบร้อยแล้ว`, 'success');
                        
                        // เปลี่ยน Tab ไปยังเดือนที่ประมวลผลเสร็จ และโหลดข้อมูลใหม่
                        $(`#tab-${monthNo}`).tab('show');
                        loadLedger(monthNo);
                    }).fail(function() {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถประมวลผลข้อมูลได้ กรุณาลองใหม่', 'error');
                    });
                }
            });
        });

        // Export PDF
        $(document).on('click', '.btn-export-pdf', function() {
            let monthNo = $(this).data('month-no');
            let year = $('#budget_year_select').val();
            window.open(`{{ url('debtor/acc_ledger_pdf') }}?budget_year=${year}&month_no=${monthNo}`, '_blank');
        });

        // Export Excel
        $(document).on('click', '.btn-export-excel', function() {
            let monthNo = $(this).data('month-no');
            let year = $('#budget_year_select').val();
            window.location.href = `{{ url('debtor/acc_ledger_excel') }}?budget_year=${year}&month_no=${monthNo}`;
        });

        function formatNum(num) {
            return parseFloat(num || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });
</script>
<style>
    .nav-tabs-modern {
        border-bottom: 2px solid #e2e8f0;
        gap: 0;
        padding-bottom: 0;
    }
    .nav-tabs-modern .nav-link {
        border: none;
        color: #64748b;
        font-weight: 600;
        padding: 0.6rem 1.2rem;
        border-radius: 8px 8px 0 0;
        margin-bottom: -2px;
        position: relative;
        background: transparent;
        transition: all 0.2s ease;
    }
    .tab-item-shadow {
        background: #fff;
        border-radius: 8px 8px 0 0;
        border: 1px solid #e2e8f0;
        border-left: none;
    }
    .tab-item-shadow:first-child {
        border-left: 1px solid #e2e8f0;
    }
    .nav-tabs-modern .nav-link.active {
        color: #2f855a;
        background: #f0fff4;
        border-bottom: 2px solid #38a169;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .nav-tabs-modern .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 3px;
        background: #38a169;
        border-radius: 3px;
    }
    .bg-light-soft { background-color: #f8fafc; }
    .table-modern thead th {
        background-color: #f8fafc;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.025em;
        color: #64748b;
        padding-top: 1rem;
        padding-bottom: 1rem;
        border-bottom-width: 2px;
    }
    .sticky-header thead th {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .btn-xs { padding: 1px 5px; font-size: 10px; }
</style>
@endpush
