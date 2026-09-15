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
    font-family: var(--bs-font-monospace);
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
    font-family: var(--bs-font-monospace);
  }
  .input-plan70-sub:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
    background-color: #faf5ff;
  }
  .input-subplan-bg, .input-subplan-nonbg {
    font-size: 0.8rem;
    font-weight: 600;
    text-align: right;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    padding: 0.2rem 0.5rem;
    background-color: #ffffff;
    font-family: var(--bs-font-monospace);
  }
  .input-subplan-bg:focus, .input-subplan-nonbg:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
    background-color: #faf5ff;
  }
  .input-growth-sub {
    width: 75px;
    font-size: 0.78rem;
    text-align: center;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    padding: 0.2rem 0.3rem;
  }
  .btn-outline-indigo {
    color: #4f46e5;
    border-color: #6366f1;
    background-color: #ffffff;
  }
  .btn-outline-indigo:hover {
    color: #ffffff;
    background-color: #4f46e5;
    border-color: #4f46e5;
  }
  .text-indigo {
    color: #4f46e5 !important;
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
                                                <th class="text-end" style="width: 135px;">แผนทั้งปี</th>
                                                <th class="text-end" style="width: 135px;">แผนสะสม ({{ $cumMonths }} ด.)</th>
                                                <th class="text-end" style="width: 145px;">ผลดำเนินงานจริง</th>
                                                <th class="text-end" style="width: 125px;">ผลต่าง</th>
                                                <th class="text-end" style="width: 85px;">ร้อยละ</th>
                                                <th class="text-center" style="width: 90px;">สถานะ</th>
                                                <th class="text-center" style="width: 105px; min-width: 100px;">วิเคราะห์ / AI</th>
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
                                                        <td class="text-center text-muted" style="font-size: 0.75rem;">-</td>
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
                                                        <td class="text-center text-nowrap">
                                                            @if(in_array($code, ['P14', 'P15', 'P151', 'P16', 'P04', 'P05', 'P06', 'P61', 'P07', 'P08', 'P09', 'P10', 'P13S', 'P26S', 'P27S']))
                                                                <div class="d-inline-flex align-items-center gap-1">
                                                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5 shadow-xs fw-bold"
                                                                            onclick="openServiceDrilldownModal('{{ $code }}', '{{ addslashes($r['name']) }}')"
                                                                            title="คลิกเพื่อดูกราฟเทียบงบทดลอง vs ยอดใช้จริง HOSxP และปริมาณคนไข้"
                                                                            style="font-size: 0.72rem;">
                                                                        <i class="bi bi-bar-chart-line-fill text-primary"></i> <span>เทียบ</span>
                                                                    </button>
                                                                    <button type="button" class="btn btn-xs btn-outline-indigo rounded-circle p-1 shadow-xs"
                                                                            onclick="openPlanfinAiModal('{{ $code }}', '{{ addslashes($r['name']) }}')"
                                                                            title="ถามน้องมีตังค์ (RiMS AI) วิเคราะห์ผลต่าง"
                                                                            style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-color: #a5b4fc; background: #eef2ff;">
                                                                        <span style="font-size: 0.75rem;">🤖</span>
                                                                    </button>
                                                                </div>
                                                            @else
                                                                <span class="text-muted" style="font-size: 0.72rem;">-</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>

                                                @if($hasSubs)
                                                    @foreach($subAccountsByPlan[$code] as $sub)
                                                        @php
                                                            $share = ($r['actual_cum'] != 0) ? ($sub['actual_cum'] / $r['actual_cum']) * 100 : 0;
                                                            $hasPlan = ($sub['plan_annual'] ?? 0) != 0;
                                                        @endphp
                                                        <tr class="sub-row-t1 t1_{{ $code }} d-none">
                                                            <td class="text-center font-monospace text-muted ps-2" style="font-size: 0.76rem;">
                                                                <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">{{ $sub['account_code'] }}</span>
                                                            </td>
                                                            <td class="sub-indent text-secondary" style="font-size: 0.82rem;">
                                                                <span class="sub-dash"><i class="bi bi-dash-lg"></i></span>{{ $sub['account_name'] }}
                                                            </td>
                                                            <td class="text-end font-monospace {{ $hasPlan ? 'text-dark' : 'text-muted' }}" style="font-size: 0.76rem;">
                                                                {{ $hasPlan ? number_format($sub['plan_annual'], 2) : '-' }}
                                                            </td>
                                                            <td class="text-end font-monospace {{ $hasPlan ? 'text-dark' : 'text-muted' }}" style="font-size: 0.76rem;">
                                                                {{ $hasPlan ? number_format($sub['plan_cum'], 2) : '-' }}
                                                            </td>
                                                            <td class="text-end font-monospace fw-semibold {{ $sub['actual_cum'] < 0 ? 'text-danger' : 'text-dark' }}" style="font-size: 0.8rem;">
                                                                {{ number_format($sub['actual_cum'], 2) }}
                                                            </td>
                                                            <td class="text-end font-monospace {{ $hasPlan ? ($sub['status_cum'] === 'OK' ? 'text-success' : 'text-danger') : 'text-muted' }}" style="font-size: 0.76rem;">
                                                                @if($hasPlan)
                                                                    {{ ($sub['diff_cum'] > 0 ? '+' : '') . number_format($sub['diff_cum'], 2) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="text-end font-monospace {{ $hasPlan ? ($sub['status_cum'] === 'OK' ? 'text-success' : 'text-danger') : 'text-muted' }}" style="font-size: 0.76rem;">
                                                                @if($hasPlan)
                                                                    {{ number_format($sub['percent_cum'], 1) }}%
                                                                @else
                                                                    {{ number_format($share, 1) }}%
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if($hasPlan && $sub['status_cum'] !== '-')
                                                                    <span class="badge-status-{{ $sub['status_cum'] === 'OK' ? 'ok' : 'not-ok' }}" style="font-size: 0.65rem; padding: 2px 6px;">
                                                                        {{ $sub['status_cum'] }}
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">ย่อย</span>
                                                                @endif
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
                                                <th class="text-end" style="width: 135px;">แผนทั้งปี</th>
                                                <th class="text-end" style="width: 135px;">แผนงวดเดือนนี้</th>
                                                <th class="text-end" style="width: 145px;">ผลดำเนินงานจริง</th>
                                                <th class="text-end" style="width: 125px;">ผลต่าง</th>
                                                <th class="text-end" style="width: 85px;">ร้อยละ</th>
                                                <th class="text-center" style="width: 90px;">สถานะ</th>
                                                <th class="text-center" style="width: 105px; min-width: 100px;">วิเคราะห์ / AI</th>
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
                                                        <td class="text-center text-muted" style="font-size: 0.75rem;">-</td>
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
                                                        <td class="text-center text-nowrap">
                                                            @if(in_array($code, ['P14', 'P15', 'P151', 'P16', 'P04', 'P05', 'P06', 'P61', 'P07', 'P08', 'P09', 'P10', 'P13S', 'P26S', 'P27S']))
                                                                <div class="d-inline-flex align-items-center gap-1">
                                                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5 shadow-xs fw-bold"
                                                                            onclick="openServiceDrilldownModal('{{ $code }}', '{{ addslashes($r['name']) }}')"
                                                                            title="คลิกเพื่อดูกราฟเทียบงบทดลอง vs ยอดใช้จริง HOSxP และปริมาณคนไข้"
                                                                            style="font-size: 0.72rem;">
                                                                        <i class="bi bi-bar-chart-line-fill text-primary"></i> <span>เทียบ</span>
                                                                    </button>
                                                                    <button type="button" class="btn btn-xs btn-outline-indigo rounded-circle p-1 shadow-xs"
                                                                            onclick="openPlanfinAiModal('{{ $code }}', '{{ addslashes($r['name']) }}')"
                                                                            title="ถามน้องมีตังค์ (RiMS AI) วิเคราะห์ผลต่าง"
                                                                            style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-color: #a5b4fc; background: #eef2ff;">
                                                                        <span style="font-size: 0.75rem;">🤖</span>
                                                                    </button>
                                                                </div>
                                                            @else
                                                                <span class="text-muted" style="font-size: 0.72rem;">-</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>

                                                @if($hasSubs)
                                                    @foreach($subAccountsByPlan[$code] as $sub)
                                                        @php
                                                            $subActualM = $sub['actual_month'] ?? 0;
                                                            $shareM = ($r['actual_month'] != 0) ? ($subActualM / $r['actual_month']) * 100 : 0;
                                                            $hasPlanM = ($sub['plan_annual'] ?? 0) != 0;
                                                        @endphp
                                                        <tr class="sub-row-t1m t1m_{{ $code }} d-none">
                                                            <td class="text-center font-monospace text-muted ps-2" style="font-size: 0.76rem;">
                                                                <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.72rem;">{{ $sub['account_code'] }}</span>
                                                            </td>
                                                            <td class="sub-indent text-secondary" style="font-size: 0.82rem;">
                                                                <span class="sub-dash"><i class="bi bi-dash-lg"></i></span>{{ $sub['account_name'] }}
                                                            </td>
                                                            <td class="text-end font-monospace {{ $hasPlanM ? 'text-dark' : 'text-muted' }}" style="font-size: 0.76rem;">
                                                                {{ $hasPlanM ? number_format($sub['plan_annual'], 2) : '-' }}
                                                            </td>
                                                            <td class="text-end font-monospace {{ $hasPlanM ? 'text-dark' : 'text-muted' }}" style="font-size: 0.76rem;">
                                                                {{ $hasPlanM ? number_format($sub['plan_month'], 2) : '-' }}
                                                            </td>
                                                            <td class="text-end font-monospace fw-semibold {{ $subActualM < 0 ? 'text-danger' : 'text-dark' }}" style="font-size: 0.8rem;">
                                                                {{ number_format($subActualM, 2) }}
                                                            </td>
                                                            <td class="text-end font-monospace {{ $hasPlanM ? ($sub['status_month'] === 'OK' ? 'text-success' : 'text-danger') : 'text-muted' }}" style="font-size: 0.76rem;">
                                                                @if($hasPlanM)
                                                                    {{ ($sub['diff_month'] > 0 ? '+' : '') . number_format($sub['diff_month'], 2) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="text-end font-monospace {{ $hasPlanM ? ($sub['status_month'] === 'OK' ? 'text-success' : 'text-danger') : 'text-muted' }}" style="font-size: 0.76rem;">
                                                                @if($hasPlanM)
                                                                    {{ number_format($sub['percent_month'], 1) }}%
                                                                @else
                                                                    {{ number_format($shareM, 1) }}%
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if($hasPlanM && $sub['status_month'] !== '-')
                                                                    <span class="badge-status-{{ $sub['status_month'] === 'OK' ? 'ok' : 'not-ok' }}" style="font-size: 0.65rem; padding: 2px 6px;">
                                                                        {{ $sub['status_month'] }}
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">ย่อย</span>
                                                                @endif
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
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-xs fw-bold d-inline-flex align-items-center gap-1.5" 
                                                id="btnOpenMatrixCategoryChart" onclick="openMatrixChartModal('category')" 
                                                title="ดูกราฟเส้นแนวโน้ม 12 เดือนของหมวดหลัก" style="font-size: 0.78rem;">
                                            <i class="bi bi-graph-up-arrow text-primary"></i>
                                            <span>กราฟหมวดหลัก</span>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-indigo rounded-pill px-3 py-1 shadow-xs fw-bold d-inline-flex align-items-center gap-1.5" 
                                                id="btnOpenMatrixSubChart" onclick="openMatrixChartModal('sub')" 
                                                title="ดูกราฟเส้นแนวโน้ม 12 เดือนของผังบัญชีย่อย" style="font-size: 0.78rem;">
                                            <i class="bi bi-bar-chart-steps text-indigo"></i>
                                            <span>กราฟผังบัญชีย่อย</span>
                                        </button>
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
                                        <th class="text-end" style="width: 130px;">แผนทั้งปี {{ $budgetYear }}</th>
                                        <th class="text-end" style="width: 135px;">แผนสะสม ({{ $baseMonths }} ด.)</th>
                                        <th class="text-end" style="width: 135px;">ผลการดำเนินงาน ({{ $baseMonths }} ด.)</th>
                                        <th class="text-end" style="width: 140px;">ประมาณการ ผลดำเนินงานทั้งปี</th>
                                        <th class="text-center" style="width: 100px;">% เติบโต</th>
                                        <th class="text-end" style="width: 185px; min-width: 175px; background-color: #f3f0ff;">แผนประมาณการ ปี {{ $targetSimYear }}</th>
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
                                            <td class="text-end font-monospace text-muted">{{ number_format($r['plan_annual'] ?? 0, 2) }}</td>
                                            <td class="text-end font-monospace text-muted">{{ number_format($r['plan_cum'] ?? 0, 2) }}</td>
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
                                                    <input type="text" inputmode="decimal" class="form-control form-control-sm input-plan70 text-end" 
                                                           id="target_{{ $code }}" value="{{ number_format($r['target_sim'], 2) }}" 
                                                           oninput="handleMoneyInput(this, () => onParentTargetChange('{{ $code }}'))"
                                                           onblur="handleMoneyBlur(this, () => onParentTargetChange('{{ $code }}'))">
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
                                                    <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">{{ number_format($sub['plan_annual'] ?? 0, 2) }}</td>
                                                    <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">{{ number_format($sub['plan_cum_base'] ?? 0, 2) }}</td>
                                                    <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">{{ number_format($sub['y_base_months'], 2) }}</td>
                                                    <td class="text-end font-monospace text-muted" style="font-size: 0.76rem;">{{ number_format($sub['y_base_est'], 2) }}</td>
                                                    <td class="text-center">
                                                        <input type="number" step="0.1" class="form-control form-control-sm input-growth-sub" 
                                                               id="subgrowth_{{ $sanitizedCode }}" 
                                                               value="{{ number_format($sub['growth_rate'], 1, '.', '') }}" 
                                                               oninput="onSubGrowthChange('{{ $code }}', '{{ $sub['account_code'] }}', this.value)">
                                                    </td>
                                                    <td>
                                                        <input type="text" inputmode="decimal" class="form-control form-control-sm input-plan70-sub text-end" 
                                                               id="subtarget_{{ $sanitizedCode }}" 
                                                               value="{{ number_format($sub['target_sim'], 2) }}" 
                                                               oninput="handleMoneyInput(this, () => onSubTargetChange('{{ $code }}', '{{ $sub['account_code'] }}'))"
                                                               onblur="handleMoneyBlur(this, () => onSubTargetChange('{{ $code }}', '{{ $sub['account_code'] }}'))">
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

                            <!-- Smart Procurement Calculator Banner -->
                            <div class="card border-0 shadow-xs rounded-3 mb-3" style="background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%); border: 1px solid #c7d2fe !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom" style="border-color: rgba(99, 102, 241, 0.15) !important;">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-circle p-2 text-white shadow-xs" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                                                <i class="bi bi-magic fs-6"></i>
                                            </span>
                                            <div>
                                                <strong class="text-dark" style="font-size: 0.88rem;">🎯 Smart Procurement Calculator: คำนวณงบจัดซื้อยา/เวชภัณฑ์/Lab จากประมาณการบริการปี {{ $targetSimYear }}</strong>
                                                <div class="text-muted small" style="font-size: 0.74rem;">
                                                    เชื่อมโยงอัตโนมัติจากฐาน HOSxP (มาตรฐาน <code>drg_chrgitem</code> 16 แฟ้ม) และ Unit Cost อัตราการใช้จริง
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span class="badge bg-white text-indigo border border-indigo-subtle px-2.5 py-1 rounded-pill shadow-xs" style="font-size: 0.72rem;">
                                                <i class="bi bi-check2-circle text-success me-1"></i> drg_chrgitem หมวด 3, 4, 5, 7, 8, 10, 13
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3 col-sm-6">
                                            <div class="d-flex align-items-center justify-content-between bg-white px-2.5 py-1.5 rounded-2 border shadow-2xs">
                                                <label class="small text-muted fw-bold mb-0" style="font-size: 0.76rem;">คาดการณ์ผู้ป่วยนอก (OPD):</label>
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <input type="number" id="calcOpGrowth" value="5.0" step="0.5" class="form-control form-control-sm text-end fw-bold px-1 py-0.5" style="width: 58px; font-size: 0.8rem;">
                                                    <span class="small text-muted fw-bold">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <div class="d-flex align-items-center justify-content-between bg-white px-2.5 py-1.5 rounded-2 border shadow-2xs">
                                                <label class="small text-muted fw-bold mb-0" style="font-size: 0.76rem;">คาดการณ์วันนอน (IPD):</label>
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <input type="number" id="calcIpGrowth" value="3.0" step="0.5" class="form-control form-control-sm text-end fw-bold px-1 py-0.5" style="width: 58px; font-size: 0.8rem;">
                                                    <span class="small text-muted fw-bold">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <div class="d-flex align-items-center justify-content-between bg-white px-2.5 py-1.5 rounded-2 border shadow-2xs">
                                                <label class="small text-muted fw-bold mb-0" style="font-size: 0.76rem;">เผื่อสำรอง Safety Stock:</label>
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <input type="number" id="calcSafetyBuffer" value="5.0" step="0.5" class="form-control form-control-sm text-end fw-bold px-1 py-0.5" style="width: 58px; font-size: 0.8rem;">
                                                    <span class="small text-muted fw-bold">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 d-flex gap-1.5">
                                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold flex-fill shadow-xs d-inline-flex align-items-center justify-content-center gap-1"
                                                    id="btnRunProcureCalc" onclick="runSmartProcurementCalc()" style="font-size: 0.78rem; background: #4f46e5; border-color: #4338ca;">
                                                <span class="spinner-border spinner-border-sm d-none" id="calcProcureSpinner"></span>
                                                <i class="bi bi-cpu me-0.5" id="calcProcureIcon"></i>
                                                <span>คำนวณงบแนะนำ</span>
                                            </button>
                                            <button type="button" class="btn btn-outline-indigo btn-sm rounded-pill px-2.5 fw-bold bg-white shadow-xs"
                                                    onclick="consultAiForProcurement()" title="ปรึกษาน้องมีตังค์ประเมินงบประมาณ" style="font-size: 0.78rem;">
                                                <span>🤖 AI</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Procurement Recommendation Summary Result Box (hidden by default) -->
                                    <div id="boxProcurementResult" class="d-none mt-3 pt-2.5 border-top" style="border-color: rgba(99, 102, 241, 0.2) !important;">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                            <div class="small fw-bold text-indigo" style="font-size: 0.82rem;">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i> ยอดประมาณการจัดซื้อแนะนำรวม (แผนที่ 2): 
                                                <span id="txtTotalProcureRecommended" class="fw-black fs-6 text-dark ms-1">0.00</span> บาท
                                            </div>
                                            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1"
                                                    onclick="applyProcurementToSubplans()" style="font-size: 0.76rem;">
                                                <i class="bi bi-arrow-down-circle-fill"></i>
                                                <span>นำยอดที่คำนวณได้ หยอดลงในตารางแผนที่ 2 และแผนหลัก</span>
                                            </button>
                                        </div>
                                        <div class="row g-2" id="gridProcurementResultItems">
                                            <!-- Dynamically filled with MED01 - MED06 pills -->
                                        </div>
                                    </div>
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
                                                                        <input type="text" inputmode="decimal" class="form-control form-control-sm text-end input-subplan-bg"
                                                                               id="subplan_bg_{{ $item['code'] }}" 
                                                                               value="{{ number_format($item['budget_amt'], 2) }}"
                                                                               oninput="handleMoneyInput(this, () => calcSubPlanTotal('{{ $item['code'] }}'))"
                                                                               onblur="handleMoneyBlur(this, () => calcSubPlanTotal('{{ $item['code'] }}'))">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" inputmode="decimal" class="form-control form-control-sm text-end input-subplan-nonbg"
                                                                               id="subplan_nonbg_{{ $item['code'] }}" 
                                                                               value="{{ number_format($item['non_budget_amt'], 2) }}"
                                                                               oninput="handleMoneyInput(this, () => calcSubPlanTotal('{{ $item['code'] }}'))"
                                                                               onblur="handleMoneyBlur(this, () => calcSubPlanTotal('{{ $item['code'] }}'))">
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

<!-- Chart.js Local Vendor (ใช้งานแบบ Offline / Intranet 100%) -->
<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>

<!-- ========================================================================= -->
<!-- MODAL 3: ข้อมูลบริการและต้นทุนการใช้จริง HOSxP เทียบงบทดลอง (Service Drilldown) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalPlanfinServiceDrilldown" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 1680px; width: 94vw; margin: 1.25rem auto;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header text-white py-2.5 px-3 px-md-4" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle p-1.5 bg-white bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-bar-chart-fill fs-6"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="drilldownModalTitle" style="font-size: 0.95rem;">
                            📊 ข้อมูลบริการและการใช้จริง HOSxP เทียบงบทดลอง
                        </h6>
                        <div class="small text-white-50" id="drilldownModalSubtitle" style="font-size: 0.72rem;">
                            เปรียบเทียบยอดซื้อเข้า (GL) vs ยอดใช้จริง (HOSxP drg_chrgitem) vs ปริมาณคนไข้
                        </div>
                    </div>
                    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 ms-2 font-monospace" id="drilldownPlanCodeBadge" style="font-size: 0.75rem;">
                        -
                    </span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2.5 p-md-3" style="background-color: #f8fafc;">
                <!-- Loading Skeleton -->
                <div id="drilldownLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                    <div class="text-secondary fw-bold" style="font-size: 0.85rem;">กำลังรวบรวมข้อมูลจาก HOSxP (drg_chrgitem) และงบทดลอง...</div>
                </div>

                <!-- Content Container (hidden while loading) -->
                <div id="drilldownContent" class="d-none">
                    <!-- KPI Cards Row -->
                    <div class="row g-2 mb-3">
                        <!-- KPI 1: บัญชี GL (ซื้อจริง / รายได้จริง) -->
                        <div class="col-xl-3 col-md-6 col-12">
                            <div class="planfin-card-mini p-2.5 bg-white h-100 border-start border-4 border-primary rounded-2 shadow-xs">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted fw-semibold" style="font-size: 0.72rem;" id="lblDrillGlActual">ยอดจริง/งบทดลอง (GL สะสม)</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">บัญชี GL</span>
                                </div>
                                <div class="d-flex align-items-baseline gap-1 my-0.5">
                                    <h5 class="mb-0 fw-black text-dark" id="kpiDrillGlActual" style="font-size: 1.15rem;">0.00</h5>
                                    <span class="small text-muted" style="font-size: 0.7rem;">บ.</span>
                                </div>
                                <div class="small" id="kpiDrillGlDiff" style="font-size: 0.7rem;">แผนสะสม: 0.00</div>
                            </div>
                        </div>

                        <!-- KPI 2: รวมบริการ HOSxP & อัตราส่วน -->
                        <div class="col-xl-3 col-md-6 col-12">
                            <div class="planfin-card-mini p-2.5 bg-white h-100 border-start border-4 rounded-2 shadow-xs" style="border-left-color: #8b5cf6 !important;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted fw-semibold" style="font-size: 0.72rem;" id="lblDrillHosxpCost">รวมบริการ HOSxP (OPD+IPD)</span>
                                    <span class="badge px-1.5 py-0.5" style="font-size: 0.65rem; background-color: #f3e8ff; color: #7c3aed;">รวมทั้งสิ้น</span>
                                </div>
                                <div class="d-flex align-items-baseline gap-1 my-0.5">
                                    <h5 class="mb-0 fw-black" id="kpiDrillHosxpCost" style="font-size: 1.15rem; color: #7c3aed;">0.00</h5>
                                    <span class="small text-muted" style="font-size: 0.7rem;">บ.</span>
                                    <span class="badge ms-auto font-monospace px-1.5 py-0.5 border" id="kpiDrillHosxpCharge" style="font-size: 0.68rem;">เทียบ GL: 0.00 บ.</span>
                                </div>
                                <div class="small text-muted d-flex justify-content-between" style="font-size: 0.7rem;">
                                    <span id="kpiDrillHosxpAvgVisit">เฉลี่ย/Visit: <strong class="text-info">0.00</strong> บ./ครั้ง</span>
                                    <span id="kpiDrillStockMove">เฉลี่ย/AdjRW: <strong class="text-primary">0.00</strong> บ.</span>
                                </div>
                            </div>
                        </div>

                        <!-- KPI 3: ผู้ป่วยนอก OPD -->
                        <div class="col-xl-3 col-md-6 col-12">
                            <div class="planfin-card-mini p-2.5 bg-white h-100 border-start border-4 border-info rounded-2 shadow-xs">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted fw-semibold" style="font-size: 0.72rem;" id="lblDrillOpVisits">ผู้ป่วยนอก OPD สะสม (Visits)</span>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">OPD</span>
                                </div>
                                <div class="d-flex align-items-baseline gap-1 my-0.5">
                                    <h5 class="mb-0 fw-black text-info" id="kpiDrillOpVisits" style="font-size: 1.15rem;">0</h5>
                                    <span class="small text-muted" style="font-size: 0.7rem;">ครั้ง</span>
                                </div>
                                <div class="small text-muted d-flex justify-content-end" style="font-size: 0.7rem;">
                                    <span>มูลค่า: <strong class="text-dark" id="kpiDrillOpCost">0.00</strong> บ.</span>
                                </div>
                            </div>
                        </div>

                        <!-- KPI 4: ผู้ป่วยใน IPD & AdjRW -->
                        <div class="col-xl-3 col-md-6 col-12">
                            <div class="planfin-card-mini p-2.5 bg-white h-100 border-start border-4 rounded-2 shadow-xs" style="border-left-color: #f59e0b !important;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted fw-semibold" style="font-size: 0.72rem;" id="lblDrillIpAdmits">ผู้ป่วยใน IPD & AdjRW สะสม</span>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">IPD</span>
                                </div>
                                <div class="d-flex align-items-baseline gap-1 my-0.5">
                                    <h5 class="mb-0 fw-black text-warning-emphasis" id="kpiDrillIpAdmits" style="font-size: 1.15rem;">0</h5>
                                    <span class="small text-muted" style="font-size: 0.7rem;">AN (ครั้ง)</span>
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle ms-auto font-monospace px-2 py-0.5" style="font-size: 0.72rem;">
                                        AdjRW รวม: <strong id="kpiDrillIpAdjrw" class="text-dark">0.0000</strong>
                                    </span>
                                </div>
                                <div class="small text-muted d-flex justify-content-between" style="font-size: 0.7rem;">
                                    <span id="kpiDrillIpBedDays">วันนอน: 0 วัน</span>
                                    <span>มูลค่า: <strong class="text-dark" id="kpiDrillIpCost">0.00</strong> บ.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dual-Axis Interactive Chart Card -->
                    <div class="card border-0 shadow-xs rounded-3 mb-3 bg-white">
                        <div class="card-header bg-transparent border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="fw-bold text-dark small" style="font-size: 0.82rem;">
                                <i class="bi bi-graph-up-arrow text-primary me-1"></i> กราฟแนวโน้มรายเดือน: แยกบริการ OPD (ฟ้า) vs IPD (ม่วง) vs ยอดจริง GL (เขียว/แดง)
                            </span>
                            <span class="badge bg-light text-secondary border px-2 py-0.5" id="lblDrillPeriodRange" style="font-size: 0.72rem;">
                                12 งวดปีงบประมาณ
                            </span>
                        </div>
                        <div class="card-body py-2.5 px-3">
                            <div style="height: 240px; width: 100%;">
                                <canvas id="chartPlanfinDrilldown"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Detail Table -->
                    <div class="table-responsive rounded-3 border bg-white mb-3">
                        <table class="table table-hover table-sm align-middle mb-0 text-nowrap w-100" id="tableDrilldownDetail" style="font-size: 0.74rem;">
                            <thead class="table-light">
                                <tr class="text-center" style="border-bottom: 2px solid #cbd5e1;">
                                    <th rowspan="2" class="align-middle bg-slate-100" style="width: 7%;">งวดเดือน</th>
                                    <th colspan="3" class="text-center bg-primary-subtle text-primary fw-bold" style="border-right: 1px solid #cbd5e1;">
                                        <i class="bi bi-journal-text me-1"></i> งบการเงิน GL (บาท)
                                    </th>
                                    <th rowspan="2" class="align-middle fw-bold text-center" style="width: 10%; line-height: 1.25; border-right: 2px solid #cbd5e1; background-color: #f5f3ff; color: #6366f1;">
                                        รวมบริการ<br>HOSxP (บาท)
                                    </th>
                                    <th colspan="3" class="text-center bg-info-subtle text-info-emphasis fw-bold" style="border-right: 1px solid #cbd5e1;">
                                        <i class="bi bi-person-walking me-1"></i> ผู้ป่วยนอก OPD
                                    </th>
                                    <th colspan="5" class="text-center bg-warning-subtle text-warning-emphasis fw-bold">
                                        <i class="bi bi-hospital me-1"></i> ผู้ป่วยใน IPD
                                    </th>
                                </tr>
                                <tr class="small text-muted text-center" style="font-size: 0.71rem;">
                                    <!-- GL -->
                                    <th class="text-end" style="width: 7.5%;">แผนงวด</th>
                                    <th class="text-end" id="thDrillGlAmount" style="width: 8%;">ยอดจริง GL</th>
                                    <th class="text-end" style="width: 7.5%; border-right: 1px solid #cbd5e1;">ผลต่าง</th>
                                    
                                    <!-- OPD -->
                                    <th class="text-end" style="width: 6.5%;">Visits (ครั้ง)</th>
                                    <th class="text-end" style="width: 8%;">มูลค่า OPD (บ.)</th>
                                    <th class="text-end" style="width: 7%; border-right: 1px solid #cbd5e1;">เฉลี่ย/Visit</th>
                                    
                                    <!-- IPD -->
                                    <th class="text-end" style="width: 6%;">AN (ครั้ง)</th>
                                    <th class="text-end" style="width: 5.5%;">วันนอน</th>
                                    <th class="text-end fw-bold text-dark" style="width: 6.5%; background-color: #fef3c7;">AdjRW</th>
                                    <th class="text-end" style="width: 8%;">มูลค่า IPD (บ.)</th>
                                    <th class="text-end" style="width: 7.5%; border-right: 1px solid #cbd5e1;">เฉลี่ย/AdjRW</th>
                                </tr>
                            </thead>
                            <tbody id="bodyDrilldownDetail">
                                <!-- Populated dynamically -->
                            </tbody>
                            <tfoot class="table-light fw-bold border-top" id="footDrilldownDetail">
                                <!-- Populated dynamically with cumulative totals -->
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-indigo btn-sm rounded-pill px-3 fw-bold d-inline-flex align-items-center gap-1.5 shadow-xs"
                            onclick="triggerAiFromDrilldown()">
                        <span>🤖</span>
                        <span>ให้น้องมีตังค์ (RiMS AI) วิเคราะห์หมวดนี้</span>
                    </button>
                </div>

                <!-- Footer HOSxP Data Source & drg_chrgitem Mapping Info (แสดงเฉพาะหมวดที่มี drg_chrgitem) -->
                <div class="d-flex align-items-center gap-2 d-none" id="drilldownFooterSourceBox">
                    <div class="px-2.5 py-1 rounded-pill border bg-white shadow-xs d-flex align-items-center gap-1.5" style="font-size: 0.74rem;">
                        <i class="bi bi-database-fill-check" style="color: #4f46e5;"></i>
                        <span class="text-muted fw-semibold">แหล่งข้อมูล HOSxP:</span>
                        <span class="badge rounded-pill font-monospace fw-bold" id="lblDrilldownSourceTag" 
                              style="background-color: #e0e7ff !important; color: #3730a3 !important; border: 1px solid #a5b4fc !important; font-size: 0.73rem;">
                            drg_chrgitem
                        </span>
                        <span class="text-dark fw-bold" id="lblDrilldownSourceDetail">
                            -
                        </span>
                    </div>
                </div>

                <div>
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 4: ผู้ช่วยอัจฉริยะ "น้องมีตังค์ (RiMS AI)" -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalPlanfinAi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header text-white py-2.5 px-3 px-md-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle p-1.5 bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 1.1rem;">
                        🤖
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" style="font-size: 0.95rem;">
                            น้องมีตังค์ (RiMS AI) - ผู้ช่วยวิเคราะห์แผนเงินบำรุง
                        </h6>
                        <div class="small text-white-50" style="font-size: 0.72rem;">
                            ระบบวิเคราะห์ผลต่างทางการเงินและข้อมูลบริการ (Intelligent Variance & Procurement Advisor)
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background: #fafafa;">
                <!-- Loading State -->
                <div id="aiLoadingContainer" class="text-center py-5">
                    <div class="spinner-grow text-indigo mb-2" role="status" style="width: 2.2rem; height: 2.2rem; color: #6366f1;"></div>
                    <div class="text-dark fw-bold" style="font-size: 0.88rem;">น้องมีตังค์กำลังประมวลผลข้อมูล...</div>
                    <div class="text-muted small" style="font-size: 0.76rem;">กระทบยอดงบทดลอง, HOSxP drg_chrgitem, และสถิติคนไข้</div>
                </div>

                <!-- Answer Container -->
                <div id="aiAnswerContainer" class="d-none bg-white p-3 p-md-4 rounded-3 border shadow-xs">
                    <!-- Populated by JS -->
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                <div class="small text-muted" style="font-size: 0.74rem;">
                    <i class="bi bi-shield-check text-success me-1"></i> วิเคราะห์จากฐานข้อมูลจริง 100%
                </div>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 5: กราฟแนวโน้ม 12 เดือน (หมวดหลัก และ ผังบัญชีย่อย) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalMatrixTrendsChart" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-lg-down" style="max-width: 95vw; width: 95vw;">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden">
            <!-- Modal Header with Gradient and Mode Switch -->
            <div class="modal-header py-2.5 px-3 px-md-4 text-white" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle p-2 bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center shadow-xs" style="width: 40px; height: 40px;">
                        <i class="bi bi-graph-up-arrow fs-5" id="matrixModalHeaderIcon"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-black mb-0 text-white" id="matrixModalTitle" style="font-size: 1.12rem; letter-spacing: -0.01em;">
                                📈 กราฟแนวโน้ม 12 เดือน: หมวดหลัก
                            </h5>
                            <span class="badge rounded-pill bg-warning text-dark fw-bold px-2 py-0.5" style="font-size: 0.72rem;">
                                ปีงบประมาณ {{ $budgetYear }}
                            </span>
                        </div>
                        <div class="small text-white-50" style="font-size: 0.73rem;">
                            เปรียบเทียบแนวโน้มผลดำเนินงานจริงรายเดือน 12 งวด (ต.ค. {{ substr($budgetYear - 1, -2) }} – ก.ย. {{ substr($budgetYear, -2) }})
                        </div>
                    </div>
                </div>

                <!-- Mode Switch Nav (หมวดหลัก vs ผังบัญชีย่อย) -->
                <div class="d-flex align-items-center gap-2 ms-auto me-3">
                    <div class="p-1 rounded-pill d-inline-flex shadow-xs" style="background: rgba(15, 23, 42, 0.45); border: 1px solid rgba(255, 255, 255, 0.25);">
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap" 
                                id="btnMatrixModeCategory" onclick="switchMatrixChartMode('category')"
                                style="font-size: 0.78rem; background-color: #4f46e5; color: #ffffff;">
                            <i class="bi bi-collection me-1"></i> หมวดหลัก ({{ count($matrixCategoryLookup ?? []) }})
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap" 
                                id="btnMatrixModeSub" onclick="switchMatrixChartMode('sub')"
                                style="font-size: 0.78rem; background-color: transparent; color: #cbd5e1;">
                            <i class="bi bi-list-nested me-1"></i> ผังบัญชีย่อย ({{ count($matrixSubLookup ?? []) }})
                        </button>
                    </div>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body (Two-Column Layout) -->
            <div class="modal-body p-0" style="background-color: #f8fafc;">
                <div class="row g-0">
                    <!-- LEFT COLUMN: Search & Checkbox List (Sidebar) -->
                    <div class="col-lg-4 col-xl-3 bg-white border-end d-flex flex-column" style="min-width: 320px; max-width: 380px;">
                        <!-- Search & Quick Filters Header -->
                        <div class="p-3 border-bottom bg-slate-50">
                            <!-- Search Input -->
                            <div class="input-group input-group-sm mb-2 shadow-2xs">
                                <span class="input-group-text bg-white border-end-0 text-muted">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="matrixSearchInput" class="form-control border-start-0 ps-0" 
                                       placeholder="ค้นหารหัส, ชื่อหมวด/บัญชี..." oninput="onMatrixSearchInput(this.value)">
                                <button class="btn btn-outline-secondary bg-white border-start-0" type="button" onclick="clearMatrixSearch()" title="ล้างการค้นหา">
                                    <i class="bi bi-x-lg text-muted"></i>
                                </button>
                            </div>

                            <!-- Selection Counter & Reset Action -->
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="small text-muted" style="font-size: 0.74rem;">
                                    เลือกแล้ว: <strong class="text-primary font-monospace fs-6" id="matrixSelectedCount">0</strong> 
                                    <span class="text-muted" id="matrixListTotalCount" style="font-size: 0.70rem;">/ {{ count($matrixCategoryLookup ?? []) }} รายการ</span>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-0.5 shadow-2xs" onclick="matrixSelectAll(false)" title="ยกเลิกการเลือกทั้งหมดเพื่อเริ่มเลือกใหม่" style="font-size: 0.70rem;">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> ล้างที่เลือก
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable Checkbox List -->
                        <div class="p-2 overflow-y-auto" id="matrixChecklistContainer" style="max-height: calc(85vh - 210px); min-height: 420px;">
                            <!-- Populated dynamically by JS -->
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Interactive Multi Line Chart Area -->
                    <div class="col-lg-8 col-xl-9 p-3 p-md-4 d-flex flex-column">
                        <!-- Top Chart KPI Metric Summary Strip -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-2.5 rounded-3 bg-white border shadow-xs">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div>
                                    <span class="small text-muted" style="font-size: 0.72rem;">รวมผลจริงทั้งปี:</span>
                                    <div class="fw-black text-dark font-monospace" id="matrixChartSumTotal" style="font-size: 1.10rem;">
                                        0.00 บ.
                                    </div>
                                </div>
                                <div class="border-start ps-3">
                                    <span class="small text-muted" style="font-size: 0.72rem;">รวมแผนทั้งปี:</span>
                                    <div class="fw-bold text-primary font-monospace" id="matrixChartPlanTotal" style="font-size: 1.05rem;">
                                        0.00 บ.
                                    </div>
                                </div>
                                <div class="border-start ps-3">
                                    <span class="small text-muted" style="font-size: 0.72rem;">ผลต่าง (จริง - แผน):</span>
                                    <div class="fw-bold font-monospace" id="matrixChartDiffTotal" style="font-size: 1.05rem;">
                                        0.00 บ.
                                    </div>
                                </div>
                                <div class="border-start ps-3 d-none d-xl-block">
                                    <span class="small text-muted" style="font-size: 0.72rem;">เฉลี่ยจริงต่องวด (12 เดือน):</span>
                                    <div class="fw-bold text-secondary font-monospace" id="matrixChartMonthlyAvg" style="font-size: 0.92rem;">
                                        0.00 บ.
                                    </div>
                                </div>
                            </div>

                            <!-- Chart Action (Clean, No extra buttons) -->
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-xs fw-bold d-inline-flex align-items-center gap-1.5" 
                                        onclick="downloadMatrixChartPng()" title="บันทึกภาพกราฟเป็นไฟล์ PNG" style="font-size: 0.78rem;">
                                    <i class="bi bi-camera me-1"></i> บันทึกภาพกราฟ
                                </button>
                            </div>
                        </div>

                        <!-- Chart Canvas Container -->
                        <div class="bg-white rounded-3 border p-3 shadow-xs position-relative flex-grow-1 d-flex flex-column justify-content-center" style="min-height: 480px;">
                            <div id="matrixChartEmptyState" class="position-absolute top-50 start-50 translate-middle text-center text-muted p-4" style="max-width: 440px;">
                                <div class="rounded-circle bg-slate-50 d-inline-flex align-items-center justify-content-center mb-3 shadow-2xs" style="width: 64px; height: 64px; border: 2px dashed #cbd5e1;">
                                    <i class="bi bi-graph-up-arrow fs-2 text-primary opacity-75"></i>
                                </div>
                                <div class="fw-bold fs-6 text-dark mb-1">ยังไม่ได้เลือกรายการเพื่อดูกราฟ</div>
                                <div class="small text-secondary" style="line-height: 1.6;">
                                    กรุณาติ๊กเลือกหมวดหลักหรือผังบัญชีย่อยจากแถบเมนูด้านซ้าย<br>
                                    เพื่อแสดงกราฟเส้นแนวโน้ม 12 เดือน (ผลจริงคู่กับแผน)
                                </div>
                            </div>
                            <div style="height: 460px; width: 100%;">
                                <canvas id="canvasMatrixTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center border-top">
                <div class="small text-muted" style="font-size: 0.74rem;">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    <strong>เส้นทึบ</strong> = ผลดำเนินงานจริงตามงบทดลอง 12 งวด | <strong>เส้นประ</strong> = แผนเป้าหมายงวดรายเดือน (1/12) | ชี้ที่จุดบนกราฟเพื่อดูยอดเงินเปรียบเทียบ ผลจริง vs แผน
                </div>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
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
    // Number Formatting & Currency Helpers for Tab 2
    // =========================================================================
    function parseNum(val) {
        if (typeof val === 'number') return isNaN(val) ? 0 : val;
        if (!val) return 0;
        const cleaned = String(val).replace(/,/g, '').trim();
        const num = parseFloat(cleaned);
        return isNaN(num) ? 0 : num;
    }

    function formatNumberWithCommas(val) {
        if (val === '' || val === null || val === undefined) return '';
        let str = String(val).replace(/[^\d.-]/g, '');
        const isNeg = str.startsWith('-');
        str = str.replace(/-/g, '');
        if (isNeg) str = '-' + str;
        
        const parts = str.split('.');
        const intPart = parts[0];
        const isNegative = intPart.startsWith('-');
        let intDigits = isNegative ? intPart.substring(1) : intPart;
        
        if (intDigits.length > 1 && intDigits.startsWith('0')) {
            intDigits = intDigits.replace(/^0+/, '') || '0';
        }
        
        let formattedInt = (isNegative ? '-' : '') + intDigits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        let res = formattedInt;
        if (parts.length > 1) {
            res += '.' + parts.slice(1).join('').substring(0, 2);
        } else if (str.endsWith('.')) {
            res += '.';
        }
        return res;
    }

    function handleMoneyInput(inputEl, callback) {
        const rawVal = inputEl.value;
        const cursorPosition = inputEl.selectionStart;

        // Count how many valid characters (digits, minus, dot) were before the cursor
        const validBeforeCursor = rawVal.slice(0, cursorPosition).replace(/[^\d.-]/g, '').length;

        // Format new value
        const formatted = formatNumberWithCommas(rawVal);
        inputEl.value = formatted;

        // Reposition cursor in formatted string
        let newCursor = 0;
        let validCount = 0;
        for (let i = 0; i < formatted.length; i++) {
            if (formatted[i] !== ',') {
                validCount++;
            }
            newCursor = i + 1;
            if (validCount >= validBeforeCursor) break;
        }
        inputEl.setSelectionRange(newCursor, newCursor);

        if (typeof callback === 'function') {
            callback();
        }
    }

    function handleMoneyBlur(inputEl, callback) {
        const trimmed = inputEl.value.trim();
        if (trimmed === '' || trimmed === '-' || trimmed === '.') {
            inputEl.value = '0.00';
        } else {
            const num = parseNum(trimmed);
            inputEl.value = num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        if (typeof callback === 'function') {
            callback();
        }
    }

    // Handle Backspace when cursor is directly after a comma
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && e.target.matches('.input-plan70, .input-plan70-sub, .input-subplan-bg, .input-subplan-nonbg')) {
            const el = e.target;
            const pos = el.selectionStart;
            if (pos === el.selectionEnd && pos > 1 && el.value[pos - 1] === ',') {
                e.preventDefault();
                const val = el.value;
                el.value = val.slice(0, pos - 2) + val.slice(pos);
                el.setSelectionRange(pos - 2, pos - 2);
                el.dispatchEvent(new Event('input'));
            }
        }
    });

    // =========================================================================
    // Two-Way Sync Calculations: Sub-accounts <-> Parent Categories
    // =========================================================================
    function onSubGrowthChange(parentCode, subCode, growthVal) {
        const sanitized = subCode.replace(/\./g, '_');
        const targetInput = document.getElementById('subtarget_' + sanitized);
        const tr = document.querySelector(`tr[data-subcode="${subCode}"]`);
        const base = parseFloat(tr?.getAttribute('data-base')) || 0;

        const growth = parseNum(growthVal);
        const newTarget = base * (1 + (growth / 100));
        if (targetInput) targetInput.value = newTarget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        syncParentFromSubAccounts(parentCode);
    }

    function onSubTargetChange(parentCode, subCode, targetVal) {
        const sanitized = subCode.replace(/\./g, '_');
        const targetInput = document.getElementById('subtarget_' + sanitized);
        const growthInput = document.getElementById('subgrowth_' + sanitized);
        const tr = document.querySelector(`tr[data-subcode="${subCode}"]`);
        const base = parseFloat(tr?.getAttribute('data-base')) || 0;

        const rawTarget = targetVal !== undefined ? targetVal : (targetInput ? targetInput.value : 0);
        const target = parseNum(rawTarget);
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
                sumTarget += parseNum(targetInp.value);
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

        if (parentTargetInp) parentTargetInp.value = sumTarget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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

        const growth = parseNum(growthInput?.value || 0);
        const newTarget = base * (1 + (growth / 100));
        if (targetInput) targetInput.value = newTarget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // Pro-rate to sub-accounts
        const subRows = document.querySelectorAll(`tr.sub-row-t2[data-parent="${code}"]`);
        subRows.forEach(subTr => {
            const subCode = subTr.getAttribute('data-subcode');
            const sanitized = subCode.replace(/\./g, '_');
            const subBase = parseFloat(subTr.getAttribute('data-base')) || 0;
            const subTargetInp = document.getElementById('subtarget_' + sanitized);
            const subGrowthInp = document.getElementById('subgrowth_' + sanitized);

            const subTarget = subBase * (1 + (growth / 100));
            if (subTargetInp) subTargetInp.value = subTarget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (subGrowthInp) subGrowthInp.value = growth.toFixed(1);
        });

        recalculateSimulator();
    }

    function onParentTargetChange(code) {
        const growthInput = document.getElementById('growth_' + code);
        const targetInput = document.getElementById('target_' + code);
        const tr = document.querySelector(`tr[data-code="${code}"]`);
        const base = parseFloat(tr.getAttribute('data-base')) || 0;

        const target = parseNum(targetInput?.value || 0);
        let growth = 0;
        if (base > 0) {
            growth = ((target - base) / base) * 100;
            if (growthInput) growthInput.value = growth.toFixed(2);
        } else {
            if (growthInput) growthInput.value = '0.00';
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
                if (subTargetInp) subTargetInp.value = subTarget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                if (subGrowthInp) subGrowthInp.value = growth.toFixed(1);
            });
        }

        recalculateSimulator();
    }

    // Operational Sub-Plans Calculations
    function calcSubPlanTotal(code) {
        const bg = parseNum(document.getElementById('subplan_bg_' + code)?.value || 0);
        const nonBg = parseNum(document.getElementById('subplan_nonbg_' + code)?.value || 0);
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
            if (inp) sumRev += parseNum(inp.value);
        });

        expCodes.forEach(c => {
            const inp = document.getElementById('target_' + c);
            if (inp) sumExp += parseNum(inp.value);
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

        const p13Val = parseNum(document.getElementById('target_P13')?.value || 0);
        const p121Val = parseNum(document.getElementById('target_P121')?.value || 0);
        const p24Val = parseNum(document.getElementById('target_P24')?.value || 0);
        const p251Val = parseNum(document.getElementById('target_P251')?.value || 0);

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
                        growth_rate: parseNum(growthInp?.value || 0),
                        target_amount: parseNum(targetInp.value || 0)
                    });
                }
            }
        });

        // 2. Sub-Accounts
        document.querySelectorAll('#tableSimulator70 tbody tr.sub-row-t2[data-subcode]').forEach(tr => {
            const parentCode = tr.getAttribute('data-parent');
            const subCode = tr.getAttribute('data-subcode');
            const sanitized = subCode.replace(/\./g, '_');
            const targetInp = document.getElementById('subtarget_' + sanitized);
            const growthInp = document.getElementById('subgrowth_' + sanitized);
            const base = parseFloat(tr.getAttribute('data-base')) || 0;
            if (targetInp) {
                items.push({
                    plan_code: subCode,
                    parent_code: parentCode,
                    baseline_amount: base,
                    growth_rate: parseNum(growthInp?.value || 0),
                    target_amount: parseNum(targetInp.value || 0)
                });
            }
        });

        // 3. Operational Sub-Plans Items (Plans 2 through 7)
        document.querySelectorAll('tr[data-subplan-code]').forEach(tr => {
            const code = tr.getAttribute('data-subplan-code');
            const bgInp = document.getElementById('subplan_bg_' + code);
            const nonBgInp = document.getElementById('subplan_nonbg_' + code);
            if (bgInp || nonBgInp) {
                const bg = parseNum(bgInp?.value || 0);
                const nonBg = parseNum(nonBgInp?.value || 0);
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

    // =========================================================================
    // SMART PLANFIN: CLINICAL DRILLDOWN, DUAL-AXIS CHART & RI-MS AI
    // =========================================================================
    let drilldownChartInstance = null;
    let currentDrilldownData = null;
    let latestProcurementResults = null;

    function openServiceDrilldownModal(planCode, planName) {
        const modalEl = document.getElementById('modalPlanfinServiceDrilldown');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        
        document.getElementById('drilldownPlanCodeBadge').textContent = planCode;
        document.getElementById('drilldownModalTitle').innerHTML = `📊 ข้อมูลบริการและการใช้จริง HOSxP: <span class="text-warning">${planCode} - ${planName}</span>`;
        document.getElementById('drilldownLoading').classList.remove('d-none');
        document.getElementById('drilldownContent').classList.add('d-none');
        
        modal.show();

        const url = `{{ url('hosfin/planfin/service_drilldown') }}?plan_code=${encodeURIComponent(planCode)}&budget_year={{ $budgetYear }}&period={{ $selectedPeriod }}`;
        
        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (!data.success) {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถโหลดข้อมูลได้'));
                    return;
                }
                currentDrilldownData = data;
                renderDrilldownData(data);
                document.getElementById('drilldownLoading').classList.add('d-none');
                document.getElementById('drilldownContent').classList.remove('d-none');
            })
            .catch(err => {
                console.error('Drilldown fetch error:', err);
                document.getElementById('drilldownLoading').innerHTML = `
                    <div class="text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-2 mb-2 d-block"></i>
                        เกิดข้อผิดพลาดในการโหลดข้อมูล: ${err.message}
                    </div>
                `;
            });
    }

    function renderDrilldownData(data) {
        const sum = data.summary;
        const mapping = data.clinical_mapping || {};
        const isRev = (data.category_type === 'revenue' || sum.is_revenue === true);

        // Update Modal Title and Subtitle dynamically
        const modalTitle = isRev 
            ? `📊 ข้อมูลบริการและรายได้จริง HOSxP: <span class="text-warning">${data.plan_code} - ${data.plan_name}</span>`
            : `📊 ข้อมูลบริการและการใช้จริง HOSxP: <span class="text-warning">${data.plan_code} - ${data.plan_name}</span>`;
        document.getElementById('drilldownModalTitle').innerHTML = modalTitle;

        const modalSubtitle = isRev 
            ? `เปรียบเทียบรายได้จริง (GL) vs มูลค่าบริการ OPD & IPD (${mapping.drg_label || 'แยกตามสิทธิการรักษา'}) vs ปริมาณคนไข้`
            : (mapping.drg_ids && mapping.drg_ids.length > 0
                ? `เปรียบเทียบยอดซื้อเข้า (GL) vs ยอดใช้จริง (HOSxP drg_chrgitem หมวด ${mapping.drg_ids.join(', ')}) vs ปริมาณคนไข้`
                : `เปรียบเทียบยอดซื้อเข้า (GL) vs ยอดเบิกใช้จริง OPD & IPD vs ปริมาณคนไข้`);
        document.getElementById('drilldownModalSubtitle').textContent = modalSubtitle;

        // Update Footer Data Source Information (แสดงเฉพาะฝั่งต้นทุน/ค่าใช้จ่ายที่มีการจับคู่ drg_chrgitem)
        const footerSourceBox = document.getElementById('drilldownFooterSourceBox');
        const sourceTagEl = document.getElementById('lblDrilldownSourceTag');
        const sourceDetailEl = document.getElementById('lblDrilldownSourceDetail');
        if (footerSourceBox && sourceTagEl && sourceDetailEl) {
            if (!isRev && mapping.drg_ids && mapping.drg_ids.length > 0 && !['P26S', 'P29-E'].includes(data.plan_code)) {
                footerSourceBox.classList.remove('d-none');
                sourceTagEl.className = 'badge rounded-pill font-monospace fw-bold';
                sourceTagEl.style.cssText = 'background-color: #e0e7ff !important; color: #3730a3 !important; border: 1px solid #a5b4fc !important; font-size: 0.73rem;';
                sourceTagEl.innerHTML = `<i class="bi bi-tag-fill me-1" style="color: #4f46e5;"></i>drg_chrgitem หมวด ${mapping.drg_ids.join(', ')}`;
                const detailText = mapping.drg_full_names || mapping.drg_label || '';
                sourceDetailEl.innerHTML = detailText;
                sourceDetailEl.setAttribute('title', detailText);
            } else {
                // ฝั่งรายได้, หมวดสรุปภาพรวม (P26S), หรือหมวดค่าใช้จ่ายทั่วไป ไม่ต้องแสดง
                footerSourceBox.classList.add('d-none');
            }
        }

        // KPI Labels & Headers
        const isNetMargin = (data.plan_code === 'P27S' || data.plan_code === 'P29');
        if (document.getElementById('lblDrillGlActual')) {
            if (isNetMargin) {
                document.getElementById('lblDrillGlActual').textContent = 'รายได้สูง(ต่ำ)กว่าค่าใช้จ่าย (GL สะสม)';
            } else {
                document.getElementById('lblDrillGlActual').textContent = isRev ? 'รายได้จริง/งบทดลอง (GL สะสม)' : 'ยอดซื้อเข้า/งบทดลอง (GL สะสม)';
            }
        }
        if (document.getElementById('lblDrillHosxpCost')) {
            if (isNetMargin) {
                document.getElementById('lblDrillHosxpCost').textContent = 'ส่วนต่างสุทธิ HOSxP (รายได้-ค่าใช้จ่าย)';
            } else {
                document.getElementById('lblDrillHosxpCost').textContent = isRev ? 'รวมรายได้ HOSxP (OPD+IPD)' : 'รวมมูลค่าบริการ HOSxP';
            }
        }
        if (document.getElementById('lblDrillOpVisits')) {
            document.getElementById('lblDrillOpVisits').textContent = mapping.service_metric_label ? mapping.service_metric_label.replace('(ครั้ง/เดือน)', 'OPD สะสม (Visits)') : (isRev ? 'ผู้ป่วยนอก OPD สะสม (Visits)' : 'ผู้ป่วยนอก OPD สะสม (Visits)');
        }
        if (document.getElementById('lblDrillIpAdmits')) {
            document.getElementById('lblDrillIpAdmits').textContent = isRev ? 'ผู้ป่วยใน IPD & AdjRW สะสม' : 'ผู้ป่วยใน IPD & AdjRW สะสม';
        }

        // Table Column Header for GL
        if (document.getElementById('thDrillGlAmount')) {
            if (isNetMargin) {
                document.getElementById('thDrillGlAmount').textContent = 'ผลสุทธิ GL';
            } else {
                document.getElementById('thDrillGlAmount').textContent = isRev ? 'ยอดจริง GL' : 'ซื้อจริง GL';
            }
        }

        // KPI 1: GL Actual
        document.getElementById('kpiDrillGlActual').textContent = Number(sum.cum_gl_actual || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const diffSign = sum.cum_gl_diff > 0 ? '+' : '';
        const diffColor = sum.cum_gl_diff > 0 ? (isRev ? 'text-success fw-bold' : 'text-danger fw-bold') : (isRev ? 'text-danger fw-bold' : 'text-success fw-bold');
        document.getElementById('kpiDrillGlDiff').innerHTML = `แผนสะสม: ${Number(sum.cum_gl_plan || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} | <span class="${diffColor}">ต่าง ${diffSign}${Number(sum.cum_gl_diff || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} (${sum.cum_gl_diff_percent}%)</span>`;

        // KPI 2: OPD
        document.getElementById('kpiDrillOpVisits').textContent = Number(sum.cum_op_visits || 0).toLocaleString();
        document.getElementById('kpiDrillOpCost').textContent = Number(sum.cum_op_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // KPI 3: IPD & AdjRW
        document.getElementById('kpiDrillIpAdmits').textContent = Number(sum.cum_ip_admits || 0).toLocaleString();
        document.getElementById('kpiDrillIpAdjrw').textContent = Number(sum.cum_ip_adjrw || 0).toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4});
        document.getElementById('kpiDrillIpBedDays').textContent = `วันนอน: ${Number(sum.cum_ip_bed_days || 0).toLocaleString()} วัน`;
        document.getElementById('kpiDrillIpCost').textContent = Number(sum.cum_ip_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // KPI 4: HOSxP Total & Ratios
        document.getElementById('kpiDrillHosxpCost').textContent = Number(sum.cum_hosxp_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Badge เทียบ GL
        const glVsHosxp = Number(sum.cum_gl_actual || 0) - Number(sum.cum_hosxp_cost || 0);
        const signGlVs = glVsHosxp >= 0 ? '+' : '';
        const badgeColorClass = isRev 
            ? (glVsHosxp >= 0 ? 'bg-success-subtle text-success border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border-warning-subtle')
            : (glVsHosxp >= 0 ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-danger-subtle text-danger border-danger-subtle');
        const badgeEl = document.getElementById('kpiDrillHosxpCharge');
        if (badgeEl) {
            badgeEl.className = `badge ms-auto font-monospace px-1.5 py-0.5 border ${badgeColorClass}`;
            badgeEl.innerHTML = `เทียบ GL: <strong>${signGlVs}${Number(glVsHosxp).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> บ.`;
        }

        // บรรทัดล่างของ Card รวมบริการ HOSxP: เฉลี่ย/Visit และ เฉลี่ย/AdjRW
        const avgVisit = Number(sum.avg_op_unit_cost || sum.avg_unit_cost_visit || 0);
        const avgAdjrw = Number(sum.avg_ip_cost_per_adjrw || 0);
        const avgVisitEl = document.getElementById('kpiDrillHosxpAvgVisit');
        if (avgVisitEl) {
            avgVisitEl.innerHTML = `เฉลี่ย/Visit: <strong class="text-info">${avgVisit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> บ./ครั้ง`;
        }
        document.getElementById('kpiDrillStockMove').innerHTML = `เฉลี่ย/AdjRW: <strong class="text-primary">${avgAdjrw.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> บ.`;

        // Render Detail Table rows
        const tbody = document.getElementById('bodyDrilldownDetail');
        let htmlRows = '';
        data.series.forEach(item => {
            const isSelected = item.is_selected;
            const rowStyle = isSelected ? 'background-color: #eff6ff; font-weight: bold;' : '';
            const diffColor = item.gl_diff > 0 ? (isRev ? 'text-success' : 'text-danger') : (isRev ? 'text-danger' : 'text-success');
            const diffSign = item.gl_diff > 0 ? '+' : '';

            htmlRows += `
                <tr style="${rowStyle}">
                    <td class="text-center font-monospace">${item.label} ${isSelected ? '<span class="badge bg-primary rounded-pill" style="font-size: 0.65rem;">งวดนี้</span>' : ''}</td>
                    
                    <!-- GL -->
                    <td class="text-end font-monospace text-muted">${Number(item.gl_plan || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace fw-semibold text-dark">${Number(item.gl_amount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace ${diffColor}" style="border-right: 1px solid #e2e8f0;">${diffSign}${Number(item.gl_diff || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    
                    <!-- HOSxP Total (Moved here right after GL!) -->
                    <td class="text-end font-monospace fw-bold text-primary" style="background-color: ${isSelected ? '#e0e7ff' : '#f5f3ff'}; border-right: 2px solid #cbd5e1;">${Number(item.hosxp_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>

                    <!-- OPD -->
                    <td class="text-end font-monospace">${Number(item.op_visits || 0).toLocaleString()}</td>
                    <td class="text-end font-monospace text-info">${Number(item.op_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace text-secondary" style="border-right: 1px solid #e2e8f0;">${Number(item.op_unit_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    
                    <!-- IPD -->
                    <td class="text-end font-monospace">${Number(item.ip_admits || 0).toLocaleString()}</td>
                    <td class="text-end font-monospace">${Number(item.ip_bed_days || 0).toLocaleString()}</td>
                    <td class="text-end font-monospace fw-bold text-dark" style="background-color: ${isSelected ? '#fef3c7' : '#fffbeb'};">${Number(item.ip_adjrw || 0).toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4})}</td>
                    <td class="text-end font-monospace" style="color: #8b5cf6;">${Number(item.ip_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace text-secondary">${Number(item.ip_cost_per_adjrw || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
            `;
        });
        tbody.innerHTML = htmlRows;

        // Render Summary Cumulative Footer
        const tfoot = document.getElementById('footDrilldownDetail');
        if (tfoot) {
            const cumDiffColor = sum.cum_gl_diff > 0 ? (isRev ? 'text-success' : 'text-danger') : (isRev ? 'text-danger' : 'text-success');
            const cumDiffSign = sum.cum_gl_diff > 0 ? '+' : '';
            tfoot.innerHTML = `
                <tr class="table-light">
                    <td class="text-center font-monospace">รวมสะสม (${data.cum_months} ด.)</td>
                    <td class="text-end font-monospace text-muted">${Number(sum.cum_gl_plan || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace text-dark">${Number(sum.cum_gl_actual || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace ${cumDiffColor}" style="border-right: 1px solid #cbd5e1;">${cumDiffSign}${Number(sum.cum_gl_diff || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    
                    <!-- HOSxP Total -->
                    <td class="text-end font-monospace text-primary fw-bold" style="background-color: #f5f3ff; border-right: 2px solid #cbd5e1;">${Number(sum.cum_hosxp_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>

                    <!-- OPD -->
                    <td class="text-end font-monospace">${Number(sum.cum_op_visits || 0).toLocaleString()}</td>
                    <td class="text-end font-monospace text-info">${Number(sum.cum_op_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace text-secondary" style="border-right: 1px solid #cbd5e1;">${Number(sum.avg_op_unit_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    
                    <!-- IPD -->
                    <td class="text-end font-monospace">${Number(sum.cum_ip_admits || 0).toLocaleString()}</td>
                    <td class="text-end font-monospace">${Number(sum.cum_ip_bed_days || 0).toLocaleString()}</td>
                    <td class="text-end font-monospace text-dark" style="background-color: #fef3c7;">${Number(sum.cum_ip_adjrw || 0).toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4})}</td>
                    <td class="text-end font-monospace" style="color: #8b5cf6;">${Number(sum.cum_ip_cost || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace text-secondary">${Number(sum.avg_ip_cost_per_adjrw || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
            `;
        }

        // Render Dual-Axis Chart
        renderDrilldownChart(data.series, mapping, sum, isRev, isNetMargin);
    }

    function renderDrilldownChart(series, mapping, sum, isRev, isNetMargin = false) {
        if (typeof isNetMargin === 'undefined') isNetMargin = false;
        const canvas = document.getElementById('chartPlanfinDrilldown');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (drilldownChartInstance) {
            drilldownChartInstance.destroy();
        }

        const labels = series.map(s => s.label);
        const glActuals = series.map(s => s.gl_amount);
        const glPlans = series.map(s => s.gl_plan);
        const opCosts = series.map(s => s.op_cost);
        const ipCosts = series.map(s => s.ip_cost);
        const opVisits = series.map(s => s.op_visits);
        const ipAdjrw = series.map(s => s.ip_adjrw);

        drilldownChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'bar',
                        label: isRev ? 'ผู้ป่วยนอก OPD สิทธิ (ครั้ง)' : 'ผู้ป่วยนอก OPD (ครั้ง)',
                        data: opVisits,
                        backgroundColor: 'rgba(99, 102, 241, 0.18)',
                        borderColor: 'rgba(99, 102, 241, 0.45)',
                        borderWidth: 1,
                        yAxisID: 'yVisits',
                        order: 5
                    },
                    {
                        type: 'bar',
                        label: 'ผู้ป่วยใน IPD (AdjRW)',
                        data: ipAdjrw,
                        backgroundColor: 'rgba(245, 158, 11, 0.28)',
                        borderColor: 'rgba(245, 158, 11, 0.6)',
                        borderWidth: 1,
                        yAxisID: 'yAdjrw',
                        order: 6
                    },
                    {
                        type: 'line',
                        label: isNetMargin ? 'ผลสุทธิ GL (บาท)' : (isRev ? 'รายได้จริง GL (บาท)' : 'ซื้อจริง GL (บาท)'),
                        data: glActuals,
                        borderColor: isRev ? '#16a34a' : '#dc2626',
                        backgroundColor: isRev ? '#16a34a' : '#dc2626',
                        borderWidth: 2.5,
                        tension: 0.2,
                        pointRadius: 4,
                        yAxisID: 'yMoney',
                        order: 1
                    },
                    {
                        type: 'line',
                        label: isNetMargin ? 'ส่วนต่างสุทธิ OPD (บาท)' : (isRev ? 'รายได้ OPD (บาท)' : 'มูลค่า OPD (บาท)'),
                        data: opCosts,
                        borderColor: '#0284c7',
                        backgroundColor: '#0284c7',
                        borderWidth: 2,
                        borderDash: [5, 4],
                        tension: 0.2,
                        pointRadius: 3,
                        yAxisID: 'yMoney',
                        order: 2
                    },
                    {
                        type: 'line',
                        label: isNetMargin ? 'ส่วนต่างสุทธิ IPD (บาท)' : (isRev ? 'รายได้ IPD (บาท)' : 'มูลค่า IPD (บาท)'),
                        data: ipCosts,
                        borderColor: '#8b5cf6',
                        backgroundColor: '#8b5cf6',
                        borderWidth: 2,
                        borderDash: [5, 4],
                        tension: 0.2,
                        pointRadius: 3,
                        yAxisID: 'yMoney',
                        order: 3
                    },
                    {
                        type: 'line',
                        label: 'แผนงวด (บาท)',
                        data: glPlans,
                        borderColor: '#94a3b8',
                        backgroundColor: '#94a3b8',
                        borderWidth: 1.5,
                        borderDash: [3, 3],
                        pointRadius: 0,
                        yAxisID: 'yMoney',
                        order: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { size: 10, weight: 'bold' },
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let val = context.parsed.y;
                                if (context.dataset.yAxisID === 'yMoney') {
                                    return `${label}: ${Number(val).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท`;
                                } else if (context.dataset.yAxisID === 'yAdjrw') {
                                    return `${label}: ${Number(val).toLocaleString(undefined, {minimumFractionDigits: 4, maximumFractionDigits: 4})} แต้ม`;
                                } else {
                                    return `${label}: ${Number(val).toLocaleString()} ครั้ง`;
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    yMoney: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'ยอดเงิน (บาท)',
                            font: { size: 10, weight: 'bold' }
                        },
                        ticks: {
                            callback: function(v) { return (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : (v/1000).toFixed(0) + 'k'); },
                            font: { size: 10 }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    yVisits: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'OPD (ครั้ง)',
                            font: { size: 10, weight: 'bold' }
                        },
                        ticks: {
                            font: { size: 10 }
                        },
                        grid: { drawOnChartArea: false }
                    },
                    yAdjrw: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'IPD (AdjRW)',
                            font: { size: 10, weight: 'bold' }
                        },
                        ticks: {
                            font: { size: 10 }
                        },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    function triggerAiFromDrilldown() {
        if (!currentDrilldownData) return;
        // Close drilldown modal and open AI modal
        const dModalEl = document.getElementById('modalPlanfinServiceDrilldown');
        const dModal = bootstrap.Modal.getInstance(dModalEl);
        if (dModal) dModal.hide();

        setTimeout(() => {
            openPlanfinAiModal(currentDrilldownData.plan_code, currentDrilldownData.plan_name);
        }, 300);
    }

    function openPlanfinAiModal(planCode, planName) {
        const modalEl = document.getElementById('modalPlanfinAi');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        document.getElementById('aiLoadingContainer').classList.remove('d-none');
        document.getElementById('aiAnswerContainer').classList.add('d-none');
        modal.show();

        fetch(`{{ url('hosfin/planfin/ai_analyze') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                plan_code: planCode,
                budget_year: {{ $budgetYear }},
                period: '{{ $selectedPeriod }}'
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('aiLoadingContainer').classList.add('d-none');
            const answerBox = document.getElementById('aiAnswerContainer');
            if (data.success && data.answer_html) {
                answerBox.innerHTML = data.answer_html;
                answerBox.classList.remove('d-none');
            } else {
                answerBox.innerHTML = `<div class="alert alert-danger mb-0">${data.message || 'ไม่สามารถวิเคราะห์ข้อมูลได้'}</div>`;
                answerBox.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error('AI error:', err);
            document.getElementById('aiLoadingContainer').classList.add('d-none');
            const answerBox = document.getElementById('aiAnswerContainer');
            answerBox.innerHTML = `<div class="alert alert-danger mb-0">เกิดข้อผิดพลาดในการเชื่อมต่อ: ${err.message}</div>`;
            answerBox.classList.remove('d-none');
        });
    }

    // =========================================================================
    // SMART PROCUREMENT CALCULATOR (TAB 2)
    // =========================================================================
    function runSmartProcurementCalc() {
        const opGrowth = parseFloat(document.getElementById('calcOpGrowth').value) || 0;
        const ipGrowth = parseFloat(document.getElementById('calcIpGrowth').value) || 0;
        const safetyBuffer = parseFloat(document.getElementById('calcSafetyBuffer').value) || 0;

        const spinner = document.getElementById('calcProcureSpinner');
        const icon = document.getElementById('calcProcureIcon');
        const btn = document.getElementById('btnRunProcureCalc');

        if (spinner) spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
        if (btn) btn.disabled = true;

        fetch(`{{ url('hosfin/planfin/calculate_procurement') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                target_year: {{ $targetSimYear }},
                budget_year: {{ $budgetYear }},
                baseline_period: '{{ $selectedPeriod }}',
                op_growth_rate: opGrowth,
                ip_growth_rate: ipGrowth,
                safety_buffer_rate: safetyBuffer
            })
        })
        .then(res => res.json())
        .then(data => {
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
            if (btn) btn.disabled = false;

            if (!data.success) {
                alert('เกิดข้อผิดพลาด: ' + (data.message || 'คำนวณไม่สำเร็จ'));
                return;
            }

            latestProcurementResults = data;
            document.getElementById('boxProcurementResult').classList.remove('d-none');
            document.getElementById('txtTotalProcureRecommended').textContent = Number(data.total_recommended || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

            const grid = document.getElementById('gridProcurementResultItems');
            let itemsHtml = '';
            for (const [code, item] of Object.entries(data.items)) {
                itemsHtml += `
                    <div class="col-md-4 col-sm-6">
                        <div class="p-2 rounded-2 bg-white border shadow-2xs">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge rounded-pill bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">${code}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">ฐานปีนี้: ${Number(item.base_annual).toLocaleString(undefined, {maximumFractionDigits: 0})} บ.</small>
                            </div>
                            <div class="small fw-semibold text-truncate mb-1" style="font-size: 0.76rem;" title="${item.name}">${item.name}</div>
                            <div class="d-flex justify-content-between align-items-baseline">
                                <span class="small text-muted" style="font-size: 0.68rem;">แนะนำ:</span>
                                <strong class="text-primary font-monospace" style="font-size: 0.88rem;">${Number(item.recommended_total).toLocaleString(undefined, {minimumFractionDigits: 2})}</strong>
                            </div>
                        </div>
                    </div>
                `;
            }
            grid.innerHTML = itemsHtml;
        })
        .catch(err => {
            console.error('Procurement calc error:', err);
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
            if (btn) btn.disabled = false;
            alert('เกิดข้อผิดพลาดในการคำนวณ: ' + err.message);
        });
    }

    function applyProcurementToSubplans() {
        if (!latestProcurementResults || !latestProcurementResults.items) {
            alert('กรุณากดคำนวณงบแนะนำก่อนนำไปหยอดในตาราง');
            return;
        }

        let updatedCount = 0;
        for (const [code, item] of Object.entries(latestProcurementResults.items)) {
            const inputEl = document.getElementById('subplan_nonbg_' + code);
            if (inputEl) {
                inputEl.value = Number(item.recommended_non_budget).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                calcSubPlanTotal(code);
                updatedCount++;
            }

            // If it maps to primary plan category (e.g. MED01 -> P15, MED04 -> P17), update main simulator input
            const pCode = item.plan_code;
            const mainInput = document.getElementById('target_' + pCode);
            if (mainInput && (code === 'MED01' || code === 'MED04' || code === 'MED06')) {
                mainInput.value = Number(item.recommended_total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                onTargetSimChange(pCode);
            }
        }

        alert(`✓ นำตัวเลขงบจัดซื้อแนะนำหยอดลงในตารางแผนปฏิบัติการย่อย (แผนที่ 2) สำเร็จเรียบร้อย (${updatedCount} รายการ) ระบบได้คำนวณผลรวมให้เรียบร้อยแล้วค่ะ!`);
    }

    function consultAiForProcurement() {
        openPlanfinAiModal('P14', 'ต้นทุนยาและแผนจัดซื้อยา');
    }

    // =========================================================================
    // SMART PLANFIN: 12-MONTH MATRIX TRENDS CHART MODAL (CATEGORIES & SUB-ACCOUNTS)
    // =========================================================================
    const matrixPeriods = @json(array_column($periodOptions, 'period'));
    const matrixPeriodLabels = @json(array_map(function($opt) { return explode(' ', $opt['label'])[0]; }, $periodOptions));
    const matrixCategoryData = @json($matrixCategoryLookup ?? []);
    const matrixSubAccountData = @json($matrixSubLookup ?? []);

    const matrixPalette = [
        '#2563eb', '#059669', '#7c3aed', '#0891b2', '#db2777', '#4f46e5', '#16a34a',
        '#9333ea', '#0284c7', '#ca8a04', '#e11d48', '#475569', '#0d9488', '#b91c1c'
    ];
    const matrixPlanPalette = [
        '#ea580c', '#d97706', '#ca8a04', '#f59e0b', '#c2410c', '#b45309', '#e11d48'
    ];

    let currentMatrixMode = 'category'; // 'category' | 'sub'
    let matrixSelectedKeys = new Set(); // Default empty: หน้าล้าง ให้ผู้ใช้เลือกแสดงเอง
    let matrixSubSelectedKeys = new Set(); // Default empty: หน้าล้าง ให้ผู้ใช้เลือกแสดงเอง
    let matrixChartInstance = null;

    // Inline plugin to render crisp compact numbers on chart points
    const matrixDataLabelsPlugin = {
        id: 'matrixDataLabelsPlugin',
        afterDatasetsDraw(chart) {
            // Automatically show labels when 1 or 2 items selected (<= 4 lines)
            if (chart.data.datasets.length > 4) return;
            const ctx = chart.ctx;
            ctx.save();
            ctx.font = 'bold 9.5px system-ui, -apple-system, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            chart.data.datasets.forEach((dataset, datasetIdx) => {
                const meta = chart.getDatasetMeta(datasetIdx);
                if (meta.hidden) return;
                const isPlan = dataset.isPlan;

                meta.data.forEach((element, pointIdx) => {
                    const val = dataset.data[pointIdx];
                    if (val === null || val === undefined || isNaN(val)) return;

                    let text = '';
                    const absVal = Math.abs(val);
                    if (absVal >= 1000000) {
                        text = (val / 1000000).toFixed(2) + 'M';
                    } else if (absVal >= 1000) {
                        text = (val / 1000).toFixed(1) + 'k';
                    } else {
                        text = Number(val).toLocaleString(undefined, {maximumFractionDigits: 0});
                    }

                    const x = element.x;
                    // Actual placed above point, Plan placed below point
                    const y = isPlan ? (element.y + 14) : (element.y - 13);

                    const metrics = ctx.measureText(text);
                    const boxW = metrics.width + 7;
                    const boxH = 14;

                    // Subtle pill background
                    ctx.fillStyle = isPlan ? 'rgba(255, 247, 237, 0.96)' : 'rgba(255, 255, 255, 0.96)';
                    ctx.strokeStyle = dataset.borderColor;
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    if (typeof ctx.roundRect === 'function') {
                        ctx.roundRect(x - (boxW / 2), y - (boxH / 2), boxW, boxH, 4);
                    } else {
                        ctx.rect(x - (boxW / 2), y - (boxH / 2), boxW, boxH);
                    }
                    ctx.fill();
                    ctx.stroke();

                    // Label text
                    ctx.fillStyle = isPlan ? '#9a3412' : '#1e40af';
                    ctx.fillText(text, x, y);
                });
            });
            ctx.restore();
        }
    };

    function openMatrixChartModal(mode = 'category') {
        currentMatrixMode = mode;
        const modalEl = document.getElementById('modalMatrixTrendsChart');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        
        // Update header buttons
        updateMatrixModeUI();

        // Render checklist and chart
        renderMatrixChecklist();
        modal.show();

        setTimeout(() => {
            renderMatrixTrendsChart();
        }, 150);
    }

    function switchMatrixChartMode(newMode) {
        if (currentMatrixMode === newMode) return;
        currentMatrixMode = newMode;
        const searchInp = document.getElementById('matrixSearchInput');
        if (searchInp) searchInp.value = '';
        updateMatrixModeUI();
        renderMatrixChecklist();
        renderMatrixTrendsChart();
    }

    function updateMatrixModeUI() {
        const btnCat = document.getElementById('btnMatrixModeCategory');
        const btnSub = document.getElementById('btnMatrixModeSub');
        const titleEl = document.getElementById('matrixModalTitle');
        const iconEl = document.getElementById('matrixModalHeaderIcon');

        if (currentMatrixMode === 'category') {
            btnCat.style.backgroundColor = '#4f46e5';
            btnCat.style.color = '#ffffff';
            btnCat.classList.add('shadow-xs');

            btnSub.style.backgroundColor = 'transparent';
            btnSub.style.color = '#cbd5e1';
            btnSub.classList.remove('shadow-xs');

            titleEl.innerHTML = '📈 กราฟแนวโน้ม 12 เดือน: <span class="text-warning">หมวดหลัก</span>';
            iconEl.className = 'bi bi-graph-up-arrow fs-5 text-white';
        } else {
            btnSub.style.backgroundColor = '#4f46e5';
            btnSub.style.color = '#ffffff';
            btnSub.classList.add('shadow-xs');

            btnCat.style.backgroundColor = 'transparent';
            btnCat.style.color = '#cbd5e1';
            btnCat.classList.remove('shadow-xs');

            titleEl.innerHTML = '📊 กราฟแนวโน้ม 12 เดือน: <span class="text-warning">ผังบัญชีย่อย</span>';
            iconEl.className = 'bi bi-bar-chart-steps fs-5 text-white';
        }
    }

    function renderMatrixChecklist() {
        const container = document.getElementById('matrixChecklistContainer');
        const isCat = (currentMatrixMode === 'category');
        const dataObj = isCat ? matrixCategoryData : matrixSubAccountData;
        const selectedSet = isCat ? matrixSelectedKeys : matrixSubSelectedKeys;
        const totalCount = Object.keys(dataObj).length;
        
        document.getElementById('matrixListTotalCount').textContent = `ทั้งหมด ${totalCount.toLocaleString()} รายการ`;
        document.getElementById('matrixSelectedCount').textContent = selectedSet.size.toLocaleString();

        let html = '';
        let colorIdx = 0;

        const items = Object.values(dataObj);
        if (!isCat) {
            items.sort((a, b) => (b.total || 0) - (a.total || 0));
        }

        items.forEach(item => {
            const key = isCat ? item.code : item.account_code;
            const isChecked = selectedSet.has(key);
            const color = matrixPalette[colorIdx % matrixPalette.length];
            colorIdx++;

            const badgeColor = item.type === 'revenue' 
                ? 'bg-success-subtle text-success border-success-subtle' 
                : (item.type === 'summary' ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-danger-subtle text-danger border-danger-subtle');

            const name = isCat ? item.name : item.account_name;
            const codeBadge = isCat ? key : `<span class="font-monospace">${key}</span>`;
            const totalFmt = Number(item.total || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

            html += `
                <div class="matrix-check-item p-2 rounded-2 mb-1 border transition-all ${isChecked ? 'bg-indigo-50 border-indigo-200' : 'bg-white'}" 
                     data-key="${key}" data-type="${item.type}" data-name="${(name + ' ' + key).toLowerCase()}" style="font-size: 0.76rem; cursor: pointer;"
                     onclick="toggleMatrixItemClick('${key}', event)">
                    <div class="d-flex align-items-center gap-2">
                        <input class="form-check-input mt-0 flex-shrink-0" type="checkbox" id="chkMatrix_${key.replace(/[^a-zA-Z0-9]/g, '_')}" 
                               value="${key}" ${isChecked ? 'checked' : ''} onchange="onMatrixCheckItem('${key}', this.checked)" onclick="event.stopPropagation()">
                        <span class="rounded-circle flex-shrink-0" style="width: 10px; height: 10px; background-color: ${color}; display: inline-block;"></span>
                        <div class="flex-grow-1 text-truncate">
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="badge ${badgeColor} border px-1.5 py-0 font-monospace" style="font-size: 0.65rem;">${codeBadge}</span>
                                <strong class="text-dark text-truncate" title="${name}">${name}</strong>
                            </div>
                        </div>
                        <div class="text-end font-monospace text-muted flex-shrink-0" style="font-size: 0.70rem;">
                            ${totalFmt} บ.
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html || '<div class="text-center text-muted py-4">ไม่พบข้อมูล</div>';
    }

    function toggleMatrixItemClick(key, e) {
        if (e.target.tagName.toLowerCase() === 'input') return;
        const isCat = (currentMatrixMode === 'category');
        const selectedSet = isCat ? matrixSelectedKeys : matrixSubSelectedKeys;
        const chk = document.getElementById('chkMatrix_' + key.replace(/[^a-zA-Z0-9]/g, '_'));
        const newChecked = !selectedSet.has(key);
        if (chk) chk.checked = newChecked;
        onMatrixCheckItem(key, newChecked);
    }

    function onMatrixCheckItem(key, isChecked) {
        const isCat = (currentMatrixMode === 'category');
        const selectedSet = isCat ? matrixSelectedKeys : matrixSubSelectedKeys;
        if (isChecked) {
            selectedSet.add(key);
        } else {
            selectedSet.delete(key);
        }
        document.getElementById('matrixSelectedCount').textContent = selectedSet.size.toLocaleString();
        
        // Highlight row
        const itemRow = document.querySelector(`.matrix-check-item[data-key="${key}"]`);
        if (itemRow) {
            if (isChecked) {
                itemRow.classList.add('bg-indigo-50', 'border-indigo-200');
                itemRow.classList.remove('bg-white');
            } else {
                itemRow.classList.remove('bg-indigo-50', 'border-indigo-200');
                itemRow.classList.add('bg-white');
            }
        }

        renderMatrixTrendsChart();
    }

    function onMatrixSearchInput(kw) {
        const clean = kw.trim().toLowerCase();
        document.querySelectorAll('.matrix-check-item').forEach(el => {
            const str = el.getAttribute('data-name') || '';
            if (!clean || str.includes(clean)) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });
    }

    function clearMatrixSearch() {
        const inp = document.getElementById('matrixSearchInput');
        if (inp) {
            inp.value = '';
            onMatrixSearchInput('');
        }
    }

    function matrixSelectAll(isChecked) {
        const isCat = (currentMatrixMode === 'category');
        const selectedSet = isCat ? matrixSelectedKeys : matrixSubSelectedKeys;
        
        // Only affect visible items from search filter
        document.querySelectorAll('.matrix-check-item:not(.d-none)').forEach(el => {
            const key = el.getAttribute('data-key');
            const chk = el.querySelector('input[type="checkbox"]');
            if (chk) chk.checked = isChecked;
            if (isChecked) {
                selectedSet.add(key);
                el.classList.add('bg-indigo-50', 'border-indigo-200');
                el.classList.remove('bg-white');
            } else {
                selectedSet.delete(key);
                el.classList.remove('bg-indigo-50', 'border-indigo-200');
                el.classList.add('bg-white');
            }
        });

        document.getElementById('matrixSelectedCount').textContent = selectedSet.size.toLocaleString();
        renderMatrixTrendsChart();
    }

    function matrixFilterByType(type) {
        const isCat = (currentMatrixMode === 'category');
        const selectedSet = isCat ? matrixSelectedKeys : matrixSubSelectedKeys;
        selectedSet.clear();

        document.querySelectorAll('.matrix-check-item').forEach(el => {
            const itemType = el.getAttribute('data-type');
            const key = el.getAttribute('data-key');
            const chk = el.querySelector('input[type="checkbox"]');
            const match = (itemType === type);
            if (chk) chk.checked = match;
            if (match) {
                selectedSet.add(key);
                el.classList.add('bg-indigo-50', 'border-indigo-200');
                el.classList.remove('bg-white');
            } else {
                el.classList.remove('bg-indigo-50', 'border-indigo-200');
                el.classList.add('bg-white');
            }
        });

        document.getElementById('matrixSelectedCount').textContent = selectedSet.size.toLocaleString();
        renderMatrixTrendsChart();
    }

    function renderMatrixTrendsChart() {
        const canvas = document.getElementById('canvasMatrixTrendsChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        if (matrixChartInstance) {
            matrixChartInstance.destroy();
        }

        const isCat = (currentMatrixMode === 'category');
        const dataObj = isCat ? matrixCategoryData : matrixSubAccountData;
        const selectedSet = isCat ? matrixSelectedKeys : matrixSubSelectedKeys;
        const emptyState = document.getElementById('matrixChartEmptyState');

        if (selectedSet.size === 0) {
            if (emptyState) emptyState.classList.remove('d-none');
            document.getElementById('matrixChartSumTotal').textContent = '0.00 บ.';
            const planEl = document.getElementById('matrixChartPlanTotal');
            if (planEl) planEl.textContent = '0.00 บ.';
            const diffEl = document.getElementById('matrixChartDiffTotal');
            if (diffEl) { diffEl.textContent = '0.00 บ.'; diffEl.className = 'fw-bold font-monospace'; }
            const avgEl = document.getElementById('matrixChartMonthlyAvg');
            if (avgEl) avgEl.textContent = '0.00 บ.';
            return;
        } else {
            if (emptyState) emptyState.classList.add('d-none');
        }

        // Build datasets
        const datasets = [];
        let grandActualTotal = 0;
        let grandPlanTotal = 0;
        let colorIdx = 0;
        const isSingle = (selectedSet.size === 1);

        const items = Object.values(dataObj);
        if (!isCat) {
            items.sort((a, b) => (b.total || 0) - (a.total || 0));
        }

        items.forEach(item => {
            const key = isCat ? item.code : item.account_code;
            if (!selectedSet.has(key)) return;

            // When single item: Actual is Royal Blue (#2563eb), Plan is Vivid Amber/Orange (#ea580c)!
            // When multiple items: Actual takes unique color from matrixPalette, Plan takes warm accent!
            const actualColor = isSingle ? '#2563eb' : matrixPalette[colorIdx % matrixPalette.length];
            const planColor = isSingle ? '#ea580c' : (matrixPlanPalette[colorIdx % matrixPlanPalette.length] || '#ea580c');
            colorIdx++;

            const actualPoints = matrixPeriods.map(p => Number(item.series[p] || 0));
            const actualTotal = Number(item.total || 0);
            grandActualTotal += actualTotal;

            const planAnnual = Number(item.annual_target || 0);
            grandPlanTotal += planAnnual;

            const name = isCat ? `${item.code} - ${item.name}` : `${item.account_code} ${item.account_name}`;

            // 1. เส้นผลการดำเนินงานจริง (Actual - เส้นทึบ สีน้ำเงิน มี Fill สวยงาม)
            datasets.push({
                label: `[ผลจริง] ${name}`,
                rawName: name,
                isPlan: false,
                data: actualPoints,
                borderColor: actualColor,
                backgroundColor: 'rgba(37, 99, 235, 0.12)',
                borderWidth: isSingle ? 2.8 : 2.2,
                borderDash: [],
                fill: true,
                tension: 0.35,
                pointRadius: isSingle ? 4.5 : 3.5,
                pointHoverRadius: 6.5,
                pointBackgroundColor: actualColor,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                order: 1
            });

            // 2. เส้นแผนเป้าหมาย (Plan - เส้นประ สีส้มอำพัน/ทอง แยกสีชัดเจน)
            const planPoints = matrixPeriods.map(p => {
                if (item.plan_series && item.plan_series[p] !== undefined) {
                    return Number(item.plan_series[p] || 0);
                }
                return Number(item.plan_monthly || (planAnnual / 12.0) || 0);
            });

            datasets.push({
                label: `[แผน] ${name}`,
                rawName: name,
                isPlan: true,
                data: planPoints,
                borderColor: planColor,
                backgroundColor: 'transparent',
                borderWidth: 2.2,
                borderDash: [6, 4], // เส้นประ
                fill: false,
                tension: 0.0,
                pointRadius: isSingle ? 4 : 3,
                pointHoverRadius: 6,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: planColor,
                pointBorderWidth: 2,
                order: 2
            });
        });

        // Summary stats
        document.getElementById('matrixChartSumTotal').textContent = Number(grandActualTotal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บ.';
        const planTotalEl = document.getElementById('matrixChartPlanTotal');
        if (planTotalEl) {
            planTotalEl.textContent = Number(grandPlanTotal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บ.';
        }

        const diffTotal = grandActualTotal - grandPlanTotal;
        const diffEl = document.getElementById('matrixChartDiffTotal');
        if (diffEl) {
            const prefix = diffTotal > 0 ? '+' : '';
            diffEl.textContent = prefix + Number(diffTotal).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บ.';
            diffEl.className = 'fw-bold font-monospace ' + (diffTotal >= 0 ? 'text-success' : 'text-danger');
        }

        const monthlyAvg = grandActualTotal / 12.0;
        const monthlyAvgEl = document.getElementById('matrixChartMonthlyAvg');
        if (monthlyAvgEl) {
            monthlyAvgEl.textContent = Number(monthlyAvg).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บ.';
        }

        matrixChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: matrixPeriodLabels,
                datasets: datasets
            },
            plugins: [matrixDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: datasets.length <= 16,
                        position: 'top',
                        labels: {
                            font: { size: 10, weight: 'bold' },
                            boxWidth: 16,
                            padding: 8
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = Number(context.parsed.y || 0);
                                const isPlan = context.dataset.isPlan;
                                const icon = isPlan ? '┄ ' : '● ';
                                return ` ${icon}${context.dataset.label}: ${val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 11, weight: 'bold' } }
                    },
                    y: {
                        grid: { color: 'rgba(0,0,0,0.06)' },
                        ticks: {
                            font: { size: 10 },
                            callback: function(val) {
                                if (Math.abs(val) >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                if (Math.abs(val) >= 1000) return (val / 1000).toFixed(0) + 'k';
                                return val;
                            }
                        }
                    }
                }
            }
        });
    }

    function downloadMatrixChartPng() {
        if (!matrixChartInstance) return;
        const link = document.createElement('a');
        link.download = `TrendChart_${currentMatrixMode}_${Date.now()}.png`;
        link.href = matrixChartInstance.toBase64Image();
        link.click();
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
