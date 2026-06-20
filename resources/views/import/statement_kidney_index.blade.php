@extends('layouts.app')

@section('content')

    <!-- Section 2: Specialty & Dialysis Claims -->
    @if ($hasLookupIcode_kidney)
    <div class="mt-4 mb-4">
        <h5 class="text-secondary fw-bold mb-3 d-flex align-items-center">
            <span class="badge bg-danger me-2"><i class="bi bi-droplet-fill"></i></span>
            ระบบนำเข้าข้อมูล Statement ฟอกไต 
        </h5>
        <div class="row g-4">
            <!-- 1. UCS Kidney -->
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
                            ข้อมูลการชดเชยค่าบริการผู้ป่วยไตวายเรื้อรัง สิทธิ์ประกันสุขภาพถ้วนหน้า
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_ucs_kidney') }}" class="btn btn-outline-teal btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. OFC Kidney -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-ofc-kidney">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-pink-soft text-pink me-3">
                                <i class="bi bi-heart-pulse fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-OFC [ฟอกไต]</h5>
                                <span class="badge bg-pink-soft text-pink small mt-1">สิทธิ์ฟอกไตข้าราชการ</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการผู้ป่วยไตวายเรื้อรัง สิทธิ์ข้าราชการ
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_ofc_csop') }}" class="btn btn-outline-pink btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. BKK Kidney -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-bkk-kidney">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-violet-soft text-violet me-3">
                                <i class="bi bi-clipboard-pulse fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-BKK [ฟอกไต]</h5>
                                <span class="badge bg-violet-soft text-violet small mt-1">สิทธิ์ฟอกไต กทม.</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการผู้ป่วยฟอกไต กทม.
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_bkk_kidney') }}" class="btn btn-outline-violet btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. BMT Kidney -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-bmt-kidney">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-fuchsia-soft text-fuchsia me-3">
                                <i class="bi bi-clipboard-pulse fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-BMT [ฟอกไต]</h5>
                                <span class="badge bg-fuchsia-soft text-fuchsia small mt-1">สิทธิ์ฟอกไต ขสมก.</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการผู้ป่วยฟอกไต ขสมก.
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_bmt_kidney') }}" class="btn btn-outline-fuchsia btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. LGO Kidney -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm stm-card card-lgo-kidney">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box bg-sky-soft text-sky me-3">
                                <i class="bi bi-water fs-3"></i>
                            </div>
                            <div>
                                <h5 class="card-title fw-bold mb-0 text-dark">STM-LGO [ฟอกไต]</h5>
                                <span class="badge bg-sky-soft text-sky small mt-1">สิทธิ์ฟอกไต อปท.</span>
                            </div>
                        </div>
                        <p class="text-muted small flex-grow-1">
                            ข้อมูลการชดเชยค่าบริการผู้ป่วยฟอกไต พนักงานส่วนท้องถิ่น
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_lgo_kidney') }}" class="btn btn-outline-sky btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. SSS Kidney -->
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
                            ข้อมูลการชดเชยค่าบริการผู้ป่วยฟอกไต ประกันสังคม
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ url('/import/stm_sss_kidney') }}" class="btn btn-outline-indigo btn-sm fw-bold d-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-cloud-arrow-up-fill"></i> นำเข้าข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

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
        .card-ucs-kidney { border-top: 4px solid #0d9488 !important; }
        .card-ofc-kidney { border-top: 4px solid #db2777 !important; }
        .card-lgo-kidney { border-top: 4px solid #0284c7 !important; }
        .card-sss-kidney { border-top: 4px solid #4f46e5 !important; }
        .card-bkk-kidney { border-top: 4px solid #6d28d9 !important; }
        .card-bmt-kidney { border-top: 4px solid #c026d3 !important; }

        /* Custom colored glow effects on hover */
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
        .bg-teal-soft { background-color: rgba(13, 148, 136, 0.08) !important; }
        .bg-pink-soft { background-color: rgba(219, 39, 119, 0.08) !important; }
        .bg-sky-soft { background-color: rgba(2, 132, 199, 0.08) !important; }
        .bg-indigo-soft { background-color: rgba(79, 70, 229, 0.08) !important; }
        .bg-violet-soft { background-color: rgba(109, 40, 217, 0.08) !important; }
        .bg-fuchsia-soft { background-color: rgba(192, 38, 211, 0.08) !important; }

        /* Text colors */
        .text-teal { color: #0d9488 !important; }
        .text-pink { color: #db2777 !important; }
        .text-sky { color: #0284c7 !important; }
        .text-indigo { color: #4f46e5 !important; }
        .text-violet { color: #6d28d9 !important; }
        .text-fuchsia { color: #c026d3 !important; }

        /* Button styling */
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
