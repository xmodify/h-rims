@extends('layouts.app')

@section('content') 
<style>    
  /* Dashboard Specific Clock Styles */
  #realtime-clock, #realtime-clock_ipd {
    font-family: 'Inter', sans-serif;
    font-weight: 700;
    padding: 2px 8px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    font-size: 0.85rem;
  }
</style>

<div class="container-fluid py-4 px-lg-5" style="background-color: #f0fdf4;">
    <div class="row">      
        <div class="col-12 px-3">
          <div class="page-header-box mt-2" style="border-left-color: #3b82f6 !important;">
            <div class="d-flex align-items-center gap-2">
              <h6 class="text-primary mb-0 fw-bold">
                <i class="bi bi-activity me-1"></i> DASHBOARD OVERVIEW
              </h6>
              <small class="text-muted ms-2 fw-normal dashboard-date-info">
                Visit ล่าสุด <i class="bi bi-calendar3 me-1"></i> {{DateThai(date('Y-m-d'))}} 
                <i class="bi bi-clock-history ms-2 me-1"></i> {{ $latest_vsttime }}
              </small>
            </div>
            <div class="d-flex align-items-center gap-3">
              <div class="px-3 border-end">
                <small class="text-muted d-block" style="font-size: 0.65rem;">OPD TOTAL</small>
                <div class="h6 mb-0 fw-bold text-dark text-center">{{$opd_total}}</div>
              </div>
              <div class="px-3 border-end">
                <small class="text-muted d-block" style="font-size: 0.65rem;">Authen</small>
                <div class="h6 mb-0 fw-bold text-success text-center">{{$opd_auth}}</div>
              </div>
              <div class="px-3 border-end">
                <small class="text-muted d-block" style="font-size: 0.65rem;">ปิดสิทธิ สปสช.</small>
                <div class="h6 mb-0 fw-bold text-primary text-center">{{$endpoint}}</div>
              </div>
              <div>
                <a class="btn btn-outline-primary btn-sm rounded-pill px-3" href="{{ url('check/nhso_endpoint') }}" target="_blank" style="font-size: 0.75rem; border-width: 2px;">
                  <i class="bi bi-cloud-download me-1"></i> ดึงปิดสิทธิ สปสช.
                </a>
              </div>
            </div>
          </div>
        </div>
      <!-- OPD Metrics Cards -->
      <div class="col-sm-3 mb-3">
          <div class="card dash-card accent-1">
            <div class="card-body">
              <span class="card-label"><i class="bi bi-credit-card-2-front me-1 icon-color-1"></i> OFC Visit : รูดบัตร : ปิดสิทธิ</span>
              <div class="card-metric">{{$ofc}} : {{$ofc_edc}} : {{$ofc_endpoint}}</div> 
              <a href="{{ url('/opd_ofc') }}" target="_blank" class="card-footer-link text-color-1">
                View Report <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
      </div>
      <div class="col-sm-3 mb-3">
          <div class="card dash-card accent-2">
            <div class="card-body">
              <span class="card-label"><i class="bi bi-shield-lock me-1 icon-color-2"></i> ไม่ขอ AuthenCode</span>
              <div class="card-metric">{{$non_authen}}</div>  
              <a href="{{ url('/opd_non_authen') }}" target="_blank" class="card-footer-link text-color-2">
                View Report <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
      </div>        
      <div class="col-sm-3 mb-3">
          <div class="card dash-card accent-3">
            <div class="card-body">
              <span class="card-label"><i class="bi bi-building me-1 icon-color-3"></i> ไม่บันทึกสถานพยาบาลหลัก</span>
              <div class="card-metric">{{$non_hmain}}</div>
              <a href="{{ url('/opd_non_hospmain') }}" target="_blank" class="card-footer-link text-color-3">
                View Report <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
      </div>
      <div class="col-sm-3 mb-3">
        <div class="card dash-card accent-4">
          <div class="card-body">
            <span class="card-label"><i class="bi bi-check2-square me-1 icon-color-4"></i> PPFS : ปิดสิทธิ </span>
            <div class="card-metric">{{$ppfs}} : {{$ppfs_endpoint}}</div> 
            <a href="{{ url('/opd_ppfs') }}" target="_blank" class="card-footer-link text-color-4">
              View Report <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>      
      <div class="col-sm-3 mb-3">
        <div class="card dash-card accent-5">
          <div class="card-body">
            <span class="card-label"><i class="bi bi-star me-1 icon-color-5"></i> UC บริการเฉพาะ : ปิดสิทธิ</span>
            <div class="card-metric">{{$uc_cr}} : {{$uc_cr_endpoint}}</div>  
            <a href="{{ url('/opd_ucs_cr') }}" target="_blank" class="card-footer-link text-color-5">
              View Report <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>        
      <div class="col-sm-3 mb-3">
        <div class="card dash-card accent-6">
          <div class="card-body">
            <span class="card-label"><i class="bi bi-flower1 me-1 icon-color-6"></i> UC ยาสมุนไพร : ปิดสิทธิ</span>
            <div class="card-metric">{{$uc_herb}} : {{$uc_herb_endpoint}}</div>
            <a href="{{ url('/opd_ucs_herb') }}" target="_blank" class="card-footer-link text-color-6">
              View Report <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="col-sm-3 mb-3">
        <div class="card dash-card accent-7">
          <div class="card-body">
            <span class="card-label"><i class="bi bi-hospital me-1 icon-color-7"></i> UC แพทย์แผนไทย : ปิดสิทธิ </span>
            <div class="card-metric">{{$uc_healthmed}} : {{$uc_healthmed_endpoint}}</div>  
            <a href="{{ url('/opd_ucs_healthmed') }}" target="_blank" class="card-footer-link text-color-7">
              View Report <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="col-sm-3 mb-3">
          <div class="card dash-card accent-8">
            <div class="card-body">
              <span class="card-label"><i class="bi bi-geo-alt me-1 icon-color-8"></i> UC Anywhere : ปิดสิทธิ</span>
              <div class="card-metric">{{$uc_anywhere}} : {{$uc_anywhere_endpoint}}</div>  
              <a href="{{ url('/opd_ucs_anywhere') }}" target="_blank" class="card-footer-link text-color-8">
                View Report <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
      </div>
      <div class="col-sm-3 mb-3">
          <div class="card dash-card accent-13">
            <div class="card-body">
              <span class="card-label"><i class="bi bi-droplet me-1 icon-color-13"></i> UC ฟอกไต : ปิดสิทธิ</span>
              <div class="card-metric">{{$uc_kidney}} : {{$uc_kidney_endpoint}}</div>  
              <a href="{{ url('/opd_ucs_kidney') }}" target="_blank" class="card-footer-link text-color-13">
                View Report <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
      </div>
      
      <!-- IPD Section -->
      <div class="col-12 px-3 mt-1">
        <div class="page-header-box" style="border-left-color: #198754 !important;">
          <div class="d-flex align-items-center gap-2">
            <h6 class="text-success mb-0 fw-bold">
              <i class="bi bi-door-open me-2"></i> INPATIENT ADMISSIONS 
            </h6>
            <small class="text-muted ms-2 fw-normal dashboard-date-info">
              Admit ล่าสุด <i class="bi bi-calendar3 me-1"></i> {{DateThai(date('Y-m-d'))}}
              <i class="bi bi-clock-history me-1"></i> {{ $latest_regtime }} 
              <span class="ms-3">ADMIT NOW: <span class="text-dark fw-bold">{{$admit_now}}</span> AN</span>
            </small>
          </div>
        </div>
      </div>

      <!-- IPD Metrics Cards -->
      <div class="col-sm-3 mb-3">
        <div class="card dash-card accent-9">
          <div class="card-body">
            <span class="card-label"><i class="bi bi-house me-1 icon-color-9"></i> Admit Homeward : Authen</span>
            <div class="card-metric">{{$admit_homeward}} : {{$admit_homeward_endpoint}}</div> 
            <a href="{{ url('/ipd_homeward') }}" target="_blank" class="card-footer-link text-color-9">
              View Report <i class="bi bi-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="col-sm-3 mb-3">
          <div class="card dash-card accent-10">
            <div class="card-body">
              <span class="card-label"><i class="bi bi-file-earmark-medical me-1 icon-color-10"></i> Chart รอแพทย์สรุป : รอบันทึก ICD10</span>
              <div class="card-metric">{{$non_diagtext}} : {{$non_icd10}}</div>  
              <a href="{{ url('/ipd_non_dchsummary') }}" target="_blank" class="card-footer-link text-color-10">
                View Report <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
      </div>        
      <div class="col-sm-3 mb-3">
          <div class="card dash-card accent-11">
            <div class="card-body">
              <span class="card-label"><i class="bi bi-arrow-left-right me-1 icon-color-11"></i> รอโอนค่าใช้จ่าย</span>
              <div class="card-metric">{{$not_transfer}}</div>
              <a href="{{ url('/ipd_finance_chk_opd_wait_transfer') }}" target="_blank" class="card-footer-link text-color-11">
                View Report <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
      </div>
      <div class="col-sm-3 mb-3">
          <div class="card dash-card accent-12">
            <div class="card-body">
              <span class="card-label"><i class="bi bi-cash-coin me-1 icon-color-12"></i> รอชำระเงินสด : จำนวนเงิน</span>
              <div class="card-metric" style="font-size: 1.25rem;">{{$wait_paid_money}} : {{number_format($sum_wait_paid_money,2)}}</div>  
              <a href="{{ url('/ipd_finance_chk_wait_rcpt_money') }}" target="_blank" class="card-footer-link text-color-12">
                View Report <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
      </div> 
      
      <div class="col-12 mt-4 mb-2">
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm border-start border-primary border-5">
            <h5 class="text-dark mb-0 fw-bold">
              <i class="bi bi-bar-chart-fill text-primary me-2"></i> สถิติผู้ป่วยใน ปีงบประมาณ {{$budget_year}}
            </h5>
            <form method="POST" enctype="multipart/form-data" class="m-0">
              @csrf
              <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">ปีงบประมาณ:</span>
                <select class="form-select form-select-sm" name="budget_year" style="width: 160px; border-radius: 8px;">
                  @foreach ($budget_year_select as $row)
                    <option value="{{ $row->LEAVE_YEAR_ID }}"
                      {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                      {{ $row->LEAVE_YEAR_NAME }}
                    </option>
                  @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">ค้นหา</button>
              </div>
            </form>
        </div>
      </div>
      <!-- IPD Tabs Navigation -->
      <div class="col-12 px-3 mt-3">
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

      <div class="tab-content col-12 px-3 mt-3" id="ipdTabsContent">
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
                <small class="text-muted d-block mt-1">ประมวลผลตาม เดือนที่จำหน่าย (Discharge Date)</small>
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
                <small class="text-muted d-block mt-1">ประมวลผลตาม เดือนที่จำหน่าย (Discharge Date)</small>
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
                <small class="text-muted d-block mt-1">ประมวลผลตาม เดือนที่จำหน่าย (Discharge Date)</small>
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
    </div> <!-- //row -->

</div>

<!-- ionicon -->
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>



</div>
@endsection

@push('scripts')
<!-- Vendor JS Files -->
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>

<script>
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

      // TAB 1: ผู้ป่วยในรวม (สีกราฟและขอบการ์ดล้อตามภาพตัวอย่างที่ 2)
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

