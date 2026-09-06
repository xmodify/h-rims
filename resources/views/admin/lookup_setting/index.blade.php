@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-lg-5">
    <!-- Page Header -->
    <div class="page-header-box mb-4 p-3 p-md-4 rounded-4 bg-white shadow-sm border w-100 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="mb-1 text-primary fw-bold d-flex align-items-center">
                <span class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning me-2.5 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-folder-fill fs-5"></i>
                </span>
                Lookup Setting (ศูนย์รวมตารางมาตรฐาน)
            </h4>
            <small class="text-muted">จัดการตารางข้อมูลอ้างอิง ระบบรหัสยา/หัตถการ หอผู้ป่วย รหัสสถานพยาบาล และปีงบประมาณ</small>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('admin.main_setting') }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm hover-scale">
                <i class="bi bi-gear-wide-connected me-1"></i> Main Setting (ระบบตั้งค่า)
            </a>
        </div>
    </div>

    <!-- Hub Cards Grid -->
    <div class="row g-4 mb-5">
        <!-- 1. Lookup icode -->
        <div class="col-xl-6 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 lookup-hub-card hover-scale overflow-hidden bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="lookup-icon-badge bg-success-subtle text-success">
                                <i class="bi bi-capsule-pill fs-3"></i>
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold fs-6">
                                {{ number_format($counts['icode'] ?? 0) }} รายการ
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Lookup icode (รหัสยา / หัตถการ)</h5>
                        <p class="text-muted small mb-3 lh-base">
                            ตารางจับคู่รหัสยา หัตถการ และบริการของ HOSxP เข้ากับรหัสมาตรฐานการเรียกเก็บ e-Claim / สปสช. (ADP Code, ADP Type, SubInscl)
                        </p>
                        <div class="d-flex flex-wrap gap-1.5 mb-4">
                            <span class="badge bg-light text-dark border rounded-pill small">UC Cost Recovery</span>
                            <span class="badge bg-light text-dark border rounded-pill small">PPFS ส่งเสริมสุขภาพ</span>
                            <span class="badge bg-light text-dark border rounded-pill small">สมุนไพร 32 รายการ</span>
                            <span class="badge bg-light text-dark border rounded-pill small">ฟอกไต HD</span>
                            <span class="badge bg-light text-dark border rounded-pill small">EMS</span>
                            <span class="badge bg-light text-dark border rounded-pill small">ปกส. ทันตกรรม</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <small class="text-muted"><i class="bi bi-diagram-3 me-1"></i> ตาราง: <code>lookup_icode</code></small>
                        <a href="{{ route('admin.lookup_icode.index') }}" class="btn btn-success btn-sm px-4 rounded-pill shadow-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i> จัดการข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Lookup ward -->
        <div class="col-xl-6 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 lookup-hub-card hover-scale overflow-hidden bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="lookup-icon-badge bg-warning-subtle text-warning">
                                <i class="bi bi-hospital fs-3"></i>
                            </span>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1.5 fw-bold fs-6">
                                {{ number_format($counts['ward'] ?? 0) }} หอผู้ป่วย
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Lookup ward (หอผู้ป่วยและเตียง)</h5>
                        <p class="text-muted small mb-3 lh-base">
                            จัดการข้อมูลหอผู้ป่วยใน กำหนดจำนวนเตียงประจำวอร์ด และประเภทการรับบริการ เพื่อคำนวณวันนอนและสถิติผู้ป่วยใน (IPD)
                        </p>
                        <div class="d-flex flex-wrap gap-1.5 mb-4">
                            <span class="badge bg-light text-dark border rounded-pill small">วอร์ดทั่วไป</span>
                            <span class="badge bg-light text-dark border rounded-pill small">หอผู้ป่วยชาย</span>
                            <span class="badge bg-light text-dark border rounded-pill small">หอผู้ป่วยหญิง</span>
                            <span class="badge bg-light text-dark border rounded-pill small">ห้องพิเศษ VIP</span>
                            <span class="badge bg-light text-dark border rounded-pill small">ห้องคลอด LR</span>
                            <span class="badge bg-light text-dark border rounded-pill small">Homeward</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <small class="text-muted"><i class="bi bi-diagram-3 me-1"></i> ตาราง: <code>lookup_ward</code></small>
                        <a href="{{ route('admin.lookup_ward.index') }}" class="btn btn-warning btn-sm px-4 rounded-pill shadow-sm text-dark fw-semibold">
                            <i class="bi bi-box-arrow-up-right me-1"></i> จัดการข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Lookup hospcode -->
        <div class="col-xl-6 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 lookup-hub-card hover-scale overflow-hidden bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="lookup-icon-badge bg-info-subtle text-info">
                                <i class="bi bi-building fs-3"></i>
                            </span>
                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1.5 fw-bold fs-6">
                                {{ number_format($counts['hospcode'] ?? 0) }} แห่ง
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Lookup hospcode (รหัสสถานพยาบาล)</h5>
                        <p class="text-muted small mb-3 lh-base">
                            รหัสสถานพยาบาล 5 หลัก หน่วยบริการประจำ และสถานะสิทธิหลัก (Hmain UCS, Hmain SSS) รวมถึงการกำหนดสถานะสถานพยาบาลในเขตหรือนอกเขตจังหวัด
                        </p>
                        <div class="d-flex flex-wrap gap-1.5 mb-4">
                            <span class="badge bg-light text-dark border rounded-pill small">รหัส 5 หลักสถานพยาบาล</span>
                            <span class="badge bg-light text-dark border rounded-pill small">Hmain UCS บัตรทอง</span>
                            <span class="badge bg-light text-dark border rounded-pill small">Hmain SSS ประกันสังคม</span>
                            <span class="badge bg-light text-dark border rounded-pill small">ในจังหวัด / นอกจังหวัด</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <small class="text-muted"><i class="bi bi-diagram-3 me-1"></i> ตาราง: <code>lookup_hospcode</code></small>
                        <a href="{{ route('admin.lookup_hospcode.index') }}" class="btn btn-info btn-sm px-4 rounded-pill shadow-sm text-white">
                            <i class="bi bi-box-arrow-up-right me-1"></i> จัดการข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Budget year -->
        <div class="col-xl-6 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 lookup-hub-card hover-scale overflow-hidden bg-white">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="lookup-icon-badge bg-danger-subtle text-danger">
                                <i class="bi bi-calendar3 fs-3"></i>
                            </span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5 fw-bold fs-6">
                                {{ number_format($counts['budget_year'] ?? 0) }} ปีงบ
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Budget year (ปีงบประมาณ)</h5>
                        <p class="text-muted small mb-3 lh-base">
                            กำหนดรอบระยะเวลาของปีงบประมาณ วันเริ่มต้น (DATE_BEGIN) และวันสิ้นสุด (DATE_END) สำหรับใช้ในการกรองข้อมูลเรียกเก็บและรายงานทุกระบบในโปรแกรม
                        </p>
                        <div class="d-flex flex-wrap gap-1.5 mb-4">
                            <span class="badge bg-light text-dark border rounded-pill small">รหัสปีงบประมาณ</span>
                            <span class="badge bg-light text-dark border rounded-pill small">วันเริ่มต้นรอบงบ</span>
                            <span class="badge bg-light text-dark border rounded-pill small">วันสิ้นสุดรอบงบ</span>
                            <span class="badge bg-light text-dark border rounded-pill small">Dashboard Filter</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <small class="text-muted"><i class="bi bi-diagram-3 me-1"></i> ตาราง: <code>budget_year</code></small>
                        <a href="{{ route('admin.budget_year.index') }}" class="btn btn-danger btn-sm px-4 rounded-pill shadow-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i> จัดการข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .lookup-hub-card {
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }
    .lookup-hub-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08) !important;
    }
    .lookup-icon-badge {
        width: 54px;
        height: 54px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
    }
    .hover-scale {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-scale:hover {
        transform: translateY(-2px);
    }
</style>
@endsection
