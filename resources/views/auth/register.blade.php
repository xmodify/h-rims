<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('images/favicon_darkgreen.ico') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('images/favicon_darkgreen.ico') }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RiMS - ลงทะเบียนผู้ใช้งานใหม่</title>

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
        .register-card {
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
            padding: 3rem 2.5rem;
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
        .btn-register {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 700;
            border-radius: 12px;
            color: white;
            transition: all 0.25s ease-in-out;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }
        .login-link {
            color: #10b981;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.2s ease;
        }
        .login-link:hover {
            color: #047857;
            text-decoration: underline;
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
            <div class="card register-card">
                <div class="row g-0">
                    <!-- Left Side (Logo and Intro) -->
                    <div class="col-md-5 logo-section text-center">
                        <img src="{{ asset('images/logo_hrims.png') }}" alt="RiMS Logo" class="img-fluid" style="max-height: 220px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));">
                    </div>
                    
                    <!-- Right Side (Form) -->
                    <div class="col-md-7 form-section">
                        <div class="mb-4">
                            <h4 class="fw-bold text-dark mb-1">ลงทะเบียนผู้ใช้งานใหม่</h4>
                            <p class="text-muted small mb-0">โปรดกรอกรายละเอียดข้อมูลให้ถูกต้องเพื่อขอรับสิทธิ์เข้าใช้งานระบบ</p>
                        </div>
                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <input type="hidden" name="active" value="N">
                            <input type="hidden" name="status" value="user">

                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold text-secondary">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required placeholder="กรอกชื่อ-นามสกุลจริง" autofocus>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold text-secondary">อีเมล (Email) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required placeholder="example@mail.com">
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- CID -->
                            <div class="mb-3">
                                <label for="cid" class="form-label fw-bold text-secondary">เลขบัตรประชาชน (CID) <span class="text-muted fw-normal">(ไม่บังคับ)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-list"></i></span>
                                    <input id="cid" type="text" class="form-control @error('cid') is-invalid @enderror" name="cid" value="{{ old('cid') }}" placeholder="เลขบัตรประชาชน 13 หลัก (ใช้เชื่อมต่อ Provider ID)" maxlength="13" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    @error('cid')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold text-secondary">รหัสผ่าน <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required placeholder="รหัสผ่านไม่ต่ำกว่า 6 ตัวอักษร">
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label for="password-confirm" class="form-label fw-bold text-secondary">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required placeholder="กรอกรหัสผ่านเดิมอีกครั้งเพื่อยืนยัน">
                                </div>
                            </div>

                            <!-- Submit button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-register py-2 fw-bold text-white">
                                    <i class="bi bi-person-plus-fill me-1"></i> ลงทะเบียนเข้าใช้งาน
                                </button>
                            </div>

                            <!-- Login link -->
                            <div class="text-center mt-3">
                                <span class="text-muted small">มีบัญชีผู้ใช้งานแล้ว? </span>
                                <a href="{{ route('login') }}" class="login-link small">เข้าสู่ระบบที่นี่</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
