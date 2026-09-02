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
        <div class="col-12 px-3 mb-4">
            <div class="page-header-box mt-2" style="border-left-color: #10b981 !important; background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%); padding: 18px 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 w-100">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="text-primary mb-1 fw-bold">
                                <i class="bi bi-bank me-2 text-success"></i> ข้อมูลบัญชีหน่วยงาน (HosFin Dashboard)
                            </h5>
                            <small class="text-muted d-inline-flex align-items-center gap-2">
                                @if($hasData)
                                    <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 0.75rem; height: 0.75rem;"></span>
                                    ข้อมูลล่าสุด ณ งวดบัญชี: <strong class="text-dark">{{ $latestPeriodLabel }}</strong>
                                @else
                                    ศูนย์รวมรายงานสถานะทางการเงินและวิเคราะห์ต้นทุนการรักษาพยาบาล
                                @endif
                            </small>
                        </div>
                        
                        @if($hasData)
                            @php
                                $val105 = $latestMetrics['105']['val'];
                                $isPositive105 = $val105 >= 0;
                                $bgClass105 = $isPositive105 ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10';
                                $borderClass105 = $isPositive105 ? 'border-success-subtle' : 'border-danger-subtle';
                                $textClass105 = $isPositive105 ? 'text-success-custom' : 'text-danger';
                                $label105 = $isPositive105 ? 'ปกติ (บวก)' : 'วิกฤต (ติดลบ)';
                            @endphp
                            <!-- Net Cash Balance Display (105) -->
                            <div class="d-flex align-items-center rounded-3 shadow-sm border metric-card {{ $bgClass105 }} {{ $borderClass105 }} px-3" 
                                 style="border-width: 1px !important; height: 48px; cursor: pointer; gap: 6px;"
                                 data-code="105" data-name="เงินบำรุงคงเหลือสุทธิ (105)">
                                <span class="text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">เงินบำรุงคงเหลือสุทธิ:</span>
                                <div class="fw-bold {{ $textClass105 }}" style="font-size: 1.15rem; font-family: monospace; line-height: 1.1; font-weight: 800;">
                                    {{ number_format($val105, 2) }} บาท
                                </div>
                            </div>
                            
                            <!-- Risk Score Display -->
                            <div class="d-flex align-items-center rounded-3 shadow-sm border metric-card {{ $riskScoreBgClass }} px-3" 
                                 style="border-width: 1px !important; height: 48px; cursor: pointer; gap: 8px;"
                                 data-code="RISK_SCORE" data-name="RISK SCORE (คะแนนความเสี่ยงทางการเงิน)">
                                <span class="text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">RISK SCORE:</span>
                                <div class="fw-bold rounded {{ $riskScoreNumBgClass }} {{ $riskScoreTextClass }}" style="font-size: 1.6rem; font-family: monospace; line-height: 1.1; padding: 2px 8px; font-weight: 900;">
                                    {{ $riskScore }}
                                </div>
                            </div>
                        @endif
                    </div>
                    @if($hasData)
                        <div class="d-flex align-items-center gap-2 ms-lg-auto">
                            <!-- Action Buttons -->
                            @if(\App\Services\Ai\AiService::isActive())
                                <button type="button" class="btn rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm btn-nav-custom text-white" 
                                        onclick="openHosFinAiModal()"
                                        style="font-size: 0.85rem; height: 48px; font-weight: 700; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                                    <i class="bi bi-robot fs-5"></i> AI วิเคราะห์วิกฤต & แนวโน้ม
                                </button>
                            @endif
                            <a href="{{ url('hosfin/trial_balance') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm btn-nav-custom btn-tb-custom" 
                               style="font-size: 0.85rem; height: 48px; font-weight: 700; background: #ffffff; border: 1.5px solid #10b981; color: #059669; transition: all 0.25s ease;">
                                <i class="bi bi-file-earmark-spreadsheet text-success" style="font-size: 1.1rem;"></i> งบทดลอง
                            </a>
                            <a href="{{ url('hosfin/ratio_report') }}" class="btn rounded-pill px-3 d-flex align-items-center gap-2 shadow-sm btn-nav-custom btn-rr-custom" 
                               style="font-size: 0.85rem; height: 48px; font-weight: 700; background: #ffffff; border: 1.5px solid #3b82f6; color: #2563eb; transition: all 0.25s ease;">
                                <i class="bi bi-graph-up-arrow text-primary" style="font-size: 1.1rem;"></i> อัตราส่วนการเงิน
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
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
            
            // Get latest month data
            const definition = ratioDefs[code] || { numerator_name: '', denominator_name: '', unit: '', precision: 2 };
            const numVal = latestMetrics[code]['num'];
            const denVal = latestMetrics[code]['den'];
            const resVal = latestMetrics[code]['val'];

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
        if (typeof toggleAiChat === 'function') {
            toggleAiChat();
        }
    }
</script>

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
@endsection
