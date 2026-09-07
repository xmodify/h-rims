@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-lg-5">
    <!-- Page Header -->
    <div class="page-header-box mb-4 p-3 p-md-4 rounded-4 bg-white shadow-sm border w-100 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="mb-1 text-primary fw-bold d-flex align-items-center">
                <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary me-2.5 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-gear-wide-connected fs-5"></i>
                </span>
                ระบบตั้งค่า (Main Setting)
            </h4>
            <small class="text-muted">จัดการการตั้งค่าพื้นฐาน พารามิเตอร์ระบบ การเชื่อมต่อภายนอก และการจับคู่รหัสสิทธิ</small>
        </div>
        
        <div class="d-flex flex-column align-items-end gap-2 ms-auto">
            <!-- Action Buttons -->
            <div class="d-flex gap-2 justify-content-end">
                <button class="btn btn-outline-danger btn-sm px-3 rounded-pill shadow-sm hover-scale" id="gitPullBtn">
                    <i class="bi bi-git me-1"></i> Git Pull
                </button>
                <form id="structureForm" method="POST" action="{{ route('admin.up_structure') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm hover-scale" onclick="confirmAction(event)">
                        <i class="bi bi-database-fill-up me-1"></i> Upgrade Structure
                    </button>
                </form>
            </div>

            <!-- Global Search Input (Under Upgrade Structure Button) -->
            <div class="input-group input-group-sm mt-1" style="width: 320px; max-width: 100%;">
                <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="globalSettingSearchInput" class="form-control bg-light border-start-0 border-end-0 py-1.5" placeholder="ค้นหาชื่อการตั้งค่าทุกหมวด..." autocomplete="off">
                <button class="btn bg-light border-start-0 text-muted rounded-end-pill pe-3" type="button" id="clearGlobalSearchBtn" style="display: none;">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </div>
    </div>

    @php
        // Metadata mapping for categories: icon, color, short Thai subtitle
        $categoryMeta = [
            'Basic Information' => [
                'icon' => 'bi-hospital',
                'color' => 'primary',
                'sub' => 'ข้อมูล รพ., เตียง, BaseRate',
                'slug' => 'basic-info'
            ],
            'HOSxP Mapping' => [
                'icon' => 'bi-diagram-3-fill',
                'color' => 'indigo',
                'sub' => 'แมปปิ้งรหัสสิทธิ แล็บ ยา HOSxP',
                'slug' => 'hosxp-mapping'
            ],
            'FDH Setting' => [
                'icon' => 'bi-cloud-arrow-up-fill',
                'color' => 'info',
                'sub' => 'เชื่อมต่อ FDH MOPH Claim API',
                'slug' => 'fdh-setting'
            ],
            'Notify Setting' => [
                'icon' => 'bi-bell-fill',
                'color' => 'warning',
                'sub' => 'Telegram, MOPH Notify',
                'slug' => 'notify-setting'
            ],
            'RiMS Copilot (AI & LLM)' => [
                'icon' => 'bi-robot',
                'color' => 'success',
                'sub' => 'HosFin การเงิน & คลังความรู้ RAG',
                'slug' => 'ai-copilot'
            ],
            'Provider ID (Health ID)' => [
                'icon' => 'bi-person-badge-fill',
                'color' => 'teal',
                'sub' => 'ยืนยันตัวตนบุคลากรสาธารณสุข',
                'slug' => 'health-id'
            ],
            'Moph Alert 2FA' => [
                'icon' => 'bi-shield-lock-fill',
                'color' => 'danger',
                'sub' => 'ระบบความปลอดภัย 2FA MOPH',
                'slug' => 'moph-alert'
            ],
            'KTB Corporate (EDC)' => [
                'icon' => 'bi-credit-card-2-front-fill',
                'color' => 'primary',
                'sub' => 'เชื่อมต่อเครื่องรูดบัตร KTB EDC',
                'slug' => 'ktb-edc'
            ],
            'License Setting' => [
                'icon' => 'bi-patch-check-fill',
                'color' => 'success',
                'sub' => 'ใบอนุญาตสิทธิ์, GitHub Token',
                'slug' => 'license-setting'
            ],
            'Other Settings' => [
                'icon' => 'bi-sliders',
                'color' => 'secondary',
                'sub' => 'การตั้งค่าระบบอื่นๆ',
                'slug' => 'other-settings'
            ],
        ];

        $totalSettingsCount = 0;
        $allSettingsFlat = [];
        $sensitiveList = [
            'fdh_pass', 'fdh_secretKey', 'git_token', 'telegram_token', 'aopod_token', 
            'telegram_chat_id_register', 'telegram_chat_id_ipdsummary',
            'telegram_chat_id_notify_summary',
            'health_id_client_id', 'health_id_client_secret',
            'provider_id_client_id', 'provider_id_secret_key',
            'moph_alert_client_id', 'moph_alert_client_secret',
            'ktb_password', 'ai_api_key', 'ai_hosfin_api_key', 'ai_rag_api_key', 'ai_hosxp_api_key'
        ];
        $booleanList = ['provider_id_active', 'moph_alert_active', 'ai_active'];

        foreach ($groupedData as $cat => $items) {
            $totalSettingsCount += count($items);
            $m = $categoryMeta[$cat] ?? [
                'icon' => 'bi-gear-fill',
                'color' => 'primary',
                'sub' => 'การตั้งค่าระบบ',
                'slug' => 'cat'
            ];
            foreach ($items as $s) {
                $allSettingsFlat[] = [
                    'category' => $cat,
                    'category_slug' => $m['slug'],
                    'category_icon' => $m['icon'],
                    'category_color' => $m['color'],
                    'name' => $s->name,
                    'name_th' => $s->name_th,
                    'value' => $s->value,
                    'is_sensitive' => in_array($s->name, $sensitiveList),
                    'is_boolean' => in_array($s->name, $booleanList)
                ];
            }
        }
    @endphp

    <!-- Main Setting Vertical Tab Layout -->
    <div class="row g-4 mb-5">
        <!-- Left Sidebar (Category Navigation) -->
        <div class="col-xl-3 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-setting-sidebar bg-white overflow-hidden">
                <!-- Sidebar Header -->
                <div class="p-3 border-bottom bg-light bg-opacity-50">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark small text-uppercase tracking-wider">
                            <i class="bi bi-grid-fill me-1 text-primary"></i> หมวดหมู่การตั้งค่า
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 small">
                            {{ count($groupedData) }} หมวด
                        </span>
                    </div>
                </div>

                <!-- Category Nav List -->
                <div class="p-2 setting-nav-scroll" id="settingNavList">
                    <div class="nav flex-column nav-pills" id="settingTabs" role="tablist" aria-orientation="vertical">
                        @php $idx = 0; @endphp
                        @foreach($groupedData as $category => $settings)
                            @php
                                $meta = $categoryMeta[$category] ?? [
                                    'icon' => 'bi-gear-fill',
                                    'color' => 'primary',
                                    'sub' => 'การตั้งค่าระบบ',
                                    'slug' => 'cat-' . $idx
                                ];
                                $tabSlug = $meta['slug'];
                                $isFirst = ($idx === 0);
                                $itemCount = count($settings);
                                $idx++;
                            @endphp
                            <button class="nav-link setting-nav-btn {{ $isFirst ? 'active' : '' }}" 
                                    id="nav-tab-{{ $tabSlug }}" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#pane-{{ $tabSlug }}" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="pane-{{ $tabSlug }}" 
                                    aria-selected="{{ $isFirst ? 'true' : 'false' }}"
                                    data-slug="{{ $tabSlug }}">
                                <div class="d-flex align-items-center text-truncate me-2">
                                    <span class="setting-nav-icon bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }} me-2.5">
                                        <i class="bi {{ $meta['icon'] }}"></i>
                                    </span>
                                    <div class="text-truncate">
                                        <div class="setting-nav-title text-truncate">{{ $category }}</div>
                                        <div class="setting-nav-sub text-truncate">{{ $meta['sub'] }}</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill setting-nav-badge">
                                    {{ $itemCount }}
                                </span>
                            </button>
                        @endforeach

                        <!-- Divider -->
                        <hr class="my-2 text-muted opacity-25">

                        <!-- Show All Tab -->
                        <button class="nav-link setting-nav-btn" 
                                id="nav-tab-all" 
                                data-bs-toggle="pill" 
                                data-bs-target="#pane-all" 
                                type="button" 
                                role="tab" 
                                aria-controls="pane-all" 
                                aria-selected="false"
                                data-slug="all">
                            <div class="d-flex align-items-center text-truncate me-2">
                                <span class="setting-nav-icon bg-secondary-subtle text-secondary me-2.5">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                </span>
                                <div class="text-truncate">
                                    <div class="setting-nav-title text-truncate">ดูทั้งหมด (Show All)</div>
                                    <div class="setting-nav-sub text-truncate">แสดงทุกหมวดหมู่ในหน้าเดียว</div>
                                </div>
                            </div>
                            <span class="badge rounded-pill setting-nav-badge">
                                {{ $totalSettingsCount }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Content Area -->
        <div class="col-xl-9 col-lg-8">
            <!-- Global Search Results Container (Shown when searching) -->
            <div id="globalSearchResultsWrapper" style="display: none;">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-search fs-5"></i>
                            </span>
                            <div>
                                <h5 class="mb-0 fw-bold text-dark">
                                    ผลการค้นหา: <span id="searchKeywordDisplay" class="text-primary"></span>
                                </h5>
                                <small class="text-muted">พบทั้งหมด <b id="searchMatchCount">0</b> รายการจากการค้นหาทุกหมวดหมู่</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" id="closeSearchResultsBtn">
                            <i class="bi bi-x-lg me-1"></i> ปิดผลการค้นหา
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle setting-table">
                            <thead class="bg-light bg-opacity-75">
                                <tr>
                                    <th class="ps-4 py-3 text-muted fw-semibold small text-uppercase" style="width: 24%;">หมวดหมู่</th>
                                    <th class="py-3 text-muted fw-semibold small text-uppercase" style="width: 36%;">ชื่อการตั้งค่า</th>
                                    <th class="py-3 text-muted fw-semibold small text-uppercase">ค่าที่ตั้งไว้</th>
                                    <th class="pe-4 py-3 text-end text-muted fw-semibold small text-uppercase" style="width: 110px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="globalSearchResultsTbody">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="globalSearchNoResults" class="p-5 text-center text-muted" style="display: none;">
                        <i class="bi bi-search text-muted fs-1 d-block mb-2 opacity-50"></i>
                        <h6 class="fw-bold text-dark">ไม่พบการตั้งค่าที่ตรงกับคำค้นหา</h6>
                        <p class="small text-muted mb-0">ลองค้นหาด้วยคำอื่น เช่น <code>fdh</code>, <code>token</code>, <code>bed</code>, <code>ai</code></p>
                    </div>
                </div>
            </div>

            <!-- Normal Tab Panes (Hidden when global search is active) -->
            <div class="tab-content" id="settingTabsContent">
                @php $idx = 0; @endphp
                @foreach($groupedData as $category => $settings)
                    @php
                        $meta = $categoryMeta[$category] ?? [
                            'icon' => 'bi-gear-fill',
                            'color' => 'primary',
                            'sub' => 'การตั้งค่าระบบ',
                            'slug' => 'cat-' . $idx
                        ];
                        $tabSlug = $meta['slug'];
                        $isFirst = ($idx === 0);
                        $idx++;
                    @endphp
                    <div class="tab-pane fade {{ $isFirst ? 'show active' : '' }}" 
                         id="pane-{{ $tabSlug }}" 
                         role="tabpanel" 
                         aria-labelledby="nav-tab-{{ $tabSlug }}"
                         tabindex="0">
                        
                        {{-- Special Layout for RiMS Copilot (AI & LLM): Sub-tabs for HosFin, RAG, and HOSxP --}}
                        @if($category === 'RiMS Copilot (AI & LLM)')
                            @php
                                $hosfinSettings = $settings->filter(function($s) {
                                    return str_starts_with($s->name, 'ai_hosfin');
                                });
                                $ragSettings = $settings->filter(function($s) {
                                    return str_starts_with($s->name, 'ai_rag');
                                });
                                $hosxpSettings = $settings->filter(function($s) {
                                    return str_starts_with($s->name, 'ai_hosxp');
                                });
                            @endphp
                            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden setting-category-card">
                                <!-- Main Card Header -->
                                <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="category-header-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                                            <i class="bi bi-robot fs-4"></i>
                                        </span>
                                        <div>
                                            <h5 class="mb-0 fw-bold text-dark">{{ $category }}</h5>
                                            <small class="text-muted">ระบบผู้ช่วยปัญญาประดิษฐ์ (การเงิน HosFin, คลังความรู้ RAG, ตรวจสอบข้อมูล HOSxP)</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold">
                                        <i class="bi bi-cpu me-1"></i> รวม {{ count($settings) }} การตั้งค่า
                                    </span>
                                </div>

                                <!-- Sub-tabs Navigation -->
                                <div class="px-4 pt-3 bg-light bg-opacity-40 border-bottom">
                                    <ul class="nav nav-tabs custom-inner-tabs border-bottom-0 gap-2" id="aiSubTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active px-4 py-2.5 fw-bold rounded-top-3 d-flex align-items-center gap-2" 
                                                    id="tab-btn-hosfin" 
                                                    data-bs-toggle="tab" 
                                                    data-bs-target="#subpane-hosfin" 
                                                    type="button" 
                                                    role="tab" 
                                                    aria-controls="subpane-hosfin" 
                                                    aria-selected="true">
                                                <i class="bi bi-cash-stack text-success fs-6"></i>
                                                <span>ระบบการเงิน (HosFin)</span>
                                                <span class="badge rounded-pill bg-success text-white ms-1">{{ count($hosfinSettings) }}</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link px-4 py-2.5 fw-bold rounded-top-3 d-flex align-items-center gap-2" 
                                                    id="tab-btn-rag" 
                                                    data-bs-toggle="tab" 
                                                    data-bs-target="#subpane-rag" 
                                                    type="button" 
                                                    role="tab" 
                                                    aria-controls="subpane-rag" 
                                                    aria-selected="false">
                                                <i class="bi bi-book-half text-primary fs-6"></i>
                                                <span>คลังความรู้ (RAG)</span>
                                                <span class="badge rounded-pill bg-primary text-white ms-1">{{ count($ragSettings) }}</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link px-4 py-2.5 fw-bold rounded-top-3 d-flex align-items-center gap-2" 
                                                    id="tab-btn-hosxp" 
                                                    data-bs-toggle="tab" 
                                                    data-bs-target="#subpane-hosxp" 
                                                    type="button" 
                                                    role="tab" 
                                                    aria-controls="subpane-hosxp" 
                                                    aria-selected="false">
                                                <i class="bi bi-database-check fs-6" style="color: #6366f1;"></i>
                                                <span>ตรวจสอบข้อมูล HOSxP</span>
                                                <span class="badge rounded-pill text-white ms-1" style="background-color: #6366f1;">{{ count($hosxpSettings) }}</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Sub-tabs Content -->
                                <div class="tab-content" id="aiSubTabsContent">
                                    <!-- HosFin Pane -->
                                    <div class="tab-pane fade show active" id="subpane-hosfin" role="tabpanel" aria-labelledby="tab-btn-hosfin" tabindex="0">
                                        <div class="p-3 px-4 bg-light bg-opacity-30 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <span class="small text-muted">
                                                <i class="bi bi-info-circle-fill text-success me-1"></i> การเชื่อมต่อ Provider, API Key และโมเดลสำหรับวิเคราะห์การเงินและข้อมูลลูกหนี้ HosFin
                                            </span>
                                            <button type="button" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm hover-scale" onclick="openAiSettingsModal('hosfin')">
                                                <i class="bi bi-gear-fill me-1"></i> ตั้งค่า AI HosFin
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0 align-middle setting-table">
                                                <thead class="bg-light bg-opacity-75">
                                                    <tr>
                                                        <th class="ps-4 py-3 text-muted fw-semibold small text-uppercase" style="width: 42%;">ชื่อการตั้งค่า</th>
                                                        <th class="py-3 text-muted fw-semibold small text-uppercase">ค่าที่ตั้งไว้</th>
                                                        <th class="pe-4 py-3 text-end text-muted fw-semibold small text-uppercase" style="width: 110px;">จัดการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($hosfinSettings as $row)
                                                        @php 
                                                            $isSensitive = in_array($row->name, $sensitiveList);
                                                            $isBoolean = in_array($row->name, $booleanList);
                                                        @endphp
                                                        <tr class="setting-row">
                                                            <td class="ps-4 py-3">
                                                                <span class="fw-bold text-dark">{{ $row->name_th }}</span>
                                                                <div class="d-flex align-items-center gap-1 mt-0.5">
                                                                    <code class="text-muted small">{{ $row->name }}</code>
                                                                    @if($isSensitive)
                                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill py-0 px-1.5" style="font-size: 10px;">Sensitive</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td class="py-3">
                                                                @if($isBoolean)
                                                                    <span class="badge bg-{{ $row->value === 'Y' ? 'success' : 'secondary' }} rounded-pill text-white fw-bold px-3 py-1.5 shadow-xs">
                                                                        <i class="bi bi-{{ $row->value === 'Y' ? 'check-circle-fill' : 'dash-circle' }} me-1"></i>
                                                                        {{ $row->value === 'Y' ? 'เปิดใช้งาน (ON)' : 'ปิดใช้งาน (OFF)' }}
                                                                    </span>
                                                                @elseif($isSensitive)
                                                                    <div class="input-group input-group-sm" style="max-width: 280px;">
                                                                        <input type="password" class="form-control border bg-light fw-semibold sensitive-input rounded-start-pill ps-3" value="{{ $row->value }}" readonly>
                                                                        <button class="btn btn-light border border-start-0 btn-peek rounded-end-pill pe-3 text-muted" type="button" title="กดเพื่อดู/ซ่อนรหัสผ่าน">
                                                                            <i class="bi bi-eye"></i>
                                                                        </button>
                                                                    </div>
                                                                @else
                                                                    <span class="badge bg-light text-dark border p-2 px-3 rounded-pill fw-bold text-wrap text-break" style="max-width: 400px;">
                                                                        {{ $row->value ?: '-' }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="pe-4 py-3 text-end">
                                                                <button class="btn btn-warning btn-sm btn-edit rounded-pill shadow-xs hover-scale px-3" 
                                                                    data-id="{{ $row->name }}"    
                                                                    data-name="{{ $row->name }}"
                                                                    data-name-th="{{ $row->name_th }}"
                                                                    data-value="{{ $row->value }}"   
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editModal">
                                                                    <i class="bi bi-pencil-square me-1"></i> แก้ไข
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- RAG Pane -->
                                    <div class="tab-pane fade" id="subpane-rag" role="tabpanel" aria-labelledby="tab-btn-rag" tabindex="0">
                                        <div class="p-3 px-4 bg-light bg-opacity-30 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <span class="small text-muted">
                                                <i class="bi bi-info-circle-fill text-primary me-1"></i> การเชื่อมต่อ LLM Model และ Vector Embedding Model สำหรับระบบค้นหาคลังความรู้ RAG
                                            </span>
                                            <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm hover-scale" onclick="openAiSettingsModal('rag')">
                                                <i class="bi bi-gear-fill me-1"></i> ตั้งค่า AI RAG
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0 align-middle setting-table">
                                                <thead class="bg-light bg-opacity-75">
                                                    <tr>
                                                        <th class="ps-4 py-3 text-muted fw-semibold small text-uppercase" style="width: 42%;">ชื่อการตั้งค่า</th>
                                                        <th class="py-3 text-muted fw-semibold small text-uppercase">ค่าที่ตั้งไว้</th>
                                                        <th class="pe-4 py-3 text-end text-muted fw-semibold small text-uppercase" style="width: 110px;">จัดการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($ragSettings as $row)
                                                        @php 
                                                            $isSensitive = in_array($row->name, $sensitiveList);
                                                            $isBoolean = in_array($row->name, $booleanList);
                                                        @endphp
                                                        <tr class="setting-row">
                                                            <td class="ps-4 py-3">
                                                                <span class="fw-bold text-dark">{{ $row->name_th }}</span>
                                                                <div class="d-flex align-items-center gap-1 mt-0.5">
                                                                    <code class="text-muted small">{{ $row->name }}</code>
                                                                    @if($isSensitive)
                                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill py-0 px-1.5" style="font-size: 10px;">Sensitive</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td class="py-3">
                                                                @if($isBoolean)
                                                                    <span class="badge bg-{{ $row->value === 'Y' ? 'success' : 'secondary' }} rounded-pill text-white fw-bold px-3 py-1.5 shadow-xs">
                                                                        <i class="bi bi-{{ $row->value === 'Y' ? 'check-circle-fill' : 'dash-circle' }} me-1"></i>
                                                                        {{ $row->value === 'Y' ? 'เปิดใช้งาน (ON)' : 'ปิดใช้งาน (OFF)' }}
                                                                    </span>
                                                                @elseif($isSensitive)
                                                                    <div class="input-group input-group-sm" style="max-width: 280px;">
                                                                        <input type="password" class="form-control border bg-light fw-semibold sensitive-input rounded-start-pill ps-3" value="{{ $row->value }}" readonly>
                                                                        <button class="btn btn-light border border-start-0 btn-peek rounded-end-pill pe-3 text-muted" type="button" title="กดเพื่อดู/ซ่อนรหัสผ่าน">
                                                                            <i class="bi bi-eye"></i>
                                                                        </button>
                                                                    </div>
                                                                @else
                                                                    <span class="badge bg-light text-dark border p-2 px-3 rounded-pill fw-bold text-wrap text-break" style="max-width: 400px;">
                                                                        {{ $row->value ?: '-' }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="pe-4 py-3 text-end">
                                                                <button class="btn btn-warning btn-sm btn-edit rounded-pill shadow-xs hover-scale px-3" 
                                                                    data-id="{{ $row->name }}"    
                                                                    data-name="{{ $row->name }}"
                                                                    data-name-th="{{ $row->name_th }}"
                                                                    data-value="{{ $row->value }}"   
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editModal">
                                                                    <i class="bi bi-pencil-square me-1"></i> แก้ไข
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- HOSxP Pane -->
                                    <div class="tab-pane fade" id="subpane-hosxp" role="tabpanel" aria-labelledby="tab-btn-hosxp" tabindex="0">
                                        <div class="p-3 px-4 bg-light bg-opacity-30 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <span class="small text-muted">
                                                <i class="bi bi-info-circle-fill me-1" style="color: #6366f1;"></i> การเชื่อมต่อ Provider, API Key และโมเดลสำหรับตรวจสอบข้อมูลพื้นฐาน HOSxP (แพทย์, ค่ารักษา, สิทธิการรักษา)
                                            </span>
                                            <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm hover-scale" style="border-color: #6366f1; color: #6366f1;" onclick="openAiSettingsModal('hosxp')">
                                                <i class="bi bi-gear-fill me-1"></i> ตั้งค่า AI HOSxP
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0 align-middle setting-table">
                                                <thead class="bg-light bg-opacity-75">
                                                    <tr>
                                                        <th class="ps-4 py-3 text-muted fw-semibold small text-uppercase" style="width: 42%;">ชื่อการตั้งค่า</th>
                                                        <th class="py-3 text-muted fw-semibold small text-uppercase">ค่าที่ตั้งไว้</th>
                                                        <th class="pe-4 py-3 text-end text-muted fw-semibold small text-uppercase" style="width: 110px;">จัดการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($hosxpSettings as $row)
                                                        @php 
                                                            $isSensitive = in_array($row->name, $sensitiveList);
                                                            $isBoolean = in_array($row->name, $booleanList);
                                                        @endphp
                                                        <tr class="setting-row">
                                                            <td class="ps-4 py-3">
                                                                <span class="fw-bold text-dark">{{ $row->name_th }}</span>
                                                                <div class="d-flex align-items-center gap-1 mt-0.5">
                                                                    <code class="text-muted small">{{ $row->name }}</code>
                                                                    @if($isSensitive)
                                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill py-0 px-1.5" style="font-size: 10px;">Sensitive</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td class="py-3">
                                                                @if($isBoolean)
                                                                    <span class="badge bg-{{ $row->value === 'Y' ? 'success' : 'secondary' }} rounded-pill text-white fw-bold px-3 py-1.5 shadow-xs">
                                                                        <i class="bi bi-{{ $row->value === 'Y' ? 'check-circle-fill' : 'dash-circle' }} me-1"></i>
                                                                        {{ $row->value === 'Y' ? 'เปิดใช้งาน (ON)' : 'ปิดใช้งาน (OFF)' }}
                                                                    </span>
                                                                @elseif($isSensitive)
                                                                    <div class="input-group input-group-sm" style="max-width: 280px;">
                                                                        <input type="password" class="form-control border bg-light fw-semibold sensitive-input rounded-start-pill ps-3" value="{{ $row->value }}" readonly>
                                                                        <button class="btn btn-light border border-start-0 btn-peek rounded-end-pill pe-3 text-muted" type="button" title="กดเพื่อดู/ซ่อนรหัสผ่าน">
                                                                            <i class="bi bi-eye"></i>
                                                                        </button>
                                                                    </div>
                                                                @else
                                                                    <span class="badge bg-light text-dark border p-2 px-3 rounded-pill fw-bold text-wrap text-break" style="max-width: 400px;">
                                                                        {{ $row->value ?: '-' }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="pe-4 py-3 text-end">
                                                                <button class="btn btn-warning btn-sm btn-edit rounded-pill shadow-xs hover-scale px-3" 
                                                                    data-id="{{ $row->name }}"    
                                                                    data-name="{{ $row->name }}"
                                                                    data-name-th="{{ $row->name_th }}"
                                                                    data-value="{{ $row->value }}"   
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editModal">
                                                                    <i class="bi bi-pencil-square me-1"></i> แก้ไข
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Standard Layout for Other Categories --}}
                            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden setting-category-card">
                                <!-- Card Header -->
                                <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="category-header-icon bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }} rounded-3 d-flex align-items-center justify-content-center">
                                            <i class="bi {{ $meta['icon'] }} fs-4"></i>
                                        </span>
                                        <div>
                                            <h5 class="mb-0 fw-bold text-dark">{{ $category }}</h5>
                                            <small class="text-muted">{{ $meta['sub'] }} ({{ count($settings) }} รายการ)</small>
                                        </div>
                                    </div>

                                    <!-- Header Action Buttons -->
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @if($category === 'FDH Setting' || $category === 'Claim (FDH)')
                                            <button type="button" class="btn btn-outline-info btn-sm px-3 rounded-pill shadow-sm hover-scale" id="testFdhUserBtn">
                                                <i class="bi bi-shield-lock-fill me-1"></i> ทดสอบดึง Token
                                            </button>
                                        @elseif($category === 'License Setting')
                                            <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#licenseInfoModal">
                                                <i class="bi bi-info-circle-fill me-1"></i> ขอบเขตสิทธิ์การใช้งาน
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Special License Info Box (For License Setting) -->
                                @if($category === 'License Setting')
                                    @php
                                        $licInfo = \App\Services\LicenseVerificationService::getLicenseStatusInfo();
                                        $licKey = \App\Services\LicenseVerificationService::getLicenseKey();
                                    @endphp
                                    <div class="p-4 border-bottom bg-light bg-opacity-40">
                                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="small fw-bold text-muted">สถานะลิขสิทธิ์:</span>
                                                @if($licInfo['status'] === 'active')
                                                    <span class="badge bg-success rounded-pill fw-bold text-white px-3 py-1.5 shadow-xs">
                                                        <i class="bi bi-patch-check-fill me-1"></i> Active (ใช้งานได้ปกติ)
                                                    </span>
                                                @elseif($licInfo['status'] === 'expired')
                                                    <span class="badge bg-danger rounded-pill fw-bold text-white px-3 py-1.5 shadow-xs">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Expired (หมดอายุ)
                                                    </span>
                                                @elseif($licInfo['status'] === 'pending')
                                                    <span class="badge bg-warning text-dark rounded-pill fw-bold px-3 py-1.5 shadow-xs">
                                                        <i class="bi bi-hourglass-split me-1"></i> Pending (รอตรวจสอบ)
                                                    </span>
                                                @elseif($licInfo['status'] === 'not_registered')
                                                    <span class="badge bg-secondary rounded-pill fw-bold px-3 py-1.5 shadow-xs">
                                                        <i class="bi bi-slash-circle me-1"></i> Not Registered
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger rounded-pill fw-bold text-white px-3 py-1.5 shadow-xs">
                                                        <i class="bi bi-shield-slash-fill me-1"></i> Locked
                                                    </span>
                                                @endif
                                                
                                                @if($licInfo['offline'])
                                                    <span class="badge bg-info text-dark rounded-pill fw-bold px-2.5 py-1" title="โหมดออฟไลน์ชั่วคราว">
                                                        <i class="bi bi-wifi-off me-1"></i> Offline Mode
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="badge bg-light text-dark border rounded-pill fw-bold px-3 py-1.5">
                                                <i class="bi bi-hospital me-1 text-primary"></i> HCODE: {{ \App\Services\LicenseVerificationService::getHcode() }}
                                            </span>
                                        </div>
                                        <ul class="list-unstyled mb-3 text-muted small lh-lg">
                                            <li>• <b>วันหมดอายุ:</b> <span class="fw-bold text-dark">{{ \App\Services\LicenseVerificationService::formatThaiShortDate($licInfo['expires_at'] ?? '') }}</span></li>
                                            @if(!empty($licInfo['message']))
                                                <li>• <b>รายละเอียด:</b> {{ $licInfo['message'] }}</li>
                                            @endif
                                        </ul>
                                        <div class="d-flex gap-2">
                                            @if(!empty($licKey))
                                                <form action="{{ route('admin.license.verify') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm hover-scale">
                                                        <i class="bi bi-arrow-repeat me-1"></i> ตรวจสอบสิทธิ์ออนไลน์
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Settings Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle setting-table">
                                        <thead class="bg-light bg-opacity-75">
                                            <tr>
                                                <th class="ps-4 py-3 text-muted fw-semibold small text-uppercase" style="width: 42%;">ชื่อการตั้งค่า</th>
                                                <th class="py-3 text-muted fw-semibold small text-uppercase">ค่าที่ตั้งไว้</th>
                                                <th class="pe-4 py-3 text-end text-muted fw-semibold small text-uppercase" style="width: 110px;">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($settings as $row)
                                                @php 
                                                    $isSensitive = in_array($row->name, $sensitiveList);
                                                    $isBoolean = in_array($row->name, $booleanList);
                                                @endphp
                                                <tr class="setting-row">
                                                    <td class="ps-4 py-3">
                                                        <span class="fw-bold text-dark">{{ $row->name_th }}</span>
                                                        <div class="d-flex align-items-center gap-1 mt-0.5">
                                                            <code class="text-muted small">{{ $row->name }}</code>
                                                            @if($isSensitive)
                                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill py-0 px-1.5" style="font-size: 10px;">Sensitive</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="py-3">
                                                        @if($isBoolean)
                                                            <span class="badge bg-{{ $row->value === 'Y' ? 'success' : 'secondary' }} rounded-pill text-white fw-bold px-3 py-1.5 shadow-xs">
                                                                <i class="bi bi-{{ $row->value === 'Y' ? 'check-circle-fill' : 'dash-circle' }} me-1"></i>
                                                                {{ $row->value === 'Y' ? 'เปิดใช้งาน (ON)' : 'ปิดใช้งาน (OFF)' }}
                                                            </span>
                                                        @elseif($isSensitive)
                                                            <div class="input-group input-group-sm" style="max-width: 280px;">
                                                                <input type="password" class="form-control border bg-light fw-semibold sensitive-input rounded-start-pill ps-3" value="{{ $row->value }}" readonly>
                                                                <button class="btn btn-light border border-start-0 btn-peek rounded-end-pill pe-3 text-muted" type="button" title="กดเพื่อดู/ซ่อนรหัสผ่าน">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                            </div>
                                                        @else
                                                            <span class="badge bg-light text-dark border p-2 px-3 rounded-pill fw-bold text-wrap text-break" style="max-width: 400px;">
                                                                {{ $row->value ?: '-' }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="pe-4 py-3 text-end">
                                                        <button class="btn btn-warning btn-sm btn-edit rounded-pill shadow-xs hover-scale px-3" 
                                                            data-id="{{ $row->name }}"    
                                                            data-name="{{ $row->name }}"
                                                            data-name-th="{{ $row->name_th }}"
                                                            data-value="{{ $row->value }}"   
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editModal">
                                                            <i class="bi bi-pencil-square me-1"></i> แก้ไข
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                <!-- Show All Tab Pane -->
                <div class="tab-pane fade" id="pane-all" role="tabpanel" aria-labelledby="nav-tab-all" tabindex="0">
                    <div class="alert alert-primary border-0 rounded-4 shadow-sm p-3 mb-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                            <div>
                                <strong class="d-block text-primary">โหมดแสดงการตั้งค่าทั้งหมด (Show All Mode)</strong>
                                <span class="text-muted small">แสดงการ์ดทุกหมวดหมู่รวม {{ count($groupedData) }} หมวด ({{ $totalSettingsCount }} รายการ)</span>
                            </div>
                        </div>
                        <span class="badge bg-primary text-white rounded-pill px-3 py-1.5">
                            {{ $totalSettingsCount }} รายการ
                        </span>
                    </div>

                    <div class="d-flex flex-column gap-4">
                        @foreach($groupedData as $category => $settings)
                            @php
                                $meta = $categoryMeta[$category] ?? [
                                    'icon' => 'bi-gear-fill',
                                    'color' => 'primary',
                                    'sub' => 'การตั้งค่าระบบ',
                                    'slug' => 'cat-' . $loop->index
                                ];
                            @endphp
                            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden setting-category-card">
                                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <span class="setting-nav-icon bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }}">
                                            <i class="bi {{ $meta['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">{{ $category }}</h6>
                                            <small class="text-muted">{{ $meta['sub'] }}</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-muted border rounded-pill">{{ count($settings) }} รายการ</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle setting-table">
                                        <tbody>
                                            @foreach ($settings as $row)
                                                @php 
                                                    $isSensitive = in_array($row->name, $sensitiveList);
                                                    $isBoolean = in_array($row->name, $booleanList);
                                                @endphp
                                                <tr class="setting-row">
                                                    <td class="ps-4 py-2.5" style="width: 42%;">
                                                        <span class="fw-bold text-dark">{{ $row->name_th }}</span>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <code class="text-muted small">{{ $row->name }}</code>
                                                            @if($isSensitive)
                                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill py-0 px-1.5" style="font-size: 10px;">Sensitive</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5">
                                                        @if($isBoolean)
                                                            <span class="badge bg-{{ $row->value === 'Y' ? 'success' : 'secondary' }} rounded-pill text-white fw-bold px-3 py-1 shadow-xs">
                                                                {{ $row->value === 'Y' ? 'เปิดใช้งาน (ON)' : 'ปิดใช้งาน (OFF)' }}
                                                            </span>
                                                        @elseif($isSensitive)
                                                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                                                <input type="password" class="form-control border bg-light fw-semibold sensitive-input rounded-start-pill ps-3" value="{{ $row->value }}" readonly>
                                                                <button class="btn btn-light border border-start-0 btn-peek rounded-end-pill pe-3 text-muted" type="button">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                            </div>
                                                        @else
                                                            <span class="badge bg-light text-dark border p-1.5 px-3 rounded-pill fw-bold text-wrap text-break" style="max-width: 400px;">
                                                                {{ $row->value ?: '-' }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="pe-4 py-2.5 text-end" style="width: 110px;">
                                                        <button class="btn btn-warning btn-sm btn-edit rounded-pill shadow-xs hover-scale px-3" 
                                                            data-id="{{ $row->name }}"    
                                                            data-name="{{ $row->name }}"
                                                            data-name-th="{{ $row->name_th }}"
                                                            data-value="{{ $row->value }}"   
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editModal">
                                                            <i class="bi bi-pencil-square me-1"></i> แก้ไข
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="editForm" class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                @csrf @method('PUT')
                <div class="modal-header bg-warning text-dark border-0 py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <i class="bi bi-pencil-square me-2"></i> แก้ไขการตั้งค่า
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <small class="text-muted d-block mb-1">พารามิเตอร์ที่กำลังแก้ไข:</small>
                        <div class="fw-bold text-dark fs-6" id="editLabelNameTh"></div>
                    </div>
                    <div class="form-floating mb-3" id="editValueContainer">
                        <input class="form-control shadow-sm rounded-3" id="editValue" name="value" type="text" placeholder="Value" required>
                        <label for="editValue" class="fw-bold text-muted">ค่าที่ต้องการตั้ง (Value)</label>
                    </div>
                    <div class="alert alert-info border-0 shadow-sm rounded-3 small p-3 mb-0">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle-fill me-2 mt-1 fs-6"></i>
                            <div>
                                <strong class="d-block mb-1">คำแนะนำรูปแบบข้อมูล:</strong>
                                <ul class="list-unstyled mb-0 opacity-80 lh-base">
                                    <li>• <b>ตัวอักษร/รหัส:</b> ใส่เครื่องหมาย <code>""</code> ครอบตัวอักษรหากมีเครื่องหมายพิเศษ (เช่น <code>"S6"</code>)</li>
                                    <li>• <b>ตัวเลข:</b> ใส่เลขได้ทันที (เช่น <code>10989</code>, <code>8350</code>)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm hover-scale">
                        <i class="bi bi-check2-circle me-1"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- License Scope Info Modal -->
    <div class="modal fade" id="licenseInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i> ขอบเขตการอนุมัติสิทธิ์ (License Scope)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-dark text-start">
                    @php
                        $licInfo = \App\Services\LicenseVerificationService::getLicenseStatusInfo();
                        $moduleDetails = $licInfo['module_details'] ?? [];
                        $licenseType = $licInfo['license_type'] ?? 'Standard';
                        $expiresAt = $licInfo['expires_at'] ?? ($licInfo['expired_at'] ?? null);
                        $formattedExpiresAt = $expiresAt ? \App\Services\LicenseVerificationService::formatThaiShortDate($expiresAt) : 'ไม่มีกำหนด';

                        $moduleMeta = [
                            'export_ssop' => ['icon' => 'bi-file-earmark-arrow-up-fill', 'color' => 'success', 'desc' => 'ครอบคลุมการรวบรวม ตรวจสอบ และสร้างไฟล์ข้อมูลเคลมค่ารักษาพยาบาลประกันสังคม (SSOP Export)'],
                            'export_aipn' => ['icon' => 'bi-file-earmark-medical-fill', 'color' => 'success', 'desc' => 'ครอบคลุมการรวบรวม ตรวจสอบ และสร้างไฟล์ข้อมูลเคลมค่ารักษาพยาบาลประกันสังคมผู้ป่วยใน (AIPN Export)'],
                            'export_csop' => ['icon' => 'bi-file-earmark-zip-fill', 'color' => 'warning', 'desc' => 'ครอบคลุมการรวบรวม ตรวจสอบ และสร้างไฟล์ข้อมูลเคลมค่ารักษาพยาบาลสวัสดิการข้าราชการผู้ป่วยนอก (CSOP Export)'],
                            'export_cipn' => ['icon' => 'bi-file-earmark-lock-fill', 'color' => 'warning', 'desc' => 'ครอบคลุมการรวบรวม ตรวจสอบ และสร้างไฟล์ข้อมูลเคลมค่ารักษาพยาบาลสวัสดิการข้าราชการผู้ป่วยใน (CIPN Export)'],
                            'export_f16_eclaim' => ['icon' => 'bi-file-earmark-code-fill', 'color' => 'info', 'desc' => 'ครอบคลุมการส่งออกข้อมูล 16 แฟ้ม e-Claim'],
                            'export_f16_fdh' => ['icon' => 'bi-cloud-arrow-up-fill', 'color' => 'primary', 'desc' => 'ครอบคลุมการส่งออกข้อมูล 16 แฟ้ม FDH MOPH Claim'],
                            'sync_eclaim_thaid' => ['icon' => 'bi-qr-code-scan', 'color' => 'info', 'desc' => 'ระบบเชื่อมต่อและดึงข้อมูล e-Claim ผ่าน QR ThaID'],
                            'debtor_control' => ['icon' => 'bi-cash-coin', 'color' => 'purple', 'desc' => 'ระบบทะเบียนคุมลูกหนี้ และบันทึกการรับชำระหนี้ (DebtorControl)'],
                            'hosfin' => ['icon' => 'bi-graph-up-arrow', 'color' => 'success', 'desc' => 'ระบบรายงานสถานะการเงินการคลัง (HosFin)'],
                        ];
                    @endphp

                    <!-- License Summary Card -->
                    <div class="card bg-light border-0 rounded-3 mb-4 shadow-sm">
                        <div class="card-body p-3">
                            <div class="row text-center text-sm-start g-3">
                                <div class="col-sm-6">
                                    <span class="text-muted small d-block mb-1">ประเภทสิทธิ์ใช้งาน (License Type)</span>
                                    <strong class="text-primary fs-5">{{ strtoupper($licenseType) }}</strong>
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <span class="text-muted small d-block mb-1">วันหมดอายุสิทธิ์ (Expiration Date)</span>
                                    <strong class="text-danger fs-5">{{ $formattedExpiresAt }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($moduleDetails))
                        <div class="mb-2">
                            <span class="text-muted small fw-bold"><i class="bi bi-grid-fill me-1"></i> โมดูลย่อย/ฟังก์ชันเสริมพิเศษที่ได้รับสิทธิ์:</span>
                        </div>
                        <div class="list-group list-group-flush rounded-3 border mb-3">
                            @foreach($moduleDetails as $index => $module)
                                @php
                                    $code = $module['code'] ?? '';
                                    $name = $module['name'] ?? $code;
                                    $status = $module['status'] ?? 'inactive';
                                    $mExpiredAt = $module['expired_at'] ?? null;
                                    
                                    $meta = $moduleMeta[$code] ?? ['icon' => 'bi-shield-check', 'color' => 'secondary', 'desc' => 'ระบบงานในโปรแกรม RiMS'];
                                    $colorClass = $meta['color'] === 'purple' ? 'text-primary bg-primary bg-opacity-10' : 'text-'.$meta['color'].' bg-'.$meta['color'].' bg-opacity-10';
                                @endphp
                                <div class="list-group-item d-flex align-items-center justify-content-between gap-3 py-3 {{ $index < count($moduleDetails) - 1 ? 'border-bottom' : '' }}">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle p-2 d-flex {{ $colorClass }}">
                                            <i class="{{ $meta['icon'] }} fs-5"></i>
                                        </div>
                                        <div>
                                            <strong class="d-block text-dark">{{ $name }}</strong>
                                            <span class="text-muted small">{{ $meta['desc'] }}</span>
                                        </div>
                                    </div>
                                    <div class="text-end shrink-0">
                                        @if($status === 'active')
                                            <span class="badge bg-success rounded-pill fw-bold text-white px-2.5 py-1 mb-1 d-inline-block">Active</span>
                                        @else
                                            <span class="badge bg-danger rounded-pill fw-bold text-white px-2.5 py-1 mb-1 d-inline-block">Inactive</span>
                                        @endif
                                        @if($mExpiredAt)
                                            <span class="d-block text-muted small" style="font-size: 0.75rem;">หมดอายุ: {{ \App\Services\LicenseVerificationService::formatThaiShortDate($mExpiredAt) }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- No sub-modules message -->
                        <div class="alert alert-info border-0 rounded-3 mb-3 d-flex align-items-center gap-2 py-3">
                            <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                            <div class="text-dark small">
                                ใช้งานฟังก์ชันมาตรฐานทั่วไปของระบบ RiMS (ไม่มีโมดูลย่อยเพิ่มเติม)
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-warning border-0 small mb-0 rounded-3 mt-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        หากคีย์ลิขสิทธิ์หมดอายุหรือถูกปิดใช้งาน ระบบจะระงับการทำงานชั่วคราวจนกว่าจะได้รับการเปิดสิทธิ์อนุมัติใหม่
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Styles -->
<style>
    /* Hover & Micro-interactions */
    .hover-scale {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-scale:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
    }
    .shadow-xs {
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    /* Sticky Sidebar Navigation */
    @media (min-width: 992px) {
        .sticky-setting-sidebar {
            position: sticky;
            top: 85px;
            z-index: 10;
            max-height: calc(100vh - 105px);
            display: flex;
            flex-direction: column;
        }
        .setting-nav-scroll {
            overflow-y: auto;
            flex: 1 1 auto;
        }
        .setting-nav-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .setting-nav-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .setting-nav-scroll::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 4px;
        }
        .setting-nav-scroll::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    }

    /* Navigation Buttons */
    .setting-nav-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.65rem 0.85rem;
        margin-bottom: 0.35rem;
        border: none;
        background: transparent;
        border-radius: 0.75rem;
        text-align: left;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        color: #334155;
    }

    .setting-nav-btn:hover {
        background-color: #f1f5f9;
        color: #0f172a;
        transform: translateX(3px);
    }

    .setting-nav-btn.active {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(13, 110, 253, 0.3);
    }

    .setting-nav-btn.active .setting-nav-icon {
        background-color: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
    }

    .setting-nav-btn.active .setting-nav-title {
        color: #ffffff !important;
        font-weight: 700;
    }

    .setting-nav-btn.active .setting-nav-sub {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .setting-nav-btn.active .setting-nav-badge {
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        border: none !important;
    }

    .setting-nav-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.05rem;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .category-header-icon {
        width: 48px;
        height: 48px;
        flex-shrink: 0;
    }

    .setting-nav-title {
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.25;
        color: #1e293b;
    }

    .setting-nav-sub {
        font-size: 0.725rem;
        color: #64748b;
        margin-top: 2px;
        line-height: 1.2;
    }

    .setting-nav-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.3em 0.65em;
        background-color: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    /* Inner Sub-Tabs for RiMS Copilot */
    .custom-inner-tabs .nav-link {
        border: none;
        background: transparent;
        color: #64748b;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
    }
    .custom-inner-tabs .nav-link:hover {
        color: #0f172a;
        background: rgba(0, 0, 0, 0.03);
    }
    .custom-inner-tabs .nav-link.active {
        color: #0d6efd;
        background: #ffffff;
        border-bottom: 3px solid #0d6efd;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
    }

    /* Table Styling */
    .setting-table th {
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    .setting-table td {
        border-color: #f1f5f9;
    }
    .setting-table tbody tr:last-child td {
        border-bottom: none;
    }
    .setting-table tbody tr {
        transition: background-color 0.15s ease;
    }
    .setting-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .sensitive-input {
        letter-spacing: 2px;
    }
    .btn-peek:hover {
        background-color: #e2e8f0;
    }

    .status-indicator-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .text-xs {
        font-size: 0.75rem;
    }
    .tracking-wider {
        letter-spacing: 0.05em;
    }

    /* Subtly colored badges */
    .bg-indigo-subtle { background-color: #e0e7ff; }
    .text-indigo { color: #4338ca; }
    .bg-purple-subtle { background-color: #f3e8ff; }
    .text-purple { color: #7e22ce; }
    .bg-teal-subtle { background-color: #ccfbf1; }
    .text-teal { color: #0f766e; }
</style>

<!-- Scripts -->
@push('scripts')
<script>
    // Tab Persistence via URL Hash & LocalStorage
    document.addEventListener('DOMContentLoaded', function () {
        function activateSettingTab(btn) {
            if (!btn) return;

            // 1. Manual class toggling to guarantee the pane displays even if Bootstrap Tab event delegation fails
            const targetSelector = btn.getAttribute('data-bs-target');
            const targetPane = targetSelector ? document.querySelector(targetSelector) : null;
            if (targetPane) {
                document.querySelectorAll('#settingTabs .setting-nav-btn').forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });
                document.querySelectorAll('#settingTabsContent > .tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-selected', 'true');
                targetPane.classList.add('show', 'active');
            }

            // 2. Also trigger bootstrap.Tab if available
            if (window.bootstrap && bootstrap.Tab) {
                try {
                    bootstrap.Tab.getOrCreateInstance(btn).show();
                } catch (e) {}
            }

            const slug = btn.getAttribute('data-slug');
            if (slug) {
                localStorage.setItem('rims_setting_active_tab', slug);
                history.replaceState(null, null, '#' + slug);
            }
        }

        // Listen for tab click
        const tabButtons = document.querySelectorAll('.setting-nav-btn[data-bs-toggle="pill"]');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                activateSettingTab(this);
            });
        });

        const hash = window.location.hash.replace('#', '');
        const targetSlug = hash || 'basic-info';

        if (targetSlug) {
            const targetBtn = document.querySelector(`.setting-nav-btn[data-slug="${targetSlug}"]`)
                           || document.querySelector('.setting-nav-btn[data-bs-toggle="pill"]');
            if (targetBtn) {
                activateSettingTab(targetBtn);
            }
        }

        // Handle hashchange event (e.g. clicking Main Setting from top navbar while on page)
        window.addEventListener('hashchange', function () {
            const newHash = window.location.hash.replace('#', '') || 'basic-info';
            const targetBtn = document.querySelector(`.setting-nav-btn[data-slug="${newHash}"]`)
                           || document.querySelector('.setting-nav-btn[data-bs-toggle="pill"]');
            if (targetBtn) {
                activateSettingTab(targetBtn);
            }
        });
    });

    // Global Search Across All Categories
    const allSettingsList = @json($allSettingsFlat);
    const globalSearchInput = document.getElementById('globalSettingSearchInput');
    const clearGlobalSearchBtn = document.getElementById('clearGlobalSearchBtn');
    const globalSearchResultsWrapper = document.getElementById('globalSearchResultsWrapper');
    const settingTabsContent = document.getElementById('settingTabsContent');
    const globalSearchResultsTbody = document.getElementById('globalSearchResultsTbody');
    const globalSearchNoResults = document.getElementById('globalSearchNoResults');
    const searchKeywordDisplay = document.getElementById('searchKeywordDisplay');
    const searchMatchCount = document.getElementById('searchMatchCount');
    const closeSearchResultsBtn = document.getElementById('closeSearchResultsBtn');

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function performGlobalSearch(query) {
        query = (query || '').trim().toLowerCase();

        if (!query) {
            globalSearchResultsWrapper.style.display = 'none';
            settingTabsContent.style.display = 'block';
            clearGlobalSearchBtn.style.display = 'none';
            return;
        }

        clearGlobalSearchBtn.style.display = 'inline-block';
        globalSearchResultsWrapper.style.display = 'block';
        settingTabsContent.style.display = 'none';
        searchKeywordDisplay.innerText = `"${query}"`;

        const matches = allSettingsList.filter(item => {
            const name = (item.name || '').toLowerCase();
            const nameTh = (item.name_th || '').toLowerCase();
            const cat = (item.category || '').toLowerCase();
            const val = (item.value || '').toLowerCase();
            return name.includes(query) || nameTh.includes(query) || cat.includes(query) || val.includes(query);
        });

        searchMatchCount.innerText = matches.length;
        globalSearchResultsTbody.innerHTML = '';

        if (matches.length === 0) {
            globalSearchNoResults.style.display = 'block';
            globalSearchResultsTbody.parentElement.style.display = 'none';
        } else {
            globalSearchNoResults.style.display = 'none';
            globalSearchResultsTbody.parentElement.style.display = 'table';

            matches.forEach(item => {
                const tr = document.createElement('tr');
                tr.className = 'setting-row';

                let valHtml = '';
                if (item.is_boolean) {
                    const isOn = item.value === 'Y';
                    valHtml = `
                        <span class="badge bg-${isOn ? 'success' : 'secondary'} rounded-pill text-white fw-bold px-3 py-1.5 shadow-xs">
                            <i class="bi bi-${isOn ? 'check-circle-fill' : 'dash-circle'} me-1"></i>
                            ${isOn ? 'เปิดใช้งาน (ON)' : 'ปิดใช้งาน (OFF)'}
                        </span>
                    `;
                } else if (item.is_sensitive) {
                    valHtml = `
                        <div class="input-group input-group-sm" style="max-width: 280px;">
                            <input type="password" class="form-control border bg-light fw-semibold sensitive-input rounded-start-pill ps-3" value="${escapeHtml(item.value || '')}" readonly>
                            <button class="btn btn-light border border-start-0 btn-peek rounded-end-pill pe-3 text-muted" type="button" title="กดเพื่อดู/ซ่อนรหัสผ่าน">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    `;
                } else {
                    valHtml = `
                        <span class="badge bg-light text-dark border p-2 px-3 rounded-pill fw-bold text-wrap text-break" style="max-width: 400px;">
                            ${escapeHtml(item.value || '-')}
                        </span>
                    `;
                }

                tr.innerHTML = `
                    <td class="ps-4 py-3">
                        <span class="badge bg-${item.category_color}-subtle text-${item.category_color} border border-${item.category_color}-subtle rounded-pill px-2.5 py-1 fw-semibold text-truncate d-inline-flex align-items-center gap-1" style="max-width: 200px;">
                            <i class="bi ${item.category_icon}"></i>
                            <span class="text-truncate">${escapeHtml(item.category)}</span>
                        </span>
                    </td>
                    <td class="py-3">
                        <span class="fw-bold text-dark">${escapeHtml(item.name_th)}</span>
                        <div class="d-flex align-items-center gap-1 mt-0.5">
                            <code class="text-muted small">${escapeHtml(item.name)}</code>
                            ${item.is_sensitive ? '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill py-0 px-1.5" style="font-size: 10px;">Sensitive</span>' : ''}
                        </div>
                    </td>
                    <td class="py-3">${valHtml}</td>
                    <td class="pe-4 py-3 text-end">
                        <button class="btn btn-warning btn-sm btn-edit rounded-pill shadow-xs hover-scale px-3" 
                            data-id="${escapeHtml(item.name)}"    
                            data-name="${escapeHtml(item.name)}"
                            data-name-th="${escapeHtml(item.name_th)}"
                            data-value="${escapeHtml(item.value || '')}"   
                            data-bs-toggle="modal"
                            data-bs-target="#editModal">
                            <i class="bi bi-pencil-square me-1"></i> แก้ไข
                        </button>
                    </td>
                `;
                globalSearchResultsTbody.appendChild(tr);
            });

            // Re-bind Peek and Edit events for search rows
            bindPeekButtons(globalSearchResultsTbody);
            bindEditButtons(globalSearchResultsTbody);
        }
    }

    if (globalSearchInput) {
        globalSearchInput.addEventListener('input', function () {
            performGlobalSearch(this.value);
        });

        clearGlobalSearchBtn.addEventListener('click', function () {
            globalSearchInput.value = '';
            performGlobalSearch('');
            globalSearchInput.focus();
        });

        closeSearchResultsBtn.addEventListener('click', function () {
            globalSearchInput.value = '';
            performGlobalSearch('');
        });
    }

    // Copy to Clipboard Helper
    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกสำเร็จ',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
        });
    }

    // Peek Password / Secret Functionality
    function bindPeekButtons(context = document) {
        context.querySelectorAll('.btn-peek').forEach(btn => {
            if (btn.dataset.peekBound) return;
            btn.dataset.peekBound = "true";
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = "password";
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });
    }
    bindPeekButtons();

    // Modal Edit Data Binding
    function bindEditButtons(context = document) {
        context.querySelectorAll('.btn-edit').forEach(button => {
            if (button.dataset.editBound) return;
            button.dataset.editBound = "true";
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const nameTh = this.dataset.nameTh;
                let value = this.dataset.value;    
                
                document.getElementById('editLabelNameTh').innerHTML = `<span class="text-primary fw-bold">${nameTh}</span> <code class="ms-1 text-muted">(${name})</code>`;
                
                const valueContainer = document.getElementById('editValueContainer');
                if (name === 'provider_id_active' || name === 'moph_alert_active' || name === 'ai_active') {
                    valueContainer.innerHTML = `
                        <select class="form-select shadow-sm fw-bold text-success rounded-3" id="editValue" name="value" required style="height: 58px; padding-top: 1.625rem;">
                            <option value="Y" ${value === 'Y' ? 'selected' : ''}>Y - เปิดใช้งาน (ON)</option>
                            <option value="N" ${value === 'N' ? 'selected' : ''}>N - ปิดใช้งาน (OFF)</option>
                        </select>
                        <label for="editValue" class="fw-bold text-muted">สถานะการเปิดใช้งาน</label>
                    `;
                } else {
                    valueContainer.innerHTML = `
                        <input class="form-control shadow-sm rounded-3" id="editValue" name="value" type="text" value="${value !== undefined && value !== null ? value : ''}" placeholder="Value">
                        <label for="editValue" class="fw-bold text-muted">ค่าที่ต้องการตั้ง (Value)</label>
                    `;
                }
                
                document.getElementById('editForm').action = "{{ url('admin/main_setting') }}/" + name;
            });
        });
    }
    bindEditButtons();

    // Git Pull Logic
    const gitPullBtn = document.getElementById('gitPullBtn');
    if (gitPullBtn) {
        gitPullBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'ต้องการ Git Pull ใช่ไหม?',
                text: "ระบบจะทำการอัปเดตโค้ดล่าสุดจาก Server",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#d33',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังอัปเดต...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch("{{ route('admin.git.pull') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        const outputText = data.output || data.error || 'ไม่มีข้อมูล';
                        const isSuccess = data.output && (data.output.includes('Updating') || data.output.includes('Already up to date'));
                        
                        Swal.fire({
                            icon: isSuccess ? 'success' : 'info',
                            title: isSuccess ? 'Git Pull สำเร็จ' : 'Git Pull Finished',
                            text: isSuccess ? 'ระบบทำการดึงข้อมูลโค้ดล่าสุดจาก Server เรียบร้อยแล้ว' : 'กรุณาตรวจสอบผลการทำงาน',
                            footer: `<button class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm" onclick="showGitDetail()">ดูรายละเอียด Git Output</button>`,
                            showConfirmButton: true,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#0a4d2c'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });

                        window.showGitDetail = function() {
                            Swal.fire({
                                title: 'Git Pull Output',
                                html: '<pre class="text-start bg-light p-3 small border rounded-3" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;">' + outputText + '</pre>',
                                width: '800px',
                                confirmButtonText: 'ปิด',
                                confirmButtonColor: '#6c757d'
                            }).then(() => {
                                if (isSuccess) {
                                    window.location.reload();
                                }
                            });
                        };
                    })
                    .catch(error => {
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error });
                    });
                }
            });
        });
    }

    // Test FDH User Token Logic
    const testFdhUserBtn = document.getElementById('testFdhUserBtn');
    if (testFdhUserBtn) {
        testFdhUserBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'กำลังทดสอบการเชื่อมต่อ...',
                text: 'กรุณารอสักครู่ ระบบกำลังขอ Token จาก FDH',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('{{ url("api/fdh/testtoken") }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.token) {
                    Swal.fire({
                        icon: 'success',
                        title: 'เชื่อมต่อสำเร็จ',
                        html: `
                            <div class="text-start p-2">
                                <p class="mb-2 text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ดึง Access Token สำเร็จ</p>
                                <div class="bg-light p-3 small border rounded-3" style="word-break: break-all; font-family: monospace; max-height: 150px; overflow-y: auto;">
                                    ${data.token}
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#0a4d2c'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์เพื่อทดสอบ Token ได้'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message || error,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#d33'
                });
            });
        });
    }

    // Upgrade Structure Logic
    async function confirmAction(event) {
        event.preventDefault();
        
        const steps = [
            { num: 1, name: "ขั้นตอนที่ 1/3: ตรวจสอบและอัปเกรดโครงสร้างตารางระบบทั้งหมด" },
            { num: 2, name: "ขั้นตอนที่ 2/3: นำเข้า/ซิงค์ข้อมูล Lookup (EquipdevAIPN, adp_type, adp_code, subinscl)" },
            { num: 3, name: "ขั้นตอนที่ 3/3: ซิงค์ข้อมูลตั้งค่าหลัก (main_setting)" }
        ];

        const { isConfirmed } = await Swal.fire({
            title: 'อัปเกรดโครงสร้างฐานข้อมูล?',
            text: "คุณต้องการดึงข้อมูลมาตรฐานมาตรวจสอบและอัปเดตระบบหรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ดำเนินการ!',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        });

        if (!isConfirmed) return;

        Swal.fire({
            title: 'กำลังอัปเกรดโครงสร้างฐานข้อมูล...',
            html: `
                <div id="upgrade-progress-text" class="mb-2 text-start small text-muted">กำลังเตรียมขั้นตอนการอัปเกรด...</div>
                <div class="progress" style="height: 25px;">
                    <div id="upgrade-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div id="upgrade-details-log" class="mt-3 text-start small bg-light p-2 border rounded-3" style="max-height: 150px; overflow-y: auto; font-family: monospace; font-size: 11px; line-height: 1.4;"></div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const logDiv = document.getElementById('upgrade-details-log');
        const progressText = document.getElementById('upgrade-progress-text');
        const progressBar = document.getElementById('upgrade-progress-bar');
        
        let detailsOutput = [];
        
        for (let i = 0; i < steps.length; i++) {
            const step = steps[i];
            const percent = Math.round((i / steps.length) * 100);

            if (progressText) progressText.innerText = step.name;
            if (progressBar) {
                progressBar.style.width = `${percent}%`;
                progressBar.innerHTML = `${percent}%`;
                progressBar.setAttribute('aria-valuenow', percent);
            }
            if (logDiv) {
                logDiv.innerHTML += `<div>🚀 เริ่มต้น ${step.name}...</div>`;
                logDiv.scrollTop = logDiv.scrollHeight;
            }

            try {
                let response = await fetch("{{ route('admin.up_structure') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ step: step.num })
                });

                let rawText = await response.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (parseErr) {
                    throw new Error(`เซิร์ฟเวอร์ตอบกลับผิดพลาด (HTTP ${response.status}): ${rawText.substring(0, 150)}`);
                }
                
                if (data.success) {
                    if (logDiv) {
                        logDiv.innerHTML += `<div class="text-success" style="margin-left: 10px; margin-bottom: 5px;">✔ ${data.message}</div>`;
                        logDiv.scrollTop = logDiv.scrollHeight;
                    }
                    detailsOutput.push(data.message);
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาดในการประมวลผล');
                }
            } catch (error) {
                if (logDiv) {
                    logDiv.innerHTML += `<div class="text-danger" style="margin-left: 10px; margin-bottom: 5px;">❌ ล้มเหลว: ${error.message}</div>`;
                    logDiv.scrollTop = logDiv.scrollHeight;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการอัปเกรด!',
                    text: error.message || error,
                    confirmButtonText: 'ตกลง'
                });
                return;
            }
        }

        if (progressBar) {
            progressBar.style.width = '100%';
            progressBar.innerHTML = '100%';
            progressBar.setAttribute('aria-valuenow', 100);
            progressBar.classList.replace('bg-primary', 'bg-success');
        }
        if (progressText) progressText.innerText = 'อัปเกรดโครงสร้างฐานข้อมูลเสร็จสมบูรณ์!';

        Swal.fire({
            icon: 'success',
            title: 'อัปเกรดโครงสร้างเสร็จสิ้น!',
            html: `
                <div class="text-start p-2" style="max-height: 250px; overflow-y: auto;">
                    <p class="fw-bold mb-2 text-success">การดำเนินการทุกขั้นตอนสำเร็จเรียบร้อย:</p>
                    <ul class="mb-0 small text-muted" style="padding-left: 15px;">
                        ${detailsOutput.map(d => `<li>${d}</li>`).join('')}
                    </ul>
                </div>
            `,
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#0a4d2c'
        }).then(() => {
            window.location.reload();
        });
    }

    @if(session('migrate_output'))
        $(document).ready(function() {
            Swal.fire({
                icon: 'success',
                title: 'อัปเกรดโครงสร้างสำเร็จ!',
                text: {!! json_encode(session('success')) !!},
                footer: '<button class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm" onclick="showMigrateOutput()">ดูรายละเอียดการอัปเกรด</button>',
                showConfirmButton: true,
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#0a4d2c'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('admin.main_setting') }}";
                }
            });
        });

        window.showMigrateOutput = function() {
            const output = {!! json_encode(session('migrate_output')) !!};
            Swal.fire({
                title: 'รายละเอียดการอัปเกรด',
                html: '<pre class="text-start bg-light p-3 small border rounded-3" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;">' + output + '</pre>',
                width: '800px',
                confirmButtonText: 'ปิด',
                confirmButtonColor: '#6c757d'
            }).then(() => {
                window.location.href = "{{ route('admin.main_setting') }}";
            });
        }
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด!',
            text: {!! json_encode(session('error')) !!},
            confirmButtonText: 'ตกลง'
        });
    @endif
</script>
@endpush
@endsection