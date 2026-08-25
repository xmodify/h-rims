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

<div class="container-fluid pt-2 pb-4 px-lg-5" style="background-color: #f8fafc;">
    <div class="row">
        <!-- Back Button -->
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
                        <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i> ข้อมูลบัญชีหน่วยงาน
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
                    <button type="button" class="btn btn-upload-custom d-flex align-items-center gap-1 text-nowrap shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-excel"></i> นำเข้างบทดลอง (Excel)
                    </button>

                    <!-- Import MDB Button -->
                    <button type="button" class="btn btn-primary d-flex align-items-center gap-1 text-nowrap shadow-sm" data-bs-toggle="modal" data-bs-target="#importMdbModal">
                        <i class="bi bi-database-fill-up"></i> นำเข้าข้อมูลบัญชีหน่วยงาน hfo (.zip)
                    </button>
                </div>
            </div>
        </div>



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
                                    <tr class="fw-bold align-middle" style="border-top: 2px solid #cbd5e1 !important; border-bottom: 2px solid #cbd5e1 !important; background-color: #e0f2fe !important;">
                                        <th colspan="3" class="text-center text-dark" style="background-color: #e0f2fe !important;">รวมทั้งสิ้น</th>
                                        <th class="text-end text-dark" style="background-color: #e0f2fe !important;">0.00</th>
                                        <th class="text-end text-dark" style="background-color: #e0f2fe !important;">0.00</th>
                                        <th class="text-end text-dark" style="background-color: #e0f2fe !important;">0.00</th>
                                        <th class="text-end text-dark" style="background-color: #e0f2fe !important;">0.00</th>
                                        <th class="text-end text-primary" style="background-color: #e0f2fe !important;">0.00</th>
                                        <th class="text-end text-danger" style="background-color: #e0f2fe !important;">0.00</th>
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

<!-- Import MDB Modal -->
<div class="modal fade" id="importMdbModal" tabindex="-1" aria-labelledby="importMdbModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-3 border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold" id="importMdbModalLabel" style="font-size: 0.95rem;">
                    <i class="bi bi-database-fill-up me-1"></i> นำเข้าข้อมูลบัญชีหน่วยงาน hfo (.zip)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Info Alert -->
                <div class="alert alert-info py-2 px-3 small border-0 d-flex gap-2 align-items-center mb-3">
                    <i class="bi bi-info-circle-fill text-info" style="font-size: 1.1rem;"></i>
                    <div style="font-size: 0.8rem;">
                        ระบบจะทำการวิเคราะห์ช่วงเวลาที่มีอยู่ในไฟล์งบกองเศรษฐกิจสุขภาพและหลักประกันสุขภาพ และแสดงตารางเลือกรายเดือนเพื่อเขียนทับฐานข้อมูล
                    </div>
                </div>

                <!-- File input -->
                <form id="mdbAnalyzeForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-end mb-3">
                        <div class="col-md-9 col-sm-12">
                            <label for="mdb_file" class="form-label fw-bold text-secondary mb-1" style="font-size: 0.8rem;">ไฟล์ฐานข้อมูลกองเศรษฐกิจสุขภาพและหลักประกันสุขภาพ (zip)</label>
                            <input type="file" class="form-control form-control-sm" id="mdb_file" name="file" accept=".zip" required>
                        </div>
                        <div class="col-md-3 col-sm-12 mt-2 mt-md-0">
                            <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold d-flex align-items-center justify-content-center gap-1 py-15">
                                <span class="spinner-border spinner-border-sm d-none" id="analyzeSpinner" role="status" aria-hidden="true"></span>
                                วิเคราะห์ไฟล์
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Analysis Container -->
                <div id="mdbAnalysisContainer" class="d-none mt-2">
                    <hr class="text-muted opacity-25 my-3">
                    <h6 class="fw-bold mb-2 text-secondary" style="font-size: 0.85rem;">
                        <i class="bi bi-calendar3 me-1"></i> ตรวจพบงวดงบประมาณที่มีอยู่ดังนี้:
                    </h6>
                    
                    <div class="table-responsive rounded-3 border" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover table-striped mb-0 table-custom table-sm" id="mdbPeriodsTable" style="font-size: 0.82rem;">
                            <thead class="sticky-top bg-light" style="z-index: 5;">
                                <tr>
                                    <th class="py-2">รอบเวลา (พ.ศ.)</th>
                                    <th class="text-end py-2">จำนวนแถวข้อมูล</th>
                                    <th class="text-center py-2" style="width: 150px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be filled by AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
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
            pageLength: 10,
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

    // Handle MDB File Analysis Form Submission
    $('#mdbAnalyzeForm').on('submit', function(e) {
        e.preventDefault();
        
        var fileInput = $('#mdb_file')[0];
        if (fileInput.files.length === 0) {
            Swal.fire({ icon: 'warning', title: 'คำเตือน', text: 'กรุณาเลือกไฟล์ก่อนทำการวิเคราะห์' });
            return;
        }
        
        var file = fileInput.files[0];
        var filename = file.name;
        var ext = filename.split('.').pop().toLowerCase();
        
        if (ext !== 'zip') {
            Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: 'กรุณาอัปโหลดไฟล์บีบอัด .zip เท่านั้น' });
            return;
        }
        
        if (!filename.toUpperCase().startsWith('D')) {
            Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: 'ชื่อไฟล์ zip ต้องขึ้นต้นด้วยตัวอักษร D เท่านั้น (เช่น D1625_xxx.zip)' });
            return;
        }
        
        var formData = new FormData(this);
        $('#analyzeSpinner').removeClass('d-none');
        $('#mdbAnalyzeForm button[type="submit"]').prop('disabled', true);
        $('#mdbAnalysisContainer').addClass('d-none');
        $('#mdbPeriodsTable tbody').empty();

        $.ajax({
            url: "{{ route('hosfin.trial_balance.analyze_mdb') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $('#analyzeSpinner').addClass('d-none');
                $('#mdbAnalyzeForm button[type="submit"]').prop('disabled', false);

                if (res.success) {
                    currentMdbToken = res.temp_token;
                    
                    res.periods.forEach(function(p) {
                        var tr = `
                            <tr>
                                <td class="fw-semibold text-dark py-1 align-middle" style="font-size: 0.8rem;">${p.label}</td>
                                <td class="text-end fw-semibold text-secondary py-1 align-middle" style="font-size: 0.8rem;">${p.count.toLocaleString()} รายการ</td>
                                <td class="text-center py-1 align-middle">
                                    <button type="button" class="btn btn-success btn-sm rounded-pill px-2 py-1 btn-import-mdb-period" 
                                            data-pdate="${p.pdate}" data-period="${p.period}" data-label="${p.label}"
                                            style="font-size: 0.72rem; padding: 2px 8px !important; line-height: 1;">
                                        <i class="bi bi-file-earmark-arrow-up"></i> นำเข้า/เขียนทับ
                                    </button>
                                </td>
                            </tr>
                        `;
                        $('#mdbPeriodsTable tbody').append(tr);
                    });
                    
                    $('#mdbAnalysisContainer').removeClass('d-none');
                } else {
                    Swal.fire({ icon: 'error', title: 'ล้มเหลว', text: res.message });
                }
            },
            error: function(xhr) {
                $('#analyzeSpinner').addClass('d-none');
                $('#mdbAnalyzeForm button[type="submit"]').prop('disabled', false);
                
                if (xhr.responseJSON && xhr.responseJSON.is_python_missing) {
                    var guide = xhr.responseJSON.guide || {};
                    var stepsHtml = '';
                    if (guide.steps && guide.steps.length > 0) {
                        stepsHtml = '<ol class="text-start ps-3 small text-secondary mt-2 mb-3" style="line-height: 1.6;">' +
                            guide.steps.map(function(s) { return '<li class="mb-1">' + s + '</li>'; }).join('') +
                        '</ol>';
                    }
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'จำเป็นต้องติดตั้ง Python บนเซิร์ฟเวอร์',
                        html: `
                            <div class="text-start">
                                <p class="text-dark mb-2">${xhr.responseJSON.message}</p>
                                <div class="p-3 bg-light border rounded-3 text-start small">
                                    <div class="fw-bold text-primary mb-1"><i class="bi bi-info-circle-fill me-1"></i> คำแนะนำการติดตั้งสำหรับ XAMPP / Windows Server:</div>
                                    ${stepsHtml}
                                </div>
                                <div class="mt-3 text-center">
                                    <a href="https://www.python.org/downloads/" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> ไปยังหน้าดาวน์โหลด Python.org
                                    </a>
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'รับทราบ',
                        confirmButtonColor: '#0a4d2c',
                        width: 600
                    });
                    return;
                }

                var msg = 'เกิดข้อผิดพลาดในการวิเคราะห์ไฟล์';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: msg });
            }
        });
    });

    // Flag to track if we need to reload the page when the modal is closed
    window.shouldRefreshTrialBalance = false;

    // Handle individual MDB Period Import
    $(document).on('click', '.btn-import-mdb-period', function() {
        var $btn = $(this);
        var pdate = $btn.data('pdate');
        var period = $btn.data('period');
        var label = $btn.data('label');
        
        Swal.fire({
            title: 'ยืนยันการนำเข้าข้อมูล?',
            text: 'ระบบจะนำข้อมูลของเดือน ' + label + ' จากไฟล์งบกองเศรษฐกิจสุขภาพและหลักประกันสุขภาพไปเขียนทับและบันทึกในฐานข้อมูลหลัก ต้องการดำเนินการต่อหรือไม่?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'ใช่, ต้องการนำเข้า!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable all import buttons to prevent duplicate imports
                $('.btn-import-mdb-period').prop('disabled', true);
                
                // Show inline loading state on the button
                var originalHtml = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังนำเข้า...');
                $btn.removeClass('btn-success').addClass('btn-secondary');
                
                $.ajax({
                    url: "{{ route('hosfin.trial_balance.import_mdb_period') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        temp_token: currentMdbToken,
                        pdate: pdate,
                        period: period
                    },
                    success: function(res) {
                        if (res.success) {
                            // Mark as successfully imported
                            $btn.html('<i class="bi bi-check-lg"></i> นำเข้าแล้ว');
                            $btn.removeClass('btn-secondary').addClass('btn-outline-secondary');
                            $btn.prop('disabled', true);
                            
                            // Success Toast (non-blocking)
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'นำเข้าข้อมูล ' + label + ' สำเร็จ',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                            
                            window.shouldRefreshTrialBalance = true;
                        } else {
                            // Restore button
                            $btn.html(originalHtml);
                            $btn.removeClass('btn-secondary').addClass('btn-success');
                            $btn.prop('disabled', false);
                            Swal.fire({ icon: 'error', title: 'ล้มเหลว', text: res.message });
                        }
                        
                        // Re-enable other non-imported buttons
                        $('.btn-import-mdb-period').each(function() {
                            var $otherBtn = $(this);
                            if (!$otherBtn.hasClass('btn-outline-secondary')) {
                                $otherBtn.prop('disabled', false);
                            }
                        });
                    },
                    error: function(xhr) {
                        // Restore button
                        $btn.html(originalHtml);
                        $btn.removeClass('btn-secondary').addClass('btn-success');
                        $btn.prop('disabled', false);
                        
                        // Re-enable other non-imported buttons
                        $('.btn-import-mdb-period').each(function() {
                            var $otherBtn = $(this);
                            if (!$otherBtn.hasClass('btn-outline-secondary')) {
                                $otherBtn.prop('disabled', false);
                            }
                        });
                        
                        if (xhr.responseJSON && xhr.responseJSON.is_python_missing) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'จำเป็นต้องติดตั้ง Python บนเซิร์ฟเวอร์',
                                text: xhr.responseJSON.message,
                                confirmButtonText: 'รับทราบ',
                                confirmButtonColor: '#0a4d2c'
                            });
                            return;
                        }

                        var msg = 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: msg });
                    }
                });
            }
        });
    });

    // Reload page when closing the modal if at least one import succeeded
    $('#importMdbModal').on('hidden.bs.modal', function () {
        if (window.shouldRefreshTrialBalance) {
            window.location.reload();
        }
    });
});

// Variable to store temp token of analyzed MDB file
var currentMdbToken = '';

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


@endsection
