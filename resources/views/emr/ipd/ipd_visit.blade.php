@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Filter -->
    <div class="page-header-box mt-3 mb-4 d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm border-start border-primary border-5">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                สถิติผู้ป่วยใน ปีงบประมาณ {{ $budget_year }}
            </h5>
            <div class="text-muted small mt-1">ประมวลผลตาม เดือนที่จำหน่าย (Discharge Date)</div>
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

    <!-- IPD Tabs Navigation -->
    <div class="col-12 px-0 mt-3">
        <ul class="nav nav-tabs custom-ipd-tabs border-0 shadow-sm" id="ipdTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-content" type="button" role="tab" aria-controls="all-content" aria-selected="true">
                    <i class="bi bi-people-fill text-danger me-2"></i> ผู้ป่วยในรวม
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="normal-tab" data-bs-toggle="tab" data-bs-target="#normal-content" type="button" role="tab" aria-controls="normal-content" aria-selected="false">
                    <i class="bi bi-building text-secondary me-2"></i> ทั่วไป
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-content" type="button" role="tab" aria-controls="home-content" aria-selected="false">
                    <i class="bi bi-star-fill text-warning me-2"></i> Homeward
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content col-12 px-0 mt-3" id="ipdTabsContent">
        <!-- TAB 1: ผู้ป่วยในรวม -->
        <div class="tab-pane fade show active" id="all-content" role="tabpanel" aria-labelledby="all-tab">
            <!-- Charts Grid -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #3b82f6; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-person-fill text-primary me-2"></i> จำนวน (ผู้ป่วยในรวม)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_all_an" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #10b981; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-percent text-success me-2"></i> อัตราครองเตียง % (ผู้ป่วยในรวม)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_all_occupancy" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #f59e0b; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-warning"><i class="bi bi-graph-up-arrow text-warning me-2"></i> AdjRW (ผู้ป่วยในรวม)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_all_adjrw" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #06b6d4; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-info" style="color: #06b6d4 !important;"><i class="bi bi-award-fill text-info me-2" style="color: #06b6d4 !important;"></i> CMI (ผู้ป่วยในรวม)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_all_cmi" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table for ผู้ป่วยในรวม -->
            <div class="card shadow-sm border-0 mt-3" style="border-radius: 12px; border-top: 4px solid #dc3545 !important; overflow: hidden;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3">
                    <div>
                        <h6 class="mb-0 fw-bold text-danger" style="font-size: 1rem;">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i> สรุปสถิติผู้ป่วยในรวม รายเดือน
                        </h6>
                    </div>
                    <button onclick="exportTableToExcel('table_summary_all', 'สรุปสถิติผู้ป่วยในรวม รายเดือน')" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive w-100">
                        <table class="table table-hover align-middle mb-0 w-100" id="table_summary_all" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th class="py-3">เดือนปี</th>
                                    <th>Admit (AN)</th>
                                    <th>วันนอนรวม</th>
                                    <th>วันนอนเฉลี่ย (วัน)</th>
                                    <th>อัตราครองเตียง (%)</th>
                                    <th>Active Bed</th>
                                    <th>Sum AdjRW</th>
                                    <th>CMI</th>
                                    <th>รายได้/RW</th>
                                    <th>ค่ายา</th>
                                    <th>ค่า LAB</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_an = 0;
                                    $total_admdate = 0;
                                    $total_active_bed = 0;
                                    $total_adjrw = 0;
                                    $total_income = 0;
                                    $total_drug_price = 0;
                                    $total_lab_price = 0;
                                    $total_days = 0;
                                    
                                    foreach($ip_all as $row) {
                                        $total_an += $row->an;
                                        $total_admdate += $row->admdate;
                                        $total_active_bed += $row->active_bed;
                                        $total_adjrw += $row->adjrw;
                                        $total_income += $row->income_rw * $row->adjrw;
                                        $total_drug_price += $row->drug_price;
                                        $total_lab_price += $row->lab_price;
                                        $total_days += $row->days_in_month;
                                    }
                                    
                                    $bed_qty = DB::table('main_setting')->where('name', 'bed_qty')->value('value') ?: 1;
                                    $overall_occupancy = $total_days > 0 ? round(($total_admdate * 100) / ($bed_qty * $total_days), 2) : 0;
                                    $overall_active_bed = $total_days > 0 ? round($total_admdate / $total_days, 2) : 0;
                                    $overall_avg_admdate = $total_an > 0 ? round($total_admdate / $total_an, 2) : 0;
                                    $overall_cmi = $total_an > 0 ? round($total_adjrw / $total_an, 2) : 0;
                                    $overall_income_rw = $total_adjrw > 0 ? round($total_income / $total_adjrw, 2) : 0;
                                @endphp
                                
                                @foreach($ip_all as $row)
                                <tr>
                                    <td align="center" class="fw-bold">{{ $row->month }}</td>
                                    <td align="right" class="text-primary fw-bold">{{ number_format($row->an) }}</td>
                                    <td align="right" class="fw-bold">{{ number_format($row->admdate) }}</td>
                                    <td align="right" class="fw-bold" style="color: #dc3545;">{{ number_format($row->avg_admdate, 2) }}</td>
                                    <td align="center">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">{{ number_format($row->bed_occupancy, 2) }}%</div>
                                        <div class="progress" style="height: 6px; width: 100px; margin: 0 auto; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            @php
                                                $barColor = 'bg-danger';
                                                if ($row->bed_occupancy < 60) {
                                                    $barColor = 'bg-success';
                                                } elseif ($row->bed_occupancy < 80) {
                                                    $barColor = 'bg-warning';
                                                }
                                            @endphp
                                            <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($row->bed_occupancy, 100) }}%;"></div>
                                        </div>
                                    </td>
                                    <td align="right" class="fw-bold">{{ number_format($row->active_bed, 2) }}</td>
                                    <td align="right" class="fw-bold text-primary">{{ number_format($row->adjrw, 2) }}</td>
                                    <td align="right" class="fw-bold text-info" style="color: #0d6efd !important;">{{ number_format($row->cmi, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #06b6d4;">{{ number_format($row->income_rw, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #198754;">{{ number_format($row->drug_price, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #fd7e14;">{{ number_format($row->lab_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td align="center">รวมทั้งหมด</td>
                                    <td align="right" class="text-primary">{{ number_format($total_an) }}</td>
                                    <td align="right text-dark">{{ number_format($total_admdate) }}</td>
                                    <td align="right" style="color: #dc3545;">{{ number_format($overall_avg_admdate, 2) }}</td>
                                    <td align="center">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">{{ number_format($overall_occupancy, 2) }}%</div>
                                        <div class="progress" style="height: 6px; width: 100px; margin: 0 auto; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            @php
                                                $barColor = 'bg-danger';
                                                if ($overall_occupancy < 60) {
                                                    $barColor = 'bg-success';
                                                } elseif ($overall_occupancy < 80) {
                                                    $barColor = 'bg-warning';
                                                }
                                            @endphp
                                            <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($overall_occupancy, 100) }}%;"></div>
                                        </div>
                                    </td>
                                    <td align="right">{{ number_format($overall_active_bed, 2) }}</td>
                                    <td align="right" class="text-primary">{{ number_format($total_adjrw, 2) }}</td>
                                    <td align="right" class="text-info" style="color: #0d6efd !important;">{{ number_format($overall_cmi, 2) }}</td>
                                    <td align="right" style="color: #06b6d4;">{{ number_format($overall_income_rw, 2) }}</td>
                                    <td align="right" style="color: #198754;">{{ number_format($total_drug_price, 2) }}</td>
                                    <td align="right" style="color: #fd7e14;">{{ number_format($total_lab_price, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: ผู้ป่วยในทั่วไป -->
        <div class="tab-pane fade" id="normal-content" role="tabpanel" aria-labelledby="normal-tab">
            <!-- Charts Grid -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #3b82f6; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-person-fill text-primary me-2"></i> จำนวน (ผู้ป่วยในทั่วไป)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_normal_an" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #10b981; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-percent text-success me-2"></i> อัตราครองเตียง % (ผู้ป่วยในทั่วไป)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_normal_occupancy" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #f59e0b; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-warning"><i class="bi bi-graph-up-arrow text-warning me-2"></i> AdjRW (ผู้ป่วยในทั่วไป)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_normal_adjrw" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #06b6d4; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-info" style="color: #06b6d4 !important;"><i class="bi bi-award-fill text-info me-2" style="color: #06b6d4 !important;"></i> CMI (ผู้ป่วยในทั่วไป)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_normal_cmi" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table for ผู้ป่วยในทั่วไป -->
            <div class="card shadow-sm border-0 mt-3" style="border-radius: 12px; border-top: 4px solid #198754 !important; overflow: hidden;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3">
                    <div>
                        <h6 class="mb-0 fw-bold text-success" style="font-size: 1rem;">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i> สรุปสถิติผู้ป่วยในทั่วไป รายเดือน
                        </h6>
                    </div>
                    <button onclick="exportTableToExcel('table_summary_normal', 'สรุปสถิติผู้ป่วยในทั่วไป รายเดือน')" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive w-100">
                        <table class="table table-hover align-middle mb-0 w-100" id="table_summary_normal" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th class="py-3">เดือนปี</th>
                                    <th>Admit (AN)</th>
                                    <th>วันนอนรวม</th>
                                    <th>วันนอนเฉลี่ย (วัน)</th>
                                    <th>อัตราครองเตียง (%)</th>
                                    <th>Active Bed</th>
                                    <th>Sum AdjRW</th>
                                    <th>CMI</th>
                                    <th>รายได้/RW</th>
                                    <th>ค่ายา</th>
                                    <th>ค่า LAB</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_an_norm = 0;
                                    $total_admdate_norm = 0;
                                    $total_active_bed_norm = 0;
                                    $total_adjrw_norm = 0;
                                    $total_income_norm = 0;
                                    $total_drug_price_norm = 0;
                                    $total_lab_price_norm = 0;
                                    $total_days_norm = 0;
                                    
                                    foreach($ip_normal as $row) {
                                        $total_an_norm += $row->an;
                                        $total_admdate_norm += $row->admdate;
                                        $total_active_bed_norm += $row->active_bed;
                                        $total_adjrw_norm += $row->adjrw;
                                        $total_income_norm += $row->income_rw * $row->adjrw;
                                        $total_drug_price_norm += $row->drug_price;
                                        $total_lab_price_norm += $row->lab_price;
                                        $total_days_norm += $row->days_in_month;
                                    }
                                    
                                    $overall_occupancy_norm = $total_days_norm > 0 ? round(($total_admdate_norm * 100) / ($bed_qty * $total_days_norm), 2) : 0;
                                    $overall_active_bed_norm = $total_days_norm > 0 ? round($total_admdate_norm / $total_days_norm, 2) : 0;
                                    $overall_avg_admdate_norm = $total_an_norm > 0 ? round($total_admdate_norm / $total_an_norm, 2) : 0;
                                    $overall_cmi_norm = $total_an_norm > 0 ? round($total_adjrw_norm / $total_an_norm, 2) : 0;
                                    $overall_income_rw_norm = $total_adjrw_norm > 0 ? round($total_income_norm / $total_adjrw_norm, 2) : 0;
                                @endphp
                                
                                @foreach($ip_normal as $row)
                                <tr>
                                    <td align="center" class="fw-bold">{{ $row->month }}</td>
                                    <td align="right" class="text-primary fw-bold">{{ number_format($row->an) }}</td>
                                    <td align="right" class="fw-bold">{{ number_format($row->admdate) }}</td>
                                    <td align="right" class="fw-bold" style="color: #dc3545;">{{ number_format($row->avg_admdate, 2) }}</td>
                                    <td align="center">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">{{ number_format($row->bed_occupancy, 2) }}%</div>
                                        <div class="progress" style="height: 6px; width: 100px; margin: 0 auto; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            @php
                                                $barColor = 'bg-danger';
                                                if ($row->bed_occupancy < 60) {
                                                    $barColor = 'bg-success';
                                                } elseif ($row->bed_occupancy < 80) {
                                                    $barColor = 'bg-warning';
                                                }
                                            @endphp
                                            <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($row->bed_occupancy, 100) }}%;"></div>
                                        </div>
                                    </td>
                                    <td align="right" class="fw-bold">{{ number_format($row->active_bed, 2) }}</td>
                                    <td align="right" class="fw-bold text-primary">{{ number_format($row->adjrw, 2) }}</td>
                                    <td align="right" class="fw-bold text-info" style="color: #0d6efd !important;">{{ number_format($row->cmi, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #06b6d4;">{{ number_format($row->income_rw, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #198754;">{{ number_format($row->drug_price, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #fd7e14;">{{ number_format($row->lab_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td align="center">รวมทั้งหมด</td>
                                    <td align="right" class="text-primary">{{ number_format($total_an_norm) }}</td>
                                    <td align="right text-dark">{{ number_format($total_admdate_norm) }}</td>
                                    <td align="right" style="color: #dc3545;">{{ number_format($overall_avg_admdate_norm, 2) }}</td>
                                    <td align="center">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">{{ number_format($overall_occupancy_norm, 2) }}%</div>
                                        <div class="progress" style="height: 6px; width: 100px; margin: 0 auto; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            @php
                                                $barColor = 'bg-danger';
                                                if ($overall_occupancy_norm < 60) {
                                                    $barColor = 'bg-success';
                                                } elseif ($overall_occupancy_norm < 80) {
                                                    $barColor = 'bg-warning';
                                                }
                                            @endphp
                                            <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($overall_occupancy_norm, 100) }}%;"></div>
                                        </div>
                                    </td>
                                    <td align="right">{{ number_format($overall_active_bed_norm, 2) }}</td>
                                    <td align="right" class="text-primary">{{ number_format($total_adjrw_norm, 2) }}</td>
                                    <td align="right" class="text-info" style="color: #0d6efd !important;">{{ number_format($overall_cmi_norm, 2) }}</td>
                                    <td align="right" style="color: #06b6d4;">{{ number_format($overall_income_rw_norm, 2) }}</td>
                                    <td align="right" style="color: #198754;">{{ number_format($total_drug_price_norm, 2) }}</td>
                                    <td align="right" style="color: #fd7e14;">{{ number_format($total_lab_price_norm, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Homeward -->
        <div class="tab-pane fade" id="home-content" role="tabpanel" aria-labelledby="home-tab">
            <!-- Charts Grid -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #3b82f6; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-person-fill text-primary me-2"></i> จำนวน (Homeward)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_home_an" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #10b981; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="bi bi-percent text-success me-2"></i> อัตราครองเตียง % (Homeward)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_home_occupancy" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #f59e0b; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-warning"><i class="bi bi-graph-up-arrow text-warning me-2"></i> AdjRW (Homeward)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_home_adjrw" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100" style="border: 2px solid #06b6d4; border-radius: 12px; overflow: hidden;">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-info" style="color: #06b6d4 !important;"><i class="bi bi-award-fill text-info me-2" style="color: #06b6d4 !important;"></i> CMI (Homeward)</h6>
                        </div>
                        <div class="card-body py-0">
                            <div id="chart_home_cmi" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table for Homeward -->
            <div class="card shadow-sm border-0 mt-3" style="border-radius: 12px; border-top: 4px solid #ffc107 !important; overflow: hidden;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3">
                    <div>
                        <h6 class="mb-0 fw-bold text-warning" style="font-size: 1rem;">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i> สรุปสถิติ Homeward รายเดือน
                        </h6>
                    </div>
                    <button onclick="exportTableToExcel('table_summary_home', 'สรุปสถิติ Homeward รายเดือน')" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive w-100">
                        <table class="table table-hover align-middle mb-0 w-100" id="table_summary_home" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th class="py-3">เดือนปี</th>
                                    <th>Admit (AN)</th>
                                    <th>วันนอนรวม</th>
                                    <th>วันนอนเฉลี่ย (วัน)</th>
                                    <th>อัตราครองเตียง (%)</th>
                                    <th>Active Bed</th>
                                    <th>Sum AdjRW</th>
                                    <th>CMI</th>
                                    <th>รายได้/RW</th>
                                    <th>ค่ายา</th>
                                    <th>ค่า LAB</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_an_home = 0;
                                    $total_admdate_home = 0;
                                    $total_active_bed_home = 0;
                                    $total_adjrw_home = 0;
                                    $total_income_home = 0;
                                    $total_drug_price_home = 0;
                                    $total_lab_price_home = 0;
                                    $total_days_home = 0;
                                    
                                    foreach($ip_homeward as $row) {
                                        $total_an_home += $row->an;
                                        $total_admdate_home += $row->admdate;
                                        $total_active_bed_home += $row->active_bed;
                                        $total_adjrw_home += $row->adjrw;
                                        $total_income_home += $row->income_rw * $row->adjrw;
                                        $total_drug_price_home += $row->drug_price;
                                        $total_lab_price_home += $row->lab_price;
                                        $total_days_home += $row->days_in_month;
                                    }
                                    
                                    $overall_occupancy_home = $total_days_home > 0 ? round(($total_admdate_home * 100) / ($bed_qty * $total_days_home), 2) : 0;
                                    $overall_active_bed_home = $total_days_home > 0 ? round($total_admdate_home / $total_days_home, 2) : 0;
                                    $overall_avg_admdate_home = $total_an_home > 0 ? round($total_admdate_home / $total_an_home, 2) : 0;
                                    $overall_cmi_home = $total_an_home > 0 ? round($total_adjrw_home / $total_an_home, 2) : 0;
                                    $overall_income_rw_home = $total_adjrw_home > 0 ? round($total_income_home / $total_adjrw_home, 2) : 0;
                                @endphp
                                
                                @foreach($ip_homeward as $row)
                                <tr>
                                    <td align="center" class="fw-bold">{{ $row->month }}</td>
                                    <td align="right" class="text-primary fw-bold">{{ number_format($row->an) }}</td>
                                    <td align="right" class="fw-bold">{{ number_format($row->admdate) }}</td>
                                    <td align="right" class="fw-bold" style="color: #dc3545;">{{ number_format($row->avg_admdate, 2) }}</td>
                                    <td align="center">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">{{ number_format($row->bed_occupancy, 2) }}%</div>
                                        <div class="progress" style="height: 6px; width: 100px; margin: 0 auto; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            @php
                                                $barColor = 'bg-danger';
                                                if ($row->bed_occupancy < 60) {
                                                    $barColor = 'bg-success';
                                                } elseif ($row->bed_occupancy < 80) {
                                                    $barColor = 'bg-warning';
                                                }
                                            @endphp
                                            <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($row->bed_occupancy, 100) }}%;"></div>
                                        </div>
                                    </td>
                                    <td align="right" class="fw-bold">{{ number_format($row->active_bed, 2) }}</td>
                                    <td align="right" class="fw-bold text-primary">{{ number_format($row->adjrw, 2) }}</td>
                                    <td align="right" class="fw-bold text-info" style="color: #0d6efd !important;">{{ number_format($row->cmi, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #06b6d4;">{{ number_format($row->income_rw, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #198754;">{{ number_format($row->drug_price, 2) }}</td>
                                    <td align="right" class="fw-bold" style="color: #fd7e14;">{{ number_format($row->lab_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td align="center">รวมทั้งหมด</td>
                                    <td align="right" class="text-primary">{{ number_format($total_an_home) }}</td>
                                    <td align="right text-dark">{{ number_format($total_admdate_home) }}</td>
                                    <td align="right" style="color: #dc3545;">{{ number_format($overall_avg_admdate_home, 2) }}</td>
                                    <td align="center">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">{{ number_format($overall_occupancy_home, 2) }}%</div>
                                        <div class="progress" style="height: 6px; width: 100px; margin: 0 auto; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                            @php
                                                $barColor = 'bg-danger';
                                                if ($overall_occupancy_home < 60) {
                                                    $barColor = 'bg-success';
                                                } elseif ($overall_occupancy_home < 80) {
                                                    $barColor = 'bg-warning';
                                                }
                                            @endphp
                                            <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ min($overall_occupancy_home, 100) }}%;"></div>
                                        </div>
                                    </td>
                                    <td align="right">{{ number_format($overall_active_bed_home, 2) }}</td>
                                    <td align="right" class="text-primary">{{ number_format($total_adjrw_home, 2) }}</td>
                                    <td align="right" class="text-info" style="color: #0d6efd !important;">{{ number_format($overall_cmi_home, 2) }}</td>
                                    <td align="right" style="color: #06b6d4;">{{ number_format($overall_income_rw_home, 2) }}</td>
                                    <td align="right" style="color: #198754;">{{ number_format($total_drug_price_home, 2) }}</td>
                                    <td align="right" style="color: #fd7e14;">{{ number_format($total_lab_price_home, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Vendor JS Files -->
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>

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
      const months = <?php echo json_encode($month); ?>;

      // สร้างตัวเลือกกราฟมาตรฐานแบบยืดหยุ่น รองรับ Bar, Area, และ Line
      const chartOptions = (seriesName, data, color, type) => {
          let fillOpt = {};
          if (type === 'area') {
              fillOpt = {
                  type: "gradient",
                  gradient: {
                      shadeIntensity: 1,
                      opacityFrom: 0.4,
                      opacityTo: 0.1,
                      stops: [0, 90, 100]
                  }
              };
          } else {
              fillOpt = {
                  opacity: 0.85
              };
          }

          return {
              series: [{
                  name: seriesName,
                  data: data
              }],
              chart: {
                  height: 230,
                  type: type,
                  toolbar: { show: false }
              },
              markers: { size: type === 'bar' ? 0 : 4 },
              colors: [color],
              fill: fillOpt,
              dataLabels: { enabled: true },
              stroke: { 
                  curve: 'smooth', 
                  width: type === 'bar' ? 0 : 2 
              },
              xaxis: {
                  type: 'text',
                  categories: months
              }
          };
      };

      // Data Arrays
      const dataAll = {
          an: <?php echo json_encode(array_column($ip_all, 'an')); ?>,
          occupancy: <?php echo json_encode(array_column($ip_all, 'bed_occupancy')); ?>,
          adjrw: <?php echo json_encode(array_column($ip_all, 'adjrw')); ?>,
          cmi: <?php echo json_encode(array_column($ip_all, 'cmi')); ?>
      };
      
      const dataNormal = {
          an: <?php echo json_encode(array_column($ip_normal, 'an')); ?>,
          occupancy: <?php echo json_encode(array_column($ip_normal, 'bed_occupancy')); ?>,
          adjrw: <?php echo json_encode(array_column($ip_normal, 'adjrw')); ?>,
          cmi: <?php echo json_encode(array_column($ip_normal, 'cmi')); ?>
      };

      const dataHome = {
          an: <?php echo json_encode(array_column($ip_homeward, 'an')); ?>,
          occupancy: <?php echo json_encode(array_column($ip_homeward, 'bed_occupancy')); ?>,
          adjrw: <?php echo json_encode(array_column($ip_homeward, 'adjrw')); ?>,
          cmi: <?php echo json_encode(array_column($ip_homeward, 'cmi')); ?>
      };

      // TAB 1: ผู้ป่วยในรวม
      new ApexCharts(document.querySelector("#chart_all_an"), chartOptions("จำนวน AN", dataAll.an, "#3b82f6", "bar")).render();
      new ApexCharts(document.querySelector("#chart_all_occupancy"), chartOptions("อัตราครองเตียง (%)", dataAll.occupancy, "#10b981", "area")).render();
      new ApexCharts(document.querySelector("#chart_all_adjrw"), chartOptions("AdjRW", dataAll.adjrw, "#f59e0b", "bar")).render();
      new ApexCharts(document.querySelector("#chart_all_cmi"), chartOptions("CMI", dataAll.cmi, "#06b6d4", "line")).render();

      // TAB 2: ผู้ป่วยในทั่วไป
      new ApexCharts(document.querySelector("#chart_normal_an"), chartOptions("จำนวน AN", dataNormal.an, "#3b82f6", "bar")).render();
      new ApexCharts(document.querySelector("#chart_normal_occupancy"), chartOptions("อัตราครองเตียง (%)", dataNormal.occupancy, "#10b981", "area")).render();
      new ApexCharts(document.querySelector("#chart_normal_adjrw"), chartOptions("AdjRW", dataNormal.adjrw, "#f59e0b", "bar")).render();
      new ApexCharts(document.querySelector("#chart_normal_cmi"), chartOptions("CMI", dataNormal.cmi, "#06b6d4", "line")).render();

      // TAB 3: Homeward
      new ApexCharts(document.querySelector("#chart_home_an"), chartOptions("จำนวน AN", dataHome.an, "#3b82f6", "bar")).render();
      new ApexCharts(document.querySelector("#chart_home_occupancy"), chartOptions("อัตราครองเตียง (%)", dataHome.occupancy, "#10b981", "area")).render();
      new ApexCharts(document.querySelector("#chart_home_adjrw"), chartOptions("AdjRW", dataHome.adjrw, "#f59e0b", "bar")).render();
      new ApexCharts(document.querySelector("#chart_home_cmi"), chartOptions("CMI", dataHome.cmi, "#06b6d4", "line")).render();

      // จัดการการกางความกว้างเมื่อมีการคลิกเปลี่ยนแท็บ
      document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
          tabEl.addEventListener('shown.bs.tab', () => {
              window.dispatchEvent(new Event('resize'));
          });
      });
  });
</script>
@endpush
