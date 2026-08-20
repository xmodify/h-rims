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
</style>

<div class="container-fluid py-4 px-lg-5" style="background-color: #f8fafc;">
    <div class="row">
        <!-- Header -->
        <div class="col-12 px-3 mb-4">
            <div class="page-header-box mt-2" style="border-left-color: #10b981 !important;">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="text-primary mb-0 fw-bold">
                        <i class="bi bi-bank me-2 text-success"></i> ระบบรายงานสถานะการเงินการคลัง (HosFin)
                    </h5>
                </div>
                <small class="text-muted">
                    ศูนย์รวมรายงานสถานะทางการเงินและวิเคราะห์ต้นทุนการรักษาพยาบาล
                </small>
            </div>
        </div>
        
        <!-- Cards Grid -->
        <div class="col-md-4 mb-4">
            <div class="card hosfin-card accent-teal h-100 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 text-success me-3">
                            <i class="bi bi-file-earmark-spreadsheet" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">ระบบงบทดลอง</h5>
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

        <div class="col-md-4 mb-4">
            <div class="card hosfin-card accent-blue h-100 shadow-sm opacity-75">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 text-primary me-3">
                            <i class="bi bi-graph-up-arrow" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">รายงานรายได้</h5>
                            <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Coming Soon</span>
                        </div>
                    </div>
                    <p class="text-muted mb-4 flex-grow-1" style="font-size: 0.9rem; line-height: 1.6;">
                        ระบบวิเคราะห์โครงสร้างรายได้โรงพยาบาล แยกตามสิทธิการรักษาพยาบาลและหมวดค่าบริการทางการแพทย์รายเดือนและรายปีงบประมาณ
                    </p>
                    <button class="btn btn-outline-secondary rounded-pill px-4 align-self-start mt-auto" disabled>
                        ยังไม่เปิดบริการ
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card hosfin-card accent-red h-100 shadow-sm opacity-75">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-3 text-danger me-3">
                            <i class="bi bi-graph-down-arrow" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">รายงานรายจ่าย</h5>
                            <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Coming Soon</span>
                        </div>
                    </div>
                    <p class="text-muted mb-4 flex-grow-1" style="font-size: 0.9rem; line-height: 1.6;">
                        ระบบควบคุมและตรวจสอบยอดค่าใช้จ่ายในการดำเนินงาน ต้นทุนบุคลากร และต้นทุนการรักษาพยาบาลจำแนกตามโครงสร้างแผนก
                    </p>
                    <button class="btn btn-outline-secondary rounded-pill px-4 align-self-start mt-auto" disabled>
                        ยังไม่เปิดบริการ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
