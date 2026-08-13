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

        .card-ucs:hover { 
            border-color: #10b981 !important; 
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.15) !important; 
            transform: translateY(-5px); 
        }

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

        .btn-outline-emerald { color: #10b981; border-color: #10b981; }
        .btn-outline-emerald:hover { color: #fff; background-color: #10b981; border-color: #10b981; }
    </style>

@endsection
