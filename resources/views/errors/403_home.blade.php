@extends('layouts.app')

@section('content')
<div class="container py-5 text-center">
    <div class="card shadow-sm border-0 p-5 mt-5 bg-white" style="border-radius: 20px;">
        <div class="mb-4">
            <i class="bi bi-shield-lock text-warning" style="font-size: 5rem;"></i>
        </div>
        <h3 class="fw-bold text-dark">ขออภัย คุณไม่มีสิทธิ์เข้าถึงหน้านี้</h3>
        <p class="text-muted">กรุณาติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์การใช้งาน (Home Detail)</p>
        <div class="mt-4 d-flex justify-content-center gap-2">
            <a href="{{ url('/home') }}" class="btn btn-primary px-4 rounded-pill">
                <i class="bi bi-house-door me-1"></i> กลับหน้าหลัก
            </a>
            <button onclick="history.back()" class="btn btn-outline-secondary px-4 rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
            </button>
        </div>
    </div>
</div>
@endsection
