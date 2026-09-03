@extends('layouts.app')

@section('content')
<style>
    .cost-card {
        border-radius: 14px;
        transition: all 0.25s ease-in-out;
        border: 1px solid #e2e8f0;
    }
    .cost-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -6px rgba(0,0,0,0.08) !important;
    }
    .btn-action-custom {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.85rem;
        height: 42px;
        font-weight: 700;
    }
    .btn-action-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }
    /* DataTables Modern Styling */
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 20px;
        padding: 5px 14px;
        border: 1px solid #cbd5e1;
        font-size: 0.88rem;
        outline: none;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 10px;
        padding: 4px 8px;
        border: 1px solid #cbd5e1;
        font-size: 0.88rem;
    }
    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #10b981;
        border-color: #10b981;
    }
    .dataTables_wrapper .pagination .page-link {
        border-radius: 8px;
        margin: 0 2px;
        font-size: 0.85rem;
    }
    table.dataTable thead th {
        border-bottom: 2px solid #e2e8f0 !important;
        font-size: 0.84rem;
        white-space: nowrap;
    }
    .dt-buttons {
        margin-left: 6px;
    }
    .dt-buttons .btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        background-color: #16a34a !important;
        border-color: #16a34a !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        border-radius: 50rem !important;
        padding: 6px 16px !important;
        font-size: 0.85rem !important;
        box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2) !important;
        transition: all 0.2s ease-in-out !important;
    }
    .dt-buttons .btn:hover {
        background-color: #15803d !important;
        border-color: #15803d !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(22, 163, 74, 0.35) !important;
    }
    table.dataTable tfoot th {
        background-color: #f8fafc !important;
        font-weight: 800 !important;
        border-top: 2px solid #cbd5e1 !important;
        border-bottom: 2px solid #cbd5e1 !important;
        white-space: nowrap;
    }
</style>

<div class="container-fluid pt-2 pb-4 px-lg-5" style="background-color: #f8fafc; min-height: 100vh;">
    <!-- Back Button -->
    <div class="row">
        <div class="col-12 px-3 mb-1">
            <a href="{{ url('hosfin') }}" class="btn btn-outline-secondary btn-sm rounded-pill shadow-sm px-3 d-inline-flex align-items-center gap-2" style="font-size: 0.85rem; border-color: #cbd5e1; color: #475569; background-color: #fff;">
                <i class="bi bi-arrow-left"></i> ย้อนกลับ
            </a>
        </div>

        <!-- Header -->
        <div class="col-12 px-3 mb-3">
            <div class="page-header-box mt-2" style="border-left-color: #10b981 !important;">
                <div>
                    <h5 class="text-primary mb-0 fw-bold">
                        <i class="bi bi-pie-chart me-2 text-success"></i> รายงานวิเคราะห์โครงสร้างต้นทุนบริการ (LC / MC / CC)
                    </h5>
                    <small class="text-muted">สรุปสัดส่วนค่าแรง (Labor), ค่าวัสดุ (Material), ค่าลงทุน (Capital) สำหรับคำนวณต้นทุนต่อหน่วยบริการ (Unit Cost)</small>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap ms-lg-auto mt-2 mt-lg-0">
                    <a href="{{ url('hosfin/ap_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #ef4444; color: #dc2626;">
                        <i class="bi bi-receipt-cutoff"></i> เจ้าหนี้ (AP)
                    </a>
                    <a href="{{ url('hosfin/ar_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #0284c7; color: #0369a1;">
                        <i class="bi bi-wallet2"></i> ลูกหนี้ (AR)
                    </a>
                    <a href="{{ url('hosfin/cost_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #d97706; border: 1.5px solid #d97706; color: #ffffff;">
                        <i class="bi bi-pie-chart"></i> ต้นทุน (LC/MC/CC)
                    </a>
                    <a href="{{ url('hosfin/ratio_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #3b82f6; color: #2563eb;">
                        <i class="bi bi-graph-up-arrow"></i> อัตราส่วน
                    </a>
                    <a href="{{ url('hosfin/trial_balance') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #10b981; color: #059669;">
                        <i class="bi bi-file-earmark-spreadsheet"></i> งบทดลอง
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Highlight Cards (LC, MC, CC, Total) -->
    <div class="row g-3 px-3 mb-4">
        <!-- LC Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 cost-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">LC: ค่าแรงและบุคลากร (Labor Cost)</span>
                            <div class="fw-black mt-1 text-primary" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalLc, 2) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บาท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">หมวด 5101-5103</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fw-bold" style="font-size: 0.75rem;">
                            {{ $lcPercent }}% ของต้นทุน
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MC Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 cost-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">MC: ค่าวัสดุ/ยา/เวชภัณฑ์ (Material)</span>
                            <div class="fw-black mt-1 text-danger" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalMc, 2) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บาท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-danger bg-opacity-10 text-danger" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-capsule fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">หมวด 5104</span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill fw-bold" style="font-size: 0.75rem;">
                            {{ $mcPercent }}% ของต้นทุน
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CC Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 cost-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">CC: ค่าลงทุนและเสื่อมราคา (Capital)</span>
                            <div class="fw-black mt-1 text-warning-emphasis" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalCc, 2) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บาท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning-emphasis" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-building-gear fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">หมวด 5107, 5202</span>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill fw-bold" style="font-size: 0.75rem;">
                            {{ $ccPercent }}% ของต้นทุน
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Cost Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 cost-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">ต้นทุนรวมบริการ (Total Cost)</span>
                            <div class="fw-black mt-1 text-dark" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalCost, 2) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บาท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-dark bg-opacity-10 text-dark" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-dark-subtle text-dark border border-dark-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">LC + MC + CC</span>
                        <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.75rem;">100.0% โครงสร้างต้นทุน</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend Table -->
    <div class="row px-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-calendar3 me-2 text-primary"></i> การกระจายต้นทุน LC / MC / CC รายเดือน (Monthly Cost Distribution)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-4">งวดบัญชี</th>
                                <th>เดือน / ปีงบประมาณ</th>
                                <th class="text-end">LC: ค่าแรง (บาท)</th>
                                <th class="text-end">MC: ค่าวัสดุ (บาท)</th>
                                <th class="text-end">CC: ค่าลงทุน (บาท)</th>
                                <th class="text-end">อื่นๆ (บาท)</th>
                                <th class="text-end text-dark fw-bold pe-4">ต้นทุนรวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costSummaries as $cs)
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-primary">{{ $cs->acc_period }}</td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold">
                                            {{ $cs->period_label }}
                                        </span>
                                        <small class="text-muted ms-1">(เดือนที่ {{ $cs->fiscal_month }})</small>
                                    </td>
                                    <td class="text-end font-monospace text-primary">{{ number_format($cs->lc_amount, 2) }}</td>
                                    <td class="text-end font-monospace text-danger">{{ number_format($cs->mc_amount, 2) }}</td>
                                    <td class="text-end font-monospace text-success">{{ number_format($cs->cc_amount, 2) }}</td>
                                    <td class="text-end font-monospace text-muted">{{ number_format($cs->other_cost, 2) }}</td>
                                    <td class="text-end font-monospace fw-bold pe-4 text-dark fs-6">{{ number_format($cs->total_cost, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูลสรุปต้นทุนรายเดือน</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Accounts under หมวด 5 Table -->
    <div class="row px-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-stars me-2 text-warning"></i> ผังบัญชีค่าใช้จ่ายหลัก (Expense Accounts หมวด 5)</h6>
                        <small class="text-muted">แยกตามประเภทต้นทุน LC / MC / CC และบริการ Direct / Indirect Cost (สามารถคลิกหัวตารางเพื่อเรียงลำดับ หรือค้นหาได้ทันที)</small>
                    </div>
                    <div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5 fw-bold">
                            ค่าใช้จ่ายรวม {{ number_format($topAccounts->sum('net_expense'), 2) }} บาท
                        </span>
                    </div>
                </div>
                <div class="table-responsive p-2">
                    <table class="table table-hover align-middle mb-0 w-100" id="expenseAccountsTable">
                        <thead class="table-light">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-3 text-center" style="width: 50px;">#</th>
                                <th>รหัสบัญชี</th>
                                <th>ชื่อผังบัญชี</th>
                                <th class="text-center">ประเภทต้นทุน</th>
                                <th class="text-center">การบริการ</th>
                                <th class="text-center">จำนวนรายการ</th>
                                <th class="text-end text-dark pe-3">ค่าใช้จ่ายสุทธิ (Dr - Cr)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $accIdx = 1; @endphp
                            @foreach($topAccounts as $acc)
                                @php $exp = (float)$acc->net_expense; @endphp
                                <tr>
                                    <td class="ps-3 text-center fw-bold text-muted" data-order="{{ $accIdx }}">{{ $accIdx++ }}</td>
                                    <td class="font-monospace fw-bold text-primary">{{ $acc->account_code }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $acc->account_name }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($acc->cost_type === 'LC')
                                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1">LC: ค่าแรง</span>
                                        @elseif($acc->cost_type === 'MC')
                                            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1">MC: ค่าวัสดุ</span>
                                        @elseif($acc->cost_type === 'CC')
                                            <span class="badge bg-success text-white rounded-pill px-2.5 py-1">CC: ค่าลงทุน</span>
                                        @else
                                            <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1">ทั่วไป</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($acc->service_type === 'direct')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1">Direct (บริการตรง)</span>
                                        @else
                                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1">Indirect (สนับสนุน)</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-monospace" data-order="{{ $acc->tx_count }}">{{ number_format($acc->tx_count) }}</td>
                                    <td class="text-end font-monospace pe-3 fw-bold fs-6 text-dark" data-order="{{ $exp }}">
                                        {{ number_format($exp, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end fw-bold py-2.5 ps-3">รวมทั้งหมด (ตามที่กรอง):</th>
                                <th class="text-center font-monospace py-2.5" id="footTotalTx">0</th>
                                <th class="text-end font-monospace py-2.5 text-danger pe-3 fs-6" id="footTotalExpense">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined' && $.fn.DataTable) {
            var commonDom = '<"d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-bottom"<"d-flex align-items-center"l><"d-flex align-items-center gap-2"fB>>' +
                            'rt' +
                            '<"d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-top"<"text-muted small"i><"pagination-sm"p>>';

            var intVal = function (i) {
                if (typeof i === 'number') return i;
                if (typeof i === 'string') {
                    var stripped = i.replace(/<[^>]+>/g, '').replace(/,/g, '').trim();
                    return parseFloat(stripped) || 0;
                }
                return 0;
            };

            var fmt = function (num) {
                return num.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            var fmtInt = function (num) {
                return num.toLocaleString('th-TH');
            };

            if ($.fn.DataTable.isDataTable('#expenseAccountsTable')) {
                $('#expenseAccountsTable').DataTable().destroy();
            }

            $('#expenseAccountsTable').DataTable({
                dom: commonDom,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel-fill text-white fs-6"></i> ส่งออก Excel',
                        className: 'btn btn-success btn-sm rounded-pill px-3 shadow-sm text-white fw-bold',
                        title: 'ผังบัญชีค่าใช้จ่ายหลัก_HosFin_LC_MC_CC',
                        exportOptions: {
                            columns: ':visible',
                            footer: true
                        }
                    }
                ],
                language: {
                    search: "ค้นหา:",
                    searchPlaceholder: "พิมพ์ชื่อ หรือ รหัสบัญชี...",
                    lengthMenu: "แสดง _MENU_ บัญชี",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ บัญชี",
                    infoEmpty: "ไม่พบข้อมูลผังบัญชี",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ บัญชี)",
                    zeroRecords: "ไม่พบข้อมูลผังบัญชีที่ตรงกับคำค้นหา",
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
                    }
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                order: [[6, 'desc']], // Column 6: ค่าใช้จ่ายสุทธิ
                columnDefs: [
                    { targets: [0], orderable: false }
                ],
                autoWidth: false,
                footerCallback: function (row, data, start, end, display) {
                    try {
                        var api = this.api();
                        var totalTx = api.column(5, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                        var totalExp = api.column(6, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                        $('#footTotalTx').html(fmtInt(totalTx || 0));
                        $('#footTotalExpense').html(fmt(totalExp || 0));
                    } catch (e) {}
                }
            });
        }
    });
</script>
@endsection
