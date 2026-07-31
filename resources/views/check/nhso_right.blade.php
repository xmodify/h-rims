@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page-wide Drag Overlay -->
    <div id="dragOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center bg-primary bg-opacity-75" style="z-index: 9999; backdrop-filter: blur(8px); transition: all 0.3s;">
        <div class="text-center text-white p-5 border border-4 border-dashed border-white rounded-5">
            <i class="bi bi-file-earmark-arrow-up fs-1 mb-3"></i>
            <h3 class="fw-bold">ลากไฟล์ token.txt มาปล่อยที่นี่</h3>
            <p class="mb-0">ระบบจะทำการดึงข้อมูลคีย์เชื่อมต่อโดยอัตโนมัติ</p>
        </div>
    </div>

    <!-- Page Header -->
    <div class="row mt-3 mb-4">
        <div class="col-12 px-3">
            <div class="page-header-box d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="text-dark mb-0 fw-bold">
                        <i class="bi bi-card-checklist text-info me-2"></i>
                        ตรวจสอบสิทธิการรักษา (สปสช. SRM v1.5)
                    </h5>
                    <div class="text-muted small mt-1">ตรวจสอบข้อมูลสิทธิ์ปัจจุบันโดยตรงจาก API สำนักงานหลักประกันสุขภาพแห่งชาติ (Production System)</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div id="tokenStatusBadge">
                        <!-- Loaded via JS -->
                    </div>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" onclick="syncSrmToken(true)">
                        <i class="bi bi-arrow-repeat me-1"></i> Sync SRM Token
                    </button>
                    <!-- Hidden File Input -->
                    <input type="file" id="tokenFileInput" class="d-none" accept=".txt">
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="row mb-4">
        <div class="col-12 px-3">
            <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
                <div class="card-body p-4">
                    <form id="searchForm" class="m-0">
                        <div class="row justify-content-center align-items-center g-3">
                            <div class="col-md-6 col-lg-5">
                                <label class="form-label small fw-bold text-primary mb-2">
                                    <i class="bi bi-person-bounding-box me-1"></i> 
                                    กรอกเลขประจำตัวประชาชน 13 หลัก
                                </label>
                                <div class="input-group shadow rounded-pill overflow-hidden border border-white border-2">
                                    <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted fs-5"></i></span>
                                    <input type="text" id="pidInput" class="form-control border-0 py-3 text-center fs-5 fw-bold" placeholder="x-xxxx-xxxxx-xx-x" maxlength="13" required autocomplete="off" style="letter-spacing: 2px;">
                                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm d-flex align-items-center gap-2" id="searchBtn">
                                        <span>ดึงข้อมูล</span>
                                        <i class="bi bi-arrow-right-short fs-4"></i>
                                    </button>
                                </div>
                                <div class="text-center text-muted small mt-2">
                                    คีย์การเชื่อมต่ออ่านจากไฟล์ <code class="bg-white px-1.5 py-0.5 rounded text-dark">token.txt</code> ในเครื่องผู้ใช้
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Display & Loaders -->
    <div class="row">
        <div class="col-12 px-3">
            <!-- Loading Box -->
            <div id="loaderBox" class="card border-0 shadow-sm rounded-4 d-none py-5 text-center">
                <div class="card-body py-5 d-flex flex-column align-items-center justify-content-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">กำลังดึงข้อมูลสิทธิ์การรักษา...</h5>
                    <div class="text-muted small">ระบบกำลังเชื่อมต่อไปยังเซิร์ฟเวอร์ สปสช. กรุณารอสักครู่</div>
                </div>
            </div>

            <!-- Error Box -->
            <div id="errorBox" class="alert alert-danger border-0 shadow-sm rounded-4 p-4 d-none">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                    <div>
                        <h6 class="fw-bold mb-1">เกิดข้อผิดพลาดในการตรวจสอบสิทธิ์</h6>
                        <span id="errorMessage" class="small">ไม่สามารถดำเนินการดึงข้อมูลได้</span>
                    </div>
                </div>
            </div>

            <!-- Results Card -->
            <div id="resultBox" class="d-none">
                <!-- Demographic & Patient Profile -->
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-fill text-primary me-2"></i>ข้อมูลผู้รับบริการ</h6>
                            </div>
                            <div class="card-body p-4 pt-2 text-center d-flex flex-column align-items-center justify-content-center border-top">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px;">
                                    <i class="bi bi-person-vcard fs-1"></i>
                                </div>
                                <h4 class="fw-bold text-dark mb-1" id="resFullname">-</h4>
                                <div class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill mb-4 fs-6" id="resPid">-</div>
                                
                                <div class="w-100 text-start">
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted small">วันเกิด</span>
                                        <span class="fw-bold text-dark" id="resBirthdate">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted small">เพศ</span>
                                        <span class="fw-bold text-dark" id="resSex">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted small">สัญชาติ</span>
                                        <span class="fw-bold text-dark" id="resNation">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                        <span class="text-muted small">วันที่ตรวจสอบสิทธิ์</span>
                                        <span class="fw-bold text-dark" id="resCheckDate">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 text-danger d-none" id="resDeathDateRow">
                                        <span class="small fw-bold">วันที่เสียชีวิต</span>
                                        <span class="fw-bold" id="resDeathDate">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Funds & Treatment Rights -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-credit-card-2-front-fill text-success me-2"></i>สิทธิการรักษาพยาบาลที่พบ</h6>
                                <span class="badge bg-success-subtle text-success border px-2 py-1 rounded-pill" id="fundsCount">0 สิทธิ์</span>
                            </div>
                            <div class="card-body p-4 pt-2 border-top">
                                <div id="fundsList" class="d-flex flex-column gap-3">
                                    <!-- Dynamic Funds Card -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .transition-all { transition: all 0.25s ease-in-out; }
    .fund-card {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 16px;
        transition: all 0.2s ease;
    }
    .fund-card:hover {
        background: #ffffff;
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
    }
</style>
@endsection

@push('scripts')
<script>
    const STORAGE_ACCESS_KEY = 'nhso_srm_access_token';
    const STORAGE_REFRESH_KEY = 'nhso_srm_refresh_token';

    $(document).ready(function() {
        // 1. โหลดตรวจสอบสถานะ Token จาก LocalStorage
        syncSrmToken(false);

        // 2. ดักการป้อนเลขบัตรประชาชนให้รับเฉพาะตัวเลข
        $('#pidInput').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // 3. จัดการการลากและวางไฟล์ในหน้าเพจ (Page-wide Drag & Drop)
        $(window).on('dragenter dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#dragOverlay').removeClass('d-none').addClass('d-flex');
        });

        $('#dragOverlay').on('dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#dragOverlay').removeClass('d-flex').addClass('d-none');
        });

        $('#dragOverlay').on('drop', function(e) {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0 && files[0].name.endsWith('.txt')) {
                parseTokenFile(files[0]);
            } else {
                Swal.fire({ icon: 'error', title: 'ไฟล์ไม่ถูกต้อง', text: 'กรุณาอัปโหลดไฟล์นามสกุล .txt เท่านั้น' });
            }
        });

        // 4. จัดการเมื่อเลือกไฟล์ผ่าน Input
        $('#tokenFileInput').on('change', function(e) {
            if (e.target.files.length > 0) {
                parseTokenFile(e.target.files[0]);
            }
        });

        // 5. ดักจับการส่งฟอร์มค้นหา
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            performRightSearch();
        });

        // 6. ตั้งเวลาเช็คและซิงค์คีย์อัตโนมัติทุกๆ 30 วินาที
        setInterval(function() {
            checkAndAutoSyncToken();
        }, 30000);
    });

    // ตรวจสอบสถานะว่าหมดอายุหรือใกล้หมดอายุหรือไม่เพื่อทำการซิงค์อัตโนมัติ
    function checkAndAutoSyncToken() {
        const access = localStorage.getItem(STORAGE_ACCESS_KEY);
        const exp = localStorage.getItem('nhso_srm_token_exp');
        if (!access || !exp) {
            syncSrmToken(false);
            return;
        }

        const expTime = parseInt(exp) * 1000;
        const now = Date.now();
        // หากหมดอายุแล้ว หรือจะหมดอายุในอีกไม่เกิน 2 นาที ให้ดึงคีย์ใหม่จากไฟล์โดยอัตโนมัติ
        if (now > (expTime - 120000)) {
            syncSrmToken(false);
        }
    }

    // ฟังก์ชันซิงค์อ่านไฟล์ token.txt อัตโนมัติ
    function syncSrmToken(showSuccessAlert = false) {
        $.ajax({
            url: "{{ route('check.nhso_right.load_local_token') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.status === 'success' && response.access_token) {
                    saveTokens(response.access_token, response.refresh_token, response.expires_at);
                    if (showSuccessAlert) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sync Token สำเร็จ!',
                            text: 'อัปเดตคีย์เชื่อมต่อระบบ สปสช. (SRM) เรียบร้อยแล้ว',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    updateStatusBadge();
                    if (showSuccessAlert) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Sync ล้มเหลว',
                            text: 'พบความผิดพลาดในการเข้าถึงหรือการอ่านรูปแบบไฟล์ token.txt'
                        });
                    }
                }
            },
            error: function(xhr) {
                updateStatusBadge();
                if (showSuccessAlert) {
                    const errData = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: 'Sync ไม่สำเร็จ',
                        text: errData?.message || 'ไม่พบไฟล์ token.txt หรือไม่สามารถเชื่อมต่อไปยังเครื่องท้องถิ่นได้'
                    });
                }
            }
        });
    }

    // ปรับปรุง Badge แสดงสถานะการเชื่อมต่อ Token
    function updateStatusBadge() {
        const access = localStorage.getItem(STORAGE_ACCESS_KEY);
        const exp = localStorage.getItem('nhso_srm_token_exp');
        const badge = $('#tokenStatusBadge');
        
        if (access) {
            if (exp) {
                const expTime = parseInt(exp) * 1000;
                const now = Date.now();
                if (now > expTime) {
                    badge.html(`<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i> Token หมดอายุเมื่อ ${formatThaiDateTime(new Date(expTime))}</span>`);
                    return;
                } else {
                    badge.html(`<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Token พร้อมใช้งาน (หมดอายุ ${formatThaiDateTime(new Date(expTime))})</span>`);
                    return;
                }
            }
            badge.html('<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Token พร้อมใช้งาน</span>');
        } else {
            badge.html('<span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill"><i class="bi bi-exclamation-circle me-1"></i> ยังไม่ได้นำเข้า Token</span>');
        }
    }

    // บันทึก Token ลง Storage
    function saveTokens(access, refresh, expiresAt = null) {
        if (access) {
            localStorage.setItem(STORAGE_ACCESS_KEY, access);
            if (expiresAt) {
                localStorage.setItem('nhso_srm_token_exp', expiresAt);
            } else {
                // Parse JWT payload on client side as fallback
                try {
                    const parts = access.split('.');
                    if (parts.length === 3) {
                        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                        if (payload.exp) {
                            localStorage.setItem('nhso_srm_token_exp', payload.exp);
                        }
                    }
                } catch (e) {
                    localStorage.removeItem('nhso_srm_token_exp');
                }
            }
        } else {
            localStorage.removeItem(STORAGE_ACCESS_KEY);
            localStorage.removeItem('nhso_srm_token_exp');
        }

        if (refresh) localStorage.setItem(STORAGE_REFRESH_KEY, refresh);
        else localStorage.removeItem(STORAGE_REFRESH_KEY);

        updateStatusBadge();
    }

    // แกะวิเคราะห์หา Token ในไฟล์ token.txt
    function parseTokenFile(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const content = e.target.result;
            const lines = content.split('\n');
            let access = '';
            let refresh = '';

            lines.forEach(line => {
                const cleanLine = line.replace('\r', '').trim();
                if (cleanLine.startsWith('access-token=')) {
                    access = cleanLine.split('access-token=')[1];
                } else if (cleanLine.startsWith('refresh-token=')) {
                    refresh = cleanLine.split('refresh-token=')[1];
                }
            });

            if (access) {
                saveTokens(access, refresh);
                Swal.fire({
                    icon: 'success',
                    title: 'เชื่อมต่อ Token สำเร็จ!',
                    text: 'ตรวจพบคีย์เชื่อมต่อระบบ สปสช. (SRM) เรียบร้อยแล้ว',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'ไฟล์ไม่ถูกต้อง', 
                    text: 'ไม่พบคีย์ access-token ภายในไฟล์ token.txt กรุณาตรวจสอบไฟล์อีกครั้ง\n(เลือกไฟล์จาก %userprofile%\\SRM Smart Card Single Sign-On\\)' 
                });
            }
            // Reset input file value so the user can re-select the same file if needed
            $('#tokenFileInput').val('');
        };
        reader.readAsText(file);
    }

    // ส่ง Ajax ค้นหาข้อมูลสิทธิ์การรักษา
    function performRightSearch() {
        const pid = $('#pidInput').val().trim();
        if (pid.length !== 13) {
            Swal.fire({ icon: 'warning', title: 'ระบุข้อมูลไม่ครบถ้วน', text: 'กรุณากรอกเลขบัตรประชาชนให้ครบ 13 หลัก' });
            return;
        }

        const access = localStorage.getItem(STORAGE_ACCESS_KEY) || '';
        const exp = localStorage.getItem('nhso_srm_token_exp');
        const now = Date.now();
        const expTime = exp ? parseInt(exp) * 1000 : 0;

        // หากไม่มี Token หรือ Token หมดอายุแล้ว ให้ทำการดึงคีย์ใหม่โดยอัตโนมัติก่อนส่งค้นหา
        if (!access || now > expTime) {
            $('#loaderBox').removeClass('d-none');
            $('#errorBox').addClass('d-none');
            $('#resultBox').addClass('d-none');

            $.ajax({
                url: "{{ route('check.nhso_right.load_local_token') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.status === 'success' && response.access_token) {
                        saveTokens(response.access_token, response.refresh_token, response.expires_at);
                        executeSearchAjax(pid);
                    } else {
                        $('#loaderBox').addClass('d-none');
                        showError('ไม่สามารถดึงข้อมูลได้: กรุณาเปิดโปรแกรม SRM Smart Card และทำการเสียบบัตรประชาชนเพื่อสร้างคีย์เชื่อมต่อ');
                    }
                },
                error: function() {
                    $('#loaderBox').addClass('d-none');
                    showError('เชื่อมต่อโปรแกรม SRM ในเครื่องไม่สำเร็จ กรุณาตรวจสอบการทำงานของโปรแกรม SRM Smart Card Single Sign-On');
                }
            });
        } else {
            executeSearchAjax(pid);
        }
    }

    // ฟังก์ชันยิง Ajax ค้นหาข้อมูลจริง
    function executeSearchAjax(pid) {
        const access = localStorage.getItem(STORAGE_ACCESS_KEY) || '';
        const refresh = localStorage.getItem(STORAGE_REFRESH_KEY) || '';

        // แสดงหน้าจอกำลังโหลด
        if ($('#loaderBox').hasClass('d-none')) {
            $('#loaderBox').removeClass('d-none');
        }
        $('#errorBox').addClass('d-none');
        $('#resultBox').addClass('d-none');

        $.ajax({
            url: "{{ route('check.nhso_right.search') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                pid: pid,
                environment: 'production',
                access_token: access,
                refresh_token: refresh
            },
            success: function(response) {
                $('#loaderBox').addClass('d-none');
                
                // หากเซิร์ฟเวอร์ต่ออายุ Token ให้บันทึกใหม่
                if (response.token_refreshed) {
                    saveTokens(response.access_token, response.refresh_token, response.expires_at);
                }

                if (response.status === 'success' && response.data) {
                    displayResult(response.data);
                } else {
                    showError(response.message || 'ไม่พบข้อมูลตอบรับที่ถูกต้องจาก สปสช.');
                }
            },
            error: function(xhr) {
                $('#loaderBox').addClass('d-none');
                const errData = xhr.responseJSON;

                if (xhr.status === 401) {
                    showError('คีย์เชื่อมต่อระบบ (Access Token) หมดอายุ หรือไม่ถูกต้อง กรุณาทำรายการซิงค์เพื่อต่ออายุคีย์');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Token ไม่ถูกต้องหรือหมดอายุ',
                        text: 'กรุณาตรวจสอบว่าโปรแกรม SRM Smart Card Single Sign-On ในเครื่องกำลังทำงานอยู่ จากนั้นคลิกปุ่ม Sync Token เพื่อดึงคีย์ใหม่ล่าสุด',
                        confirmButtonText: 'Sync Token',
                        confirmButtonColor: '#3b82f6',
                        showCancelButton: true,
                        cancelButtonText: 'ยกเลิก'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            syncSrmToken(true);
                        }
                    });
                } else {
                    showError(errData?.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล (HTTP Status: ' + xhr.status + ')');
                }
            }
        });
    }

    // แสดงรายละเอียดความผิดพลาด
    function showError(message) {
        $('#errorMessage').text(message);
        $('#errorBox').removeClass('d-none');
        $('#resultBox').addClass('d-none');
    }

    // แสดงข้อมูลผลลัพธ์
    function displayResult(d) {
        $('#resFullname').text(`${d.tname || ''}${d.fname || ''} ${d.lname || ''}`);
        $('#resPid').text(formatPid(d.pid));
        $('#resBirthdate').text(d.birthDate || '-');
        $('#resSex').text(d.sex?.name || '-');
        $('#resNation').text(d.nation?.name || '-');
        $('#resCheckDate').text(formatThaiDateTime(d.checkDate));

        if (d.deathDate) {
            $('#resDeathDate').text(formatThaiDateTime(d.deathDate));
            $('#resDeathDateRow').removeClass('d-none');
            $('#resFullname').append(' <span class="badge bg-danger ms-2" style="font-size:0.8rem;"><i class="bi bi-heartbreak-fill"></i> เสียชีวิตแล้ว</span>');
        } else {
            $('#resDeathDateRow').addClass('d-none');
        }

        const fundsList = $('#fundsList');
        fundsList.empty();

        const funds = d.funds || [];
        $('#fundsCount').text(`${funds.length} สิทธิ์`);

        if (funds.length === 0) {
            fundsList.html('<div class="text-center py-4 text-muted"><i class="bi bi-card-text fs-3 d-block mb-2"></i>ไม่พบข้อมูลสิทธิการรักษาพยาบาลปัจจุบัน</div>');
        } else {
            funds.sort((a, b) => {
                if (a.fundType === 'Y' && b.fundType !== 'Y') return -1;
                if (a.fundType !== 'Y' && b.fundType === 'Y') return 1;
                return 0;
            });

            funds.forEach(fund => {
                const isMainFund = fund.fundType === 'Y';
                const mainBadge = isMainFund 
                    ? '<span class="badge bg-primary text-white px-3 py-1.5 rounded-pill"><i class="bi bi-star-fill me-1 text-warning"></i> สิทธิหลัก (Main Fund)</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary border px-3 py-1.5 rounded-pill">สิทธิอื่น ๆ (Other Fund)</span>';
                
                let paidModelText = '-';
                if (fund.paidModel) {
                    const models = { '1': 'จ่ายล่วงหน้า (Capitation)', '2': 'จ่ายตามจริงรายครั้ง (Fee For Service)', '4': 'จ่ายตามกลุ่มวินิจฉัยโรคร่วม (DRGs)', '5': 'จ่ายในอัตราคงที่ตามเกณฑ์' };
                    paidModelText = models[fund.paidModel] || `รูปแบบที่ ${fund.paidModel}`;
                }

                const fundHtml = `
                    <div class="fund-card p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-bottom pb-3 mb-3">
                            <div>
                                <h5 class="fw-bold text-primary mb-1">${fund.mainInscl?.name || '-'} (${fund.mainInscl?.id || '-'})</h5>
                                <div class="text-muted small">สิทธิย่อย: <span class="text-dark fw-semibold">${fund.subInscl?.name || '-'} (${fund.subInscl?.id || '-'})</span></div>
                            </div>
                            <div>
                                ${mainBadge}
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <div class="d-flex flex-column">
                                    <span class="text-muted small mb-1"><i class="bi bi-building me-1"></i> หน่วยบริการประจำ (hospMainOp)</span>
                                    <span class="fw-bold text-dark">${fund.hospMainOp?.hname || '-'}</span>
                                    <span class="text-muted small font-monospace">รหัส: ${fund.hospMainOp?.hcode || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="d-flex flex-column">
                                    <span class="text-muted small mb-1"><i class="bi bi-hospital me-1"></i> หน่วยบริการปฐมภูมิ (hospSub)</span>
                                    <span class="fw-bold text-dark">${fund.hospSub?.hname || '-'}</span>
                                    <span class="text-muted small font-monospace">รหัส: ${fund.hospSub?.hcode || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="d-flex flex-column">
                                    <span class="text-muted small mb-1"><i class="bi bi-signpost-split me-1"></i> หน่วยบริการส่งต่อ (hospMain)</span>
                                    <span class="fw-bold text-dark">${fund.hospMain?.hname || '-'}</span>
                                    <span class="text-muted small font-monospace">รหัส: ${fund.hospMain?.hcode || '-'}</span>
                                </div>
                            </div>
                            
                            <div class="col-12"><hr class="my-1 opacity-5"></div>

                            <div class="col-md-6 col-lg-4">
                                <span class="text-muted small d-block">จังหวัดลงทะเบียน</span>
                                <span class="fw-semibold text-dark">${fund.purchaseProvince?.name || '-'} (รหัส ${fund.purchaseProvince?.id || '-'})</span>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <span class="text-muted small d-block">หน่วยงานสังกัด / ต้นสังกัด</span>
                                <span class="fw-semibold text-dark">${fund.department?.name || '-'} (${fund.department?.relation || 'ไม่มีความสัมพันธ์'})</span>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <span class="text-muted small d-block">เลขบัตร / เลขสิทธิ์ (cardId)</span>
                                <span class="fw-semibold font-monospace text-dark">${fund.cardId || '-'}</span>
                            </div>
                            
                            <div class="col-md-6 col-lg-4">
                                <span class="text-muted small d-block">รูปแบบการจ่ายเงิน (paidModel)</span>
                                <span class="fw-semibold text-dark">${paidModelText}</span>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <span class="text-muted small d-block">วันที่เริ่มใช้สิทธิ์</span>
                                <span class="fw-semibold text-dark text-success"><i class="bi bi-calendar-check me-1"></i>${formatThaiDate(fund.startDateTime)}</span>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <span class="text-muted small d-block">วันที่สิทธิ์สิ้นสุด</span>
                                <span class="fw-semibold text-dark ${fund.expireDateTime ? 'text-danger' : 'text-muted'}">
                                    <i class="bi bi-calendar-x me-1"></i>${fund.expireDateTime ? formatThaiDate(fund.expireDateTime) : 'ไม่มีวันหมดอายุ'}
                                </span>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <span class="text-muted small d-block">วันที่เปลี่ยนแปลงสิทธิ์ล่าสุด</span>
                                <span class="fw-semibold text-dark">
                                    <i class="bi bi-calendar-event me-1"></i>${fund.transDate ? formatThaiDate(fund.transDate) : '-'}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
                fundsList.append(fundHtml);
            });
        }

        $('#resultBox').removeClass('d-none');
    }

    function formatPid(pid) {
        if (!pid || pid.length !== 13) return pid;
        return `${pid[0]}-${pid.substring(1, 5)}-${pid.substring(5, 10)}-${pid.substring(10, 12)}-${pid[12]}`;
    }

    function formatThaiDate(dateStr) {
        if (!dateStr) return '-';
        const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear() + 543}`;
    }

    function formatThaiDateTime(dateStr) {
        if (!dateStr) return '-';
        const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const timeStr = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0') + ' น.';
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear() + 543} เวลา ${timeStr}`;
    }
</script>
@endpush
