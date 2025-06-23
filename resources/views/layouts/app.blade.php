<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'H-RiMS') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="{{ url('/ipd/dchsummary') }}" >
                                    IPD-D/C Summary
                                </a> 
                            </div>                 
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                ประกันสุขภาพ UCS
                            </a>
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    UC-OP ใน CUP
                                </a> 
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    UC-OP ในจังหวัด
                                </a> 
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    UC-OP ต่างจังหวัด
                                </a>   
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    UC-IP
                                </a>      
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    STP-OP
                                </a>    
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    STP-IP
                                </a>                                
                            </div> 
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                กรมบัญชีกลาง OFC
                            </a>
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OFC-OP
                                </a>    
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    OFC-IP
                                </a>                         
                            </div> 
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                อปท. LGO
                            </a>
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    LGO-OP
                                </a>    
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    LGO-IP
                                </a>                                    
                            </div> 
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                อปท. รูปแบบพิเศษ
                            </a>
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    BKK-OP
                                </a>    
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    BKK-IP
                                </a>   
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    BMT-OP
                                </a>  
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    BMT-IP
                                </a>                                   
                            </div> 
                        </li> 
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                ประกันสังคม SSS
                            </a>
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    SS-OP
                                </a>    
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    SS-IP
                                </a>                                    
                            </div> 
                        </li>       
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="btn btn-outline-info dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                               ชำระเงิน
                            </a>
                            <div class=" btn btn-outline-success dropdown-menu dropdown-menu-end">                                       
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    ชำระเงิน-OP
                                </a>    
                                <a class="dropdown-item link-primary text-white " href="#" >
                                    ชำระเงิน-IP
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
                                    <!-- Admin -->
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
</body>
</html>
