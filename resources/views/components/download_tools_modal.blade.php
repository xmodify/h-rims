@php
    $glHospcode = \App\Models\MainSetting::where('name', 'hospital_code')->value('value')
                  ?: \Illuminate\Support\Facades\DB::table('lookup_hospcode')->value('hospcode')
                  ?: '';
    $glHospname = \App\Models\MainSetting::where('name', 'hospital_name')->value('value')
                  ?: \Illuminate\Support\Facades\DB::table('lookup_hospcode')->where('hospcode', $glHospcode)->value('hospcode_name')
                  ?: 'โรงพยาบาล';
    $glExpectedToken = $glHospcode ? ('rims-gl-' . $glHospcode . '-token-2569') : 'rims-gl-token-2569-secret';
    $glCustomToken = config('services.gl_sync.token', env('GL_SYNC_TOKEN'));
    if ($glCustomToken && !in_array($glCustomToken, ['rims-gl-token-2569-secret', ''])) {
        $glExpectedToken = $glCustomToken;
    }
@endphp

<!-- Global Download Tools Modal -->
<div class="modal fade" id="downloadToolsModal" tabindex="-1" aria-labelledby="downloadToolsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-10 p-2.5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-cloud-arrow-down-fill fs-4 text-info"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="downloadToolsModalLabel">
                            ศูนย์ดาวน์โหลดโปรแกรมและส่วนเสริม (Download Center)
                        </h5>
                        <small class="text-white-50">โปรแกรมสนับสนุนและส่วนขยายเพื่อเพิ่มประสิทธิภาพการทำงานของระบบ RiMS</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 p-md-4 bg-light">
                <div class="row g-3">
                    <!-- Tool 1: GL Sync Agent -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 border-start border-4 border-primary">
                            <!-- Top: Header + Badges -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                        <i class="bi bi-windows fs-3"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h6 class="fw-bold text-dark mb-0 fs-6">Rims GL Sync</h6>
                                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-0.5" style="font-size: 0.70rem;"><i class="bi bi-windows me-1"></i>โปรแกรม Windows (.exe)</span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill" style="font-size: 0.70rem;">v2.0</span>
                                            @if($glHospcode)
                                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill" style="font-size: 0.70rem;"><i class="bi bi-hospital me-1"></i> ประจำ: {{ $glHospname }} ({{ $glHospcode }})</span>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">เชื่อมต่อและส่งข้อมูลบัญชี MS Access (.accdb) เข้าสู่ระบบ HosFin Dashboard อัตโนมัติ ปลอดภัยด้วย Token ประจำ รพ.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- API Config Box: Separate Rows so URL and Token are full width -->
                            <div class="p-2.5 rounded-3 bg-light border">
                                <div class="mb-2">
                                    <label class="form-label text-muted small mb-0.5 fw-bold d-flex align-items-center gap-1" style="font-size: 0.74rem;">
                                        <i class="bi bi-hdd-network text-primary"></i> Server API URL:
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-muted py-0.5 px-2"><i class="bi bi-link-45deg"></i></span>
                                        <input type="text" class="form-control font-monospace bg-white text-primary fw-bold py-1" id="modalGlSyncApiUrl" 
                                               value="{{ url('api/hosfin/gl/sync') }}" readonly style="font-size: 0.82rem;">
                                        <button class="btn btn-outline-primary px-3 py-1 fw-bold d-flex align-items-center gap-1" type="button" 
                                                onclick="copyModalText('modalGlSyncApiUrl', 'คัดลอก API URL สำเร็จ!')" style="font-size: 0.76rem;">
                                            <i class="bi bi-clipboard"></i> คัดลอก
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label text-muted small mb-0.5 fw-bold d-flex align-items-center justify-content-between" style="font-size: 0.74rem;">
                                        <span><i class="bi bi-key-fill text-warning me-1"></i> API Token (Secret Key):</span>
                                        <span class="text-primary fw-semibold" style="font-size: 0.70rem;">({{ $glHospname }})</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-muted py-0.5 px-2"><i class="bi bi-shield-lock"></i></span>
                                        <input type="text" class="form-control font-monospace bg-white text-dark fw-bold py-1" id="modalGlSyncToken" 
                                               value="{{ $glExpectedToken }}" readonly style="font-size: 0.82rem;">
                                        <button class="btn btn-outline-secondary px-3 py-1 fw-bold d-flex align-items-center gap-1" type="button" 
                                                onclick="copyModalText('modalGlSyncToken', 'คัดลอก Token สำเร็จ!')" style="font-size: 0.76rem;">
                                            <i class="bi bi-clipboard"></i> คัดลอก
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Single Row: Left 32-bit, Right 64-bit -->
                            <div class="row g-2 mt-2 pt-2 border-top">
                                <!-- Left Col: 32-bit -->
                                <div class="col-md-6">
                                    <div class="p-2 px-2.5 rounded-3 border d-flex align-items-center justify-content-between gap-2 shadow-xs h-100" 
                                         style="background: #eff6ff; border-color: #bfdbfe !important;">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 32px; height: 32px;">
                                                <i class="bi bi-windows" style="font-size: 0.92rem;"></i>
                                            </div>
                                            <div class="text-truncate">
                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                    <strong class="text-dark" style="font-size: 0.88rem;">รุ่น 32-bit (x86)</strong>
                                                    <span class="badge bg-primary text-white rounded-pill px-2 py-0.5" id="badgeGlSync32" style="font-size: 0.68rem;">แนะนำทั่วไป</span>
                                                </div>
                                                <small class="text-muted d-block text-truncate" style="font-size: 0.72rem;" title="แนะนำ: รองรับ Office 32-bit (ใช้ได้ทุกเครื่อง)">รองรับ Windows ทุกรุ่น และ Office 32-bit</small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                            <a href="{{ url('downloads/Rims-GL-Sync-x86.exe') }}" id="btnGlSync32" 
                                               class="btn btn-primary btn-sm rounded-pill px-2.5 py-1 fw-bold shadow-xs d-inline-flex align-items-center gap-1" 
                                               download="Rims-GL-Sync-x86.exe" style="font-size: 0.80rem;">
                                                <i class="bi bi-download"></i> .exe
                                            </a>
                                            <a href="{{ url('downloads/Rims-GL-Sync-x86.zip') }}" 
                                               class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-1 small" 
                                               download style="font-size: 0.74rem;" title="ดาวน์โหลดไฟล์สำรอง .zip">
                                                .zip
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Col: 64-bit -->
                                <div class="col-md-6">
                                    <div class="p-2 px-2.5 rounded-3 border d-flex align-items-center justify-content-between gap-2 shadow-xs h-100" 
                                         style="background: #f8fafc; border-color: #cbd5e1 !important;">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                            <div class="rounded-circle bg-light border text-secondary d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 32px; height: 32px;">
                                                <i class="bi bi-windows" style="font-size: 0.92rem;"></i>
                                            </div>
                                            <div class="text-truncate">
                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                    <strong class="text-dark" style="font-size: 0.88rem;">รุ่น 64-bit (x64)</strong>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-0.5" id="badgeGlSync64" style="font-size: 0.68rem;">Office 64-bit</span>
                                                </div>
                                                <small class="text-muted d-block text-truncate" style="font-size: 0.72rem;" title="สำหรับเครื่องที่ติดตั้ง MS Office 64-bit">สำหรับเครื่องที่ติดตั้ง Office 64-bit</small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                            <a href="{{ url('downloads/Rims-GL-Sync-x64.exe') }}" id="btnGlSync64" 
                                               class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 fw-bold shadow-xs d-inline-flex align-items-center gap-1" 
                                               download="Rims-GL-Sync-x64.exe" style="font-size: 0.80rem;">
                                                <i class="bi bi-download"></i> .exe
                                            </a>
                                            <a href="{{ url('downloads/Rims-GL-Sync-x64.zip') }}" 
                                               class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-1 small" 
                                               download style="font-size: 0.74rem;" title="ดาวน์โหลดไฟล์สำรอง .zip">
                                                .zip
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tool 2: Chrome Extension e-Claim Sync -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 border-start border-4 border-success">
                            <!-- Top: Header + Badges -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                                        <i class="bi bi-browser-chrome fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h6 class="fw-bold text-dark mb-0 fs-6">Extension e-Claim Sync</h6>
                                            <span class="badge bg-success text-white rounded-pill px-2.5 py-0.5" style="font-size: 0.70rem;"><i class="bi bi-puzzle me-1"></i>ส่วนขยายเบราว์เซอร์ (Extension)</span>
                                            <span class="badge bg-light text-secondary border rounded-pill" style="font-size: 0.70rem;">Chrome / Edge</span>
                                        </div>
                                        <small class="text-muted d-block" style="font-size: 0.74rem;">ดึงผล Claim และ Statement จาก e-Claim สปสช. เข้าสู่ระบบ RiMS อัตโนมัติ</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Middle: Compact Installation Instructions Strip -->
                            <div class="p-2 px-2.5 rounded-3 bg-light border d-flex align-items-center flex-wrap gap-2 small text-secondary mb-2" style="font-size: 0.74rem;">
                                <span class="fw-bold text-dark"><i class="bi bi-info-circle-fill text-success me-1"></i>วิธีติดตั้ง:</span>
                                <span>1. แตกไฟล์ .zip</span>
                                <span class="text-muted">›</span>
                                <span>2. ไปที่ <code>chrome://extensions</code></span>
                                <span class="text-muted">›</span>
                                <span>3. เปิด <strong>Developer mode</strong></span>
                                <span class="text-muted">›</span>
                                <span>4. กด <strong>Load unpacked</strong> เลือกโฟลเดอร์</span>
                            </div>

                            <!-- Bottom: Download Action Row (Swapped to bottom) -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="badge bg-light text-muted border" style="font-size: 0.70rem;">ขนาดไฟล์ ~10 KB</span>
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.70rem;"><i class="bi bi-shield-check text-success me-1"></i> ปลอดภัย รับรองโดยระบบ RiMS</span>
                                </div>
                                <a href="{{ url('downloads/eclaim_sync.zip') }}" class="btn btn-success btn-sm rounded-pill px-3.5 py-1.5 fw-bold shadow-xs d-inline-flex align-items-center gap-1.5 ms-auto" style="font-size: 0.82rem;">
                                    <i class="bi bi-cloud-arrow-down-fill"></i> ดาวน์โหลด Extension (.zip)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2.5 px-4 d-flex justify-content-between align-items-center">
                <small class="text-muted"><i class="bi bi-shield-lock me-1"></i> ไฟล์ทั้งหมดได้รับการสแกนและรับรองความปลอดภัยจากระบบ RiMS</small>
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
    function copyModalText(elementId, successMsg) {
        var el = document.getElementById(elementId);
        if (!el) return;
        el.select();
        el.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(el.value).then(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: successMsg || 'คัดลอกเรียบร้อยแล้ว',
                    showConfirmButton: false,
                    timer: 1800
                });
            } else {
                alert(successMsg || 'คัดลอกเรียบร้อยแล้ว');
            }
        });
    }

    // Auto-detect Client Architecture (32-bit vs 64-bit)
    (function detectClientArch() {
        function applyArch(is64) {
            var badge32 = document.getElementById('badgeGlSync32');
            var badge64 = document.getElementById('badgeGlSync64');

            if (is64) {
                if (badge64) {
                    badge64.innerHTML = '⭐ เครื่องนี้ 64-bit';
                    badge64.className = 'badge bg-primary text-white rounded-pill px-2 py-0.5 fw-bold';
                }
            } else {
                if (badge32) {
                    badge32.innerHTML = '⭐ เครื่องนี้ 32-bit';
                    badge32.className = 'badge bg-primary text-white rounded-pill px-2 py-0.5 fw-bold';
                }
            }
        }

        // Try modern userAgentData first
        if (navigator.userAgentData && navigator.userAgentData.getHighEntropyValues) {
            navigator.userAgentData.getHighEntropyValues(['architecture', 'bitness']).then(function(ua) {
                var is64 = (ua.bitness === '64' || ua.architecture === 'x86_64');
                applyArch(is64);
            }).catch(function() {
                fallbackUA();
            });
        } else {
            fallbackUA();
        }

        function fallbackUA() {
            var ua = navigator.userAgent || '';
            var plat = navigator.platform || '';
            var is64 = /WOW64|Win64|x86_64|x64|amd64/i.test(ua) || /Win64|x64/i.test(plat);
            applyArch(is64);
        }
    })();
</script>
