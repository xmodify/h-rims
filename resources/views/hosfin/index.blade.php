@extends('layouts.app')

@section('content')
<style>
  .hosfin-card {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #ffffff;
  }
  .hosfin-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.1);
  }
  .accent-teal { border-top: 4px solid #10b981 !important; }
  .accent-blue { border-top: 4px solid #3b82f6 !important; }
  .accent-red { border-top: 4px solid #ef4444 !important; }

  /* Dashboard Metrics Card Styles */
  .metric-card {
    transition: all 0.25s ease-in-out;
    cursor: pointer;
    border: 1px solid #e2e8f0 !important;
  }
  .executive-kpi-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  }
  .executive-kpi-card:hover {
    transform: translateY(-4px) scale(1.015) !important;
    box-shadow: 0 12px 22px -6px rgba(0, 0, 0, 0.1) !important;
  }
  .fw-black {
    font-weight: 900 !important;
  }
  /* DataTable in Modal Styling */
  #cashAccountsTable_wrapper .row:first-child {
    align-items: center !important;
    margin-bottom: 0.85rem !important;
  }
  #cashAccountsTable_wrapper .dataTables_length {
    text-align: left !important;
  }
  #cashAccountsTable_wrapper .dataTables_length label {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    margin-bottom: 0 !important;
    font-size: 0.82rem !important;
    color: #475569 !important;
  }
  #cashAccountsTable_wrapper .dataTables_length select {
    border-radius: 20px !important;
    padding: 0.25rem 1.75rem 0.25rem 0.75rem !important;
    border: 1px solid #cbd5e1 !important;
    font-size: 0.82rem !important;
    outline: none !important;
  }
  #cashAccountsTable_wrapper .dataTables_filter {
    text-align: right !important;
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center !important;
    width: 100% !important;
    margin-bottom: 0 !important;
  }
  #cashAccountsTable_wrapper .dataTables_filter label {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 6px !important;
    margin-bottom: 0 !important;
    width: 100% !important;
  }
  #cashAccountsTable_wrapper .dataTables_filter input {
    border-radius: 20px !important;
    padding: 0.38rem 1rem !important;
    border: 1px solid #cbd5e1 !important;
    outline: none !important;
    font-size: 0.82rem !important;
    width: 280px !important;
    max-width: 100% !important;
    margin-left: auto !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
    transition: all 0.2s ease !important;
  }
  #cashAccountsTable_wrapper .dataTables_filter input:focus {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.18) !important;
  }
  #cashAccountsTable_wrapper .pagination {
    margin-bottom: 0 !important;
    justify-content: flex-end !important;
  }
  #cashAccountsTable_wrapper .pagination .page-item.active .page-link {
    background-color: #10b981 !important;
    border-color: #10b981 !important;
    color: #ffffff !important;
  }
  #cashAccountsTable thead th {
    user-select: none;
    white-space: nowrap;
  }
  .cash-filter-card {
    cursor: pointer;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent !important;
  }
  .cash-filter-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
  }
  .cash-filter-card.active-operating {
    border: 2px solid #10b981 !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.18) !important;
  }
  .cash-filter-card.active-restricted {
    border: 2px solid #f59e0b !important;
    background-color: #fffbeb !important;
    box-shadow: 0 6px 18px rgba(245, 158, 11, 0.18) !important;
  }
  .cash-filter-card.active-all {
    border: 2px solid #3b82f6 !important;
    background-color: #eff6ff !important;
    box-shadow: 0 6px 18px rgba(59, 130, 246, 0.18) !important;
  }
  .hover-highlight:hover {
    background-color: rgba(16, 185, 129, 0.1) !important;
  }
  .metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08) !important;
    filter: brightness(0.98);
  }
  .metric-title {
    font-size: 0.8rem;
    color: #475569;
    font-weight: 600;
  }
  .metric-value {
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1.2;
  }
  .text-success-custom { color: #15803d !important; }
  .text-danger-custom { color: #b91c1c !important; }
  .text-warning-custom { color: #b45309 !important; }
  .metric-unit {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 600;
  }
  .section-title-custom {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 6px;
    margin-bottom: 14px;
  }
  .badge-custom {
    font-size: 0.72rem;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: 600;
  }
  .btn-nav-custom {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  }
  .btn-nav-custom:hover {
    transform: translateY(-2.5px) scale(1.025);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1) !important;
  }
  .btn-nav-custom:active {
    transform: translateY(0) scale(0.98);
  }
  .btn-tb-custom:hover {
    background-color: #f0fdf4 !important;
    border-color: #059669 !important;
    color: #047857 !important;
  }
  .btn-rr-custom:hover {
    background-color: #eff6ff !important;
    border-color: #2563eb !important;
    color: #1d4ed8 !important;
  }
  .drill-chip {
    transition: all 0.2s ease-in-out;
    background-color: #f8fafc;
    border-color: #cbd5e1;
    color: #334155;
    font-size: 0.8rem;
    padding: 0.35rem 0.75rem;
  }
  .drill-chip:hover {
    background-color: #ecfdf5 !important;
    border-color: #10b981 !important;
    color: #047857 !important;
    transform: translateY(-1.5px);
    box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.15);
  }

  /* Print Styles for HosFin */
  @media print {
    body.modal-open > *:not(#hosFinAiModal):not(.modal-backdrop) {
      display: none !important;
    }
    body.modal-open .modal-backdrop {
      display: none !important;
    }
    body.modal-open #hosFinAiModal {
      position: absolute !important;
      left: 0 !important;
      top: 0 !important;
      width: 100% !important;
      height: auto !important;
      overflow: visible !important;
      display: block !important;
      background: #ffffff !important;
      padding: 0 !important;
    }
    body.modal-open #hosFinAiModal .modal-dialog {
      max-width: 100% !important;
      width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      box-shadow: none !important;
    }
    body.modal-open #hosFinAiModal .modal-content {
      border: none !important;
      box-shadow: none !important;
      border-radius: 0 !important;
      background: #ffffff !important;
    }
    body.modal-open #hosFinAiModal .modal-header {
      background: #ffffff !important;
      color: #0f172a !important;
      border-bottom: 2px solid #0f172a !important;
      padding: 10px 0 !important;
    }
    body.modal-open #hosFinAiModal .modal-header * {
      color: #0f172a !important;
    }
    body.modal-open #hosFinAiModal .modal-header .btn-close {
      display: none !important;
    }
    body.modal-open #hosFinAiModal .modal-body {
      overflow: visible !important;
      max-height: none !important;
      padding: 15px 0 !important;
    }
    body.modal-open #hosFinAiModal .modal-footer,
    body.modal-open #aiChatbotFloatingBtn,
    body.modal-open #aiChatbotDrawer {
      display: none !important;
    }
    body.modal-open #aiAnalysisContent {
      border: none !important;
      box-shadow: none !important;
      padding: 0 !important;
    }
    body.modal-open #hosFinAiSnapshotCard .row > div {
      width: 25% !important;
      float: left !important;
    }
  }
</style>

<div class="container-fluid py-4 px-lg-5" style="background-color: #f8fafc;">
    <div class="row">
        <!-- Header banner -->
        <div class="col-12 px-3 mb-3">
            <div class="page-header-box mt-2 d-flex flex-column align-items-stretch" style="border-left: 4px solid #10b981 !important; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); padding: 16px 22px; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-left: 4px solid #10b981 !important;">
                <!-- Row 1: Header Title (Far Left) & Action Buttons (Far Right) -->
                <div class="w-100 d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-1">
                    <h5 class="text-primary mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-bank2 text-success fs-4"></i> ระบบบริหารการเงินการคลัง <span class="d-none d-sm-inline" style="font-size: 0.95rem; font-weight: 600; opacity: 0.85;">(HosFin Dashboard)</span>
                    </h5>

                    <!-- Action Buttons (ขวาสุด) -->
                    <div class="d-flex align-items-center gap-1.5 flex-wrap ms-auto">
                        @if(\App\Services\LicenseVerificationService::isModuleLicensed('ai_knowledge') && \App\Services\Ai\AiService::isActive())
                            @php
                                $hasAiAccess = Auth::check() && (Auth::user()->status === 'admin' || Auth::user()->allow_ai_copilot === 'Y');
                            @endphp
                            <button type="button" class="btn rounded-pill px-2.5 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom text-white" 
                                    onclick="{{ $hasAiAccess ? 'openHosFinAiModal()' : 'showAiAccessDeniedAlert()' }}"
                                    style="font-size: 0.82rem; height: 36px; font-weight: 700; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;"
                                    title="{{ $hasAiAccess ? 'คลิกเพื่อดูบทวิเคราะห์วิกฤตทางการเงินด้วย AI' : 'คุณไม่ได้รับสิทธิ์ใช้งาน AI' }}">
                                <i class="bi bi-robot"></i> AI วิเคราะห์
                            </button>
                        @endif

                        <a href="{{ url('hosfin/cash_register') }}" class="btn rounded-pill px-2.5 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom" 
                           style="font-size: 0.82rem; height: 36px; font-weight: 700; background: #ffffff; border: 1.5px solid #059669; color: #059669; transition: all 0.25s ease;"
                           title="ทะเบียนรับ-จ่ายเงินสดและเงินฝากธนาคาร (Cash Register)">
                            <i class="bi bi-cash-stack"></i> รับ-จ่าย (Cash)
                        </a>

                        <a href="{{ url('hosfin/ap_report') }}" class="btn rounded-pill px-2.5 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom" 
                           style="font-size: 0.82rem; height: 36px; font-weight: 700; background: #ffffff; border: 1.5px solid #ef4444; color: #dc2626; transition: all 0.25s ease;"
                           title="รายงานเจ้าหนี้การค้าและบิลค้างชำระ (AP)">
                            <i class="bi bi-receipt-cutoff"></i> เจ้าหนี้ (AP)
                        </a>

                        <a href="{{ url('hosfin/ar_report') }}" class="btn rounded-pill px-2.5 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom" 
                           style="font-size: 0.82rem; height: 36px; font-weight: 700; background: #ffffff; border: 1.5px solid #0284c7; color: #0369a1; transition: all 0.25s ease;"
                           title="รายงานลูกหนี้ค่ารักษาพยาบาลแยกตามสิทธิ (AR)">
                            <i class="bi bi-wallet2"></i> ลูกหนี้ (AR)
                        </a>

                        <a href="{{ url('hosfin/cost_report') }}" class="btn rounded-pill px-2.5 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom" 
                           style="font-size: 0.82rem; height: 36px; font-weight: 700; background: #ffffff; border: 1.5px solid #d97706; color: #b45309; transition: all 0.25s ease;"
                           title="รายงานวิเคราะห์ต้นทุนบริการ (LC / MC / CC)">
                            <i class="bi bi-pie-chart"></i> ต้นทุน (LC/MC/CC)
                        </a>

                        <a href="{{ url('hosfin/trial_balance') }}" class="btn rounded-pill px-2.5 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom btn-tb-custom" 
                           style="font-size: 0.82rem; height: 36px; font-weight: 700; background: #ffffff; border: 1.5px solid #10b981; color: #059669; transition: all 0.25s ease;"
                           title="รายงานและนำเข้างบทดลอง (Trial Balance)">
                            <i class="bi bi-file-earmark-spreadsheet"></i> งบทดลอง
                        </a>

                        <a href="{{ url('hosfin/planfin') }}" class="btn rounded-pill px-2.5 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom" 
                           style="font-size: 0.82rem; height: 36px; font-weight: 700; background: #ffffff; border: 1.5px solid #6366f1; color: #4f46e5; transition: all 0.25s ease;"
                           title="ระบบบริหารและติดตามแผนเงินบำรุง (PlanFin)">
                            <i class="bi bi-graph-up-arrow"></i> PlanFin
                        </a>
                    </div>
                </div>

                <!-- Row 2: Status Badges (Period, Risk Score, Last Sync) -->
                @if($hasData)
                <div class="w-100 d-flex align-items-center gap-2 flex-wrap pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                    <!-- Budget Year Selector -->
                    @if(isset($budgetYearChoices) && count($budgetYearChoices) > 0)
                        <div class="d-inline-flex align-items-center gap-1.5 bg-white border border-success-subtle rounded-pill px-2.5 py-1 shadow-xs">
                            <i class="bi bi-calendar3 text-success" style="font-size: 0.8rem;"></i>
                            <span class="small fw-bold text-success" style="font-size: 0.76rem;">ปีงบประมาณ:</span>
                            <select class="form-select form-select-sm border-0 py-0 ps-1 pe-4 fw-bold text-dark bg-transparent" 
                                    style="font-size: 0.78rem; cursor: pointer; width: auto; box-shadow: none;" 
                                    onchange="location.href='{{ url('hosfin') }}?budget_year=' + this.value">
                                @foreach($budgetYearChoices as $by)
                                    <option value="{{ $by }}" {{ $budgetYear == $by ? 'selected' : '' }}>
                                        {{ $by }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Period Dropdown -->
                    @if(isset($periods) && count($periods) > 0)
                        <div class="d-inline-flex align-items-center gap-1.5 bg-white border border-success-subtle rounded-pill px-2.5 py-1 shadow-xs">
                            <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 0.45rem; height: 0.45rem;"></span>
                            <span class="small fw-bold text-success" style="font-size: 0.76rem;">งวดบัญชี:</span>
                            <select class="form-select form-select-sm border-0 py-0 ps-1 pe-4 fw-bold text-dark bg-transparent" 
                                    style="font-size: 0.78rem; cursor: pointer; width: auto; box-shadow: none;" 
                                    onchange="location.href='{{ url('hosfin') }}?budget_year={{ $budgetYear }}&period=' + this.value">
                                @foreach(array_reverse($periods) as $p)
                                    @if(in_array($p['period'], $importedPeriods ?? []))
                                        <option value="{{ $p['period'] }}" {{ $p['period'] === $latestPeriod ? 'selected' : '' }}>
                                            {{ $p['label'] }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @else
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2.5 py-1">
                            <span class="spinner-grow spinner-grow-sm text-success me-1" role="status" style="width: 0.5rem; height: 0.5rem;"></span>
                            ข้อมูลงวดบัญชีล่าสุด: <strong>{{ $latestPeriodLabel }}</strong> (ปีงบประมาณ {{ $budgetYear }})
                        </span>
                    @endif


                    <!-- Risk Score Badge -->
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill border shadow-xs {{ $riskScoreBgClass }} metric-card" 
                         style="cursor: pointer; transition: all 0.2s ease;" data-code="RISK_SCORE" data-name="RISK SCORE (คะแนนความเสี่ยงทางการเงิน)" title="คลิกเพื่อดูเกณฑ์คะแนนความเสี่ยง">
                        <div class="d-flex align-items-center gap-1.5">
                            <i class="bi bi-shield-exclamation {{ $riskScoreTextClass }} fs-5"></i>
                            <span class="fw-bold text-dark" style="font-size: 0.82rem; letter-spacing: 0.3px;">Risk Score</span>
                        </div>
                        <span class="badge {{ $riskScoreNumBgClass ?? ($riskScore >= 6 ? 'bg-danger text-white' : ($riskScore > 0 ? 'bg-warning text-dark' : 'bg-secondary text-white')) }} rounded-pill px-2.5 py-0.5 fw-black font-monospace shadow-xs" style="font-size: 0.92rem; line-height: 1.2;">
                            {{ $riskScore }}
                        </span>
                        <span class="badge {{ $riskScore >= 6 ? 'bg-danger text-white' : ($riskScore >= 3 ? 'bg-warning text-dark' : ($riskScore > 0 ? 'bg-success text-white' : 'bg-secondary text-white')) }} rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem;">
                            {{ $riskScoreLevelLabel }}
                        </span>
                        <i class="bi bi-arrow-up-right text-muted" style="font-size: 0.75rem;"></i>
                    </div>

                    <!-- Last Sync Timestamp Badge -->
                    <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1 rounded-pill border shadow-xs bg-white text-muted" 
                         style="font-size: 0.78rem; border-color: #e2e8f0 !important; cursor: default;" 
                         title="วันเวลาที่เชื่อมโยงและประมวลผลข้อมูลล่าสุดจากโปรแกรม GL">
                        <span class="d-inline-block rounded-circle {{ $glSyncSuccess ? 'bg-success' : 'bg-secondary' }}" style="width: 7px; height: 7px;"></span>
                        <i class="bi bi-arrow-repeat {{ $glSyncSuccess ? 'text-success' : 'text-muted' }}" style="font-size: 0.85rem;"></i>
                        <span>Sync ล่าสุด:</span>
                        <strong class="text-dark font-monospace" style="font-size: 0.82rem;">{{ $glSyncTimeText }}</strong>
                    </div>
                </div>
                @else
                <div class="small text-muted mt-1">
                    ศูนย์รวมรายงานสถานะทางการเงินและวิเคราะห์ต้นทุนการรักษาพยาบาล
                </div>
                @endif
            </div>
        </div>

        @if($hasData)
            @php
                $val105 = $latestMetrics['105']['val'] ?? 0;
                if ($val105 == 0) {
                    $bgClass105 = '#f8fafc';
                    $borderClass105 = '#e2e8f0';
                    $textClass105 = '#64748b';
                    $badgeBg105 = 'bg-secondary text-white';
                    $iconClass105 = 'bi-dash-circle text-muted';
                    $label105 = 'รอข้อมูล GL (0.00)';
                } else {
                    $isPositive105 = $val105 > 0;
                    $bgClass105 = $isPositive105 ? '#f0fdf4' : '#fef2f2';
                    $borderClass105 = $isPositive105 ? '#bbf7d0' : '#fecaca';
                    $textClass105 = $isPositive105 ? '#15803d' : '#b91c1c';
                    $badgeBg105 = $isPositive105 ? 'bg-success text-white' : 'bg-danger text-white';
                    $iconClass105 = $isPositive105 ? 'bi-cash-coin text-success' : 'bi-exclamation-octagon-fill text-danger';
                    $label105 = $isPositive105 ? 'ปกติ (บวก)' : 'วิกฤต (ติดลบ)';
                }

                $val104 = $latestMetrics['104']['val'] ?? 0;
                $val100 = $latestMetrics['100']['val'] ?? 0;
            @endphp

            <!-- Executive Top KPI Cards Strip -->
            <div class="col-12 px-3 mb-3">
                <div class="row g-3">
                    <!-- Card 1: เงินบำรุงคงเหลือสุทธิ (105) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100 metric-card executive-kpi-card" 
                             style="background: {{ $bgClass105 }}; border: 1.5px solid {{ $borderClass105 }} !important; cursor: pointer;"
                             data-code="105" data-name="เงินบำรุงคงเหลือสุทธิ (105)">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.4px;">
                                            เงินบำรุงคงเหลือสุทธิ (105)
                                        </span>
                                        <div class="fw-black mt-1" style="font-size: 1.45rem; font-family: monospace; font-weight: 800; color: {{ $textClass105 }}; line-height: 1.2;">
                                            {{ number_format($val105, 2) }}
                                            <span style="font-size: 0.78rem; font-weight: 600;">บาท</span>
                                        </div>
                                    </div>
                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center shadow-xs" style="background: rgba(255,255,255,0.85); width: 42px; height: 42px;">
                                        <i class="bi {{ $iconClass105 }} fs-4"></i>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <span class="badge rounded-pill px-2.5 py-1 {{ $badgeBg105 }}" style="font-size: 0.72rem;">
                                        สถานะ: {{ $label105 }}
                                    </span>
                                    <small class="text-muted" style="font-size: 0.73rem;">คลิกดูแนวโน้ม <i class="bi bi-arrow-up-right"></i></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: เงินสดและเงินฝากธนาคาร (Cash & Bank Balance) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100 executive-kpi-card bg-white" 
                             style="border: 1.5px solid #a7f3d0 !important; background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%); cursor: pointer;"
                             data-bs-toggle="modal" data-bs-target="#cashBankModal" onclick="openCashModal('all')">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.74rem; letter-spacing: 0.4px;">
                                                เงินสดและเงินฝากจริง (GL)
                                            </span>
                                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-1.5 py-0.5" style="font-size: 0.65rem;">
                                                {{ number_format($cashAccountsCount ?? 0) }} เล่ม
                                            </span>
                                        </div>
                                        <div class="fw-black mt-1 text-success" style="font-size: 1.40rem; font-family: monospace; font-weight: 800; line-height: 1.2;">
                                            {{ number_format($cashBalance ?? 0, 2) }}
                                            <span style="font-size: 0.75rem; font-weight: 600;">บาท</span>
                                        </div>
                                    </div>
                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success shadow-xs" style="width: 40px; height: 40px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="#059669" class="bi bi-cash-stack" viewBox="0 0 16 16">
                                            <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                                            <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Breakdown Pill Box: เงินสดและรายการเทียบเท่าเงินสด vs เฉพาะกิจ/บริจาค -->
                                <div class="p-2 rounded-3 my-1.5" style="background: rgba(255, 255, 255, 0.9); border: 1px dashed #6ee7b7;">
                                    <div class="d-flex justify-content-between align-items-center mb-1 p-1 rounded-2 hover-highlight" 
                                         style="cursor: pointer;" 
                                         onclick="event.stopPropagation(); openCashModal('operating');" 
                                         title="คลิกเพื่อเปิดตารางกรองเฉพาะเงินสดและรายการเทียบเท่าเงินสด">
                                        <span class="text-secondary small d-flex align-items-center text-truncate" style="font-size: 0.70rem;">
                                            <i class="bi bi-check-circle-fill text-success me-1 flex-shrink-0"></i> <span class="text-truncate">เงินสดและรายการเทียบเท่าเงินสด:</span>
                                        </span>
                                        <span class="font-monospace fw-bold text-success text-nowrap ms-1" style="font-size: 0.78rem;">
                                            {{ number_format($operatingCash ?? 0, 2) }} บ. <i class="bi bi-chevron-right text-muted" style="font-size: 0.65rem;"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center p-1 rounded-2 hover-highlight" 
                                         style="cursor: pointer;" 
                                         onclick="event.stopPropagation(); openCashModal('restricted');" 
                                         title="คลิกเพื่อเปิดตารางกรองเฉพาะงบลงทุน UC และเงินบริจาค">
                                        <span class="text-secondary small d-flex align-items-center text-truncate" style="font-size: 0.70rem;">
                                            <i class="bi bi-lock-fill text-warning me-1 flex-shrink-0"></i> <span class="text-truncate">งบลงทุน/บริจาค:</span>
                                        </span>
                                        <span class="font-monospace fw-bold text-secondary text-nowrap ms-1" style="font-size: 0.78rem;">
                                            {{ number_format($restrictedCash ?? 0, 2) }} บ. <i class="bi bi-chevron-right text-muted" style="font-size: 0.65rem;"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-1.5 border-top mt-1" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <span class="badge bg-success text-white shadow-xs rounded-pill px-2 py-0.5 d-inline-flex align-items-center" style="font-size: 0.68rem; font-weight: 600;" title="ยอดเงินสดและรายการเทียบเท่าเงินสดใน GL ณ ปัจจุบัน">
                                        <span class="spinner-grow spinner-grow-sm text-light me-1" style="width: 5px; height: 5px;" role="status"></span>
                                        Live เงินสด: {{ number_format($operatingCashLive ?? $operatingCash ?? 0, 2) }} บ.
                                    </span>
                                    <small class="text-success fw-bold text-nowrap ms-1" style="font-size: 0.72rem;">คลิกดูแยกเล่ม <i class="bi bi-arrow-up-right"></i></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: เจ้าหนี้การค้าค้างจ่าย (AP) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100 executive-kpi-card bg-white" 
                             style="border: 1.5px solid #fecaca !important; background: linear-gradient(180deg, #ffffff 0%, #fff5f5 100%); cursor: pointer;"
                             data-bs-toggle="modal" data-bs-target="#hosfinApModal" onclick="openApModal()">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.4px;">
                                            หนี้สินเจ้าหนี้การค้า (AP)
                                        </span>
                                        <div class="fw-black mt-1 text-danger" style="font-size: 1.45rem; font-family: monospace; font-weight: 800; line-height: 1.2;">
                                            {{ number_format($apEndingBalance ?? $apUnpaidSum ?? 0, 2) }}
                                            <span style="font-size: 0.78rem; font-weight: 600;">บาท</span>
                                        </div>
                                        <div class="mt-1.5 d-flex flex-wrap gap-1.5 align-items-center">
                                            <span class="badge bg-white text-secondary border shadow-xs rounded-pill px-2 py-0.5" style="font-size: 0.70rem; font-weight: 600;">
                                                ณ ปิดงวด {{ $latestPeriodLabel }}
                                            </span>
                                            @if(isset($apUnpaidSum) && $apUnpaidSum > 0)
                                                <span class="badge bg-danger text-white shadow-sm rounded-pill px-2.5 py-1 d-inline-flex align-items-center" style="font-size: 0.76rem; font-weight: 700;" title="บิลเจ้าหนี้คงค้างจริงในระบบ GL ณ ปัจจุบัน">
                                                    <span class="spinner-grow spinner-grow-sm text-light me-1.5" style="width: 6px; height: 6px;" role="status"></span>
                                                    <span>ปัจจุบัน:&nbsp;</span>
                                                    <span class="font-monospace fw-black" style="font-size: 0.82rem;">{{ number_format($apUnpaidSum, 2) }}</span>
                                                    <span style="font-size: 0.70rem; opacity: 0.95;">&nbsp;บ.</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger" style="width: 42px; height: 42px;">
                                        <i class="bi bi-receipt-cutoff fs-4"></i>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-1" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <span class="text-muted text-truncate" style="font-size: 0.69rem;" title="ยอดตรงกับงบทดลองของงวดนี้">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                                        ตรงงบทดลอง <strong class="text-dark">{{ $latestPeriodLabel }}</strong>
                                    </span>
                                    <small class="text-danger fw-bold text-nowrap ms-1" style="font-size: 0.73rem;">คลิกดูสรุปเจ้าหนี้ <i class="bi bi-arrow-up-right"></i></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: ลูกหนี้ค่ารักษาค้างรับ (AR) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100 executive-kpi-card bg-white" 
                             style="border: 1.5px solid #bfdbfe !important; background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%); cursor: pointer;"
                             data-bs-toggle="modal" data-bs-target="#hosfinArModal" onclick="openArModal()">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.4px;">
                                            ลูกหนี้ค่ารักษาพยาบาล (AR)
                                        </span>
                                        <div class="fw-black mt-1 text-primary" style="font-size: 1.45rem; font-family: monospace; font-weight: 800; line-height: 1.2;">
                                            {{ number_format($arEndingBalance ?? $arOutstandingSum ?? 0, 2) }}
                                            <span style="font-size: 0.78rem; font-weight: 600;">บาท</span>
                                        </div>
                                        <div class="mt-1.5 d-flex flex-wrap gap-1.5 align-items-center">
                                            <span class="badge bg-white text-secondary border shadow-xs rounded-pill px-2 py-0.5" style="font-size: 0.70rem; font-weight: 600;">
                                                ณ ปิดงวด {{ $latestPeriodLabel }}
                                            </span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.70rem;">
                                                {{ number_format($arAccountCount ?? 0) }} ผังบัญชี
                                            </span>
                                        </div>
                                    </div>
                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width: 42px; height: 42px;">
                                        <i class="bi bi-wallet2 fs-4"></i>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-1" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <span class="text-muted text-truncate" style="font-size: 0.69rem;" title="ยอดคงเหลือ ณ สิ้นงวดบัญชีนี้">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                                        ตรงงบทดลอง <strong class="text-dark">{{ $latestPeriodLabel }}</strong>
                                    </span>
                                    <small class="text-primary fw-bold text-nowrap ms-1" style="font-size: 0.73rem;">คลิกดูสรุปลูกหนี้ <i class="bi bi-arrow-up-right"></i></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($hasData)
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

        @if(!$hasData)
            <!-- Placeholder when no GL data synced -->
            <div class="col-12 px-3 mb-4">
                <div class="card border-0 shadow-sm rounded-4 p-5 mx-auto text-center" style="max-width: 720px; background: #ffffff; border: 1.5px dashed #cbd5e1 !important;">
                    <div class="rounded-circle text-success p-4 mx-auto mb-3 shadow-xs" style="width: 90px; height: 90px; display: flex; align-items: center; justify-content: center; background: #ecfdf5; border: 2px solid #a7f3d0;">
                        <i class="bi bi-cloud-arrow-down-fill text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">ยังไม่มีข้อมูลจากระบบ GL (รอการเชื่อมต่อ)</h4>
                    <p class="text-muted mb-4" style="font-size: 0.95rem; line-height: 1.6;">
                        หน้าบริหารการเงินการคลัง (HosFin Dashboard) นี้ประมวลผลข้อมูลสดจากโปรแกรม <strong>Rims GL Sync</strong><br>
                        กรุณาเปิดโปรแกรม <strong>Rims GL Sync</strong> และกดปุ่ม <strong>[ 🚀 ซิงค์ข้อมูลทันที ]</strong> เพื่อนำเข้าข้อมูลสมุดรายวัน
                    </p>
                    <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                        <a href="{{ asset('downloads/Rims-GL-Sync.zip') }}" class="btn btn-success rounded-pill px-4 py-2.5 fw-bold shadow-sm d-flex align-items-center gap-2" style="font-size: 0.9rem;">
                            <i class="bi bi-download"></i> ดาวน์โหลด Rims GL Sync (.zip)
                        </a>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2.5 fw-bold d-flex align-items-center gap-2" onclick="location.reload()" style="font-size: 0.9rem;">
                            <i class="bi bi-arrow-clockwise"></i> ตรวจสอบสถานะใหม่
                        </button>
                    </div>
                </div>
            </div>
        @else
            <!-- Metrics Executive Panel (3 Rows Grid) -->
            <div class="col-12 px-3 mb-2">
                @php
                    $rows = [
                        'liquidity' => [
                            'title' => 'การวิเคราะห์วิกฤตทางการเงินและสภาพคล่องหมุนเวียน (Liquidity & Cash Balance)',
                            'icon' => 'bi-shield-check text-success',
                            'codes' => [
                                ['code' => '100', 'icon' => 'bi-arrow-left-right', 'name' => 'Current Ratio'],
                                ['code' => '101', 'icon' => 'bi-lightning-charge', 'name' => 'Quick Ratio'],
                                ['code' => '102', 'icon' => 'bi-cash-stack', 'name' => 'Cash Ratio'],
                                ['code' => '104', 'icon' => 'bi-wallet-fill', 'name' => 'Net Working Capital (NWC)']
                            ]
                        ],
                        'efficiency' => [
                            'title' => 'ประสิทธิภาพการบริหารคลัง ยา และลูกหนี้ชดเชย (Operational Efficiency)',
                            'icon' => 'bi-speedometer2 text-primary',
                            'codes' => [
                                ['code' => '260', 'icon' => 'bi-clock-history', 'name' => 'ระยะเวลาชำระเจ้าหนี้การค้ายา&เวชภัณฑ์มิใช่ยา'],
                                ['code' => '261', 'icon' => 'bi-wallet2', 'name' => 'ระยะเวลาถัวเฉลี่ยในการเรียกเก็บหนี้สิทธิ UC'],
                                ['code' => '262', 'icon' => 'bi-person-check-fill', 'name' => 'ระยะเวลาถัวเฉลี่ยในการเรียกเก็บหนี้สิทธิข้าราชการ'],
                                ['code' => '264', 'icon' => 'bi-prescription2', 'name' => 'การบริหารสินคงคลัง (Inventory Management)']
                            ]
                        ],
                        'profitability' => [
                            'title' => 'ความสามารถในการคุมรายจ่ายและทำกำไร (Profitability & Cost Control)',
                            'icon' => 'bi-percent text-danger',
                            'codes' => [
                                ['code' => '307', 'icon' => 'bi-file-earmark-bar-graph', 'name' => 'Net Margin (มีค่าเสื่อม)'],
                                ['code' => '320', 'icon' => 'bi-graph-up-arrow', 'name' => 'Operating Margin %'],
                                ['code' => '321', 'icon' => 'bi-briefcase', 'name' => 'Return on Asset % (ROA)'],
                                ['code' => 'NI', 'icon' => 'bi-calculator', 'name' => 'Net Income (กำไรสุทธิ)']
                            ]
                        ]
                    ];

                    $themes = [
                        ['border' => '#0d9488', 'text' => '#0d9488'], // Teal 600
                        ['border' => '#2563eb', 'text' => '#2563eb'], // Blue 600
                        ['border' => '#7c3aed', 'text' => '#7c3aed'], // Violet 600
                        ['border' => '#db2777', 'text' => '#db2777'], // Pink 600
                        ['border' => '#e11d48', 'text' => '#e11d48']  // Rose 600
                    ];
                @endphp

                @foreach($rows as $rowKey => $rowInfo)
                    <div class="section-title-custom">
                        <i class="bi {{ $rowInfo['icon'] }} me-1"></i> {{ $rowInfo['title'] }}
                        <span class="text-muted fw-normal" style="font-size: 0.75rem; margin-left: 6px;">(คลิกที่การ์ดเพื่อดูแนวโน้มรายงวดบัญชี)</span>
                    </div>
                    <div class="row g-3 mb-4 row-cols-1 row-cols-md-2 row-cols-xl-4">
                        @foreach($rowInfo['codes'] as $c)
                            @php
                                $code = $c['code'];
                                $def = $ratioDefs[$code];
                                $icon = $c['icon'];
                                $status = $statusMap[$code];
                                $metricsData = $latestMetrics[$code];
                                $theme = $themes[$loop->index % 5];
                                
                                $colorClass = 'text-dark';
                                if (strpos($status['class'], 'text-success') !== false) {
                                    $colorClass = 'text-success-custom';
                                } elseif (strpos($status['class'], 'text-danger') !== false) {
                                    $colorClass = 'text-danger-custom';
                                } elseif (strpos($status['class'], 'text-warning') !== false) {
                                    $colorClass = 'text-warning-custom';
                                }
                            @endphp
                            <div class="col">
                                <div class="card metric-card shadow-sm" style="border-left: 5.5px solid {{ $theme['border'] }} !important;" data-code="{{ $code }}" data-name="{{ $def['name'] }}">
                                    <div class="card-body p-3">
                                        <!-- Row 1: Title & Status -->
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="metric-title text-wrap" title="{{ $def['name'] }}">{{ $code }} {{ $c['name'] }}</span>
                                            <span class="badge {{ $status['bg'] }} {{ $status['class'] }} badge-custom text-nowrap ms-1">
                                                {{ $status['label'] }}
                                            </span>
                                        </div>
                                        <!-- Row 2: Value & Details -->
                                        <div class="d-flex justify-content-between align-items-end">
                                            <div>
                                                <div class="d-flex align-items-baseline">
                                                    <span class="metric-value {{ $colorClass }}">{{ number_format($metricsData['val'], $def['precision']) }}</span>
                                                    <span class="metric-unit ms-1">{{ $def['unit'] }}</span>
                                                </div>
                                                <div class="text-muted mt-1 text-nowrap" style="font-size: 0.72rem; font-weight: 500;">
                                                    @php
                                                        $numName = $def['numerator_name'];
                                                        $denName = $def['denominator_name'];
                                                        $numVal = $metricsData['num'];
                                                        $denVal = $metricsData['den'];
                                                        
                                                        // Shorten very long names to fit in the card
                                                        $numLabelShort = mb_substr($numName, 0, 7) . (mb_strlen($numName) > 7 ? '..' : '');
                                                        $denLabelShort = mb_substr($denName, 0, 7) . (mb_strlen($denName) > 7 ? '..' : '');
                                                        
                                                        $formatVal = function($v) {
                                                            if (abs($v) >= 1000000) {
                                                                return number_format($v / 1000000, 1) . 'M';
                                                            } elseif (abs($v) >= 1000) {
                                                                return number_format($v / 1000, 0) . 'k';
                                                            }
                                                            return number_format($v, 0);
                                                        };
                                                    @endphp
                                                    ({{ $numLabelShort }}: {{ $formatVal($numVal) }} | {{ $denLabelShort }}: {{ $formatVal($denVal) }})
                                                </div>
                                            </div>
                                            <div class="pb-1" style="color: {{ $theme['text'] }}; opacity: 0.85;">
                                                <i class="bi {{ $icon }}" style="font-size: 1.15rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif


    </div>
</div>

<!-- Trend Graph Popup Modal -->
@if($hasData)
<div class="modal fade" id="trendModal" tabindex="-1" aria-labelledby="trendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h6 class="modal-title fw-bold" id="trendModalLabel">
                    <i class="bi bi-graph-up me-2 text-warning"></i> กราฟแนวโน้มรายงวดบัญชี
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Chart Container -->
                <div class="bg-white p-3 rounded-3 shadow-sm border mb-3">
                    <div class="chart-container" style="position: relative; height: 260px; width: 100%">
                        <canvas id="modalTrendChart"></canvas>
                    </div>
                </div>

                <!-- Detailed Breakdown Display -->
                <div class="bg-white p-3 rounded-3 shadow-sm border mb-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <h7 class="fw-bold text-dark mb-0"><i class="bi bi-calculator-fill text-primary me-1"></i> <span id="modalBreakdownPeriodTitle">รายละเอียดที่มาของตัวเลขประจำงวด {{ $latestPeriodLabel }}</span></h7>
                        <small class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-hand-index-thumb me-1"></i>คลิกที่จุดบนกราฟเพื่อดูงวดอื่น</small>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted d-block" id="modalNumLabel"></small>
                            <strong class="text-dark fs-6" id="modalNumValue"></strong>
                        </div>
                        <div class="col-4 border-start border-end">
                            <small class="text-muted d-block" id="modalDenLabel"></small>
                            <strong class="text-dark fs-6" id="modalDenValue"></strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">ผลลัพธ์คำนวณ</small>
                            <strong class="text-success fs-5" id="modalResultValue"></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Decision and Analysis Guide Panel -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-3 bg-white">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="bi bi-journal-check text-success me-1"></i> คำอธิบายและแนวทางการวิเคราะห์สำหรับผู้บริหาร</h6>
                        <div id="modalGuideDescription" class="small text-secondary mb-3"></div>
                        <div id="modalGuideAction" class="p-3 rounded-3 small" style="background-color: #f8fafc; border-left: 4px solid #10b981; line-height: 1.6; font-size: 0.82rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AP Creditors Overview Modal -->
<div class="modal fade" id="hosfinApModal" tabindex="-1" aria-labelledby="hosfinApModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-receipt-cutoff fs-5 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="hosfinApModalLabel">สรุปสถานะหนี้สินเจ้าหนี้การค้า (Accounts Payable Overview)</h5>
                        <small class="text-white-50">ข้อมูลจากสมุดรายวัน GL ณ สิ้นงวด {{ $latestPeriodLabel }} และแฟ้มตั้งหนี้-จ่ายชำระ</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- 4 Highlights Top Strip -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-danger">
                            <small class="text-muted fw-bold d-block">ยอดหนี้ ณ สิ้นงวด ({{ $latestPeriodLabel }})</small>
                            <span class="fs-5 fw-black text-danger font-monospace">{{ number_format($apEndingBalance ?? $apUnpaidSum, 2) }}</span>
                            <small class="text-muted d-block">บาท (ตรงงบทดลอง)</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-warning">
                            <small class="text-muted fw-bold d-block">บิลค้างชำระจริง (ปัจจุบัน)</small>
                            <span class="fs-5 fw-black text-dark font-monospace">{{ number_format($apUnpaidSum, 2) }}</span>
                            <small class="text-muted d-block">บาท</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-dark">
                            <small class="text-muted fw-bold d-block">จำนวนบิลค้างชำระ</small>
                            <span class="fs-5 fw-black text-dark font-monospace">{{ number_format($apUnpaidCount) }}</span>
                            <small class="text-muted d-block">ใบ</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-primary">
                            <small class="text-muted fw-bold d-block">บริษัทคู่ค้าที่ค้างจ่าย</small>
                            <span class="fs-5 fw-black text-primary font-monospace">{{ number_format($apTotalVendorsCount) }}</span>
                            <small class="text-muted d-block">บริษัท</small>
                        </div>
                    </div>
                </div>

                <!-- Top Creditors Table -->
                <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                    <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center">
                        <strong class="text-dark small"><i class="bi bi-trophy-fill text-warning me-1"></i> เจ้าหนี้ที่มียอดค้างชำระสูงสุด 8 อันดับแรก</strong>
                        <span class="badge bg-danger-subtle text-danger border rounded-pill">Top Creditors</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-3" style="width: 40px;">#</th>
                                    <th>ชื่อบริษัทคู่ค้า / เจ้าหนี้</th>
                                    <th>หมวดหมู่</th>
                                    <th class="text-center">บิลค้างจ่าย</th>
                                    <th class="text-end pe-3">หนี้คงเหลือ (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($apTopCreditors as $idx => $creditor)
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                        <td class="fw-bold text-dark">{{ $creditor->vendor_name }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $creditor->category ?: 'ทั่วไป' }}</span></td>
                                        <td class="text-center font-monospace">{{ number_format($creditor->unpaid_bills) }} ใบ</td>
                                        <td class="text-end pe-3 font-monospace fw-bold text-danger">{{ number_format($creditor->remaining_debt, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">ไม่มีรายการหนี้สินค้างชำระ</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Executive Insight Box -->
                <div class="p-3 rounded-3 bg-white border" style="border-left: 4px solid #ef4444 !important; font-size: 0.82rem; line-height: 1.6;">
                    <strong class="text-danger d-block mb-1"><i class="bi bi-lightbulb-fill text-warning me-1"></i> ข้อเสนอแนะการบริหารหนี้สินสำหรับผู้บริหาร:</strong>
                    หนี้สินส่วนใหญ่กระจุกตัวในกลุ่มยาและเวชภัณฑ์หลัก แนะนำจัดลำดับจ่ายเช็คตาม Credit Term และยอดส่วนลดรับ เพื่อรักษาสภาพคล่องหมุนเวียน (Current Ratio) ไม่ให้ต่ำกว่าเกณฑ์วิกฤต
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                <a href="{{ url('hosfin/ap_report') }}" class="btn btn-danger btn-sm px-4 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right"></i> ดูรายงานเจ้าหนี้ & บิลทั้งหมด 52 บริษัท (หน้ารายงานเต็ม)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- AR Debtors Overview Modal -->
<div class="modal fade" id="hosfinArModal" tabindex="-1" aria-labelledby="hosfinArModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-wallet2 fs-5 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="hosfinArModalLabel">สรุปสถานะลูกหนี้ค่ารักษาพยาบาล (Accounts Receivable Overview)</h5>
                        <small class="text-white-50">ข้อมูลการตั้งเบิก ชดเชย และลูกหนี้ค้างท่อ ณ สิ้นงวด {{ $latestPeriodLabel }} แยกตามสิทธิกองทุนหลัก</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- 3 Highlights Top Strip -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-info">
                            <small class="text-muted fw-bold d-block">ลูกหนี้ค้างรับสุทธิ ณ สิ้นงวด ({{ $latestPeriodLabel }})</small>
                            <span class="fs-5 fw-black text-primary font-monospace">{{ number_format($arEndingBalance ?? $arOutstandingSum, 2) }}</span>
                            <small class="text-muted d-block">บาท (ตรงงบทดลอง)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-success">
                            <small class="text-muted fw-bold d-block">ยอดตั้งเบิกระหว่างปีนี้</small>
                            <span class="fs-5 fw-black text-success font-monospace">{{ number_format($arTotalBilled, 2) }}</span>
                            <small class="text-muted d-block">บาท (รวมยอดยกมา: {{ number_format((($arTotalBilled ?? 0) + ($arTotalOb ?? 0)) / 1000000, 2) }}M)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-warning">
                            <small class="text-muted fw-bold d-block">ชดเชยที่รับเงินแล้วปีนี้</small>
                            <span class="fs-5 fw-black text-dark font-monospace">{{ number_format($arTotalCollected, 2) }}</span>
                            <small class="text-muted d-block">บาท ({{ $arTotalBilled > 0 ? round(($arTotalCollected / $arTotalBilled) * 100, 1) : 0 }}% ของยอดตั้งเบิกปีนี้)</small>
                        </div>
                    </div>
                </div>

                <!-- Rights Group Breakdown Table -->
                <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                    <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center">
                        <strong class="text-dark small"><i class="bi bi-pie-chart-fill text-primary me-1"></i> ยอดลูกหนี้และสถานะชดเชยแยกตามสิทธิกองทุน</strong>
                        <span class="badge bg-primary-subtle text-primary border rounded-pill">Funds Breakdown</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-3">สิทธิกองทุนการรักษา</th>
                                    <th class="text-center">ผังบัญชี</th>
                                    <th class="text-end">ยอดยกมา OB (บาท)</th>
                                    <th class="text-end">ตั้งเบิกปีนี้ (บาท)</th>
                                    <th class="text-end">ชดเชยปีนี้ (บาท)</th>
                                    <th class="text-end pe-3 text-primary">ลูกหนี้คงค้างสุทธิ (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($arTypeSummaries as $ts)
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark">
                                            <i class="bi bi-tag-fill text-primary me-1"></i> {{ $ts->debtor_type ?: 'ทั่วไป' }}
                                        </td>
                                        <td class="text-center font-monospace">{{ $ts->account_count }}</td>
                                        <td class="text-end font-monospace text-muted">{{ number_format($ts->ob_balance, 2) }}</td>
                                        <td class="text-end font-monospace">{{ number_format($ts->total_billed, 2) }}</td>
                                        <td class="text-end font-monospace text-success">{{ number_format($ts->total_collected, 2) }}</td>
                                        <td class="text-end pe-3 font-monospace fw-bold {{ $ts->outstanding_balance > 0.01 ? 'text-primary' : 'text-muted' }}">
                                            {{ number_format($ts->outstanding_balance, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">ไม่มีข้อมูลลูกหนี้</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td class="ps-3">รวมทั้งหมด</td>
                                    <td class="text-center font-monospace">{{ $arAccountCount }}</td>
                                    <td class="text-end font-monospace text-muted">{{ number_format($arTotalOb ?? 0, 2) }}</td>
                                    <td class="text-end font-monospace">{{ number_format($arTotalBilled, 2) }}</td>
                                    <td class="text-end font-monospace text-success">{{ number_format($arTotalCollected, 2) }}</td>
                                    <td class="text-end pe-3 font-monospace text-primary fs-6">{{ number_format($arOutstandingSum, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Executive Insight Box -->
                <div class="p-3 rounded-3 bg-white border" style="border-left: 4px solid #0284c7 !important; font-size: 0.82rem; line-height: 1.6;">
                    <strong class="text-primary d-block mb-1"><i class="bi bi-lightbulb-fill text-warning me-1"></i> ข้อเสนอแนะการบริหารลูกหนี้สำหรับผู้บริหาร:</strong>
                    ควรเร่งติดตามการตัดหนี้สูญและ Reprocess ข้อมูลที่ติด C ของกองทุน สปสช. (UC) และข้าราชการ/อปท. เพื่อเร่งเงินชดเชยกลับเข้าสู่บัญชีเงินบำรุงโรงพยาบาลให้เร็วที่สุด
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                <a href="{{ url('hosfin/ar_report') }}" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right"></i> ดูรายงานลูกหนี้แยกตามผังบัญชี (หน้ารายงานเต็ม)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: รายละเอียดบัญชีเงินสดและเงินฝากธนาคาร (Cash & Bank Accounts) -->
<div class="modal fade" id="cashBankModal" tabindex="-1" aria-labelledby="cashBankModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #065f46 0%, #059669 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm bg-white" style="width: 44px; height: 44px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#059669" class="bi bi-cash-stack" viewBox="0 0 16 16">
                            <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                            <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="cashBankModalLabel">
                            สมุดบัญชีเงินสดและเงินฝากธนาคารทั้งหมด (Cash & Bank)
                        </h5>
                        <small class="text-white-50">ข้อมูลจากงบทดลอง GL ณ สิ้นงวด: {{ $latestPeriodLabel }} (ปีงบประมาณ {{ $budgetYear }})</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- KPI Highlight Banner inside Modal with Classification (Clickable Filters) -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 shadow-xs p-3 bg-white border-start border-4 border-success h-100 cash-filter-card"
                             id="filterCardOperating"
                             onclick="filterCashTable('operating')"
                             title="คลิกเพื่อกรองตารางแสดงเฉพาะเงินสดและรายการเทียบเท่าเงินสด (1003X)">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.74rem;">1. เงินสดและรายการเทียบเท่าเงินสด</span>
                                <span id="badgeFilterOperating" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                                    <i class="bi bi-funnel me-1"></i>คลิกกรอง
                                </span>
                            </div>
                            <div class="fs-5 fw-black text-success font-monospace mt-1">
                                {{ number_format($operatingCash ?? 0, 2) }} <span class="fs-6 fw-normal text-muted">บาท</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mt-1">
                                <small class="text-success fw-bold" style="font-size: 0.72rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i> สภาพคล่องพร้อมใช้ (คิด Cash Ratio & 105)
                                </small>
                                <span class="badge bg-success text-white rounded-pill px-2 py-0.5 shadow-xs fw-bold" style="font-size: 0.68rem;">
                                    <span class="spinner-grow spinner-grow-sm text-light me-1" style="width: 4px; height: 4px;" role="status"></span>
                                    Live เงินสด: {{ number_format($operatingCashLive ?? $operatingCash ?? 0, 2) }} บ.
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 shadow-xs p-3 bg-white border-start border-4 border-warning h-100 cash-filter-card"
                             id="filterCardRestricted"
                             onclick="filterCashTable('restricted')"
                             title="คลิกเพื่อกรองตารางแสดงเฉพาะเงินเฉพาะกิจ / งบลงทุน UC / บริจาค">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.74rem;">2. เฉพาะกิจ / งบลงทุน UC / บริจาค</span>
                                <span id="badgeFilterRestricted" class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                                    <i class="bi bi-funnel me-1"></i>คลิกกรอง
                                </span>
                            </div>
                            <div class="fs-5 fw-black text-dark font-monospace mt-1">
                                {{ number_format($restrictedCash ?? 0, 2) }} <span class="fs-6 fw-normal text-muted">บาท</span>
                            </div>
                            <small class="text-warning-emphasis fw-bold d-block mt-1" style="font-size: 0.72rem;">
                                <i class="bi bi-lock-fill me-1"></i> มีข้อผูกพันเฉพาะ กันออกตามเกณฑ์ สธ.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 shadow-xs p-3 bg-white border-start border-4 border-primary h-100 cash-filter-card"
                             id="filterCardAll"
                             onclick="filterCashTable('all')"
                             title="คลิกเพื่อแสดงบัญชีเงินสดและเงินฝากทั้งหมด">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.74rem;">3. รวมเงินสด & เงินฝากทุกเล่มใน GL</span>
                                <span id="badgeFilterAll" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i>แสดงทั้งหมด
                                </span>
                            </div>
                            <div class="fs-5 fw-black text-primary font-monospace mt-1">
                                {{ number_format($cashBalance ?? 0, 2) }} <span class="fs-6 fw-normal text-muted">บาท</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mt-1">
                                <small class="text-muted" style="font-size: 0.72rem;">
                                    ตรวจนับจริง {{ number_format($cashAccountsCount ?? 0) }} เล่ม (ณ ปิดงวด {{ $latestPeriodLabel }})
                                </small>
                                <span class="badge bg-primary text-white rounded-pill px-2.5 py-0.5 shadow-xs fw-bold" style="font-size: 0.70rem;">
                                    <span class="spinner-grow spinner-grow-sm text-light me-1" style="width: 5px; height: 5px;" role="status"></span>
                                    Live รวม: {{ number_format($cashLiveBalance ?? $cashBalance ?? 0, 2) }} บ.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table of Accounts -->
                <div class="card border-0 rounded-4 shadow-xs overflow-hidden bg-white mb-3 p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small w-100" id="cashAccountsTable">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-3 text-center" style="width: 45px;">#</th>
                                    <th>รหัสบัญชี</th>
                                    <th>ชื่อบัญชี / เลขที่บัญชีธนาคาร</th>
                                    <th class="text-center">ประเภทเงิน</th>
                                    <th class="text-end pe-3 text-success">ยอดคงเหลือ (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $cIdx = 1; @endphp
                                @forelse($cashBankAccounts as $ca)
                                    <tr>
                                        <td class="ps-3 text-muted fw-bold text-center" data-order="{{ $cIdx }}">{{ $cIdx++ }}</td>
                                        <td class="font-monospace fw-bold text-primary">{{ $ca->account_code }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $ca->account_name }}</div>
                                        </td>
                                        <td class="text-center" 
                                            data-order="{{ !empty($ca->is_restricted) ? '2_restricted' : '1_operating' }}"
                                            data-filter="{{ !empty($ca->is_restricted) ? 'restricted เฉพาะกิจ งบลงทุน บริจาค' : 'operating 1003X พร้อมใช้ เงินสด' }}"
                                            data-search="{{ !empty($ca->is_restricted) ? 'restricted เฉพาะกิจ งบลงทุน บริจาค' : 'operating 1003X พร้อมใช้ เงินสด' }}">
                                            @if(!empty($ca->is_restricted))
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 0.68rem;" title="เงินงบลงทุน UC หรือเงินบริจาคที่มีวัตถุประสงค์เฉพาะ">
                                                    <i class="bi bi-lock-fill me-1"></i> เฉพาะกิจ/งบลงทุน/บริจาค
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.68rem;" title="เงินสดและรายการเทียบเท่าเงินสดตามเกณฑ์ สธ. 1003X">
                                                    <i class="bi bi-check-circle-fill me-1"></i> สธ. 1003X (พร้อมใช้)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3 font-monospace fw-bold {{ $ca->net_balance > 0 ? 'text-success' : ($ca->net_balance < 0 ? 'text-danger' : 'text-muted') }}" data-order="{{ $ca->net_balance }}">
                                            {{ number_format($ca->net_balance, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูลบัญชีเงินสด</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light border-top border-2">
                                <tr class="fw-bold align-middle">
                                    <th colspan="4" class="ps-3 py-2.5 text-secondary" id="cashTableFooterLabel">
                                        <i class="bi bi-calculator me-1"></i> รวมยอดเงินสดและเงินฝากธนาคาร:
                                    </th>
                                    <th class="text-end pe-3 py-2.5 font-monospace text-success fs-6" id="cashTableFilteredTotal">
                                        {{ number_format($cashBalance ?? 0, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="p-3 rounded-3 bg-white border small text-muted" style="border-left: 4px solid #10b981 !important; line-height: 1.6;">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-info-circle-fill text-success me-1"></i> หมายเหตุการเงินตามเกณฑ์ สธ.:</strong>
                    ยอดเงินสดและเงินฝากธนาคารจริงใน GL รวม <strong>{{ number_format($cashBalance ?? 0, 2) }} บาท</strong> ({{ number_format($cashAccountsCount ?? 0) }} เล่มบัญชี) แบ่งเป็น <strong>เงินสดพร้อมใช้ตามเกณฑ์กระทรวง (กลุ่ม 1003X) {{ number_format($operatingCash ?? 0, 2) }} บาท</strong> ซึ่งนำไปคำนวณในสูตร Cash Ratio (102) และเงินบำรุงคงเหลือสุทธิ (105) และ <strong>เงินเฉพาะกิจ/งบลงทุน UC/บริจาค {{ number_format($restrictedCash ?? 0, 2) }} บาท</strong> ซึ่งกันไว้ไม่นำมารวมเป็นสภาพคล่องทั่วไปตามระเบียบเงินบำรุงของกระทรวงสาธารณสุข
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                <a href="{{ url('hosfin/trial_balance') }}" class="btn btn-success btn-sm px-4 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet"></i> ดูงบทดลองแบบเต็ม (Trial Balance)
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>

    let cashTableInstance = null;
    let currentCashFilter = 'all';

    function initCashDataTable() {
        if (typeof $ === 'undefined' || !$.fn.DataTable) return;
        const tableEl = $('#cashAccountsTable');
        if (!tableEl.length) return;

        if ($.fn.DataTable.isDataTable('#cashAccountsTable')) {
            cashTableInstance = tableEl.DataTable();
            cashTableInstance.columns.adjust();
            return;
        }

        cashTableInstance = tableEl.DataTable({
            language: {
                search: "",
                searchPlaceholder: "🔍 ค้นหารหัส, ชื่อบัญชี, ธนาคาร, ประเภท...",
                lengthMenu: "แสดง _MENU_ เล่ม",
                info: "แสดง _START_ - _END_ จากทั้งหมด _TOTAL_ เล่มบัญชี",
                infoEmpty: "ไม่พบข้อมูลเล่มบัญชี",
                infoFiltered: "(กรองจากทั้งหมด _MAX_ เล่ม)",
                zeroRecords: "ไม่พบบัญชีเงินสดที่ตรงกับคำค้นหา",
                paginate: {
                    first: "«",
                    previous: "‹",
                    next: "›",
                    last: "»"
                }
            },
            order: [], // Preserve original balance descending order
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "ทั้งหมด"]],
            autoWidth: false,
            dom: "<'row mb-3 align-items-center'<'col-6'l><'col-6 d-flex justify-content-end'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row mt-3 align-items-center g-2'<'col-md-5'i><'col-md-7 d-flex justify-content-end'p>>",
            columnDefs: [
                { targets: 0, orderable: false, width: '45px' },
                { targets: 1, width: '140px' },
                { targets: 3, width: '165px' },
                { targets: 4, width: '150px' }
            ],
            footerCallback: function (row, data, start, end, display) {
                const api = this.api();
                const intVal = function (i) {
                    if (typeof i === 'number') return i;
                    if (typeof i === 'string') {
                        return parseFloat(i.replace(/[\$,]/g, '')) || 0;
                    }
                    return 0;
                };

                let total = 0;
                api.column(4, { search: 'applied' }).nodes().each(function (cell) {
                    const raw = $(cell).attr('data-order') !== undefined ? $(cell).attr('data-order') : $(cell).text();
                    total += intVal(raw);
                });

                $('#cashTableFilteredTotal').text(
                    new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(total)
                );
            }
        });
    }

    function filterCashTable(group) {
        currentCashFilter = group || 'all';

        const cardOperating = $('#filterCardOperating');
        const cardRestricted = $('#filterCardRestricted');
        const cardAll = $('#filterCardAll');

        cardOperating.removeClass('active-operating');
        cardRestricted.removeClass('active-restricted');
        cardAll.removeClass('active-all');

        $('#badgeFilterOperating').html('<i class="bi bi-funnel me-1"></i>คลิกกรอง')
            .attr('class', 'badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5');
        $('#badgeFilterRestricted').html('<i class="bi bi-funnel me-1"></i>คลิกกรอง')
            .attr('class', 'badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2 py-0.5');
        $('#badgeFilterAll').html('<i class="bi bi-arrow-repeat me-1"></i>แสดงทั้งหมด')
            .attr('class', 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5');

        if (currentCashFilter === 'operating') {
            cardOperating.addClass('active-operating');
            $('#badgeFilterOperating').html('<i class="bi bi-check-circle-fill me-1"></i>กำลังแสดงกลุ่มนี้')
                .attr('class', 'badge bg-success text-white rounded-pill px-2 py-0.5 shadow-xs');
            $('#cashTableFooterLabel').html('<i class="bi bi-calculator me-1"></i> รวมยอดเงินสดและรายการเทียบเท่าเงินสด (1003X):');
        } else if (currentCashFilter === 'restricted') {
            cardRestricted.addClass('active-restricted');
            $('#badgeFilterRestricted').html('<i class="bi bi-check-circle-fill me-1"></i>กำลังแสดงกลุ่มนี้')
                .attr('class', 'badge bg-warning text-dark rounded-pill px-2 py-0.5 shadow-xs');
            $('#cashTableFooterLabel').html('<i class="bi bi-calculator me-1"></i> รวมยอดเงินเฉพาะกิจ / งบลงทุน UC / บริจาค:');
        } else {
            cardAll.addClass('active-all');
            $('#badgeFilterAll').html('<i class="bi bi-check-circle-fill me-1"></i>กำลังแสดงทั้งหมด')
                .attr('class', 'badge bg-primary text-white rounded-pill px-2 py-0.5 shadow-xs');
            $('#cashTableFooterLabel').html('<i class="bi bi-calculator me-1"></i> รวมยอดเงินสดและเงินฝากธนาคารทั้งหมด:');
        }

        if (!cashTableInstance) {
            initCashDataTable();
        }

        if (cashTableInstance) {
            if (currentCashFilter === 'operating') {
                cashTableInstance.column(3).search('operating').draw();
            } else if (currentCashFilter === 'restricted') {
                cashTableInstance.column(3).search('restricted').draw();
            } else {
                cashTableInstance.column(3).search('').draw();
            }
            cashTableInstance.columns.adjust();
        }
    }

    function openCashModal(group = 'all') {
        currentCashFilter = group || 'all';
        const el = document.getElementById('cashBankModal');
        if (el) {
            if (typeof $ !== 'undefined' && typeof $(el).modal === 'function') {
                $(el).modal('show');
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
            }
            setTimeout(function() {
                filterCashTable(currentCashFilter);
            }, 100);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const cashModalEl = document.getElementById('cashBankModal');
        if (cashModalEl) {
            cashModalEl.addEventListener('shown.bs.modal', function () {
                if (!cashTableInstance) {
                    initCashDataTable();
                }
                filterCashTable(currentCashFilter);
                if (cashTableInstance) {
                    cashTableInstance.columns.adjust();
                }
            });
        }
    });
    function openApModal() {
        const el = document.getElementById('hosfinApModal');
        if (el) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
            } else if (typeof $ !== 'undefined' && typeof $(el).modal === 'function') {
                $(el).modal('show');
            }
        }
    }
    function openArModal() {
        const el = document.getElementById('hosfinArModal');
        if (el) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
            } else if (typeof $ !== 'undefined' && typeof $(el).modal === 'function') {
                $(el).modal('show');
            }
        }
    }

    // Injected variables
    const chartLabels = @json($chartLabels);
    const chartData = @json($chartData);
    const ratioDefs = @json($ratioDefs);
    const statusMap = @json($statusMap);
    const latestMetrics = @json($latestMetrics);
    const periodHistory = @json($periodHistory ?? []);
    const selectedPeriodLabel = @json($latestPeriodLabel ?? '');

    // Revenue/Expense trend chart setup
    @if(isset($monthlyRevenueExpenseTrend) && count($monthlyRevenueExpenseTrend) > 0)
    document.addEventListener("DOMContentLoaded", () => {
        const trendMap = @json($monthlyRevenueExpenseTrend);
        const trendLabels = Object.keys(trendMap);
        const revenuesData = trendLabels.map(label => trendMap[label].revenue || 0.0);
        const expensesData = trendLabels.map(label => trendMap[label].expense || 0.0);

        const trendChartOptions = {
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
                    opacityFrom: 0.3,
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
                categories: trendLabels,
                tooltip: {
                    enabled: true
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

        const trendChart = new ApexCharts(document.querySelector("#categoryTrendChart"), trendChartOptions);
        trendChart.render();
    });
    @endif
    
    // Executive Guides Lookup
    const analysisGuides = {
        '105': {
            desc: '<strong>เงินบำรุงคงเหลือสุทธิ (หักหนี้แล้ว)</strong>: ประเมินระดับวิกฤตทางการเงินระดับ 7 ของกระทรวงสาธารณสุข โดยแสดงค่าจริงเป็นบาท',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> หากค่าเงินบำรุงสุทธิติดลบหรือลดลงต่อเนื่องในแต่ละเดือน แสดงว่าโรงพยาบาลมีสภาวะความเสี่ยงทางการเงินสูง (ระดับ 7) ผู้บริหารควรจัดตั้งคณะกรรมการเพื่อควบคุมและลดรายจ่ายที่ไม่จำเป็นในทันที และมอบหมายทีมประกันสุขภาพให้ส่งข้อมูลเบิกเคลมในสิทธิ UC/CSMBS บ่อยขึ้นเพื่อเร่งเรียกรับเงินสดกลับคืนโรงพยาบาล'
        },
        '100': {
            desc: '<strong>Current Ratio (อัตราส่วนสภาพคล่องหมุนเวียน)</strong>: สินทรัพย์หมุนเวียน ÷ หนี้สินหมุนเวียน (วัดความมั่นคงรวมในระยะสั้น)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควบคุมอัตราส่วนนี้ให้สูงกว่า <strong>1.5 เท่า</strong> หากตัวเลขต่ำกว่า 1.0 เท่า แสดงว่าโรงพยาบาลมีแนวโน้มสินทรัพย์ไม่พอจ่ายหนี้ระยะสั้นใน 1 ปี ควรเจรจาขอยืดการจ่ายชำระเจ้าหนี้ค่ายาเวชภัณฑ์ และระงับโครงการลงทุนจัดซื้อครุภัณฑ์ใหม่ ๆ ที่ยังไม่จำเป็นเร่งด่วนออกไปก่อน'
        },
        '101': {
            desc: '<strong>Quick Ratio (อัตราส่วนสภาพคล่องเร่งด่วน)</strong>: (เงินสด + ลูกหนี้) ÷ หนี้สินหมุนเวียน (หักผลกระทบความหน่วงของคลังยาออก)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควรมีสัดส่วนมากกว่า <strong>1.0 เท่า</strong> หากตัวเลขตกเกณฑ์ แสดงว่างบหมุนเวียนไปจมอยู่ในรูปของสต็อกของในคลังวัสดุ หรือเป็นลูกหนี้ที่ยังเรียกเก็บเงินสดไม่ได้ ผู้บริหารควรเร่งรัดฝ่ายจัดเก็บรายได้ให้ติดตามการเคลมยอดค้าง และสั่งห้องยาให้ปรับเกณฑ์การบริหารคลังให้กระชับขึ้น'
        },
        '102': {
            desc: '<strong>Cash Ratio (อัตราส่วนเงินสดพร้อมจ่าย)</strong>: เงินสดและเงินฝากธนาคาร ÷ หนี้สินหมุนเวียน (วัดความพร้อมจ่ายทันทีถ้าโดนทวงหนี้)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ตัวเลขควรอยู่ในระดับ <strong>0.8 เท่าขึ้นไป</strong> หากต่ำกว่า 0.8 เท่า แสดงว่าเงินสดในมือของ รพ. มีน้อยมาก หากเกิดเหตุฉุกเฉินหรือคู่ค้ามาเรียกชำระพร้อมกัน อาจทำให้ รพ. ขาดสภาพคล่องกะทันหัน ควรเพิ่มวินัยการสำรองสัดส่วนเงินสดฝากธนาคารให้อยู่ในเกณฑ์มาตรฐาน'
        },
        '264': {
            desc: '<strong>การบริหารสินคงคลัง (Inventory Management)</strong>: วัสดุคงคลังเฉลี่ย ÷ วัสดุใช้ไป (คูณ 300 วัน เพื่อดูระยะเวลาเป็นวัน)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควบคุมให้อยู่ในช่วง <strong>30-45 วัน</strong> หากจำนวนวันเฉลี่ยสูงเกิน 45-60 วัน แปลว่า รพ. สั่งซื้อยาหรือวัสดุการแพทย์มาดองไว้มากเกินจำเป็น ทำให้เงินสดหมุนเวียนไปจมในคลังและเสี่ยงต่อยาหมดอายุ ผู้บริหารควรสั่งการห้องยา/พัสดุให้ปรับลดปริมาณสำรองวัสดุยา (Min Stock) เพื่อระบายเงินสดออกมาหมุนเวียน'
        },
        '261': {
            desc: '<strong>ระยะเวลาถัวเฉลี่ยในการเรียกเก็บหนี้สิทธิ UC (Average Collection Period - UC)</strong>: ลูกหนี้ UC เฉลี่ย ÷ รายได้ UC สุทธิ (คูณ 300 วัน เพื่อดูความเร็วการตามเงินจาก สปสช.)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควบคุมให้ต่ำกว่า <strong>60 วัน</strong> (เกณฑ์ สธ.) หากตัวเลขพุ่งสูง 60-90 วันขึ้นไป สะท้อนว่าหน่วยประกันสุขภาพของ รพ. ส่งเบิกเคลมช้า หรือมีปัญหาเคลมล่าช้า ผู้บริหารควรสั่งการให้หน่วยเบิกเคลมประกันเร่งส่งข้อมูลและเคลียร์เคสที่ติดขัดโดยด่วน'
        },
        '262': {
            desc: '<strong>ระยะเวลาถัวเฉลี่ยในการเรียกเก็บหนี้สิทธิข้าราชการ (Average Collection Period - CSMBS)</strong>: ลูกหนี้ CS เฉลี่ย ÷ รายได้ CS สุทธิ (คูณ 300 วัน เพื่อดูความเร็วการตามเงินจ่ายตรงกรมบัญชีกลาง)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควบคุมให้ต่ำกว่า <strong>60 วัน</strong> (เกณฑ์ สธ.) สิทธิข้าราชการจ่ายตรงควรเก็บเงินได้เร็ว หากตัวเลขสูงเกิน 60 วัน แสดงว่าการเงิน รพ. ล่าช้าในการส่งชุดข้อมูลเบิกจ่าย หรือบันทึกเข้าระบบจ่ายตรงของกรมบัญชีกลาง ควรส่งทีมไอทีตรวจสอบปัญหารหัสเบิกจ่ายสิทธิทางออนไลน์'
        },
        '260': {
            desc: '<strong>ระยะเวลาชำระเจ้าหนี้การค้ายา&เวชภัณฑ์มิใช่ยา (Average Payment Period)</strong>: เจ้าหนี้การค้าเฉลี่ย ÷ เจ้าหนี้รวม (คูณ 300 วัน)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> เกณฑ์มาตรฐาน **90-120 วัน** หากตัวเลขพุ่งเกิน 150-180 วัน สะท้อนว่าสภาพคล่องเงินสดของ รพ. ตึงตัวจนต้องดึงการจ่ายหนี้คู่ค้าออกไปยาวนาน ผู้บริหารควรตรวจสอบภาพรวมรายรับของ รพ. และตั้งแผนเจรจาขอยืดจ่ายอย่างเป็นระบบเพื่อไม่ให้โดนบริษัทยาระงับจัดส่งยาสำคัญ'
        },
        '320': {
            desc: '<strong>Operating Margin % (EBITDA)</strong>: EBITDA ÷ รายได้จากการรักษา/งบบุคลากร/กองทุน (คูณ 100% เพื่อดูความสามารถทำกำไรที่เป็นเม็ดเงินสดจริง)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ตัวเลขควรเป็นบวกเพื่อชี้วัดว่างบรายรับรักษาพยาบาลครอบคลุมต้นทุนจริงทางการดำเนินงาน หากติดลบแปลว่า รพ. ต้องเร่งลดรายจ่ายส่วนโสหุ้ย/ค่าล่วงเวลา หรือวิเคราะห์ประสิทธิภาพการลดความสูญเสียในแต่ละแผนก'
        },
        '321': {
            desc: '<strong>Return on Asset % (ROA)</strong>: รายได้สูง(ต่ำ)กว่าค่าใช้จ่ายสุทธิ ÷ สินทรัพย์รวม (คูณ 100% เพื่อวัดประสิทธิภาพการใช้ทรัพย์สิน)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ตัวเลขควรเป็นบวก หากเป็นลบแปลว่า รพ. มีทรัพย์สินและเครื่องมือแพทย์มากแต่ไม่สามารถสร้างสัดส่วนรายได้ที่คุ้มทุน ควรทบทวนความคุ้มค่าของการลงทุนซื้อสิ่งก่อสร้างหรือครุภัณฑ์ชิ้นใหม่เพิ่มเติม'
        },
        '307': {
            desc: '<strong>Net Profit Margin % (อัตรากำไรสุทธิมีค่าเสื่อม)</strong>: กำไรสุทธิ (มีค่าเสื่อมฯ) ÷ รายได้รวม (คูณ 100% เพื่อดูผลกำไรทางบัญชีอย่างเป็นทางการ)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> หากตัวนี้ติดลบแต่ตัว EBITDA (รหัส 320) เป็นบวก แสดงว่า รพ. ขาดทุนเฉพาะทางบัญชีจากค่าเสื่อมราคาการใช้สิ่งก่อสร้างและครุภัณฑ์ ซึ่งยังไม่น่าวิตกในระยะสั้น แต่ควรระวังและวางแผนสะสมงบเงินบำรุงระยะยาวเพื่อรอการลงทุนจัดซื้อเครื่องมือทดแทนของเดิมที่จะเสื่อมสภาพ'
        },
        '334': {
            desc: '<strong>NI+Depreciation (กำไรสุทธิบวกค่าเสื่อมราคา)</strong>: รายได้สูง(ต่ำ)กว่าค่าใช้จ่ายสุทธิสะสม ชี้วัดกระแสเงินสดสุทธิจากการดำเนินงาน',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> บ่งบอกศักยภาพการทำกำไรที่แท้จริงบวกกระแสเงินสดค่าเสื่อมที่สำรองไว้ในระบบ หากค่าตัวนี้เป็นบวกในอัตราที่สูง แสดงว่า รพ. มีศักยภาพและความพร้อมในการขยายงานหรือจัดซื้อทดแทนเครื่องมือแพทย์ดั้งเดิม'
        },
        '105': {
            desc: '<strong>เงินบำรุงคงเหลือสุทธิ (Net Cash Balance)</strong>: ยอดเงินบำรุงคงเหลือในบัญชีเงินฝากและเงินสดลบภาระผูกพันทางการเงิน',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ยอดเงินควรเป็นบวกและเพียงพอต่อการรองรับค่าใช้จ่ายดำเนินงานของโรงพยาบาล หากติดลบแปลว่าสภาพคล่องเงินสดจริงกำลังตึงตัวอย่างรุนแรง'
        },
        'RISK_SCORE': {
            desc: '<strong>RISK SCORE (ระดับความเสี่ยงทางการเงิน)</strong>: คะแนนประเมินความเสี่ยงทางการเงินโดยรวมจาก 0 ถึง 7 คะแนน ตามเกณฑ์ของกระทรวงสาธารณสุข',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> คะแนนยิ่งน้อยยิ่งดี (เป้าหมายคือ 0-2 คะแนน) หากคะแนนสูงเกิน 5 คะแนนขึ้นไป ถือว่าเป็นสถานะวิกฤตทางการเงินที่ต้องกำหนดแผนเผชิญเหตุและควบคุมรายจ่ายอย่างเคร่งครัด'
        }
    };

    let activeChart = null;

    // Handle Card Click Events
    document.querySelectorAll('.metric-card').forEach(card => {
        card.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            if (!code || typeof latestMetrics === 'undefined' || !latestMetrics[code]) {
                return;
            }
            
            // Get latest month data
            const definition = Object.assign({}, ratioDefs[code] || { numerator_name: '-', denominator_name: '-', unit: '', precision: 2 });
            if (code === 'RISK_SCORE') {
                definition.numerator_name = 'คะแนนความเสี่ยงที่ได้';
                definition.denominator_name = 'เกณฑ์ประเมินคะแนนเต็ม';
                definition.unit = 'คะแนน';
                definition.precision = 0;
            }

            const numVal = (latestMetrics[code]['num'] !== undefined) ? latestMetrics[code]['num'] : 0;
            const denVal = (latestMetrics[code]['den'] !== undefined) ? latestMetrics[code]['den'] : 0;
            const resVal = (latestMetrics[code]['val'] !== undefined) ? latestMetrics[code]['val'] : 0;

            // Set modal labels and values
            document.getElementById('trendModalLabel').innerHTML = `<i class="bi bi-graph-up me-2 text-warning"></i> แนวโน้มรายงวดบัญชี: ${name}`;
            const breakdownTitleEl = document.getElementById('modalBreakdownPeriodTitle');
            if (breakdownTitleEl) {
                breakdownTitleEl.textContent = `รายละเอียดที่มาของตัวเลขประจำงวด ${selectedPeriodLabel}`;
            }
            
            // Populate Numerator / Denominator info
            document.getElementById('modalNumLabel').textContent = definition.numerator_name;
            document.getElementById('modalNumValue').textContent = numVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            document.getElementById('modalDenLabel').textContent = definition.denominator_name;
            document.getElementById('modalDenValue').textContent = denVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // Dynamic color evaluation based on statusMap
            const status = statusMap[code] || { class: 'text-dark border-secondary' };
            let colorClass = 'text-dark';
            let borderColor = '#cbd5e1'; // gray fallback
            
            if (status.class.includes('text-success')) {
                colorClass = 'text-success-custom';
                borderColor = '#16a34a';
            } else if (status.class.includes('text-danger')) {
                colorClass = 'text-danger-custom';
                borderColor = '#b91c1c';
            } else if (status.class.includes('text-warning')) {
                colorClass = 'text-warning-custom';
                borderColor = '#b45309';
            }

            const resultValEl = document.getElementById('modalResultValue');
            resultValEl.className = `fs-5 fw-bold ${colorClass}`;
            resultValEl.textContent = resVal.toLocaleString(undefined, {minimumFractionDigits: definition.precision, maximumFractionDigits: definition.precision}) + ' ' + definition.unit;

            // Set guide descriptions
            const guide = analysisGuides[code] || { desc: 'ไม่มีคำอธิบายสำหรับรหัสนี้', guide: 'ไม่มีคำแนะนำเพิ่มเติม' };
            document.getElementById('modalGuideDescription').innerHTML = guide.desc;
            
            const guideActionEl = document.getElementById('modalGuideAction');
            guideActionEl.style.borderLeft = `4px solid ${borderColor}`;
            guideActionEl.innerHTML = guide.guide;

            // Open Modal via jQuery (Standard for project)
            $('#trendModal').modal('show');

            // Render Chart inside modal
            setTimeout(() => {
                const ctx = document.getElementById('modalTrendChart').getContext('2d');
                
                // Destroy old chart if exists
                if (activeChart) {
                    activeChart.destroy();
                }

                const datasetValues = chartData[code] || [];
                
                let yScaleOpts = {
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' ' + definition.unit;
                        }
                    }
                };

                if (code === 'RISK_SCORE') {
                    yScaleOpts.min = 0;
                    yScaleOpts.max = 8;
                } else {
                    yScaleOpts.grace = '15%';
                }

                activeChart = new Chart(ctx, {
                    type: 'line',
                    plugins: [ChartDataLabels],
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: name,
                            data: datasetValues,
                            borderColor: '#0284c7',
                            backgroundColor: 'rgba(2, 132, 199, 0.1)',
                            borderWidth: 3,
                            pointBackgroundColor: '#0284c7',
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        onClick: (event, elements) => {
                            if (elements && elements.length > 0) {
                                const index = elements[0].index;
                                const clickedMonth = chartLabels[index];
                                if (clickedMonth && periodHistory[code] && periodHistory[code][clickedMonth]) {
                                    const mData = periodHistory[code][clickedMonth];
                                    const mNum = mData.num !== undefined ? mData.num : 0;
                                    const mDen = mData.den !== undefined ? mData.den : 0;
                                    const mVal = mData.val !== undefined ? mData.val : 0;

                                    if (breakdownTitleEl) {
                                        breakdownTitleEl.textContent = `รายละเอียดที่มาของตัวเลขประจำงวด ${clickedMonth}`;
                                    }
                                    document.getElementById('modalNumValue').textContent = mNum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    document.getElementById('modalDenValue').textContent = mDen.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    resultValEl.textContent = mVal.toLocaleString(undefined, {
                                        minimumFractionDigits: definition.precision, 
                                        maximumFractionDigits: definition.precision
                                    }) + ' ' + definition.unit;
                                }
                            }
                        },
                        onHover: (event, chartElement) => {
                            const target = event.native ? event.native.target : (event.chart ? event.chart.canvas : null);
                            if (target) {
                                target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return value.toLocaleString(undefined, {
                                        minimumFractionDigits: definition.precision, 
                                        maximumFractionDigits: definition.precision
                                    }) + ' ' + definition.unit;
                                },
                                font: {
                                    weight: 'bold',
                                    size: 10
                                },
                                color: '#475569',
                                offset: 4
                            }
                        },
                        scales: {
                            y: yScaleOpts
                        }
                    }
                });
            }, 250); // Small timeout to ensure canvas is fully rendered in DOM
        });
    });

    // Helper function to escape HTML
    function escapeHosFinHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Helper Markdown renderer with LaTeX and Color support
    function formatHosFinAiMarkdown(raw) {
        if (!raw) return '';

        // 1. Convert LaTeX math block $$ \text{...} $$
        let res = raw.replace(/\$\$([\s\S]*?)\$\$/g, function(match, formula) {
            let clean = formula
                .replace(/\\text\{([^}]+)\}/g, '$1')
                .replace(/\\quad/g, ' ')
                .replace(/\\times/g, ' × ')
                .replace(/\\div/g, ' ÷ ')
                .replace(/\\approx/g, ' ≈ ')
                .replace(/\\le/g, ' ≤ ')
                .replace(/\\ge/g, ' ≥ ')
                .trim();
            return `<div class="p-2 my-2 bg-light border border-primary-subtle rounded-3 text-center fw-bold text-primary font-monospace small"><i class="bi bi-calculator me-1"></i> ${clean}</div>`;
        });

        // 2. Convert inline LaTeX math $ \text{...} $
        res = res.replace(/\$([^\$\n]+)\$/g, function(match, formula) {
            return formula.replace(/\\text\{([^}]+)\}/g, '$1').trim();
        });

        // 3. Support font colors
        res = res
            .replace(/<font\s+color=['"]?red['"]?>(.*?)<\/font>/gi, '<span class="text-danger fw-bold">$1</span>')
            .replace(/<font\s+color=['"]?green['"]?>(.*?)<\/font>/gi, '<span class="text-success fw-bold">$1</span>')
            .replace(/<font\s+color=['"]?blue['"]?>(.*?)<\/font>/gi, '<span class="text-primary fw-bold">$1</span>')
            .replace(/<font[^>]*>/gi, '')
            .replace(/<\/font>/gi, '');

        // 4. Standard Markdown
        return res
            .replace(/^### (.*$)/gim, '<h6 class="fw-bold text-dark mt-3 mb-2 border-bottom pb-1"><i class="bi bi-caret-right-fill text-primary me-1"></i> $1</h6>')
            .replace(/^#### (.*$)/gim, '<h6 class="fw-bold text-secondary mt-2 mb-1" style="font-size: 0.9rem;">$1</h6>')
            .replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/gim, '<em>$1</em>')
            .replace(/`([^`]+)`/gim, '<code class="bg-light px-1 py-0.5 rounded text-dark border small">$1</code>')
            .replace(/^\* (.*$)/gim, '<li class="mb-1 ms-3">$1</li>')
            .replace(/^- (.*$)/gim, '<li class="mb-1 ms-3">$1</li>')
            .replace(/\n\n/gim, '<br>')
            .replace(/\n/gim, '<br>');
    }

    // AI Financial Diagnosis Modal Logic
    function openHosFinAiModal() {
        const periodSelect = document.querySelector('select[onchange*="period="]');
        let currentPeriod = periodSelect ? periodSelect.value : (new URLSearchParams(window.location.search).get('period'));

        $('#hosFinAiModal').modal('show');
        if (!window.hosFinAnalysisLoaded || window.hosFinLoadedPeriod !== currentPeriod) {
            fetchHosFinAiAnalysis(currentPeriod);
        }
    }

    function fetchHosFinAiAnalysis(forcePeriod = null) {
        let currentPeriod = forcePeriod;
        if (!currentPeriod) {
            const periodSelect = document.querySelector('select[onchange*="period="]');
            currentPeriod = periodSelect ? periodSelect.value : (new URLSearchParams(window.location.search).get('period'));
        }

        const isAdmin = {{ (Auth::check() && Auth::user()->status === 'admin') ? 'true' : 'false' }};
        const loading = document.getElementById('aiAnalysisLoading');
        const content = document.getElementById('aiAnalysisContent');
        const errBox = document.getElementById('aiAnalysisError');

        loading.classList.remove('d-none');
        content.classList.add('d-none');
        errBox.classList.add('d-none');

        fetch(`{{ route('hosfin.ai_analyze') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ period: currentPeriod })
        })
        .then(res => res.json())
        .then(data => {
            loading.classList.add('d-none');
            if (data.success && data.answer) {
                window.hosFinAnalysisLoaded = true;
                window.hosFinLoadedPeriod = currentPeriod;
                content.classList.remove('d-none');

                // Update Snapshot card dynamically if snapshot data provided
                if (data.snapshot) {
                    window.currentHosFinAiContext = data.snapshot;
                    const snap = data.snapshot;
                    if (document.getElementById('hosFinSnapPeriod')) document.getElementById('hosFinSnapPeriod').innerText = snap.periodLabel || snap.period || '-';
                    if (document.getElementById('hosFinSnapYear')) document.getElementById('hosFinSnapYear').innerText = `ปีงบ ${snap.budgetYear || ''}`;
                    if (document.getElementById('hosFinSnapRiskScore')) document.getElementById('hosFinSnapRiskScore').innerText = `ระดับ ${snap.riskScore || 0} / 7`;
                    if (document.getElementById('hosFinSnapRiskBadge')) document.getElementById('hosFinSnapRiskBadge').innerText = snap.riskScoreLabel || '';
                    if (document.getElementById('hosFinSnapFund')) {
                        const fundEl = document.getElementById('hosFinSnapFund');
                        const fundVal = parseFloat(snap.netOperatingFund || 0);
                        fundEl.className = `fw-bold ${fundVal < 0 ? 'text-danger' : 'text-success'} mb-0 mt-1`;
                        fundEl.innerHTML = `${fundVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} <span class="fs-6 fw-normal">บาท</span>`;
                    }
                    if (document.getElementById('hosFinSnapRatios')) {
                        document.getElementById('hosFinSnapRatios').innerText = `Current Ratio: ${snap.currentRatio || 0} | Cash: ${snap.cashRatio || 0}`;
                    }
                    if (document.getElementById('hosFinSnapDrugPayDays')) {
                        document.getElementById('hosFinSnapDrugPayDays').innerHTML = `<i class="bi bi-clock-history me-1"></i> จ่ายค่ายา: ${snap.drugPayDays || 0} วัน`;
                    }
                    if (document.getElementById('hosFinSnapOfcCollectDays')) {
                        document.getElementById('hosFinSnapOfcCollectDays').innerHTML = `<i class="bi bi-receipt me-1"></i> เก็บหนี้ข้าราชการ: ${snap.ofcCollectDays || 0} วัน`;
                    }
                    if (document.getElementById('hosFinSnapAp')) {
                        const apVal = parseFloat(snap.totalUnpaidAp || 0);
                        document.getElementById('hosFinSnapAp').innerText = `${apVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} บาท`;
                    }
                    if (document.getElementById('hosFinSnapAr')) {
                        const arVal = parseFloat(snap.totalArOutstanding || 0);
                        document.getElementById('hosFinSnapAr').innerText = `${arVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} บาท`;
                    }
                    if (document.getElementById('hosFinSnapCash')) {
                        const cashVal = parseFloat(snap.totalCash || 0);
                        document.getElementById('hosFinSnapCash').innerText = `${cashVal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} บาท`;
                    }
                }

                // Header badge showing provider & model
                const providerBadge = data.provider_label ? `<div class="mb-3"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-1 px-2"><i class="bi bi-stars me-1"></i> ขับเคลื่อนด้วย ${data.provider_label} (${data.model || ''})</span></div>` : '';
                document.getElementById('aiAnalysisText').innerHTML = providerBadge + formatHosFinAiMarkdown(data.answer);

                // Render sources
                const srcContainer = document.getElementById('aiAnalysisSources');
                if (data.sources && data.sources.length > 0) {
                    let srcHtml = '<div class="mt-3 pt-3 border-top"><small class="fw-bold text-muted d-block mb-1"><i class="bi bi-bookmark-check-fill text-success me-1"></i> ฐานข้อมูล GL อ้างอิง:</small><div class="d-flex flex-wrap gap-2">';
                    data.sources.forEach(s => {
                        srcHtml += `<span class="badge bg-light text-dark border small" title="${s.snippet || ''}"><i class="bi bi-database-check text-primary me-1"></i>${s.title}</span>`;
                    });
                    srcHtml += '</div></div>';
                    srcContainer.innerHTML = srcHtml;
                } else {
                    srcContainer.innerHTML = '';
                }
            } else {
                errBox.classList.remove('d-none');
                const providerLabel = data.provider_label || 'ยังไม่ได้ระบุ';
                const errMsg = data.message || 'ไม่สามารถเชื่อมต่อผู้ให้บริการ AI ได้';
                const settingsUrl = data.settings_url || '{{ route('admin.rag.index') }}';

                errBox.innerHTML = `
                    <div class="card border-warning shadow-sm rounded-3 overflow-hidden bg-white">
                        <div class="card-header bg-warning bg-opacity-10 text-dark border-warning border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-4"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">ระบบ AI ยังไม่พร้อมใช้งาน หรือยังไม่ได้ตั้งค่าการเชื่อมต่อ</h6>
                                    <small class="text-muted">ผู้ให้บริการปัจจุบัน: <span class="badge bg-secondary text-white">${providerLabel}</span></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-danger py-2 px-3 small mb-3 border-0 bg-danger bg-opacity-10 text-danger">
                                <strong><i class="bi bi-x-circle me-1"></i> สาเหตุ:</strong> ${errMsg}
                            </div>
                            <p class="text-muted small mb-3" style="line-height: 1.6;">
                                ฟังก์ชันนี้ขับเคลื่อนด้วยระบบปัญญาประดิษฐ์ (Generative AI) 100% เพื่อประมวลผลเชิงกลยุทธ์ โดยระบบ RiMS รองรับ AI ผู้ให้บริการหลากหลายค่าย:
                            </p>
                            <div class="row g-2 mb-4">
                                <div class="col-md-4">
                                    <div class="border rounded p-2 bg-light small h-100">
                                        <strong class="d-block text-primary"><i class="bi bi-google me-1"></i> Google Gemini</strong>
                                        <span class="text-muted" style="font-size: 0.8rem;">เร็ว ใช้งานง่าย ฟรีโควตา (เชื่อมต่อด้วย API Key)</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 bg-light small h-100">
                                        <strong class="d-block text-success"><i class="bi bi-hdd-network me-1"></i> Ollama (Local)</strong>
                                        <span class="text-muted" style="font-size: 0.8rem;">รันบนเซิร์ฟเวอร์ใน รพ. ปลอดภัยสูงสุด ออฟไลน์ได้ (เช่น Typhoon, Llama 3)</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 bg-light small h-100">
                                        <strong class="d-block text-info"><i class="bi bi-lightning-charge me-1"></i> OpenAI / DeepSeek</strong>
                                        <span class="text-muted" style="font-size: 0.8rem;">รองรับโมเดลมาตรฐานภายนอกอื่นๆ</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" onclick="fetchHosFinAiAnalysis()">
                                    <i class="bi bi-arrow-clockwise me-1"></i> ลองใหม่อีกครั้ง
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            errBox.classList.remove('d-none');
            errBox.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์: ${err}
                </div>
            `;
        });
    }

    // In-Modal Interactive Drill-Down (Text-to-SQL) Functions
    function focusDrillDownInput() {
        const section = document.getElementById('hosFinDrillDownSection');
        if (section) {
            section.classList.remove('d-none');
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        const modalBody = document.querySelector('#hosFinAiModal .modal-body');
        if (modalBody) {
            modalBody.scrollTo({ top: modalBody.scrollHeight, behavior: 'smooth' });
        }
        const input = document.getElementById('hosFinDrillDownInput');
        if (input) {
            setTimeout(() => input.focus(), 300);
        }
    }

    function askDrillDownQuestion(question) {
        const input = document.getElementById('hosFinDrillDownInput');
        if (input) {
            input.value = question;
            const form = document.getElementById('hosFinDrillDownForm');
            if (form) {
                form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        }
    }

    function handleDrillDownSubmit(event) {
        event.preventDefault();
        const input = document.getElementById('hosFinDrillDownInput');
        const question = input.value.trim();
        if (!question) return;

        const historyBox = document.getElementById('drillDownHistory');
        const btn = document.getElementById('hosFinDrillDownBtn');
        const spinner = document.getElementById('drillDownBtnSpinner');
        const icon = document.getElementById('drillDownBtnIcon');

        // Disable input & show loading state
        input.disabled = true;
        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        const reqId = 'drilldown_' + Date.now();
        const activeLabel = window.currentHosFinAiContext?.periodLabel || 'งวดปัจจุบัน';
        const userCardHtml = `
            <div class="card border border-primary-subtle shadow-xs rounded-3 bg-white overflow-hidden" id="${reqId}">
                <div class="card-header bg-primary bg-opacity-10 py-2 px-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-pill px-2 py-1"><i class="bi bi-person-fill me-1"></i> คำถามเจาะลึก</span>
                        <strong class="text-dark small">${escapeHosFinHtml(question)}</strong>
                    </div>
                    <small class="badge bg-white text-secondary border px-2 py-1">${escapeHosFinHtml(activeLabel)}</small>
                </div>
                <div class="card-body p-3">
                    <div class="drilldown-loading text-center py-3">
                        <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                        <span class="text-muted small">AI กำลังวิเคราะห์คำสั่ง SQL และดึงตัวเลขจากฐานข้อมูล GL...</span>
                    </div>
                    <div class="drilldown-content d-none"></div>
                </div>
            </div>
        `;
        historyBox.insertAdjacentHTML('beforeend', userCardHtml);
        input.value = '';

        const targetCard = document.getElementById(reqId);
        targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        fetch(`{{ route('hosfin.ai_drilldown') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question: question,
                period: window.currentHosFinAiContext?.period,
                budget_year: window.currentHosFinAiContext?.budgetYear
            })
        })
        .then(res => res.json())
        .then(res => {
            input.disabled = false;
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
            input.focus();

            const card = document.getElementById(reqId);
            if (!card) return;
            const loadingDiv = card.querySelector('.drilldown-loading');
            const contentDiv = card.querySelector('.drilldown-content');
            loadingDiv.classList.add('d-none');
            contentDiv.classList.remove('d-none');

            if (res.success) {
                let html = '';
                if (res.summary) {
                    html += `<div class="small mb-3 text-dark" style="line-height: 1.7;">${formatHosFinAiMarkdown(res.summary)}</div>`;
                }
                if (res.sql) {
                    const sqlId = 'sql_' + Date.now();
                    html += `
                        <div class="mb-2">
                            <a class="badge bg-light text-secondary border text-decoration-none small py-1 px-2" data-bs-toggle="collapse" href="#${sqlId}" role="button" aria-expanded="false">
                                <i class="bi bi-code-square text-primary me-1"></i> ดูคำสั่ง SQL (${res.db_target || 'hrims'}) <i class="bi bi-chevron-down ms-1"></i>
                            </a>
                            <div class="collapse mt-1" id="${sqlId}">
                                <div class="card card-body bg-dark text-light p-2 font-monospace small" style="font-size: 0.78rem; max-height: 120px; overflow-y: auto;">
                                    ${escapeHosFinHtml(res.sql)}
                                </div>
                            </div>
                        </div>
                    `;
                }
                if (res.rows && res.rows.length > 0) {
                    html += `<div class="table-responsive my-2 border rounded-3 overflow-hidden shadow-xs" style="max-height: 250px;">
                        <table class="table table-sm table-striped table-hover mb-0 small text-nowrap align-middle">
                            <thead class="table-light sticky-top"><tr>`;
                    const cols = res.columns || Object.keys(res.rows[0]);
                    cols.forEach(c => {
                        const label = (res.column_labels && res.column_labels[c]) ? res.column_labels[c] : c;
                        html += `<th class="py-1.5 px-2 text-secondary fw-bold">${escapeHosFinHtml(label)}</th>`;
                    });
                    html += `</tr></thead><tbody>`;
                    res.rows.forEach(r => {
                        html += `<tr>`;
                        cols.forEach(c => {
                            let val = r[c] !== null && r[c] !== undefined ? r[c] : '-';
                            const isNum = typeof val === 'number' || (!isNaN(val) && val !== '' && !isNaN(parseFloat(val)));
                            const formattedVal = (isNum && typeof val === 'number') ? Number(val).toLocaleString(undefined, { minimumFractionDigits: (val % 1 === 0 ? 0 : 2), maximumFractionDigits: 2 }) : escapeHosFinHtml(String(val));
                            const alignClass = isNum ? 'text-end font-monospace' : '';
                            html += `<td class="py-1 px-2 ${alignClass}">${formattedVal}</td>`;
                        });
                        html += `</tr>`;
                    });
                    html += `</tbody></table></div>`;
                    html += `<div class="text-end text-muted small" style="font-size: 0.72rem;">พบข้อมูล ${res.total_rows || res.rows.length} รายการ (ประมวลผล ${res.execution_time_ms || 0} ms)</div>`;
                } else if (!res.summary) {
                    html += `<div class="alert alert-info py-2 px-3 small mb-0"><i class="bi bi-info-circle me-1"></i> ไม่พบข้อมูลที่ตรงกับเงื่อนไขคำถามในงวดนี้</div>`;
                }

                if (res.suggestions && res.suggestions.length > 0) {
                    html += `<div class="mt-2 pt-2 border-top d-flex align-items-center gap-1.5 flex-wrap"><span class="small text-muted fw-bold">คำถามแนะนำ:</span>`;
                    res.suggestions.forEach(s => {
                        html += `<button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 small" style="font-size: 0.75rem;" onclick="askDrillDownQuestion('${escapeHosFinHtml(s)}')">${escapeHosFinHtml(s)}</button>`;
                    });
                    html += `</div>`;
                }

                contentDiv.innerHTML = html;
            } else {
                contentDiv.innerHTML = `
                    <div class="alert alert-warning py-2 px-3 small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> ${res.message || 'ไม่สามารถค้นหาข้อมูลได้ กรุณาลองปรับคำถามใหม่'}
                    </div>
                `;
            }
        })
        .catch(err => {
            input.disabled = false;
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
            const card = document.getElementById(reqId);
            if (card) {
                card.querySelector('.drilldown-loading').classList.add('d-none');
                const contentDiv = card.querySelector('.drilldown-content');
                contentDiv.classList.remove('d-none');
                contentDiv.innerHTML = `<div class="alert alert-danger py-2 px-3 small mb-0"><i class="bi bi-x-circle me-1"></i> เกิดข้อผิดพลาดในการติดต่อเซิร์ฟเวอร์: ${err}</div>`;
            }
        });
    }

    function continueInChatbot() {
        $('#hosFinAiModal').modal('hide');
        const periodText = window.currentHosFinAiContext?.periodLabel || 'งวดล่าสุด';
        const fundVal = window.currentHosFinAiContext?.netOperatingFund ? parseFloat(window.currentHosFinAiContext.netOperatingFund).toLocaleString() : '';
        const prompt = `ขอปรึกษาเจาะลึกสถานการณ์การเงิน HosFin จากข้อมูลบัญชี GL (${periodText}` + (fundVal ? `, เงินบำรุงสุทธิ ${fundVal} บาท` : '') + `)`;

        if (typeof window.openAiChatWithPrompt === 'function') {
            window.openAiChatWithPrompt(prompt);
        } else if (typeof toggleAiChatbot === 'function') {
            toggleAiChatbot();
        }
    }

    function printHosFinAiReport() {
        const aiText = document.getElementById('aiAnalysisText');
        if (!aiText || !aiText.innerHTML.trim()) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่มีข้อมูลรายงาน',
                    text: 'กรุณารอระบบ AI ประมวลผลรายงานสรุปให้เสร็จสิ้นก่อนพิมพ์ครับ'
                });
            } else {
                alert('กรุณารอระบบ AI ประมวลผลรายงานสรุปให้เสร็จสิ้นก่อนพิมพ์');
            }
            return;
        }

        const reportHtml = aiText.innerHTML;
        const sourcesHtml = document.getElementById('aiAnalysisSources') ? document.getElementById('aiAnalysisSources').innerHTML : '';

        const now = new Date();
        const dateStr = now.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
        const timeStr = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
        const hospitalName = `{{ DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?? 'โรงพยาบาล' }}`.replace(/^"|"$/g, '');

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            window.print();
            return;
        }

        const doc = printWindow.document;
        doc.open();
        doc.write(`
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปผลการวินิจฉัยสุขภาพการเงิน - ${hospitalName}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap');
        
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        body {
            font-family: 'Sarabun', sans-serif;
            color: #1e293b;
            background-color: #fff;
            font-size: 9.5pt;
            line-height: 1.55;
            padding: 0;
            margin: 0;
        }

        .report-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* 4-Column Executive KPI Table (Locks 4 columns on print, never stacks) */
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .kpi-table td {
            width: 25%;
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            vertical-align: top;
        }

        .kpi-title {
            font-size: 7.5pt;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 2px;
        }

        .kpi-value {
            font-size: 10.5pt;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        .kpi-sub {
            font-size: 7.5pt;
            color: #64748b;
            line-height: 1.3;
        }

        .badge-risk-danger {
            display: inline-block;
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 0px 5px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: 700;
        }

        .badge-data-source {
            display: inline-block;
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: 600;
        }

        /* Report Body Typography */
        .report-body {
            font-size: 9.5pt;
            line-height: 1.55;
            color: #1e293b;
        }

        .report-body h5, .report-body h6 {
            font-family: 'Sarabun', sans-serif;
            font-size: 10.5pt;
            font-weight: 700;
            color: #0f172a;
            margin-top: 10px;
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1px solid #e2e8f0;
            page-break-after: avoid;
            break-after: avoid;
        }

        .report-body p {
            margin-top: 0;
            margin-bottom: 5px;
            text-align: justify;
        }

        .report-body ul, .report-body ol {
            margin-top: 0;
            margin-bottom: 5px;
            padding-left: 18px;
        }

        .report-body li {
            margin-bottom: 2px;
            text-align: justify;
        }

        .report-body strong {
            font-weight: 700;
            color: #0f172a;
        }

        .report-body .badge {
            font-size: 7.5pt !important;
            padding: 2px 7px !important;
            border-radius: 10px !important;
        }

        .signature-section {
            margin-top: 25px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .no-print {
            display: block;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .container-fluid {
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print bg-light border-bottom p-2 px-3 mb-3 d-flex justify-content-between align-items-center" style="position: sticky; top: 0; z-index: 1000;">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary px-2 py-1"><i class="bi bi-file-earmark-pdf-fill me-1"></i> A4 Executive Report</span>
            <span class="fw-bold text-dark small">พิมพ์รายงานสรุปผลการวินิจฉัยสุขภาพการเงิน (Executive Summary)</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm px-3 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> สั่งพิมพ์รายงาน (Print / Save as PDF)
            </button>
            <button class="btn btn-outline-secondary btn-sm px-3" onclick="window.close()">
                <i class="bi bi-x-lg me-1"></i> ปิดหน้านี้
            </button>
        </div>
    </div>

    <div class="container-fluid px-3 py-1">
        <!-- Header -->
        <div class="report-header">
            <table style="width: 100%; border: none; border-collapse: collapse;">
                <tr>
                    <td style="border: none; padding: 0; vertical-align: middle;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-bank2 text-primary" style="font-size: 20pt;"></i>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark" style="font-family: 'Sarabun'; font-size: 13pt; letter-spacing: -0.2px;">
                                    รายงานสรุปผลการวินิจฉัยสุขภาพการเงิน & แนวโน้ม (Executive Summary)
                                </h5>
                                <div class="text-secondary" style="font-size: 8.5pt;">
                                    <strong class="text-dark">${hospitalName}</strong> | ระบบบริหารการเงินการคลัง (RiMS HosFin)
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="border: none; padding: 0; text-align: right; vertical-align: middle; font-size: 7.5pt; color: #64748b; line-height: 1.35;">
                        <div><strong>งวดบัญชีวิเคราะห์:</strong> {{ $latestPeriodLabel }} (ปีงบ {{ $budgetYear }})</div>
                        <div><strong>วันที่พิมพ์รายงาน:</strong> ${dateStr} เวลา ${timeStr} น.</div>
                        <div><strong>ผู้จัดพิมพ์:</strong> {{ Auth::user()->name ?? 'ผู้ดูแลระบบ' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 4-Column Executive KPI Table (Always stays in a neat 4x2 grid) -->
        <table class="kpi-table">
            <tr>
                <td style="width: 25%;">
                    <div class="kpi-title"><i class="bi bi-calendar3 text-primary me-1"></i> งวดบัญชีวิเคราะห์</div>
                    <div class="kpi-value">{{ $latestPeriodLabel }}</div>
                    <div class="kpi-sub">ปีงบประมาณ {{ $budgetYear }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-title"><i class="bi bi-shield-fill-exclamation text-danger me-1"></i> ระดับความเสี่ยง (Risk Score)</div>
                    <div class="kpi-value text-danger">ระดับ {{ $riskScore }} / 7</div>
                    <div class="kpi-sub"><span class="badge-risk-danger">{{ $riskScoreLevelLabel }}</span></div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-title"><i class="bi bi-cash-stack text-success me-1"></i> เงินบำรุงคงเหลือสุทธิ (105)</div>
                    <div class="kpi-value {{ $latestMetrics['105']['val'] < 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($latestMetrics['105']['val'], 2) }} <small class="text-muted fw-normal" style="font-size: 7.5pt;">บาท</small>
                    </div>
                    <div class="kpi-sub">CR: {{ $latestMetrics['100']['val'] }} | Cash: {{ $latestMetrics['102']['val'] }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-title"><i class="bi bi-clock-history text-secondary me-1"></i> ระยะเวลาค้างจ่าย / เรียกเก็บ</div>
                    <div class="kpi-sub" style="margin-top: 3px;">
                        <span class="text-danger fw-bold">จ่ายค่ายา: {{ $latestMetrics['260']['val'] }} วัน</span><br>
                        <span class="text-secondary">เก็บหนี้ข้าราชการ: {{ $latestMetrics['262']['val'] }} วัน</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="kpi-title"><i class="bi bi-file-earmark-spreadsheet text-danger me-1"></i> หนี้เจ้าหนี้การค้า (AP)</div>
                    <div class="kpi-value {{ ($apUnpaidSum ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ number_format($apUnpaidSum ?? 0, 2) }} <small class="text-muted fw-normal" style="font-size: 7.5pt;">บาท</small>
                    </div>
                    <div class="kpi-sub">ค้างจ่าย {{ number_format($apUnpaidCount ?? 0) }} บิล ({{ $apTotalVendorsCount ?? 0 }} บริษัท)</div>
                </td>
                <td>
                    <div class="kpi-title"><i class="bi bi-people text-warning me-1"></i> ลูกหนี้ค่ารักษาพยาบาล (AR)</div>
                    <div class="kpi-value text-dark">
                        {{ number_format($arOutstandingSum ?? 0, 2) }} <small class="text-muted fw-normal" style="font-size: 7.5pt;">บาท</small>
                    </div>
                    <div class="kpi-sub">จาก {{ number_format($arAccountCount ?? 0) }} ผังบัญชี</div>
                </td>
                <td>
                    <div class="kpi-title"><i class="bi bi-safe text-success me-1"></i> เงินสด & เงินฝากธนาคาร GL</div>
                    <div class="kpi-value text-success">
                        {{ number_format($cashBalance ?? 0, 2) }} <small class="text-muted fw-normal" style="font-size: 7.5pt;">บาท</small>
                    </div>
                    <div class="kpi-sub">{{ $cashAccountsCount ?? 0 }} บัญชีเงินฝาก</div>
                </td>
                <td>
                    <div class="kpi-title"><i class="bi bi-database-check text-primary me-1"></i> แหล่งข้อมูลประมวลผล</div>
                    <div class="kpi-sub" style="margin-top: 4px;">
                        <span class="badge-data-source">✓ ฐานข้อมูลบัญชีแยกประเภท GL จริง</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Executive AI Analysis Content -->
        <div class="report-body">
            ${reportHtml}
            ${sourcesHtml}
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <table style="width: 100%; border: none; border-collapse: collapse; text-align: center;">
                <tr>
                    <td style="width: 50%; border: none; padding: 10px;">
                        <div style="height: 45px;"></div>
                        <div style="font-size: 9.5pt;">ลงชื่อ..................................................................</div>
                        <div style="font-size: 9.5pt; margin-top: 3px;">(..................................................................)</div>
                        <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">ผู้จัดทำรายงาน / หัวหน้ากลุ่มงานการเงินและบัญชี</div>
                    </td>
                    <td style="width: 50%; border: none; padding: 10px;">
                        <div style="height: 45px;"></div>
                        <div style="font-size: 9.5pt;">ลงชื่อ..................................................................</div>
                        <div style="font-size: 9.5pt; margin-top: 3px;">(..................................................................)</div>
                        <div style="font-size: 8pt; color: #64748b; margin-top: 2px;">ผู้อำนวยการ ${hospitalName}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="border-top mt-3 pt-2 text-muted text-center" style="font-size: 7.5pt;">
            เอกสารรายงานสรุปผู้บริหารนี้ประมวลผลอัตโนมัติโดยระบบ RiMS HosFin ร่วมกับ AI Copilot เพื่อใช้ประกอบการวิเคราะห์และวางแผนบริหารการเงินการคลัง
        </div>
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
        doc.close();
    }

    function showAiAccessDeniedAlert() {
        Swal.fire({
            icon: 'warning',
            title: 'ไม่มีสิทธิ์เข้าถึงระบบ AI',
            text: 'คุณไม่ได้รับสิทธิ์ใช้งานระบบ AI (RiMS Copilot) กรุณาติดต่อผู้ดูแลระบบเพื่อขอเปิดสิทธิ์การใช้งาน',
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'ตกลง'
        });
    }
</script>

@if(\App\Services\LicenseVerificationService::isModuleLicensed('ai_knowledge') && \App\Services\Ai\AiService::isActive())
<!-- HosFin AI Analysis Modal -->
<div class="modal fade" id="hosFinAiModal" tabindex="-1" aria-labelledby="hosFinAiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header text-white py-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white bg-opacity-20 p-2 rounded-circle">
                        <i class="bi bi-robot fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="hosFinAiModalLabel">
                            AI วินิจฉัยวิกฤตสุขภาพการเงิน & แนวโน้ม (Executive Summary)
                        </h5>
                        <small class="text-white-50"><i class="bi bi-cpu me-1"></i> วิเคราะห์ข้อมูลอัตโนมัติเจาะลึกจากฐานข้อมูลบัญชี GL จริง (สมุดรายวัน, เจ้าหนี้ AP, ลูกหนี้ AR, ต้นทุน LC/MC/CC)</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light bg-opacity-25">
                <!-- Summary Snapshot Card -->
                <div class="card border rounded-3 p-3 mb-4 bg-white shadow-sm" id="hosFinAiSnapshotCard">
                    <div class="row g-3 text-center text-md-start align-items-center">
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold">งวดบัญชีวิเคราะห์</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1" id="hosFinSnapPeriod">{{ $latestPeriodLabel }}</h5>
                            <small class="text-muted" id="hosFinSnapYear">ปีงบ {{ $budgetYear }}</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold">ระดับความเสี่ยง (Risk Score)</span>
                            <h5 class="fw-bold text-danger mb-0 mt-1" id="hosFinSnapRiskScore">ระดับ {{ $riskScore }} / 7</h5>
                            <small class="badge bg-danger bg-opacity-10 text-danger border border-danger small" id="hosFinSnapRiskBadge">{{ $riskScoreLevelLabel }}</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold">เงินบำรุงคงเหลือสุทธิ (105)</span>
                            <h5 class="fw-bold {{ $latestMetrics['105']['val'] < 0 ? 'text-danger' : 'text-success' }} mb-0 mt-1" id="hosFinSnapFund">
                                {{ number_format($latestMetrics['105']['val'], 2) }} <span class="fs-6 fw-normal">บาท</span>
                            </h5>
                            <small class="text-muted" id="hosFinSnapRatios">Current Ratio: {{ $latestMetrics['100']['val'] }} | Cash: {{ $latestMetrics['102']['val'] }}</small>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small fw-bold">ระยะเวลาเก็บหนี้ / ค้างจ่าย</span>
                            <div class="small mt-1">
                                <span class="d-block text-danger fw-bold" id="hosFinSnapDrugPayDays"><i class="bi bi-clock-history me-1"></i> จ่ายค่ายา: {{ $latestMetrics['260']['val'] }} วัน</span>
                                <span class="d-block text-warning-custom" id="hosFinSnapOfcCollectDays"><i class="bi bi-receipt me-1"></i> เก็บหนี้ข้าราชการ: {{ $latestMetrics['262']['val'] }} วัน</span>
                            </div>
                        </div>
                    </div>
                    @if(isset($apUnpaidSum) || isset($arOutstandingSum) || isset($cashBalance))
                    <div class="row g-3 text-center text-md-start align-items-center mt-2 pt-2 border-top">
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold"><i class="bi bi-file-earmark-spreadsheet text-danger me-1"></i> หนี้เจ้าหนี้การค้า (AP)</span>
                            <h6 class="fw-bold {{ ($apUnpaidSum ?? 0) > 0 ? 'text-danger' : 'text-muted' }} mb-0 mt-1" id="hosFinSnapAp">{{ number_format($apUnpaidSum ?? 0, 2) }} บาท</h6>
                            <small class="text-muted" id="hosFinSnapApDesc">{{ ($apUnpaidSum ?? 0) > 0 ? 'ค้างจ่าย ' . number_format($apUnpaidCount ?? 0) . ' บิล (' . ($apTotalVendorsCount ?? 0) . ' บริษัท)' : '0 บิล (ยังไม่นำเข้าบิล AP)' }}</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold"><i class="bi bi-people text-warning me-1"></i> ลูกหนี้ค่ารักษา (AR)</span>
                            <h6 class="fw-bold text-dark mb-0 mt-1" id="hosFinSnapAr">{{ number_format($arOutstandingSum ?? 0, 2) }} บาท</h6>
                            <small class="text-muted" id="hosFinSnapArDesc">จาก {{ number_format($arAccountCount ?? 0) }} ผังบัญชี</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold"><i class="bi bi-safe text-success me-1"></i> เงินสด & เงินฝากธนาคาร GL</span>
                            <h6 class="fw-bold text-success mb-0 mt-1" id="hosFinSnapCash">{{ number_format($cashBalance ?? 0, 2) }} บาท</h6>
                            <small class="text-muted" id="hosFinSnapCashDesc">{{ $cashAccountsCount ?? 0 }} บัญชี (สธ. 1003X: {{ number_format($operatingCash ?? 0, 2) }} บ.)</small>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small fw-bold"><i class="bi bi-pie-chart text-info me-1"></i> แหล่งข้อมูลบัญชี</span>
                            <div class="small mt-1">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"><i class="bi bi-check-circle-fill me-1"></i> บัญชีแยกประเภท GL จริง</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Loading State -->
                <div id="aiAnalysisLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">กำลังประมวลผลการวิเคราะห์สุขภาพการเงิน...</h6>
                    <small class="text-muted">AI กำลังวิเคราะห์ดัชนีชี้วัดทั้ง 13 ตัว, กราฟรายรับ-รายจ่าย และเทียบเคียงมาตรฐานบัญชีลูกหนี้</small>
                </div>

                <!-- Error Box -->
                <div id="aiAnalysisError" class="d-none my-3"></div>

                <!-- Analysis Result Content -->
                <div id="aiAnalysisContent" class="card border rounded-3 p-4 bg-white shadow-sm d-none">
                    <div id="aiAnalysisText" class="fs-6" style="line-height: 1.8; color: #1e293b;"></div>
                    <div id="aiAnalysisSources"></div>

                    <!-- In-Modal Interactive Drill-Down Section -->
                    <div id="hosFinDrillDownSection" class="mt-4 pt-4 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-circle p-2 text-white" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                                    <i class="bi bi-search fs-6"></i>
                                </span>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        ถามเจาะลึกต่อยอดด้วย AI Interactive Drill-Down
                                    </h6>
                                    <small class="text-muted">คลิกคำถามแนะนำ หรือพิมพ์คำถามเจาะลึกจากฐานข้อมูลบัญชี GL จริงของงวดนี้</small>
                                </div>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-1 small">
                                <i class="bi bi-database-check me-1"></i> เชื่อมต่อ ฐานข้อมูล GL
                            </span>
                        </div>

                        <!-- Quick Action Chips -->
                        <div class="d-flex flex-wrap gap-2 mb-3" id="drillDownChipsContainer">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill drill-chip shadow-xs" onclick="askDrillDownQuestion('แสดง 5 บริษัทเจ้าหนี้ค่ายาที่ค้างชำระนานที่สุดและยอดหนี้')">
                                <i class="bi bi-receipt-cutoff text-danger me-1"></i> 5 เจ้าหนี้ค่ายาค้างนานสุด
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill drill-chip shadow-xs" onclick="askDrillDownQuestion('แสดงลูกหนี้สิทธิข้าราชการที่ยังค้างเบิกจ่ายและยอดรวม')">
                                <i class="bi bi-wallet2 text-primary me-1"></i> ลูกหนี้สิทธิข้าราชการค้างเบิก
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill drill-chip shadow-xs" onclick="askDrillDownQuestion('สรุปโครงสร้างต้นทุนบริการ LC MC CC ในงวดนี้')">
                                <i class="bi bi-pie-chart text-warning me-1"></i> สรุปสัดส่วนต้นทุน LC/MC/CC
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill drill-chip shadow-xs" onclick="askDrillDownQuestion('แสดงรายการบัญชีเงินสดและเงินฝากธนาคารทั้งหมดพร้อมยอดคงเหลือ')">
                                <i class="bi bi-cash-coin text-success me-1"></i> บัญชีเงินสด & เงินฝากทั้งหมด
                            </button>
                        </div>

                        <!-- Drill-Down Conversation & Results Container -->
                        <div id="drillDownHistory" class="d-flex flex-column gap-3 mb-3"></div>

                        <!-- Drill-Down Input Form -->
                        <form id="hosFinDrillDownForm" onsubmit="handleDrillDownSubmit(event)" class="mt-2">
                            <div class="input-group shadow-sm rounded-pill overflow-hidden border border-success-subtle">
                                <span class="input-group-text bg-white border-0 ps-3 pe-2 text-success">
                                    <i class="bi bi-chat-dots-fill"></i>
                                </span>
                                <input type="text" id="hosFinDrillDownInput" class="form-control border-0 py-2" 
                                       placeholder="พิมพ์คำถามเจาะลึก เช่น 'มียอดค่าใช้จ่ายหมวดไหนสูงสุดในงวดนี้', 'บิลเจ้าหนี้ค้างเกิน 60 วันมีกี่รายการ'..." 
                                       autocomplete="off" style="box-shadow: none;">
                                <button class="btn btn-success px-4 fw-bold d-flex align-items-center gap-1.5" type="submit" id="hosFinDrillDownBtn">
                                    <span id="drillDownBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    <i class="bi bi-send-fill" id="drillDownBtnIcon"></i>
                                    <span>ถามเจาะลึก</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" onclick="printHosFinAiReport()">
                        <i class="bi bi-printer me-1"></i> พิมพ์รายงานสรุป
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill" onclick="fetchHosFinAiAnalysis()">
                        <i class="bi bi-arrow-clockwise me-1"></i> วิเคราะห์ใหม่อีกครั้ง
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endif
@endif
@endsection
