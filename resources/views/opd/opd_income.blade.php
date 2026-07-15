@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Filter -->
    <div class="page-header-box mt-3 mb-4 d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm border-start border-primary border-5">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-cash-coin text-primary me-2"></i>
                รายได้ตามหมวดค่ารักษา OPD ปีงบประมาณ {{ $budget_year }}
            </h5>
            <div class="text-muted small mt-1">ประมวลผลสำหรับผู้ป่วยนอก OPD ตามวันที่รับบริการ</div>
        </div>
        
        <div class="d-flex align-items-center">
            <form method="POST" enctype="multipart/form-data" class="m-0">
                @csrf
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-check"></i></span>
                    <select class="form-select border-start-0" name="budget_year" style="min-width: 150px; border-radius: 0 8px 8px 0;">
                        @foreach ($budget_year_select as $row)
                            <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                {{ $row->LEAVE_YEAR_NAME }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary px-3 ms-2 rounded-pill">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Chart Card: Monthly Trend by Category -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up text-primary me-2"></i> กราฟแนวโน้มรายเดือนของรายได้ค่ารักษา</h6>
            <div style="width: 250px;">
                <select id="categorySelector" class="form-select form-select-sm">
                    <option value="all">ทั้งหมด (รายได้รวมทุกหมวด)</option>
                    @foreach($categories as $catId => $cat)
                        <option value="{{ $catId }}">{{ $cat->group_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            <div id="monthlyTrendChart" style="height: 280px;"></div>
        </div>
    </div>

    <!-- Month Tabs Navigation -->
    <div class="col-12 px-0 mt-3">
        <ul class="nav nav-tabs custom-ipd-tabs border-0 shadow-sm" id="monthTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-yearly" data-bs-toggle="tab" data-bs-target="#content-yearly" type="button" role="tab" aria-controls="content-yearly" aria-selected="true">
                    <i class="bi bi-calendar-range-fill me-1"></i> รวมทั้งปี
                </button>
            </li>
            @foreach ($months_list as $m)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-{{ $m }}" data-bs-toggle="tab" data-bs-target="#content-{{ $m }}" type="button" role="tab" aria-controls="content-{{ $m }}" aria-selected="false">
                        {{ $months_names[$m] }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content col-12 px-0 mt-3" id="monthTabsContent">
        <!-- TAB: รวมทั้งปี -->
        <div class="tab-pane fade show active" id="content-yearly" role="tabpanel" aria-labelledby="tab-yearly">
            <div class="card shadow-sm border-0" style="border-radius: 12px; border-top: 4px solid #0d6efd !important; overflow: hidden;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-grid-3x3-gap-fill me-2"></i> รายได้ตามหมวดค่ารักษา OPD ยอดรวมทั้งปี</h6>
                    <button onclick="exportTableToExcel('table-yearly', 'รายได้ OPD หมวดค่ารักษา ยอดรวมทั้งปี')" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive w-100">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-yearly" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>กลุ่มหมวดค่ารักษา</th>
                                    <th>UCS</th>
                                    <th>กรมบัญชีกลาง</th>
                                    <th>อปท.</th>
                                    <th>ต้นสังกัด</th>
                                    <th>ประกันสังคม</th>
                                    <th>ต่างด้าว</th>
                                    <th>อื่นๆ</th>
                                    <th>ยอดรวม (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $sums = ['ucs'=>0, 'ofc'=>0, 'lgo'=>0, 'gov'=>0, 'sss'=>0, 'immigrant'=>0, 'others'=>0, 'total'=>0];
                                @endphp
                                @foreach($yearly_data as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row->group_id }}. {{ $row->group_name }}</td>
                                    <td align="right">{{ number_format($row->ucs, 2) }}</td>
                                    <td align="right">{{ number_format($row->ofc, 2) }}</td>
                                    <td align="right">{{ number_format($row->lgo, 2) }}</td>
                                    <td align="right">{{ number_format($row->gov, 2) }}</td>
                                    <td align="right">{{ number_format($row->sss, 2) }}</td>
                                    <td align="right">{{ number_format($row->immigrant, 2) }}</td>
                                    <td align="right">{{ number_format($row->others, 2) }}</td>
                                    <td align="right" class="fw-bold text-primary">{{ number_format($row->total, 2) }}</td>
                                </tr>
                                @php
                                    $sums['ucs'] += $row->ucs; $sums['ofc'] += $row->ofc; $sums['lgo'] += $row->lgo; $sums['gov'] += $row->gov;
                                    $sums['sss'] += $row->sss; $sums['immigrant'] += $row->immigrant; $sums['others'] += $row->others; $sums['total'] += $row->total;
                                @endphp
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold text-end">
                                    <td align="center">รวมทั้งหมด</td>
                                    <td>{{ number_format($sums['ucs'], 2) }}</td>
                                    <td>{{ number_format($sums['ofc'], 2) }}</td>
                                    <td>{{ number_format($sums['lgo'], 2) }}</td>
                                    <td>{{ number_format($sums['gov'], 2) }}</td>
                                    <td>{{ number_format($sums['sss'], 2) }}</td>
                                    <td>{{ number_format($sums['immigrant'], 2) }}</td>
                                    <td>{{ number_format($sums['others'], 2) }}</td>
                                    <td class="text-primary">{{ number_format($sums['total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: รายเดือนแต่ละเดือน -->
        @foreach ($months_list as $m)
        <div class="tab-pane fade" id="content-{{ $m }}" role="tabpanel" aria-labelledby="tab-{{ $m }}">
            <div class="card shadow-sm border-0" style="border-radius: 12px; border-top: 4px solid #198754 !important; overflow: hidden;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3">
                    <h6 class="mb-0 fw-bold text-success"><i class="bi bi-grid-3x3-gap-fill me-2"></i> รายได้ตามหมวดค่ารักษา OPD ประจำเดือน {{ $months_names[$m] }}</h6>
                    <button onclick="exportTableToExcel('table-{{ $m }}', 'รายได้ OPD หมวดค่ารักษา เดือน {{ $months_names[$m] }}')" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive w-100">
                        <table class="table table-hover align-middle mb-0 w-100" id="table-{{ $m }}" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>กลุ่มหมวดค่ารักษา</th>
                                    <th>UCS</th>
                                    <th>กรมบัญชีกลาง</th>
                                    <th>อปท.</th>
                                    <th>ต้นสังกัด</th>
                                    <th>ประกันสังคม</th>
                                    <th>ต่างด้าว</th>
                                    <th>อื่นๆ</th>
                                    <th>ยอดรวม (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $m_sums = ['ucs'=>0, 'ofc'=>0, 'lgo'=>0, 'gov'=>0, 'sss'=>0, 'immigrant'=>0, 'others'=>0, 'total'=>0];
                                @endphp
                                @foreach($report_data[$m] as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row->group_id }}. {{ $row->group_name }}</td>
                                    <td align="right">{{ number_format($row->ucs, 2) }}</td>
                                    <td align="right">{{ number_format($row->ofc, 2) }}</td>
                                    <td align="right">{{ number_format($row->lgo, 2) }}</td>
                                    <td align="right">{{ number_format($row->gov, 2) }}</td>
                                    <td align="right">{{ number_format($row->sss, 2) }}</td>
                                    <td align="right">{{ number_format($row->immigrant, 2) }}</td>
                                    <td align="right">{{ number_format($row->others, 2) }}</td>
                                    <td align="right" class="fw-bold text-success">{{ number_format($row->total, 2) }}</td>
                                </tr>
                                @php
                                    $m_sums['ucs'] += $row->ucs; $m_sums['ofc'] += $row->ofc; $m_sums['lgo'] += $row->lgo; $m_sums['gov'] += $row->gov;
                                    $m_sums['sss'] += $row->sss; $m_sums['immigrant'] += $row->immigrant; $m_sums['others'] += $row->others; $m_sums['total'] += $row->total;
                                @endphp
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold text-end">
                                    <td align="center">รวมทั้งหมด</td>
                                    <td>{{ number_format($m_sums['ucs'], 2) }}</td>
                                    <td>{{ number_format($m_sums['ofc'], 2) }}</td>
                                    <td>{{ number_format($m_sums['lgo'], 2) }}</td>
                                    <td>{{ number_format($m_sums['gov'], 2) }}</td>
                                    <td>{{ number_format($m_sums['sss'], 2) }}</td>
                                    <td>{{ number_format($m_sums['immigrant'], 2) }}</td>
                                    <td>{{ number_format($m_sums['others'], 2) }}</td>
                                    <td class="text-success">{{ number_format($m_sums['total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
    // Excel Exporter Function
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

    document.addEventListener("DOMContentLoaded", () => {
        const monthsNames = <?php echo json_encode(array_values($months_names)); ?>;
        const chartData = <?php echo json_encode($chart_data); ?>;
        const categories = <?php echo json_encode($categories); ?>;

        // กำหนดค่าหมวดเริ่มต้นเป็น 'all' (รายได้รวมทุกหมวด)
        const initialCatId = 'all';
        const initialCatName = 'ทั้งหมด (รายได้รวมทุกหมวด)';

        const chartOptions = {
            series: [{
                name: 'ยอดรวม (บาท)',
                data: chartData[initialCatId] || []
            }],
            chart: {
                height: 280,
                type: 'area',
                toolbar: { show: false }
            },
            colors: ['#0d6efd'],
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " บาท";
                    }
                }
            },
            stroke: { curve: 'smooth', width: 3 },
            markers: { size: 4 },
            xaxis: {
                categories: monthsNames
            },
            title: {
                text: initialCatName,
                align: 'left',
                style: {
                    fontSize: '14px',
                    fontWeight: 'bold',
                    color: '#475569'
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#monthlyTrendChart"), chartOptions);
        chart.render();

        // ตัวเปลี่ยนหมวดค่ารักษาในการแสดงกราฟ
        document.getElementById('categorySelector').addEventListener('change', function() {
            const selectedCatId = this.value;
            const selectedCatName = selectedCatId === 'all' ? 'ทั้งหมด (รายได้รวมทุกหมวด)' : (categories[selectedCatId] ? categories[selectedCatId].group_name : '');
            
            chart.updateOptions({
                title: {
                    text: selectedCatName
                }
            });

            chart.updateSeries([{
                name: 'ยอดรวม (บาท)',
                data: chartData[selectedCatId] || []
            }]);
        });

        // สั่งกระตุ้นการจัดโครงขนาดกราฟเมื่อมีการเปลี่ยนแท็บ
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', () => {
                window.dispatchEvent(new Event('resize'));
            });
        });
    });
</script>
@endpush
