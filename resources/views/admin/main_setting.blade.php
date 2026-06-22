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
        </div>
    </div>


    <div class="row">
        @php $i = 1; @endphp
        @foreach($groupedData as $category => $settings)
            <div class="col-xl-6 col-lg-12 mb-4">
                <div class="card dash-card accent-{{ $i }}">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-color-{{ $i }}">
                            <i class="bi bi-collection-fill me-2"></i> {{ $category }}
                        </h6>
                        @if($category === 'Claim (FDH)')
                            <button type="button" class="btn btn-outline-info btn-sm px-3 rounded-pill shadow-sm hover-scale" id="testFdhUserBtn">
                                <i class="bi bi-shield-lock-fill me-1"></i> ทดสอบดึง Token
                            </button>
                        @endif
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

    // Test FDH User Token Logic
    const testFdhUserBtn = document.getElementById('testFdhUserBtn');
    if (testFdhUserBtn) {
        testFdhUserBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'กำลังทดสอบการเชื่อมต่อ...',
                text: 'กรุณารอสักครู่ ระบบกำลังขอ Token จาก FDH',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('{{ url("api/fdh/testtoken") }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.token) {
                    Swal.fire({
                        icon: 'success',
                        title: 'เชื่อมต่อสำเร็จ',
                        html: `
                            <div class="text-start p-2">
                                <p class="mb-2 text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ดึง Access Token สำเร็จ</p>
                                <div class="bg-light p-3 small border rounded-3" style="word-break: break-all; font-family: monospace; max-height: 150px; overflow-y: auto;">
                                    ${data.token}
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#0a4d2c'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เชื่อมต่อล้มเหลว',
                        text: 'ไม่สามารถขอ Access Token จาก FDH ได้ กรุณาตรวจสอบความถูกต้องของ FDH User, Pass, Secret Key และ Hospital Code ในการตั้งค่า',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message || error,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#d33'
                });
            });
        });
    }

    // Upgrade Structure Logic
    async function confirmAction(event) {
        event.preventDefault();
        
        const steps = [
            { num: 1, name: "ขั้นตอนที่ 1/3: ตรวจสอบและอัปเกรดโครงสร้างตารางระบบทั้งหมด" },
            { num: 2, name: "ขั้นตอนที่ 2/3: นำเข้า/ซิงค์ข้อมูล Lookup (EquipdevAIPN, adp_type, adp_code, subinscl)" },
            { num: 3, name: "ขั้นตอนที่ 3/3: ซิงค์ข้อมูลตั้งค่าหลัก (main_setting)" }
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