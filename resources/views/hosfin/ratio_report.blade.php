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

<div class="container-fluid py-4 px-lg-5" style="background-color: #f8fafc;">
    <div class="row">
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
                        <i class="bi bi-gear"></i> ตั้งค่าจับคู่ผังบัญชี
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
                    <i class="bi bi-gear me-2 text-warning"></i> ตั้งค่าจับคู่ผังบัญชีรายละเอียดประกอบงบ (Account Mappings Setup)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <div class="row g-4">
                    <!-- Left pane: Add Mapping Form -->
                    <div class="col-lg-4 col-12">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-plus-circle text-success me-1"></i> เพิ่มการจับคู่รหัสผังบัญชีใหม่</h6>
                                <form id="addMappingForm">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-secondary">กลุ่มรายละเอียดประกอบงบ</label>
                                        <select id="add_group_code" class="form-select" required style="font-size: 0.9rem;">
                                            <!-- Loaded via AJAX -->
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-secondary">รหัสผังบัญชี (เช่น 1101030102.106)</label>
                                        <input type="text" id="add_account_code" class="form-control" placeholder="ระบุรหัสผังบัญชีในงบทดลอง" required style="font-size: 0.9rem;">
                                        <small class="text-muted" style="font-size: 0.75rem;">ระบุเป็นรหัสบัญชีหลักหรือรหัสย่อยก็ได้ ระบบจะเทียบตามรหัสที่ขึ้นต้น</small>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-semibold mt-2 shadow-sm">
                                        <i class="bi bi-plus-circle me-1"></i> บันทึกการจับคู่
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right pane: Search and List mappings -->
                    <div class="col-lg-8 col-12">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-ul text-primary me-1"></i> รายการจับคู่ผังบัญชีที่บันทึกแล้ว</h6>
                                    <div style="min-width: 250px;">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" id="searchMappingQuery" class="form-control" placeholder="ค้นหาโดยกลุ่ม รหัสบัญชี...">
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-hover align-middle" style="font-size: 0.85rem;">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th style="width: 100px;">รหัสกลุ่ม</th>
                                                <th>ชื่อกลุ่มรายละเอียดประกอบงบ</th>
                                                <th>รหัสผังบัญชี</th>
                                                <th class="text-center" style="width: 60px;">ลบ</th>
                                            </tr>
                                        </thead>
                                        <tbody id="mappingsTableBody">
                                            <!-- Loaded via AJAX -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination info and controls -->
                                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                    <small class="text-muted" id="mappingPaginationInfo">กำลังโหลด...</small>
                                    <div class="d-flex gap-1" id="mappingPaginationButtons">
                                        <!-- Buttons -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

    // Account Mappings Setup AJAX CRUD Logic
    let currentMappingPage = 1;
    let searchMappingTimeout = null;

    function openMappingsModal() {
        $('#mappingsModal').modal('show');
        loadMappings(1);
    }

    function loadMappings(page = 1) {
        currentMappingPage = page;
        const q = document.getElementById('searchMappingQuery').value;
        
        fetch(`{{ url('hosfin/mappings') }}?page=${page}&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Populate Group dropdown if empty
                    const selectGroup = document.getElementById('add_group_code');
                    if (selectGroup.children.length === 0) {
                        let optionsHtml = '';
                        data.groups.forEach(g => {
                            optionsHtml += `<option value="${g.group_code}">${g.group_code} - ${g.group_name}</option>`;
                        });
                        selectGroup.innerHTML = optionsHtml;
                    }

                    // Populate mappings list table
                    const tbody = document.getElementById('mappingsTableBody');
                    if (data.mappings.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">ไม่พบข้อมูลความสัมพันธ์การจับคู่</td></tr>`;
                    } else {
                        let rowsHtml = '';
                        data.mappings.forEach(row => {
                            rowsHtml += `
                                <tr>
                                    <td class="fw-semibold text-secondary">${row.group_code}</td>
                                    <td class="text-dark">${row.group_name}</td>
                                    <td class="fw-bold text-dark">${row.account_code}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="deleteMapping('${row.group_code}', '${row.account_code}')">
                                            <i class="bi bi-trash-fill" style="font-size: 1.1rem;"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = rowsHtml;
                    }

                    // Pagination controls
                    document.getElementById('mappingPaginationInfo').innerText = `แสดงหน้า ${data.current_page} จากทั้งหมด ${data.last_page} หน้า (ทั้งหมด ${data.total} รายการ)`;
                    
                    let paginationButtonsHtml = '';
                    if (data.current_page > 1) {
                        paginationButtonsHtml += `<button type="button" class="btn btn-xs btn-outline-secondary" onclick="loadMappings(${data.current_page - 1})">ก่อนหน้า</button>`;
                    }
                    if (data.current_page < data.last_page) {
                        paginationButtonsHtml += `<button type="button" class="btn btn-xs btn-outline-secondary" onclick="loadMappings(${data.current_page + 1})">ถัดไป</button>`;
                    }
                    document.getElementById('mappingPaginationButtons').innerHTML = paginationButtonsHtml;
                }
            });
    }

    // Debounce search mapping inputs
    document.getElementById('searchMappingQuery').addEventListener('input', function() {
        clearTimeout(searchMappingTimeout);
        searchMappingTimeout = setTimeout(() => {
            loadMappings(1);
        }, 300);
    });

    // Add mapping override submission
    document.getElementById('addMappingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const group_code = document.getElementById('add_group_code').value;
        const account_code = document.getElementById('add_account_code').value.trim();

        fetch('{{ url("hosfin/mappings/store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ group_code, account_code })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'บันทึกสำเร็จ',
                    text: data.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                document.getElementById('add_account_code').value = '';
                loadMappings(1);
                
                // Reload ratio calculations on background
                // We reload page when modal closes or immediately
                // Let's reload page when they exit modal to reflect new calculations
                document.getElementById('mappingsModal').addEventListener('hidden.bs.modal', function () {
                    window.location.reload();
                }, { once: true });
            } else {
                Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
            }
        });
    });

    // Delete mapping override
    function deleteMapping(group_code, account_code) {
        Swal.fire({
            title: 'ยืนยันการลบการจับคู่?',
            text: `ต้องการยกเลิกรหัสผังบัญชี ${account_code} ออกจากกลุ่ม ${group_code} ใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'ยืนยัน ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ url("hosfin/mappings/delete") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ group_code, account_code })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'ลบสำเร็จ',
                            text: data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadMappings(currentMappingPage);
                        
                        document.getElementById('mappingsModal').addEventListener('hidden.bs.modal', function () {
                            window.location.reload();
                        }, { once: true });
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
