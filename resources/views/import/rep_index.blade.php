@extends('layouts.app')

@section('content')

    <!-- Section 1: General OP-IP Claims -->
    <div class="mb-4">
        <h5 class="text-secondary fw-bold mb-3 d-flex align-items-center">
            <span class="badge bg-success me-2"><i class="bi bi-grid-fill"></i></span>
            ระบบนำเข้าข้อมูลการตรวจสอบเบื้องต้น (REP) OP-IP
        </h5>
        <div class="row g-4">
            <!-- 1. REP UCS [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-ucs">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-emerald-soft text-emerald me-3">
                                <i class="bi bi-shield-fill-check fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">REP-UCS [OP-IP]</h5>
                                <span class="badge bg-emerald-soft text-emerald small mt-1">สิทธิ์ประกันสุขภาพถ้วนหน้า</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลรายงานการชดเชยค่าบริการระบบหลักประกันสุขภาพถ้วนหน้า (UCS) จาก e-Claim ทั้งผู้ป่วยนอกและผู้ป่วยใน เพื่อใช้ตรวจสอบความถูกต้องและวิเคราะห์เคสที่ติดขัด/ปฏิเสธการจ่าย (Deny)
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/rep_ucs') }}" class="btn btn-outline-emerald btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 2. REP OFC [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-ofc">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-primary-soft text-primary me-3">
                                <i class="bi bi-person-workspace fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">REP-OFC [OP-IP]</h5>
                                <span class="badge bg-primary-soft text-primary small mt-1">สิทธิ์ข้าราชการ/CSOP</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการระบบสิทธิ์สวัสดิการข้าราชการ (OFC/CSOP) จาก e-Claim ทั้งประเภทผู้ป่วยนอกและผู้ป่วยใน เพื่อใช้วิเคราะห์และตรวจสอบเคสติดขัด
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/rep_ofc') }}" class="btn btn-outline-primary btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 3. REP SSS [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-sss">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-indigo-soft text-indigo me-3">
                                <i class="bi bi-shield-fill-plus fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">REP-SSS [OP-IP]</h5>
                                <span class="badge bg-indigo-soft text-indigo small mt-1">สิทธิ์ประกันสังคม</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการระบบสิทธิ์ประกันสังคม (SSS) จาก e-Claim ทั้งประเภทผู้ป่วยนอกและผู้ป่วยใน เพื่อใช้วิเคราะห์และตรวจสอบเคสติดขัด
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/rep_sss') }}" class="btn btn-outline-indigo btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 4. REP LGO [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-lgo">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-cyan-soft text-cyan me-3">
                                <i class="bi bi-building fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">REP-LGO [OP-IP]</h5>
                                <span class="badge bg-cyan-soft text-cyan small mt-1">สิทธิ์องค์กรปกครองส่วนท้องถิ่น</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการระบบสิทธิ์พนักงานส่วนท้องถิ่น (LGO/อปท.) จาก e-Claim ทั้งประเภทผู้ป่วยนอกและผู้ป่วยใน เพื่อใช้วิเคราะห์และตรวจสอบเคสติดขัด
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/rep_lgo') }}" class="btn btn-outline-cyan btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 5. REP BKK [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-bkk">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-success-soft text-success me-3">
                                <i class="bi bi-geo-alt-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">REP-BKK [OP-IP]</h5>
                                <span class="badge bg-success-soft text-success small mt-1">สิทธิ์ข้าราชการ กทม.</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์เจ้าหน้าที่กรุงเทพมหานคร (BKK) จาก e-Claim ทั้งผู้ป่วยนอกและผู้ป่วยใน เพื่อใช้วิเคราะห์และตรวจสอบเคสติดขัด
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/rep_bkk') }}" class="btn btn-outline-success btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 6. REP BMT [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-bmt">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-warning-soft text-warning me-3">
                                <i class="bi bi-bus-front-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">REP-BMT [OP-IP]</h5>
                                <span class="badge bg-warning-soft text-warning small mt-1">สิทธิ์พนักงาน ขสมก.</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์พนักงานองค์การขนส่งมวลชนกรุงเทพ (BMT/ขสมก.) จาก e-Claim ทั้งผู้ป่วยนอกและผู้ป่วยใน เพื่อใช้วิเคราะห์และตรวจสอบเคสติดขัด
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/rep_bmt') }}" class="btn btn-outline-warning btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 7. REP SRT [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-srt">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-purple-soft text-purple me-3">
                                <i class="bi bi-train-front-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">REP-SRT [OP-IP]</h5>
                                <span class="badge bg-purple-soft text-purple small mt-1">สิทธิ์พนักงานการรถไฟฯ</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์เจ้าหน้าที่การรถไฟแห่งประเทศไทย (SRT) จาก e-Claim ทั้งผู้ป่วยนอกและผู้ป่วยใน เพื่อใช้วิเคราะห์และตรวจสอบเคสติดขัด
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/rep_srt') }}" class="btn btn-outline-purple btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 8. REP PVT [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-pvt">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-pink-soft text-pink me-3">
                                <i class="bi bi-journal-bookmark-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">REP-PVT [OP-IP]</h5>
                                <span class="badge bg-pink-soft text-pink small mt-1">สิทธิ์ครูโรงเรียนเอกชน</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์ครูโรงเรียนเอกชน (PVT) จาก e-Claim ทั้งผู้ป่วยนอกและผู้ป่วยใน เพื่อใช้วิเคราะห์และตรวจสอบเคสติดขัด
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/rep_pvt') }}" class="btn btn-outline-pink btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        
        .card-ucs { border-top: 4px solid #10b981 !important; }
        .card-ofc { border-top: 4px solid #0d6efd !important; }
        .card-sss { border-top: 4px solid #6f42c1 !important; }
        .card-lgo { border-top: 4px solid #17a2b8 !important; }
        .card-bkk { border-top: 4px solid #198754 !important; }
        .card-bmt { border-top: 4px solid #fd7e14 !important; }
        .card-srt { border-top: 4px solid #6f42c1 !important; }
        .card-pvt { border-top: 4px solid #d63384 !important; }
        
        .card-ucs:hover { border-color: #10b981 !important; box-shadow: 0 12px 24px rgba(16, 185, 129, 0.15) !important; transform: translateY(-5px); }
        .card-ofc:hover { border-color: #0d6efd !important; box-shadow: 0 12px 24px rgba(13, 110, 253, 0.15) !important; transform: translateY(-5px); }
        .card-sss:hover { border-color: #6f42c1 !important; box-shadow: 0 12px 24px rgba(111, 66, 193, 0.15) !important; transform: translateY(-5px); }
        .card-lgo:hover { border-color: #17a2b8 !important; box-shadow: 0 12px 24px rgba(23, 162, 184, 0.15) !important; transform: translateY(-5px); }
        .card-bkk:hover { border-color: #198754 !important; box-shadow: 0 12px 24px rgba(25, 135, 84, 0.15) !important; transform: translateY(-5px); }
        .card-bmt:hover { border-color: #fd7e14 !important; box-shadow: 0 12px 24px rgba(253, 126, 20, 0.15) !important; transform: translateY(-5px); }
        .card-srt:hover { border-color: #6f42c1 !important; box-shadow: 0 12px 24px rgba(111, 66, 193, 0.15) !important; transform: translateY(-5px); }
        .card-pvt:hover { border-color: #d63384 !important; box-shadow: 0 12px 24px rgba(214, 51, 132, 0.15) !important; transform: translateY(-5px); }

        .icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 12px;
        }

        .bg-emerald-soft { background-color: rgba(16, 185, 129, 0.08) !important; }
        .text-emerald { color: #10b981 !important; }

        .bg-primary-soft { background-color: rgba(13, 110, 253, 0.08) !important; }
        .text-primary { color: #0d6efd !important; }

        .bg-indigo-soft { background-color: rgba(111, 66, 193, 0.08) !important; }
        .text-indigo { color: #6f42c1 !important; }

        .bg-cyan-soft { background-color: rgba(23, 162, 184, 0.08) !important; }
        .text-cyan { color: #17a2b8 !important; }
        
        .bg-success-soft { background-color: rgba(25, 135, 84, 0.08) !important; }
        .text-success { color: #198754 !important; }
        
        .bg-warning-soft { background-color: rgba(253, 126, 20, 0.08) !important; }
        .text-warning { color: #fd7e14 !important; }
        
        .bg-purple-soft { background-color: rgba(111, 66, 193, 0.08) !important; }
        .text-purple { color: #6f42c1 !important; }
        .bg-purple { background-color: #6f42c1 !important; }
        
        .bg-pink-soft { background-color: rgba(214, 51, 132, 0.08) !important; }
        .text-pink { color: #d63384 !important; }
        .bg-pink { background-color: #d63384 !important; }

        .btn-outline-emerald { color: #10b981; border-color: #10b981; }
        .btn-outline-emerald:hover { color: #fff; background-color: #10b981; border-color: #10b981; }

        .btn-outline-primary { color: #0d6efd; border-color: #0d6efd; }
        .btn-outline-primary:hover { color: #fff; background-color: #0d6efd; border-color: #0d6efd; }

        .btn-outline-indigo { color: #6f42c1; border-color: #6f42c1; }
        .btn-outline-indigo:hover { color: #fff; background-color: #6f42c1; border-color: #6f42c1; }

        .btn-outline-cyan { color: #17a2b8; border-color: #17a2b8; }
        .btn-outline-cyan:hover { color: #fff; background-color: #17a2b8; border-color: #17a2b8; }
        
        .btn-outline-success { color: #198754; border-color: #198754; }
        .btn-outline-success:hover { color: #fff; background-color: #198754; border-color: #198754; }
        
        .btn-outline-warning { color: #fd7e14; border-color: #fd7e14; }
        .btn-outline-warning:hover { color: #fff; background-color: #fd7e14; border-color: #fd7e14; }
        
        .btn-outline-purple { color: #6f42c1; border-color: #6f42c1; }
        .btn-outline-purple:hover { color: #fff; background-color: #6f42c1; border-color: #6f42c1; }
        
        .btn-outline-pink { color: #d63384; border-color: #d63384; }
        .btn-outline-pink:hover { color: #fff; background-color: #d63384; border-color: #d63384; }
    </style>

@endsection
