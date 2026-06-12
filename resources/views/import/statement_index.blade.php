@extends('layouts.app')

@section('content')

    <!-- Page Header -->
    <div class="page-header-box mt-2 mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-file-earmark-arrow-up-fill me-2"></i>
                Statement Portal
            </h4>
            <p class="text-muted small mb-0 mt-1">
                ระบบจัดการและนำเข้าข้อมูล Statement ของกองทุนต่าง ๆ เพื่อตรวจสอบการชดเชยค่าบริการ
            </p>
        </div>
    </div>


    <!-- General OP-IP Grid -->
    <div class="row g-4">
        <!-- 1. UCS General [OP-IP] -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-ucs">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-success-soft text-success me-3">
                            <i class="bi bi-shield-fill-check fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-UCS [OP-IP]</h5>
                            <span class="badge bg-success-soft text-success small mt-1">สิทธิ์บัตรทองทั่วไป</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        จัดการนำเข้าข้อมูลและเรียกดูรายงานของระบบหลักประกันสุขภาพถ้วนหน้า (UCS) ทั้งผู้ป่วยนอกและผู้ป่วยใน
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_ucs') }}" class="btn btn-outline-success btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. OFC General [OP-IP] -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-ofc">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-warning-soft text-warning me-3">
                            <i class="bi bi-person-badge-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-OFC [OP-IP]</h5>
                            <span class="badge bg-warning-soft text-warning small mt-1">สิทธิ์ข้าราชการ</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        นำเข้าข้อมูล Statement ของกองทุนผู้ป่วยสิทธิ์สวัสดิการข้าราชการ (OFC/BKK/BMT)
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_ofc') }}" class="btn btn-outline-warning btn-sm text-dark fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. OFC CSOP -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-csop">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-danger-soft text-danger me-3">
                            <i class="bi bi-droplet-half fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-OFC [CSOP]</h5>
                            <span class="badge bg-danger-soft text-danger small mt-1">สิทธิ์ฟอกไตข้าราชการ (CSOP)</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        ข้อมูลการชดเชยค่าบริการฟอกเลือดด้วยเครื่องไตเทียมของผู้ป่วยสิทธิ์ข้าราชการ (CSOP)
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_ofc_csop') }}" class="btn btn-outline-danger btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. OFC CIPN -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-cipn">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-purple-soft text-purple me-3">
                            <i class="bi bi-clipboard-pulse fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-OFC [CIPN]</h5>
                            <span class="badge bg-purple-soft text-purple small mt-1">โครงการแลกเปลี่ยนข้อมูล</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        ตรวจสอบรายงานและประมวลผลการชดเชยกลุ่มโรคในระบบสวัสดิการข้าราชการรักษาพยาบาล
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_ofc_cipn') }}" class="btn btn-outline-purple btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. LGO General [OP-IP] -->
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-lgo">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-info-soft text-info me-3">
                            <i class="bi bi-building-fill-check fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-LGO [OP-IP]</h5>
                            <span class="badge bg-info-soft text-info small mt-1">สิทธิ์พนักงานส่วนท้องถิ่น</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        สถิติข้อมูลนำเข้าและชดเชยค่าบริการของผู้ป่วยสิทธิ์องค์กรปกครองส่วนท้องถิ่น (LGO)
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_lgo') }}" class="btn btn-outline-info btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. UCS Kidney -->
        @if ($hasLookupIcode_kidney)
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-ucs-kidney">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-teal-soft text-teal me-3">
                            <i class="bi bi-activity fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-UCS [ฟอกไต]</h5>
                            <span class="badge bg-teal-soft text-teal small mt-1">สิทธิ์ฟอกไตบัตรทอง</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        ระบบบันทึกและตรวจสอบ Statement รายละเอียดการส่งเบิกกรณีผู้ป่วยไตวายเรื้อรัง
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_ucs_kidney') }}" class="btn btn-outline-teal btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- 2. OFC Kidney -->
        @if ($hasLookupIcode_kidney)
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-ofc-kidney">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-danger-soft text-danger me-3">
                            <i class="bi bi-droplet-half fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-OFC [ฟอกไต]</h5>
                            <span class="badge bg-danger-soft text-danger small mt-1">สิทธิ์ฟอกไตข้าราชการ</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        ระบบบันทึกและตรวจสอบ Statement รายละเอียดการส่งเบิกกรณีผู้ป่วยไตวายเรื้อรัง (สิทธิ์ข้าราชการ)
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_ofc_csop') }}" class="btn btn-outline-danger btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- 3. LGO Kidney -->
        @if ($hasLookupIcode_kidney)
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-lgo-kidney">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-cyan-soft text-cyan me-3">
                            <i class="bi bi-water fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-LGO [ฟอกไต]</h5>
                            <span class="badge bg-cyan-soft text-cyan small mt-1">สิทธิ์ฟอกไต อปท.</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        นำเข้าและสืบค้นบันทึกชดเชยสิทธิ์ฟอกเลือดกรณีฟอกไตของผู้ป่วย อปท.
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_lgo_kidney') }}" class="btn btn-outline-cyan btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- 4. SSS Kidney -->
        @if ($hasLookupIcode_kidney)
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm stm-card card-sss-kidney">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-box bg-indigo-soft text-indigo me-3">
                            <i class="bi bi-heart-pulse-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="card-title fw-bold mb-0 text-dark">STM-SSS [ฟอกไต]</h5>
                            <span class="badge bg-indigo-soft text-indigo small mt-1">สิทธิ์ฟอกไตประกันสังคม</span>
                        </div>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        จัดการนำเข้าไฟล์ของระบบประกันสังคมสำหรับกลุ่มโรคฟอกไตเทียม
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ url('/import/stm_sss_kidney') }}" class="btn btn-outline-indigo btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Styling for modern looks -->
    <style>
        .stm-card {
            transition: all 0.25s ease-in-out;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(225, 230, 235, 0.5) !important;
            border-radius: 14px !important;
        }
        
        /* Distinct Border Top Colors */
        .card-ucs { border-top: 4px solid #28a745 !important; }
        .card-ofc { border-top: 4px solid #ffc107 !important; }
        .card-csop { border-top: 4px solid #dc3545 !important; }
        .card-cipn { border-top: 4px solid #6f42c1 !important; }
        .card-lgo { border-top: 4px solid #0dcaf0 !important; }
        .card-ucs-kidney { border-top: 4px solid #20c997 !important; }
        .card-ofc-kidney { border-top: 4px solid #e83e8c !important; }
        .card-lgo-kidney { border-top: 4px solid #0891b2 !important; }
        .card-sss-kidney { border-top: 4px solid #6610f2 !important; }

        /* Custom colored glow effects on hover */
        .card-ucs:hover { border-color: #28a745 !important; box-shadow: 0 12px 24px rgba(40, 167, 69, 0.15) !important; transform: translateY(-5px); }
        .card-ofc:hover { border-color: #ffc107 !important; box-shadow: 0 12px 24px rgba(255, 193, 7, 0.15) !important; transform: translateY(-5px); }
        .card-csop:hover { border-color: #dc3545 !important; box-shadow: 0 12px 24px rgba(220, 53, 69, 0.15) !important; transform: translateY(-5px); }
        .card-cipn:hover { border-color: #6f42c1 !important; box-shadow: 0 12px 24px rgba(111, 66, 193, 0.15) !important; transform: translateY(-5px); }
        .card-lgo:hover { border-color: #0dcaf0 !important; box-shadow: 0 12px 24px rgba(13, 202, 240, 0.15) !important; transform: translateY(-5px); }
        .card-ucs-kidney:hover { border-color: #20c997 !important; box-shadow: 0 12px 24px rgba(32, 201, 151, 0.15) !important; transform: translateY(-5px); }
        .card-ofc-kidney:hover { border-color: #e83e8c !important; box-shadow: 0 12px 24px rgba(232, 62, 140, 0.15) !important; transform: translateY(-5px); }
        .card-lgo-kidney:hover { border-color: #0891b2 !important; box-shadow: 0 12px 24px rgba(8, 145, 178, 0.15) !important; transform: translateY(-5px); }
        .card-sss-kidney:hover { border-color: #6610f2 !important; box-shadow: 0 12px 24px rgba(102, 16, 242, 0.15) !important; transform: translateY(-5px); }

        .icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 12px;
        }
        /* Soft background helpers */
        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1) !important; }
        .bg-success-soft { background-color: rgba(40, 167, 69, 0.1) !important; }
        .bg-teal-soft { background-color: rgba(32, 201, 151, 0.1) !important; }
        .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1) !important; }
        .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1) !important; }
        .bg-purple-soft { background-color: rgba(111, 66, 193, 0.1) !important; }
        .bg-info-soft { background-color: rgba(23, 162, 184, 0.1) !important; }
        .bg-cyan-soft { background-color: rgba(8, 145, 178, 0.1) !important; }
        .bg-indigo-soft { background-color: rgba(102, 16, 242, 0.1) !important; }

        .text-teal { color: #20c997 !important; }
        .text-indigo { color: #6610f2 !important; }
        .text-purple { color: #6f42c1 !important; }
        .text-cyan { color: #0891b2 !important; }

        .btn-outline-teal {
            color: #20c997;
            border-color: #20c997;
        }
        .btn-outline-teal:hover {
            color: #fff;
            background-color: #20c997;
            border-color: #20c997;
        }
        .btn-outline-indigo {
            color: #6610f2;
            border-color: #6610f2;
        }
        .btn-outline-indigo:hover {
            color: #fff;
            background-color: #6610f2;
            border-color: #6610f2;
        }
        .btn-outline-purple {
            color: #6f42c1;
            border-color: #6f42c1;
        }
        .btn-outline-purple:hover {
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }
        .btn-outline-cyan {
            color: #0891b2;
            border-color: #0891b2;
        }
        .btn-outline-cyan:hover {
            color: #fff;
            background-color: #0891b2;
            border-color: #0891b2;
        }
    </style>

@endsection
