<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('images/favicon_darkgreen.ico') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('images/favicon_darkgreen.ico') }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RiMS - เข้าสู่ระบบ</title>

    <!-- Local Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- SweetAlert2 -->
    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0fdf4 100%);
            min-height: 100vh;
            display: flex;
            align-items: start;
            font-family: 'Nunito', 'Inter', sans-serif;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(25, 135, 84, 0.08);
            background: #ffffff;
            overflow: hidden;
            border-top: 5px solid #198754;
            transition: all 0.3s ease-in-out;
        }
        .logo-section {
            background: #f8fafc;
            border-right: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem 2rem;
        }
        .form-section {
            padding: 3.5rem 2.5rem;
        }
        .input-group-text {
            background-color: #f8fafc;
            border-right: none;
            color: #64748b;
            border-color: #e2e8f0;
            padding-left: 1.25rem;
            padding-right: 0.75rem;
        }
        .form-control {
            border-left: none;
            padding: 0.75rem 1rem 0.75rem 0;
            background-color: #ffffff;
            border-color: #e2e8f0;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #e2e8f0;
            box-shadow: none;
        }
        .input-group:focus-within .input-group-text {
            border-color: #10b981;
            color: #10b981;
        }
        .input-group:focus-within .form-control {
            border-color: #10b981;
        }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            border-radius: 12px;
            color: white;
            transition: all 0.25s ease-in-out;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.25);
            color: white;
        }
        .btn-provider {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            border-radius: 12px;
            color: white;
            transition: all 0.25s ease-in-out;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
            text-decoration: none;
        }
        .btn-provider:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.25);
            color: white;
        }
        .register-link {
            color: #10b981;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s ease;
        }
        .register-link:hover {
            color: #047857;
            text-decoration: underline;
        }
        .form-request-btn {
            color: #475569;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 16px;
            border-radius: 9999px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.25s ease-in-out;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }
        .form-request-btn:hover {
            color: #047857;
            background: #ecfdf5;
            border-color: #6ee7b7;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.18);
            transform: translateY(-1px);
        }
        .pdf-icon-badge {
            color: #ef4444;
            font-size: 1.05rem;
            display: inline-flex;
            align-items: center;
            transition: transform 0.2s ease;
        }
        .form-request-btn:hover .pdf-icon-badge {
            transform: scale(1.15);
        }
        .form-label {
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }
    </style>
</head>
<body>

<div class="container" style="padding-top: 8vh; padding-bottom: 5vh;">
    <div class="row justify-content-center align-items-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card login-card">
                <div class="row g-0">
                    <!-- Left Side (Logo and Intro) -->
                    <div class="col-md-5 logo-section text-center">
                        <img src="{{ asset('images/logo_hrims.png') }}" alt="RiMS Logo" class="img-fluid" style="max-height: 220px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));">
                    </div>
                    
                    <!-- Right Side (Form) -->
                    <div class="col-md-7 form-section">
                        <div class="mb-4">
                            <h4 class="fw-bold text-dark mb-1">เข้าสู่ระบบ</h4>
                            <p class="text-muted small mb-0">ระบุบัญชีผู้ใช้งานของท่านเพื่อเข้าสู่ระบบ RiMS</p>
                        </div>
                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold text-secondary">อีเมล (Email)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required placeholder="example@mail.com" autofocus>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold text-secondary">รหัสผ่าน</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required placeholder="กรอกรหัสผ่านเข้าใช้งาน">
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            @php
                                $providerActiveSetting = null;
                                try {
                                    if (\Illuminate\Support\Facades\Schema::hasTable('main_setting')) {
                                        $providerActiveSetting = \Illuminate\Support\Facades\DB::table('main_setting')
                                            ->where('name', 'provider_id_active')
                                            ->value('value');
                                    }
                                } catch (\Exception $e) {
                                    $providerActiveSetting = null;
                                }
                            @endphp

                            <!-- Buttons -->
                            <div class="row g-2 mb-3">
                                <div class="{{ $providerActiveSetting === 'Y' ? 'col-sm-6' : 'col-12' }}">
                                    <button type="submit" class="btn btn-login w-100 py-2 fw-bold text-white d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
                                    </button>
                                </div>
                                @if($providerActiveSetting === 'Y')
                                <div class="col-sm-6">
                                    <a href="{{ route('auth.health-id.redirect') }}" class="btn btn-provider w-100 py-2 fw-bold text-white d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-shield-lock-fill"></i> เข้าด้วย Provider ID
                                    </a>
                                </div>
                                @endif
                            </div>

                            <!-- Register link -->
                            <div class="text-center mt-4">
                                <span class="text-muted small">ยังไม่มีบัญชีผู้ใช้งานระบบ? </span>
                                <a href="{{ route('register') }}" class="register-link small">สมัครสมาชิกใหม่ที่นี่</a>
                            </div>

                            <!-- แบบฟอร์มขอความอนุเคราะห์เข้าใช้งาน RiMS -->
                            <div class="text-center mt-3 pt-2">
                                <a href="javascript:void(0);" 
                                   class="form-request-btn small d-inline-flex align-items-center gap-2"
                                   data-bs-toggle="modal" 
                                   data-bs-target="#formRequestModal">
                                    <span class="pdf-icon-badge">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </span>
                                    <span>แบบฟอร์มขอความอนุเคราะห์เข้าใช้งาน RiMS</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal แบบฟอร์มขอความอนุเคราะห์เข้าใช้งานระบบ RiMS -->
<div class="modal fade" id="formRequestModal" tabindex="-1" aria-labelledby="formRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #fee2e2; color: #dc2626; font-size: 1.3rem;">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="formRequestModalLabel">
                            แบบฟอร์มขอความอนุเคราะห์เข้าใช้งานระบบ RiMS
                        </h5>
                        <small class="text-muted">ดาวน์โหลด พิมพ์ หรือบันทึกแบบฟอร์มเพื่อส่งขอเปิดสิทธิ์การใช้งานระบบ</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill d-flex align-items-center gap-1 shadow-sm fw-semibold" onclick="printPdfDocument()" title="สั่งพิมพ์เอกสารนี้">
                        <i class="bi bi-printer-fill text-primary"></i>
                        <span>พิมพ์เอกสาร</span>
                    </button>
                    <a href="{{ route('downloads.rims-request-form', ['download' => 1]) }}" 
                       download="แบบฟอร์มขอความอนุเคราะห์เข้าใช้งานระบบRiMS.pdf" 
                       class="btn btn-success btn-sm px-3 rounded-pill d-flex align-items-center gap-1 shadow-sm fw-semibold text-white" 
                       title="บันทึกไฟล์ PDF ลงเครื่อง">
                        <i class="bi bi-download"></i>
                        <span>บันทึก / ดาวน์โหลด</span>
                    </a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Modal Body (PDF Viewer) -->
            <div class="modal-body p-0 position-relative" style="height: 72vh; background: #334155;">
                <div id="pdfLoadingSpinner" class="position-absolute top-50 start-50 translate-middle text-center text-white" style="z-index: 0;">
                    <div class="spinner-border text-light mb-2" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <div class="small">กำลังโหลดเอกสาร PDF...</div>
                </div>
                <iframe id="pdfFrame" 
                        data-src="{{ route('downloads.rims-request-form') }}#toolbar=1" 
                        class="w-100 h-100 border-0 position-relative" 
                        style="z-index: 1;"
                        title="แบบฟอร์มขอความอนุเคราะห์เข้าใช้งานระบบ RiMS"
                        onload="var sp = document.getElementById('pdfLoadingSpinner'); if(sp) sp.style.display='none';">
                </iframe>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer d-flex justify-content-between align-items-center py-2 px-4 bg-light" style="border-top: 1px solid #e2e8f0;">
                <div class="small text-muted d-flex align-items-center gap-1">
                    <i class="bi bi-info-circle-fill text-success"></i>
                    <span>กรุณากรอกข้อมูลในแบบฟอร์มให้ครบถ้วน จากนั้นส่งให้ผู้ดูแลระบบเพื่อเปิดสิทธิ์การใช้งาน</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold" onclick="printPdfDocument()">
                        <i class="bi bi-printer me-1"></i> พิมพ์เอกสาร
                    </button>
                    <a href="{{ route('downloads.rims-request-form', ['download' => 1]) }}" 
                       download="แบบฟอร์มขอความอนุเคราะห์เข้าใช้งานระบบRiMS.pdf" 
                       class="btn btn-primary btn-sm px-3 rounded-pill fw-semibold">
                        <i class="bi bi-download me-1"></i> ดาวน์โหลด / บันทึก (Save)
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">
                        ปิด
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function printPdfDocument() {
    const iframe = document.getElementById('pdfFrame');
    if (iframe && iframe.contentWindow) {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            return;
        } catch (e) {
            console.warn('Direct iframe print failed, falling back to window.open:', e);
        }
    }
    const pdfUrl = "{{ route('downloads.rims-request-form') }}";
    const win = window.open(pdfUrl, '_blank');
    if (win) {
        win.focus();
        setTimeout(function() {
            try { win.print(); } catch(err) {}
        }, 800);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('formRequestModal');
    const trigger = document.querySelector('[data-bs-target="#formRequestModal"]');

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function() {
            const iframe = document.getElementById('pdfFrame');
            if (iframe && (!iframe.getAttribute('src') || iframe.getAttribute('src') === 'about:blank')) {
                iframe.setAttribute('src', iframe.getAttribute('data-src'));
            }
        });
    }

    if (trigger && modalEl) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.bootstrap && window.bootstrap.Modal) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        });
    }
});
</script>

@if (session('register_success'))
<script>
Swal.fire({
    icon: 'success',
    title: 'ลงทะเบียนสำเร็จ',
    text: '{{ session('register_success') }}',
    confirmButtonText: 'ตกลง',
    confirmButtonColor: '#198754',
    borderRadius: '15px'
});
</script>
@endif

@if (session('error'))
<script>
Swal.fire({
    icon: 'error',
    title: 'เกิดข้อผิดพลาด',
    text: '{{ session('error') }}',
    confirmButtonText: 'ตกลง',
    confirmButtonColor: '#d33',
    borderRadius: '15px'
});
</script>
@endif

@if ($errors->any())
<script>
Swal.fire({
    icon: 'error',
    title: 'เกิดข้อผิดพลาด',
    text: '{{ $errors->first() }}',
    confirmButtonText: 'ตกลง',
    confirmButtonColor: '#d33',
    borderRadius: '15px'
});
</script>
@endif

</body>
</html>