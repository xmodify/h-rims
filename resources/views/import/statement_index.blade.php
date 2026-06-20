@extends('layouts.app')

@section('content')


    <!-- Section 1: General OP-IP Claims -->
    <div class="mb-4">
        <h5 class="text-secondary fw-bold mb-3 d-flex align-items-center">
            <span class="badge bg-primary me-2"><i class="bi bi-grid-fill"></i></span>
            ระบบนำเข้าข้อมูล Statement OP-IP 
        </h5>
        <div class="row g-4">
            <!-- 1. UCS General [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-ucs">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-emerald-soft text-emerald me-3">
                                <i class="bi bi-shield-fill-check fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-UCS [OP-IP]</h5>
                                <span class="badge bg-emerald-soft text-emerald small mt-1">สิทธิ์ประกันสุขภาพถ้วนหน้า</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการระบบหลักประกันสุขภาพถ้วนหน้า (UCS) ทั้งผู้ป่วยนอกและผู้ป่วยใน
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_ucs') }}" class="btn btn-outline-emerald btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
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
                            <div class="icon-box bg-amber-soft text-amber me-3">
                                <i class="bi bi-person-badge-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-OFC [OP-IP]</h5>
                                <span class="badge bg-amber-soft text-amber small mt-1">สิทธิ์ข้าราชการ</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์สวัสดิการข้าราชการ (OFC)
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_ofc') }}" class="btn btn-outline-amber btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. BKK General [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-bkk-general">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-royal-soft text-royal me-3">
                                <i class="bi bi-building fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-BKK [OP-IP]</h5>
                                <span class="badge bg-royal-soft text-royal small mt-1">สิทธิ์ข้าราชการ กทม.</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์ข้าราชการกรุงเทพมหานคร (BKK)
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_bkk') }}" class="btn btn-outline-royal btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. BMT General [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-bmt-general">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-rose-soft text-rose me-3">
                                <i class="bi bi-truck-front-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-BMT [OP-IP]</h5>
                                <span class="badge bg-rose-soft text-rose small mt-1">สิทธิ์ข้าราชการ ขสมก.</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์ข้าราชการ ขสมก. (BMT)
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_bmt') }}" class="btn btn-outline-rose btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
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
                            <div class="icon-box bg-cyan-soft text-cyan me-3">
                                <i class="bi bi-geo-alt-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-LGO [OP-IP]</h5>
                                <span class="badge bg-cyan-soft text-cyan small mt-1">สิทธิ์พนักงานส่วนท้องถิ่น</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์พนักงานส่วนท้องถิ่น (LGO)
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_lgo') }}" class="btn btn-outline-cyan btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
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
                            <div class="icon-box bg-orange-soft text-orange me-3">
                                <i class="bi bi-droplet-half fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-OFC [CSOP]</h5>
                                <span class="badge bg-orange-soft text-orange small mt-1">สิทธิ์ข้าราชการ (CSOP)</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการผู้ป่วยสิทธิ์ข้าราชการ (CSOP)
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_ofc_csop') }}" class="btn btn-outline-orange btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. OFC CIPN -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-cipn">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-purple-soft text-purple me-3">
                                <i class="bi bi-arrow-left-right fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-OFC [CIPN]</h5>
                                <span class="badge bg-purple-soft text-purple small mt-1">สิทธิ์ข้าราชการ (CIPN)</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการผู้ป่วยสิทธิ์ข้าราชการ (CSOP)
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_ofc_cipn') }}" class="btn btn-outline-purple btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 8. SRT General [OP-IP] -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-srt-general">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-slate-soft text-slate me-3">
                                <i class="bi bi-train-front-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-SRT [OP-IP]</h5>
                                <span class="badge bg-slate-soft text-slate small mt-1">การรถไฟแห่งประเทศไทย</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการสิทธิ์การรถไฟแห่งประเทศไทย (SRT)
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_srt') }}" class="btn btn-outline-slate btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
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
        
        /* Distinct Border Top Colors */
        .card-ucs { border-top: 4px solid #10b981 !important; }
        .card-ofc { border-top: 4px solid #f59e0b !important; }
        .card-bkk-general { border-top: 4px solid #2563eb !important; }
        .card-bmt-general { border-top: 4px solid #e11d48 !important; }
        .card-csop { border-top: 4px solid #ea580c !important; }
        .card-cipn { border-top: 4px solid #7c3aed !important; }
        .card-lgo { border-top: 4px solid #0891b2 !important; }
        .card-srt-general { border-top: 4px solid #64748b !important; }
        .card-ucs-kidney { border-top: 4px solid #0d9488 !important; }
        .card-ofc-kidney { border-top: 4px solid #db2777 !important; }
        .card-lgo-kidney { border-top: 4px solid #0284c7 !important; }
        .card-sss-kidney { border-top: 4px solid #4f46e5 !important; }
        .card-bkk-kidney { border-top: 4px solid #6d28d9 !important; }
        .card-bmt-kidney { border-top: 4px solid #c026d3 !important; }

        /* Custom colored glow effects on hover */
        .card-ucs:hover { border-color: #10b981 !important; box-shadow: 0 12px 24px rgba(16, 185, 129, 0.15) !important; transform: translateY(-5px); }
        .card-ofc:hover { border-color: #f59e0b !important; box-shadow: 0 12px 24px rgba(245, 158, 11, 0.15) !important; transform: translateY(-5px); }
        .card-bkk-general:hover { border-color: #2563eb !important; box-shadow: 0 12px 24px rgba(37, 99, 235, 0.15) !important; transform: translateY(-5px); }
        .card-bmt-general:hover { border-color: #e11d48 !important; box-shadow: 0 12px 24px rgba(225, 29, 72, 0.15) !important; transform: translateY(-5px); }
        .card-csop:hover { border-color: #ea580c !important; box-shadow: 0 12px 24px rgba(234, 88, 12, 0.15) !important; transform: translateY(-5px); }
        .card-cipn:hover { border-color: #7c3aed !important; box-shadow: 0 12px 24px rgba(124, 58, 237, 0.15) !important; transform: translateY(-5px); }
        .card-lgo:hover { border-color: #0891b2 !important; box-shadow: 0 12px 24px rgba(8, 145, 178, 0.15) !important; transform: translateY(-5px); }
        .card-srt-general:hover { border-color: #64748b !important; box-shadow: 0 12px 24px rgba(100, 116, 139, 0.15) !important; transform: translateY(-5px); }
        .card-ucs-kidney:hover { border-color: #0d9488 !important; box-shadow: 0 12px 24px rgba(13, 148, 136, 0.15) !important; transform: translateY(-5px); }
        .card-ofc-kidney:hover { border-color: #db2777 !important; box-shadow: 0 12px 24px rgba(219, 39, 119, 0.15) !important; transform: translateY(-5px); }
        .card-lgo-kidney:hover { border-color: #0284c7 !important; box-shadow: 0 12px 24px rgba(2, 132, 199, 0.15) !important; transform: translateY(-5px); }
        .card-sss-kidney:hover { border-color: #4f46e5 !important; box-shadow: 0 12px 24px rgba(79, 70, 229, 0.15) !important; transform: translateY(-5px); }
        .card-bkk-kidney:hover { border-color: #6d28d9 !important; box-shadow: 0 12px 24px rgba(109, 40, 217, 0.15) !important; transform: translateY(-5px); }
        .card-bmt-kidney:hover { border-color: #c026d3 !important; box-shadow: 0 12px 24px rgba(192, 38, 211, 0.15) !important; transform: translateY(-5px); }

        .icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 12px;
        }

        /* Soft background helpers */
        .bg-emerald-soft { background-color: rgba(16, 185, 129, 0.08) !important; }
        .bg-amber-soft { background-color: rgba(245, 158, 11, 0.08) !important; }
        .bg-royal-soft { background-color: rgba(37, 99, 235, 0.08) !important; }
        .bg-rose-soft { background-color: rgba(225, 29, 72, 0.08) !important; }
        .bg-orange-soft { background-color: rgba(234, 88, 12, 0.08) !important; }
        .bg-purple-soft { background-color: rgba(124, 58, 237, 0.08) !important; }
        .bg-cyan-soft { background-color: rgba(8, 145, 178, 0.08) !important; }
        .bg-slate-soft { background-color: rgba(100, 116, 139, 0.08) !important; }
        .bg-teal-soft { background-color: rgba(13, 148, 136, 0.08) !important; }
        .bg-pink-soft { background-color: rgba(219, 39, 119, 0.08) !important; }
        .bg-sky-soft { background-color: rgba(2, 132, 199, 0.08) !important; }
        .bg-indigo-soft { background-color: rgba(79, 70, 229, 0.08) !important; }
        .bg-violet-soft { background-color: rgba(109, 40, 217, 0.08) !important; }
        .bg-fuchsia-soft { background-color: rgba(192, 38, 211, 0.08) !important; }

        /* Text colors */
        .text-emerald { color: #10b981 !important; }
        .text-amber { color: #f59e0b !important; }
        .text-royal { color: #2563eb !important; }
        .text-rose { color: #e11d48 !important; }
        .text-orange { color: #ea580c !important; }
        .text-purple { color: #7c3aed !important; }
        .text-cyan { color: #0891b2 !important; }
        .text-slate { color: #64748b !important; }
        .text-teal { color: #0d9488 !important; }
        .text-pink { color: #db2777 !important; }
        .text-sky { color: #0284c7 !important; }
        .text-indigo { color: #4f46e5 !important; }
        .text-violet { color: #6d28d9 !important; }
        .text-fuchsia { color: #c026d3 !important; }

        /* Button styling */
        .btn-outline-emerald { color: #10b981; border-color: #10b981; }
        .btn-outline-emerald:hover { color: #fff; background-color: #10b981; border-color: #10b981; }

        .btn-outline-amber { color: #f59e0b; border-color: #f59e0b; }
        .btn-outline-amber:hover { color: #fff; background-color: #f59e0b; border-color: #f59e0b; }

        .btn-outline-royal { color: #2563eb; border-color: #2563eb; }
        .btn-outline-royal:hover { color: #fff; background-color: #2563eb; border-color: #2563eb; }

        .btn-outline-rose { color: #e11d48; border-color: #e11d48; }
        .btn-outline-rose:hover { color: #fff; background-color: #e11d48; border-color: #e11d48; }

        .btn-outline-orange { color: #ea580c; border-color: #ea580c; }
        .btn-outline-orange:hover { color: #fff; background-color: #ea580c; border-color: #ea580c; }

        .btn-outline-purple { color: #7c3aed; border-color: #7c3aed; }
        .btn-outline-purple:hover { color: #fff; background-color: #7c3aed; border-color: #7c3aed; }

        .btn-outline-cyan { color: #0891b2; border-color: #0891b2; }
        .btn-outline-cyan:hover { color: #fff; background-color: #0891b2; border-color: #0891b2; }

        .btn-outline-slate { color: #64748b; border-color: #64748b; }
        .btn-outline-slate:hover { color: #fff; background-color: #64748b; border-color: #64748b; }

        .btn-outline-teal { color: #0d9488; border-color: #0d9488; }
        .btn-outline-teal:hover { color: #fff; background-color: #0d9488; border-color: #0d9488; }

        .btn-outline-pink { color: #db2777; border-color: #db2777; }
        .btn-outline-pink:hover { color: #fff; background-color: #db2777; border-color: #db2777; }

        .btn-outline-sky { color: #0284c7; border-color: #0284c7; }
        .btn-outline-sky:hover { color: #fff; background-color: #0284c7; border-color: #0284c7; }

        .btn-outline-indigo { color: #4f46e5; border-color: #4f46e5; }
        .btn-outline-indigo:hover { color: #fff; background-color: #4f46e5; border-color: #4f46e5; }

        .btn-outline-violet { color: #6d28d9; border-color: #6d28d9; }
        .btn-outline-violet:hover { color: #fff; background-color: #6d28d9; border-color: #6d28d9; }

        .btn-outline-fuchsia { color: #c026d3; border-color: #c026d3; }
        .btn-outline-fuchsia:hover { color: #fff; background-color: #c026d3; border-color: #c026d3; }
    </style>

@endsection
