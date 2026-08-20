@extends('layouts.app')

@section('content')
<style>
  .nav-tabs-custom {
    border-bottom: 2px solid #e2e8f0;
  }
  .nav-tabs-custom .nav-link {
    border: none;
    color: #64748b;
    font-weight: 500;
    padding: 10px 16px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
  }
  .nav-tabs-custom .nav-link:hover {
    color: #0f172a;
    border-bottom-color: #cbd5e1;
  }
  .nav-tabs-custom .nav-link.active {
    color: #10b981;
    border-bottom-color: #10b981;
    background: none;
  }
  .status-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    margin-left: 5px;
    vertical-align: middle;
  }
  .table-custom th {
    background-color: #f8fafc !important;
    color: #475569;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0 !important;
  }
  .table-custom td {
    vertical-align: middle;
  }
  .btn-upload-custom {
    background-color: #10b981;
    border-color: #10b981;
    color: #fff;
  }
  .btn-upload-custom:hover {
    background-color: #059669;
    border-color: #059669;
    color: #fff;
  }
  .category-summary-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
  }
  .active-card .category-summary-card {
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
      transform: translateY(-3px);
  }
</style>

<div class="container-fluid py-4 px-lg-5" style="background-color: #f8fafc;">
    <div class="row">
        <!-- Header -->
        <div class="col-12 px-3 mb-3">
            <div class="page-header-box mt-2" style="border-left-color: #10b981 !important;">
                <div>
                    <h5 class="text-primary mb-0 fw-bold">
                        <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i> ระบบจัดการงบทดลอง (Trial Balance)
                    </h5>
                    <small class="text-muted">นำเข้าไฟล์และเรียกดูรายงานงบทดลองประจำแต่ละเดือนแยกตามปีงบประมาณ</small>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <!-- Budget Year Dropdown -->
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted" style="font-size: 0.9rem;">ปีงบประมาณ</span>
                        <select id="select_budget_year" class="form-select" style="min-width: 100px; font-size: 0.9rem;">
                            @foreach($yearChoices as $yr)
                                <option value="{{ $yr }}" {{ $budgetYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Hidden input for category selection to keep JS functionality working -->
                    <input type="hidden" id="select_category" value="all">
                    
                    <!-- Import Button -->
                    <button type="button" class="btn btn-upload-custom d-flex align-items-center gap-1 text-nowrap" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-arrow-up"></i> นำเข้างบทดลอง
                    </button>
                </div>
            </div>
        </div>

        @if(count($importedPeriods) > 0)
            <!-- Dynamic Trend Chart Section -->
            <div class="col-12 px-3 mb-3">
                <div class="card card-trend border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up text-primary me-1"></i> กราฟเปรียบเทียบแนวโน้มรายรับ - รายจ่ายประจำแต่ละเดือน</h6>
                                <small class="text-muted">เปรียบเทียบยอดคงเหลือสะสมรายเดือน หมวดรายได้ (Revenue) และหมวดค่าใช้จ่าย (Expenses) ปีงบประมาณ {{ $budgetYear }}</small>
                            </div>
                        </div>
                        <div id="categoryTrendChart" style="min-height: 280px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Period Tabs -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <ul class="nav nav-tabs nav-tabs-custom" id="periodTabs" role="tablist">
                        <!-- All year tab -->
                        <li class="nav-item">
                            <a class="nav-link {{ $selectedPeriod === 'all' ? 'active' : '' }}" 
                               href="{{ url('hosfin/trial_balance') }}?budget_year={{ $budgetYear }}&period=all">
                                รวมทั้งปี
                            </a>
                        </li>
                        <!-- Monthly tabs -->
                        @foreach($periods as $p)
                            @php
                                $hasData = in_array($p['period'], $importedPeriods);
                            @endphp
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedPeriod === $p['period'] ? 'active' : '' }}" 
                                   href="{{ url('hosfin/trial_balance') }}?budget_year={{ $budgetYear }}&period={{ $p['period'] }}">
                                    {{ $p['label'] }}
                                     @if($hasData)
                                         <span class="status-dot bg-success" title="มีข้อมูลนำเข้าแล้ว"></span>
                                     @else
                                         <span class="status-dot bg-secondary bg-opacity-25" title="ยังไม่มีข้อมูล"></span>
                                     @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="card-body p-4">
                    <!-- Tab Content Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            @php
                                $currentPeriodInfo = collect($periods)->firstWhere('period', $selectedPeriod);
                                $periodLabel = $currentPeriodInfo ? $currentPeriodInfo['label'] : $selectedPeriod;
                            @endphp
                            <h6 class="fw-bold mb-0 text-dark">
                                @if($selectedPeriod === 'all')
                                    <i class="bi bi-calculator me-1 text-primary"></i> ยอดเคลื่อนไหวสะสมปีงบประมาณ {{ $budgetYear }}
                                @else
                                    <i class="bi bi-calendar3 me-1 text-primary"></i> ข้อมูลประจำรอบบัญชี {{ $periodLabel }}
                                @endif
                            </h6>
                            @if($selectedPeriod !== 'all' && count($data) > 0)
                                <small class="text-muted">
                                    <i class="bi bi-file-earmark-check me-1"></i> ไฟล์อ้างอิง: {{ $data[0]->import_filename }}
                                </small>
                            @endif
                        </div>
                        
                        <!-- Actions inside Tab content -->
                        <div class="d-flex gap-2">
                            @if($selectedPeriod !== 'all' && count($data) > 0)
                                <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" onclick="deletePeriod('{{ $selectedPeriod }}', '{{ $periodLabel }}')">
                                    <i class="bi bi-trash"></i> ลบข้อมูลรอบนี้
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Data Table -->
                    @if(count($data) > 0)
                        <!-- Category Summary Cards -->
                        <div class="row g-3 mb-4 flex-wrap">
                            @foreach($categorySums as $catId => $sum)
                                <div class="col-md col-sm-6 col-12 category-card-trigger" data-category="{{ $catId }}" style="cursor: pointer;">
                                    <div class="card shadow-sm border-0 h-100 border-top border-4 category-summary-card" style="border-top-color: {{ $sum['color'] }} !important; background: #fff; transition: all 0.2s;">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold text-dark" style="font-size: 0.85rem;">
                                                    {{ $catId }}. {{ $sum['label'] }}
                                                </span>
                                                <i class="bi {{ $sum['icon'] }}" style="color: {{ $sum['color'] }}; font-size: 1.1rem;"></i>
                                            </div>
                                            <div style="font-size: 0.75rem;" class="text-secondary">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span>ยอดยกมา:</span>
                                                    <span class="fw-semibold text-dark">
                                                        {{ number_format(abs($sum['bf']), 2) }}
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span>เดบิต:</span>
                                                    <span class="fw-semibold text-dark text-success">
                                                        {{ $sum['month_dr'] > 0 ? number_format($sum['month_dr'], 2) : '-' }}
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span>เครดิต:</span>
                                                    <span class="fw-semibold text-dark text-danger">
                                                        {{ $sum['month_cr'] > 0 ? number_format($sum['month_cr'], 2) : '-' }}
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                                    <span>ยอดคงเหลือ:</span>
                                                    <span class="fw-bold" style="color: {{ $sum['color'] }};">
                                                        {{ number_format(abs($sum['net']), 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="table-responsive">
                            <table id="trial_balance_table" class="table table-hover table-custom w-100" style="font-size: 0.9rem;">
                                <thead>
                                    <tr class="align-middle text-center">
                                        <th rowspan="2" style="width: 50px;">ลำดับ</th>
                                        <th rowspan="2" style="width: 150px;">รหัสผังบัญชี</th>
                                        <th rowspan="2">ชื่อผังบัญชี</th>
                                        <th colspan="2">ยอดยกมา</th>
                                        <th colspan="2">รับจ่ายเดือนนี้</th>
                                        <th colspan="2">ยอดคงเหลือ</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th class="text-end" style="width: 120px; border-bottom: 2px solid #e2e8f0 !important;">เดบิต</th>
                                        <th class="text-end" style="width: 120px; border-bottom: 2px solid #e2e8f0 !important;">เครดิต</th>
                                        <th class="text-end" style="width: 120px; border-bottom: 2px solid #e2e8f0 !important;">เดบิต</th>
                                        <th class="text-end" style="width: 120px; border-bottom: 2px solid #e2e8f0 !important;">เครดิต</th>
                                        <th class="text-end" style="width: 120px; border-bottom: 2px solid #e2e8f0 !important;">เดบิต</th>
                                        <th class="text-end" style="width: 120px; border-bottom: 2px solid #e2e8f0 !important;">เครดิต</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data as $index => $row)
                                        <tr>
                                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                                            <td class="fw-bold text-secondary">{{ $row->account_code }}</td>
                                            <td>{{ $row->account_name }}</td>
                                            
                                            <!-- ยอดยกมา -->
                                            <td class="text-end {{ $row->debit_bf > 0 ? 'text-dark fw-semibold' : 'text-muted bg-light bg-opacity-10' }}">
                                                {{ $row->debit_bf > 0 ? number_format($row->debit_bf, 2) : '-' }}
                                            </td>
                                            <td class="text-end {{ $row->credit_bf > 0 ? 'text-dark fw-semibold' : 'text-muted bg-light bg-opacity-10' }}">
                                                {{ $row->credit_bf > 0 ? number_format($row->credit_bf, 2) : '-' }}
                                            </td>
                                            
                                            <!-- รับจ่ายเดือนนี้ -->
                                            <td class="text-end {{ $row->debit_month > 0 ? 'text-dark' : 'text-muted bg-light bg-opacity-10' }}">
                                                {{ $row->debit_month > 0 ? number_format($row->debit_month, 2) : '-' }}
                                            </td>
                                            <td class="text-end {{ $row->credit_month > 0 ? 'text-dark' : 'text-muted bg-light bg-opacity-10' }}">
                                                {{ $row->credit_month > 0 ? number_format($row->credit_month, 2) : '-' }}
                                            </td>
                                            
                                            <!-- ยอดคงเหลือ -->
                                            <td class="text-end fw-bold text-primary {{ $row->debit_net > 0 ? '' : 'text-muted bg-light bg-opacity-10' }}">
                                                {{ $row->debit_net > 0 ? number_format($row->debit_net, 2) : '-' }}
                                            </td>
                                            <td class="text-end fw-bold text-danger {{ $row->credit_net > 0 ? '' : 'text-muted bg-light bg-opacity-10' }}">
                                                {{ $row->credit_net > 0 ? number_format($row->credit_net, 2) : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold bg-light align-middle" style="border-top: 2px solid #cbd5e1 !important; border-bottom: 2px solid #cbd5e1 !important;">
                                        <th colspan="3" class="text-center bg-light">รวมทั้งสิ้น</th>
                                        <th class="text-end bg-light text-dark">0.00</th>
                                        <th class="text-end bg-light text-dark">0.00</th>
                                        <th class="text-end bg-light text-dark">0.00</th>
                                        <th class="text-end bg-light text-dark">0.00</th>
                                        <th class="text-end bg-light text-primary">0.00</th>
                                        <th class="text-end bg-light text-danger">0.00</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <!-- No Data View -->
                        <div class="text-center py-5 my-4 bg-light rounded-3 border border-dashed border-2">
                            <div class="mb-3 text-muted">
                                <i class="bi bi-file-earmark-excel" style="font-size: 3.5rem;"></i>
                            </div>
                            <h5 class="fw-bold text-secondary">ไม่พบข้อมูลในรอบนี้</h5>
                            <p class="text-muted mb-4 small">
                                ยังไม่มีการนำเข้างบทดลองในรอบบัญชีนี้ หรือ ข้อมูลถูกลบออกไปแล้ว
                            </p>
                            @if($selectedPeriod !== 'all')
                                <button type="button" class="btn btn-success rounded-pill px-4" onclick="openImportModalWithPeriod('{{ $selectedPeriod }}')">
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i> เริ่มนำเข้าข้อมูลเดือนนี้
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-3 border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title fw-bold" id="importModalLabel">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> นำเข้าไฟล์งบทดลอง (Trial Balance)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <!-- Alert info -->
                    <div class="alert alert-info py-2 px-3 small border-0 d-flex gap-2 align-items-center mb-4">
                        <i class="bi bi-info-circle-fill text-info" style="font-size: 1.1rem;"></i>
                        <div>
                            หากมีข้อมูลเดือนเดิมซ้ำ ระบบจะทำการเขียนทับข้อมูลชุดเก่าทันที
                        </div>
                    </div>

                    <!-- File input -->
                    <div class="mb-3">
                        <label for="file" class="form-label fw-bold text-secondary" style="font-size: 0.85rem;">ไฟล์ Excel งบทดลอง (.xls, .xlsx)</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xls,.xlsx" required>
                    </div>

                    <!-- Month and Year Row -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="import_month" class="form-label fw-bold text-secondary" style="font-size: 0.85rem;">เดือนรอบบัญชี</label>
                            <select class="form-select" id="import_month" name="import_month" required>
                                <option value="1">มกราคม</option>
                                <option value="2">กุมภาพันธ์</option>
                                <option value="3">มีนาคม</option>
                                <option value="4">เมษายน</option>
                                <option value="5">พฤษภาคม</option>
                                <option value="6">มิถุนายน</option>
                                <option value="7">กรกฎาคม</option>
                                <option value="8">สิงหาคม</option>
                                <option value="9">กันยายน</option>
                                <option value="10">ตุลาคม</option>
                                <option value="11">พฤศจิกายน</option>
                                <option value="12">ธันวาคม</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="import_year" class="form-label fw-bold text-secondary" style="font-size: 0.85rem;">ปีงบประมาณ (พ.ศ.)</label>
                            <select class="form-select" id="import_year" name="import_year" required>
                                @foreach($yearChoices as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 d-flex align-items-center gap-2">
                        <span class="spinner-border spinner-border-sm d-none" id="importSpinner" role="status" aria-hidden="true"></span>
                        ยืนยันการนำเข้า
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTable
    if ($('#trial_balance_table').length > 0) {
        var table = $('#trial_balance_table').DataTable({
            language: {
                search: "ค้นหาบัญชี:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                paginate: {
                    first: "หน้าแรก",
                    last: "หน้าสุดท้าย",
                    next: "ถัดไป",
                    previous: "ก่อนหน้า"
                },
                zeroRecords: "ไม่พบข้อมูลรายการที่ค้นหา"
            },
            pageLength: 50,
            ordering: true,
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();

                // Convert formatted strings to numbers
                var intVal = function (i) {
                    if (typeof i === 'string') {
                        var cleaned = i.replace(/[\$,]/g, '').trim();
                        if (cleaned === '-') return 0;
                        return parseFloat(cleaned) || 0;
                    }
                    return typeof i === 'number' ? i : 0;
                };

                // Sum columns for visible (filtered) rows
                var sumCol = function(colIndex) {
                    return api
                        .column(colIndex, { filter: 'applied' })
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                };

                // Compute total for each column
                var totalDebitBf = sumCol(3);
                var totalCreditBf = sumCol(4);
                var totalDebitMonth = sumCol(5);
                var totalCreditMonth = sumCol(6);
                var totalDebitNet = sumCol(7);
                var totalCreditNet = sumCol(8);

                // Helper to format currency values
                var formatCurr = function(val) {
                    if (val === 0) return '-';
                    return val.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                // Update footer HTML values
                $(api.column(3).footer()).html(formatCurr(totalDebitBf));
                $(api.column(4).footer()).html(formatCurr(totalCreditBf));
                $(api.column(5).footer()).html(formatCurr(totalDebitMonth));
                $(api.column(6).footer()).html(formatCurr(totalCreditMonth));
                $(api.column(7).footer()).html(formatCurr(totalDebitNet));
                $(api.column(8).footer()).html(formatCurr(totalCreditNet));
            }
        });

        // Custom search function for account category
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var selectedCat = $('#select_category').val();
                if (selectedCat === 'all') return true;
                
                var accCode = data[1] || ''; // Column index 1 is account_code
                accCode = accCode.trim();
                return accCode.startsWith(selectedCat);
            }
        );

        // Re-draw table when category selection changes
        $('#select_category').on('change', function() {
            var selectedCat = $(this).val();
            table.draw();

            // Highlight the selected card and dim other cards
            $('.category-card-trigger').removeClass('active-card').css('opacity', '1');
            if (selectedCat !== 'all') {
                $('.category-card-trigger').css('opacity', '0.55');
                var $activeTrigger = $('.category-card-trigger[data-category="' + selectedCat + '"]');
                $activeTrigger.css('opacity', '1').addClass('active-card');
            }
        });

        // Handle category card click
        $('.category-card-trigger').on('click', function() {
            var clickedCat = $(this).data('category');
            var current = $('#select_category').val();

            // Toggle filter: if already active, clear filter, else apply
            if (current == clickedCat) {
                $('#select_category').val('all').trigger('change');
            } else {
                $('#select_category').val(clickedCat).trigger('change');
            }
        });
    }

    // Handle Budget Year selector change
    $('#select_budget_year').on('change', function() {
        var yr = $(this).val();
        window.location.href = "{{ url('hosfin/trial_balance') }}?budget_year=" + yr + "&period=all";
    });

    // Auto-detect month and year from filename on upload
    $('#file').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;

        var filename = file.name;
        
        // Month list to match
        var thaiMonths = [
            { names: ['ม.ค.', 'มกรา', 'january', 'jan'], value: 1 },
            { names: ['ก.พ.', 'กุมภา', 'february', 'feb'], value: 2 },
            { names: ['มี.ค.', 'มีนา', 'march', 'mar'], value: 3 },
            { names: ['เม.ย.', 'เมษา', 'april', 'apr'], value: 4 },
            { names: ['พ.ค.', 'พฤษภา', 'may'], value: 5 },
            { names: ['มิ.ย.', 'มิถุนา', 'june', 'jun'], value: 6 },
            { names: ['ก.ค.', 'กรกฎา', 'july', 'jul'], value: 7 },
            { names: ['ส.ค.', 'สิงหา', 'august', 'aug'], value: 8 },
            { names: ['ก.ย.', 'กันยา', 'september', 'sep'], value: 9 },
            { names: ['ต.ค.', 'ตุลา', 'october', 'oct'], value: 10 },
            { names: ['พ.ย.', 'พฤศจิกา', 'november', 'nov'], value: 11 },
            { names: ['ธ.ค.', 'ธันวา', 'december', 'dec'], value: 12 }
        ];

        // Parse Month
        var matchedMonth = null;
        for (var i = 0; i < thaiMonths.length; i++) {
            var item = thaiMonths[i];
            for (var j = 0; j < item.names.length; j++) {
                if (filename.toLowerCase().indexOf(item.names[j]) !== -1) {
                    matchedMonth = item.value;
                    break;
                }
            }
            if (matchedMonth) break;
        }

        if (matchedMonth) {
            $('#import_month').val(matchedMonth);
        }

        // Parse Year
        var matchedYear = null;
        var yearMatches = filename.match(/(25\d{2}|\b\d{2}\b)/); // Matches 25xx or standalone xx
        if (yearMatches) {
            var val = parseInt(yearMatches[0]);
            if (val >= 2500 && val <= 2600) {
                matchedYear = val;
            } else if (val >= 60 && val <= 99) { // Short BE format e.g. 69
                matchedYear = 2500 + val;
            }
        }

        if (matchedYear) {
            // Convert parsed calendar year to Thai budget year based on the parsed month
            var budgetYear = matchedYear;
            if (matchedMonth && matchedMonth >= 10) {
                budgetYear = matchedYear + 1;
            }
            
            // Check if budget year exists in dropdown, if so, select it
            if ($('#import_year option[value="' + budgetYear + '"]').length > 0) {
                $('#import_year').val(budgetYear);
            }
        }
    });

    // Handle AJAX Form Submission
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        $('#importSpinner').removeClass('d-none');
        $('button[type="submit"]').prop('disabled', true);

        $.ajax({
            url: "{{ url('hosfin/trial_balance/import') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#importSpinner').addClass('d-none');
                $('button[type="submit"]').prop('disabled', false);
                $('#importModal').modal('hide');

                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        var month = parseInt($('#import_month').val());
                        var year = parseInt($('#import_year').val());
                        var calYear = (month >= 10) ? (year - 1) : year;
                        var period = calYear + '-' + (month < 10 ? '0' + month : month);
                        window.location.href = "{{ url('hosfin/trial_balance') }}?budget_year=" + year + "&period=" + period;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ล้มเหลว',
                        text: res.message
                    });
                }
            },
            error: function(xhr) {
                $('#importSpinner').addClass('d-none');
                $('button[type="submit"]').prop('disabled', false);
                
                var msg = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: msg
                });
            }
        });
    });
});

// Helper: Open import modal with prefilled period
function openImportModalWithPeriod(period) {
    if (period && period !== 'all') {
        var parts = period.split('-');
        var yr = parseInt(parts[0]);
        var mo = parseInt(parts[1]);
        
        $('#import_month').val(mo);
        if ($('#import_year option[value="' + yr + '"]').length > 0) {
            $('#import_year').val(yr);
        }
    }
    $('#importModal').modal('show');
}

// Action: Delete period trial balance
function deletePeriod(period, label) {
    var displayLabel = label || period;
    Swal.fire({
        title: 'ยืนยันการลบข้อมูล?',
        text: 'ต้องการลบข้อมูลรายงานงบทดลองรอบบัญชี ' + displayLabel + ' หรือไม่? ข้อมูลทั้งหมดของเดือนนี้จะสูญหายทันที!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ต้องการลบ!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ url('hosfin/trial_balance/delete') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "DELETE",
                    period: period
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบสำเร็จ',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ล้มเหลว',
                            text: res.message
                        });
                    }
                },
                error: function(xhr) {
                    var msg = 'เกิดข้อผิดพลาดในการติดต่อเซิร์ฟเวอร์';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: msg
                    });
                }
            });
        }
    });
}
</script>

<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
    @if(count($importedPeriods) > 0)
        const chartDataMap = @json($chartData);
        const chartLabels = Object.keys(chartDataMap); // Monthly period labels
        
        document.addEventListener("DOMContentLoaded", () => {
            const revenuesData = chartLabels.map(label => chartDataMap[label][4] || 0.0);
            const expensesData = chartLabels.map(label => chartDataMap[label][5] || 0.0);

            const chartOptions = {
                series: [
                    {
                        name: 'รายได้',
                        data: revenuesData
                    },
                    {
                        name: 'ค่าใช้จ่าย',
                        data: expensesData
                    }
                ],
                chart: {
                    height: 280,
                    type: 'area',
                    toolbar: { show: false }
                },
                colors: ['#10b981', '#ef4444'], // Green for Revenue, Red for Expenses
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3, // Soft faint shadow, more visible
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                markers: {
                    size: 4,
                    hover: {
                        size: 6
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        if (val === 0) return '';
                        return val.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                    },
                    style: {
                        fontSize: '9px',
                        fontWeight: 'bold'
                    },
                    background: {
                        enabled: true,
                        foreColor: '#fff',
                        padding: 4,
                        borderRadius: 4,
                        borderWidth: 1,
                        borderColor: '#cbd5e1',
                        opacity: 0.95
                    }
                },
                xaxis: {
                    categories: chartLabels,
                    tooltip: {
                        enabled: true // Highlights X-axis label on hover
                    },
                    crosshairs: {
                        show: true,
                        width: 1,
                        position: 'back',
                        stroke: {
                            color: '#cbd5e1',
                            width: 1,
                            dashArray: 3
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            if (Math.abs(val) >= 1000000) {
                                return (val / 1000000).toFixed(1) + 'M';
                            }
                            if (Math.abs(val) >= 1000) {
                                return (val / 1000).toFixed(0) + 'K';
                            }
                            return val.toLocaleString();
                        }
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " บาท";
                        }
                    }
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'center',
                    fontFamily: 'inherit',
                    fontWeight: 'bold',
                    labels: {
                        colors: '#334155'
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#categoryTrendChart"), chartOptions);
            chart.render();
        });
    @endif
</script>
@endsection
