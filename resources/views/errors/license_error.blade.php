<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลิขสิทธิ์โปรแกรม RiMS มีปัญหา | License Error</title>
    <!-- Google Fonts: Inter & Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-color: #f8fafc;
            --primary-accent: #f43f5e; /* Rose / Coral red */
            --warning-accent: #fbbf24; /* Amber yellow */
        }
        
        body {
            font-family: 'Sarabun', 'Inter', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient glows */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(244, 63, 94, 0.15);
            border-radius: 50%;
            top: 10%;
            left: 10%;
            filter: blur(100px);
            z-index: 0;
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 50%;
            bottom: 10%;
            right: 10%;
            filter: blur(120px);
            z-index: 0;
        }

        .error-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            text-align: center;
            z-index: 1;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .error-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(244, 63, 94, 0.1);
            color: var(--primary-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            border: 1px solid rgba(244, 63, 94, 0.2);
            animation: pulse 2s infinite;
        }

        .icon-box.pending {
            background: rgba(251, 191, 36, 0.1);
            color: var(--warning-accent);
            border-color: rgba(251, 191, 36, 0.2);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.4);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 15px rgba(244, 63, 94, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(244, 63, 94, 0);
            }
        }

        .hcode-badge {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .btn-modern {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff;
            font-weight: 600;
            border: none;
            padding: 10px 24px;
            border-radius: 12px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-modern:hover {
            background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary-modern {
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 24px;
            border-radius: 12px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary-modern:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .status-badge {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 12px;
            border-radius: 100px;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .status-expired {
            background: rgba(244, 63, 94, 0.2);
            color: #fda4af;
            border: 1px solid rgba(244, 63, 94, 0.3);
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.2);
            color: #fde047;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
    </style>
</head>
<body>

    <div class="error-card">
        @php
            $status = $licenseInfo['status'] ?? 'inactive';
            $isPending = ($status === 'pending');
        @endphp

        <div class="icon-box {{ $isPending ? 'pending' : '' }}">
            @if($isPending)
                <i class="bi bi-hourglass-split"></i>
            @else
                <i class="bi bi-shield-x"></i>
            @endif
        </div>

        <div class="hcode-badge">
            <i class="bi bi-building me-1"></i> รหัสหน่วยงาน (HCODE): {{ \App\Services\LicenseVerificationService::getHcode() }}
        </div>

        @if($isPending)
            <div class="status-badge status-pending">รออนุมัติลิขสิทธิ์ (Pending Approval)</div>
            <h2 class="fw-bold mb-3">อยู่ระหว่างรอการอนุมัติสิทธิ์การใช้งาน</h2>
            <p class="text-muted mb-4 lead small">
                ส่งคำร้องขอคีย์ลิขสิทธิ์โปรแกรม RiMS สำเร็จแล้วและอยู่ระหว่างรอเจ้าหน้าที่ดำเนินการอนุมัติสิทธิ์ โปรดเก็บรักษารหัสคีย์ลิขสิทธิ์ของท่านไว้
            </p>
        @elseif($status === 'expired')
            <div class="status-badge status-expired">ลิขสิทธิ์หมดอายุ (Expired)</div>
            <h2 class="fw-bold mb-3 text-danger">ลิขสิทธิ์หมดอายุการใช้งาน</h2>
            <p class="text-muted mb-4">
                ลิขสิทธิ์โปรแกรม RiMS ของหน่วยงานท่านหมดอายุแล้วเมื่อวันที่ 
                <strong>{{ \App\Services\LicenseVerificationService::formatThaiShortDate($licenseInfo['expires_at'] ?? '') }}</strong> 
                กรุณาติดต่อผู้พัฒนาเพื่อต่ออายุสิทธิ์
            </p>
        @elseif($status === 'suspended')
            <div class="status-badge status-expired">ลิขสิทธิ์ถูกระงับ (Suspended)</div>
            <h2 class="fw-bold mb-3 text-danger">การใช้งานถูกระงับ (Suspended)</h2>
            <p class="text-muted mb-4">
                สิทธิ์การเข้าถึงระบบ RiMS ของท่านถูกระงับใช้งานชั่วคราวชั่วคราว กรุณาติดต่อผู้พัฒนาโปรแกรม
            </p>
        @elseif($status === 'network_timeout')
            <div class="status-badge status-expired">ออฟไลน์เกินกำหนด (Offline Timeout)</div>
            <h2 class="fw-bold mb-3 text-warning">ไม่สามารถเชื่อมต่อระบบลิขสิทธิ์ได้</h2>
            <p class="text-muted mb-4">
                ระบบไม่ได้รับการอัปเดตสถานะลิขสิทธิ์จากทางออนไลน์เกินระยะเวลาที่กำหนดผ่อนผัน (15 วัน) โปรดตรวจสอบการเชื่อมต่ออินเทอร์เน็ตของเครื่องเซิร์ฟเวอร์
            </p>
        @else
            <div class="status-badge status-expired">ลิขสิทธิ์ไม่ถูกต้อง (Invalid License)</div>
            <h2 class="fw-bold mb-3">ไม่พบสิทธิ์การใช้งานโปรแกรม RiMS</h2>
            <p class="text-muted mb-4">
                โปรแกรมไม่สามารถยืนยันลิขสิทธิ์ร่วมกับรหัสโรงพยาบาลปัจจุบันของท่านได้ หรืออาจยังไม่มีการตั้งคีย์ที่ถูกต้องในระบบ
            </p>
        @endif

        @if(!empty($licenseInfo['message']))
            <div class="alert alert-dark text-start border-0 bg-opacity-25 bg-white small mb-4 p-3 rounded-3" style="color: #94a3b8;">
                <i class="bi bi-info-circle-fill me-2 text-info"></i> รายละเอียดข้อผิดพลาด: {{ $licenseInfo['message'] }}
            </div>
        @endif

        <div class="d-flex justify-content-center gap-3">
            <a href="{{ url('admin/main_setting') }}" class="btn-modern">
                <i class="bi bi-gear-fill"></i> เข้าสู่หน้าตั้งค่าหลัก
            </a>
            <a href="https://huataphanhospital.go.th" target="_blank" class="btn-secondary-modern">
                <i class="bi bi-chat-left-dots"></i> ติดต่อผู้พัฒนา
            </a>
        </div>
    </div>

</body>
</html>
