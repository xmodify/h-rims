@props([
    'modalId' => 'importHubModal',
    'title' => 'ศูนย์รวมการนำเข้าข้อมูล (Import Hub)',
    'claimTitle' => 'ข้อมูลผู้มารับบริการ',
    'repUrl' => null,
    'stmUrl' => null,
    'hasEdc' => false,
    'edcModalId' => 'importEdcModal',
    'hasFdh' => false,
    'fdhFunction' => 'checkFdhBulk(event)',
    'extensionModalId' => 'ExtensionInfoModal'
])

@php
    $cardCount = ($repUrl ? 1 : 0) + ($stmUrl ? 1 : 0) + ($hasFdh ? 1 : 0) + ($hasEdc ? 1 : 0) + ($extensionModalId ? 1 : 0);
    $cardCol = ($cardCount === 3) ? 'col-md-4' : 'col-md-6';
@endphp

<!-- Modal ศูนย์รวมการนำเข้าข้อมูล (Import Hub Modal) -->
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-cloud-arrow-up-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="{{ $modalId }}Label">{{ $title }}</h5>
                        <span class="small text-white-50">เลือกช่องทางนำเข้าข้อมูลสำหรับ {{ $claimTitle }}</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-3">
                    @if($repUrl)
                    <!-- Option 1: REP -->
                    <div class="{{ $cardCol }}">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-3 hover-lift bg-white">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 p-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                    <i class="bi bi-file-earmark-check-fill fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-1">ผลการตรวจเบื้องต้น (REP)</h6>
                                    <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">ดึงไฟล์สรุปผลการตรวจสอบเบื้องต้น (REP) จาก e-Claim สปสช. อัตโนมัติ</p>
                                    <a href="{{ $repUrl }}" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> ไปหน้าดึง REP
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($stmUrl)
                    <!-- Option 2: Statement (STM) -->
                    <div class="{{ $cardCol }}">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-3 hover-lift bg-white">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                    <i class="bi bi-file-earmark-spreadsheet-fill fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-1">ใบแจ้งโอนเงิน (Statement)</h6>
                                    <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">ดึง Statement (STM) ยอดเงินชดเชยจาก e-Claim สปสช. อัตโนมัติ</p>
                                    <a href="{{ $stmUrl }}" target="_blank" class="btn btn-sm btn-success text-white rounded-pill px-3 fw-bold">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> ไปหน้าดึง Statement
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($hasFdh)
                    <!-- Option: FDH Bulk Check -->
                    <div class="{{ $cardCol }}">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-3 hover-lift bg-white">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                    <i class="bi bi-arrow-repeat fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-1">ดึงสถานะ FDH (สปสช.)</h6>
                                    <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">ดึงสถานะการส่งข้อมูลและผลการประเมินจากระบบ Financial Data Hub (FDH) อัตโนมัติ</p>
                                    <button type="button" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold" onclick="{{ $fdhFunction }}" data-bs-dismiss="modal">
                                        <i class="bi bi-arrow-repeat me-1"></i> ดึงสถานะ FDH
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($hasEdc)
                    <!-- Option 3: EDC (ZIP) -->
                    <div class="{{ $cardCol }}">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-3 hover-lift bg-white">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                    <i class="bi bi-credit-card-2-front-fill fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-1">เลขอนุมัติ EDC (ZIP File)</h6>
                                    <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">อัปโหลดไฟล์ ZIP รายงานเลขอนุมัติบัตร EDC เพื่อจับคู่กับข้อมูลผู้ป่วย</p>
                                    <button type="button" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#{{ $edcModalId }}">
                                        <i class="bi bi-upload me-1"></i> นำเข้าไฟล์ EDC
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Option 4: Chrome Extension -->
                    <div class="{{ $cardCol }}">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-3 hover-lift bg-white">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                    <i class="bi bi-puzzle-fill fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold text-dark mb-1">Chrome Extension (RiMS)</h6>
                                    <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">คู่มือติดตั้งและเชื่อมต่อส่วนเสริมเพื่อซิงก์ข้อมูล e-Claim สปสช.</p>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#{{ $extensionModalId }}">
                                        <i class="bi bi-info-circle me-1"></i> วิธีติดตั้ง Extension
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>
