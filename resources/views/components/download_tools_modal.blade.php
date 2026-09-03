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
            <div class="modal-body p-4 bg-light">
                <div class="row g-4">
                    <!-- Tool 1: GL Sync Agent -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 border-start border-4 border-primary">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div class="d-flex gap-3">
                                    <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                                        <i class="bi bi-bank fs-2"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h6 class="fw-bold text-dark mb-0 fs-5">Rims GL Sync</h6>
                                            <span class="badge bg-success text-white rounded-pill px-2.5 py-1">โปรแกรม Windows (.exe ไฟล์เดียว)</span>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill">v1.2 (Standalone GUI)</span>
                                        </div>
                                        <p class="text-muted small mt-1 mb-2" style="line-height: 1.5;">
                                            โปรแกรม <strong>Rims-GL-Sync.exe ไฟล์เดียว ดับเบิ้ลคลิกเปิดหน้าจอ GUI ได้ทันที</strong> มีช่องกรอก Server API, Token, ปุ่ม Browse เลือกไฟล์ GL (.accdb), ติ๊กเริ่มทำงานอัตโนมัติเมื่อเปิดเครื่อง (Auto-Start Windows), กำหนดความถี่ส่งข้อมูลอัตโนมัติ และปุ่มกดซิงค์ทันที
                                        </p>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                                            <span class="badge bg-light text-dark border"><i class="bi bi-windows me-1 text-success"></i> Windows 64-bit (.exe)</span>
                                            <span class="badge bg-light text-dark border"><i class="bi bi-display me-1 text-info"></i> หน้าต่าง GUI ในตัว</span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-folder2-open me-1"></i> มีปุ่ม Browse ที่อยู่ไฟล์ GL</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="bi bi-clock-history me-1"></i> Auto-Schedule 30 นาที</span>
                                            <span class="badge bg-light text-dark border"><i class="bi bi-power me-1 text-danger"></i> Auto-Start Windows</span>
                                            <span class="badge bg-light text-muted border">ขนาด 6.5 MB</span>
                                        </div>
                                        
                                        <!-- API Config Box for GUI -->
                                        <div class="p-3 rounded-3 bg-light border mt-2">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong class="text-dark small d-flex align-items-center gap-1.5">
                                                    <i class="bi bi-hdd-network text-primary"></i> ข้อมูล API สำหรับนำไปกรอกในหน้าต่างโปรแกรม:
                                                </strong>
                                            </div>

                                            <!-- API URL -->
                                            <div class="mb-2">
                                                <label class="form-label text-muted small mb-1 fw-bold" style="font-size: 0.76rem;">Server API URL:</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white text-muted"><i class="bi bi-link-45deg"></i></span>
                                                    <input type="text" class="form-control font-monospace bg-white text-primary fw-bold" id="modalGlSyncApiUrl" 
                                                           value="{{ url('api/hosfin/gl/sync') }}" readonly>
                                                    <button class="btn btn-outline-primary px-3 fw-bold d-flex align-items-center gap-1" type="button" 
                                                            onclick="copyModalText('modalGlSyncApiUrl', 'คัดลอก API URL สำเร็จ!')">
                                                        <i class="bi bi-clipboard"></i> คัดลอก
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- API Token -->
                                            <div>
                                                <label class="form-label text-muted small mb-1 fw-bold" style="font-size: 0.76rem;">API Token (Secret Key):</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white text-muted"><i class="bi bi-key-fill text-warning"></i></span>
                                                    <input type="text" class="form-control font-monospace bg-white text-dark" id="modalGlSyncToken" 
                                                           value="{{ config('services.gl_sync.token', env('GL_SYNC_TOKEN', 'rims-gl-token-2569-secret')) }}" readonly>
                                                    <button class="btn btn-outline-secondary px-3 fw-bold d-flex align-items-center gap-1" type="button" 
                                                            onclick="copyModalText('modalGlSyncToken', 'คัดลอก Token สำเร็จ!')">
                                                        <i class="bi bi-clipboard"></i> คัดลอก
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ms-auto flex-shrink-0 align-self-center text-center d-flex flex-column gap-2">
                                    <a href="{{ url('downloads/Rims-GL-Sync.exe') }}" class="btn btn-success rounded-pill px-4 py-2.5 fw-bold shadow-sm d-inline-flex align-items-center justify-content-center gap-2" download="Rims-GL-Sync.exe">
                                        <i class="bi bi-download fs-5"></i> ดาวน์โหลด Rims GL Sync (.exe)
                                    </a>
                                    <a href="{{ url('downloads/Rims-GL-Sync.zip') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 text-muted" download="Rims-GL-Sync.zip">
                                        <i class="bi bi-file-earmark-zip me-1"></i> ดาวน์โหลดแบบ (.zip)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tool 2: Chrome Extension e-Claim Sync -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 border-start border-4 border-success">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div class="d-flex gap-3">
                                    <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
                                        <i class="bi bi-browser-chrome fs-2"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h6 class="fw-bold text-dark mb-0 fs-5">Extension e-Claim Sync</h6>
                                            <span class="badge bg-success text-white rounded-pill px-2.5 py-1">ส่วนขยายเบราว์เซอร์ (Chrome)</span>
                                            <span class="badge bg-light text-secondary border rounded-pill">Manifest V3</span>
                                        </div>
                                        <p class="text-muted small mt-1 mb-2" style="line-height: 1.5;">
                                            ส่วนขยาย Google Chrome ช่วยอำนวยความสะดวกในการดึงผลการ Claim, สรุปยอด และ Statement จากระบบ e-Claim สปสช. เข้าสู่ระบบ RIMS โดยตรงอย่างรวดเร็วและปลอดภัย
                                        </p>
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                                            <span class="badge bg-light text-dark border"><i class="bi bi-google me-1 text-danger"></i> Google Chrome / Edge</span>
                                            <span class="badge bg-light text-dark border"><i class="bi bi-shield-check me-1 text-success"></i> ปลอดภัย 100%</span>
                                            <span class="badge bg-light text-muted border">ขนาด 10 KB</span>
                                        </div>

                                        <!-- Quick Instructions Accordion/Box -->
                                        <div class="p-2.5 rounded-3 bg-light border small text-secondary" style="font-size: 0.8rem;">
                                            <strong class="text-dark d-block mb-1"><i class="bi bi-info-circle-fill text-success me-1"></i> วิธีติดตั้งบน Google Chrome:</strong>
                                            1. แตกไฟล์ <code>eclaim_sync.zip</code> ออกเป็นโฟลเดอร์<br>
                                            2. เปิด Google Chrome พิมพ์ที่ช่อง URL ว่า <code>chrome://extensions</code><br>
                                            3. เปิดสวิตช์ <strong>Developer mode (โหมดนักพัฒนา)</strong> ที่มุมขวาบน<br>
                                            4. คลิกปุ่ม <strong>Load unpacked (โหลดส่วนขยายที่คลายแล้ว)</strong> แล้วเลือกโฟลเดอร์ที่แตกไว้
                                        </div>
                                    </div>
                                </div>
                                <div class="ms-auto flex-shrink-0 align-self-center text-center">
                                    <a href="{{ url('downloads/eclaim_sync.zip') }}" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-cloud-arrow-down-fill fs-5"></i> ดาวน์โหลด Extension (.zip)
                                    </a>
                                </div>
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
