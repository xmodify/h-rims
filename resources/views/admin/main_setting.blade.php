@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-lg-5">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold">
                <i class="bi bi-gear-fill me-2"></i> ระบบตั้งค่า (Main Setting)
            </h4>
            <small class="text-muted">จัดการการตั้งค่าพื้นฐาน, การเชื่อมต่อระบบภายนอก และการจับคู่รหัสสิทธิ</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger btn-sm px-3 shadow-sm hover-scale" id="gitPullBtn">
                <i class="bi bi-git me-1"></i> Git Pull
            </button>
            <form id="structureForm" method="POST" action="{{ route('admin.up_structure') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm hover-scale" onclick="confirmAction(event)">
                    <i class="bi bi-database-fill-up me-1"></i> Upgrade Structure
                </button>
            </form>
            @if($hospcode === '00025')
                <button type="button" class="btn btn-success btn-sm px-3 shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#sendAOPODModal">
                    <i class="bi bi-send-fill me-1"></i> ส่งข้อมูล AOPOD
                </button>
            @endif
        </div>
    </div>


    <div class="row">
        @php $i = 1; @endphp
        @foreach($groupedData as $category => $settings)
            <div class="col-xl-6 col-lg-12 mb-4">
                <div class="card dash-card accent-{{ $i }}">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0 fw-bold text-color-{{ $i }}">
                            <i class="bi bi-collection-fill me-2"></i> {{ $category }}
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 border-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 text-start ps-3" style="width: 40%;">ชื่อการตั้งค่า</th>
                                        <th class="border-0 text-start">ค่าที่ตั้งไว้</th>
                                        <th class="border-0 text-end pe-3" style="width: 100px;">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($settings as $row)
                                        @php 
                                            $isSensitive = in_array($row->name, ['fdh_pass', 'fdh_secretKey', 'telegram_token', 'opoh_token', 'token_authen_kiosk_nhso', 'telegram_chat_id_register', 'telegram_chat_id_ipdsummary']);
                                        @endphp
                                        <tr>
                                            <td class="ps-3 border-0">
                                                <span class="fw-semibold text-dark">{{ $row->name_th }}</span><br>
                                                <small class="text-muted">{{ $row->name }}</small>
                                            </td>
                                            <td class="border-0">
                                                @if($isSensitive)
                                                    <div class="input-group input-group-sm" style="max-width: 250px;">
                                                        <input type="password" class="form-control border-0 bg-light fw-bold sensitive-input" value="{{ $row->value }}" readonly>
                                                        <button class="btn btn-outline-secondary border-0 btn-peek" type="button">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="badge bg-light text-dark border p-2 px-3 rounded-pill fw-bold">
                                                        {{ $row->value ?: '-' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="pe-3 border-0 text-end">
                                                <button class="btn btn-warning btn-sm btn-edit rounded-pill shadow-sm" 
                                                    data-id="{{ $row->name }}"    
                                                    data-name="{{ $row->name }}"
                                                    data-name-th="{{ $row->name_th }}"
                                                    data-value="{{ $row->value }}"   
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal">
                                                    <i class="bi bi-pencil-square"></i> Edit
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
            @php $i++; if($i > 12) $i = 1; @endphp
        @endforeach
    </div>

    <!-- Scheduled Tasks Guide -->
    <!-- Scheduled Tasks Guide -->
    <div class="card dash-card border-start-0 border-end-0 border-bottom-0 border-top-4 border-dark mb-4 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-clock-history me-2"></i> การตั้งค่า Windows Task Scheduler
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="row align-items-center mb-4">
                <div class="col-auto">
                    <div class="bg-light p-3 rounded-3 border">
                        <span class="text-muted small text-uppercase fw-bold d-block mb-1">Program/script:</span>
                        <code class="h6 mb-0">powershell.exe</code>
                    </div>
                </div>
                <div class="col">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle me-1"></i> คัดลอกคำสั่งด้านล่างไปใส่ในช่อง <b>Add arguments (optional)</b> ของ Task Scheduler ใน Windows
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Task 1: Telegram -->
                <div class="col-xl-3 col-md-6">
                    <div class="p-3 rounded-4 border bg-light h-100">
                        <h6 class="fw-bold text-info mb-2"><i class="bi bi-telegram me-2"></i>Notify Telegram | 08.00 น.</h6>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control border-0 bg-white" value="-WindowStyle Hidden -Command &quot;Invoke-WebRequest -Uri '{{$notify_summary}}' -UseBasicParsing&quot;" readonly id="tg_cmd">
                            <button class="btn btn-info text-white rounded-end" type="button" onclick="copyToClipboard('tg_cmd')">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Task 2: NHSO -->
                <div class="col-xl-3 col-md-6">
                    <div class="p-3 rounded-4 border bg-light h-100">
                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-cloud-download-fill me-2"></i>NhsoEnpoint Pull Yesterday | 00.05 น.</h6>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control border-0 bg-white" value="-WindowStyle Hidden -Command &quot;Invoke-RestMethod -Uri '{{$nhso_endpoint_pull_yesterday}}' -Method Post&quot;" readonly id="nhso_cmd">
                            <button class="btn btn-primary rounded-end" type="button" onclick="copyToClipboard('nhso_cmd')">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Task 3: FDH -->
                <div class="col-xl-3 col-md-6">
                    <div class="p-3 rounded-4 border bg-light h-100">
                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-calendar-check-fill me-2"></i>FDH Pull Last10Days | 00.30 น.</h6>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control border-0 bg-white" value="-WindowStyle Hidden -Command &quot;Invoke-RestMethod -Uri '{{$fdh_check_claim_lastdays}}' -Method Post&quot;" readonly id="fdh_cmd">
                            <button class="btn btn-success rounded-end" type="button" onclick="copyToClipboard('fdh_cmd')">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Task 4: AOPOD -->
                <div class="col-xl-3 col-md-6">
                    <div class="p-3 rounded-4 border bg-light h-100">
                        <h6 class="fw-bold text-danger mb-2"><i class="bi bi-send-fill me-2"></i>AOPOD Send | ทุกชั่วโมง (นาทีที่ 15)</h6>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control border-0 bg-white" value="-WindowStyle Hidden -Command &quot;Invoke-RestMethod -Uri '{{$amnosend}}' -Method Post&quot;" readonly id="aopod_cmd">
                            <button class="btn btn-danger text-white rounded-end" type="button" onclick="copyToClipboard('aopod_cmd')">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="editForm" class="modal-content shadow-lg border-0">
                @csrf @method('PUT')
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-fill me-2"></i> แก้ไขการตั้งค่า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" id="editLabelNameTh"></p>
                    <div class="form-floating mb-3">
                        <input class="form-control shadow-sm" id="editValue" name="value" type="text" placeholder="Value" required>
                        <label for="editValue" class="fw-bold text-muted">ค่าที่ต้องการตั้ง (Value)</label>
                    </div>
                    <div class="alert alert-info border-0 shadow-sm rounded-4 small p-3 mb-0">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                            <div>
                                <strong class="d-block mb-1">คำแนะนำรูปแบบข้อมูล:</strong>
                                <ul class="list-unstyled mb-0 opacity-75">
                                    <li>• <b>ตัวอักษร/ตัวเลข:</b> ใส่เครื่องหมาย <code>""</code> ครอบตัวอักษร (เช่น <code>"S6"</code>, <code>000</code>)</li>
                                    <li>• <b>ตัวเลข:</b> ใส่เลขได้ทันที (เช่น <code>10989</code>, <code>000</code>)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill shadow">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal เลือกช่วงวันที่ AOPOD -->
    <div class="modal fade" id="sendAOPODModal" tabindex="-1" aria-labelledby="sendAOPODLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-send-check-fill me-2"></i> ส่งข้อมูล AOPOD</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="text-muted mb-4">กรุณาเลือกช่วงเวลาที่ต้องการส่งข้อมูลไปยังระบบกลาง</p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">วันที่เริ่มต้น</label>
                            <input type="date" id="start_date" class="form-control border-success shadow-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">วันที่สิ้นสุด</label>
                            <input type="date" id="end_date" class="form-control border-success shadow-sm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-success px-4 rounded-pill shadow" id="sendAOPODBtn">ส่งข้อมูลทันที</button>
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
    .sensitive-input {
        letter-spacing: 2px;
    }
    .btn-peek:hover {
        background-color: #f8fafc;
    }
</style>

<!-- Scripts -->
@push('scripts')
<script>
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

    // Peek Functionalitiy
    document.querySelectorAll('.btn-peek').forEach(btn => {
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

    // Git Pull Logic
    document.getElementById('gitPullBtn').addEventListener('click', function () {
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
                            window.location.href = "{{ route('admin.main_setting') }}";
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
                                window.location.href = "{{ route('admin.main_setting') }}";
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

    // Upgrade Structure Logic
    async function confirmAction(event) {
        event.preventDefault();
        
        const steps = [
            { num: 1, name: "ขั้นตอนที่ 1/5: ตรวจสอบและอัปเกรดโครงสร้างตารางระบบทั้งหมด" },
            { num: 2, name: "ขั้นตอนที่ 2/5: นำเข้า EquipdevAIPN" },
            { num: 3, name: "ขั้นตอนที่ 3/5: นำเข้า lookup_nhso_adp_type" },
            { num: 4, name: "ขั้นตอนที่ 4/5: นำเข้า lookup_nhso_adp_code" },
            { num: 5, name: "ขั้นตอนที่ 5/5: ซิงค์ข้อมูลตั้งค่าหลัก (main_setting)" }
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

            // Update UI before step start
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
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ step: step.num })
                });

                const data = await response.json();
                
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

        // Final UI state
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

    // Modal Edit Data Binding
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const nameTh = this.dataset.nameTh;
            let value = this.dataset.value;    
            
            document.getElementById('editLabelNameTh').innerHTML = `<i class="bi bi-tag-fill me-2"></i> ${nameTh} (<code>${name}</code>)`;
            document.getElementById('editValue').value = value;
            document.getElementById('editForm').action = "{{ url('admin/main_setting') }}/" + name;
        });
    });

    // AOPOD Sending Logic
    document.getElementById('sendAOPODBtn').addEventListener('click', function() {
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;

        if (!start || !end) {
            Swal.fire({ icon: 'warning', title: 'กรุณาเลือกวันที่ให้ครบ', confirmButtonText: 'ตกลง' });
            return;
        }

        Swal.fire({
            title: 'ยืนยันการส่งข้อมูล?',
            text: `ช่วงวันที่ ${start} ถึง ${end}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ส่งข้อมูล',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#28a745'
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
                    title: 'กำลังส่งข้อมูล...',
                    html: `
                        <div id="aopods-progress-text" class="mb-2">กำลังเตรียมข้อมูลและแบ่งช่วงเวลา...</div>
                        <div class="progress" style="height: 20px;">
                            <div id="aopods-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
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
                        progressText.innerHTML = `กำลังส่งข้อมูลช่วงที่ ${i + 1}/${totalPeriods}<br>(${period.start} ถึง ${period.end})`;
                    }
                    if (progressBar) {
                        progressBar.style.width = `${percent}%`;
                        progressBar.innerHTML = `${percent}%`;
                        progressBar.setAttribute('aria-valuenow', percent);
                    }

                    try {
                        let response = await fetch(`{{ url('api/amnosend') }}?start_date=${period.start}&end_date=${period.end}`, {
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
                                failedPeriods.push(`${period.start} ถึง ${period.end} (มีช่วงที่ล้มเลว)`);
                            }
                        } catch (e) {
                            overallSuccess = false;
                            failedPeriods.push(`${period.start} ถึง ${period.end} (ข้อมูลตอบกลับไม่ถูกต้อง)`);
                        }
                    } catch (error) {
                        overallSuccess = false;
                        failedPeriods.push(`${period.start} ถึง ${period.end} (${error.message || error})`);
                    }
                }

                // Final update to progress bar
                const progressBar = document.getElementById('aopods-progress-bar');
                if (progressBar) {
                    progressBar.style.width = '100%';
                    progressBar.innerHTML = '100%';
                    progressBar.setAttribute('aria-valuenow', 100);
                }

                // Show final result
                const summaryText = `
                    <div class="text-start p-2">
                        <b>สถานะ:</b> ${overallSuccess ? '✅ สำเร็จทั้งหมด' : '⚠️ เสร็จสิ้นแต่มีข้อผิดพลาดบางส่วน'}<br>
                        <b>ช่วงวันที่ส่งจริง:</b> ${start} ถึง ${end}<br>
                        ${failedPeriods.length > 0 ? `<b class="text-danger">ช่วงข้อมูลที่ล้มเหลว:</b><br><ul class="text-danger">${failedPeriods.map(p => `<li>${p}</li>`).join('')}</ul>` : ''}
                        <hr>
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
                    confirmButtonText: 'ปิด'
                });
            }
        });
    });

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