<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('images/favicon_darkgreen.ico') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('images/favicon_darkgreen.ico') }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RiMS - ยืนยันตัวตนเข้าระบบ (2FA)</title>

    <!-- Local Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- SweetAlert2 -->
    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    
    <style>
        body {
            background-color: #f4f6f9 !important;
            font-family: 'Nunito', 'Inter', sans-serif;
        }
        .verify-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            max-width: 440px;
            width: 100%;
        }
        .otp-input-field {
            letter-spacing: 0.6rem;
            padding-left: 1.8rem;
            font-size: 1.6rem;
            text-align: center;
            font-weight: 700;
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
        .otp-input-field:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        .otp-input-field::placeholder {
            color: #adb5bd;
            opacity: 0.6;
            letter-spacing: 0.6rem;
        }
        .btn-verify {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-verify:hover {
            background-color: #157347;
            border-color: #146c43;
        }
    </style>
</head>
<body>

<div class="container min-vh-100 d-flex align-items-start justify-content-center" style="padding-top: 8vh;">
    <div class="card verify-card p-4 p-sm-5 text-center">
        <!-- Shield Icon -->
        <div class="mb-4">
            <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center mx-auto" style="width: 70px; height: 70px;">
                <i class="bi bi-shield-fill-check text-success fs-1"></i>
            </div>
        </div>

        <h4 class="fw-bold text-dark mb-2">ยืนยันตัวตนเข้าระบบ (2FA)</h4>
        <p class="text-muted small mb-4">
            ระบบความปลอดภัยต้องการรหัสผ่านขั้นตอนที่สองเพื่อเข้าใช้งาน<br>
            กรุณากรอกรหัส OTP ที่ได้รับทาง <strong>หมอพร้อม LineOA</strong>
        </p>

        <!-- Form for Verification -->
        <form action="{{ route('auth.2fa.verify') }}" method="POST" class="mb-3">
            @csrf
            
            <div class="mb-3">
                <input type="text" 
                       name="otp_code" 
                       id="otp_code" 
                       class="form-control otp-input-field shadow-sm text-uppercase @error('otp_code') is-invalid @enderror" 
                       maxlength="6" 
                       placeholder="XXXXXX" 
                       pattern="[a-zA-Z0-9]{6}" 
                       required 
                       autocomplete="one-time-code"
                       autofocus>
                @error('otp_code')
                    <div class="text-danger text-start mt-1 small" style="font-size: 0.85rem; font-weight: bold; padding-left: 5px;">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <button type="submit" class="btn btn-verify btn-lg w-100 text-white fw-bold rounded-3 shadow-sm py-2.5 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-lock-fill"></i> ยืนยันรหัส OTP
            </button>
        </form>

        <!-- Form for Logout / Back to Login -->
        <form action="{{ route('logout') }}" method="POST" class="mb-4">
            @csrf
            <button type="submit" class="btn btn-light w-100 py-2.5 rounded-3 text-muted border border-light shadow-sm d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-arrow-left"></i> กลับไปหน้า Login
            </button>
        </form>

        <!-- Timer / Resend Links -->
        <div class="text-muted small">
            <div class="mt-2" id="resend-container">
                <form action="{{ route('auth.2fa.resend') }}" method="POST" id="resend-form" class="d-none">
                    @csrf
                    ไม่ได้รับรหัสใช่ไหม? 
                    <button type="submit" class="btn btn-link btn-sm p-0 text-success text-decoration-none fw-bold">
                        ส่งรหัสใหม่อีกครั้ง
                    </button>
                </form>
                <span id="countdown-text" class="text-muted">
                    หมดเวลาใน <strong id="timer-seconds">120</strong> วินาที
                </span>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timerSeconds = document.getElementById('timer-seconds');
        const countdownText = document.getElementById('countdown-text');
        const resendForm = document.getElementById('resend-form');

        // Check if there is an active OTP cooldown from the backend (using session's last sent)
        @php
            $lastSent = session('moph_alert_last_sent');
            $secondsSinceLast = $lastSent ? (time() - $lastSent) : 120;
            $remaining = 120 - $secondsSinceLast;
            if ($remaining < 0) $remaining = 0;
        @endphp
        
        let timeLeft = {{ $remaining }};

        if (timeLeft <= 0) {
            countdownText.classList.add('d-none');
            resendForm.classList.remove('d-none');
        } else {
            const timerInterval = setInterval(function () {
                timeLeft--;
                timerSeconds.innerText = Math.ceil(timeLeft);

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    countdownText.classList.add('d-none');
                    resendForm.classList.remove('d-none');
                }
            }, 1000);
        }

        // Auto submit form once user enters exactly 6 digits
        const otpInput = document.getElementById('otp_code');
        otpInput.addEventListener('input', function() {
            // Remove non-alphanumeric chars
            this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
            
            if (this.value.length === 6) {
                this.closest('form').submit();
            }
        });



        // Trigger SweetAlert for resend success
        @if (session('success_resend'))
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: '{{ session('success_resend') }}',
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#198754'
            }).then(() => {
                otpInput.focus();
            });
        @endif
    });
</script>
</body>
</html>
