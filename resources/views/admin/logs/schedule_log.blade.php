@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-lg-5">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold">
                <i class="bi bi-clock-history me-2"></i> Log Schedule
            </h4>
            <small class="text-muted">ประวัติการทำงานของตัวตั้งเวลาทำงาน (Task Scheduler) แยกตามงาน</small>
        </div>
        <div class="d-flex gap-2">
            <a href="" class="btn btn-outline-secondary btn-sm px-3 shadow-sm hover-scale">
                <i class="bi bi-arrow-clockwise me-1"></i> โหลดข้อมูลใหม่
            </a>
        </div>
    </div>
    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-3 bg-white p-2 rounded-4 shadow-sm border" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-3 fw-bold" id="pills-nhso-tab" data-bs-toggle="pill" data-bs-target="#pills-nhso" type="button" role="tab" aria-controls="pills-nhso" aria-selected="true">
                <i class="bi bi-check-circle-fill me-1 text-primary"></i> Log สปสช.
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold" id="pills-fdh-tab" data-bs-toggle="pill" data-bs-target="#pills-fdh" type="button" role="tab" aria-controls="pills-fdh" aria-selected="false">
                <i class="bi bi-wallet-fill me-1 text-info"></i> Log FDH
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold" id="pills-notify-tab" data-bs-toggle="pill" data-bs-target="#pills-notify" type="button" role="tab" aria-controls="pills-notify" aria-selected="false">
                <i class="bi bi-bell-fill me-1 text-warning"></i> Log Notify
            </button>
        </li>
    </ul>
    <!-- Tab Contents -->
    <div class="tab-content" id="pills-tabContent">
        <!-- NHSO Log -->
        <div class="tab-pane fade show active" id="pills-nhso" role="tabpanel" aria-labelledby="pills-nhso-tab" tabindex="0">
            <div class="card dash-card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-clock-history me-2"></i> NHSO Endpoint Scheduler Log
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm" onclick="startNhsoManualPull()">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i> ดึงข้อมูล สปสช. (เมื่อวาน)
                        </button>
                        <button class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm" onclick="testNhsoConnection()">
                            <i class="bi bi-patch-check-fill me-1"></i> ทดสอบการเชื่อมต่อ
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Windows Task Scheduler Guide -->
                    <div class="alert alert-light border shadow-sm rounded-4 mb-3 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white p-2 rounded-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-clock-history fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">การตั้งค่า Windows Task Scheduler (NhsoEndpoint Pull Yesterday | 00.05 น.)</h6>
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Program: <code>powershell.exe</code> | ช่อง Add arguments (optional):</small>
                                </div>
                            </div>
                            <div style="flex-grow: 1; max-width: 650px; min-width: 280px;">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control border-secondary bg-white" value="-WindowStyle Hidden -Command &quot;Invoke-RestMethod -Uri '{{$nhso_endpoint_pull_yesterday}}' -Method Post&quot;" readonly id="nhso_cmd">
                                    <button class="btn btn-primary rounded-end px-3" type="button" onclick="copyToClipboard('nhso_cmd')">
                                        <i class="bi bi-copy me-1"></i> คัดลอก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(count($nhsoLogs) > 0)
                        <div class="table-responsive">
                            <table id="nhsoLogsTable" class="table table-hover align-middle mb-0 w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 200px;">เวลา</th>
                                        <th style="width: 120px;">สถานะ</th>
                                        <th>รายละเอียดการทำงาน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($nhsoLogs as $log)
                                        <tr>
                                            <td class="fw-bold text-secondary text-nowrap">{{ $log['timestamp'] ? DatetimeThai($log['timestamp']) : 'N/A' }}</td>
                                            <td>
                                                @if(isset($log['data']['ok']) && $log['data']['ok'] === true)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">สำเร็จ</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">ล้มเหลว</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log['data'])
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-dark">{{ $log['data']['message'] ?? 'ดึงข้อมูลสำเร็จ' }}</span>
                                                        <small class="text-muted mt-1">
                                                            ดึงทั้งหมด: <strong class="text-primary">{{ $log['data']['pulled_records'] ?? 0 }} รายการ</strong> | 
                                                            เพิ่มใหม่: <strong class="text-success">{{ $log['data']['inserted'] ?? 0 }} รายการ</strong> | 
                                                            อัปเดต: <strong class="text-info">{{ $log['data']['updated'] ?? 0 }} รายการ</strong>
                                                        </small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">{{ $log['raw'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-info-circle fs-1 d-block mb-2 text-secondary"></i>
                            ยังไม่มีประวัติการทำงานในขณะนี้ (No logs found)
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- FDH Log -->
        <div class="tab-pane fade" id="pills-fdh" role="tabpanel" aria-labelledby="pills-fdh-tab" tabindex="0">
            <div class="card dash-card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold text-info">
                        <i class="bi bi-clock-history me-2"></i> FDH Claim Status Scheduler Log
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-info btn-sm px-3 text-dark rounded-pill shadow-sm" onclick="startFdhManualCheck()">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i> เช็คสถานะ FDH (ย้อนหลัง 15 วัน)
                        </button>
                        <button class="btn btn-outline-info btn-sm px-3 rounded-pill shadow-sm" onclick="testFdhConnection()">
                            <i class="bi bi-patch-check-fill me-1"></i> ทดสอบการเชื่อมต่อ
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Windows Task Scheduler Guide -->
                    <div class="alert alert-light border shadow-sm rounded-4 mb-3 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-info text-dark p-2 rounded-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-clock-history fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">การตั้งค่า Windows Task Scheduler (FDH Pull Last10Days | 00.30 น.)</h6>
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Program: <code>powershell.exe</code> | ช่อง Add arguments (optional):</small>
                                </div>
                            </div>
                            <div style="flex-grow: 1; max-width: 650px; min-width: 280px;">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control border-secondary bg-white" value="-WindowStyle Hidden -Command &quot;Invoke-RestMethod -Uri '{{$fdh_check_claim_lastdays}}' -Method Post&quot;" readonly id="fdh_cmd">
                                    <button class="btn btn-info rounded-end px-3 text-dark" type="button" onclick="copyToClipboard('fdh_cmd')">
                                        <i class="bi bi-copy me-1"></i> คัดลอก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(count($fdhLogs) > 0)
                        <div class="table-responsive">
                            <table id="fdhLogsTable" class="table table-hover align-middle mb-0 w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 200px;">เวลา</th>
                                        <th style="width: 120px;">สถานะ</th>
                                        <th>รายละเอียดการทำงาน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fdhLogs as $log)
                                        <tr>
                                            <td class="fw-bold text-secondary text-nowrap">{{ $log['timestamp'] ? DatetimeThai($log['timestamp']) : 'N/A' }}</td>
                                            <td>
                                                @if(isset($log['data']['ok']) && $log['data']['ok'] === true)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">สำเร็จ</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">ล้มเหลว</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log['data'])
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-dark">{{ $log['data']['message'] ?? 'ตรวจสอบสถานะสำเร็จ' }}</span>
                                                        <small class="text-muted mt-1">
                                                            จำนวนวันที่ตรวจย้อนหลัง: <strong class="text-primary">{{ $log['data']['checked_days'] ?? 0 }} วัน</strong> | 
                                                            อัปเดตสถานะเคลม: <strong class="text-success">{{ $log['data']['updated_claims'] ?? 0 }} รายการ</strong> | 
                                                            ไม่พบข้อมูล: <strong class="text-warning">{{ $log['data']['not_found_claims'] ?? 0 }} รายการ</strong> | 
                                                            เกิดข้อผิดพลาด: <strong class="text-danger">{{ $log['data']['errors'] ?? 0 }} รายการ</strong>
                                                        </small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">{{ $log['raw'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-info-circle fs-1 d-block mb-2 text-secondary"></i>
                            ยังไม่มีประวัติการทำงานในขณะนี้ (No logs found)
                        </div>
                    @endif
                </div>
            </div>
        </div>


        <!-- Notify Log -->
        <div class="tab-pane fade" id="pills-notify" role="tabpanel" aria-labelledby="pills-notify-tab" tabindex="0">
            <div class="card dash-card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold text-warning">
                        <i class="bi bi-bell-fill me-2"></i> Telegram Notify Scheduler Log
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-warning btn-sm px-3 text-dark rounded-pill shadow-sm" onclick="startNotifyManualSend()">
                            <i class="bi bi-send-fill me-1"></i> ส่ง Notify
                        </button>
                        <button class="btn btn-outline-warning btn-sm px-3 rounded-pill shadow-sm" onclick="testNotifyConnection()">
                            <i class="bi bi-patch-check-fill me-1"></i> ทดสอบการเชื่อมต่อ
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Windows Task Scheduler Guide -->
                    <div class="alert alert-light border shadow-sm rounded-4 mb-3 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-warning text-dark p-2 rounded-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-clock-history fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">การตั้งค่า Windows Task Scheduler (Notify Telegram | 08.00 น.)</h6>
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Program: <code>powershell.exe</code> | ช่อง Add arguments (optional):</small>
                                </div>
                            </div>
                            <div style="flex-grow: 1; max-width: 650px; min-width: 280px;">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control border-secondary bg-white" value="-WindowStyle Hidden -Command &quot;Invoke-WebRequest -Uri '{{$notify_summary}}' -UseBasicParsing&quot;" readonly id="tg_cmd">
                                    <button class="btn btn-warning rounded-end px-3 text-dark" type="button" onclick="copyToClipboard('tg_cmd')">
                                        <i class="bi bi-copy me-1"></i> คัดลอก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(count($notifyLogs) > 0)
                        <div class="table-responsive">
                            <table id="notifyLogsTable" class="table table-hover align-middle mb-0 w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 200px;">เวลา</th>
                                        <th style="width: 120px;">สถานะ</th>
                                        <th>รายละเอียดการทำงาน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notifyLogs as $log)
                                        <tr>
                                            <td class="fw-bold text-secondary text-nowrap">{{ $log['timestamp'] ? DatetimeThai($log['timestamp']) : 'N/A' }}</td>
                                            <td>
                                                @if(isset($log['data']['success']) && $log['data']['success'] === 'success')
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">สำเร็จ</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">ล้มเหลว</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log['data'])
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-dark">ส่งรายงานสรุปบริการไปยัง Telegram เรียบร้อยแล้ว</span>
                                                        <small class="text-muted mt-1">
                                                            ผลลัพธ์: <strong class="text-success">{{ json_encode($log['data'], JSON_UNESCAPED_UNICODE) }}</strong>
                                                        </small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">{{ $log['raw'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-info-circle fs-1 d-block mb-2 text-secondary"></i>
                            ยังไม่มีประวัติการทำงานในขณะนี้ (No logs found)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Scroll all consoles to bottom on load
        document.querySelectorAll('.log-console').forEach(function(consoleElem) {
            consoleElem.scrollTop = consoleElem.scrollHeight;
        });

        // Re-scroll when switching tabs
        const tabElList = document.querySelectorAll('button[data-bs-toggle="pill"]');
        tabElList.forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', event => {
                const targetId = event.target.getAttribute('data-bs-target');
                const activeConsole = document.querySelector(targetId + ' .log-console');
                if (activeConsole) {
                    activeConsole.scrollTop = activeConsole.scrollHeight;
                }
            });
        });

        const dtConfig = {
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
            language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                infoEmpty: "แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ",
                infoFiltered: "(กรองข้อมูลจากทั้งหมด _MAX_ รายการ)",
                zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
                paginate: {
                    first: "หน้าแรก",
                    last: "หน้าสุดท้าย",
                    next: "ถัดไป",
                    previous: "ก่อนหน้า"
                }
            }
        };

        $('#nhsoLogsTable').DataTable(dtConfig);
        $('#fdhLogsTable').DataTable(dtConfig);
        $('#notifyLogsTable').DataTable(dtConfig);
    });

    function testNhsoConnection() {
        Swal.fire({
            title: 'กำลังทดสอบการเชื่อมต่อ สปสช...',
            html: 'กรุณารอสักครู่ ระบบกำลังทดสอบการเชื่อมต่อกับ authenucws.nhso.go.th',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ url("api/nhso/testconnection") }}')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'การทดสอบสำเร็จ',
                        text: data.message,
                        confirmButtonText: 'ตกลง'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'การทดสอบล้มเหลว',
                        text: data.message,
                        confirmButtonText: 'ตกลง'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการร้องขอ',
                    text: err.message,
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    function testFdhConnection() {
        Swal.fire({
            title: 'กำลังทดสอบการเชื่อมต่อ FDH...',
            html: 'กรุณารอสักครู่ ระบบกำลังสร้าง Token และเชื่อมต่อกับ fdh.moph.go.th',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ url("api/fdh/testtoken") }}')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'เชื่อมต่อ FDH สำเร็จ',
                        html: `<p class="mb-2">สามารถเชื่อมต่อและสร้าง Access Token ได้เรียบร้อย</p><textarea class="form-control form-control-sm bg-light text-muted" rows="4" readonly style="font-size: 11px; font-family: monospace;">${data.token}</textarea>`,
                        confirmButtonText: 'ตกลง'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เชื่อมต่อ FDH ล้มเหลว',
                        text: data.message || 'โปรดตรวจสอบการตั้งค่า User/Password/SecretKey/รหัสโรงพยาบาล ในหน้าตั้งค่าระบบ',
                        confirmButtonText: 'ตกลง'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการร้องขอ',
                    text: err.message,
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    function startNhsoManualPull() {
        const pullDate = "{{ date('Y-m-d', strtotime('-1 day')) }}";

        Swal.fire({
            title: 'กำลังตรวจสอบคิวงาน สปสช...',
            html: `ค้นหารายชื่อผู้ป่วยที่เข้ารับบริการในวันที่ ${pullDate} (เมื่อวาน) จาก HOSxP`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // 1. ดึงรายชื่อ CIDs ทั้งหมด
        fetch(`{{ url("api/nhso/get-pull-list") }}?vstdate=${pullDate}`)
            .then(res => res.json())
            .then(data => {
                const cids = data.cids || [];
                if (cids.length === 0) {
                    fetch('{{ url("api/nhso/log-manual-pull") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            pulled_records: 0,
                            inserted: 0,
                            updated: 0,
                            ok: true,
                            message: `ดึงข้อมูลด้วยตนเองสำเร็จ (ไม่พบผู้ป่วยที่ต้องดึงข้อมูลปิดสิทธิ์เพิ่มเติมในวันที่ ${pullDate})`
                        })
                    }).finally(() => {
                        Swal.fire('ดึงข้อมูลเสร็จสิ้น', `ไม่พบผู้ป่วยที่ต้องดึงข้อมูลปิดสิทธิ์เพิ่มเติมในวันที่ ${pullDate}`, 'info')
                        .then(() => {
                            location.reload();
                        });
                    });
                    return;
                }

                // 2. แบ่งข้อมูลเป็น Chunk ละ 15 รายการเพื่อหลีกเลี่ยง Timeout
                const chunkSize = 15;
                const chunks = [];
                for (let i = 0; i < cids.length; i += chunkSize) {
                    chunks.push(cids.slice(i, i + chunkSize));
                }

                // แสดง Swal พร้อม Progress Bar
                Swal.fire({
                    title: 'กำลังดึงข้อมูลสิทธิ์จาก สปสช.',
                    html: `
                        <div class="progress mb-2 mt-2" style="height: 20px;">
                            <div id="swal-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;">0%</div>
                        </div>
                        <div id="swal-progress-text" class="small text-muted text-start">กำลังดึงข้อมูลคิวแรก...</div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        let processed = 0;
                        let totalPulled = 0;
                        let totalInserted = 0;
                        let totalUpdated = 0;

                        function runChunk(index) {
                            if (index >= chunks.length) {
                                fetch('{{ url("api/nhso/log-manual-pull") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        pulled_records: totalPulled,
                                        inserted: totalInserted,
                                        updated: totalUpdated,
                                        ok: true,
                                        message: 'ดึงข้อมูลด้วยตนเอง (Manual Pull) สำเร็จ'
                                    })
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        return response.text().then(text => {
                                            throw new Error(`HTTP ${response.status}: ${text}`);
                                        });
                                    }
                                    return response.json();
                                })
                                .then(res => {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'ดึงข้อมูลสำเร็จ',
                                        html: `ดึงข้อมูลปิดสิทธิ์จาก สปสช. เรียบร้อยแล้ว!<br>
                                               ตรวจสอบผู้ป่วย: <strong>${cids.length} คน</strong><br>
                                               ดึงรายการปิดสิทธิ์ได้: <strong>${totalPulled} รายการ</strong><br>
                                               เพิ่มใหม่: <strong class="text-success">${totalInserted} รายการ</strong><br>
                                               อัปเดตสิทธิ์: <strong class="text-info">${totalUpdated} รายการ</strong>`,
                                        confirmButtonText: 'ตกลง'
                                    }).then(() => {
                                        location.reload();
                                    });
                                })
                                .catch(err => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'บันทึก Log ไม่สำเร็จ',
                                        text: err.message,
                                        confirmButtonText: 'ตกลง'
                                    }).then(() => {
                                        location.reload();
                                    });
                                });
                                return;
                            }

                            const progressBar = document.getElementById('swal-progress-bar');
                            const progressText = document.getElementById('swal-progress-text');
                            const pct = Math.round((index / chunks.length) * 100);

                            if (progressBar) {
                                progressBar.style.width = pct + '%';
                                progressBar.innerText = pct + '%';
                            }
                            if (progressText) {
                                progressText.innerText = `กำลังดึงข้อมูลกลุ่มที่ ${index + 1}/${chunks.length} (ผู้ป่วย ${index * chunkSize} - ${Math.min((index + 1) * chunkSize, cids.length)} จากทั้งหมด ${cids.length} คน)...`;
                            }

                            fetch('{{ url("api/nhso/pull-chunk") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    vstdate: pullDate,
                                    cids: chunks[index]
                                })
                            })
                            .then(r => r.json())
                            .then(res => {
                                totalPulled += res.pulled || 0;
                                totalInserted += res.inserted || 0;
                                totalUpdated += res.updated || 0;
                                runChunk(index + 1);
                            })
                            .catch(err => {
                                console.error('Chunk error:', err);
                                runChunk(index + 1);
                            });
                        }

                        runChunk(0);
                    }
                });
            })
            .catch(err => {
                Swal.fire('เกิดข้อผิดพลาด', err.message, 'error');
            });
    }

    function startFdhManualCheck() {
        const dates = [
            @for ($i = 15; $i >= 1; $i--)
                "{{ date('Y-m-d', strtotime("-$i days")) }}",
            @endfor
        ];

        Swal.fire({
            title: 'กำลังตรวจสอบสถานะการส่งเคลม FDH ย้อนหลัง 15 วัน',
            html: `
                <div class="progress mb-2 mt-2" style="height: 20px;">
                    <div id="swal-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info text-dark" role="progressbar" style="width: 0%;">0%</div>
                </div>
                <div id="swal-progress-text" class="small text-muted text-start">กำลังรวบรวมข้อมูลผู้ป่วยนอกสิทธิ์ UCS ย้อนหลัง 15 วัน...</div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                let allItems = [];
                let dateIndex = 0;

                function fetchNextDate() {
                    if (dateIndex >= dates.length) {
                        if (allItems.length === 0) {
                            fetch('{{ url("api/fdh/log-manual-check") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    checked_days: 15,
                                    updated_claims: 0,
                                    not_found_claims: 0,
                                    errors: 0,
                                    ok: true,
                                    message: 'ตรวจสอบสถานะด้วยตนเองสำเร็จ (ไม่พบสิทธิ์บัตรทอง UCS ที่ต้องเช็คย้อนหลัง)'
                                })
                            }).finally(() => {
                                Swal.fire('ตรวจสอบเสร็จสิ้น', 'ไม่พบสิทธิ์บัตรทอง UCS ที่ต้องเช็คย้อนหลังในช่วง 15 วันนี้', 'info')
                                .then(() => {
                                    location.reload();
                                });
                            });
                            return;
                        }
                        processFdhChunks(allItems);
                        return;
                    }

                    const targetDate = dates[dateIndex];
                    const progressText = document.getElementById('swal-progress-text');
                    if (progressText) {
                        progressText.innerText = `กำลังรวบรวมข้อมูล UCS วันที่ ${targetDate} (${dateIndex + 1}/${dates.length})...`;
                    }

                    fetch(`{{ url("api/fdh/get-check-list") }}?date_start=${targetDate}&date_end=${targetDate}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.items && data.items.length > 0) {
                                allItems = allItems.concat(data.items);
                            }
                            dateIndex++;
                            fetchNextDate();
                        })
                        .catch(err => {
                            console.error('Fetch check list error for date ' + targetDate, err);
                            dateIndex++;
                            fetchNextDate();
                        });
                }

                fetchNextDate();
            }
        });
    }

    function processFdhChunks(items) {
        const chunkSize = 20;
        const chunks = [];
        for (let i = 0; i < items.length; i += chunkSize) {
            chunks.push(items.slice(i, i + chunkSize));
        }

        let totalChecked = 0;
        let totalUpdated = 0;
        let totalNotFound = 0;
        let totalErrors = 0;

        function runChunk(index) {
            if (index >= chunks.length) {
                fetch('{{ url("api/fdh/log-manual-check") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        checked_days: 15,
                        updated_claims: totalUpdated,
                        not_found_claims: totalNotFound,
                        errors: totalErrors,
                        ok: true,
                        message: 'ตรวจสอบสถานะด้วยตนเอง (Manual Check) สำเร็จ'
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP ${response.status}: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(res => {
                    Swal.fire({
                        icon: 'success',
                        title: 'ตรวจสอบเสร็จสมบูรณ์',
                        html: `เช็คสถานะการส่งเคลม FDH ย้อนหลัง 15 วันเสร็จสิ้น!<br>
                               สแกนทั้งสิ้น: <strong>${items.length} รายการ</strong><br>
                               อัปเดตสถานะใหม่: <strong class="text-success">${totalUpdated} รายการ</strong><br>
                               ไม่พบข้อมูล: <strong class="text-warning">${totalNotFound} รายการ</strong><br>
                               พบการตอบกลับผิดพลาด: <strong class="text-danger">${totalErrors} รายการ</strong>`,
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        location.reload();
                    });
                })
                .catch(err => {
                    console.error('Log error:', err);
                    Swal.fire('เกิดข้อผิดพลาดในการบันทึก Log', err.message, 'error').then(() => {
                        location.reload();
                    });
                });
                return;
            }

            const progressBar = document.getElementById('swal-progress-bar');
            const progressText = document.getElementById('swal-progress-text');
            const pct = Math.round((index / chunks.length) * 100);

            if (progressBar) {
                progressBar.style.width = pct + '%';
                progressBar.innerText = pct + '%';
            }
            if (progressText) {
                progressText.innerText = `กำลังเชื่อมต่อกลุ่มที่ ${index + 1}/${chunks.length} (เคส ${index * chunkSize} - ${Math.min((index + 1) * chunkSize, items.length)} จากทั้งหมด ${items.length} รายการ)...`;
            }

            fetch('{{ url("api/fdh/check-chunk") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    items: chunks[index]
                })
            })
            .then(r => r.json())
            .then(res => {
                totalChecked += res.total || 0;
                totalUpdated += res.updated_count || 0;
                totalNotFound += res.not_found_count || 0;
                totalErrors += res.errors_count || 0;
                runChunk(index + 1);
            })
            .catch(err => {
                console.error('FDH Chunk error:', err);
                totalErrors += chunks[index].length;
                runChunk(index + 1);
            });
        }

        runChunk(0);
    }

    function testNotifyConnection() {
        Swal.fire({
            title: 'กำลังทดสอบการเชื่อมต่อ Telegram...',
            html: 'กรุณารอสักครู่ ระบบกำลังทดสอบ Token และส่งข้อความทดสอบไปยัง Telegram',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ url("admin/logs/schedule/notify/test") }}')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'การทดสอบสำเร็จ',
                        text: data.message,
                        confirmButtonText: 'ตกลง'
                    });
                } else if (data.status === 'warning') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'พบข้อเตือนใจ',
                        text: data.message,
                        confirmButtonText: 'ตกลง'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'การทดสอบล้มเหลว',
                        text: data.message,
                        confirmButtonText: 'ตกลง'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการร้องขอ',
                    text: err.message,
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    function startNotifyManualSend() {
        Swal.fire({
            title: 'ยืนยันการส่งรายงานสรุปบริการ?',
            text: 'ระบบจะส่งรายงานสรุปบริการปัจจุบันไปยัง Telegram Chat ID ที่กำหนด',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ส่งรายงานทันที',
            cancelButtonText: 'ยกเลิก',
            customClass: {
                confirmButton: 'text-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังส่งข้อมูลไปยัง Telegram...',
                    html: 'กรุณารอสักครู่ ระบบกำลังรวบรวมรายงานและส่งข้อมูล',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('{{ url("admin/logs/schedule/notify/send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ส่งรายงานสำเร็จ',
                            text: data.message,
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ส่งรายงานล้มเหลว',
                            text: data.message,
                            confirmButtonText: 'ตกลง'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาดในการร้องขอ',
                        text: err.message,
                        confirmButtonText: 'ตกลง'
                    });
                });
            }
        });
    }

    // Copy to Clipboard
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
</script>

<style>
    .nav-pills .nav-link {
        color: #4a5568;
    }
    .nav-pills .nav-link.active {
        background-color: var(--nav-green, #0a4d2c) !important;
        color: #fff !important;
    }
</style>
@endsection
