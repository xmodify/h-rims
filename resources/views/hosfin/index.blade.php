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
    border-width: 0 0 0 4px !important;
  }
  .metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08) !important;
    filter: brightness(0.98);
  }
  .metric-title {
    font-size: 0.82rem;
    color: #475569;
    font-weight: 600;
  }
  .metric-value {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
  }
  .metric-unit {
    font-size: 0.8rem;
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
</style>

<div class="container-fluid py-4 px-lg-5" style="background-color: #f8fafc;">
    <div class="row">
        <!-- Header banner -->
        <div class="col-12 px-3 mb-4">
            <div class="page-header-box mt-2" style="border-left-color: #10b981 !important; background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%); padding: 18px 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="text-primary mb-1 fw-bold">
                            <i class="bi bi-bank me-2 text-success"></i> ข้อมูลบัญชีหน่วยงาน (HosFin Dashboard)
                        </h5>
                        <small class="text-muted">
                            ศูนย์รวมรายงานสถานะทางการเงินและวิเคราะห์ต้นทุนการรักษาพยาบาล
                        </small>
                    </div>
                    @if($hasData)
                        <div class="bg-white border rounded-pill px-3 py-1 text-secondary shadow-sm d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                            <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                            ข้อมูลล่าสุด ณ งวดบัญชี: <strong class="text-dark">{{ $latestPeriodLabel }}</strong> (ปีงบประมาณ {{ $budgetYear }})
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if(!$hasData)
            <!-- Placeholder when no data imported -->
            <div class="col-12 px-3 mb-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-5 text-center">
                        <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='80' height='80' fill='%2394a3b8' class='bi bi-folder-plus' viewBox='0 0 16 16'><path d='m.5 3 .04.875L.5 3.875z'/><path d='M1.5 15a.5.5 0 0 0 .5.5h12a.5.5 0 0 0 .5-.5V4H1.5zM2 5h12v9H2zM0 3c0-1.105.895-2 2-2h3.933a2 2 0 0 1 1.664.89l.818 1.228A1 1 0 0 0 9.25 3.5h4.75a2 2 0 0 1 2 2V14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm8.5 5.5v2a.5.5 0 0 1-1 0v-2h-2a.5.5 0 0 1 0-1h2v-2a.5.5 0 0 1 1 0v2h2a.5.5 0 0 1 0 1z'/></svg>" alt="No Data" class="mb-3" style="opacity: 0.5;">
                        <h5 class="text-muted fw-bold">ยินดีต้อนรับสู่ระบบข้อมูลบัญชีหน่วยงาน HosFin</h5>
                        <p class="text-secondary small mb-4" style="max-width: 500px; margin: 0 auto;">ขณะนี้ยังไม่มีข้อมูลบัญชีหน่วยงานนำเข้าในระบบ กรุณานำเข้าข้อมูลไฟล์งบกระทรวง (.zip) ที่ปุ่มเข้าใช้งานด้านล่างเพื่อเริ่มการประมวลผลดัชนีชี้วัดของผู้บริหาร</p>
                        <a href="{{ url('hosfin/trial_balance') }}" class="btn btn-success rounded-pill px-4 shadow-sm">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> ไปหน้านำเข้าข้อมูลบัญชีหน่วยงาน hfo (.zip)
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Metrics Executive Panel (3 Rows Grid) -->
            <div class="col-12 px-3 mb-2">
                
                <!-- ROW 1: Liquidity & Cash Balance -->
                <div class="section-title-custom">
                    <i class="bi bi-shield-check text-success me-1"></i> 1. การวิเคราะห์วิกฤตทางการเงินและสภาพคล่องหมุนเวียน (Liquidity & Cash Balance)
                    <span class="text-muted fw-normal" style="font-size: 0.75rem; margin-left: 6px;">(คลิกที่การ์ดเพื่อดูแนวโน้มรายงวดบัญชี)</span>
                </div>
                <div class="row g-3 mb-4">
                    <!-- 1. Net Cash Balance (105) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['105']['class'] }}" data-code="105" data-name="เงินบำรุงคงเหลือสุทธิ (หักหนี้แล้ว)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">เงินบำรุงคงเหลือสุทธิ</span>
                                    <span class="badge {{ $statusMap['105']['bg'] }} {{ $statusMap['105']['class'] }} badge-custom">
                                        {{ $statusMap['105']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value text-nowrap">{{ number_format($latestMetrics['105'], 2) }}</span>
                                        <span class="metric-unit ms-1">บาท</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-piggy-bank" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Current Ratio (100) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['100']['class'] }}" data-code="100" data-name="Current Ratio (อัตราส่วนสภาพคล่องภาพรวม)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">Current Ratio</span>
                                    <span class="badge {{ $statusMap['100']['bg'] }} {{ $statusMap['100']['class'] }} badge-custom">
                                        {{ $statusMap['100']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['100'], 2) }}</span>
                                        <span class="metric-unit ms-1">เท่า</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-arrow-left-right" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Quick Ratio (101) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['101']['class'] }}" data-code="101" data-name="Quick Ratio (อัตราส่วนสภาพคล่องเร่งด่วน)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">Quick Ratio</span>
                                    <span class="badge {{ $statusMap['101']['bg'] }} {{ $statusMap['101']['class'] }} badge-custom">
                                        {{ $statusMap['101']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['101'], 2) }}</span>
                                        <span class="metric-unit ms-1">เท่า</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-lightning-charge" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Cash Ratio (102) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['102']['class'] }}" data-code="102" data-name="Cash Ratio (อัตราส่วนเงินสดพร้อมจ่ายทันที)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">Cash Ratio</span>
                                    <span class="badge {{ $statusMap['102']['bg'] }} {{ $statusMap['102']['class'] }} badge-custom">
                                        {{ $statusMap['102']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['102'], 2) }}</span>
                                        <span class="metric-unit ms-1">เท่า</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-cash-stack" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROW 2: Operational Efficiency -->
                <div class="section-title-custom">
                    <i class="bi bi-speedometer2 text-primary me-1"></i> 2. ประสิทธิภาพการบริหารคลัง ยา และลูกหนี้ชดเชย (Operational Efficiency)
                    <span class="text-muted fw-normal" style="font-size: 0.75rem; margin-left: 6px;">(คลิกที่การ์ดเพื่อดูแนวโน้มรายงวดบัญชี)</span>
                </div>
                <div class="row g-3 mb-4">
                    <!-- 5. Inventory Days (264) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['264']['class'] }}" data-code="264" data-name="ระยะเวลาระบายคลังยาและเวชภัณฑ์ (Inventory Days)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">ระยะเวลาสต็อกคลังยา</span>
                                    <span class="badge {{ $statusMap['264']['bg'] }} {{ $statusMap['264']['class'] }} badge-custom">
                                        {{ $statusMap['264']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['264'], 2) }}</span>
                                        <span class="metric-unit ms-1">วัน</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-prescription2" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Collection Period - UC (261) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['261']['class'] }}" data-code="261" data-name="ระยะเวลาเก็บเงินสิทธิบัตรทอง (Collection Period - UC)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">เก็บหนี้สิทธิบัตรทอง (UC)</span>
                                    <span class="badge {{ $statusMap['261']['bg'] }} {{ $statusMap['261']['class'] }} badge-custom">
                                        {{ $statusMap['261']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['261'], 2) }}</span>
                                        <span class="metric-unit ms-1">วัน</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-wallet2" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Collection Period - CSMBS (262) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['262']['class'] }}" data-code="262" data-name="ระยะเวลาเก็บหนี้สิทธิข้าราชการ (Collection Period - CSMBS)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">เก็บหนี้สิทธิข้าราชการ (CS)</span>
                                    <span class="badge {{ $statusMap['262']['bg'] }} {{ $statusMap['262']['class'] }} badge-custom">
                                        {{ $statusMap['262']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['262'], 2) }}</span>
                                        <span class="metric-unit ms-1">วัน</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-person-check-fill" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Average Payment Period (260) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['260']['class'] }}" data-code="260" data-name="ระยะเวลาจ่ายชำระหนี้ค่ายาและคู่ค้า (Average Payment Period)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">ระยะเวลาค้างจ่ายค่ายา</span>
                                    <span class="badge {{ $statusMap['260']['bg'] }} {{ $statusMap['260']['class'] }} badge-custom">
                                        {{ $statusMap['260']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['260'], 2) }}</span>
                                        <span class="metric-unit ms-1">วัน</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-clock-history" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROW 3: Profitability & Cost Control -->
                <div class="section-title-custom">
                    <i class="bi bi-percent text-danger me-1"></i> 3. ความสามารถในการคุมรายจ่ายและทำกำไร (Profitability & Cost Control)
                    <span class="text-muted fw-normal" style="font-size: 0.75rem; margin-left: 6px;">(คลิกที่การ์ดเพื่อดูแนวโน้มรายงวดบัญชี)</span>
                </div>
                <div class="row g-3 mb-4">
                    <!-- 9. Operating Margin % (320) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['320']['class'] }}" data-code="320" data-name="Operating Margin % (กำไรจากการดำเนินงาน EBITDA)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">Operating Margin (EBITDA)</span>
                                    <span class="badge {{ $statusMap['320']['bg'] }} {{ $statusMap['320']['class'] }} badge-custom">
                                        {{ $statusMap['320']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['320'], 2) }}</span>
                                        <span class="metric-unit ms-1">%</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-graph-up-arrow" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 10. ROA % (321) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['321']['class'] }}" data-code="321" data-name="Return on Assets % (ROA - อัตราผลตอบแทนต่อสินทรัพย์รวม)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">ROA % (ผลตอบแทนทรัพย์สิน)</span>
                                    <span class="badge {{ $statusMap['321']['bg'] }} {{ $statusMap['321']['class'] }} badge-custom">
                                        {{ $statusMap['321']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['321'], 2) }}</span>
                                        <span class="metric-unit ms-1">%</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-briefcase" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 11. Net Profit Margin % (307) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['307']['class'] }}" data-code="307" data-name="Net Profit Margin % (อัตรากำไรสุทธิแบบรวมค่าเสื่อมราคา)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">Net Margin (กำไรมีค่าเสื่อม)</span>
                                    <span class="badge {{ $statusMap['307']['bg'] }} {{ $statusMap['307']['class'] }} badge-custom">
                                        {{ $statusMap['307']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value">{{ number_format($latestMetrics['307'], 2) }}</span>
                                        <span class="metric-unit ms-1">%</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-file-earmark-bar-graph" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 12. Net Working Capital (104) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card metric-card border-start shadow-sm border-{{ $statusMap['104']['class'] }}" data-code="104" data-name="Networking Capital (เงินทุนหมุนเวียนสุทธิ)">
                            <div class="card-body p-3">
                                <!-- Row 1: Title & Status -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="metric-title">Networking Capital</span>
                                    <span class="badge {{ $statusMap['104']['bg'] }} {{ $statusMap['104']['class'] }} badge-custom">
                                        {{ $statusMap['104']['label'] }}
                                    </span>
                                </div>
                                <!-- Row 2: Value & Unit & Icon -->
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="metric-value text-nowrap">{{ number_format($latestMetrics['104'], 2) }}</span>
                                        <span class="metric-unit ms-1">บาท</span>
                                    </div>
                                    <div class="text-secondary opacity-50">
                                        <i class="bi bi-wallet" style="font-size: 1.15rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Card menus (Original entry buttons) -->
        <div class="col-12 px-3 mb-2 mt-2">
            <div class="section-title-custom"><i class="bi bi-menu-button-wide text-secondary me-1"></i> เข้าถึงระบบบริหารจัดการหลัก</div>
        </div>
        
        <!-- Cards Grid -->
        <div class="col-md-6 mb-4 px-3">
            <div class="card hosfin-card accent-teal h-100 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 text-success me-3">
                            <i class="bi bi-file-earmark-spreadsheet" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">ข้อมูลบัญชีหน่วยงาน</h5>
                            <small class="text-muted">Trial Balance Manager</small>
                        </div>
                    </div>
                    <p class="text-muted mb-4 flex-grow-1" style="font-size: 0.9rem; line-height: 1.6;">
                        ระบบนำเข้า ตรวจสอบ และวิเคราะห์ยอดเงินงบทดลองประจำแต่ละเดือน แยกรายปีงบประมาณอย่างเป็นระบบ พร้อมฟังก์ชันเปรียบเทียบความถูกต้องของยอดเงิน
                    </p>
                    <a href="{{ url('hosfin/trial_balance') }}" class="btn btn-success rounded-pill px-4 align-self-start shadow-sm mt-auto">
                        เข้าใช้งานระบบ <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4 px-3">
            <div class="card hosfin-card accent-blue h-100 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 text-primary me-3">
                            <i class="bi bi-graph-up-arrow" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">วิเคราะห์อัตราส่วนการเงิน</h5>
                            <small class="text-muted">Financial Ratio Analysis</small>
                        </div>
                    </div>
                    <p class="text-muted mb-4 flex-grow-1" style="font-size: 0.9rem; line-height: 1.6;">
                        ระบบวิเคราะห์และคำนวณอัตราส่วนทางการเงินรายเดือนและรายปีงบประมาณตามเกณฑ์กระทรวงสาธารณสุข พร้อมกราฟวิเคราะห์แนวโน้มรายเดือนและระบบตั้งค่าจับคู่ผังบัญชี
                    </p>
                    <a href="{{ url('hosfin/ratio_report') }}" class="btn btn-primary rounded-pill px-4 align-self-start shadow-sm mt-auto">
                        เข้าใช้งานระบบ <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Injected variables
    const chartLabels = @json($chartLabels);
    const chartData = @json($chartData);
    const ratioDefs = @json($ratioDefs);
    const statusMap = @json($statusMap);
    
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
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ตัวเลขควรอยู่ในระดับ <strong>0.2 - 0.5 เท่า</strong> หากต่ำกว่า 0.2 เท่า แสดงว่าเงินสดในมือของ รพ. มีน้อยมาก หากเกิดเหตุฉุกเฉินหรือคู่ค้ามาเรียกชำระพร้อมกัน อาจทำให้ รพ. ขาดสภาพคล่องกะทันหัน ควรเพิ่มวินัยการสำรองสัดส่วนเงินสดฝากธนาคารให้อยู่ในเกณฑ์มาตรฐาน'
        },
        '264': {
            desc: '<strong>Inventory Management (ระยะเวลาถือครองสินค้าคงคลังยา)</strong>: วัสดุคงคลังเฉลี่ย ÷ วัสดุใช้ไป (คูณ 300 วัน เพื่อดูระยะเวลาเป็นวัน)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควบคุมให้อยู่ในช่วง <strong>30-45 วัน</strong> หากจำนวนวันเฉลี่ยสูงเกิน 45-60 วัน แปลว่า รพ. สั่งซื้อยาหรือวัสดุการแพทย์มาดองไว้มากเกินจำเป็น ทำให้เงินสดหมุนเวียนไปจมในคลังและเสี่ยงต่อยาหมดอายุ ผู้บริหารควรสั่งการห้องยา/พัสดุให้ปรับลดปริมาณสำรองวัสดุยา (Min Stock) เพื่อระบายเงินสดออกมาหมุนเวียน'
        },
        '261': {
            desc: '<strong>Average Collection Period - UC (วันเก็บหนี้สิทธิบัตรทอง)</strong>: ลูกหนี้ UC เฉลี่ย ÷ รายได้ UC สุทธิ (คูณ 300 วัน เพื่อดูความเร็วการตามเงินจาก สปสช.)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควบคุมให้ต่ำกว่า <strong>30 วัน</strong> หากตัวเลขพุ่งสูง 60-90 วันขึ้นไป สะท้อนว่าหน่วยประกันสุขภาพของ รพ. ส่งเบิกเคลมช้า หรือมีการติดปัญหารหัสสิทธิการรักษาพยาบาลค้างเบิก ผู้บริหารควรสั่งการให้หน่วยเบิกเคลมประกันเร่งส่งข้อมูลและเคลียร์เคสที่ติดขัดโดยด่วน'
        },
        '262': {
            desc: '<strong>Average Collection Period - CSMBS (วันเก็บหนี้สิทธิข้าราชการ)</strong>: ลูกหนี้ CS เฉลี่ย ÷ รายได้ CS สุทธิ (คูณ 300 วัน เพื่อดูความเร็วการตามเงินจ่ายตรงกรมบัญชีกลาง)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควบคุมให้ต่ำกว่า <strong>30 วัน</strong> สิทธิข้าราชการจ่ายตรงควรเก็บเงินได้เร็ว หากตัวเลขสูงเกิน 60 วัน แสดงว่าการเงิน รพ. ล่าช้าในการส่งชุดข้อมูลเบิกจ่าย หรือบันทึกเข้าระบบจ่ายตรงของกรมบัญชีกลาง ควรส่งทีมไอทีตรวจสอบปัญหารหัสเบิกจ่ายสิทธิทางออนไลน์'
        },
        '260': {
            desc: '<strong>Average Payment Period (ระยะเวลาชำระหนี้ค่ายา/เวชภัณฑ์)</strong>: เจ้าหนี้การค้าเฉลี่ย ÷ เจ้าหนี้รวม (คูณ 300 วัน)',
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
        '104': {
            desc: '<strong>Networking Capital (เงินทุนหมุนเวียนสุทธิ)</strong>: สินทรัพย์หมุนเวียน - หนี้สินหมุนเวียน (แสดงกระแสเงินสดสะสมส่วนเกินที่เป็นหน่วยบาท)',
            guide: '💡 <strong>แนวทางสั่งการสำหรับผู้บริหาร:</strong> ควรมีค่าเป็นบวก ยิ่งสูงยิ่งแสดงถึงความปลอดภัยและความคล่องตัวในคลังเงินสด หากตัวเลขเริ่มดิ่งลงหรือติดลบ แสดงว่าหนี้สินระยะสั้นมีมากกว่าทรัพย์สินที่จะแปรสภาพมาจ่ายได้ทัน ควรระงับโครงการก่อสร้างหรืองบจัดซื้อระยะสั้นชั่วคราว'
        }
    };

    let activeChart = null;

    // Handle Card Click Events
    document.querySelectorAll('.metric-card').forEach(card => {
        card.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            
            // Set modal labels
            document.getElementById('trendModalLabel').innerHTML = `<i class="bi bi-graph-up me-2 text-warning"></i> แนวโน้มรายงวดบัญชี: ${name}`;
            
            // Set guide descriptions
            const guide = analysisGuides[code] || { desc: 'ไม่มีคำอธิบายสำหรับรหัสนี้', guide: 'ไม่มีคำแนะนำเพิ่มเติม' };
            document.getElementById('modalGuideDescription').innerHTML = guide.desc;
            document.getElementById('modalGuideAction').innerHTML = guide.guide;

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
                const definition = ratioDefs[code] || { unit: '' };
                
                activeChart = new Chart(ctx, {
                    type: 'line',
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
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString() + ' ' + definition.unit;
                                    }
                                }
                            }
                        }
                    }
                });
            }, 250); // Small timeout to ensure canvas is fully rendered in DOM
        });
    });
</script>
@endif
@endsection
