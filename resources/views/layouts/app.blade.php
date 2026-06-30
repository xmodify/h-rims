<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <script>
        window.addEventListener('error', function(e) {
            var stack = e.error && e.error.stack ? e.error.stack : 'No stack trace';
            alert("JS Error: " + e.message + "\n\nStack Trace:\n" + stack);
        });
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('images/favicon_darkgreen.ico') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('images/favicon_darkgreen.ico') }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RiMS</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- DataTables + Buttons + Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">

    <!-- Datepicker Thai -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">

    <!-- SweetAlert2 CDN -->
    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

    <style>
        :root {
            --nav-green: #0a4d2c;
            --nav-green-light: #126e41;
            --nav-gradient: linear-gradient(135deg, #0a4d2c 0%, #126e41 100%);
            --dash-blue: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --dash-indigo: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            --dash-cyan: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            --dash-amber: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --dash-orange: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            --dash-rose: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
            --dash-pink: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
            --dash-purple: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            --dash-emerald: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --dash-green: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            --dash-red: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --dash-maroon: linear-gradient(135deg, #991b1b 0%, #7f1d1d 100%);
        }

        /* Navbar Modern Styles */
        .navbar-modern {
            background: var(--nav-gradient) !important;
            padding: 0.5rem 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand-modern {
            font-weight: 800;
            letter-spacing: 1px;
            color: #fff !important;
            padding: 5px 15px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none !important;
        }

        .navbar-brand-modern:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
        }

        .nav-link-modern {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-link-modern:hover,
        .nav-link-modern.show {
            background: rgba(255, 255, 255, 0.15);
            color: #fff !important;
        }

        .dropdown-menu-modern {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 4px;
            margin-top: 2px !important;
            background: #ffffff !important;
        }

        .dropdown-item-modern {
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #2d3748 !important;
            transition: all 0.2s;
        }

        .dropdown-item-modern:hover {
            background: #f0f7f4 !important;
            color: var(--nav-green) !important;
            padding-left: 20px;
        }

        .dropdown-menu-modern .dropend:hover>.dropdown-menu {
            display: block;
            top: 0;
            left: 100%;
            margin-top: -4px;
            margin-left: 0px;
        }

        .nav-version-badge {
            font-size: 0.7rem;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
        }

        /* Dash Card Tokens */
        .dash-card {
            background: #ffffff !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            overflow: hidden !important;
            height: 100% !important;
            position: relative !important;
        }

        .dash-card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
        }

        .dash-card .card-body {
            padding: 1.25rem !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .dash-card .card-label {
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            color: #4a5568 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.025em !important;
            margin-bottom: 0.5rem !important;
            display: flex !important;
            align-items: center !important;
        }

        .dash-card .card-metric {
            font-size: 1.85rem !important;
            font-weight: 800 !important;
            color: #1a202c !important;
            margin-bottom: 0.75rem !important;
            line-height: 1.2 !important;
            text-align: center !important;
        }

        .dash-card .card-footer-link {
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            margin-top: auto !important;
            width: 100% !important;
            padding-top: 12px !important;
            border-top: 1px solid rgba(0, 0, 0, 0.05) !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            text-decoration: none !important;
            transition: opacity 0.2s !important;
        }

        .dash-card .card-footer-link:hover {
            opacity: 0.7 !important;
        }

        /* Page Headers */
        .page-header-box {
            background: #ffffff !important;
            border-radius: 12px !important;
            padding: 0.85rem 1.5rem !important;
            /* Slightly more compact */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03) !important;
            border-left: 5px solid var(--nav-green) !important;
            margin-bottom: 1.25rem !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 1rem !important;
        }

        .dashboard-date-info {
            background: #f8fafc;
            padding: 4px 12px;
            border-radius: 8px;
            border: 1px solid #edf2f7;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
        }

        /* Color Accents (Using vibrant Tailwind-like colors) */
        .accent-1 {
            border-top: 4px solid #3b82f6 !important;
        }

        /* Blue */
        .accent-2 {
            border-top: 4px solid #6366f1 !important;
        }

        /* Indigo */
        .accent-3 {
            border-top: 4px solid #06b6d4 !important;
        }

        /* Cyan */
        .accent-4 {
            border-top: 4px solid #f59e0b !important;
        }

        /* Amber/Yellow */
        .accent-5 {
            border-top: 4px solid #f97316 !important;
        }

        /* Orange */
        .accent-6 {
            border-top: 4px solid #f43f5e !important;
        }

        /* Rose */
        .accent-7 {
            border-top: 4px solid #ec4899 !important;
        }

        /* Pink */
        .accent-8 {
            border-top: 4px solid #a855f7 !important;
        }

        /* Purple */
        .accent-9 {
            border-top: 4px solid #10b981 !important;
        }

        /* Emerald */
        .accent-10 {
            border-top: 4px solid #22c55e !important;
        }

        /* Green */
        .accent-11 {
            border-top: 4px solid #ef4444 !important;
        }

        /* Red */
        .accent-12 {
            border-top: 4px solid #991b1b !important;
        }

        .accent-13 {
            border-top: 4px solid #0d9488 !important;
        }

        /* Maroon */

        .icon-color-1,
        .text-color-1 {
            color: #3b82f6 !important;
        }

        .icon-color-2,
        .text-color-2 {
            color: #6366f1 !important;
        }

        .icon-color-3,
        .text-color-3 {
            color: #06b6d4 !important;
        }

        .icon-color-4,
        .text-color-4 {
            color: #f59e0b !important;
        }

        .icon-color-5,
        .text-color-5 {
            color: #f97316 !important;
        }

        .icon-color-6,
        .text-color-6 {
            color: #f43f5e !important;
        }

        .icon-color-7,
        .text-color-7 {
            color: #ec4899 !important;
        }

        .icon-color-8,
        .text-color-8 {
            color: #a855f7 !important;
        }

        .icon-color-9,
        .text-color-9 {
            color: #10b981 !important;
        }

        .icon-color-10,
        .text-color-10 {
            color: #22c55e !important;
        }

        .icon-color-11,
        .text-color-11 {
            color: #ef4444 !important;
        }

        .icon-color-12,
        .text-color-12 {
            color: #991b1b !important;
        }

        .icon-color-13,
        .text-color-13 {
            color: #0d9488 !important;
        }

        .icon-bg-1 {
            background: var(--dash-blue) !important;
            color: #fff !important;
        }

        .icon-bg-2 {
            background: var(--dash-indigo) !important;
            color: #fff !important;
        }

        .icon-bg-3 {
            background: var(--dash-cyan) !important;
            color: #fff !important;
        }

        .icon-bg-4 {
            background: var(--dash-amber) !important;
            color: #fff !important;
        }

        .icon-bg-5 {
            background: var(--dash-orange) !important;
            color: #fff !important;
        }

        .icon-bg-6 {
            background: var(--dash-rose) !important;
            color: #fff !important;
        }

        .icon-bg-7 {
            background: var(--dash-pink) !important;
            color: #fff !important;
        }

        .icon-bg-8 {
            background: var(--dash-purple) !important;
            color: #fff !important;
        }

        .icon-bg-9 {
            background: var(--dash-emerald) !important;
            color: #fff !important;
        }

        .icon-bg-10 {
            background: var(--dash-green) !important;
            color: #fff !important;
        }

        .icon-bg-11 {
            background: var(--dash-red) !important;
            color: #fff !important;
        }

        .icon-bg-12 {
            background: var(--dash-maroon) !important;
            color: #fff !important;
        }

        .icon-bg-13 {
            background: #0d9488 !important;
            color: #fff !important;
        }

        .icon-box {
            width: 42px !important;
            height: 42px !important;
            border-radius: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.25rem !important;
            margin-bottom: 12px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            flex-shrink: 0 !important;
        }

        /* Modern Tables - New Compact & Striped Style (Subtle Borders) */
        /* Global Table Styles - Compact & Professional Style */
        .table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin-bottom: 1.5rem !important;
            width: 100% !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            overflow: hidden !important;
        }

        /* Zebra Striping */
        .table tbody tr:nth-of-type(even) td {
            background-color: #f8fafc !important;
        }

        .table tbody tr:nth-of-type(odd) td {
            background-color: #ffffff !important;
        }

        /* Table Cells - Precise & Clean */
        .table th,
        .table td {
            border-bottom: 1px solid #e2e8f0 !important;
            border-right: 1px solid #e2e8f0 !important;
            padding: 8px 10px !important;
            /* Reduced padding to prevent overlap */
            vertical-align: middle !important;
            font-size: 0.875rem !important;
            /* Increased for better readability */
            line-height: 1.4 !important;
        }

        /* Remove last border right */
        .table th:last-child,
        .table td:last-child {
            border-right: none !important;
        }

        /* Table Headers - Structured Blue Theme */
        .table thead th {
            background-color: #dbeafe !important;
            /* Pastel Blue */
            color: #1e3a8a !important;
            /* Dark Navy */
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 0.8rem !important;
            letter-spacing: 0.025em !important;
            border-bottom: 2px solid #bfdbfe !important;
            border-right: 1px solid #bfdbfe !important;
            /* Explicit Vertical Border for Headers */
            text-align: center !important;
        }

        /* Ensure vertical lines for all cells including headers */
        .table th,
        .table td {
            border-right: 1px solid #e2e8f0 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 8px 10px !important;
            vertical-align: middle !important;
            font-size: 0.875rem !important;
            line-height: 1.4 !important;
        }

        /* Border colors for headers to be slightly darker than the background */
        .table thead th {
            border-right-color: #bfdbfe !important;
            border-left-color: #bfdbfe !important;
        }

        /* Last child should not have border right */
        .table th:last-child,
        .table td:last-child {
            border-right: none !important;
        }

        /* Hover Effect */
        .table tbody tr:hover td {
            background-color: #f1f5f9 !important;
            transition: background-color 0.2s ease;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar-collapse {
                background: var(--nav-green);
                border-radius: 12px;
                padding: 15px;
                margin-top: 10px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }
        }
    </style>
</head>

<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark navbar-modern sticky-top">
            <div class="container-fluid px-lg-4">
                <a class="navbar-brand-modern" href="{{ url('/') }}">
                    <i class="bi bi-coin" style="color: #ffd700; font-size: 1.7rem;"></i>
                    <div class="d-flex flex-column leading-none">
                        <span class="fs-5 lh-1">RiMS</span>
                        @if($hospital_name)
                            <span class="d-none d-sm-block text-truncate"
                                style="font-size: 0.65rem; color: rgba(255,255,255,0.8); font-weight: 500; letter-spacing: 0;">{{ $hospital_name }}</span>
                        @endif
                    </div>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @guest
                        @else
                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_import == 'Y')
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#"
                                        role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        v-pre>
                                        <i class="bi bi-cloud-arrow-down-fill me-1" style="color: #38bdf8;"></i> นำเข้าข้อมูล
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ route('import.statement') }}">
                                                <i class="bi bi-file-earmark-arrow-up-fill me-1 text-primary"></i> Statement OP-IP
                                            </a>
                                        </li>
                                        @if ($hasLookupIcode_kidney)
                                            <li>
                                                <a class="dropdown-item dropdown-item-modern" href="{{ route('import.statement_kidney') }}">
                                                    <i class="bi bi-droplet-fill me-1 text-danger"></i> Statement ฟอกไต
                                                </a>
                                            </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ route('import.dmis') }}">
                                                <i class="bi bi-puzzle-fill me-1 text-warning"></i> Seamless For DMIS
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_check == 'Y')
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#"
                                        role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        v-pre>
                                        <i class="bi bi-check-circle-fill me-1" style="color: #facc15;"></i> ตรวจสอบข้อมูล
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end">
                                        <!-- เมนูอื่น -->
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ url('check/nhso_endpoint') }}">
                                                <i class="bi bi-person-x-fill text-danger me-2"></i> ปิดสิทธิ สปสช.
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ url('check/fdh_claim_status') }}">
                                                <i class="bi bi-cloud-check-fill text-primary me-2"></i> FDH-Claim Status
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ url('check/eclaim_status') }}">
                                                <i class="bi bi-file-earmark-check-fill text-success me-2"></i> E-Claim Status
                                            </a>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-capsule-pill text-info me-2"></i> Drug Catalog
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/drugcat_nhso') }}"><i class="bi bi-chevron-right text-muted me-1"></i> สปสช.</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/drugcat_chi') }}"><i class="bi bi-chevron-right text-muted me-1"></i> สกส.</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/drugcat_fdh') }}"><i class="bi bi-chevron-right text-muted me-1"></i> FDH</a></li>
                                            </ul>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-clipboard-pulse text-warning me-2"></i> Lab Catalog
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/labcat_nhso') }}"><i class="bi bi-chevron-right text-muted me-1"></i> สปสช.</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/labcat_chi') }}"><i class="bi bi-chevron-right text-muted me-1"></i> สกส.</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/labcat_fdh') }}"><i class="bi bi-chevron-right text-muted me-1"></i> FDH</a></li>
                                            </ul>
                                        </li>

                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-card-checklist text-primary me-2"></i> ข้อมูลพื้นฐาน
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/nondrugitems') }}"><i class="bi bi-chevron-right text-muted me-1"></i> ค่ารักษาพยาบาล</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/pttype') }}"><i class="bi bi-chevron-right text-muted me-1"></i> สิทธิการักษา HOSxP</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/nhso_subinscl') }}"><i class="bi bi-chevron-right text-muted me-1"></i> สิทธิการรักษา สปสช.</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('check/sss_equipdev_aipn') }}"><i class="bi bi-chevron-right text-muted me-1"></i> Equipdev AIPN</a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_emr == 'Y')
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#"
                                        role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        v-pre>
                                        <i class="bi bi-file-earmark-medical-fill me-1" style="color: #60a5fa;"></i> งานเวชระเบียน
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end">
                                        <!-- เมนูอื่น -->
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('opd/oppp_visit') }}">
                                                <i class="bi bi-people-fill text-primary me-2"></i> OP-จำนวนผู้มารับบริการ
                                            </a>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-activity text-danger me-2"></i> OP-รายโรคสำคัญ
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('opd/diag_sepsis') }}"><i class="bi bi-chevron-right text-muted me-1"></i> Sepsis</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('opd/diag_stroke') }}"><i class="bi bi-chevron-right text-muted me-1"></i> Stroke</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('opd/diag_stemi') }}"><i class="bi bi-chevron-right text-muted me-1"></i> Stemi</a></li>
                                                <li><a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('opd/diag_pneumonia') }}"><i class="bi bi-chevron-right text-muted me-1"></i> Pneumonia</a></li>
                                            </ul>
                                        </li>
                                        <!-- เมนูอื่น -->
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('/ipd/dchsummary') }}">
                                                <i class="bi bi-file-earmark-medical-fill text-success me-2"></i> IP-D/C Summary
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_claim_op == 'Y')
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#"
                                        role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        v-pre>
                                        <i class="bi bi-wallet-fill me-1" style="color: #4ade80;"></i> เรียกเก็บ OP
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end">
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-heart-fill text-danger me-2"></i> OP-UCS ประกันสุขภาพ
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/ucs_incup') }}"><i class="bi bi-chevron-right text-muted me-1"></i> UC-OP ใน CUP </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/ucs_inprovince') }}"><i class="bi bi-chevron-right text-muted me-1"></i> UC-OP ในจังหวัด </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/ucs_inprovince_va') }}"><i class="bi bi-chevron-right text-muted me-1"></i> UC-OP ในจังหวัด VA</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/ucs_outprovince') }}"><i class="bi bi-chevron-right text-muted me-1"></i> UC-OP ต่างจังหวัด </a>
                                                </li>
                                                @if ($hasLookupIcode_kidney)
                                                    <li>
                                                        <a class="dropdown-item dropdown-item-modern"
                                                            href="{{ url('claim_op/ucs_kidney') }}"><i class="bi bi-chevron-right text-muted me-1"></i> UC-OP ฟอกไต </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> OP-STP บุคคลที่มีปัญหาสถานะและสิทธิ
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/stp_incup') }}"><i class="bi bi-chevron-right text-muted me-1"></i> STP-OP ใน CUP </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/stp_outcup') }}"><i class="bi bi-chevron-right text-muted me-1"></i> STP-OP นอก CUP </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-building text-primary me-2"></i> OP-OFC กรมบัญชีกลาง
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/ofc') }}"><i class="bi bi-chevron-right text-muted me-1"></i> OFC-OP กรมบัญชีกลาง</a>
                                                </li>
                                                @if ($hasLookupIcode_kidney)
                                                    <li>
                                                        <a class="dropdown-item dropdown-item-modern"
                                                            href="{{ url('claim_op/ofc_kidney') }}"><i class="bi bi-chevron-right text-muted me-1"></i> OFC-OP กรมบัญชีกลาง ฟอกไต
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-bank text-info me-2"></i> OP-LGO อปท.
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/lgo') }}"><i class="bi bi-chevron-right text-muted me-1"></i> LGO-OP อปท.</a>
                                                </li>
                                                @if ($hasLookupIcode_kidney)
                                                    <li>
                                                        <a class="dropdown-item dropdown-item-modern"
                                                            href="{{ url('claim_op/lgo_kidney') }}"><i class="bi bi-chevron-right text-muted me-1"></i> LGO-OP อปท. ฟอกไต </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                        <!-- เมนูอื่น -->
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-flower1 text-success me-2"></i> OP-BKK ข้าราชการ กทม.
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/bkk') }}"><i class="bi bi-chevron-right text-muted me-1"></i> BKK-OP ข้าราชการ กทม.</a>
                                                </li>
                                                @if ($hasLookupIcode_kidney)
                                                    <li>
                                                        <a class="dropdown-item dropdown-item-modern"
                                                            href="{{ url('claim_op/bkk_kidney') }}"><i class="bi bi-chevron-right text-muted me-1"></i> BKK-OP ข้าราชการ กทม. ฟอกไต </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-car-front-fill text-secondary me-2"></i> OP-BMT องค์การขนส่งมวลชนกรุงเทพ
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/bmt') }}"><i class="bi bi-chevron-right text-muted me-1"></i> BMT-OP ขสมก.</a>
                                                </li>
                                                @if ($hasLookupIcode_kidney)
                                                    <li>
                                                        <a class="dropdown-item dropdown-item-modern"
                                                            href="{{ url('claim_op/bmt_kidney') }}"><i class="bi bi-chevron-right text-muted me-1"></i> BMT-OP ขสมก. ฟอกไต </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                        <!-- เมนูอื่น -->
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_op/srt') }}">
                                                <i class="bi bi-train-front text-primary me-2"></i> OP-SRT การรถไฟแห่งประเทศไทย
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_op/pvt') }}">
                                                <i class="bi bi-file-earmark-person-fill text-primary me-2"></i> OP-PVT ครูเอกชน
                                            </a>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-shield-check text-success me-2"></i> OP-SSS ประกันสังคม
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/sss_main') }}"><i class="bi bi-chevron-right text-muted me-1"></i> SS-OP ประกันสังคม เครือข่าย</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/sss_ppfs') }}"><i class="bi bi-chevron-right text-muted me-1"></i> SS-OP ประกันสังคม PPFS</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/sss_fund') }}"><i class="bi bi-chevron-right text-muted me-1"></i> SS-OP ประกันสังคม กองทุนทดแทน</a>
                                                </li>
                                                @if ($hasLookupIcode_kidney)
                                                    <li>
                                                        <a class="dropdown-item dropdown-item-modern"
                                                            href="{{ url('claim_op/sss_kidney') }}"><i class="bi bi-chevron-right text-muted me-1"></i> SS-OP ประกันสังคม ฟอกไต</a>
                                                    </li>
                                                @endif
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_op/sss_hc') }}"><i class="bi bi-chevron-right text-muted me-1"></i> SS-OP ประกันสังคม ค่าใช้จ่ายสูง</a>
                                                </li>
                                            </ul>
                                        </li>
                                        <!-- เมนูอื่น -->
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_op/rcpt') }}">
                                                <i class="bi bi-cash-coin text-success me-2"></i> OP-ชำระเงิน
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_op/act') }}">
                                                <i class="bi bi-shield-shaded text-danger me-2"></i> OP-พรบ.
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_claim_ip == 'Y')
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#"
                                        role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        v-pre>
                                        <i class="bi bi-hospital-fill me-1" style="color: #f87171;"></i> เรียกเก็บ IP
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end">
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-heart-fill text-danger me-2"></i> IP-UCS ประกันสุขภาพ
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_ip/ucs_incup') }}"><i class="bi bi-chevron-right text-muted me-1"></i> UC-IP ใน CUP </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_ip/ucs_outcup') }}"><i class="bi bi-chevron-right text-muted me-1"></i> UC-IP นอก CUP </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <!-- เมนูอื่น -->
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/stp') }}">
                                                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> IP-STP บุคคลที่มีปัญหาสถานะและสิทธิ
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/ofc') }}">
                                                <i class="bi bi-building text-primary me-2"></i> IP-OFC กรมบัญชีกลาง
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/lgo') }}">
                                                <i class="bi bi-bank text-info me-2"></i> IP-LGO อปท.
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/bkk') }}">
                                                <i class="bi bi-flower1 text-success me-2"></i> IP-BKK ข้าราชการ กทม.
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/bmt') }}">
                                                <i class="bi bi-car-front-fill text-secondary me-2"></i> IP-BMT องค์การขนส่งมวลชนกรุงเทพ
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/srt') }}">
                                                <i class="bi bi-train-front text-primary me-2"></i> IP-SRT การรถไฟแห่งประเทศไทย
                                            </a>
                                        </li>
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-shield-check text-success me-2"></i> IP-SSS ประกันสังคม
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_ip/sss') }}"><i class="bi bi-chevron-right text-muted me-1"></i> SS-IP ประกันสังคม ทั่วไป </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('claim_ip/sss_hc') }}"><i class="bi bi-chevron-right text-muted me-1"></i> SS-IP ประกันสังคม ค่าใช้จ่ายสูง
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <!-- เมนูอื่น -->
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/gof') }}">
                                                <i class="bi bi-mortarboard text-secondary me-2"></i> IP-GOF หน่วยงานรัฐ
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/rcpt') }}">
                                                <i class="bi bi-cash-coin text-success me-2"></i> IP-ชำระเงิน
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/act') }}">
                                                <i class="bi bi-shield-shaded text-danger me-2"></i> IP-พรบ.
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_mishos == 'Y')
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#"
                                        role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        v-pre>
                                        <i class="bi bi-graph-up-arrow me-1" style="color: #f472b6;"></i> MIS Hospital
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end">
                                        <!-- ชี้ขวา -->
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-people-fill text-primary me-2"></i> บริการผู้ป่วยนอก
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ae') }}"><i class="bi bi-chevron-right text-muted me-1"></i> ผู้ป่วยนอกฉุกเฉิน</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_walkin') }}"><i class="bi bi-chevron-right text-muted me-1"></i> OP WALKIN</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_herb') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการแพทย์แผนไทย ยาสมุนไพร</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_telemed') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการสาธารณสุขทางไกล (TELEMED)</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_rider') }}"><i class="bi bi-chevron-right text-muted me-1"></i> จัดส่งยาทางไปรษณีย์</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_gdm') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการในกลุ่ม GDM</a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-cash text-danger me-2"></i> บริการค่าใช้จ่ายสูง
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_drug_clopidogrel') }}"><i class="bi bi-chevron-right text-muted me-1"></i> ยาต้านเกล็ดเลือด</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_drug_sk') }}"><i class="bi bi-chevron-right text-muted me-1"></i> ยาละลายลิ่มเลือด</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ins') }}"><i class="bi bi-chevron-right text-muted me-1"></i> อวัยวะเทียม/อุปกรณ์</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_palliative') }}"><i class="bi bi-chevron-right text-muted me-1"></i> Palliative Care</a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="dropend position-relative">
                                            <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-shield-plus text-success me-2"></i> การส่งเสริมป้องกันโรค
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-modern">
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_fp') }}"><i class="bi bi-chevron-right text-muted me-1"></i> การบริการวางแผนครอบครัว</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_prt') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการทดสอบการตั้งครรภ์ (PRT)</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_ida') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการคัดกรองโลหิตจางจากการขาดธาตุเหล็ก</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_ferrofolic') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการยาเม็ดเสริมธาตุเหล็ก</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_fluoride') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการเคลือบฟลูออไรด์ (กลุ่มเสี่ยง)</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_anc') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการฝากครรภ์ (ANC)</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_postnatal') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการตรวจหลังคลอด</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_fittest') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการตรวจคัดกรองมะเร็งลำไส้ใหญ่และสำไส้ตรง (Fit test)</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item dropdown-item-modern"
                                                        href="{{ url('mishos/ucs_ppfs_scr') }}"><i class="bi bi-chevron-right text-muted me-1"></i> บริการคัดกรองและประเมินปัจจัยเสี่ยงต่อสุขภาพกาย/สุขภาพจิต (SCR)</a>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            @endif

                            @if(Auth::user()->status == 'admin' || Auth::user()->allow_debtor == 'Y')
                                <li class="nav-item">
                                    <a class="nav-link nav-link-modern" href="{{ url('debtor') }}">
                                        <i class="bi bi-person-lines-fill me-1" style="color: #fb923c;"></i> ลูกหนี้ค่ารักษา
                                    </a>
                                </li>
                            @endif
                        @endguest
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item d-flex align-items-center me-2">
                            <div class="nav-version-badge">
                                V.69-06-30 18:00
                            </div>
                        </li>
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                    v-pre>
                                    <i class="bi bi-person-circle me-1" style="color: #c084fc;"></i> {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-modern"
                                    aria-labelledby="navbarDropdown">
                                    <!-- Admin -->
                                    @auth
                                        @if (auth()->user()->status === 'admin')
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ route('admin.main_setting') }}">
                                                <i class="bi bi-gear-fill me-2 text-secondary"></i> Main Setting
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ route('admin.users.index') }}">
                                                <i class="bi bi-people-fill me-2 text-primary"></i> Manage User
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ route('admin.lookup_icode.index') }}">
                                                <i class="bi bi-search me-2 text-success"></i> Lookup icode
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ route('admin.lookup_ward.index') }}">
                                                <i class="bi bi-hospital-fill me-2 text-warning"></i> Lookup ward
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ route('admin.lookup_hospcode.index') }}">
                                                <i class="bi bi-building me-2 text-info"></i> Lookup hospcode
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ route('admin.budget_year.index') }}">
                                                <i class="bi bi-calendar3 me-2 text-danger"></i> Budget year
                                            </a>
                                            <a class="dropdown-item dropdown-item-modern"
                                                href="{{ route('admin.logs.schedule') }}">
                                                <i class="bi bi-clock-history me-2 text-success"></i> Log Schedule
                                            </a>
                                        @endif
                                        @if(auth()->user()->status === 'admin' || auth()->user()->allow_aopod_death === 'Y')
                                            @if(\Illuminate\Support\Facades\Schema::hasTable('lookup_hospcode') && \Illuminate\Support\Facades\DB::table('lookup_hospcode')->where('hospcode', '00025')->exists())
                                                <a class="dropdown-item dropdown-item-modern"
                                                    href="{{ route('admin.aopod') }}">
                                                    <i class="bi bi-send-fill me-2 text-success"></i> ข้อมูล AOPOD
                                                </a>
                                            @endif
                                        @endif
                                    @endauth
                                    <!-- -->
                                    <div class="dropdown-divider opacity-10"></div>
                                    <a class="dropdown-item dropdown-item-modern" href="#" data-bs-toggle="modal"
                                        data-bs-target="#changePasswordModal">
                                        <i class="bi bi-shield-lock me-2 text-primary"></i> Change Password
                                    </a>
                                    <a class="dropdown-item dropdown-item-modern text-danger" href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                                                                 document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-2 text-danger"></i> {{ __('Logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>

                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @if (request()->routeIs('stm_*') || request()->is('import/stm_*') || request()->is('import/stm_*/*'))
                <div class="container-fluid px-lg-4 mb-3">
                    @php
                        $is_kidney = request()->is('*kidney*') || request()->is('*csop*') || request()->routeIs('*kidney*') || request()->routeIs('*csop*');
                    @endphp
                    @if ($is_kidney)
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <a href="{{ route('import.statement_kidney') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                                <i class="bi bi-arrow-left me-1"></i> ย้อนกลับไปยัง Statement ฟอกไต Portal
                            </a>
                            <a href="{{ route('import.statement') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                                <i class="bi bi-arrow-left me-1"></i> ย้อนกลับไปยัง Statement Portal
                            </a>
                        </div>
                    @else
                        <a href="{{ route('import.statement') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                            <i class="bi bi-arrow-left me-1"></i> ย้อนกลับไปยัง Statement Portal
                        </a>
                    @endif
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('assets/vendor/jquery/jquery-3.7.0.min.js') }}"></script>

    <!-- DataTables core -->
    <script src="{{ asset('assets/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>

    <!-- Buttons + Export -->
    <script src="{{ asset('assets/vendor/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/buttons.html5.min.js') }}"></script>

    <!-- JSZip (required for Excel export) -->
    <script src="{{ asset('assets/vendor/jszip/jszip.min.js') }}"></script>

    <!-- Datepicker Thai -->
    <script src="{{ asset('assets/vendor/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap-datepicker/bootstrap-datepicker-thai.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap-datepicker/bootstrap-datepicker.th.min.js') }}"></script>

    <!-- Stack for per-page script -->
    <script>
    async function runFdhBulkCheck(items, csrfToken, checkChunkUrl, reloadCallback) {
        if (!items || items.length === 0) {
            Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
            return;
        }

        const confirm = await Swal.fire({
            title: 'ยืนยันการดึง FDH?',
            html: `ดึงสถานะ FDH สำหรับผู้ป่วยในหน้านี้จำนวน <b>${items.length}</b> รายการ`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ดึงข้อมูล',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#0dcaf0'
        });
        if (!confirm.isConfirmed) return;

        const chunkSize = 20;
        const totalItems = items.length;
        let totalUpdated = 0;
        let totalNotFound = 0;
        let totalErrors = 0;
        let overallSuccess = true;
 
        Swal.fire({
            title: 'กำลังดึงสถานะ FDH...',
            html: `
                <div id="fdh-progress-text" class="mb-2">กำลังเตรียมข้อมูล...</div>
                <div class="progress mb-2" style="height: 22px;">
                    <div id="fdh-progress-bar"
                         class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                         role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div id="fdh-progress-sub" class="text-muted small mb-2"></div>
                <div class="border rounded p-1 bg-light text-start" style="max-height: 150px; overflow-y: auto; font-size: 0.7rem; font-family: monospace; line-height: 1.3;" id="fdh-progress-logs">
                    <div class="text-muted">กำลังเตรียมข้อมูล...</div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false
        });
 
        const logsBox = document.getElementById('fdh-progress-logs');
 
        for (let i = 0; i < totalItems; i += chunkSize) {
            const chunk = items.slice(i, i + chunkSize);
            const percent = Math.round((i / totalItems) * 100);
 
            const pText = document.getElementById('fdh-progress-text');
            const pBar  = document.getElementById('fdh-progress-bar');
            const pSub  = document.getElementById('fdh-progress-sub');
            if (pText) pText.innerHTML = `กำลังดึงข้อมูล <b>${i + chunk.length}/${totalItems}</b> รายการ`;
            if (pBar)  { pBar.style.width = `${percent}%`; pBar.innerHTML = `${percent}%`; pBar.setAttribute('aria-valuenow', percent); }
            if (pSub)  pSub.textContent = totalErrors > 0 ? `⚠️ พบข้อผิดพลาด ${totalErrors} รายการ` : '';
 
            try {
                const res = await fetch(checkChunkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ items: chunk })
                });
                const data = await res.json();
                if (data.success) {
                    if (data.details && data.details.length > 0) {
                        data.details.forEach(detail => {
                            if (detail.status == 200) {
                                totalUpdated++;
                            } else if (detail.status == 404) {
                                totalNotFound++;
                            } else {
                                totalErrors++;
                                overallSuccess = false;
                            }
                            
                            const ident = detail.an ? `AN: ${detail.an}` : `SEQ: ${detail.seq}`;
                            let logClass = "text-success";
                            let prefix = "✔";
                            if (detail.status != 200) {
                                logClass = detail.status == 404 ? "text-warning" : "text-danger";
                                prefix = detail.status == 404 ? "⚠" : `❌[${detail.status}]`;
                            }
                            const msg = detail.status_message_th || detail.error || '';
                            if (logsBox) {
                                logsBox.innerHTML += `<div class="${logClass}">${prefix} HN: ${detail.hn} (${ident}) - ${msg}</div>`;
                            }
                        });
                        if (logsBox) logsBox.scrollTop = logsBox.scrollHeight;
                    }
                } else {
                    overallSuccess = false;
                    totalErrors += chunk.length;
                    if (logsBox) {
                        logsBox.innerHTML += `<div class="text-danger">❌ เกิดข้อผิดพลาดในการดึงข้อมูลทั้งกลุ่ม (Chunk)</div>`;
                        logsBox.scrollTop = logsBox.scrollHeight;
                    }
                }
            } catch (err) {
                overallSuccess = false;
                totalErrors += chunk.length;
                if (logsBox) {
                    logsBox.innerHTML += `<div class="text-danger">❌ การเชื่อมต่อล้มเหลว: ${err.message}</div>`;
                    logsBox.scrollTop = logsBox.scrollHeight;
                }
            }
            
            // หน่วงเวลาฝั่ง Client 300ms ระหว่างเรียก Chunk ถัดไป เพื่อลดภาระของ Server FDH
            await new Promise(resolve => setTimeout(resolve, 300));
        }
 
        const pBarFinal = document.getElementById('fdh-progress-bar');
        if (pBarFinal) { pBarFinal.style.width = '100%'; pBarFinal.innerHTML = '100%'; pBarFinal.setAttribute('aria-valuenow', 100); }
 
        const summaryHtml = `
            <div class="text-start p-2">
                <b>สถานะ:</b> ${overallSuccess ? '✅ สำเร็จทั้งหมด' : '⚠️ เสร็จสิ้น แต่มีข้อผิดพลาดบางส่วน'}<br>
                <b>รายการทั้งหมด:</b> ${totalItems} รายการ<br>
                <hr class="my-2">
                <b>ดึงข้อมูลสำเร็จ:</b> <span class="badge bg-success text-white">${totalUpdated}</span> รายการ<br>
                <b>ไม่พบข้อมูล:</b> <span class="badge bg-warning text-dark">${totalNotFound}</span> รายการ<br>
                <b>เกิดข้อผิดพลาด:</b> <span class="badge bg-danger text-white">${totalErrors}</span> รายการ
            </div>
        `;
 
        await Swal.fire({
            icon: overallSuccess ? 'success' : 'warning',
            title: 'ดึงสถานะ FDH เสร็จสิ้น',
            html: summaryHtml,
            confirmButtonText: 'โหลดข้อมูล',
            confirmButtonColor: '#0dcaf0'
        });

        if (reloadCallback) {
            reloadCallback();
        } else {
            location.reload();
        }
    }
    </script>
    @stack('scripts')

    @auth
        <!-- Change Password Modal -->
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('profile.password.update') }}" class="modal-content border-0 shadow-lg"
                    id="changePasswordForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-primary text-white py-3 border-0">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-shield-lock me-2"></i> เปลี่ยนรหัสผ่าน
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-bold">รหัสผ่านปัจจุบัน</label>
                            <input type="password"
                                class="form-control bg-light @error('current_password') is-invalid @enderror"
                                name="current_password" required placeholder="กรอกรหัสผ่านเดิม">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <hr class="my-4 opacity-10">
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-bold">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control bg-light @error('new_password') is-invalid @enderror"
                                name="new_password" required placeholder="อย่างน้อย 8 ตัวอักษร">
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-1">
                            <label for="new_password_confirmation" class="form-label fw-bold">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" class="form-control bg-light" name="new_password_confirmation" required
                                placeholder="กรอกรหัสผ่านใหม่ให้ตรงกัน">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary px-4 rounded-pill"
                            data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">บันทึกรหัสผ่านใหม่</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            $(document).ready(function () {
                // Re-open modal if there are errors
                @if ($errors->has('current_password') || $errors->has('new_password'))
                    var myModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
                    myModal.show();
                @endif

                @if (session('success') && !session('migrate_output'))
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: '{{ session('success') }}',
                        timer: 2000,
                        showConfirmButton: false,
                        borderRadius: '15px'
                    });
                @endif
                                        });
        </script>
    @endauth

    <script>
        // Override showLoadingAlert and simulateProcess for sequential AJAX multi-upload
        $(document).ready(function() {


            window.showLoadingAlert = function() {
                Swal.fire({
                    title: 'กำลังนำเข้าข้อมูล...',
                    text: 'กรุณารอสักครู่',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
            };

            window.simulateProcess = function(event) {
                event.preventDefault();
                const form = event.target || document.getElementById('importForm');
                const fileInput = form.querySelector('input[type="file"]');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    Swal.fire({
                        title: 'แจ้งเตือน',
                        text: 'กรุณาเลือกไฟล์ก่อนนำเข้า',
                        icon: 'warning',
                        confirmButtonText: 'ปิด',
                        confirmButtonColor: '#673ab7',
                        customClass: {
                            confirmButton: 'btn btn-primary btn-sm px-4'
                        }
                    });
                    return;
                }

                const files = Array.from(fileInput.files);
                const totalFiles = files.length;
                let currentIndex = 0;
                let successCount = 0;
                let succeededFiles = [];
                let failedFiles = [];
                let currentXhr = null;
                let isCancelled = false;

                // Show progress bar container via SweetAlert2
                Swal.fire({
                    title: 'กำลังนำเข้าข้อมูล...',
                    html: `
                        <div id="swal-upload-container" class="text-start mt-2">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span id="swal-file-name" class="text-truncate fw-bold text-dark d-inline-block" style="max-width: 70%">กำลังเตรียมไฟล์...</span>
                                <span id="swal-file-index" class="text-muted fw-bold">0 / ${totalFiles} ไฟล์</span>
                            </div>
                            <div class="progress mb-2" style="height: 22px; border-radius: 10px; overflow: hidden; background-color: #f1f3f5;">
                                <div id="swal-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" role="progressbar" style="width: 0%">0%</div>
                            </div>
                            <div id="swal-status-text" class="text-muted small">ระบบกำลังเริ่มต้นการประมวลผล...</div>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    showCancelButton: false,
                    didOpen: () => {
                        uploadNextFile();
                    }
                });

                function uploadNextFile() {
                    if (isCancelled) return;

                    if (currentIndex >= totalFiles) {
                        let resultHtml = `<div class="text-start small" style="max-height: 250px; overflow-y: auto;">`;
                        if (successCount > 0) {
                            resultHtml += `<p class="text-success fw-bold mb-1"><i class="bi bi-check-circle-fill me-1"></i> นำเข้าสำเร็จทั้งหมด ${successCount} ไฟล์:</p>`;
                            resultHtml += `<ul class="ps-3 mb-2 text-muted">`;
                            succeededFiles.forEach(f => {
                                resultHtml += `<li>${f}</li>`;
                            });
                            resultHtml += `</ul>`;
                        }
                        if (failedFiles.length > 0) {
                            resultHtml += `<p class="text-danger fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> ไฟล์ที่ล้มเหลว (${failedFiles.length} ไฟล์):</p>`;
                            resultHtml += `<ul class="ps-3 mb-0 text-muted">`;
                            failedFiles.forEach(f => {
                                resultHtml += `<li>${f}</li>`;
                            });
                            resultHtml += `</ul>`;
                        }
                        resultHtml += `</div>`;

                        Swal.fire({
                            title: 'เสร็จสิ้นการนำเข้า',
                            html: resultHtml,
                            icon: failedFiles.length > 0 ? 'warning' : 'success',
                            confirmButtonText: 'ตกลง',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.reload();
                        });
                        return;
                    }

                    const currentFile = files[currentIndex];
                    const nameEl = document.getElementById('swal-file-name');
                    const indexEl = document.getElementById('swal-file-index');
                    const barEl = document.getElementById('swal-progress-bar');
                    const statusEl = document.getElementById('swal-status-text');

                    if (nameEl) nameEl.innerText = currentFile.name;
                    if (indexEl) indexEl.innerText = `${currentIndex + 1} / ${totalFiles} ไฟล์`;
                    if (barEl) {
                        barEl.style.width = '0%';
                        barEl.innerText = '0%';
                        barEl.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold';
                    }
                    if (statusEl) statusEl.innerText = 'กำลังอัปโหลด...';

                    const formData = new FormData();
                    Array.from(form.elements).forEach(el => {
                        if (el.name && el.name !== 'files[]' && el.type !== 'file') {
                            formData.append(el.name, el.value);
                        }
                    });
                    formData.append('files[]', currentFile);

                    const xhr = new XMLHttpRequest();
                    currentXhr = xhr;

                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            if (barEl) {
                                barEl.style.width = percent + '%';
                                barEl.innerText = percent + '%';
                            }
                            if (statusEl) {
                                if (percent === 100) {
                                    statusEl.innerText = 'อัปโหลดเสร็จสิ้น กำลังประมวลผลบนเซิร์ฟเวอร์ (กรุณารอสักครู่)...';
                                    barEl.className = 'progress-bar progress-bar-striped progress-bar-animated bg-info fw-bold';
                                } else {
                                    statusEl.innerText = `กำลังอัปโหลด... (${percent}%)`;
                                }
                            }
                        }
                    });

                    xhr.addEventListener('readystatechange', function() {
                        if (xhr.readyState === 4) {
                            currentXhr = null;
                            if (xhr.status >= 200 && xhr.status < 300) {
                                const responseText = xhr.responseText;
                                
                                // Parse HTML to check for session success elements robustly
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(responseText, 'text/html');
                                const hasSuccessAlert = doc.querySelector('.alert-success') !== null;
                                const hasSuccessScript = responseText.includes('นำเข้าสำเร็จ!');
                                const isSuccess = hasSuccessAlert || hasSuccessScript;

                                if (!isSuccess) {
                                    // Try to extract exact error message from Swal.fire script or session alert
                                    let errorMsg = 'เซิร์ฟเวอร์ประมวลผลล้มเหลว หรือเกิดข้อผิดพลาดในไฟล์';
                                    const textMatch = responseText.match(/icon:\s*['"]error['"],[\s\S]*?text:\s*(['"])(.*?)\1/);
                                    if (textMatch && textMatch[2]) {
                                        errorMsg = textMatch[2];
                                    } else {
                                        const dangerAlert = doc.querySelector('.alert-danger');
                                        if (dangerAlert) {
                                            errorMsg = dangerAlert.textContent.trim();
                                        }
                                    }
                                    handleFailure(currentFile.name, errorMsg);
                                } else {
                                    successCount++;
                                    succeededFiles.push(currentFile.name);
                                    currentIndex++;
                                    uploadNextFile();
                                }
                            } else {
                                handleFailure(currentFile.name, `HTTP Error ${xhr.status}`);
                            }
                        }
                    });

                    xhr.open('POST', form.action, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.send(formData);
                }

                function handleFailure(fileName, errorMsg) {
                    failedFiles.push(fileName);
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด',
                        html: `ไม่สามารถนำเข้าไฟล์ <strong>${fileName}</strong> ได้<br><span class="text-danger small">${errorMsg}</span>`,
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#dc3545',
                        confirmButtonText: 'ข้ามไฟล์นี้ไป',
                        cancelButtonText: 'ยกเลิกที่เหลือทั้งหมด',
                        allowOutsideClick: false,
                        customClass: {
                            confirmButton: 'btn btn-primary btn-sm px-3 me-2',
                            cancelButton: 'btn btn-danger btn-sm px-3'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            currentIndex++;
                            reopenProgressSwal();
                        } else {
                            isCancelled = true;
                            Swal.fire({
                                title: 'ยกเลิกการนำเข้า',
                                text: 'หยุดการนำเข้าไฟล์ที่เหลืออยู่เรียบร้อยแล้ว',
                                icon: 'info',
                                confirmButtonText: 'ตกลง'
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    });
                }

                function reopenProgressSwal() {
                    Swal.fire({
                        title: 'กำลังนำเข้าข้อมูล...',
                        html: `
                            <div id="swal-upload-container" class="text-start mt-2">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span id="swal-file-name" class="text-truncate fw-bold text-dark d-inline-block" style="max-width: 70%">กำลังเตรียมไฟล์...</span>
                                    <span id="swal-file-index" class="text-muted fw-bold">${currentIndex} / ${totalFiles} ไฟล์</span>
                                </div>
                                <div class="progress mb-2" style="height: 22px; border-radius: 10px; overflow: hidden; background-color: #f1f3f5;">
                                    <div id="swal-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" role="progressbar" style="width: 0%">0%</div>
                                </div>
                                <div id="swal-status-text" class="text-muted small">กำลังเริ่มต้น...</div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        showCancelButton: false,
                        didOpen: () => {
                            uploadNextFile();
                        }
                    });
                }
            };
        });
    </script>

    {{-- Global Details Modal for visiting/claim views --}}
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-clipboard2-pulse-fill me-2"></i>รายละเอียดการรับบริการ</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .spin { animation: spin 1s linear infinite; display: inline-block; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .badge-type { font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
    .badge-ppfs  { background:#fff3cd; color:#856404; }
    .badge-uc_cr { background:#cce5ff; color:#004085; }
    .badge-herb  { background:#d4edda; color:#155724; }
    </style>

    <script>
    if (typeof showDetails !== 'function') {
        window.showDetails = function(vn) {
            const body = document.getElementById('detailsModalBody');
            if (!body) return;
            body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
            $('#detailsModal').modal('show');

            $.get("{{ url('claim_op/ucs_incup/visit_details') }}", { vn: vn })
                .done(function(data) {
                    const visit = data.visit;
                    const items = data.items;
                    const v     = data.validation;

                    const statusBadge = v.is_valid
                        ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> ผ่านเงื่อนไข</span>'
                        : '<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle-fill"></i> ไม่ผ่าน ' + v.errors.length + ' รายการ</span>';

                    let html = `
                    <div class="row g-3">
                      <div class="col-md-6">
                        <div class="card border-0 bg-light-soft h-100">
                          <div class="card-body py-2 px-3">
                            <div class="fw-bold text-primary mb-2 small"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                            <table class="table table-sm table-borderless mb-0 small">
                              <tr><th class="text-muted" style="width:35%">HN</th><td class="fw-bold">${visit.hn}</td></tr>
                              <tr><th class="text-muted">CID</th><td>${visit.cid ?? '-'}</td></tr>
                              <tr><th class="text-muted">ชื่อ-สกุล</th><td>${visit.ptname}</td></tr>
                              <tr><th class="text-muted">สิทธิ์</th><td>${visit.pttype ?? '-'}</td></tr>
                              <tr><th class="text-muted">เพศ/อายุ</th><td>${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : visit.sex)} / ${visit.age_y ?? '-'} ปี</td></tr>
                            </table>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="card border-0 bg-light-soft h-100">
                          <div class="card-body py-2 px-3">
                            <div class="fw-bold text-primary mb-2 small"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก</div>
                            <table class="table table-sm table-borderless mb-0 small">
                              <tr><th class="text-muted" style="width:35%">วันที่</th><td>${visit.vstdate} ${visit.vsttime}</td></tr>
                              <tr><th class="text-muted">CC</th><td>${visit.cc ?? '-'}</td></tr>
                              <tr><th class="text-muted">PDX</th><td class="fw-bold text-danger">${visit.pdx ?? '-'}</td></tr>
                              <tr><th class="text-muted">SDX</th><td>${data.sec_diags.join(', ') || '-'}</td></tr>
                              <tr><th class="text-muted">ICD-9</th><td>${data.procedures.join(', ') || '-'}</td></tr>
                            </table>
                          </div>
                        </div>
                      </div>`;

                    // Items table
                    html += `
                      <div class="col-12">
                        <div class="fw-bold small text-dark mb-2"><i class="bi bi-list-check me-1"></i>รายการเรียกเก็บ ${statusBadge}</div>
                        <div class="table-responsive">
                          <table class="table table-sm table-hover small mb-0">
                            <thead class="table-light">
                              <tr>
                                <th>icode</th><th>รายการ</th><th>ประเภท</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-end">ราคา/หน่วย</th>
                                <th class="text-end">รวม</th>
                              </tr>
                            </thead><tbody>`;

                    items.forEach(function(item) {
                        let type = '';
                        if (item.ppfs  === 'Y') type += '<span class="badge-type badge-ppfs me-1">PPFS</span>';
                        if (item.uc_cr === 'Y') type += '<span class="badge-type badge-uc_cr me-1">UC_CR</span>';
                        if (item.herb32=== 'Y') type += '<span class="badge-type badge-herb me-1">Herb</span>';
                        html += `<tr>
                            <td class="text-muted">${item.icode}</td>
                            <td>${item.name ?? '-'}</td>
                            <td>${type}</td>
                            <td class="text-center">${item.qty}</td>
                            <td class="text-end">${parseFloat(item.unitprice).toLocaleString('th-TH',{minimumFractionDigits:2})}</td>
                            <td class="text-end fw-bold">${parseFloat(item.sum_price).toLocaleString('th-TH',{minimumFractionDigits:2})}</td>
                        </tr>`;
                    });

                    html += `</tbody></table></div></div></div>`;
                    body.innerHTML = html;
                })
                .fail(function() {
                    body.innerHTML = '<div class="alert alert-warning">ไม่สามารถโหลดข้อมูลได้</div>';
                });
        };
    }
    </script>
</body>

</html>