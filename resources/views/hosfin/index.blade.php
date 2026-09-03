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
</style>

<div class="container-fluid py-4 px-lg-5" style="background-color: #f8fafc;">
    <div class="row">
        <!-- Header banner -->
        <div class="col-12 px-3 mb-3">
            <div class="page-header-box mt-2" style="border-left-color: #10b981 !important; background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%); padding: 16px 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 w-100">
                    <div>
                        <h4 class="text-primary mb-1 fw-bold d-flex align-items-center gap-2">
                            <i class="bi bi-bank2 text-success"></i> ระบบบริหารการเงินการคลัง (HosFin Dashboard)
                        </h4>
                        <div class="text-muted d-inline-flex align-items-center gap-2 small flex-wrap mt-1">
                            @if($hasData)
                                @if(isset($periods) && count($periods) > 0)
                                    <div class="d-inline-flex align-items-center gap-1.5 bg-white border border-success-subtle rounded-pill px-2.5 py-1 shadow-xs">
                                        <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 0.5rem; height: 0.5rem;"></span>
                                        <span class="small fw-bold text-success" style="font-size: 0.76rem;">งวดบัญชี:</span>
                                        <select class="form-select form-select-sm border-0 py-0 ps-1 pe-4 fw-bold text-dark bg-transparent" 
                                                style="font-size: 0.78rem; cursor: pointer; width: auto; box-shadow: none;" 
                                                onchange="location.href='{{ url('hosfin') }}?period=' + this.value">
                                            @foreach(array_reverse($periods) as $p)
                                                @if(in_array($p['period'], $importedPeriods ?? []))
                                                    <option value="{{ $p['period'] }}" {{ $p['period'] === $latestPeriod ? 'selected' : '' }}>
                                                        {{ $p['label'] }} (ปีงบ {{ $budgetYear }})
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

                                <!-- Risk Score Badge prominently placed in Header -->
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

                            @else
                                ศูนย์รวมรายงานสถานะทางการเงินและวิเคราะห์ต้นทุนการรักษาพยาบาล
                            @endif
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 ms-lg-auto flex-wrap">

                        <!-- Action Buttons (แบบที่ 1: Quick Buttons) -->
                        @if(\App\Services\LicenseVerificationService::isModuleLicensed('ai_knowledge') && \App\Services\Ai\AiService::isActive())
                            @php
                                $hasAiAccess = Auth::check() && (Auth::user()->status === 'admin' || Auth::user()->allow_ai_copilot === 'Y');
                            @endphp
                            <button type="button" class="btn rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm btn-nav-custom text-white" 
                                    onclick="{{ $hasAiAccess ? 'openHosFinAiModal()' : 'showAiAccessDeniedAlert()' }}"
                                    style="font-size: 0.85rem; height: 42px; font-weight: 700; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;"
                                    title="{{ $hasAiAccess ? 'คลิกเพื่อดูบทวิเคราะห์วิกฤตทางการเงินด้วย AI' : 'คุณไม่ได้รับสิทธิ์ใช้งาน AI' }}">
                                <i class="bi bi-robot fs-5"></i> AI วิเคราะห์
                            </button>
                        @endif

                        <a href="{{ url('hosfin/ap_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom" 
                           style="font-size: 0.85rem; height: 42px; font-weight: 700; background: #ffffff; border: 1.5px solid #ef4444; color: #dc2626; transition: all 0.25s ease;"
                           title="รายงานเจ้าหนี้การค้าและบิลค้างชำระ (AP)">
                            <i class="bi bi-receipt-cutoff" style="font-size: 1rem;"></i> เจ้าหนี้ (AP)
                        </a>

                        <a href="{{ url('hosfin/ar_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom" 
                           style="font-size: 0.85rem; height: 42px; font-weight: 700; background: #ffffff; border: 1.5px solid #0284c7; color: #0369a1; transition: all 0.25s ease;"
                           title="รายงานลูกหนี้ค่ารักษาพยาบาลแยกตามสิทธิ (AR)">
                            <i class="bi bi-wallet2" style="font-size: 1rem;"></i> ลูกหนี้ (AR)
                        </a>

                        <a href="{{ url('hosfin/cost_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom" 
                           style="font-size: 0.85rem; height: 42px; font-weight: 700; background: #ffffff; border: 1.5px solid #d97706; color: #b45309; transition: all 0.25s ease;"
                           title="รายงานวิเคราะห์ต้นทุนบริการ (LC / MC / CC)">
                            <i class="bi bi-pie-chart" style="font-size: 1rem;"></i> ต้นทุน (LC/MC/CC)
                        </a>

                        <a href="{{ url('hosfin/ratio_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom btn-rr-custom" 
                           style="font-size: 0.85rem; height: 42px; font-weight: 700; background: #ffffff; border: 1.5px solid #3b82f6; color: #2563eb; transition: all 0.25s ease;"
                           title="รายงานอัตราส่วนทางการเงิน">
                            <i class="bi bi-graph-up-arrow" style="font-size: 1rem;"></i> อัตราส่วน
                        </a>

                        <a href="{{ url('hosfin/trial_balance') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm btn-nav-custom btn-tb-custom" 
                           style="font-size: 0.85rem; height: 42px; font-weight: 700; background: #ffffff; border: 1.5px solid #10b981; color: #059669; transition: all 0.25s ease;"
                           title="รายงานและนำเข้างบทดลอง (Trial Balance)">
                            <i class="bi bi-file-earmark-spreadsheet" style="font-size: 1rem;"></i> งบทดลอง
                        </a>
                    </div>
                </div>
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
                             data-bs-toggle="modal" data-bs-target="#cashBankModal" onclick="openCashModal()">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.4px;">
                                            เงินสดและเงินฝากธนาคาร (CASH)
                                        </span>
                                        <div class="fw-black mt-1 text-success" style="font-size: 1.45rem; font-family: monospace; font-weight: 800; line-height: 1.2;">
                                            {{ number_format($cashBalance ?? 0, 2) }}
                                            <span style="font-size: 0.78rem; font-weight: 600;">บาท</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.70rem;">
                                                {{ number_format($cashAccountsCount ?? 0) }} บัญชีเงินฝาก (GL)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success shadow-xs" style="width: 42px; height: 42px;">
                                        <i class="bi bi-cash-stack fs-4"></i>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-1" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <span class="text-muted text-truncate" style="font-size: 0.69rem;" title="เวลาที่ดึงข้อมูลจาก GL">
                                        <i class="bi {{ $glSyncSuccess ? 'bi-cloud-check-fill text-success' : 'bi-cloud-slash text-muted' }} me-1"></i>
                                        จาก GL: <strong class="{{ $glSyncSuccess ? 'text-dark' : 'text-muted' }}">{{ $glSyncTimeText }}</strong>
                                    </span>
                                    <small class="text-success fw-bold text-nowrap ms-1" style="font-size: 0.73rem;">คลิกดูสมุดบัญชี <i class="bi bi-arrow-up-right"></i></small>
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
                                            {{ number_format($apUnpaidSum ?? 0, 2) }}
                                            <span style="font-size: 0.78rem; font-weight: 600;">บาท</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.70rem;">
                                                {{ number_format($apUnpaidCount ?? 0) }} บิลค้างชำระ (GL)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger" style="width: 42px; height: 42px;">
                                        <i class="bi bi-receipt-cutoff fs-4"></i>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-1" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <span class="text-muted text-truncate" style="font-size: 0.69rem;" title="เวลาที่ดึงข้อมูลจาก GL">
                                        <i class="bi {{ $glSyncSuccess ? 'bi-cloud-check-fill text-success' : 'bi-cloud-slash text-muted' }} me-1"></i>
                                        จาก GL: <strong class="{{ $glSyncSuccess ? 'text-dark' : 'text-muted' }}">{{ $glSyncTimeText }}</strong>
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
                                            {{ number_format($arOutstandingSum ?? 0, 2) }}
                                            <span style="font-size: 0.78rem; font-weight: 600;">บาท</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.70rem;">
                                                {{ number_format($arAccountCount ?? 0) }} ผังลูกหนี้ (GL)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width: 42px; height: 42px;">
                                        <i class="bi bi-wallet2 fs-4"></i>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-1" style="border-color: rgba(0,0,0,0.06) !important;">
                                    <span class="text-muted text-truncate" style="font-size: 0.69rem;" title="เวลาที่ดึงข้อมูลจาก GL">
                                        <i class="bi {{ $glSyncSuccess ? 'bi-cloud-check-fill text-success' : 'bi-cloud-slash text-muted' }} me-1"></i>
                                        จาก GL: <strong class="{{ $glSyncSuccess ? 'text-dark' : 'text-muted' }}">{{ $glSyncTimeText }}</strong>
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
                    <h7 class="fw-bold text-dark d-block border-bottom pb-2 mb-2"><i class="bi bi-calculator-fill text-primary me-1"></i> รายละเอียดที่มาของตัวเลขประจำงวดล่าสุด</h7>
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
                        <small class="text-white-50">ข้อมูลจากสมุดรายวัน GL และแฟ้มตั้งหนี้-จ่ายชำระล่าสุด</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- 3 Highlights Top Strip -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-danger">
                            <small class="text-muted fw-bold d-block">ยอดหนี้ค้างจ่ายรวม</small>
                            <span class="fs-5 fw-black text-danger font-monospace">{{ number_format($apUnpaidSum, 2) }}</span>
                            <small class="text-muted d-block">บาท</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-dark">
                            <small class="text-muted fw-bold d-block">จำนวนบิลค้างชำระ</small>
                            <span class="fs-5 fw-black text-dark font-monospace">{{ number_format($apUnpaidCount) }}</span>
                            <small class="text-muted d-block">ใบ</small>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                        <small class="text-white-50">ข้อมูลการตั้งเบิก ชดเชย และลูกหนี้ค้างท่อแยกตามสิทธิกองทุนหลัก</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- 3 Highlights Top Strip -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-info">
                            <small class="text-muted fw-bold d-block">ลูกหนี้ค้างรับคงเหลือ</small>
                            <span class="fs-5 fw-black text-primary font-monospace">{{ number_format($arOutstandingSum, 2) }}</span>
                            <small class="text-muted d-block">บาท</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-success">
                            <small class="text-muted fw-bold d-block">ยอดตั้งเบิกสะสมรวม</small>
                            <span class="fs-5 fw-black text-success font-monospace">{{ number_format($arTotalBilled, 2) }}</span>
                            <small class="text-muted d-block">บาท</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-xs rounded-3 p-3 bg-white text-center border-start border-4 border-warning">
                            <small class="text-muted fw-bold d-block">ชดเชยที่รับเงินแล้ว</small>
                            <span class="fs-5 fw-black text-dark font-monospace">{{ number_format($arTotalCollected, 2) }}</span>
                            <small class="text-muted d-block">({{ $arTotalBilled > 0 ? round(($arTotalCollected / $arTotalBilled) * 100, 1) : 0 }}%)</small>
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
                                    <th class="text-end">ยอดตั้งเบิก (บาท)</th>
                                    <th class="text-end">ชดเชยแล้ว (บาท)</th>
                                    <th class="text-end pe-3 text-primary">ลูกหนี้คงค้าง (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($arTypeSummaries as $ts)
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark">
                                            <i class="bi bi-tag-fill text-primary me-1"></i> {{ $ts->debtor_type ?: 'ทั่วไป' }}
                                        </td>
                                        <td class="text-center font-monospace">{{ $ts->account_count }}</td>
                                        <td class="text-end font-monospace">{{ number_format($ts->total_billed, 2) }}</td>
                                        <td class="text-end font-monospace text-success">{{ number_format($ts->total_collected, 2) }}</td>
                                        <td class="text-end pe-3 font-monospace fw-bold {{ $ts->outstanding_balance > 0.01 ? 'text-primary' : 'text-muted' }}">
                                            {{ number_format($ts->outstanding_balance, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">ไม่มีข้อมูลลูกหนี้</td>
                                    </tr>
                                @endforelse
                            </tbody>
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
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px;">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="cashBankModalLabel">
                            สมุดบัญชีเงินสดและเงินฝากธนาคารทั้งหมด (Cash & Bank)
                        </h5>
                        <small class="text-white-50">ข้อมูลจากงบทดลอง GL งวดล่าสุด: {{ $latestPeriodLabel }} (ปีงบประมาณ {{ $budgetYear }})</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- KPI Highlight Banner inside Modal -->
                <div class="card border-0 rounded-4 shadow-xs p-3 mb-3 bg-white" style="border-left: 5px solid #10b981 !important;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">ยอดเงินสดและเงินฝากธนาคารรวมสุทธิ</span>
                            <div class="fs-4 fw-black text-success font-monospace mt-0.5">
                                {{ number_format($cashBalance ?? 0, 2) }} <span class="fs-6 fw-normal text-muted">บาท</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold">
                                รวม {{ number_format($cashAccountsCount ?? 0) }} เล่มบัญชี
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Table of Accounts -->
                <div class="card border-0 rounded-4 shadow-xs overflow-hidden bg-white mb-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-3" style="width: 40px;">#</th>
                                    <th>รหัสบัญชี</th>
                                    <th>ชื่อบัญชี / เลขที่บัญชีธนาคาร</th>
                                    <th class="text-end pe-3 text-success">ยอดคงเหลือ (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $cIdx = 1; @endphp
                                @forelse($cashBankAccounts as $ca)
                                    <tr>
                                        <td class="ps-3 text-muted fw-bold">{{ $cIdx++ }}</td>
                                        <td class="font-monospace fw-bold text-primary">{{ $ca->account_code }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $ca->account_name }}</div>
                                        </td>
                                        <td class="text-end pe-3 font-monospace fw-bold {{ $ca->net_balance > 0 ? 'text-success' : ($ca->net_balance < 0 ? 'text-danger' : 'text-muted') }}">
                                            {{ number_format($ca->net_balance, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">ไม่พบข้อมูลบัญชีเงินสด</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light border-top border-2">
                                <tr class="fw-bold align-middle">
                                    <th colspan="3" class="ps-3 py-2.5 text-secondary">
                                        <i class="bi bi-calculator me-1"></i> รวมเงินสดและเงินฝากธนาคารทั้งหมด:
                                    </th>
                                    <th class="text-end pe-3 py-2.5 font-monospace text-success fs-6">
                                        {{ number_format($cashBalance ?? 0, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="p-3 rounded-3 bg-white border small text-muted" style="border-left: 4px solid #10b981 !important; line-height: 1.6;">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-info-circle-fill text-success me-1"></i> หมายเหตุการเงิน:</strong>
                    ยอดเงินสดและเงินฝากธนาคารรวม <strong>{{ number_format($cashBalance ?? 0, 2) }} บาท</strong> คือสภาพคล่องที่เป็นเงินสดจริงทั้งหมดที่โรงพยาบาลมีอยู่ (กลุ่มบัญชี 1003X) อ้างอิงตามงบทดลอง HosFin GL งวด {{ $latestPeriodLabel }}
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

    function openCashModal() {
        const el = document.getElementById('cashBankModal');
        if (el) {
            if (typeof $ !== 'undefined' && typeof $(el).modal === 'function') {
                $(el).modal('show');
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
            }
        }
    }
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

    // AI Financial Diagnosis Modal Logic
    function openHosFinAiModal() {
        $('#hosFinAiModal').modal('show');
        if (!window.hosFinAnalysisLoaded) {
            fetchHosFinAiAnalysis();
        }
    }

    function fetchHosFinAiAnalysis() {
        const loading = document.getElementById('aiAnalysisLoading');
        const content = document.getElementById('aiAnalysisContent');
        const errBox = document.getElementById('aiAnalysisError');

        loading.classList.remove('d-none');
        content.classList.add('d-none');
        errBox.classList.add('d-none');

        const prompt = "ช่วยวิเคราะห์สรุปสถานการณ์วิกฤตทางการเงิน HosFin ของโรงพยาบาล ณ งวดล่าสุดนี้อย่างละเอียด โดยครอบคลุม 4 หัวข้อสำคัญ:\n1. บทสรุปสุขภาพการเงินและสภาพคล่องปัจจุบัน (เงินบำรุง, Risk Score, Current Ratio, Cash Ratio)\n2. ชี้เป้าสาเหตุของวิกฤตและคอขวด (ลูกหนี้ค้างท่อสิทธิ์ข้าราชการ/UC, หนี้ค่ายาค้างจ่าย)\n3. การคาดการณ์แนวโน้ม 3-6 เดือนข้างหน้า (หากไม่มีการปรับปรุง)\n4. แผนปฏิบัติการเร่งด่วนและข้อเสนอแนะเชิงกลยุทธ์สำหรับผู้บริหารและฝ่ายการเงิน\n\nพร้อมอ้างอิงระเบียบและแนวทางบริหารลูกหนี้ที่เกี่ยวข้องครับ";

        fetch(`{{ route('admin.rag.ask') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ question: prompt })
        })
        .then(res => res.json())
        .then(data => {
            loading.classList.add('d-none');
            if (data.success && data.answer) {
                window.hosFinAnalysisLoaded = true;
                content.classList.remove('d-none');
                
                // Simple Markdown renderer
                let formatted = data.answer
                    .replace(/^### (.*$)/gim, '<h6 class="fw-bold text-dark mt-3 mb-2 border-bottom pb-1"><i class="bi bi-caret-right-fill text-primary me-1"></i> $1</h6>')
                    .replace(/^#### (.*$)/gim, '<h6 class="fw-bold text-secondary mt-2 mb-1" style="font-size: 0.9rem;">$1</h6>')
                    .replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/gim, '<em>$1</em>')
                    .replace(/^\* (.*$)/gim, '<li class="mb-1">$1</li>')
                    .replace(/^- (.*$)/gim, '<li class="mb-1">$1</li>')
                    .replace(/\n\n/gim, '<br>')
                    .replace(/\n/gim, '<br>');

                document.getElementById('aiAnalysisText').innerHTML = formatted;

                // Render sources
                const srcContainer = document.getElementById('aiAnalysisSources');
                if (data.sources && data.sources.length > 0) {
                    let srcHtml = '<div class="mt-3 pt-3 border-top"><small class="fw-bold text-muted d-block mb-1"><i class="bi bi-bookmark-check-fill text-success me-1"></i> แหล่งข้อมูลอ้างอิง:</small><div class="d-flex flex-wrap gap-2">';
                    data.sources.forEach(s => {
                        srcHtml += `<span class="badge bg-light text-dark border small" title="${s.snippet}"><i class="bi bi-file-earmark-text me-1"></i>${s.title} ${s.page ? `(หน้า ${s.page})` : ''}</span>`;
                    });
                    srcHtml += '</div></div>';
                    srcContainer.innerHTML = srcHtml;
                } else {
                    srcContainer.innerHTML = '';
                }
            } else {
                errBox.classList.remove('d-none');
                errBox.textContent = data.message || 'ไม่สามารถวิเคราะห์ข้อมูลได้';
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            errBox.classList.remove('d-none');
            errBox.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + err;
        });
    }

    function continueInChatbot() {
        $('#hosFinAiModal').modal('hide');
        if (typeof window.openAiChatWithPrompt === 'function') {
            window.openAiChatWithPrompt('ขอปรึกษาเจาะลึกเกี่ยวกับสถานการณ์การเงินและแนวทางแก้ไขของ HosFin');
        } else if (typeof toggleAiChatbot === 'function') {
            toggleAiChatbot();
        }
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
                        <small class="text-white-50">วิเคราะห์ข้อมูลอัตโนมัติจาก HosFin และมาตรฐานบัญชีลูกหนี้ รพ. ด้วย Google Gemini</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light bg-opacity-25">
                <!-- Summary Snapshot Card -->
                <div class="card border rounded-3 p-3 mb-4 bg-white shadow-sm">
                    <div class="row g-3 text-center text-md-start align-items-center">
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold">งวดบัญชีวิเคราะห์</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">{{ $latestPeriodLabel }}</h5>
                            <small class="text-muted">ปีงบ {{ $budgetYear }}</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold">ระดับความเสี่ยง (Risk Score)</span>
                            <h5 class="fw-bold text-danger mb-0 mt-1">ระดับ {{ $riskScore }} / 7</h5>
                            <small class="badge bg-danger bg-opacity-10 text-danger border border-danger small">{{ $riskScoreLevelLabel }}</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <span class="text-muted small fw-bold">เงินบำรุงคงเหลือสุทธิ (105)</span>
                            <h5 class="fw-bold {{ $latestMetrics['105']['val'] < 0 ? 'text-danger' : 'text-success' }} mb-0 mt-1">
                                {{ number_format($latestMetrics['105']['val'], 2) }} <span class="fs-6 fw-normal">บาท</span>
                            </h5>
                            <small class="text-muted">Current Ratio: {{ $latestMetrics['100']['val'] }} | Cash: {{ $latestMetrics['102']['val'] }}</small>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small fw-bold">ระยะเวลาเก็บหนี้ / ค้างจ่าย</span>
                            <div class="small mt-1">
                                <span class="d-block text-danger fw-bold"><i class="bi bi-clock-history me-1"></i> จ่ายค่ายา: {{ $latestMetrics['260']['val'] }} วัน</span>
                                <span class="d-block text-warning-custom"><i class="bi bi-receipt me-1"></i> เก็บหนี้ข้าราชการ: {{ $latestMetrics['262']['val'] }} วัน</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="aiAnalysisLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">กำลังประมวลผลการวิเคราะห์สุขภาพการเงิน...</h6>
                    <small class="text-muted">AI กำลังวิเคราะห์ดัชนีชี้วัดทั้ง 13 ตัว, กราฟรายรับ-รายจ่าย และเทียบเคียงมาตรฐานบัญชีลูกหนี้</small>
                </div>

                <!-- Error Box -->
                <div id="aiAnalysisError" class="alert alert-danger d-none my-3"></div>

                <!-- Analysis Result Content -->
                <div id="aiAnalysisContent" class="card border rounded-3 p-4 bg-white shadow-sm d-none">
                    <div id="aiAnalysisText" class="fs-6" style="line-height: 1.8; color: #1e293b;"></div>
                    <div id="aiAnalysisSources"></div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> พิมพ์รายงานสรุป
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill" onclick="fetchHosFinAiAnalysis()">
                        <i class="bi bi-arrow-clockwise me-1"></i> วิเคราะห์ใหม่อีกครั้ง
                    </button>
                    <button type="button" class="btn btn-success btn-sm px-4 rounded-pill fw-bold" onclick="continueInChatbot()">
                        <i class="bi bi-chat-dots-fill me-1"></i> ถามเจาะลึกต่อกับ RiMS Copilot 🤖
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
