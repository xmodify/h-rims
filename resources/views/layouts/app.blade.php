<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('/images/favicon.ico') }}" type="image/x-icon">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'H-RiMS') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- DataTables + Buttons + Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">


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
                    {{ config('app.name', 'H-RiMS') }}
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
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="{{ url('/import/stm_ucs') }}" >
                                    - Statement UCS
                                </a> 
                            </div>                 
                        </li>  
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                งานเวชระเบียน
                            </a>
                            <ul class="bg-success dropdown-menu dropdown-menu-end">   
                                <!-- เมนูอื่น -->
                                <li>
                                    <a class="link-primary dropdown-item text-white" href="{{ url('/ipd/dchsummary') }}">
                                        IP-D/C Summary
                                    </a>
                                </li>
                                <!-- ชี้ขวา -->
                                <li class="dropend">
                                    <a class="link-primary dropdown-item dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                                        รายโรคสำคัญ
                                    </a>
                                    <ul class="bg-success dropdown-menu">
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/diag/sepsis') }}">Sepsis</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/diag/stroke') }}">Stroke</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/diag/stemi') }}">Stemi</a></li>
                                        <li><a class="dropdown-item link-primary text-white" href="{{ url('/diag/pneumonia') }}">Pneumonia</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                เรียกเก็บ OP
                            </a>
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-UCS ประกันสุขภาพ
                                </a> 
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-STP บุคคลที่มีปัญหาสถานะและสิทธิ 
                                </a> 
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-OFC กรมบัญชีกลาง
                                </a>   
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-LGO อปท.
                                </a>       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-BKK อปท.รูปแบบพิเศษ กทม.
                                </a>      
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-BMT อปท.รูปแบบพิเศษ ขสมก.
                                </a>
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-SSS ประกันสังคม
                                </a>    
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-ชำระเงิน
                                </a>   
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OP-พรบ.
                                </a>   
                            </div> 
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                เรียกเก็บ IP
                            </a>
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-UCS ประกันสุขภาพ
                                </a> 
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-STP บุคคลที่มีปัญหาสถานะและสิทธิ 
                                </a> 
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-OFC กรมบัญชีกลาง
                                </a>   
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-LGO อปท.
                                </a>       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-BKK อปท.รูปแบบพิเศษ กทม.
                                </a>      
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-BMT อปท.รูปแบบพิเศษ ขสมก.
                                </a>
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-SSS ประกันสังคม
                                </a>    
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-ชำระเงิน
                                </a>   
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    IP-พรบ.
                                </a>   
                            </div> 
                        </li>                    
                    @endguest
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
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

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <!-- Admin -->
                                    @auth
                                        @if(auth()->user()->status === 'admin')                                            
                                            <a class="dropdown-item" href="{{ route('admin.main_setting') }}">Main Setting</a>
                                            <a class="dropdown-item" href="{{ route('admin.users.index') }}">Manage User</a>                                            
                                            <a class="dropdown-item" href="{{ route('admin.lookup_icode.index') }}">Lookup icode</a>
                                            <a class="dropdown-item" href="{{ route('admin.lookup_ward.index') }}">Lookup ward</a>
                                            <a class="dropdown-item" href="{{ route('admin.lookup_hospcode.index') }}">Lookup hospcode</a>
                                        @endif
                                    @endauth
                                    <!-- -->
                                    <a class="dropdown-item" href="{{ route('logout') }}"
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
