<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('/images/favicon_darkgreen.ico') }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RiMS</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->    
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <!-- DataTables + Buttons + Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-bottom: 2px solid rgba(255,255,255,0.1);
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
            background: rgba(255,255,255,0.1);
            transform: scale(1.05);
        }

        .nav-link-modern {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-link-modern:hover, .nav-link-modern.show {
            background: rgba(255,255,255,0.15);
            color: #fff !important;
        }

        .dropdown-menu-modern {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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

        .dropdown-menu-modern .dropend:hover > .dropdown-menu {
            display: block;
            top: 0;
            left: 100%;
            margin-top: -4px;
            margin-left: 0px;
        }

        .nav-version-badge {
            font-size: 0.7rem;
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.7);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
        }

        /* Dash Card Tokens */
        .dash-card {
            background: #ffffff !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            overflow: hidden !important;
            height: 100% !important;
            position: relative !important;
        }

        .dash-card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
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
            border-top: 1px solid rgba(0,0,0,0.05) !important;
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
            padding: 0.85rem 1.5rem !important; /* Slightly more compact */
            box-shadow: 0 4px 15px rgba(0,0,0,0.03) !important;
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
        .accent-1 { border-top: 4px solid #3b82f6 !important; } /* Blue */
        .accent-2 { border-top: 4px solid #6366f1 !important; } /* Indigo */
        .accent-3 { border-top: 4px solid #06b6d4 !important; } /* Cyan */
        .accent-4 { border-top: 4px solid #f59e0b !important; } /* Amber/Yellow */
        .accent-5 { border-top: 4px solid #f97316 !important; } /* Orange */
        .accent-6 { border-top: 4px solid #f43f5e !important; } /* Rose */
        .accent-7 { border-top: 4px solid #ec4899 !important; } /* Pink */
        .accent-8 { border-top: 4px solid #a855f7 !important; } /* Purple */
        .accent-9 { border-top: 4px solid #10b981 !important; } /* Emerald */
        .accent-10 { border-top: 4px solid #22c55e !important; } /* Green */
        .accent-11 { border-top: 4px solid #ef4444 !important; } /* Red */
        .accent-12 { border-top: 4px solid #991b1b !important; } /* Maroon */

        .icon-color-1, .text-color-1 { color: #3b82f6 !important; }
        .icon-color-2, .text-color-2 { color: #6366f1 !important; }
        .icon-color-3, .text-color-3 { color: #06b6d4 !important; }
        .icon-color-4, .text-color-4 { color: #f59e0b !important; }
        .icon-color-5, .text-color-5 { color: #f97316 !important; }
        .icon-color-6, .text-color-6 { color: #f43f5e !important; }
        .icon-color-7, .text-color-7 { color: #ec4899 !important; }
        .icon-color-8, .text-color-8 { color: #a855f7 !important; }
        .icon-color-9, .text-color-9 { color: #10b981 !important; }
        .icon-color-10, .text-color-10 { color: #22c55e !important; }
        .icon-color-11, .text-color-11 { color: #ef4444 !important; }
        .icon-color-12, .text-color-12 { color: #991b1b !important; }

        .icon-bg-1 { background: var(--dash-blue) !important; color: #fff !important; }
        .icon-bg-2 { background: var(--dash-indigo) !important; color: #fff !important; }
        .icon-bg-3 { background: var(--dash-cyan) !important; color: #fff !important; }
        .icon-bg-4 { background: var(--dash-amber) !important; color: #fff !important; }
        .icon-bg-5 { background: var(--dash-orange) !important; color: #fff !important; }
        .icon-bg-6 { background: var(--dash-rose) !important; color: #fff !important; }
        .icon-bg-7 { background: var(--dash-pink) !important; color: #fff !important; }
        .icon-bg-8 { background: var(--dash-purple) !important; color: #fff !important; }
        .icon-bg-9 { background: var(--dash-emerald) !important; color: #fff !important; }
        .icon-bg-10 { background: var(--dash-green) !important; color: #fff !important; }
        .icon-bg-11 { background: var(--dash-red) !important; color: #fff !important; }
        .icon-bg-12 { background: var(--dash-maroon) !important; color: #fff !important; }

        .icon-box {
            width: 42px !important;
            height: 42px !important;
            border-radius: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.25rem !important;
            margin-bottom: 12px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
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
        .table th, .table td {
            border-bottom: 1px solid #e2e8f0 !important;
            border-right: 1px solid #e2e8f0 !important;
            padding: 8px 10px !important; /* Reduced padding to prevent overlap */
            vertical-align: middle !important;
            font-size: 0.875rem !important; /* Increased for better readability */
            line-height: 1.4 !important;
        }
        
        /* Remove last border right */
        .table th:last-child, .table td:last-child {
            border-right: none !important;
        }

        /* Table Headers - Structured Blue Theme */
        .table thead th {
            background-color: #dbeafe !important; /* Pastel Blue */
            color: #1e3a8a !important; /* Dark Navy */
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 0.8rem !important;
            letter-spacing: 0.025em !important;
            border-bottom: 2px solid #bfdbfe !important;
            border-right: 1px solid #bfdbfe !important; /* Explicit Vertical Border for Headers */
            text-align: center !important;
        }

        /* Ensure vertical lines for all cells including headers */
        .table th, .table td {
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
        .table th:last-child, .table td:last-child {
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
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark navbar-modern sticky-top">
            <div class="container-fluid px-lg-4">
                <a class="navbar-brand-modern" href="{{ url('/') }}">
                    <i class="bi bi-shield-lock-fill" style="color: #6ed3ff;"></i>
                    <span>RiMS</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                    @guest
                        @else 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <i class="bi bi-cloud-arrow-down-fill me-1"></i> นำเข้าข้อมูล
                            </a>
                            <ul class="dropdown-menu dropdown-menu-modern"> 
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        STM-UCS [OP-IP]
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ucs') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ucs_detail_opd') }}">รายละเอียด OPD</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ucs_detail_ipd') }}">รายละเอียด IPD</a></li>
                                    </ul>
                                </li> 
                                @if($hasLookupIcode_kidney) 
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        STM-UCS [ฟอกไต]
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ucs_kidney') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ucs_kidneydetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>  
                                @endif
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        STM-OFC:BKK:BMT [OP-IP]
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ofc') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ofc_detail_opd') }}">รายละเอียด OPD</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ofc_detail_ipd') }}">รายละเอียด IPD</a></li>
                                    </ul>
                                </li>                                 
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        STM-OFC [CSOP-ฟอกไต]
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ofc_csop') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ofc_csopdetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li> 
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        STM-OFC [CIPN]
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ofc_cipn') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_ofc_cipndetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>  
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        STM-LGO [OP-IP]
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_lgo') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_lgo_detail_opd') }}">รายละเอียด OPD</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_lgo_detail_ipd') }}">รายละเอียด IPD</a></li>
                                    </ul>
                                </li>
                                @if($hasLookupIcode_kidney) 
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        STM-LGO [ฟอกไต]
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_lgo_kidney') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_lgo_kidneydetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>  
                                @endif
                                @if($hasLookupIcode_kidney) 
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        STM-SSS [ฟอกไต]
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_sss_kidney') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('/import/stm_sss_kidneydetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>  
                                @endif
                            </ul>                                            
                        </li>
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <i class="bi bi-check-circle-fill me-1"></i> ตรวจสอบข้อมูล
                            </a>
                            <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end">                                 
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="dropdown-item dropdown-item-modern" href="{{ url('check/nhso_endpoint') }}">
                                        ปิดสิทธิ สปสช.
                                    </a>
                                    <a class="dropdown-item dropdown-item-modern" href="{{ url('check/fdh_claim_status') }}">
                                        FDH Claim Status
                                    </a>
                                    <a class="dropdown-item dropdown-item-modern" href="{{ url('check/drug_cat') }}">
                                        Drug Catalog สปสช.
                                    </a>
                                    <a class="dropdown-item dropdown-item-modern" href="{{ url('check/nondrugitems') }}">
                                        ค่ารักษาพยาบาล
                                    </a>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        สิทธิการรักษา
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('check/pttype') }}">HOSxP</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('check/nhso_subinscl') }}">สปสช.</a></li>                                       
                                    </ul>
                                </li>
                            </ul>
                        </li>   
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <i class="bi bi-file-earmark-medical-fill me-1"></i> งานเวชระเบียน
                            </a>
                            <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end"> 
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="dropdown-item dropdown-item-modern" href="{{ url('opd/oppp_visit') }}">
                                        OP-จำนวนผู้มารับบริการ
                                    </a>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        OP-รายโรคสำคัญ
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('opd/diag_sepsis') }}">Sepsis</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('opd/diag_stroke') }}">Stroke</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('opd/diag_stemi') }}">Stemi</a></li>
                                        <li><a class="dropdown-item dropdown-item-modern" href="{{ url('opd/diag_pneumonia') }}">Pneumonia</a></li>
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="dropdown-item dropdown-item-modern" href="{{ url('/ipd/dchsummary') }}">
                                        IP-D/C Summary
                                    </a>
                                </li>
                            </ul>
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <i class="bi bi-wallet-fill me-1"></i> เรียกเก็บ OP
                            </a>
                            <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end"> 
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        OP-UCS ประกันสุขภาพ
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/ucs_incup') }}"> UC-OP ใน CUP </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/ucs_inprovince') }}"> UC-OP ในจังหวัด </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/ucs_inprovince_va') }}"> UC-OP ในจังหวัด VA</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/ucs_outprovince') }}"> UC-OP ต่างจังหวัด </a>
                                        </li> 
                                        @if($hasLookupIcode_kidney)
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/ucs_kidney') }}"> UC-OP ฟอกไต </a>
                                        </li> 
                                        @endif
                                    </ul>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        OP-STP บุคคลที่มีปัญหาสถานะและสิทธิ 
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/stp_incup') }}"> STP-OP ใน CUP </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/stp_outcup') }}"> STP-OP นอก CUP </a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        OP-OFC กรมบัญชีกลาง
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/ofc') }}"> OFC-OP กรมบัญชีกลาง</a>
                                        </li>
                                        @if($hasLookupIcode_kidney) 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/ofc_kidney') }}">OFC-OP กรมบัญชีกลาง ฟอกไต </a>
                                        </li>
                                        @endif
                                    </ul>
                                </li>
                                 <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        OP-LGO อปท.
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/lgo') }}"> LGO-OP อปท.</a>
                                        </li>
                                        @if($hasLookupIcode_kidney) 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/lgo_kidney') }}">LGO-OP อปท. ฟอกไต </a>
                                        </li>
                                        @endif
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>     
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_op/bkk') }}" >
                                        OP-BKK กรุงเทพมหานคร
                                    </a>      
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_op/bmt') }}" >
                                        OP-BMT ขสมก.
                                    </a>  
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        OP-SSS ประกันสังคม
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/sss_ppfs') }}"> SS-OP ประกันสังคม PPFS</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/sss_fund') }}"> SS-OP ประกันสังคม กองทุนทดแทน</a>
                                        </li>
                                        @if($hasLookupIcode_kidney)
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/sss_kidney') }}">SS-OP ประกันสังคม ฟอกไต</a>
                                        </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_op/sss_hc') }}"> SS-OP ประกันสังคม ค่าใช้จ่ายสูง</a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>  
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_op/rcpt') }}" >
                                        OP-ชำระเงิน
                                    </a>   
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_op/act') }}" >
                                        OP-พรบ.
                                    </a>   
                                </li>
                            </ul> 
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <i class="bi bi-hospital-fill me-1"></i> เรียกเก็บ IP
                            </a>
                            <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end"> 
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        IP-UCS ประกันสุขภาพ
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_ip/ucs_incup') }}"> UC-IP ใน CUP </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_ip/ucs_outcup') }}"> UC-IP นอก CUP </a>
                                        </li> 
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/stp') }}" > 
                                        IP-STP บุคคลที่มีปัญหาสถานะและสิทธิ 
                                    </a> 
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/ofc') }}" >
                                        IP-OFC กรมบัญชีกลาง
                                    </a>   
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/lgo') }}" >
                                        IP-LGO อปท.
                                    </a>       
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/bkk') }}" >
                                        IP-BKK กรุงเทพมหานคร
                                    </a>      
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/bmt') }}" >
                                        IP-BMT ขสมก.
                                    </a>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        IP-SSS ประกันสังคม
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_ip/sss') }}"> SS-IP ประกันสังคม ทั่วไป </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('claim_ip/sss_hc') }}"> SS-IP ประกันสังคม ค่าใช้จ่ายสูง </a>
                                        </li> 
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>                                     
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/gof') }}" >
                                        IP-GOF หน่วยงานรัฐ
                                    </a>    
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/rcpt') }}" >
                                        IP-ชำระเงิน
                                    </a>   
                                    <a class="dropdown-item dropdown-item-modern " href="{{ url('claim_ip/act') }}" >
                                        IP-พรบ.
                                    </a>   
                                </li>
                            </ul> 
                        </li>    
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <i class="bi bi-graph-up-arrow me-1"></i> MIS Hospital
                            </a>
                            <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end"> 
                                <!-- ชี้ขวา -->
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        บริการผู้ป่วยนอก
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ae') }}">ผู้ป่วยนอกฉุกเฉิน</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_walkin') }}">OP WALKIN</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_herb') }}">บริการแพทย์แผนไทย ยาสมุนไพร</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_telemed') }}">บริการสาธารณสุขทางไกล (TELEMED)</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_rider') }}">จัดส่งยาทางไปรษณีย์</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_gdm') }}">บริการในกลุ่ม GDM</a>
                                        </li> 
                                    </ul>
                                </li>
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        บริการค่าใช้จ่ายสูง
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_drug_clopidogrel') }}">ยาต้านเกล็ดเลือด</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_drug_sk') }}">ยาละลายลิ่มเลือด</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ins') }}">อวัยวะเทียม/อุปกรณ์</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_palliative') }}">Palliative Care</a>
                                        </li> 
                                    </ul>
                                </li>
                                <li class="dropend position-relative">
                                    <a class="dropdown-item dropdown-item-modern dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                        การส่งเสริมป้องกันโรค
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-modern">
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_fp') }}">การบริการวางแผนครอบครัว</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_prt') }}">บริการทดสอบการตั้งครรภ์ (PRT)</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_ida') }}">บริการคัดกรองโลหิตจางจากการขาดธาตุเหล็ก</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_ferrofolic') }}">บริการยาเม็ดเสริมธาตุเหล็ก</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_fluoride') }}">บริการเคลือบฟลูออไรด์ (กลุ่มเสี่ยง)</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_anc') }}">บริการฝากครรภ์ (ANC)</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_postnatal') }}">บริการตรวจหลังคลอด</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_fittest') }}">บริการตรวจคัดกรองมะเร็งลำไส้ใหญ่และสำไส้ตรง (Fit test)</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ url('mishos/ucs_ppfs_scr') }}">บริการคัดกรองและประเมินปัจจัยเสี่ยงต่อสุขภาพกาย/สุขภาพจิต (SCR)</a>
                                        </li> 
                                    </ul>
                                </li>                                
                            </ul> 
                        </li> 
                        <li class="nav-item">                            
                            <a class="nav-link nav-link-modern" href="{{ url('debtor') }}">
                                <i class="bi bi-person-lines-fill me-1"></i> ลูกหนี้ค่ารักษา
                            </a>       
                        </li>                 
                    @endguest
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item d-flex align-items-center"> 
                            <div class="nav-version-badge">
                                V.69-02-16 21:00
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
                                <a id="navbarDropdown" class="nav-link nav-link-modern dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-modern" aria-labelledby="navbarDropdown">
                                    <!-- Admin -->
                                    @auth
                                        @if(auth()->user()->status === 'admin')                                            
                                            <a class="dropdown-item dropdown-item-modern" href="{{ route('admin.main_setting') }}">Main Setting</a>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ route('admin.users.index') }}">Manage User</a>                                            
                                            <a class="dropdown-item dropdown-item-modern" href="{{ route('admin.lookup_icode.index') }}">Lookup icode</a>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ route('admin.lookup_ward.index') }}">Lookup ward</a>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ route('admin.lookup_hospcode.index') }}">Lookup hospcode</a>
                                            <a class="dropdown-item dropdown-item-modern" href="{{ route('admin.budget_year.index') }}">Budget year</a>
                                        @endif
                                    @endauth
                                    <!-- -->
                                    <a class="dropdown-item dropdown-item-modern" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
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
            @yield('content')    
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables core -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Buttons + Export -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <!-- JSZip (required for Excel export) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>   

    <!-- Stack for per-page script -->
    @stack('scripts')
    

</body>
</html>
