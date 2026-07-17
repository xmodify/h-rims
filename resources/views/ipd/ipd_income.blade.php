@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Filter -->
    <div class="page-header-box mt-3 mb-4 d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm border-start border-success border-5">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-cash-coin text-success me-2"></i>
                รายได้ตามหมวดค่ารักษา IPD ปีงบประมาณ {{ $budget_year }}
            </h5>
            <div class="text-muted small mt-1">ประมวลผลสำหรับผู้ป่วยใน IPD ตามวันที่จำหน่ายคนไข้</div>
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
                    <button type="submit" class="btn btn-success px-3 ms-2 rounded-pill text-white">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Page Loader (Shown only during loading) -->
    <div id="main-loader" class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body py-5 text-center">
            <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="mt-3 fw-bold text-secondary">กำลังดึงข้อมูลสถิติรายได้ตามหมวดค่ารักษา IPD...</h5>
            <p class="text-muted small mb-0">ตารางสถิตินี้ใช้เวลาในการประมวลผลข้อมูลขนาดใหญ่ประมาณ 5-15 วินาที โปรดรอสักครู่</p>
        </div>
    </div>

    <!-- Chart Card: Monthly Trend by Category -->
    <div id="chart-card-container" class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden; display: none;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up text-success me-2"></i> กราฟแนวโน้มรายเดือนของรายได้ค่ารักษา IPD</h6>
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

    <!-- Table Container (AJAX loaded) -->
    <div id="table-container" class="mt-3"></div>
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
        let chart = null;

        function initChart(chartData, categories) {
            const initialCatId = 'all';
            const initialCatName = 'ทั้งหมด (รายได้รวมทุกหมวด)';

            const chartOptions = {
                series: [{
                    name: 'ยอดรวม IPD (บาท)',
                    data: chartData[initialCatId] || []
                }],
                chart: {
                    height: 280,
                    type: 'area',
                    toolbar: { show: false }
                },
                colors: ['#198754'],
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

            chart = new ApexCharts(document.querySelector("#monthlyTrendChart"), chartOptions);
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
                    name: 'ยอดรวม IPD (บาท)',
                    data: chartData[selectedCatId] || []
                }]);
            });
        }

        // ดึงข้อมูลผ่าน AJAX หลังโหลดหน้าทันที
        fetch("{{ url('ipd/income') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
                budget_year: "{{ $budget_year }}"
            })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                // ซ่อนตัวโหลดด้านบน
                const loader = document.getElementById('main-loader');
                if (loader) loader.style.display = 'none';

                // แสดงกล่องกราฟ
                const chartCard = document.getElementById('chart-card-container');
                if (chartCard) chartCard.style.display = 'block';

                // แทรก HTML ของตาราง
                document.getElementById('table-container').innerHTML = res.table_html;
                
                // วาดกราฟ
                initChart(res.chart_data, res.categories || {});

                // สั่งจัดขนาดกราฟใหม่เวลาสลับแท็บ
                document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
                    tabEl.addEventListener('shown.bs.tab', () => {
                        window.dispatchEvent(new Event('resize'));
                    });
                });
            }
        })
        .catch(err => {
            // ซ่อนตัวโหลดด้านบน
            const loader = document.getElementById('main-loader');
            if (loader) loader.style.display = 'none';

            document.getElementById('table-container').innerHTML = `
                <div class="alert alert-danger rounded-3 p-4 text-center">
                    <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                    <h5 class="mt-2 fw-bold">เกิดข้อผิดพลาดในการโหลดข้อมูล</h5>
                    <p class="mb-0">กรุณาลองกดปุ่มค้นหา/รีเฟรชใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ</p>
                </div>
            `;
        });
    });
</script>
@endpush
