@extends('layouts.app')

@section('content')
<style>
  .planfin-card {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #ffffff;
    padding: 18px 22px !important;
  }
  .planfin-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px -6px rgba(0, 0, 0, 0.08);
  }
  .planfin-card-mini {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #ffffff;
    padding: 16px 20px !important;
  }
  .planfin-card-mini:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px -4px rgba(0, 0, 0, 0.06);
  }

  /* DataTables Modern Styling in Mapping Modal */
  #mappingModal .dataTables_wrapper .dataTables_filter input {
    border-radius: 20px;
    padding: 5px 14px;
    border: 1px solid #cbd5e1;
    font-size: 0.84rem;
    outline: none;
    transition: all 0.2s ease;
  }
  #mappingModal .dataTables_wrapper .dataTables_filter input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  }
  #mappingModal .dataTables_wrapper .dataTables_length select {
    border-radius: 10px;
    padding: 4px 10px;
    border: 1px solid #cbd5e1;
    font-size: 0.84rem;
  }
  #mappingModal .dataTables_wrapper .pagination .page-item.active .page-link {
    background-color: #3b82f6;
    border-color: #3b82f6;
  }
  #mappingModal .dataTables_wrapper .pagination .page-link {
    border-radius: 8px;
    margin: 0 2px;
    font-size: 0.82rem;
  }

  .fw-black { font-weight: 900 !important; }
  .table-planfin thead th {
    background-color: #f8fafc;
    color: #475569;
    font-size: 0.8rem;
    font-weight: 700;
    vertical-align: middle;
    border-bottom: 2px solid #e2e8f0;
  }
  .table-planfin td {
    font-size: 0.84rem;
    vertical-align: middle;
  }
  .row-revenue > td { background-color: rgba(16, 185, 129, 0.015); }
  .row-expense > td { background-color: rgba(239, 68, 68, 0.015); }
  
  /* 1. รวมรายได้สะสม (P13S) - เขียวมรกตพรีเมียม ชัดเจน */
  .table-planfin tr.row-summary-rev > td,
  .table-planfin tr.row-summary-rev > td.text-dark {
    background-color: #d1fae5 !important;
    color: #064e3b !important;
    font-weight: 800 !important;
    font-size: 0.88rem !important;
    border-top: 2px solid #059669 !important;
    border-bottom: 2px solid #059669 !important;
  }
  .table-planfin tr.row-summary-rev:hover > td {
    background-color: #a7f3d0 !important;
  }

  /* 2. รวมค่าใช้จ่ายสะสม (P26S) - แดงอ่อน/กุหลาบ ชัดเจน */
  .table-planfin tr.row-summary-exp > td,
  .table-planfin tr.row-summary-exp > td.text-dark {
    background-color: #fee2e2 !important;
    color: #7f1d1d !important;
    font-weight: 800 !important;
    font-size: 0.88rem !important;
    border-top: 2px solid #dc2626 !important;
    border-bottom: 2px solid #dc2626 !important;
  }
  .table-planfin tr.row-summary-exp:hover > td {
    background-color: #fecaca !important;
  }

  /* 3. รายได้สูง (ต่ำ) กว่าค่าใช้จ่ายสุทธิ (P27S) - เขียวน้ำทะเล/Teal พร้อมขอบล่างเส้นคู่แบบบัญชี */
  .table-planfin tr.row-summary-ni > td {
    background-color: #ccfbf1 !important;
    color: #115e59 !important;
    font-weight: 900 !important;
    font-size: 0.92rem !important;
    border-top: 2px solid #0d9488 !important;
    border-bottom: 3px double #0d9488 !important;
  }
  .table-planfin tr.row-summary-ni:hover > td {
    background-color: #99f6e4 !important;
  }

  /* 4. EBITDA (P29) - สีคราม/น้ำเงิน Indigo เด่นชัด */
  .table-planfin tr.row-summary-ebitda > td {
    background-color: #e0e7ff !important;
    color: #3730a3 !important;
    font-weight: 900 !important;
    font-size: 0.92rem !important;
    border-top: 2px solid #6366f1 !important;
    border-bottom: 2px solid #6366f1 !important;
  }
  .table-planfin tr.row-summary-ebitda:hover > td {
    background-color: #c7d2fe !important;
  }
  
  .badge-status-ok {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    font-size: 0.72rem;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    font-weight: 700;
  }
  .badge-status-not-ok {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    font-size: 0.72rem;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    font-weight: 700;
  }
  .input-plan70 {
    font-size: 0.84rem;
    font-weight: 600;
    text-align: right;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding: 0.3rem 0.6rem;
    background-color: #ffffff;
    transition: all 0.2s;
  }
  .input-plan70:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    background-color: #faf5ff;
  }
  .input-growth {
    width: 80px;
    font-size: 0.8rem;
    text-align: center;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding: 0.3rem 0.4rem;
  }
  .nav-tabs .nav-link {
    font-weight: 700;
    font-size: 0.88rem;
    color: #64748b;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 0.65rem 1.25rem;
  }
  .nav-tabs .nav-link.active {
    color: #4f46e5 !important;
    border-bottom: 3px solid #4f46e5 !important;
    background: transparent !important;
  }
  .sub-row-t1, .sub-row-t1m, .sub-row-t2 {
    background-color: #f8fafc !important;
    transition: all 0.15s ease;
    border-left: 4px solid #6366f1 !important;
  }
  .sub-row-t1:hover, .sub-row-t1m:hover, .sub-row-t2:hover {
    background-color: #f1f5f9 !important;
  }
  #tab1SubNav .nav-link {
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
    background-color: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 0.38rem 1rem;
    border-radius: 9999px;
    transition: all 0.2s ease;
  }
  #tab1SubNav .nav-link:hover {
    background-color: #f1f5f9;
    color: #1e293b;
    border-color: #94a3b8;
  }
  #tab1SubNav .nav-link.active {
    background-color: #4f46e5 !important;
    color: #ffffff !important;
    border-color: #4338ca !important;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
  }
  .col-matrix-active {
    background-color: #eff6ff !important;
    font-weight: 700 !important;
    color: #1e40af !important;
  }
  .matrix-plan-subtext {
    display: block;
    transition: all 0.15s ease;
  }
  .table-matrix-hide-plan .matrix-plan-subtext {
    display: none !important;
  }
  .sub-indent {
    padding-left: 1.5rem !important;
    color: #475569;
  }
  .sub-dash {
    display: inline-flex;
    align-items: center;
    color: #94a3b8;
    font-size: 0.78rem;
    margin-right: 0.4rem;
    user-select: none;
    vertical-align: middle;
  }
  .table-section-divider td {
    border-bottom: none !important;
  }
  .legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    display: inline-block;
  }
  .btn-toggle-sub {
    width: 22px;
    height: 22px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
    border-radius: 6px;
    transition: transform 0.2s;
    line-height: 1;
  }
  .btn-toggle-sub.expanded i {
    transform: rotate(90deg);
  }
  .input-plan70-sub {
    font-size: 0.8rem;
    font-weight: 600;
    text-align: right;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    padding: 0.2rem 0.5rem;
    background-color: #ffffff;
  }
  .input-plan70-sub:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
  }
  .input-growth-sub {
    width: 75px;
    font-size: 0.78rem;
    text-align: center;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    padding: 0.2rem 0.3rem;
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

        <!-- Header Banner -->
        <div class="col-12 px-3 mb-3">
            <div class="page-header-box mt-2 d-flex flex-column align-items-stretch" style="border-left: 4px solid #6366f1 !important; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); padding: 16px 22px; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <!-- Row 1: Title -->
                <div class="w-100 d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-1">
                    <h5 class="text-primary mb-0 fw-bold d-flex align-items-center gap-2" style="color: #4338ca !important;">
                        <i class="bi bi-graph-up-arrow fs-4" style="color: #6366f1;"></i> 
                        ระบบบริหารและติดตามแผนเงินบำรุง 
                        <span class="badge bg-indigo-subtle text-indigo px-2 py-0.5 rounded-pill" style="font-size: 0.76rem; background: #ede9fe; color: #5b21b6;">PlanFin</span>
                        <span class="d-none d-md-inline text-muted" style="font-size: 0.85rem; font-weight: 500;">({{ $hospCode }} - {{ $hospName }})</span>
                    </h5>
                </div>


                <!-- Row 2: Controls & Actions -->
                <div class="w-100 d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Budget Year Selector -->
                        <div class="d-inline-flex align-items-center gap-1.5 bg-white border rounded-pill px-2.5 py-1 shadow-xs" style="border-color: #c7d2fe !important;">
                            <i class="bi bi-calendar3" style="font-size: 0.8rem; color: #6366f1 !important;"></i>
                            <span class="small fw-bold" style="font-size: 0.76rem; color: #4338ca;">ปีงบประมาณ:</span>
                            <select class="form-select form-select-sm border-0 py-0 ps-1 pe-4 fw-bold text-dark bg-transparent" 
                                    style="font-size: 0.78rem; cursor: pointer; width: auto; box-shadow: none;" 
                                    onchange="location.href='{{ url('hosfin/planfin') }}?budget_year=' + this.value">
                                @foreach($budgetYearChoices as $by)
                                    <option value="{{ $by }}" {{ $budgetYear == $by ? 'selected' : '' }}>
                                        {{ $by }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Period Selector for Tab 1 -->
                        <div class="d-inline-flex align-items-center gap-1.5 bg-white border rounded-pill px-2.5 py-1 shadow-xs" style="border-color: #c7d2fe !important;">
                            <span class="spinner-grow spinner-grow-sm text-primary" role="status" style="width: 0.45rem; height: 0.45rem; color: #6366f1 !important;"></span>
                            <span class="small fw-bold" style="font-size: 0.76rem; color: #4338ca;">งวดเดือนติดตาม:</span>
                            <select class="form-select form-select-sm border-0 py-0 ps-1 pe-4 fw-bold text-dark bg-transparent" 
                                    style="font-size: 0.78rem; cursor: pointer; width: auto; box-shadow: none;" 
                                    onchange="location.href='{{ url('hosfin/planfin') }}?budget_year={{ $budgetYear }}&period=' + this.value">
                                @foreach($periodOptions as $opt)
                                    <option value="{{ $opt['period'] }}" {{ $opt['period'] === $selectedPeriod ? 'selected' : '' }}>
                                        {{ $opt['label'] }} (สะสม {{ $opt['cum_months'] }} ด.)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <span class="badge rounded-pill bg-light text-secondary border px-2.5 py-1" style="font-size: 0.75rem;">
                            <i class="bi bi-clock-history me-1 text-primary"></i> สะสม: <strong>{{ $cumMonths }} เดือน</strong>
                        </span>
                    </div>


                    <div class="d-flex align-items-center gap-2">
                        <!-- Button Open Mappings Modal -->
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 fw-bold" 
                                data-bs-toggle="modal" data-bs-target="#mappingModal" style="font-size: 0.78rem;">
                            <i class="bi bi-diagram-3 me-1"></i> ดูผังจับคู่บัญชี
                        </button>

                        <!-- Button Open Import MDB Modal -->
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-1.5" 
                                data-bs-toggle="modal" data-bs-target="#importPlanfinMdbModal"
                                style="font-size: 0.8rem; background: #4f46e5; border-color: #4338ca;">
                            <i class="bi bi-database-fill-up"></i> นำเข้าข้อมูลแผน MOC (.zip)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Executive KPI Cards -->
        <div class="col-12 px-3 mb-3">
            <div class="row g-3">
                <!-- KPI 1: รวมรายได้จริงสะสม -->
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="planfin-card p-3 h-100" style="border-top: 4px solid #10b981 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted fw-bold" style="font-size: 0.78rem;">รวมรายได้จริงสะสม (P13S)</span>
                            <i class="bi bi-wallet2 text-success fs-5"></i>
                        </div>
                        <h4 class="mb-1 fw-black text-dark" style="font-size: 1.35rem;">
                            {{ number_format($kpiActualRevenue, 2) }} <span class="small text-muted fw-normal" style="font-size: 0.75rem;">บาท</span>
                        </h4>
                        <div class="d-flex justify-content-between small text-muted" style="font-size: 0.74rem;">
                            <span>แผนสะสม: {{ number_format($kpiPlanRevenue, 2) }}</span>
                            <span class="{{ $kpiActualRevenue >= $kpiPlanRevenue ? 'text-success fw-bold' : 'text-danger fw-bold' }}">
                                {{ $kpiPlanRevenue > 0 ? number_format((($kpiActualRevenue - $kpiPlanRevenue) / $kpiPlanRevenue) * 100, 2) : 0 }}%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- KPI 2: รวมค่าใช้จ่ายจริงสะสม -->
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="planfin-card p-3 h-100" style="border-top: 4px solid #ef4444 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted fw-bold" style="font-size: 0.78rem;">รวมค่าใช้จ่ายจริงสะสม (P26S)</span>
                            <i class="bi bi-receipt text-danger fs-5"></i>
                        </div>
                        <h4 class="mb-1 fw-black text-dark" style="font-size: 1.35rem;">
                            {{ number_format($kpiActualExpense, 2) }} <span class="small text-muted fw-normal" style="font-size: 0.75rem;">บาท</span>
                        </h4>
                        <div class="d-flex justify-content-between small text-muted" style="font-size: 0.74rem;">
                            <span>แผนสะสม: {{ number_format($kpiPlanExpense, 2) }}</span>
                            <span class="{{ $kpiActualExpense <= $kpiPlanExpense ? 'text-success fw-bold' : 'text-danger fw-bold' }}">
                                {{ $kpiPlanExpense > 0 ? number_format((($kpiActualExpense - $kpiPlanExpense) / $kpiPlanExpense) * 100, 2) : 0 }}%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- KPI 3: รายได้สูง (ต่ำ) กว่าค่าใช้จ่ายสุทธิ -->
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="planfin-card p-3 h-100" style="border-top: 4px solid {{ $kpiActualNetIncome >= 0 ? '#10b981' : '#f59e0b' }} !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted fw-bold" style="font-size: 0.78rem;">รายได้สูง (ต่ำ) กว่าค่าใช้จ่ายสุทธิ (P27S)</span>
                            <i class="bi bi-bar-chart-fill {{ $kpiActualNetIncome >= 0 ? 'text-success' : 'text-warning' }} fs-5"></i>
                        </div>
                        <h4 class="mb-1 fw-black {{ $kpiActualNetIncome >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.35rem;">
                            {{ $kpiActualNetIncome >= 0 ? '+' : '' }}{{ number_format($kpiActualNetIncome, 2) }} <span class="small text-muted fw-normal" style="font-size: 0.75rem;">บาท</span>
                        </h4>
                        <div class="small" style="font-size: 0.74rem;">
                            <span class="badge {{ $kpiActualNetIncome >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill px-2">
                                {{ $kpiActualNetIncome >= 0 ? 'เกินดุล' : 'ขาดดุล' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- KPI 4: EBITDA & วงเงินลงทุน -->
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="planfin-card p-3 h-100" style="border-top: 4px solid #6366f1 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted fw-bold" style="font-size: 0.78rem;">EBITDA (P29)</span>
                            <i class="bi bi-shield-check text-primary fs-5" style="color: #6366f1 !important;"></i>
                        </div>
                        <h4 class="mb-1 fw-black text-primary" style="font-size: 1.35rem; color: #4f46e5 !important;">
                            {{ number_format($kpiActualEBITDA, 2) }} <span class="small text-muted fw-normal" style="font-size: 0.75rem;">บาท</span>
                        </h4>
                        <div class="small text-muted" style="font-size: 0.74rem;">
                            วงเงินที่ลงทุนด้วยเงินบำรุงได้ (20%): <strong class="text-dark">{{ number_format($kpiCapInvestment, 2) }}</strong> บ.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main 2-Tab Content Card -->
        <div class="col-12 px-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Tab Headers -->
                <div class="card-header bg-white border-bottom pt-3 pb-0 px-4">
                    <ul class="nav nav-tabs border-bottom-0" id="planfinTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab1-nav" data-bs-toggle="tab" data-bs-target="#tab1-content" type="button" role="tab">
                                <i class="bi bi-speedometer2 me-1.5 text-indigo"></i> 1. ติดตามแผนรายเดือน
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab2-nav" data-bs-toggle="tab" data-bs-target="#tab2-content" type="button" role="tab">
                                <i class="bi bi-calculator me-1.5 text-indigo"></i> 2. จัดทำแผนประมาณการ
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content p-4" id="planfinTabsContent">
                    <!-- ========================================================================= -->
                    <!-- TAB 1: ติดตามแผนรายเดือน -->
                    <!-- ========================================================================= -->
                    <div class="tab-pane fade show active" id="tab1-content" role="tabpanel">
                        <!-- Sub-Tabs Navigation & Fast Period Switcher Header -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <ul class="nav nav-pills gap-2" id="tab1SubNav" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active d-inline-flex align-items-center gap-1.5" id="subtab-cum-nav" 
                                            data-bs-toggle="pill" data-bs-target="#subtab-cum-content" type="button" role="tab"
                                            aria-controls="subtab-cum-content" aria-selected="true">
                                        <i class="bi bi-layers-half"></i>
                                        <span>ยอดสะสม (YTD)</span>
                                        <span class="badge bg-white text-primary rounded-pill px-1.5 ms-1" style="font-size: 0.7rem;">สะสม {{ $cumMonths }} ด.</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link d-inline-flex align-items-center gap-1.5" id="subtab-monthly-nav" 
                                            data-bs-toggle="pill" data-bs-target="#subtab-monthly-content" type="button" role="tab"
                                            aria-controls="subtab-monthly-content" aria-selected="false">
                                        <i class="bi bi-calendar2-check"></i>
                                        <span>แยกเฉพาะเดือน ({{ $selectedPeriodLabel }})</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link d-inline-flex align-items-center gap-1.5" id="subtab-matrix-nav" 
                                            data-bs-toggle="pill" data-bs-target="#subtab-matrix-content" type="button" role="tab"
                                            aria-controls="subtab-matrix-content" aria-selected="false">
                                        <i class="bi bi-graph-up-arrow"></i>
                                        <span>ภาพรวมแนวโน้ม 12 เดือน</span>
                                    </button>
                                </li>
                            </ul>

                            <!-- Quick Period Switcher Pills -->
                            <div class="d-flex align-items-center gap-1 overflow-x-auto py-1">
                                <span class="small text-muted fw-bold me-1 text-nowrap" style="font-size: 0.74rem;">เลือกงวด:</span>
                                @foreach($periodOptions as $opt)
                                    <button type="button" 
                                            onclick="navigateToPeriod('{{ $opt['period'] }}')"
                                            class="btn btn-sm rounded-pill px-2 py-0.5 text-nowrap {{ $opt['period'] === $selectedPeriod ? 'btn-primary fw-bold shadow-xs' : 'btn-outline-secondary bg-white' }}"
                                            style="font-size: 0.72rem; {{ $opt['period'] === $selectedPeriod ? 'background: #4f46e5; border-color: #4338ca;' : '' }}"
                                            title="{{ $opt['label'] }} (สะสม {{ $opt['cum_months'] }} เดือน)">
                                        {{ explode(' ', $opt['label'])[0] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Sub-tab Content Panes -->
                        <div class="tab-content" id="tab1SubPanes">
                            <!-- ========================================================================= -->
                            <!-- SUB-TAB 1.1: ยอดสะสม (YTD) - ค่าเริ่มต้น -->
                            <!-- ========================================================================= -->
                            <div class="tab-pane fade show active" id="subtab-cum-content" role="tabpanel" aria-labelledby="subtab-cum-nav">
                                <!-- Information Bar -->
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-3 rounded-3 bg-light border">
                                    <div class="small text-secondary">
                                        <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                        รายงานทางการเงิน รายได้และค่าใช้จ่าย PlanFin ณ เดือน <strong>{{ $selectedPeriodLabel }}</strong> 
                                        (ยอดแผนสะสมคำนวณจาก: <code>แผนทั้งปี ÷ 12 × {{ $cumMonths }} เดือน</code> | ผลดำเนินงานจริง: <strong>บวกสดจากงบทดลอง</strong>)
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 shadow-xs fw-bold d-inline-flex align-items-center gap-1.5" 
                                                id="btnToggleAll_t1" onclick="toggleAllSubRows('t1', this)" style="font-size: 0.78rem;">
                                            <i class="bi bi-toggle-off fs-5 text-secondary" id="iconToggleAll_t1"></i>
                                            <span>แสดงผังบัญชีย่อย</span>
                                        </button>
                                        <a href="{{ url('hosfin/planfin/export_excel') }}?target_year={{ $targetSimYear }}&budget_year={{ $budgetYear }}&period={{ $selectedPeriod }}" 
                                           class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 shadow-xs fw-bold d-inline-flex align-items-center gap-1.5" 
                                           title="ดาวน์โหลดไฟล์ Excel รายงานติดตามแผนและประมาณการ" style="font-size: 0.78rem;">
                                            <i class="bi bi-file-earmark-excel"></i>
                                            <span>ส่งออก Excel</span>
                                        </a>
                                        <span class="badge bg-white text-dark border px-2.5 py-1.5 rounded-pill shadow-xs" style="font-size: 0.78rem;">
                                            <i class="bi bi-check2-circle text-success me-1"></i> ข้อมูลงบทดลองล่าสุด
                                        </span>
                                    </div>
                                </div>

                                <!-- Tab 1 Table (Cumulative) -->
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-hover table-planfin align-middle mb-0">
                                        <thead>
                                            <tr class="text-center">
                                                <th style="width: 115px; min-width: 110px;">รหัสรายการ</th>
                                                <th class="text-start">รายการ</th>
                                                <th class="text-end" style="width: 140px;">แผนทั้งปี</th>
                                                <th class="text-end" style="width: 140px;">แผนสะสม ({{ $cumMonths }} ด.)</th>
                                                <th class="text-end" style="width: 150px;">ผลดำเนินงานจริง</th>
                                                <th class="text-end" style="width: 130px;">ผลต่าง</th>
                                                <th class="text-end" style="width: 90px;">ร้อยละ</th>
                                                <th class="text-center" style="width: 95px;">สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tab1Rows as $code => $r)
                                                @php
                                                    $rowClass = '';
                                                    $hasSubs = isset($subAccountsByPlan[$code]) && count($subAccountsByPlan[$code]) > 0;
                                                    if ($code === 'P13S') $rowClass = 'row-summary-rev';
                                                    elseif ($code === 'P26S') $rowClass = 'row-summary-exp';
                                                    elseif ($code === 'P27S') $rowClass = 'row-summary-ni';
                                                    elseif ($code === 'P29') $rowClass = 'row-summary-ebitda';
                                                    elseif ($r['type'] === 'revenue') $rowClass = 'row-revenue';
                                                    elseif ($r['type'] === 'expense') $rowClass = 'row-expense';
                                                @endphp

                                                <tr class="{{ $rowClass }}">
                                                    <td class="text-center text-nowrap">
                                                        <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                            @if($hasSubs)
                                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-sub shadow-xs" 
                                                                        onclick="toggleSubRows('t1_{{ $code }}', this)" title="คลิกเพื่อคลี่ดู/ยุบรหัสบัญชีย่อย">
                                                                    <i class="bi bi-chevron-right"></i>
                                                                </button>
                                                            @endif
                                                            @if($code === 'P13S')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #059669; font-size: 0.78rem;">P13S</span>
                                                            @elseif($code === 'P26S')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #dc2626; font-size: 0.78rem;">P26S</span>
                                                            @elseif($code === 'P27S')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #16a34a; font-size: 0.78rem;">P27S</span>
                                                            @elseif($code === 'P29')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #2563eb; font-size: 0.78rem;">P29</span>
                                                            @elseif($r['type'] === 'revenue')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.76rem;">{{ $code }}</span>
                                                            @elseif($r['type'] === 'expense')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace" style="background-color: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.76rem;">{{ $code }}</span>
                                                            @else
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace bg-light text-secondary border" style="font-size: 0.75rem;">{{ $code }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-start {{ in_array($code, ['P13S', 'P26S', 'P27S', 'P29', 'P28']) ? 'fw-bold' : '' }}">
                                                        {{ $r['name'] }}
                                                        @if($hasSubs)
                                                            <span class="badge rounded-pill px-2 py-0.5 ms-1 fw-bold shadow-xs" 
                                                                  style="background-color: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; font-size: 0.72rem; cursor: pointer;" 
                                                                  onclick="toggleSubRows('t1_{{ $code }}', this.previousElementSibling || this)"
                                                                  title="คลิกเพื่อคลี่ดู/ยุบรหัสบัญชีย่อย">
                                                                <i class="bi bi-list-nested me-0.5"></i> {{ count($subAccountsByPlan[$code]) }} บัญชี
                                                            </span>
                                                        @endif
                                                    </td>

                                                    @if($code === 'P28')
                                                        <!-- Special Row P28 สรุปสถานะ -->
                                                        <td colspan="5" class="text-center py-2">
                                                            @if(($tab1Rows['P29']['actual_cum'] ?? 0) > 0)
                                                                <span class="badge bg-success px-3 py-1.5 rounded-pill fs-6">🟢 ผลประกอบการดำเนินงานเกินดุล (EBITDA เป็นบวก)</span>
                                                            @else
                                                                <span class="badge bg-danger px-3 py-1.5 rounded-pill fs-6">🔴 ผลประกอบการดำเนินงานขาดดุล (EBITDA ติดลบ)</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge-status-{{ ($tab1Rows['P29']['actual_cum'] ?? 0) > 0 ? 'ok' : 'not-ok' }}">
                                                                {{ ($tab1Rows['P29']['actual_cum'] ?? 0) > 0 ? 'OK' : 'Not OK' }}
                                                            </span>
                                                        </td>
                                                    @else
                                                        <td class="text-end font-monospace">{{ number_format($r['annual_target'], 2) }}</td>
                                                        <td class="text-end font-monospace">{{ number_format($r['plan_cum'], 2) }}</td>
                                                        <td class="text-end font-monospace fw-bold {{ $r['actual_cum'] < 0 ? 'text-danger' : 'text-dark' }}">
                                                            {{ number_format($r['actual_cum'], 2) }}
                                                        </td>
                                                        <td class="text-end font-monospace {{ $r['diff'] < 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ ($r['diff'] > 0 ? '+' : '') . number_format($r['diff'], 2) }}
                                                        </td>
                                                        <td class="text-end font-monospace {{ $r['percent'] < 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ number_format($r['percent'], 2) }}%
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge-status-{{ $r['status'] === 'OK' ? 'ok' : 'not-ok' }}">
                                                                {{ $r['status'] }}
                                                            </span>
                                                        </td>
                                                    @endif
                                                </tr>

                                                @if($hasSubs)
                                                    @foreach($subAccountsByPlan[$code] as $sub)
                                                        @php
                                                            $share = ($r['actual_cum'] != 0) ? ($sub['actual_cum'] / $r['actual_cum']) * 100 : 0;
                                                        @endphp
                                                        <tr class="sub-row-t1 t1_{{ $code }} d-none">
                                                            <td class="text-center font-monospace text-muted ps-2" style="font-size: 0.76rem;">
                                                                <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">{{ $sub['account_code'] }}</span>
                                                            </td>
                                                            <td class="sub-indent text-secondary" style="font-size: 0.82rem;">
                                                                <span class="sub-dash"><i class="bi bi-dash-lg"></i></span>{{ $sub['account_name'] }}
                                                            </td>
                                                            <td class="text-end text-muted font-monospace" style="font-size: 0.76rem;">-</td>
                                                            <td class="text-end text-muted font-monospace" style="font-size: 0.76rem;">-</td>
                                                            <td class="text-end font-monospace fw-semibold {{ $sub['actual_cum'] < 0 ? 'text-danger' : 'text-dark' }}" style="font-size: 0.8rem;">
                                                                {{ number_format($sub['actual_cum'], 2) }}
                                                            </td>
                                                            <td class="text-end text-muted font-monospace" style="font-size: 0.76rem;">-</td>
                                                            <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">
                                                                {{ number_format($share, 1) }}%
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">ย่อย</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ========================================================================= -->
                            <!-- SUB-TAB 1.2: แยกเฉพาะเดือน (Monthly View) -->
                            <!-- ========================================================================= -->
                            <div class="tab-pane fade" id="subtab-monthly-content" role="tabpanel" aria-labelledby="subtab-monthly-nav">
                                <!-- Mini Monthly KPI Cards -->
                                <div class="row g-3 mb-3">
                                    <!-- Card 1: รายได้ประจำเดือน -->
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="planfin-card h-100 shadow-xs" style="padding: 18px 22px !important; border-top: 4px solid #10b981 !important; border-radius: 14px;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small text-muted fw-bold" style="font-size: 0.78rem;">รายได้ประจำเดือน (P13S)</span>
                                                <i class="bi bi-wallet2 text-success fs-5"></i>
                                            </div>
                                            <h4 class="mb-2 fw-black text-dark" style="font-size: 1.30rem;">
                                                {{ number_format($kpiMonthActualRev, 2) }} <span class="small text-muted fw-normal" style="font-size: 0.75rem;">บาท</span>
                                            </h4>
                                            <div class="d-flex justify-content-between align-items-center small text-muted" style="font-size: 0.74rem;">
                                                <span>แผนเดือน: {{ number_format($kpiMonthPlanRev, 2) }}</span>
                                                <span class="{{ $kpiMonthActualRev >= $kpiMonthPlanRev ? 'text-success fw-bold' : 'text-danger fw-bold' }}">
                                                    {{ $kpiMonthPlanRev > 0 ? (($kpiMonthActualRev >= $kpiMonthPlanRev ? '+' : '') . number_format((($kpiMonthActualRev - $kpiMonthPlanRev) / $kpiMonthPlanRev) * 100, 2) . '%') : '0%' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card 2: ค่าใช้จ่ายประจำเดือน -->
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="planfin-card h-100 shadow-xs" style="padding: 18px 22px !important; border-top: 4px solid #ef4444 !important; border-radius: 14px;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small text-muted fw-bold" style="font-size: 0.78rem;">ค่าใช้จ่ายประจำเดือน (P26S)</span>
                                                <i class="bi bi-receipt text-danger fs-5"></i>
                                            </div>
                                            <h4 class="mb-2 fw-black text-dark" style="font-size: 1.30rem;">
                                                {{ number_format($kpiMonthActualExp, 2) }} <span class="small text-muted fw-normal" style="font-size: 0.75rem;">บาท</span>
                                            </h4>
                                            <div class="d-flex justify-content-between align-items-center small text-muted" style="font-size: 0.74rem;">
                                                <span>แผนเดือน: {{ number_format($kpiMonthPlanExp, 2) }}</span>
                                                <span class="{{ $kpiMonthActualExp <= $kpiMonthPlanExp ? 'text-success fw-bold' : 'text-danger fw-bold' }}">
                                                    {{ $kpiMonthPlanExp > 0 ? (($kpiMonthActualExp > $kpiMonthPlanExp ? '+' : '') . number_format((($kpiMonthActualExp - $kpiMonthPlanExp) / $kpiMonthPlanExp) * 100, 2) . '%') : '0%' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card 3: สุทธิประจำเดือน -->
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="planfin-card h-100 shadow-xs" style="padding: 18px 22px !important; border-top: 4px solid {{ $kpiMonthActualNet >= 0 ? '#10b981' : '#ef4444' }} !important; border-radius: 14px;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small text-muted fw-bold" style="font-size: 0.78rem;">สุทธิประจำเดือน (P27S)</span>
                                                <i class="bi bi-bar-chart-fill {{ $kpiMonthActualNet >= 0 ? 'text-success' : 'text-danger' }} fs-5"></i>
                                            </div>
                                            <h4 class="mb-2 fw-black {{ $kpiMonthActualNet >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 1.30rem;">
                                                {{ $kpiMonthActualNet >= 0 ? '+' : '' }}{{ number_format($kpiMonthActualNet, 2) }} <span class="small text-muted fw-normal" style="font-size: 0.75rem;">บาท</span>
                                            </h4>
                                            <div class="d-flex justify-content-between align-items-center small" style="font-size: 0.74rem;">
                                                <span class="badge {{ $kpiMonthActualNet >= 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} rounded-pill px-2.5 py-0.5">
                                                    {{ $kpiMonthActualNet >= 0 ? '✓ เกินดุล' : '⚠ ขาดดุล' }}
                                                </span>
                                                <span class="text-muted">ผลดำเนินงานเดือนนี้</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card 4: EBITDA ประจำเดือน -->
                                    <div class="col-md-3 col-sm-6 col-12">
                                        <div class="planfin-card h-100 shadow-xs" style="padding: 18px 22px !important; border-top: 4px solid #6366f1 !important; border-radius: 14px;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small text-muted fw-bold" style="font-size: 0.78rem;">EBITDA ประจำเดือน (P29)</span>
                                                <i class="bi bi-shield-check text-primary fs-5" style="color: #6366f1 !important;"></i>
                                            </div>
                                            <h4 class="mb-2 fw-black text-primary" style="font-size: 1.30rem; color: #4f46e5 !important;">
                                                {{ number_format($kpiMonthActualEbitda, 2) }} <span class="small text-muted fw-normal" style="font-size: 0.75rem;">บาท</span>
                                            </h4>
                                            <div class="small text-muted" style="font-size: 0.74rem;">
                                                สถานะ: <strong class="{{ $kpiMonthActualEbitda >= 0 ? 'text-success' : 'text-danger' }}">{{ $kpiMonthActualEbitda >= 0 ? 'สภาพคล่องบวก' : 'สภาพคล่องติดลบ' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Monthly Info Bar -->
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-3 rounded-3 bg-light border">
                                    <div class="small text-secondary">
                                        <i class="bi bi-calendar3 text-primary me-1"></i>
                                        ผลการดำเนินงานเฉพาะเดือน <strong>{{ $selectedPeriodLabel }}</strong> 
                                        (แผนงวดคำนวณจาก: <code>แผนทั้งปี ÷ 12</code> | ผลดำเนินงานจริง: <strong>การเคลื่อนไหว (Movement) เดบิต-เครดิต ประจำเดือน</strong>)
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 shadow-xs fw-bold d-inline-flex align-items-center gap-1.5" 
                                                id="btnToggleAll_t1m" onclick="toggleAllSubRows('t1m', this)" style="font-size: 0.78rem;">
                                            <i class="bi bi-toggle-off fs-5 text-secondary" id="iconToggleAll_t1m"></i>
                                            <span>แสดงผังบัญชีย่อย</span>
                                        </button>
                                        <span class="badge bg-white text-dark border px-2.5 py-1.5 rounded-pill shadow-xs" style="font-size: 0.78rem;">
                                            <i class="bi bi-check2-circle text-success me-1"></i> งวดเดือนปัจจุบัน
                                        </span>
                                    </div>
                                </div>

                                <!-- Tab 1 Table (Monthly View) -->
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-hover table-planfin align-middle mb-0">
                                        <thead>
                                            <tr class="text-center">
                                                <th style="width: 115px; min-width: 110px;">รหัสรายการ</th>
                                                <th class="text-start">รายการ</th>
                                                <th class="text-end" style="width: 140px;">แผนทั้งปี</th>
                                                <th class="text-end" style="width: 140px;">แผนงวดเดือนนี้</th>
                                                <th class="text-end" style="width: 150px;">ผลดำเนินงานจริง</th>
                                                <th class="text-end" style="width: 130px;">ผลต่าง</th>
                                                <th class="text-end" style="width: 90px;">ร้อยละ</th>
                                                <th class="text-center" style="width: 95px;">สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tab1MonthlyRows as $code => $r)
                                                @php
                                                    $rowClass = '';
                                                    $hasSubs = isset($subAccountsByPlan[$code]) && count($subAccountsByPlan[$code]) > 0;
                                                    if ($code === 'P13S') $rowClass = 'row-summary-rev';
                                                    elseif ($code === 'P26S') $rowClass = 'row-summary-exp';
                                                    elseif ($code === 'P27S') $rowClass = 'row-summary-ni';
                                                    elseif ($code === 'P29') $rowClass = 'row-summary-ebitda';
                                                    elseif ($r['type'] === 'revenue') $rowClass = 'row-revenue';
                                                    elseif ($r['type'] === 'expense') $rowClass = 'row-expense';
                                                @endphp

                                                <tr class="{{ $rowClass }}">
                                                    <td class="text-center text-nowrap">
                                                        <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                            @if($hasSubs)
                                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-sub shadow-xs" 
                                                                        onclick="toggleSubRows('t1m_{{ $code }}', this)" title="คลิกเพื่อคลี่ดู/ยุบรหัสบัญชีย่อย">
                                                                    <i class="bi bi-chevron-right"></i>
                                                                </button>
                                                            @endif
                                                            @if($code === 'P13S')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #059669; font-size: 0.78rem;">P13S</span>
                                                            @elseif($code === 'P26S')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #dc2626; font-size: 0.78rem;">P26S</span>
                                                            @elseif($code === 'P27S')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #16a34a; font-size: 0.78rem;">P27S</span>
                                                            @elseif($code === 'P29')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #2563eb; font-size: 0.78rem;">P29</span>
                                                            @elseif($r['type'] === 'revenue')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.76rem;">{{ $code }}</span>
                                                            @elseif($r['type'] === 'expense')
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace" style="background-color: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.76rem;">{{ $code }}</span>
                                                            @else
                                                                <span class="badge rounded-pill px-2 py-0.5 font-monospace bg-light text-secondary border" style="font-size: 0.75rem;">{{ $code }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-start {{ in_array($code, ['P13S', 'P26S', 'P27S', 'P29', 'P28']) ? 'fw-bold' : '' }}">
                                                        {{ $r['name'] }}
                                                        @if($hasSubs)
                                                            <span class="badge rounded-pill px-2 py-0.5 ms-1 fw-bold shadow-xs" 
                                                                  style="background-color: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; font-size: 0.72rem; cursor: pointer;" 
                                                                  onclick="toggleSubRows('t1m_{{ $code }}', this.previousElementSibling || this)"
                                                                  title="คลิกเพื่อคลี่ดู/ยุบรหัสบัญชีย่อย">
                                                                <i class="bi bi-list-nested me-0.5"></i> {{ count($subAccountsByPlan[$code]) }} บัญชี
                                                            </span>
                                                        @endif
                                                    </td>

                                                    @if($code === 'P28')
                                                        <!-- Special Row P28 สรุปสถานะ -->
                                                        <td colspan="5" class="text-center py-2">
                                                            @if(($tab1MonthlyRows['P29']['actual_month'] ?? 0) > 0)
                                                                <span class="badge bg-success px-3 py-1.5 rounded-pill fs-6">🟢 ผลประกอบการประจำเดือนเกินดุล (EBITDA เป็นบวก)</span>
                                                            @else
                                                                <span class="badge bg-danger px-3 py-1.5 rounded-pill fs-6">🔴 ผลประกอบการประจำเดือนขาดดุล (EBITDA ติดลบ)</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge-status-{{ ($tab1MonthlyRows['P29']['actual_month'] ?? 0) > 0 ? 'ok' : 'not-ok' }}">
                                                                {{ ($tab1MonthlyRows['P29']['actual_month'] ?? 0) > 0 ? 'OK' : 'Not OK' }}
                                                            </span>
                                                        </td>
                                                    @else
                                                        <td class="text-end font-monospace">{{ number_format($r['annual_target'], 2) }}</td>
                                                        <td class="text-end font-monospace">{{ number_format($r['plan_month'], 2) }}</td>
                                                        <td class="text-end font-monospace fw-bold {{ $r['actual_month'] < 0 ? 'text-danger' : 'text-dark' }}">
                                                            {{ number_format($r['actual_month'], 2) }}
                                                        </td>
                                                        <td class="text-end font-monospace {{ $r['diff_month'] < 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ ($r['diff_month'] > 0 ? '+' : '') . number_format($r['diff_month'], 2) }}
                                                        </td>
                                                        <td class="text-end font-monospace {{ $r['percent_month'] < 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ number_format($r['percent_month'], 2) }}%
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge-status-{{ $r['status_month'] === 'OK' ? 'ok' : 'not-ok' }}">
                                                                {{ $r['status_month'] }}
                                                            </span>
                                                        </td>
                                                    @endif
                                                </tr>

                                                @if($hasSubs)
                                                    @foreach($subAccountsByPlan[$code] as $sub)
                                                        @php
                                                            $subActualM = $sub['actual_month'] ?? 0;
                                                            $shareM = ($r['actual_month'] != 0) ? ($subActualM / $r['actual_month']) * 100 : 0;
                                                        @endphp
                                                        <tr class="sub-row-t1m t1m_{{ $code }} d-none">
                                                            <td class="text-center font-monospace text-muted ps-2" style="font-size: 0.76rem;">
                                                                <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">{{ $sub['account_code'] }}</span>
                                                            </td>
                                                            <td class="sub-indent text-secondary" style="font-size: 0.82rem;">
                                                                <span class="sub-dash"><i class="bi bi-dash-lg"></i></span>{{ $sub['account_name'] }}
                                                            </td>
                                                            <td class="text-end text-muted font-monospace" style="font-size: 0.76rem;">-</td>
                                                            <td class="text-end text-muted font-monospace" style="font-size: 0.76rem;">-</td>
                                                            <td class="text-end font-monospace fw-semibold {{ $subActualM < 0 ? 'text-danger' : 'text-dark' }}" style="font-size: 0.8rem;">
                                                                {{ number_format($subActualM, 2) }}
                                                            </td>
                                                            <td class="text-end text-muted font-monospace" style="font-size: 0.76rem;">-</td>
                                                            <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">
                                                                {{ number_format($shareM, 1) }}%
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">ย่อย</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ========================================================================= -->
                            <!-- SUB-TAB 1.3: ภาพรวมแนวโน้ม 12 เดือน (12-Month Matrix View) -->
                            <!-- ========================================================================= -->
                            <div class="tab-pane fade" id="subtab-matrix-content" role="tabpanel" aria-labelledby="subtab-matrix-nav">
                                <!-- Info bar -->
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-3 rounded-3 bg-light border">
                                    <div class="small text-secondary">
                                        <i class="bi bi-graph-up-arrow text-primary me-1"></i>
                                        ภาพรวมแนวโน้ม 12 งวด (ปีงบประมาณ <strong>{{ $budgetYear }}</strong>):
                                        <span class="badge bg-white text-dark border ms-1 me-1"><strong class="text-dark">บรรทัดบน = ผลจริง</strong></span>
                                        <span class="badge bg-white text-secondary border me-1"><span style="color: #64748b;">บรรทัดล่าง = แผนงวด (1/12)</span></span>
                                        (คอลัมน์สีฟ้าไฮไลต์ <strong>{{ $selectedPeriodLabel }}</strong> คืองวดเดือนที่กำลังเลือกติดตาม)
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-primary text-white rounded-pill px-3 py-1 shadow-xs fw-bold d-inline-flex align-items-center gap-1.5 active" 
                                                id="btnToggleMatrixPlan" onclick="toggleMatrixPlanSubtext(this)" style="font-size: 0.78rem;">
                                            <i class="bi bi-toggle-on fs-5 text-white" id="iconToggleMatrixPlan"></i>
                                            <span>แสดงบรรทัดแผน</span>
                                        </button>
                                        <a href="{{ url('hosfin/planfin/export_excel') }}?target_year={{ $targetSimYear }}&budget_year={{ $budgetYear }}&period={{ $selectedPeriod }}" 
                                           class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 shadow-xs fw-bold d-inline-flex align-items-center gap-1.5" 
                                           title="ดาวน์โหลดไฟล์ Excel รายงานติดตามแผนและประมาณการ" style="font-size: 0.78rem;">
                                            <i class="bi bi-file-earmark-excel"></i>
                                            <span>ส่งออก Excel</span>
                                        </a>
                                    </div>
                                </div>

                                <!-- 12-Month Matrix Table -->
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-hover table-planfin align-middle mb-0 text-nowrap" id="tableMatrix12" style="font-size: 0.82rem;">
                                        <thead class="text-center">
                                            <tr>
                                                <th style="width: 80px; min-width: 80px;">รหัส</th>
                                                <th class="text-start" style="min-width: 210px;">รายการ</th>
                                                @foreach($periodOptions as $opt)
                                                    <th style="min-width: 110px;" class="{{ $opt['period'] === $selectedPeriod ? 'bg-primary text-white' : '' }}">
                                                        <div class="fw-bold">{{ explode(' ', $opt['label'])[0] }}</div>
                                                        <div style="font-size: 0.69rem; opacity: 0.9;">งวด {{ $opt['cum_months'] }}</div>
                                                        <div class="matrix-plan-subtext badge rounded-pill px-1.5 py-0 mt-0.5 {{ $opt['period'] === $selectedPeriod ? 'bg-white text-primary' : 'bg-light text-secondary border' }}" style="font-size: 0.60rem; font-weight: normal;">
                                                            ผลจริง / แผน
                                                        </div>
                                                    </th>
                                                @endforeach
                                                <th style="min-width: 130px;" class="bg-light text-dark fw-bold">
                                                    <div>รวมทั้งปี</div>
                                                    <div class="matrix-plan-subtext" style="font-size: 0.65rem; color: #64748b; font-weight: normal;">(ผลจริง / แผน)</div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tab1Rows as $code => $r)
                                                @if($code === 'P28')
                                                    @continue
                                                @endif
                                                @php
                                                    $rowClass = '';
                                                    if ($code === 'P13S') $rowClass = 'row-summary-rev';
                                                    elseif ($code === 'P26S') $rowClass = 'row-summary-exp';
                                                    elseif ($code === 'P27S') $rowClass = 'row-summary-ni';
                                                    elseif ($code === 'P29') $rowClass = 'row-summary-ebitda';
                                                    elseif ($r['type'] === 'revenue') $rowClass = 'row-revenue';
                                                    elseif ($r['type'] === 'expense') $rowClass = 'row-expense';

                                                    $rowTotal = 0;
                                                    foreach ($periodOptions as $opt) {
                                                        $rowTotal += ($matrixLookup[$code][$opt['period']] ?? 0);
                                                    }
                                                    $annualPlan = $r['annual_target'] ?? 0;
                                                    $monthlyPlan = $annualPlan / 12.0;
                                                @endphp
                                                <tr class="{{ $rowClass }}">
                                                    <td class="text-center font-monospace">
                                                        @if($code === 'P13S')
                                                            <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #059669; font-size: 0.75rem;">P13S</span>
                                                        @elseif($code === 'P26S')
                                                            <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #dc2626; font-size: 0.75rem;">P26S</span>
                                                        @elseif($code === 'P27S')
                                                            <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #16a34a; font-size: 0.75rem;">P27S</span>
                                                        @elseif($code === 'P29')
                                                            <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #2563eb; font-size: 0.75rem;">P29</span>
                                                        @elseif($r['type'] === 'revenue')
                                                            <span class="badge rounded-pill px-2 py-0.5 font-monospace" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.72rem;">{{ $code }}</span>
                                                        @elseif($r['type'] === 'expense')
                                                            <span class="badge rounded-pill px-2 py-0.5 font-monospace" style="background-color: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.72rem;">{{ $code }}</span>
                                                        @else
                                                            <span class="badge rounded-pill px-2 py-0.5 font-monospace bg-light text-secondary border" style="font-size: 0.72rem;">{{ $code }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-start {{ in_array($code, ['P13S', 'P26S', 'P27S', 'P29']) ? 'fw-bold' : '' }}">
                                                        {{ $r['name'] }}
                                                    </td>
                                                    @foreach($periodOptions as $opt)
                                                        @php
                                                            $val = $matrixLookup[$code][$opt['period']] ?? 0;
                                                            $hasVal = ($val != 0);
                                                        @endphp
                                                        <td class="text-end font-monospace {{ $opt['period'] === $selectedPeriod ? 'col-matrix-active' : '' }} {{ $val < 0 ? 'text-danger' : '' }}" style="padding-top: 0.4rem; padding-bottom: 0.4rem;">
                                                            <div class="fw-semibold {{ $hasVal ? ($val < 0 ? 'text-danger' : 'text-dark') : 'text-muted' }}" style="font-size: 0.82rem; line-height: 1.25;">
                                                                {{ $hasVal ? number_format($val, 2) : '-' }}
                                                            </div>
                                                            <div class="matrix-plan-subtext" style="font-size: 0.68rem; line-height: 1.2; margin-top: 2px; color: #64748b; font-weight: 500;">
                                                                <span style="font-size: 0.60rem; color: #94a3b8; font-weight: normal;">แผน</span> {{ $monthlyPlan > 0 ? number_format($monthlyPlan, 2) : '-' }}
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                    <td class="text-end font-monospace fw-bold bg-light {{ $rowTotal < 0 ? 'text-danger' : 'text-dark' }}" style="padding-top: 0.4rem; padding-bottom: 0.4rem;">
                                                        <div style="font-size: 0.84rem; line-height: 1.25;">
                                                            {{ number_format($rowTotal, 2) }}
                                                        </div>
                                                        <div class="matrix-plan-subtext" style="font-size: 0.68rem; line-height: 1.2; margin-top: 2px; color: #64748b; font-weight: 500;">
                                                            <span style="font-size: 0.60rem; color: #94a3b8; font-weight: normal;">แผน</span> {{ $annualPlan > 0 ? number_format($annualPlan, 2) : '-' }}
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- TAB 2: จัดทำแผนประมาณการปี 2570 (Budget Simulator) -->
                    <!-- ========================================================================= -->
                    <div class="tab-pane fade" id="tab2-content" role="tabpanel">
                        <!-- Action Bar for Tab 2 -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-3 rounded-3 bg-white border shadow-xs" style="border-left: 4px solid #4f46e5 !important;">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-file-earmark-spreadsheet-fill text-primary me-1"></i> 1. แผนประมาณการรายได้-ควบคุมค่าใช้จ่าย
                                </h6>
                                <small class="text-muted">
                                    ฐานคาดการณ์เต็มปีคำนวณจาก: <code>(ยอดจริง {{ $baseMonths }} เดือน ÷ {{ $baseMonths }}) × 12</code> | เมื่อกรอกตัวเลข ระบบคำนวณ EBITDA และเพดานงบลงทุนให้อัตโนมัติ
                                </small>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-bold d-inline-flex align-items-center gap-1.5 shadow-xs" 
                                        id="btnToggleAll_t2" onclick="toggleAllSubRows('t2', this)" style="font-size: 0.78rem;">
                                    <i class="bi bi-toggle-off fs-5 text-secondary" id="iconToggleAll_t2"></i>
                                    <span>แสดงผังบัญชีย่อย</span>
                                </button>

                                <a href="{{ url('hosfin/planfin/export_excel') }}?target_year={{ $targetSimYear }}&budget_year={{ $budgetYear }}&period={{ $selectedPeriod }}" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5"
                                   title="ดาวน์โหลดไฟล์ Excel แผนประมาณการ">
                                    <i class="bi bi-file-earmark-excel"></i> ส่งออก Excel
                                </a>

                                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5 shadow-sm"
                                        onclick="savePlanfinTargets()">
                                    <span class="spinner-border spinner-border-sm d-none" id="saveTargetsSpinner"></span>
                                    <i class="bi bi-save"></i> บันทึกแผนประมาณการ
                                </button>
                            </div>
                        </div>

                        <!-- Live Metrics Banner for FY Simulator -->
                        <div class="row g-3 mb-3">
                            <!-- Card 1: ประมาณการ รายได้สูง(ต่ำ)กว่าค่าใช้จ่ายสุทธิ -->
                            <div class="col-md-4">
                                <div class="planfin-card p-3 h-100 shadow-xs" id="simCard1" style="border-top: 4px solid #10b981 !important; background: #ffffff;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small text-muted fw-bold" style="font-size: 0.8rem;">
                                            <i class="bi bi-wallet2 text-success me-1"></i> ประมาณการรายได้สุทธิ (P27S)
                                        </span>
                                        <span id="simNIBadge" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                            ✓ เกินดุล
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-1 my-1">
                                        <h4 class="mb-0 fw-black text-dark" id="simNI" style="font-size: 1.45rem;">0.00</h4>
                                        <span class="small text-muted">บาท</span>
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.74rem;">
                                        รายได้สูง (ต่ำ) กว่าค่าใช้จ่ายสุทธิ ประจำปีงบประมาณ {{ $targetSimYear }}
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: EBITDA ประมาณการ -->
                            <div class="col-md-4">
                                <div class="planfin-card p-3 h-100 shadow-xs" style="border-top: 4px solid #3b82f6 !important; background: #ffffff;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small text-muted fw-bold" style="font-size: 0.8rem;">
                                            <i class="bi bi-graph-up-arrow text-primary me-1"></i> EBITDA ประมาณการ (P29)
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                            กำไรดำเนินงาน
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-1 my-1">
                                        <h4 class="mb-0 fw-black text-primary" id="simEBITDA" style="font-size: 1.45rem;">0.00</h4>
                                        <span class="small text-muted">บาท</span>
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.74rem;">
                                        กำไรจากการดำเนินงานก่อนหักค่าเสื่อมราคาและดอกเบี้ย
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: วงเงินลงทุนด้วยเงินบำรุง -->
                            <div class="col-md-4">
                                <div class="planfin-card p-3 h-100 shadow-xs" style="border-top: 4px solid #8b5cf6 !important; background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-bold" style="font-size: 0.8rem; color: #7c3aed;">
                                            <i class="bi bi-bank me-1" style="color: #8b5cf6;"></i> วงเงินลงทุนด้วยเงินบำรุงได้
                                        </span>
                                        <span class="badge rounded-pill px-2 py-0.5 fw-bold" style="background-color: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; font-size: 0.72rem;">
                                            20% ของ EBITDA
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-baseline gap-1 my-1">
                                        <h4 class="mb-0 fw-black" id="simCap" style="font-size: 1.45rem; color: #6d28d9;">0.00</h4>
                                        <span class="small text-muted">บาท</span>
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.74rem;">
                                        เพดานงบลงทุนสิ่งก่อสร้างและครุภัณฑ์ประจำปี {{ $targetSimYear }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Guidance Alert for Sub-Account Keying -->
                        <div class="alert alert-info py-2 px-3 mb-3 rounded-3 d-flex align-items-center gap-2.5 small border-0 shadow-xs" 
                             style="background-color: #eff6ff; color: #1e40af; border-left: 4px solid #3b82f6 !important;">
                            <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                            <div>
                                <strong>การบันทึกแผนประมาณการ:</strong> กำหนด <strong>% เติบโต</strong> หรือ <strong>ยอดเงินเป้าหมาย</strong> ในระดับ <strong>ผังบัญชีย่อย</strong> 
                                (คลิกคลี่หมวด <i class="bi bi-chevron-right"></i> หรือกดปุ่ม <strong>"แสดงผังบัญชีย่อย"</strong>) | <strong>หมวดหลักจะรวมยอด (SUM) และแสดงผลให้อัตโนมัติ</strong>
                            </div>
                        </div>

                        

                        <!-- Tab 2 Table: แผนแม่บทหลัก (แผนที่ 1) -->
                        <div class="table-responsive rounded-3 border">
                            <table class="table table-hover table-planfin align-middle mb-0" id="tableSimulator70">
                                <thead>
                                    <tr class="text-center">
                                        <th style="width: 115px; min-width: 110px;">รหัสรายการ</th>
                                        <th class="text-start">รายการ</th>
                                        <th class="text-end" style="width: 130px;">ผลการดำเนินงาน ปี {{ $priorYear }}</th>
                                        <th class="text-end" style="width: 135px;">ผลการดำเนินงาน ({{ $baseMonths }} ด.)</th>
                                        <th class="text-end" style="width: 140px;">ประมาณการ ผลดำเนินงานทั้งปี</th>
                                        <th class="text-center" style="width: 100px;">% เติบโต</th>
                                        <th class="text-end" style="width: 170px; background-color: #f3f0ff;">แผนประมาณการ (แผนต้นปี)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tab2Rows as $code => $r)
                                        @php
                                            $rowClass = '';
                                            $hasSubs = isset($subAccountsByPlan[$code]) && count($subAccountsByPlan[$code]) > 0;
                                            $isCalculated = in_array($code, ['P13S', 'P26S', 'P27S', 'P29-R', 'P29-E', 'P29', 'P28']);
                                            if ($code === 'P13S') $rowClass = 'row-summary-rev';
                                            elseif ($code === 'P26S') $rowClass = 'row-summary-exp';
                                            elseif ($code === 'P27S') $rowClass = 'row-summary-ni';
                                            elseif ($code === 'P29') $rowClass = 'row-summary-ebitda';
                                            elseif ($r['type'] === 'revenue') $rowClass = 'row-revenue';
                                            elseif ($r['type'] === 'expense') $rowClass = 'row-expense';
                                        @endphp

                                        

                                        

                                        

                                        <tr class="{{ $rowClass }}" data-code="{{ $code }}" data-type="{{ $r['type'] }}" data-base="{{ $r['y_base_est'] }}">
                                            <td class="text-center text-nowrap">
                                                <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                    @if($hasSubs)
                                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-sub shadow-xs" 
                                                                onclick="toggleSubRows('t2_{{ $code }}', this)" title="คลิกเพื่อคลี่ดู/ปรับแก้รหัสบัญชีย่อย">
                                                            <i class="bi bi-chevron-right"></i>
                                                        </button>
                                                    @endif
                                                    @if($code === 'P13S')
                                                        <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #059669; font-size: 0.78rem;">P13S</span>
                                                    @elseif($code === 'P26S')
                                                        <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #dc2626; font-size: 0.78rem;">P26S</span>
                                                    @elseif($code === 'P27S')
                                                        <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #16a34a; font-size: 0.78rem;">P27S</span>
                                                    @elseif($code === 'P29')
                                                        <span class="badge rounded-pill px-2 py-0.5 font-monospace text-white shadow-xs" style="background-color: #2563eb; font-size: 0.78rem;">P29</span>
                                                    @elseif($r['type'] === 'revenue')
                                                        <span class="badge rounded-pill px-2 py-0.5 font-monospace" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.76rem;">{{ $code }}</span>
                                                    @elseif($r['type'] === 'expense')
                                                        <span class="badge rounded-pill px-2 py-0.5 font-monospace" style="background-color: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.76rem;">{{ $code }}</span>
                                                    @else
                                                        <span class="badge rounded-pill px-2 py-0.5 font-monospace bg-light text-secondary border" style="font-size: 0.75rem;">{{ $code }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-start {{ $isCalculated ? 'fw-bold' : '' }}">
                                                {{ $r['name'] }}
                                                @if($hasSubs)
                                                    <span class="badge rounded-pill px-2 py-0.5 ms-1 fw-bold shadow-xs" 
                                                          style="background-color: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; font-size: 0.72rem; cursor: pointer;" 
                                                          onclick="toggleSubRows('t2_{{ $code }}', this.previousElementSibling || this)"
                                                          title="คลิกเพื่อคลี่ดู/ปรับแก้รหัสบัญชีย่อย">
                                                        <i class="bi bi-list-nested me-0.5"></i> {{ count($subAccountsByPlan[$code]) }} บัญชี
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end font-monospace text-muted">{{ number_format($r['y_prior'], 2) }}</td>
                                            <td class="text-end font-monospace text-muted">{{ number_format($r['y_base_months'], 2) }}</td>
                                            <td class="text-end font-monospace fw-semibold">{{ number_format($r['y_base_est'], 2) }}</td>

                                            @if($isCalculated)
                                                @if($code === 'P28')
                                                    <td colspan="2" class="text-center fw-bold py-2" id="simStatusBadge">
                                                        🟢 เกินดุล
                                                    </td>
                                                @else
                                                    <td class="text-center text-muted">-</td>
                                                    <td class="text-end font-monospace fw-bold fs-6 pe-3" id="cell_{{ $code }}">
                                                        {{ number_format($r['target_sim'], 2) }}
                                                    </td>
                                                @endif
                                            @elseif($hasSubs)
                                                <!-- หมวดที่มีผังย่อย: แสดงผลลัพธ์คำนวณรวมจากผังย่อย (Display Only / Sum of Sub-Accounts) -->
                                                <td class="text-center">
                                                    <input type="hidden" id="growth_{{ $code }}" value="{{ number_format($r['growth_rate'], 2, '.', '') }}">
                                                    <span class="badge {{ $r['growth_rate'] > 0 ? 'bg-success-subtle text-success border border-success-subtle' : ($r['growth_rate'] < 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-white text-secondary border') }} font-monospace px-2 py-1 shadow-xs" 
                                                          id="disp_growth_{{ $code }}" style="font-size: 0.82rem;" title="คำนวณจากผลรวมผังย่อย">
                                                        {{ ($r['growth_rate'] > 0 ? '+' : '') . number_format($r['growth_rate'], 2) }}%
                                                    </span>
                                                </td>
                                                <td class="text-end pe-3" style="background-color: #f5f3ff;">
                                                    <input type="hidden" id="target_{{ $code }}" value="{{ number_format($r['target_sim'], 2, '.', '') }}">
                                                    <div class="font-monospace fw-bold text-dark fs-6" id="disp_target_{{ $code }}" title="ผลรวมจากผังบัญชีย่อย (SUM)">
                                                        {{ number_format($r['target_sim'], 2) }}
                                                    </div>
                                                </td>
                                            @else
                                                <!-- หมวดที่ไม่มีผังย่อย: อนุญาตให้คีย์ได้โดยตรง -->
                                                <td class="text-center">
                                                    <input type="number" step="0.01" class="form-control form-control-sm input-growth text-center" 
                                                           id="growth_{{ $code }}" value="{{ number_format($r['growth_rate'], 2, '.', '') }}" 
                                                           oninput="onParentGrowthChange('{{ $code }}')">
                                                </td>
                                                <td class="pe-2">
                                                    <input type="number" step="0.01" class="form-control form-control-sm input-plan70 text-end" 
                                                           id="target_{{ $code }}" value="{{ number_format($r['target_sim'], 2, '.', '') }}" 
                                                           oninput="onParentTargetChange('{{ $code }}')">
                                                </td>
                                            @endif
                                        </tr>

                                        @if($hasSubs)
                                            @foreach($subAccountsByPlan[$code] as $sub)
                                                @php $sanitizedCode = str_replace('.', '_', $sub['account_code']); @endphp
                                                <tr class="sub-row-t2 t2_{{ $code }} d-none" 
                                                    data-parent="{{ $code }}" 
                                                    data-subcode="{{ $sub['account_code'] }}" 
                                                    data-base="{{ $sub['y_base_est'] }}">
                                                    <td class="text-center font-monospace text-muted ps-2" style="font-size: 0.76rem;">
                                                        <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">{{ $sub['account_code'] }}</span>
                                                    </td>
                                                    <td class="sub-indent text-secondary" style="font-size: 0.82rem;">
                                                        <span class="sub-dash"><i class="bi bi-dash-lg"></i></span>{{ $sub['account_name'] }}
                                                    </td>
                                                    <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">{{ number_format($sub['y_prior'], 2) }}</td>
                                                    <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">{{ number_format($sub['y_base_months'], 2) }}</td>
                                                    <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">{{ number_format($sub['y_base_est'], 2) }}</td>
                                                    <td class="text-center">
                                                        <input type="number" step="0.1" class="form-control form-control-sm input-growth-sub" 
                                                               id="subgrowth_{{ $sanitizedCode }}" 
                                                               value="{{ number_format($sub['growth_rate'], 1, '.', '') }}" 
                                                               oninput="onSubGrowthChange('{{ $code }}', '{{ $sub['account_code'] }}', this.value)">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" class="form-control form-control-sm input-plan70-sub" 
                                                               id="subtarget_{{ $sanitizedCode }}" 
                                                               value="{{ number_format($sub['target_sim'], 2, '.', '') }}" 
                                                               oninput="onSubTargetChange('{{ $code }}', '{{ $sub['account_code'] }}', this.value)">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- ========================================================================= -->
                        <!-- SECTION 2: กลุ่มแผนปฏิบัติการย่อย (Operational Sub-Plans 2 - 7) -->
                        <!-- ========================================================================= -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="bi bi-collection text-primary me-1.5"></i> กลุ่มแผนปฏิบัติการย่อยประกอบแผนเงินบำรุง (แผนที่ 2–7)
                                    </h6>
                                    <small class="text-muted">
                                        แผนจัดซื้อยา/เวชภัณฑ์, วัสดุอื่น, เจ้าหนี้การค้า, ลูกหนี้, แผนลงทุน และสนับสนุน รพ.สต. ตามแบบฟอร์มกระทรวงสาธารณสุข
                                    </small>
                                </div>
                            </div>

                            <div class="accordion accordion-flush rounded-3 border" id="accordionSubPlans">
                                @foreach($subPlansData as $pKey => $pDef)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading_{{ $pKey }}">
                                            <button class="accordion-button collapsed py-2.5 px-3 fw-bold text-dark bg-light-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_{{ $pKey }}">
                                                <i class="bi {{ $pDef['icon'] }} me-2 text-indigo"></i> {{ $pDef['title'] }}
                                                <span class="badge bg-white text-secondary border ms-2 fw-normal" style="font-size: 0.72rem;">{{ count($pDef['items']) }} รายการ</span>
                                            </button>
                                        </h2>
                                        <div id="collapse_{{ $pKey }}" class="accordion-collapse collapse" data-bs-parent="#accordionSubPlans">
                                            <div class="accordion-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                                                        <thead class="table-light">
                                                            <tr class="text-center">
                                                                <th style="width: 80px;">รหัส</th>
                                                                <th class="text-start">รายการแผน</th>
                                                                <th style="width: 170px;">เงินงบประมาณ (บาท)</th>
                                                                <th style="width: 170px;">เงินนอกงบ / เงินบำรุง (บาท)</th>
                                                                <th style="width: 170px;">รวมทั้งสิ้น (บาท)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($pDef['items'] as $item)
                                                                <tr data-subplan-code="{{ $item['code'] }}">
                                                                    <td class="text-center font-monospace text-secondary fw-semibold">{{ $item['code'] }}</td>
                                                                    <td class="text-start">{{ $item['name'] }}</td>
                                                                    <td>
                                                                        <input type="number" step="0.01" class="form-control form-control-sm text-end input-subplan-bg"
                                                                               id="subplan_bg_{{ $item['code'] }}" 
                                                                               value="{{ number_format($item['budget_amt'], 2, '.', '') }}"
                                                                               oninput="calcSubPlanTotal('{{ $item['code'] }}')">
                                                                    </td>
                                                                    <td>
                                                                        <input type="number" step="0.01" class="form-control form-control-sm text-end input-subplan-nonbg"
                                                                               id="subplan_nonbg_{{ $item['code'] }}" 
                                                                               value="{{ number_format($item['non_budget_amt'], 2, '.', '') }}"
                                                                               oninput="calcSubPlanTotal('{{ $item['code'] }}')">
                                                                    </td>
                                                                    <td class="text-end font-monospace fw-bold fs-7 text-dark" id="subplan_total_{{ $item['code'] }}">
                                                                        {{ number_format($item['total_amt'], 2) }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: นำเข้าไฟล์ M1625 ZIP -->
<!-- ========================================================================= -->
<div class="modal fade" id="importPlanfinMdbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header text-white py-2.5" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                <h6 class="modal-title fw-bold" style="font-size: 0.95rem;">
                    <i class="bi bi-database-fill-up me-1.5"></i> นำเข้าข้อมูลแผนเงินบำรุง MOC PlanFin hfo (.zip)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2.5 px-3 small border-0 d-flex gap-2 align-items-center mb-3">
                    <i class="bi bi-info-circle-fill text-info fs-5"></i>
                    <div style="font-size: 0.82rem;">
                        ระบบจะวิเคราะห์ไฟล์บีบอัดฐานข้อมูลของกระทรวง (ไฟล์ M) เพื่อดึง <strong>เป้าหมายแผนที่อนุมัติ (Plan Targets)</strong> และตรวจสอบความสอดคล้องกับงบทดลอง
                    </div>
                </div>

                <!-- Form Upload -->
                <form id="planfinAnalyzeForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-end mb-3">
                        <div class="col-md-9 col-sm-12">
                            <label class="form-label fw-bold text-secondary mb-1" style="font-size: 0.82rem;">ไฟล์ฐานข้อมูลกระทรวง (.zip หรือ .mdb)</label>
                            <input type="file" class="form-control form-control-sm" id="pf_file" name="file" accept=".zip,.mdb" required>
                        </div>
                        <div class="col-md-3 col-sm-12 mt-2 mt-md-0">
                            <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold py-1.5" style="background: #4f46e5;">
                                <span class="spinner-border spinner-border-sm d-none" id="analyzePfSpinner"></span>
                                <i class="bi bi-search me-1"></i> วิเคราะห์ไฟล์
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Analysis Result Container -->
                <div id="pfAnalysisContainer" class="d-none mt-3">
                    <hr class="text-muted opacity-25 my-3">
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="row g-2">
                            <div class="col-6 small">
                                <span>รหัสหน่วยบริการ:</span> <strong id="resHcode" class="text-primary">-</strong>
                            </div>
                            <div class="col-6 small text-end">
                                <span>ข้อมูลงบทดลองสะสมถึง:</span> <strong id="resLatestMonth" class="text-success">-</strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary mb-2" style="font-size: 0.85rem;">
                        <i class="bi bi-check2-circle text-primary me-1"></i> เลือกรอบแผนที่ต้องการนำเข้าเป็นเป้าหมาย:
                    </h6>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover table-sm mb-0 align-middle" id="pfPlansTable" style="font-size: 0.82rem;">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-2 ps-3">รหัสรอบแผน</th>
                                    <th class="py-2">คำอธิบาย</th>
                                    <th class="text-end py-2">จำนวนหมวด</th>
                                    <th class="text-center py-2" style="width: 140px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Filled by JS -->
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

<!-- ========================================================================= -->
<!-- MODAL 2: ดูผังจับคู่บัญชี (Account Mapping) -->
<!-- ========================================================================= -->
<div class="modal fade" id="mappingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header text-white py-2.5 px-3 px-md-4" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle p-1.5 bg-white bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-diagram-3-fill fs-6"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" style="font-size: 0.95rem;">
                            ผังจับคู่บัญชี 10 หลัก เข้ากับหมวด PlanFin
                        </h6>
                        <div class="small text-white-50" style="font-size: 0.72rem;">
                            ผังบัญชี GL ทั้งหมดที่เชื่อมโยงกับหมวดแผนยุทธศาสตร์ PlanFin
                        </div>
                    </div>
                    <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle px-2.5 py-1 ms-2" id="mappingTotalCount" style="font-size: 0.75rem;">
                        450+ รายการ
                    </span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- Filter Section with List Box -->
                <div class="row g-2 mb-3 p-3 rounded-4 bg-light border align-items-center">
                    <div class="col-md-7 col-12">
                        <label class="form-label small fw-bold text-secondary mb-1" for="mapFilterCategory" style="font-size: 0.82rem;">
                            <i class="bi bi-funnel-fill text-primary me-1"></i> กรองตามหมวดแผน (List Box):
                        </label>
                        <select id="mapFilterCategory" class="form-select form-select-sm rounded-pill px-3 fw-semibold shadow-xs" onchange="applyCategoryFilter()" style="font-size: 0.85rem; border-color: #cbd5e1; cursor: pointer;">
                            <option value="">-- แสดงทุกหมวดแผนทั้งหมด (All Categories) --</option>
                            @foreach($categories as $cat)
                                @if(!in_array($cat->plan_code, ['P13S', 'P26S', 'P27S', 'P28', 'P29']))
                                    <option value="{{ $cat->plan_code }}">{{ $cat->plan_code }} - {{ $cat->plan_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 col-12 text-md-end mt-2 mt-md-0">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold shadow-xs" onclick="resetMappingFilters()" style="font-size: 0.8rem;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> รีเซ็ตตัวกรอง
                        </button>
                    </div>
                </div>

                <!-- DataTable Container -->
                <div class="table-responsive rounded-3 border p-3 bg-white shadow-xs">
                    <table class="table table-hover table-striped table-sm mb-0 align-middle w-100" id="mappingsMasterTable" style="font-size: 0.82rem;">
                        <thead class="table-light border-bottom">
                            <tr>
                                <th class="py-2.5 text-center" style="width: 50px;">#</th>
                                <th class="py-2.5" style="width: 160px;">รหัสบัญชี 10 หลัก</th>
                                <th class="py-2.5">ชื่อบัญชี</th>
                                <th class="py-2.5 text-center" style="width: 110px;">รหัส PlanFin</th>
                                <th class="py-2.5" style="width: 260px;">ชื่อหมวด PlanFin</th>
                            </tr>
                        </thead>
                        <tbody id="mappingsTableBody">
                            <!-- Populated via DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab 2 Simulator Logic
    const revCodes = ['P04','P05','P06','P61','P07','P08','P09','P10','P11','P12','P121','P13'];
    const expCodes = ['P14','P15','P151','P16','P17','P18','P19','P20','P21','P22','P23','P24','P241','P25','P251'];

    // =========================================================================
    // Interactive Sub-Row Toggles (Expand / Collapse)
    // =========================================================================
    function toggleSubRows(groupClass, btn) {
        const rows = document.querySelectorAll('.' + groupClass);
        if (!rows.length) return;
        const isHidden = rows[0].classList.contains('d-none');
        rows.forEach(r => {
            if (isHidden) {
                r.classList.remove('d-none');
            } else {
                r.classList.add('d-none');
            }
        });
        if (btn) {
            if (isHidden) {
                btn.classList.add('expanded');
            } else {
                btn.classList.remove('expanded');
            }
        }
        const prefix = groupClass.startsWith('t1_') ? 't1' : (groupClass.startsWith('t1m_') ? 't1m' : 't2');
        checkAllSubRowsState(prefix);
    }

    function toggleAllSubRows(prefix, btn) {
        const subRows = document.querySelectorAll('.sub-row-' + prefix);
        if (!subRows.length) return;
        
        let containerId = 'tab2-content';
        if (prefix === 't1') containerId = 'subtab-cum-content';
        else if (prefix === 't1m') containerId = 'subtab-monthly-content';

        const icon = document.getElementById('iconToggleAll_' + prefix);
        const isCurrentlyOpen = btn.classList.contains('active');
        
        if (!isCurrentlyOpen) {
            // เปิด: แสดงผังบัญชีย่อยทั้งหมด
            subRows.forEach(r => r.classList.remove('d-none'));
            document.querySelectorAll('#' + containerId + ' .btn-toggle-sub').forEach(b => b.classList.add('expanded'));
            btn.classList.add('active', 'btn-primary', 'text-white');
            btn.classList.remove('btn-outline-secondary');
            if (icon) {
                icon.className = 'bi bi-toggle-on fs-5 text-white';
            }
        } else {
            // ปิด: ซ่อนผังบัญชีย่อยทั้งหมด
            subRows.forEach(r => r.classList.add('d-none'));
            document.querySelectorAll('#' + containerId + ' .btn-toggle-sub').forEach(b => b.classList.remove('expanded'));
            btn.classList.remove('active', 'btn-primary', 'text-white');
            btn.classList.add('btn-outline-secondary');
            if (icon) {
                icon.className = 'bi bi-toggle-off fs-5 text-secondary';
            }
        }
    }

    function navigateToPeriod(period) {
        const activeSub = document.querySelector('#tab1SubNav .nav-link.active');
        let hash = '';
        if (activeSub && activeSub.id === 'subtab-monthly-nav') {
            hash = '#subtab-monthly';
        } else if (activeSub && activeSub.id === 'subtab-matrix-nav') {
            hash = '#subtab-matrix';
        }
        window.location.href = "{{ url('hosfin/planfin') }}?budget_year={{ $budgetYear }}&period=" + period + hash;
    }

    function toggleMatrixPlanSubtext(btn) {
        const table = document.getElementById('tableMatrix12');
        const icon = document.getElementById('iconToggleMatrixPlan');
        if (!table) return;

        if (table.classList.contains('table-matrix-hide-plan')) {
            // แสดงบรรทัดแผน
            table.classList.remove('table-matrix-hide-plan');
            btn.classList.add('active', 'btn-primary', 'text-white');
            btn.classList.remove('btn-outline-secondary');
            if (icon) icon.className = 'bi bi-toggle-on fs-5 text-white';
        } else {
            // ซ่อนบรรทัดแผน
            table.classList.add('table-matrix-hide-plan');
            btn.classList.remove('active', 'btn-primary', 'text-white');
            btn.classList.add('btn-outline-secondary');
            if (icon) icon.className = 'bi bi-toggle-off fs-5 text-secondary';
        }
    }

    function checkAllSubRowsState(prefix) {
        const totalRows = document.querySelectorAll('.sub-row-' + prefix);
        const hiddenRows = document.querySelectorAll('.sub-row-' + prefix + '.d-none');
        const btn = document.getElementById('btnToggleAll_' + prefix);
        const icon = document.getElementById('iconToggleAll_' + prefix);
        if (!btn) return;
        
        if (hiddenRows.length === 0 && totalRows.length > 0) {
            btn.classList.add('active', 'btn-primary', 'text-white');
            btn.classList.remove('btn-outline-secondary');
            if (icon) icon.className = 'bi bi-toggle-on fs-5 text-white';
        } else if (hiddenRows.length === totalRows.length) {
            btn.classList.remove('active', 'btn-primary', 'text-white');
            btn.classList.add('btn-outline-secondary');
            if (icon) icon.className = 'bi bi-toggle-off fs-5 text-secondary';
        }
    }

    function expandAllSubRows(prefix) {
        const btn = document.getElementById('btnToggleAll_' + prefix);
        if (btn && !btn.classList.contains('active')) {
            toggleAllSubRows(prefix, btn);
        }
    }

    function collapseAllSubRows(prefix) {
        const btn = document.getElementById('btnToggleAll_' + prefix);
        if (btn && btn.classList.contains('active')) {
            toggleAllSubRows(prefix, btn);
        }
    }

    // =========================================================================
    // Two-Way Sync Calculations: Sub-accounts <-> Parent Categories
    // =========================================================================
    function onSubGrowthChange(parentCode, subCode, growthVal) {
        const sanitized = subCode.replace(/\./g, '_');
        const targetInput = document.getElementById('subtarget_' + sanitized);
        const tr = document.querySelector(`tr[data-subcode="${subCode}"]`);
        const base = parseFloat(tr?.getAttribute('data-base')) || 0;

        const growth = parseFloat(growthVal) || 0;
        const newTarget = base * (1 + (growth / 100));
        if (targetInput) targetInput.value = newTarget.toFixed(2);

        syncParentFromSubAccounts(parentCode);
    }

    function onSubTargetChange(parentCode, subCode, targetVal) {
        const sanitized = subCode.replace(/\./g, '_');
        const growthInput = document.getElementById('subgrowth_' + sanitized);
        const tr = document.querySelector(`tr[data-subcode="${subCode}"]`);
        const base = parseFloat(tr?.getAttribute('data-base')) || 0;

        const target = parseFloat(targetVal) || 0;
        if (base > 0) {
            const growth = ((target - base) / base) * 100;
            if (growthInput) growthInput.value = growth.toFixed(1);
        } else {
            if (growthInput) growthInput.value = '0.0';
        }

        syncParentFromSubAccounts(parentCode);
    }

    function syncParentFromSubAccounts(parentCode) {
        const subRows = document.querySelectorAll(`tr.sub-row-t2[data-parent="${parentCode}"]`);
        if (!subRows.length) return;

        let sumTarget = 0;
        subRows.forEach(tr => {
            const subCode = tr.getAttribute('data-subcode');
            const sanitized = subCode.replace(/\./g, '_');
            const targetInp = document.getElementById('subtarget_' + sanitized);
            if (targetInp) {
                sumTarget += (parseFloat(targetInp.value) || 0);
            }
        });

        const parentTargetInp = document.getElementById('target_' + parentCode);
        const parentGrowthInp = document.getElementById('growth_' + parentCode);
        const dispTargetEl = document.getElementById('disp_target_' + parentCode);
        const dispGrowthEl = document.getElementById('disp_growth_' + parentCode);
        const parentTr = document.querySelector(`tr[data-code="${parentCode}"]`);
        const parentBase = parseFloat(parentTr?.getAttribute('data-base')) || 0;

        let growth = 0;
        if (parentBase > 0) {
            growth = ((sumTarget - parentBase) / parentBase) * 100;
        }

        if (parentTargetInp) parentTargetInp.value = sumTarget.toFixed(2);
        if (parentGrowthInp) parentGrowthInp.value = growth.toFixed(2);

        if (dispTargetEl) {
            dispTargetEl.innerText = sumTarget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        if (dispGrowthEl) {
            dispGrowthEl.innerText = (growth > 0 ? '+' : '') + growth.toFixed(2) + '%';
            if (growth > 0) {
                dispGrowthEl.className = 'badge bg-success-subtle text-success border border-success-subtle font-monospace px-2 py-1 shadow-xs';
            } else if (growth < 0) {
                dispGrowthEl.className = 'badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2 py-1 shadow-xs';
            } else {
                dispGrowthEl.className = 'badge bg-white text-secondary border font-monospace px-2 py-1 shadow-xs';
            }
        }

        recalculateSimulator();
    }

    function onParentGrowthChange(code) {
        const growthInput = document.getElementById('growth_' + code);
        const targetInput = document.getElementById('target_' + code);
        const tr = document.querySelector(`tr[data-code="${code}"]`);
        const base = parseFloat(tr.getAttribute('data-base')) || 0;

        const growth = parseFloat(growthInput.value) || 0;
        const newTarget = base * (1 + (growth / 100));
        targetInput.value = newTarget.toFixed(2);

        // Pro-rate to sub-accounts
        const subRows = document.querySelectorAll(`tr.sub-row-t2[data-parent="${code}"]`);
        subRows.forEach(subTr => {
            const subCode = subTr.getAttribute('data-subcode');
            const sanitized = subCode.replace(/\./g, '_');
            const subBase = parseFloat(subTr.getAttribute('data-base')) || 0;
            const subTargetInp = document.getElementById('subtarget_' + sanitized);
            const subGrowthInp = document.getElementById('subgrowth_' + sanitized);

            const subTarget = subBase * (1 + (growth / 100));
            if (subTargetInp) subTargetInp.value = subTarget.toFixed(2);
            if (subGrowthInp) subGrowthInp.value = growth.toFixed(1);
        });

        recalculateSimulator();
    }

    function onParentTargetChange(code) {
        const growthInput = document.getElementById('growth_' + code);
        const targetInput = document.getElementById('target_' + code);
        const tr = document.querySelector(`tr[data-code="${code}"]`);
        const base = parseFloat(tr.getAttribute('data-base')) || 0;

        const target = parseFloat(targetInput.value) || 0;
        let growth = 0;
        if (base > 0) {
            growth = ((target - base) / base) * 100;
            growthInput.value = growth.toFixed(2);
        } else {
            growthInput.value = '0.00';
        }

        // Pro-rate to sub-accounts
        const subRows = document.querySelectorAll(`tr.sub-row-t2[data-parent="${code}"]`);
        if (subRows.length > 0 && base > 0) {
            const ratio = target / base;
            subRows.forEach(subTr => {
                const subCode = subTr.getAttribute('data-subcode');
                const sanitized = subCode.replace(/\./g, '_');
                const subBase = parseFloat(subTr.getAttribute('data-base')) || 0;
                const subTargetInp = document.getElementById('subtarget_' + sanitized);
                const subGrowthInp = document.getElementById('subgrowth_' + sanitized);

                const subTarget = subBase * ratio;
                if (subTargetInp) subTargetInp.value = subTarget.toFixed(2);
                if (subGrowthInp) subGrowthInp.value = growth.toFixed(1);
            });
        }

        recalculateSimulator();
    }

    // Operational Sub-Plans Calculations
    function calcSubPlanTotal(code) {
        const bg = parseFloat(document.getElementById('subplan_bg_' + code)?.value || 0);
        const nonBg = parseFloat(document.getElementById('subplan_nonbg_' + code)?.value || 0);
        const totalEl = document.getElementById('subplan_total_' + code);
        if (totalEl) {
            totalEl.innerText = (bg + nonBg).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    function recalculateSimulator() {
        let sumRev = 0;
        let sumExp = 0;

        revCodes.forEach(c => {
            const inp = document.getElementById('target_' + c);
            if (inp) sumRev += (parseFloat(inp.value) || 0);
        });

        expCodes.forEach(c => {
            const inp = document.getElementById('target_' + c);
            if (inp) sumExp += (parseFloat(inp.value) || 0);
        });

        const cellP13S = document.getElementById('cell_P13S');
        const cellP26S = document.getElementById('cell_P26S');
        const cellP27S = document.getElementById('cell_P27S');
        const cellP29R = document.getElementById('cell_P29-R');
        const cellP29E = document.getElementById('cell_P29-E');
        const cellP29 = document.getElementById('cell_P29');

        if (cellP13S) cellP13S.innerText = sumRev.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (cellP26S) cellP26S.innerText = sumExp.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        const ni = sumRev - sumExp;
        if (cellP27S) {
            cellP27S.innerText = (ni >= 0 ? '+' : '') + ni.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            cellP27S.className = 'text-end font-monospace fw-bold fs-6 ' + (ni >= 0 ? 'text-success' : 'text-danger');
        }

        const p13Val = parseFloat(document.getElementById('target_P13')?.value || 0);
        const p121Val = parseFloat(document.getElementById('target_P121')?.value || 0);
        const p24Val = parseFloat(document.getElementById('target_P24')?.value || 0);
        const p251Val = parseFloat(document.getElementById('target_P251')?.value || 0);

        const p29r = sumRev - p13Val - p121Val;
        const p29e = sumExp - p24Val - p251Val;
        const ebitda = p29r - p29e;

        if (cellP29R) cellP29R.innerText = p29r.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (cellP29E) cellP29E.innerText = p29e.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (cellP29) {
            cellP29.innerText = ebitda.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            cellP29.className = 'text-end font-monospace fw-bold fs-6 text-primary';
        }

        // Status Badge
        const statusBadge = document.getElementById('simStatusBadge');
        if (statusBadge) {
            if (ebitda > 0) {
                statusBadge.innerHTML = '<span class="badge bg-success px-3 py-1.5 rounded-pill">🟢 เกินดุล</span>';
            } else {
                statusBadge.innerHTML = '<span class="badge bg-danger px-3 py-1.5 rounded-pill">🔴 ขาดดุล</span>';
            }
        }

        // Live banner KPI
        const cap = Math.max(0, ebitda * 0.20);
        const simNIEl = document.getElementById('simNI');
        if (simNIEl) {
            simNIEl.innerText = (ni >= 0 ? '+' : '') + ni.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            simNIEl.className = 'mb-0 fw-black ' + (ni >= 0 ? 'text-success' : 'text-danger');
        }
        const simNIBadge = document.getElementById('simNIBadge');
        if (simNIBadge) {
            simNIBadge.className = 'badge rounded-pill px-2 py-0.5 ' + (ni >= 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle');
            simNIBadge.innerText = ni >= 0 ? '✓ เกินดุล' : '⚠ ขาดดุล';
        }
        const simCard1 = document.getElementById('simCard1');
        if (simCard1) {
            simCard1.style.setProperty('border-top', '4px solid ' + (ni >= 0 ? '#10b981' : '#ef4444'), 'important');
        }
        const simEBITDAEl = document.getElementById('simEBITDA');
        if (simEBITDAEl) {
            simEBITDAEl.innerText = ebitda.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        const simCapEl = document.getElementById('simCap');
        if (simCapEl) {
            simCapEl.innerText = cap.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    // Save targets via AJAX (Saves Parent Categories, Sub-Accounts, and Sub-Plans)
    function savePlanfinTargets() {
        const spinner = document.getElementById('saveTargetsSpinner');
        spinner.classList.remove('d-none');

        const items = [];

        // 1. Parent Categories
        document.querySelectorAll('#tableSimulator70 tbody tr[data-code]').forEach(tr => {
            const code = tr.getAttribute('data-code');
            if (code && !['P13S','P26S','P27S','P29-R','P29-E','P29','P28'].includes(code)) {
                const targetInp = document.getElementById('target_' + code);
                const growthInp = document.getElementById('growth_' + code);
                const base = parseFloat(tr.getAttribute('data-base')) || 0;
                if (targetInp) {
                    items.push({
                        plan_code: code,
                        baseline_amount: base,
                        growth_rate: parseFloat(growthInp?.value || 0),
                        target_amount: parseFloat(targetInp.value || 0)
                    });
                }
            }
        });

        // 2. Sub-Accounts
        document.querySelectorAll('#tableSimulator70 tbody tr.sub-row-t2[data-subcode]').forEach(tr => {
            const subCode = tr.getAttribute('data-subcode');
            const sanitized = subCode.replace(/\./g, '_');
            const targetInp = document.getElementById('subtarget_' + sanitized);
            const growthInp = document.getElementById('subgrowth_' + sanitized);
            const base = parseFloat(tr.getAttribute('data-base')) || 0;
            if (targetInp) {
                items.push({
                    plan_code: subCode,
                    baseline_amount: base,
                    growth_rate: parseFloat(growthInp?.value || 0),
                    target_amount: parseFloat(targetInp.value || 0)
                });
            }
        });

        // 3. Operational Sub-Plans Items (Plans 2 through 7)
        document.querySelectorAll('tr[data-subplan-code]').forEach(tr => {
            const code = tr.getAttribute('data-subplan-code');
            const bgInp = document.getElementById('subplan_bg_' + code);
            const nonBgInp = document.getElementById('subplan_nonbg_' + code);
            if (bgInp || nonBgInp) {
                const bg = parseFloat(bgInp?.value || 0);
                const nonBg = parseFloat(nonBgInp?.value || 0);
                items.push({
                    plan_code: code,
                    baseline_amount: bg,
                    growth_rate: 0,
                    target_amount: nonBg,
                    notes: 'subplan_item'
                });
            }
        });

        fetch('{{ url("hosfin/planfin/save_target") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                budget_year: {{ $targetSimYear }},
                round_no: '1st',
                items: items
            })
        })
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.success) {
                alert(data.message || 'บันทึกแผนประมาณการเรียบร้อยแล้ว');
            } else {
                alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่ทราบสาเหตุ'));
            }
        })

        .catch(err => {
            spinner.classList.add('d-none');
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์: ' + err.message);
        });
    }

    // Modal MDB Analysis
    let tempToken = '';
    document.getElementById('planfinAnalyzeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fileInput = document.getElementById('pf_file');
        if (!fileInput.files.length) return;

        const spinner = document.getElementById('analyzePfSpinner');
        spinner.classList.remove('d-none');

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ url("hosfin/planfin/analyze_mdb") }}', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.success) {
                tempToken = data.temp_token;
                document.getElementById('resHcode').innerText = data.hcode || '10989';
                document.getElementById('resLatestMonth').innerText = data.latest_month || '-';

                const tbody = document.querySelector('#pfPlansTable tbody');
                tbody.innerHTML = '';

                data.plans.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="py-2 ps-3 fw-bold">${p.period_no}</td>
                        <td class="py-2">${p.label} ${p.is_recommended ? '<span class="badge bg-success ms-1">แนะนำ</span>' : ''}</td>
                        <td class="text-end py-2">${p.count} หมวด</td>
                        <td class="text-center py-2">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" onclick="confirmImportPlan('${p.period_no}')">
                                <i class="bi bi-cloud-arrow-down me-1"></i> นำเข้ารอบนี้
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('pfAnalysisContainer').classList.remove('d-none');
            } else {
                alert('วิเคราะห์ไฟล์ล้มเหลว: ' + data.message);
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            alert('เกิดข้อผิดพลาด: ' + err.message);
        });
    });

    function confirmImportPlan(periodNo) {
        if (!confirm(`ยืนยันการนำเข้าแผนเงินบำรุงรอบ ${periodNo} เข้าสู่ฐานข้อมูล?`)) return;

        fetch('{{ url("hosfin/planfin/import_mdb") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                temp_token: tempToken,
                period_no: periodNo
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('นำเข้าล้มเหลว: ' + data.message);
            }
        })
        .catch(err => alert('เกิดข้อผิดพลาด: ' + err.message));
    }

    // =========================================================================
    // Account Mapping Modal with DataTables & Category List Box Filter
    // =========================================================================
    let mappingsDataTable = null;
    let allMappingsData = [];

    document.getElementById('mappingModal').addEventListener('show.bs.modal', function () {
        if (allMappingsData.length === 0) {
            const tbody = document.getElementById('mappingsTableBody');
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดข้อมูลผังจับคู่บัญชี 10 หลัก...</td></tr>`;
            fetch('{{ url("hosfin/planfin/mappings") }}')
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                allMappingsData = data;
                initMappingDataTable(allMappingsData);
            })
            .catch(err => {
                console.error('Error fetching mappings:', err);
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle me-1"></i>เกิดข้อผิดพลาดในการโหลดข้อมูลผังบัญชี</td></tr>`;
            });
        }
    });

    document.getElementById('mappingModal').addEventListener('shown.bs.modal', function () {
        if (mappingsDataTable) {
            mappingsDataTable.columns.adjust().draw(false);
        }
    });

    function initMappingDataTable(data) {
        // Update header badge count
        const totalCountEl = document.getElementById('mappingTotalCount');
        if (totalCountEl) {
            totalCountEl.textContent = data.length.toLocaleString() + ' รายการ';
        }

        // Populate Category List Box dynamically with counts
        const catMap = {};
        data.forEach(item => {
            const code = (item.plan_code || '').trim();
            const name = (item.plan_name || '').trim();
            if (code) {
                if (!catMap[code]) {
                    catMap[code] = { code: code, name: name, count: 0 };
                }
                catMap[code].count++;
            }
        });

        const select = document.getElementById('mapFilterCategory');
        if (select) {
            select.innerHTML = `<option value="">-- แสดงทุกหมวดแผนทั้งหมด (${data.length} รายการ) --</option>`;
            Object.keys(catMap).sort().forEach(code => {
                const opt = document.createElement('option');
                opt.value = code;
                opt.textContent = `[${code}] ${catMap[code].name} (${catMap[code].count} รายการ)`;
                select.appendChild(opt);
            });
        }

        // Destroy previous instance if any
        if ($.fn.DataTable.isDataTable('#mappingsMasterTable')) {
            $('#mappingsMasterTable').DataTable().destroy();
        }

        // Initialize DataTable
        mappingsDataTable = $('#mappingsMasterTable').DataTable({
            data: data,
            columns: [
                {
                    data: null,
                    className: 'text-center text-muted fw-semibold ps-3',
                    orderable: false,
                    width: '50px',
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'account_code',
                    className: 'font-monospace fw-bold text-dark text-nowrap',
                    width: '160px',
                    render: function(data) {
                        return data ? `<i class="bi bi-hash text-muted me-0.5"></i>${data}` : '-';
                    }
                },
                {
                    data: 'account_name',
                    className: 'fw-medium text-dark',
                    render: function(data) {
                        return data || '-';
                    }
                },
                {
                    data: 'plan_code',
                    className: 'text-center fw-bold text-nowrap',
                    width: '110px',
                    render: function(data) {
                        return `<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 font-monospace">${data || '-'}</span>`;
                    }
                },
                {
                    data: 'plan_name',
                    className: 'text-secondary fw-semibold',
                    width: '260px',
                    render: function(data) {
                        return data || '-';
                    }
                }
            ],
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                ['10 รายการ', '25 รายการ', '50 รายการ', '100 รายการ', 'แสดงทั้งหมด']
            ],
            language: {
                search: '<i class="bi bi-search me-1"></i>ค้นหา:',
                searchPlaceholder: 'พิมพ์รหัส, ชื่อบัญชี...',
                lengthMenu: 'แสดง _MENU_',
                info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ',
                infoEmpty: 'ไม่พบรายการที่ค้นหา',
                infoFiltered: '(กรองจากทั้งหมด _MAX_ รายการ)',
                zeroRecords: '<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-4 d-block mb-1"></i>ไม่พบข้อมูลบัญชีที่ตรงกับเงื่อนไข</div>',
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            },
            dom: "<'row g-2 mb-3 align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 d-flex justify-content-md-end'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row g-2 mt-3 align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
            autoWidth: false
        });

        // Dynamic numbering on sort or page
        mappingsDataTable.on('draw.dt', function () {
            const info = mappingsDataTable.page.info();
            mappingsDataTable.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = info.start + i + 1;
            });
        });

        // Bind filter event
        $('#mapFilterCategory').off('change').on('change', function() {
            applyCategoryFilter();
        });
    }

    function applyCategoryFilter() {
        if (!mappingsDataTable) return;
        const sel = document.getElementById('mapFilterCategory').value.trim();
        if (sel) {
            // Exact regex match on Column 3 (plan_code)
            mappingsDataTable.column(3).search('^' + sel + '$', true, false).draw();
        } else {
            mappingsDataTable.column(3).search('').draw();
        }
    }

    function resetMappingFilters() {
        const select = document.getElementById('mapFilterCategory');
        if (select) select.value = '';
        if (mappingsDataTable) {
            mappingsDataTable.search('').column(3).search('').draw();
        }
    }

    // Init Recalculate on load & Sub-tab state
    document.addEventListener('DOMContentLoaded', function() {
        recalculateSimulator();

        // Restore active sub-tab from hash if present
        const hash = window.location.hash;
        if (hash === '#subtab-monthly' || hash === '#monthly') {
            const el = document.getElementById('subtab-monthly-nav');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        } else if (hash === '#subtab-matrix' || hash === '#matrix') {
            const el = document.getElementById('subtab-matrix-nav');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        }

        document.querySelectorAll('#tab1SubNav button[data-bs-toggle="pill"]').forEach(btn => {
            btn.addEventListener('shown.bs.tab', function(e) {
                const target = e.target.getAttribute('data-bs-target');
                if (target === '#subtab-monthly-content') history.replaceState(null, null, '#subtab-monthly');
                else if (target === '#subtab-matrix-content') history.replaceState(null, null, '#subtab-matrix');
                else history.replaceState(null, null, '#subtab-cum');
            });
        });
    });
</script>
@endsection
