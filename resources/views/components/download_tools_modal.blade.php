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

                            <!-- Single Primary Download Box -->
                            <div class="mt-2 pt-2 border-top">
                                <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between flex-wrap gap-3 shadow-xs" 
                                     style="background: linear-gradient(135deg, #f0f7ff 0%, #e0f2fe 100%); border-color: #bae6fd !important;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 44px; height: 44px;">
                                            <i class="bi bi-windows fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <strong class="text-dark fs-6">ดาวน์โหลดโปรแกรม Rims GL Sync</strong>
                                                <span class="badge bg-success text-white rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                                                    <i class="bi bi-check-circle-fill me-1"></i>แนะนำสำหรับทุกเครื่อง
                                                </span>
                                            </div>
                                            <small class="text-muted d-block mt-0.5" style="font-size: 0.76rem;">
                                                รองรับ Windows ทุกรุ่น (ทั้ง 32-bit และ 64-bit) ใช้งานได้ทันที ไม่ต้องติดตั้ง
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                                        <a href="{{ url('downloads/Rims-GL-Sync.exe') }}" id="btnGlSyncMain" 
                                           class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" 
                                           download="Rims-GL-Sync.exe" style="font-size: 0.85rem;">
                                            <i class="bi bi-download"></i> ดาวน์โหลด (.exe)
                                        </a>
                                        <a href="{{ url('downloads/Rims-GL-Sync.zip') }}" 
                                           class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1.5 small" 
                                           download style="font-size: 0.78rem;" title="ดาวน์โหลดไฟล์สำรอง .zip">
                                            <i class="bi bi-file-earmark-zip me-0.5"></i> .zip
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Subtle fallback for Office 64-bit -->
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-1 pt-2 text-muted" style="font-size: 0.73rem;">
                                    <span>
                                        <i class="bi bi-info-circle text-primary me-1"></i>กรณีเครื่องที่ติดตั้ง <strong>Microsoft Office 64-bit</strong> แล้วเปิดฐานข้อมูลไม่ได้:
                                    </span>
                                    <span class="ms-auto d-inline-flex align-items-center gap-2">
                                        <a href="{{ url('downloads/Rims-GL-Sync-x64.exe') }}" download="Rims-GL-Sync-x64.exe" class="text-decoration-none fw-semibold text-secondary">
                                            <i class="bi bi-box-arrow-down me-0.5"></i>ดาวน์โหลดรุ่น Office 64-bit (.exe)
                                        </a>
                                        <span class="text-black-50">|</span>
                                        <a href="{{ url('downloads/Rims-GL-Sync-x64.zip') }}" download class="text-decoration-none text-muted">
                                            .zip
                                        </a>
                                    </span>
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
</script>
