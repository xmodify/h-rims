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
    color: #0284c7;
    border-bottom-color: #0284c7;
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
  .badge-danger {
    background-color: #fee2e2;
    color: #ef4444;
    border: 1px solid #fca5a5;
  }
  .badge-warning {
    background-color: #fef3c7;
    color: #d97706;
    border: 1px solid #fcd34d;
  }
  .badge-success {
    background-color: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
  }
  .card-trend {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  }
  .math-formula {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.85rem;
    color: #475569;
  }
  .ratio-group-header {
    background-color: #f1f5f9 !important;
    color: #1e293b;
    font-weight: 700;
    font-size: 0.95rem;
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
            <div class="page-header-box mt-2" style="border-left-color: #0284c7 !important;">
                <div>
                    <h5 class="text-primary mb-0 fw-bold">
                        <i class="bi bi-graph-up text-info me-2"></i> วิเคราะห์อัตราส่วนทางการเงิน (Ratio Analysis)
                    </h5>
                    <small class="text-muted">คำนวณอัตราส่วนสภาพคล่อง ประสิทธิภาพการดำเนินงาน และกำไรของโรงพยาบาล</small>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <!-- Budget Year Dropdown -->
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted" style="font-size: 0.9rem;">ปีงบประมาณ</span>
                        <select id="select_budget_year" class="form-select" style="min-width: 100px; font-size: 0.9rem;">
                            @php
                                $currentYear = date('Y') + 543;
                                $yearChoices = range($currentYear + 1, $currentYear - 3);
                                rsort($yearChoices);
                            @endphp
                            @foreach($yearChoices as $yr)
                                <option value="{{ $yr }}" {{ $budgetYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Settings button -->
                    <button type="button" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm text-nowrap" onclick="openMappingsModal()">
                        <i class="bi bi-list-ul"></i> ตรวจสอบการจับคู่ผังบัญชี
                    </button>
                </div>
            </div>
        </div>

        <!-- Check if data exists -->
        @if(count($importedPeriods) == 0)
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-5 text-center">
                        <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='100' fill='%2394a3b8' class='bi bi-folder-x' viewBox='0 0 16 16'><path d='M.54 3.87.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3h3.982a2 2 0 0 1 1.992 2.181L15.546 8H14.54l.265-2.91A1 1 0 0 0 13.81 4H9.828a3 3 0 0 1-2.12-.879l-.83-.828A1 1 0 0 0 6.172 2H2.5a1 1 0 0 0-1 .981L1.55 8H.54z'/><path d='M11.5 15a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.707-5.354 1.647 1.646a.5.5 0 0 1-.708.708L11.5 10.707l-1.646 1.647a.5.5 0 0 1-.708-.708l1.647-1.646-1.647-1.646a.5.5 0 0 1 .708-.708l1.646 1.647 1.646-1.647a.5.5 0 0 1 .708.708z'/></svg>" alt="No Data" class="mb-3" style="opacity: 0.5;">
                        <h5 class="text-muted fw-bold">ไม่พบข้อมูลนำเข้างบทดลองในปีงบประมาณ {{ $budgetYear }}</h5>
                        <p class="text-secondary small mb-4">กรุณานำเข้าข้อมูลไฟล์งบทดลองของเดือนที่ต้องการวิเคราะห์ก่อนเรียกรายงาน</p>
                        <a href="{{ url('hosfin/trial_balance') }}?budget_year={{ $budgetYear }}" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> ไปหน้าพัฒนางบทดลองเพื่อนำเข้า
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Dynamic Trend Chart Section -->
            <div class="col-12 mb-4">
                <div class="card card-trend border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up text-primary me-1"></i> กราฟแนวโน้มแสดงความเคลื่อนไหวอัตราส่วนการเงินประจำแต่ละเดือน</h6>
                                <small class="text-muted">วาดแนวโน้มรายเดือนเปรียบเทียบตามช่วงเวลาปีงบประมาณ {{ $budgetYear }}</small>
                            </div>
                            <div style="min-width: 250px;">
                                <select id="selectChartMetric" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                    @foreach($ratios as $code => $r)
                                        <option value="{{ $code }}" {{ $code === '100' ? 'selected' : '' }}>{{ $code }} - {{ $r['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="chart-container" style="position: relative; height:280px; width:100%">
                            <canvas id="ratioTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Tabs & Table -->
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <ul class="nav nav-tabs nav-tabs-custom" id="periodTabs" role="tablist">
                            <!-- All year tab -->
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedPeriod === 'all' ? 'active' : '' }}" 
                                   href="{{ url('hosfin/ratio_report') }}?budget_year={{ $budgetYear }}&period=all">
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
                                       href="{{ url('hosfin/ratio_report') }}?budget_year={{ $budgetYear }}&period={{ $p['period'] }}">
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
                        <!-- Data table layout -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                @if($selectedPeriod === 'all')
                                    <i class="bi bi-calculator me-1 text-primary"></i> รายงานวิเคราะห์อัตราส่วนการเงินสะสมประจำปีงบประมาณ {{ $budgetYear }}
                                @else
                                    <i class="bi bi-calendar3 me-1 text-primary"></i> รายงานวิเคราะห์อัตราส่วนการเงินประจำเดือน {{ collect($periods)->firstWhere('period', $selectedPeriod)['label'] ?? $selectedPeriod }}
                                @endif
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="searchRatioTable" class="form-control" placeholder="ค้นหาอัตราส่วน..." style="font-size: 0.85rem;">
                                </div>
                                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm text-nowrap d-inline-flex align-items-center gap-1" style="flex-shrink: 0;" onclick="exportExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive rounded-3 border">
                            <table id="ratio_report_table" class="table table-hover table-custom mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 80px;">รหัส</th>
                                        <th style="min-width: 250px;">ชื่ออัตราส่วนทางการเงิน</th>
                                        <th style="min-width: 300px;">สูตรการคำนวณและตัวเลขรายละเอียดของยอดเงิน</th>
                                        <th class="text-end" style="width: 150px;">ผลลัพธ์</th>
                                        <th class="text-center" style="width: 100px;">หน่วย</th>
                                        <th class="text-center" style="width: 150px;">เกณฑ์ประเมิน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $categories = [
                                            'LIQUID' => ['label' => '1. อัตราส่วนวิเคราะห์สภาพคล่อง (Liquidity Ratios)', 'codes' => ['100', '101', '102', '103', '104', '105', '105.1']],
                                            'ACTIVITY' => ['label' => '2. อัตราส่วนวิเคราะห์ประสิทธิภาพการดำเนินงาน (Activity Ratios)', 'codes' => ['260', '261', '262', '263', '264']],
                                            'PROFIT' => ['label' => '3. อัตราส่วนวิเคราะห์ความสามารถในการทำกำไร (Profitability Ratios)', 'codes' => ['302', '303', '304', '305', '306', '307', '310', '311', '312', '313', '314', '315', '316', '320', '321', '333', '334']],
                                        ];
                                    @endphp

                                    @foreach($categories as $catKey => $catInfo)
                                        <tr class="ratio-group-header">
                                            <td colspan="6">{{ $catInfo['label'] }}</td>
                                        </tr>
                                        @foreach($catInfo['codes'] as $code)
                                            @if(isset($ratios[$code]))
                                                @php
                                                    $r = $ratios[$code];
                                                    // Determine health check status
                                                    $statusLabel = '-';
                                                    $statusBadge = '';
                                                    if ($code === '100') {
                                                        if ($r['value'] >= 1.5) { $statusLabel = '🟢 ปกติ'; $statusBadge = 'badge-success'; }
                                                        elseif ($r['value'] >= 1.0) { $statusLabel = '🟡 เฝ้าระวัง'; $statusBadge = 'badge-warning'; }
                                                        else { $statusLabel = '🔴 วิกฤต'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '101') {
                                                        if ($r['value'] >= 1.0) { $statusLabel = '🟢 ปกติ'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '104' || $code === '105') {
                                                        if ($r['value'] >= 0) { $statusLabel = '🟢 ปกติ (บวก)'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต (ติดลบ)'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '307' || $code === '320' || $code === '321') {
                                                        if ($r['value'] >= 0) { $statusLabel = '🟢 มีกำไร'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต (ขาดทุน)'; $statusBadge = 'badge-danger'; }
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="text-center fw-bold text-secondary">{{ $code }}</td>
                                                    <td>
                                                        <div class="fw-bold text-dark">{{ $r['name'] }}</div>
                                                        <small class="text-muted math-formula">{{ $r['numerator_name'] }} ÷ {{ $r['denominator_name'] }}</small>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-secondary">{{ $r['numerator_name'] }}:</span>
                                                                <span class="fw-bold text-dark">{{ number_format($r['num_value'], 2) }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-0">
                                                                <span class="text-secondary">{{ $r['denominator_name'] }}:</span>
                                                                <span class="fw-bold text-dark">{{ number_format($r['den_value'], 2) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold text-dark" style="font-size: 1.05rem;">
                                                        {{ number_format($r['value'], $r['precision']) }}
                                                    </td>
                                                    <td class="text-center text-secondary small">{{ $r['unit'] }}</td>
                                                    <td class="text-center">
                                                        @if(!empty($statusBadge))
                                                            <span class="badge px-3 py-2 rounded-pill {{ $statusBadge }}" style="font-size: 0.8rem;">
                                                                {{ $statusLabel }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted small">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mappings Setup Modal -->
<div class="modal fade" id="mappingsModal" tabindex="-1" aria-labelledby="mappingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="mappingsModalLabel">
                    <i class="bi bi-list-ul me-2 text-warning"></i> รายงานการจับคู่ผังบัญชี (Account Mappings Report)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Header Filters and Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 bg-white rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <!-- Group Category Filter -->
                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 500px;">
                            <label class="fw-bold text-dark mb-0 text-nowrap" style="font-size: 0.9rem;"><i class="bi bi-funnel-fill text-primary me-1"></i> กรองตามหมวด/กลุ่มบัญชี:</label>
                            <select id="filter_group_code" class="form-select fw-semibold" style="font-size: 0.9rem;">
                                <!-- Loaded via AJAX -->
                            </select>
                        </div>
                        
                        <!-- Search and Action -->
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="searchMappingQuery" class="form-control border-start-0" placeholder="ค้นหาโดยกลุ่ม รหัสบัญชี...">
                            </div>
                            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm" onclick="printMappings()">
                                <i class="bi bi-printer"></i> พิมพ์รายงาน
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-responsive" style="max-height: 440px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #fff;">
                    <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 120px; padding: 12px 16px;">รหัสกลุ่ม</th>
                                <th style="padding: 12px 16px;">ชื่อกลุ่มรายละเอียดประกอบงบ</th>
                                <th style="width: 180px; padding: 12px 16px;">รหัสผังบัญชี</th>
                                <th style="padding: 12px 16px;">ชื่อผังบัญชี</th>
                            </tr>
                        </thead>
                        <tbody id="mappingsTableBody">
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination controls -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <small class="text-muted fw-semibold" id="mappingPaginationInfo">กำลังโหลด...</small>
                    <nav aria-label="Mapping Pagination">
                        <ul class="pagination pagination-sm mb-0 gap-1" id="mappingPaginationButtons">
                            <!-- Page items loaded via AJAX -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    // Selected budget year change
    document.getElementById('select_budget_year').addEventListener('change', function() {
        window.location.href = "{{ url('hosfin/ratio_report') }}?budget_year=" + this.value + "&period={{ $selectedPeriod }}";
    });

    // Excel Export
    // Excel Export
    function exportExcel() {
        const periodLabel = "{{ $selectedPeriod === 'all' ? 'รวมทั้งปี' : (collect($periods)->firstWhere('period', $selectedPeriod)['label'] ?? $selectedPeriod) }}";
        const filename = `วิเคราะห์อัตราส่วนการเงินประจำปีงบประมาณ_${{ $budgetYear }}_${periodLabel}`;
        exportTableToExcel('ratio_report_table', filename);
    }

    function exportTableToExcel(tableId, filename = '') {
        if (typeof XLSX === 'undefined') {
            let script = document.createElement('script');
            script.src = "{{ asset('assets/vendor/xlsx.full.min.js') }}";
            script.onload = function() {
                doExportTableToExcel(tableId, filename);
            };
            document.head.appendChild(script);
        } else {
            doExportTableToExcel(tableId, filename);
        }
    }

    function doExportTableToExcel(tableId, filename) {
        var dataType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8';
        var tableSelect = document.getElementById(tableId);
        var wb = XLSX.utils.table_to_book(tableSelect, {sheet: "Sheet1"});
        var wbout = XLSX.write(wb, {bookType: 'xlsx', type: 'binary'});
        
        function s2ab(s) {
            var buf = new ArrayBuffer(s.length);
            var view = new Uint8Array(buf);
            for (var i=0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
            return buf;
        }
        
        var blob = new Blob([s2ab(wbout)], {type: dataType});
        filename = filename ? filename + '.xlsx' : 'excel_data.xlsx';
        
        var downloadLink = document.createElement("a");
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = filename;
        downloadLink.click();
    }

    // Chart.js Configuration
    @if(count($importedPeriods) > 0)
        const chartDataMap = @json($chartData);
        const chartLabels = Object.keys(chartDataMap); // Monthly period labels
        
        let chart = null;

        // Custom plugin to draw vertical dashed line on hover
        const hoverLinePlugin = {
            id: 'hoverLine',
            afterDraw: (chartInstance) => {
                if (chartInstance.tooltip?._active?.length) {
                    const activePoint = chartInstance.tooltip._active[0];
                    const ctx = chartInstance.ctx;
                    const x = activePoint.element.x;
                    const topY = chartInstance.scales.y.top;
                    const bottomY = chartInstance.scales.y.bottom;
                    
                    ctx.save();
                    ctx.beginPath();
                    ctx.moveTo(x, topY);
                    ctx.lineTo(x, bottomY);
                    ctx.lineWidth = 1.5;
                    ctx.strokeStyle = '#64748b'; // Slate color
                    ctx.setLineDash([3, 3]); // Dashed line
                    ctx.stroke();
                    ctx.restore();
                }
            }
        };

        function updateChart(metricCode) {
            const dataValues = chartLabels.map(label => chartDataMap[label][metricCode] || 0.0);
            
            // Get metric name from dropdown selection
            const dropdown = document.getElementById('selectChartMetric');
            const metricName = dropdown.options[dropdown.selectedIndex].text;

            const ctx = document.getElementById('ratioTrendChart').getContext('2d');
            
            if (chart) {
                chart.destroy();
            }

            chart = new Chart(ctx, {
                type: 'line',
                plugins: [ChartDataLabels, hoverLinePlugin],
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: metricName,
                        data: dataValues,
                        borderWidth: 3.5,
                        // Fill positive area with green, negative area with red
                        fill: {
                            target: 'origin',
                            above: 'rgba(16, 185, 129, 0.15)', // Green above 0
                            below: 'rgba(239, 68, 68, 0.15)'  // Red below 0
                        },
                        tension: 0.35,
                        // Points background color: Green if positive, Red if negative
                        pointBackgroundColor: (context) => {
                            const val = context.raw ?? 0;
                            return val < 0 ? '#ef4444' : '#10b981';
                        },
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        // Line segment border color: Green for positive segment, Red for negative segment
                        segment: {
                            borderColor: (ctxSegment) => {
                                const val = ctxSegment.p0.parsed.y;
                                return val < 0 ? '#ef4444' : '#10b981';
                            }
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    layout: {
                        padding: {
                            top: 24,
                            bottom: 12,
                            left: 10,
                            right: 15
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': ' + context.raw.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        },
                        datalabels: {
                            // Align above the line for positive values, below for negative values
                            align: function(context) {
                                return context.dataset.data[context.dataIndex] < 0 ? 'bottom' : 'top';
                            },
                            anchor: 'end',
                            // Dynamic dataLabel background: Green if positive, Red if negative
                            backgroundColor: function(context) {
                                const val = context.dataset.data[context.dataIndex];
                                return val < 0 ? '#ef4444' : '#10b981';
                            },
                            borderRadius: 4,
                            color: 'white',
                            font: {
                                weight: 'bold',
                                size: 9
                            },
                            formatter: function(value) {
                                if (Math.abs(value) < 50) {
                                    return Number(value).toFixed(2);
                                } else {
                                    return Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                            },
                            padding: {
                                top: 3.5,
                                bottom: 3.5,
                                left: 6,
                                right: 6
                            }
                        }
                    },
                    scales: {
                        y: {
                            grid: {
                                color: function(context) {
                                    if (context.tick && context.tick.value === 0) {
                                        return '#475569'; // Bold slate-grey line at Y = 0
                                    }
                                    return '#f1f5f9';
                                },
                                lineWidth: function(context) {
                                    if (context.tick && context.tick.value === 0) {
                                        return 2.5; // Thicker line at Y = 0
                                    }
                                    return 1;
                                }
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11 },
                                callback: function(value) {
                                    if (Math.abs(value) >= 1000000) {
                                        return (value / 1000000).toFixed(1) + 'M';
                                    }
                                    if (Math.abs(value) >= 1000) {
                                        return (value / 1000).toFixed(0) + 'K';
                                    }
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11, weight: '500' }
                            }
                        }
                    }
                }
            });
        }

        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateChart('100'); // Default to Current Ratio
        });

        // Update chart when dropdown selection changes
        document.getElementById('selectChartMetric').addEventListener('change', function() {
            updateChart(this.value);
        });

        // Search Ratio Table client-side filter
        const searchInput = document.getElementById('searchRatioTable');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const table = document.querySelector('.table-custom');
                if (!table) return;

                const rows = table.querySelectorAll('tbody tr');
                let currentHeaderRow = null;
                let visibleRowsInGroup = 0;

                rows.forEach(row => {
                    if (row.classList.contains('ratio-group-header')) {
                        if (currentHeaderRow) {
                            currentHeaderRow.style.display = visibleRowsInGroup === 0 ? 'none' : '';
                        }
                        currentHeaderRow = row;
                        visibleRowsInGroup = 0;
                        return;
                    }

                    const codeCell = row.querySelector('td:first-child');
                    const code = codeCell ? codeCell.innerText.toLowerCase() : '';
                    
                    const nameDiv = row.querySelector('td:nth-child(2) div');
                    const name = nameDiv ? nameDiv.innerText.toLowerCase() : '';
                    
                    const formulaSmall = row.querySelector('td:nth-child(2) small');
                    const formula = formulaSmall ? formulaSmall.innerText.toLowerCase() : '';

                    if (code.includes(query) || name.includes(query) || formula.includes(query)) {
                        row.style.display = '';
                        visibleRowsInGroup++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (currentHeaderRow) {
                    currentHeaderRow.style.display = visibleRowsInGroup === 0 ? 'none' : '';
                }
            });
        }
    @endif

    // Mappings Viewer AJAX pagination and print
    let currentMappingPage = 1;
    let searchMappingTimeout = null;

    function openMappingsModal() {
        $('#mappingsModal').modal('show');
        loadGroupDropdownAndMappings(1);
    }

    function loadGroupDropdownAndMappings(page = 1) {
        currentMappingPage = page;
        const groupSelect = document.getElementById('filter_group_code');
        const selectedGroup = groupSelect ? groupSelect.value : '';
        const q = document.getElementById('searchMappingQuery').value.trim();
        
        const tbody = document.getElementById('mappingsTableBody');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดข้อมูล...</td></tr>`;

        fetch(`{{ url('hosfin/mappings') }}?page=${page}&group_code=${selectedGroup}&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Populate Group dropdown if empty
                    if (groupSelect.children.length === 0) {
                        let optionsHtml = '<option value="">-- แสดงทั้งหมด (Show All) --</option>';
                        data.groups.forEach(g => {
                            optionsHtml += `<option value="${g.group_code}">${g.group_code} - ${g.group_name}</option>`;
                        });
                        groupSelect.innerHTML = optionsHtml;
                    }
                    
                    // Render mappings
                    if (data.mappings.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">ไม่พบข้อมูลการจับคู่ผังบัญชี</td></tr>`;
                    } else {
                        let rowsHtml = '';
                        data.mappings.forEach(row => {
                            const accName = row.account_name ? row.account_name : '-';
                            rowsHtml += `
                                <tr>
                                    <td class="fw-semibold text-secondary" style="padding: 10px 16px;">${row.group_code}</td>
                                    <td class="text-dark" style="padding: 10px 16px;">${row.group_name}</td>
                                    <td class="fw-bold text-dark" style="padding: 10px 16px;">${row.account_code}</td>
                                    <td class="text-secondary" style="padding: 10px 16px;">${accName}</td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = rowsHtml;
                    }

                    // Render pagination stats
                    document.getElementById('mappingPaginationInfo').innerText = `แสดงรายการที่ ${data.mappings.length > 0 ? (data.current_page - 1) * 25 + 1 : 0} ถึง ${(data.current_page - 1) * 25 + data.mappings.length} จากทั้งหมด ${data.total} รายการ`;

                    // Render page numbers like a real datatable
                    renderPaginationButtons(data.current_page, data.last_page);
                }
            });
    }

    function renderPaginationButtons(currentPage, lastPage) {
        const container = document.getElementById('mappingPaginationButtons');
        let html = '';

        if (lastPage <= 1) {
            container.innerHTML = '';
            return;
        }

        // Previous page button
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(${currentPage - 1})" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </button>
            </li>
        `;

        // Page number buttons
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(lastPage, currentPage + 2);

        if (startPage > 1) {
            html += `<li class="page-item"><button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(1)">1</button></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(${i})">${i}</button>
                </li>
            `;
        }

        if (endPage < lastPage) {
            if (endPage < lastPage - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(${lastPage})">${lastPage}</button></li>`;
        }

        // Next page button
        html += `
            <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                <button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(${currentPage + 1})" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </button>
            </li>
        `;

        container.innerHTML = html;
    }

    function printMappings() {
        const groupSelect = document.getElementById('filter_group_code');
        const selectedGroup = groupSelect.value;
        const groupText = selectedGroup ? groupSelect.options[groupSelect.selectedIndex].text : 'ทั้งหมด';
        const q = document.getElementById('searchMappingQuery').value.trim();

        // Load all matching items (without pagination) using print=1 parameter
        fetch(`{{ url('hosfin/mappings') }}?print=1&group_code=${selectedGroup}&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let printWindow = window.open('', '_blank');
                    let html = `
                        <html>
                        <head>
                            <title>รายงานการจับคู่ผังบัญชี - กลุ่ม: ${groupText}</title>
                            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
                            <style>
                                @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap');
                                body { font-family: 'Sarabun', sans-serif; padding: 40px; background-color: #fff; }
                                th, td { padding: 10px 14px; font-size: 0.95rem; }
                                h3 { font-weight: 700; color: #0d6efd; }
                                @media print {
                                    .no-print { display: none !important; }
                                    body { padding: 0; }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3>รายงานการจับคู่ผังบัญชี (Account Mappings Report)</h3>
                                <button class="btn btn-primary no-print" onclick="window.print()"><i class="bi bi-printer"></i> พิมพ์ (Print)</button>
                            </div>
                            <div class="mb-4">
                                <p class="mb-1 text-muted"><strong>ตัวกรองกลุ่มบัญชี:</strong> ${groupText}</p>
                                ${q ? `<p class="mb-1 text-muted"><strong>คำค้นหา:</strong> ${q}</p>` : ''}
                                <p class="mb-0 text-muted">พิมพ์ ณ วันที่: ${new Date().toLocaleDateString('th-TH')} เวลา ${new Date().toLocaleTimeString('th-TH')} | จำนวนทั้งหมด: ${data.mappings.length} รายการ</p>
                            </div>
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 120px;">รหัสกลุ่ม</th>
                                        <th>ชื่อกลุ่มรายละเอียดประกอบงบ</th>
                                        <th style="width: 200px;">รหัสผังบัญชี</th>
                                        <th>ชื่อผังบัญชี</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    if (data.mappings.length === 0) {
                        html += `<tr><td colspan="4" class="text-center py-4">ไม่พบข้อมูลการจับคู่ผังบัญชี</td></tr>`;
                    } else {
                        data.mappings.forEach(row => {
                            const accName = row.account_name ? row.account_name : '-';
                            html += `
                                <tr>
                                    <td class="fw-semibold">${row.group_code}</td>
                                    <td>${row.group_name}</td>
                                    <td class="fw-bold">${row.account_code}</td>
                                    <td>${accName}</td>
                                </tr>
                            `;
                        });
                    }
                    
                    html += `
                                </tbody>
                            </table>
                        </body>
                        </html>
                    `;
                    printWindow.document.write(html);
                    printWindow.document.close();
                }
            });
    }

    // Bind event listeners
    document.getElementById('filter_group_code').addEventListener('change', function() {
        loadGroupDropdownAndMappings(1);
    });

    document.getElementById('searchMappingQuery').addEventListener('input', function() {
        clearTimeout(searchMappingTimeout);
        searchMappingTimeout = setTimeout(() => {
            loadGroupDropdownAndMappings(1);
        }, 300);
    });
</script>
@endsection
