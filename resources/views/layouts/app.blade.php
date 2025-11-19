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
        .dropdown-menu .dropend:hover > .dropdown-menu {
        display: block;
        top: 0;
        left: 100%;
        margin-top: -1px;
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-success shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand btn btn-outline-info text-white" href="{{ url('/') }}">
                    <i class="bi bi-house-door"></i>
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
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                นำเข้าข้อมูล
                            </a>
                            <ul class="bg-success dropdown-menu dropdown-menu-end"> 
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        STM-UCS [OP-IP]
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_ucs') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_ucs_detail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li> 
                                @if($hasLookupIcode_kidney) 
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        STM-UCS [ฟอกไต]
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_ucs_kidney') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_ucs_kidneydetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>  
                                @endif
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        STM-OFC:BKK:BMT [OP-IP]
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_ofc') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_ofc_detail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li> 
                                @if($hasLookupIcode_kidney) 
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        STM-OFC [ฟอกไต]
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_ofc_kidney') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_ofc_kidneydetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>  
                                @endif
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        STM-LGO [OP-IP]
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_lgo') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_lgo_detail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>
                                @if($hasLookupIcode_kidney) 
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        STM-LGO [ฟอกไต]
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_lgo_kidney') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_lgo_kidneydetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>  
                                @endif
                                @if($hasLookupIcode_kidney) 
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        STM-SSS [ฟอกไต]
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_sss_kidney') }}">นำเข้าข้อมูล</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/import/stm_sss_kidneydetail') }}">รายละเอียด</a></li>                                       
                                    </ul>
                                </li>  
                                @endif
                            </ul>                                            
                        </li>
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                ตรวจสอบข้อมูล
                            </a>
                            <ul class="bg-success dropdown-menu dropdown-menu-end">                                 
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="link-primary dropdown-item text-white" href="{{ url('check/nhso_endpoint') }}">
                                        ปิดสิทธิ สปสช.
                                    </a>
                                    <a class="link-primary dropdown-item text-white" href="{{ url('check/drug_cat') }}">
                                        Drug Catalog สปสช.
                                    </a>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        สิทธิการรักษา
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('check/pttype') }}">HOSxP</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('check/nhso_subinscl') }}">สปสช.</a></li>                                       
                                    </ul>
                                </li>
                            </ul>
                        </li>   
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                งานเวชระเบียน
                            </a>
                            <ul class="bg-success dropdown-menu dropdown-menu-end"> 
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="link-primary dropdown-item text-white" href="{{ url('opd/oppp_visit') }}">
                                        OP-จำนวนผู้มารับบริการ
                                    </a>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        OP-รายโรคสำคัญ
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('opd/diag_sepsis') }}">Sepsis</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('opd/diag_stroke') }}">Stroke</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('opd/diag_stemi') }}">Stemi</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('opd/diag_pneumonia') }}">Pneumonia</a></li>
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="link-primary dropdown-item text-white" href="{{ url('/ipd/dchsummary') }}">
                                        IP-D/C Summary
                                    </a>
                                </li>
                            </ul>
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                เรียกเก็บ OP
                            </a>
                            <ul class="bg-success dropdown-menu dropdown-menu-end"> 
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        OP-UCS ประกันสุขภาพ
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/ucs_incup') }}"> UC-OP ใน CUP </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/ucs_inprovince') }}"> UC-OP ในจังหวัด </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/ucs_inprovince_va') }}"> UC-OP ในจังหวัด VA</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/ucs_outprovince') }}"> UC-OP ต่างจังหวัด </a>
                                        </li> 
                                        @if($hasLookupIcode_kidney)
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/ucs_kidney') }}"> UC-OP ฟอกไต </a>
                                        </li> 
                                        @endif
                                    </ul>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        OP-STP บุคคลที่มีปัญหาสถานะและสิทธิ 
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/stp_incup') }}"> STP-OP ใน CUP </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/stp_outcup') }}"> STP-OP นอก CUP </a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        OP-OFC กรมบัญชีกลาง
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/ofc') }}"> OFC-OP กรมบัญชีกลาง</a>
                                        </li>
                                        @if($hasLookupIcode_kidney) 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/ofc_kidney') }}">OFC-OP กรมบัญชีกลาง ฟอกไต </a>
                                        </li>
                                        @endif
                                    </ul>
                                </li>
                                 <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        OP-LGO อปท.
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/lgo') }}"> LGO-OP อปท.</a>
                                        </li>
                                        @if($hasLookupIcode_kidney) 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/lgo_kidney') }}">LGO-OP อปท. ฟอกไต </a>
                                        </li>
                                        @endif
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>     
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_op/bkk') }}" >
                                        OP-BKK อปท.รูปแบบพิเศษ กทม.
                                    </a>      
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_op/bmt') }}" >
                                        OP-BMT อปท.รูปแบบพิเศษ ขสมก.
                                    </a>  
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        OP-SSS ประกันสังคม
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/sss_ppfs') }}"> SS-OP ประกันสังคม PPFS</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/sss_fund') }}"> SS-OP ประกันสังคม กองทุนทดแทน</a>
                                        </li>
                                        @if($hasLookupIcode_kidney)
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/sss_kidney') }}">SS-OP ประกันสังคม ฟอกไต</a>
                                        </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_op/sss_hc') }}"> SS-OP ประกันสังคม ค่าใช้จ่ายสูง</a>
                                        </li>
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>  
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_op/rcpt') }}" >
                                        OP-ชำระเงิน
                                    </a>   
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_op/act') }}" >
                                        OP-พรบ.
                                    </a>   
                                </li>
                            </ul> 
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                เรียกเก็บ IP
                            </a>
                            <ul class="bg-success dropdown-menu dropdown-menu-end"> 
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        IP-UCS ประกันสุขภาพ
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_ip/ucs_incup') }}"> UC-IP ใน CUP </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_ip/ucs_outcup') }}"> UC-IP นอก CUP </a>
                                        </li> 
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_ip/stp') }}" > 
                                        IP-STP บุคคลที่มีปัญหาสถานะและสิทธิ 
                                    </a> 
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_ip/ofc') }}" >
                                        IP-OFC กรมบัญชีกลาง
                                    </a>   
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_ip/lgo') }}" >
                                        IP-LGO อปท.
                                    </a>       
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_ip/bkk') }}" >
                                        IP-BKK อปท.รูปแบบพิเศษ กทม.
                                    </a>      
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_ip/bmt') }}" >
                                        IP-BMT อปท.รูปแบบพิเศษ ขสมก.
                                    </a>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        IP-SSS ประกันสังคม
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_ip/sss') }}"> SS-IP ประกันสังคม ทั่วไป </a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('claim_ip/sss_hc') }}"> SS-IP ประกันสังคม ค่าใช้จ่ายสูง </a>
                                        </li> 
                                    </ul>
                                </li>
                                <!-- เมนูอื่น -->
                                <li>                                     
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_ip/gof') }}" >
                                        IP-GOF หน่วยงานรัฐ
                                    </a>    
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_ip/rcpt') }}" >
                                        IP-ชำระเงิน
                                    </a>   
                                    <a class="dropdown-item link-primary text-white " href="{{ url('claim_ip/act') }}" >
                                        IP-พรบ.
                                    </a>   
                                </li>
                            </ul> 
                        </li>    
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                MIS Hospital
                            </a>
                            <ul class="bg-success dropdown-menu dropdown-menu-end"> 
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        บริการผู้ป่วยนอก
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ae') }}">ผู้ป่วยนอกฉุกเฉิน</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_walkin') }}">OP WALKIN</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_herb') }}">บริการแพทย์แผนไทย ยาสมุนไพร</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_telemed') }}">บริการสาธารณสุขทางไกล (TELEMED)</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_rider') }}">จัดส่งยาทางไปรษณีย์</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_gdm') }}">บริการในกลุ่ม GDM</a>
                                        </li> 
                                    </ul>
                                </li>
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        บริการค่าใช้จ่ายสูง
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_drug_clopidogrel') }}">ยาต้านเกล็ดเลือด</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_drug_sk') }}">ยาละลายลิ่มเลือด</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ins') }}">อวัยวะเทียม/อุปกรณ์</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_palliative') }}">Palliative Care</a>
                                        </li> 
                                    </ul>
                                </li>
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        การส่งเสริมป้องกันโรค
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_fp') }}">การบริการวางแผนครอบครัว</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_prt') }}">บริการทดสอบการตั้งครรภ์ (PRT)</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_ida') }}">บริการคัดกรองโลหิตจางจากการขาดธาตุเหล็ก</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_ferrofolic') }}">บริการยาเม็ดเสริมธาตุเหล็ก</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_fluoride') }}">บริการเคลือบฟลูออไรด์ (กลุ่มเสี่ยง)</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_anc') }}">บริการฝากครรภ์ (ANC)</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_postnatal') }}">บริการตรวจหลังคลอด</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_fittest') }}">บริการตรวจคัดกรองมะเร็งลำไส้ใหญ่และสำไส้ตรง (Fit test)</a>
                                        </li> 
                                        <li>
                                            <a class="dropdown-item link-primary text-white" href="{{ url('mishos/ucs_ppfs_scr') }}">บริการคัดกรองและประเมินปัจจัยเสี่ยงต่อสุขภาพกาย/สุขภาพจิต (SCR)</a>
                                        </li> 
                                    </ul>
                                </li>                                
                            </ul> 
                        </li> 
                        <li >                            
                            <a class="btn btn-outline-info text-white" href="{{ url('debtor') }}">
                                ลูกหนี้ค่ารักษาพยาบาล
                            </a>       
                        </li>                 
                    @endguest
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <li > 
                            <div class="btn text-info">
                                V. 68-11-19 22:00
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
                                <a id="navbarDropdown" class="nav-link btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end bg-success" aria-labelledby="navbarDropdown">
                                    <!-- Admin -->
                                    @auth
                                        @if(auth()->user()->status === 'admin')                                            
                                            <a class="dropdown-item link-primary text-white" href="{{ route('admin.main_setting') }}">Main Setting</a>
                                            <a class="dropdown-item link-primary text-white" href="{{ route('admin.users.index') }}">Manage User</a>                                            
                                            <a class="dropdown-item link-primary text-white" href="{{ route('admin.lookup_icode.index') }}">Lookup icode</a>
                                            <a class="dropdown-item link-primary text-white" href="{{ route('admin.lookup_ward.index') }}">Lookup ward</a>
                                            <a class="dropdown-item link-primary text-white" href="{{ route('admin.lookup_hospcode.index') }}">Lookup hospcode</a>
                                            <a class="dropdown-item link-primary text-white" href="{{ route('admin.budget_year.index') }}">Budget year</a>
                                        @endif
                                    @endauth
                                    <!-- -->
                                    <a class="dropdown-item link-primary text-white" href="{{ route('logout') }}"
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
