@props([
    'modalId' => 'ExtensionInfoModal'
])

<!-- Modal Extension Info (แนวนอน 3 คอลัมน์ กระชับ สวยงาม) -->
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-dark text-white py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-puzzle-fill text-warning fs-4"></i>
            <div>
                <h5 class="modal-title fw-bold mb-0" id="{{ $modalId }}Label">วิธีติดตั้งและใช้งาน RiMS Chrome Extension</h5>
                <span class="small text-white-50">ส่วนเสริมสำหรับเชื่อมต่อและดึงข้อมูลจาก e-Claim สปสช. อัตโนมัติ</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-light">
          <div class="row g-3">
              <!-- Step 1 -->
              <div class="col-md-4">
                  <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                      <div class="d-flex align-items-center gap-2 mb-2">
                          <span class="badge bg-primary rounded-circle p-2 px-3 fw-bold">1</span>
                          <h6 class="fw-bold text-primary mb-0">ติดตั้งส่วนเสริม (ครั้งเดียว)</h6>
                      </div>
                      <div class="my-2">
                          <a href="{{ url('downloads/eclaim_sync.zip') }}" class="btn btn-sm btn-primary w-100 rounded-pill shadow-sm fw-bold py-2">
                              <i class="bi bi-download me-1"></i> ดาวน์โหลด eclaim_sync.zip
                          </a>
                      </div>
                      <ol class="small text-muted ps-3 mb-0 lh-base" style="font-size: 0.8rem;">
                          <li class="mb-1">แตกไฟล์ (Extract ZIP) ไว้ในโฟลเดอร์ที่สะดวก</li>
                          <li class="mb-1">เปิด Chrome พิมพ์ <code>chrome://extensions</code></li>
                          <li class="mb-1">เปิดสวิตช์ <b>Developer mode</b> (มุมขวาบน)</li>
                          <li>กดปุ่ม <b>Load unpacked</b> แล้วเลือกโฟลเดอร์ที่แตกไว้</li>
                      </ol>
                  </div>
              </div>

              <!-- Step 2 -->
              <div class="col-md-4">
                  <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                      <div class="d-flex align-items-center gap-2 mb-2">
                          <span class="badge bg-warning text-dark rounded-circle p-2 px-3 fw-bold">2</span>
                          <h6 class="fw-bold text-warning-emphasis mb-0">ตั้งค่าเชื่อมต่อ (ครั้งเดียว)</h6>
                      </div>
                      <div class="bg-light p-2 rounded-3 border my-2">
                          <div class="d-flex justify-content-between align-items-center mb-1">
                              <span class="small fw-bold text-dark" style="font-size: 0.75rem;">Server API URL:</span>
                              <button class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.7rem;" onclick="copyToClipboard('{{ url('api') }}')">
                                  <i class="bi bi-clipboard me-1"></i>คัดลอก
                              </button>
                          </div>
                          <code id="apiUrlPath" class="text-break text-primary fw-bold small" style="font-size: 0.75rem;">{{ url('api') }}</code>
                      </div>
                      <ol class="small text-muted ps-3 mb-0 lh-base" style="font-size: 0.8rem;">
                          <li class="mb-1">คลิกไอคอน Extension <b>"RiMS Sync"</b></li>
                          <li class="mb-1">คลิกไอคอน <b>⚙️ ฟันเฟือง</b> ที่มุมขวาบน</li>
                          <li>วาง <b>API URL</b> และใส่รหัส รพ. แล้วกด <b>บันทึก</b></li>
                      </ol>
                  </div>
              </div>

              <!-- Step 3 -->
              <div class="col-md-4">
                  <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white">
                      <div class="d-flex align-items-center gap-2 mb-2">
                          <span class="badge bg-success rounded-circle p-2 px-3 fw-bold">3</span>
                          <h6 class="fw-bold text-success mb-0">เข้าใช้งาน (ทำตามต้องการ)</h6>
                      </div>
                      <div class="p-2 rounded-3 bg-success-subtle border border-success-subtle text-success my-2 small" style="font-size: 0.75rem;">
                          <i class="bi bi-check-circle-fill me-1"></i> มีระบบ <b>Keep-Alive & Auto-Sync</b> เลี้ยง Session ให้อัตโนมัติ
                      </div>
                      <ol class="small text-muted ps-3 mb-0 lh-base" style="font-size: 0.8rem;">
                          <li class="mb-1">เปิดหน้าเว็บ <a href="https://eclaim.nhso.go.th" target="_blank" class="fw-bold text-success text-decoration-underline">e-Claim สปสช.</a> และ Login ThaiD</li>
                          <li class="mb-1">คลิกไอคอน Extension <b>"RiMS Sync"</b></li>
                          <li>กดปุ่มสีเขียว <b>"ซิงก์ Session เข้า RiMS"</b></li>
                      </ol>
                  </div>
              </div>
          </div>
      </div>
      <div class="modal-footer border-0 bg-light py-2">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
      </div>
    </div>
  </div>
</div>
