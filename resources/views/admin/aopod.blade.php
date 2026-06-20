@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-lg-5">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="mb-0 text-success fw-bold">
                <i class="bi bi-send-fill me-2"></i> ข้อมูล AOPOD
            </h4>
            <small class="text-muted">ส่งข้อมูล AOPOD แบบกำหนดเอง, ทดสอบสถานะเชื่อมต่อ และดูประวัติการส่งข้อมูลย้อนหลัง</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-success btn-sm px-3 shadow-sm hover-scale" onclick="testAopodConnection()">
                <i class="bi bi-patch-check-fill me-1"></i> ทดสอบการเชื่อมต่อ API
            </button>
            <a href="" class="btn btn-outline-secondary btn-sm px-3 shadow-sm hover-scale">
                <i class="bi bi-arrow-clockwise me-1"></i> โหลดข้อมูลใหม่
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Control Card -->
        <div class="col-xl-4 col-lg-12 mb-4">
            <div class="card dash-card accent-10 border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="bi bi-sliders me-2"></i> จัดการส่งข้อมูล (Manual Send)
                    </h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">เลือกช่วงเวลาที่ต้องการประมวลผลและส่งข้อมูลบริการ (OPD/IPD) ไปยังระบบกลาง AOPOD โดยระบบจะแบ่งข้อมูลเป็นช่วงละ 10 วันโดยอัตโนมัติเพื่อลดภาระงานของเซิร์ฟเวอร์</p>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">วันที่เริ่มต้น</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-success-subtle text-success"><i class="bi bi-calendar-event"></i></span>
                                <input type="hidden" id="start_date" name="start_date">
                                <input type="text" class="form-control border-success-subtle shadow-sm datepicker_th" id="start_date_display" readonly required placeholder="เลือกวันที่">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">วันที่สิ้นสุด</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-success-subtle text-success"><i class="bi bi-calendar-event"></i></span>
                                <input type="hidden" id="end_date" name="end_date">
                                <input type="text" class="form-control border-success-subtle shadow-sm datepicker_th" id="end_date_display" readonly required placeholder="เลือกวันที่">
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success w-100 py-2.5 rounded-pill shadow hover-scale fw-bold" id="sendAOPODBtn">
                        <i class="bi bi-send-check-fill me-2"></i> ส่งข้อมูลระบุช่วงวันที่
                    </button>
                    <button type="button" class="btn btn-outline-success w-100 py-2.5 rounded-pill shadow-sm hover-scale fw-bold mt-2" onclick="startAopodManualSend()">
                        <i class="bi bi-play-circle-fill me-2"></i> ส่งข้อมูลตามตารางเวลาทันที
                    </button>

                    <hr class="my-4 opacity-10">

                    <!-- Windows Task Scheduler Guide -->
                    <div class="bg-light p-3 rounded-4 border">
                        <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-clock-history me-1 text-success"></i> Windows Task Scheduler (AOPOD Send)</h6>
                        <p class="text-muted" style="font-size: 11px;">ส่งอัตโนมัติทุกชั่วโมง (นาทีที่ 15) | Program: <code>powershell.exe</code> | Arguments:</p>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control border-secondary bg-white text-muted" style="font-size: 11px;" value="-WindowStyle Hidden -Command &quot;Invoke-RestMethod -Uri '{{ $amnosend }}' -Method Post&quot;" readonly id="aopod_cmd">
                            <button class="btn btn-success text-white" type="button" onclick="copyToClipboard('aopod_cmd')">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table Card -->
        <div class="col-xl-8 col-lg-12 mb-4">
            <div class="card dash-card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="bi bi-clock-history me-2"></i> ประวัติการทำงาน (AOPOD Logs)
                    </h6>
                    <span class="badge bg-success-subtle text-success px-3 py-1.5 rounded-pill small">ทั้งหมด {{ count($aopodLogs) }} รายการล่าสุด</span>
                </div>
                <div class="card-body p-3">
                    @if(count($aopodLogs) > 0)
                        <div class="table-responsive">
                            <table id="aopodLogsTable" class="table table-hover align-middle mb-0 border-0 w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 180px;" class="border-0">เวลาบันทึก</th>
                                        <th style="width: 120px;" class="border-0">สถานะ</th>
                                        <th class="border-0 text-start">รายละเอียดผลลัพธ์</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($aopodLogs as $log)
                                        <tr>
                                            <td class="fw-bold text-secondary text-nowrap ps-3">{{ $log['timestamp'] ? DatetimeThai($log['timestamp']) : 'N/A' }}</td>
                                            <td>
                                                @if(isset($log['data']['ok']) && $log['data']['ok'] === true)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill">สำเร็จ</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill">ล้มเหลว</span>
                                                @endif
                                            </td>
                                            <td class="text-start pe-3">
                                                @if($log['data'])
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-dark">
                                                            ส่งข้อมูลวันที่ {{ isset($log['data']['start_date']) ? DateThai($log['data']['start_date']) : '-' }} ถึง {{ isset($log['data']['end_date']) ? DateThai($log['data']['end_date']) : '-' }} (รหัสรพ. {{ $log['data']['hospcode'] ?? '-' }})
                                                        </span>
                                                        <small class="text-muted mt-1">
                                                            จำนวนแถวข้อมูล: 
                                                            OPD <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill">{{ $log['data']['received']['opd'] ?? 0 }}</span> | 
                                                            IPD <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 rounded-pill">{{ $log['data']['received']['ipd'] ?? 0 }}</span> | 
                                                            IPD Bed <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill">{{ $log['data']['received']['ipd_bed'] ?? 0 }}</span> | 
                                                            Hospital <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5 rounded-pill">{{ $log['data']['received']['hospital'] ?? 0 }}</span>
                                                        </small>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">{{ $log['raw'] }}</span>
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
                            ยังไม่มีประวัติการส่งข้อมูล AOPOD ในระบบ (No logs found)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-scale {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-scale:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
    }
</style>
@endsection

@push('scripts')
<script>
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

    $(document).ready(function () {
        $('.datepicker_th').datepicker({
            format: 'd M yyyy',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            zIndexOffset: 1050
        });

        $('.datepicker_th').on('changeDate', function(e) {
            var date = e.date;
            var targetId = $(this).attr('id').replace('_display', '');
            var hiddenInput = $('#' + targetId);
            
            if(date) {
                var day = ("0" + date.getDate()).slice(-2);
                var month = ("0" + (date.getMonth() + 1)).slice(-2);
                var year = date.getFullYear(); // Gregorian
                hiddenInput.val(year + "-" + month + "-" + day);
            } else {
                hiddenInput.val('');
            }
        });

        $('#aopodLogsTable').DataTable({
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
        });
    });

    function testAopodConnection() {
        Swal.fire({
            title: 'กำลังทดสอบการเชื่อมต่อ AOPOD...',
            html: 'กรุณารอสักครู่ ระบบกำลังทดสอบการเชื่อมต่อกับเซิร์ฟเวอร์ AOPOD',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ route("admin.logs.schedule.aopod.test") }}')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'การทดสอบสำเร็จ',
                        text: data.message,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#198754'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'การทดสอบล้มเหลว',
                        text: data.message,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#dc3545'
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

    function formatThaiDate(dateStr) {
        if (!dateStr) return '';
        const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const year = parseInt(parts[0], 10) + 543;
        const month = months[parseInt(parts[1], 10) - 1];
        const day = parseInt(parts[2], 10);
        return `${day} ${month} ${year}`;
    }

    document.getElementById('sendAOPODBtn').addEventListener('click', function() {
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;
        const startDisplay = document.getElementById('start_date_display').value;
        const endDisplay = document.getElementById('end_date_display').value;

        if (!start || !end) {
            Swal.fire({ icon: 'warning', title: 'กรุณาเลือกวันที่ให้ครบ', confirmButtonText: 'ตกลง', confirmButtonColor: '#28a745' });
            return;
        }

        Swal.fire({
            title: 'ยืนยันการส่งข้อมูล AOPOD?',
            text: `ช่วงวันที่ ${startDisplay} ถึง ${endDisplay}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ส่งข้อมูล',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d'
        }).then(async (result) => {
            if (result.isConfirmed) {
                // Helper to split date range into chunks of 10 days
                function getPeriods(startDateStr, endDateStr, daysPerPeriod = 10) {
                    let start = new Date(startDateStr);
                    let end = new Date(endDateStr);
                    let periods = [];
                    
                    let currentStart = new Date(start);
                    while (currentStart <= end) {
                        let currentEnd = new Date(currentStart);
                        currentEnd.setDate(currentEnd.getDate() + daysPerPeriod - 1);
                        if (currentEnd > end) {
                            currentEnd = new Date(end);
                        }
                        
                        periods.push({
                            start: currentStart.toISOString().split('T')[0],
                            end: currentEnd.toISOString().split('T')[0]
                        });
                        
                        currentStart = new Date(currentEnd);
                        currentStart.setDate(currentStart.getDate() + 1);
                    }
                    return periods;
                }

                const periods = getPeriods(start, end, 10);
                const totalPeriods = periods.length;

                Swal.fire({
                    title: 'กำลังส่งข้อมูล AOPOD...',
                    html: `
                        <div id="aopods-progress-text" class="mb-2">กำลังเตรียมข้อมูลและแบ่งช่วงเวลา...</div>
                        <div class="progress" style="height: 20px; border-radius: 10px;">
                            <div id="aopods-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                let opdTotal = 0;
                let ipdTotal = 0;
                let ipdBedTotal = 0;
                let hospitalTotal = 0;
                let failedPeriods = [];
                let overallSuccess = true;

                for (let i = 0; i < totalPeriods; i++) {
                    const period = periods[i];
                    const percent = Math.round((i / totalPeriods) * 100);

                    // Update UI
                    const progressText = document.getElementById('aopods-progress-text');
                    const progressBar = document.getElementById('aopods-progress-bar');
                    if (progressText) {
                        progressText.innerHTML = `กำลังส่งข้อมูลช่วงที่ ${i + 1}/${totalPeriods}<br>(${formatThaiDate(period.start)} ถึง ${formatThaiDate(period.end)})`;
                    }
                    if (progressBar) {
                        progressBar.style.width = `${percent}%`;
                        progressBar.innerHTML = `${percent}%`;
                        progressBar.setAttribute('aria-valuenow', percent);
                    }

                    try {
                        let response = await fetch(`{{ url('api/amnosend') }}?start_date=${period.start}&end_date=${period.end}&log=false`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        });

                        const text = await response.text();
                        try {
                            const data = JSON.parse(text);
                            if (data.ok) {
                                opdTotal += data.received.opd || 0;
                                ipdTotal += data.received.ipd || 0;
                                ipdBedTotal += data.received.ipd_bed || 0;
                                hospitalTotal += data.received.hospital || 0;
                            } else {
                                overallSuccess = false;
                                failedPeriods.push(`${formatThaiDate(period.start)} ถึง ${formatThaiDate(period.end)} (มีช่วงที่ล้มเลว)`);
                            }
                        } catch (e) {
                            overallSuccess = false;
                            failedPeriods.push(`${formatThaiDate(period.start)} ถึง ${formatThaiDate(period.end)} (ข้อมูลตอบกลับไม่ถูกต้อง)`);
                        }
                    } catch (error) {
                        overallSuccess = false;
                        failedPeriods.push(`${formatThaiDate(period.start)} ถึง ${formatThaiDate(period.end)} (${error.message || error})`);
                    }
                }

                // Final update to progress bar
                const progressBar = document.getElementById('aopods-progress-bar');
                if (progressBar) {
                    progressBar.style.width = '100%';
                    progressBar.innerHTML = '100%';
                    progressBar.setAttribute('aria-valuenow', 100);
                }

                // Save log summary to backend
                try {
                    await fetch('{{ route("admin.aopod.log-summary") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            start_date: start,
                            end_date: end,
                            ok: overallSuccess,
                            opd: opdTotal,
                            ipd: ipdTotal,
                            ipd_bed: ipdBedTotal,
                            hospital: hospitalTotal
                        })
                    });
                } catch (e) {
                    console.error('Failed to log summary:', e);
                }

                // Show final result
                const summaryText = `
                    <div class="text-start p-2">
                        <b>สถานะ:</b> ${overallSuccess ? '<span class="text-success fw-bold">✅ สำเร็จทั้งหมด</span>' : '<span class="text-warning fw-bold">⚠️ เสร็จสิ้นแต่มีข้อผิดพลาดบางส่วน</span>'}<br>
                        <b>ช่วงวันที่ส่งจริง:</b> ${startDisplay} ถึง ${endDisplay}<br>
                        ${failedPeriods.length > 0 ? `<b class="text-danger">ช่วงข้อมูลที่ล้มเหลว:</b><br><ul class="text-danger">${failedPeriods.map(p => `<li>${p}</li>`).join('')}</ul>` : ''}
                        <hr class="my-2">
                        <b>สรุปจำนวนข้อมูลที่ถูกส่งสำเร็จ:</b><br>
                        <ul class="mb-0">
                            <li>OPD: <span class="badge bg-primary">${opdTotal}</span></li>
                            <li>IPD: <span class="badge bg-primary">${ipdTotal}</span></li>
                            <li>IPD Bed: <span class="badge bg-primary">${ipdBedTotal}</span></li>
                            <li>Hospital: <span class="badge bg-primary">${hospitalTotal}</span></li>
                        </ul>
                    </div>
                `;

                Swal.fire({
                    icon: overallSuccess ? 'success' : 'warning',
                    title: 'การส่งข้อมูล AOPOD เสร็จสิ้น',
                    html: summaryText,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    location.reload();
                });
            }
        });
    });

    function startAopodManualSend() {
        Swal.fire({
            title: 'ยืนยันการส่งข้อมูล AOPOD?',
            text: 'ระบบจะเริ่มประมวลผลข้อมูลบริการและส่งข้อมูลย้อนหลัง 10 วัน (นับจากวันนี้) ไปยังเซิร์ฟเวอร์ AOPOD ตามที่ตาราง Schedule กำหนดไว้',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'เริ่มส่งข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังส่งข้อมูล AOPOD...',
                    html: 'กรุณารอสักครู่ ระบบกำลังประมวลผลและอัปโหลดข้อมูล',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('{{ route("admin.logs.schedule.aopod.send") }}', {
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
                            title: 'ส่งข้อมูลสำเร็จ',
                            text: data.message,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#198754'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ส่งข้อมูลล้มเหลว',
                            text: data.message,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#dc3545'
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
</script>
@endpush
