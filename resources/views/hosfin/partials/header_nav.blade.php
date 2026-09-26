@php
    $currentBudgetYear = isset($budgetYear) ? $budgetYear : (\App\Http\Controllers\HosFinController::getCurrentBudgetYear() ?? date('Y') + 543);
    $yearParam = '?budget_year=' . $currentBudgetYear;
    
    $isDashboardActive = request()->is('hosfin') || request()->is('hosfin/index');
    $isGlActive = request()->is('hosfin/cash_register*') || request()->is('hosfin/ap_report*') || request()->is('hosfin/ar_report*') || request()->is('hosfin/cost_report*');
    $isTbActive = request()->is('hosfin/trial_balance*') || request()->is('hosfin/ratio_report*') || request()->is('hosfin/mappings*');
    $isPlanfinActive = request()->is('hosfin/planfin*');
    $isReportsActive = request()->is('hosfin/reports*');
@endphp

<div class="d-flex align-items-center gap-2 flex-wrap ms-auto hosfin-nav-group">
    {{-- 1. AI วิเคราะห์ (แสดงเฉพาะในหน้าที่มีสิทธิ์และฟังก์ชัน AI) --}}
    @if(isset($showAiButton) && $showAiButton)
        @if(\App\Services\LicenseVerificationService::isModuleLicensed('ai_knowledge') && \App\Services\Ai\AiService::isActive())
            @php
                $hasAiAccess = Auth::check() && (Auth::user()->status === 'admin' || Auth::user()->allow_ai_copilot === 'Y');
            @endphp
            <button type="button" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm text-white hosfin-ai-btn" 
                    onclick="{{ $hasAiAccess ? ($aiModalTrigger ?? 'openHosFinAiModal()') : 'showAiAccessDeniedAlert()' }}"
                    style="font-size: 0.84rem; height: 38px; font-weight: 700; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;"
                    title="{{ $hasAiAccess ? 'คลิกเพื่อดูบทวิเคราะห์ด้วย AI' : 'คุณไม่ได้รับสิทธิ์ใช้งาน AI' }}">
                <i class="bi bi-robot"></i> AI วิเคราะห์
            </button>
        @endif
    @endif

    {{-- 2. Dropdown: บัญชี GL --}}
    <div class="dropdown">
        <button class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm dropdown-toggle fw-bold hosfin-nav-pill {{ $isGlActive ? 'active-gl' : '' }}" 
                type="button" id="dropdownGlMenu" data-bs-toggle="dropdown" aria-expanded="false"
                style="font-size: 0.84rem; height: 38px; transition: all 0.25s ease;">
            <i class="bi bi-journal-bookmark-fill text-success"></i> บัญชี GL
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-1 hosfin-dropdown-menu" aria-labelledby="dropdownGlMenu" style="min-width: 250px; z-index: 1050;">
            <li>
                <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-2.5 {{ request()->is('hosfin/cash_register*') ? 'active bg-success text-white' : '' }}" 
                   href="{{ url('hosfin/cash_register') }}{{ $yearParam }}">
                    <div class="p-1.5 rounded-3 {{ request()->is('hosfin/cash_register*') ? 'bg-white bg-opacity-25 text-white' : 'bg-success bg-opacity-10 text-success' }}" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-cash-stack fs-6"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.86rem;">รับ-จ่าย (Cash)</div>
                        <small class="{{ request()->is('hosfin/cash_register*') ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.72rem;">ทะเบียนเงินสดและเงินฝากธนาคาร</small>
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider my-1 opacity-50"></li>
            <li>
                <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-2.5 {{ request()->is('hosfin/ap_report*') ? 'active bg-danger text-white' : '' }}" 
                   href="{{ url('hosfin/ap_report') }}{{ $yearParam }}">
                    <div class="p-1.5 rounded-3 {{ request()->is('hosfin/ap_report*') ? 'bg-white bg-opacity-25 text-white' : 'bg-danger bg-opacity-10 text-danger' }}" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-receipt-cutoff fs-6"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.86rem;">เจ้าหนี้ (AP)</div>
                        <small class="{{ request()->is('hosfin/ap_report*') ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.72rem;">รายงานเจ้าหนี้การค้าและบิลค้างชำระ</small>
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider my-1 opacity-50"></li>
            <li>
                <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-2.5 {{ request()->is('hosfin/ar_report*') ? 'active bg-info text-white' : '' }}" 
                   href="{{ url('hosfin/ar_report') }}{{ $yearParam }}">
                    <div class="p-1.5 rounded-3 {{ request()->is('hosfin/ar_report*') ? 'bg-white bg-opacity-25 text-white' : 'bg-info bg-opacity-10 text-info' }}" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-wallet2 fs-6"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.86rem;">ลูกหนี้ (AR)</div>
                        <small class="{{ request()->is('hosfin/ar_report*') ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.72rem;">รายงานลูกหนี้ค่ารักษาแยกตามสิทธิ</small>
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider my-1 opacity-50"></li>
            <li>
                <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-2.5 {{ request()->is('hosfin/cost_report*') ? 'active bg-warning text-dark' : '' }}" 
                   href="{{ url('hosfin/cost_report') }}{{ $yearParam }}">
                    <div class="p-1.5 rounded-3 {{ request()->is('hosfin/cost_report*') ? 'bg-dark bg-opacity-10 text-dark' : 'bg-warning bg-opacity-10 text-warning' }}" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-pie-chart fs-6"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.86rem;">ต้นทุน (LC/MC/CC)</div>
                        <small class="{{ request()->is('hosfin/cost_report*') ? 'text-dark-50' : 'text-muted' }}" style="font-size: 0.72rem;">รายงานวิเคราะห์ต้นทุนบริการ</small>
                    </div>
                </a>
            </li>
        </ul>
    </div>

    {{-- 4. Dropdown: งบทดลอง --}}
    <div class="dropdown">
        <button class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm dropdown-toggle fw-bold hosfin-nav-pill {{ $isTbActive ? 'active-tb' : '' }}" 
                type="button" id="dropdownTbMenu" data-bs-toggle="dropdown" aria-expanded="false"
                style="font-size: 0.84rem; height: 38px; transition: all 0.25s ease;">
            <i class="bi bi-file-earmark-spreadsheet text-teal" style="color: #0d9488;"></i> งบทดลอง
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-1 hosfin-dropdown-menu" aria-labelledby="dropdownTbMenu" style="min-width: 250px; z-index: 1050;">
            <li>
                <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-2.5 {{ request()->is('hosfin/trial_balance*') ? 'active bg-teal text-white' : '' }}" 
                   href="{{ url('hosfin/trial_balance') }}{{ $yearParam }}" style="{{ request()->is('hosfin/trial_balance*') ? 'background-color: #0d9488 !important;' : '' }}">
                    <div class="p-1.5 rounded-3 {{ request()->is('hosfin/trial_balance*') ? 'bg-white bg-opacity-25 text-white' : 'bg-success bg-opacity-10 text-success' }}" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-file-earmark-spreadsheet fs-6"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.86rem;">รายงานงบทดลอง</div>
                        <small class="{{ request()->is('hosfin/trial_balance*') ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.72rem;">นำเข้าไฟล์และดูงบทดลองรายเดือน</small>
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider my-1 opacity-50"></li>
            <li>
                <a class="dropdown-item rounded-3 py-2 px-3 d-flex align-items-center gap-2.5 {{ request()->is('hosfin/ratio_report*') ? 'active bg-primary text-white' : '' }}" 
                   href="{{ url('hosfin/ratio_report') }}{{ $yearParam }}">
                    <div class="p-1.5 rounded-3 {{ request()->is('hosfin/ratio_report*') ? 'bg-white bg-opacity-25 text-white' : 'bg-primary bg-opacity-10 text-primary' }}" style="width: 32px; height: 32px; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-graph-up-arrow fs-6"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.86rem;">อัตราส่วนการเงิน 7 ดัชนี</div>
                        <small class="{{ request()->is('hosfin/ratio_report*') ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.72rem;">วิเคราะห์สภาพคล่องและดัชนีชี้วัด</small>
                    </div>
                </a>
            </li>
        </ul>
    </div>

    {{-- 5. ปุ่ม PlanFin (ปุ่มตรง) --}}
    <a href="{{ url('hosfin/planfin') }}{{ $yearParam }}" 
       class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm fw-bold hosfin-nav-pill {{ $isPlanfinActive ? 'active-planfin' : '' }}" 
       style="font-size: 0.84rem; height: 38px; transition: all 0.25s ease;"
       title="ระบบบริหารและติดตามแผนเงินบำรุง (PlanFin)">
        <i class="bi bi-graph-up text-indigo" style="color: #6366f1;"></i> PlanFin
    </a>

    {{-- 6. ปุ่ม รายงาน (ปุ่มตรง) --}}
    <a href="{{ url('hosfin/reports') }}{{ $yearParam }}" 
       class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm fw-bold hosfin-nav-pill {{ $isReportsActive ? 'active-reports' : '' }}" 
       style="font-size: 0.84rem; height: 38px; transition: all 0.25s ease;"
       title="ศูนย์รวมรายงานการเงินและข้อมูลบริการ (Reports Hub)">
        <i class="bi bi-file-earmark-bar-graph text-purple" style="color: #8b5cf6;"></i> รายงาน
    </a>
</div>

<style>
.hosfin-nav-pill {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    color: #475569;
}
.hosfin-nav-pill:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #1e293b;
}
.hosfin-nav-pill.active-dashboard {
    background: #eff6ff !important;
    border-color: #3b82f6 !important;
    color: #1d4ed8 !important;
}
.hosfin-nav-pill.active-gl {
    background: #f0fdf4 !important;
    border-color: #059669 !important;
    color: #059669 !important;
}
.hosfin-nav-pill.active-tb {
    background: #f0fdfa !important;
    border-color: #0d9488 !important;
    color: #0d9488 !important;
}
.hosfin-nav-pill.active-planfin {
    background: #eef2ff !important;
    border-color: #6366f1 !important;
    color: #4f46e5 !important;
}
.hosfin-nav-pill.active-reports {
    background: #f5f3ff !important;
    border-color: #8b5cf6 !important;
    color: #7c3aed !important;
}
.hosfin-dropdown-menu {
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
    animation: fadeInDown 0.15s ease-out;
}
.hosfin-dropdown-menu .dropdown-item {
    transition: all 0.15s ease-in-out;
}
.hosfin-dropdown-menu .dropdown-item:hover:not(.active) {
    background-color: #f8fafc;
}
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
