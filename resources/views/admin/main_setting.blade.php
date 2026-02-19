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

    <!-- Telegram Notification Guide -->
    <div class="card dash-card mb-4 border-start-0 border-end-0 border-bottom-0 border-top-4 border-info">
        <div class="card-body">
            <h6 class="fw-bold text-info mb-3">
                <i class="bi bi-info-circle-fill me-2"></i> Nonify Telegram via Task Scheduler Windows
            </h6>
            <div class="bg-light p-3 rounded-3 border">
                <p class="mb-2"><strong>Program/script:</strong> <code>powershell.exe</code></p>
                <div class="mb-0">
                    <strong>Add arguments:</strong>
                    <div class="input-group mt-1">
                        <input type="text" class="form-control form-control-sm bg-white" value='-Command "Invoke-WebRequest {{$notify_summary}}"' readonly id="tg_cmd">
                        <button class="btn btn-outline-info btn-sm" type="button" onclick="copyToClipboard('tg_cmd')">
                            <i class="bi bi-copy"></i> คัดลอก
                        </button>
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
                            if (isSuccess && data.output.includes('Updating')) {
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
    function confirmAction(event) {
        event.preventDefault();
        Swal.fire({
            title: 'อัปเกรดโครงสร้างฐานข้อมูล?',
            text: "คุณต้องการ Upgrade Structure หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ดำเนินการ!',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังดำเนินการ...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                document.getElementById('structureForm').submit();
            }
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
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังส่งข้อมูล...',
                    html: 'กรุณารอสักครู่ ระบบกำลังประมวลผลข้อมูลขนาดใหญ่',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch(`{{ url('api/amnosend') }}?start_date=${start}&end_date=${end}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(async response => {
                    const text = await response.text();
                    try {
                        const data = JSON.parse(text);
                        const summaryText = `
                            <div class="text-start p-2">
                                <b>สถานะ:</b> ${data.ok ? '✅ สำเร็จ' : '❌ ล้มเหลว'}<br>
                                <b>ช่วงวันที่:</b> ${data.start_date} ถึง ${data.end_date}<br><hr>
                                <b>สรุปจำนวนข้อมูล:</b><br>
                                <ul class="mb-0">
                                    <li>OPD: <span class="badge bg-primary">${data.received.opd}</span></li>
                                    <li>IPD: <span class="badge bg-primary">${data.received.ipd}</span></li>
                                    <li>IPD Bed: <span class="badge bg-primary">${data.received.ipd_bed}</span></li>
                                    <li>Hospital: <span class="badge bg-primary">${data.received.hospital}</span></li>
                                </ul>
                            </div>
                        `;
                        Swal.fire({
                            icon: data.ok ? 'success' : 'warning',
                            title: 'การส่งข้อมูล AOPOD',
                            html: summaryText,
                            confirmButtonText: 'ปิด'
                        });
                    } catch (e) {
                        Swal.fire({
                            icon: 'info',
                            title: 'ผลการทำงาน',
                            html: `<pre class="text-start bg-light p-2 small border" style="white-space:pre-wrap;">${text}</pre>`,
                            confirmButtonText: 'ปิด'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: error });
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