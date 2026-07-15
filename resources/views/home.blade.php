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
    </div> <!-- //row -->

</div>

<!-- ionicon -->
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>



</div>
@endsection

@push('scripts')
<script>
  // No home scripts needed
</script>
@endpush

