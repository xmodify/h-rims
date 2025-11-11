@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <button class="btn btn-danger" id="gitPullBtn" style="display: inline;">Git Pull</button>   
    <form id="structureForm" method="POST" action="{{ route('admin.up_structure') }}" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-primary" onclick="confirmAction(event)">Upgrade Structure</button>
    </form>
    <!-- ปุ่มใหม่: ส่งข้อมูล AOPOD -->
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#sendAOPODModal">
        ส่งข้อมูล AOPOD
    </button>
    <pre id="gitOutput" style="background: #eeee; padding: 1rem; margin-top: 1rem;"></pre>
    
    <h3 class="text-primary">Main Setting</h3>
    <!-- ตาราง -->
    <table class="table table-bordered">
        <thead class="table-primary">
            <tr>
                <th>Name</th>
                <th>Value</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    <td>{{ $row->name_th }}</td>
                    <td>{{ $row->value }}</td>
                    <td>
                        <!-- ปุ่ม Edit -->
                        <button class="btn btn-warning btn-sm btn-edit" 
                            data-id="{{ $row->id }}"    
                            data-value="{{ $row->value }}"   
                            data-bs-toggle="modal"
                            data-bs-target="#editModal">
                            Edit
                        </button>
                    </td>
                </tr>
            @endforeach

        </tbody>
    </table>
    <div class="text-primary">NonifyTelegram เรียกผ่าน Task Scheduler Windows <br>
        (Program/script: powershell.exe) <br>
        แจ้งเตือน Summary | Add argumenta: -Command "Invoke-WebRequest {{$notify_summary}}"<br>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="editForm" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input class="form-control mb-2" id="editValue" name="value" type="text"  required>                   
                </div>
  
                <div class="modal-footer">
                    <button class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
    <br>
    <!-- Modal เลือกช่วงวันที่ -->
    <div class="modal fade" id="sendAOPODModal" tabindex="-1" aria-labelledby="sendAOPODLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header bg-success text-white">
            <h5 class="modal-title">ส่งข้อมูล AOPOD</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <div class="mb-3">
            <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
            <input type="date" id="start_date" class="form-control" required>
            </div>
            <div class="mb-3">
            <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
            <input type="date" id="end_date" class="form-control" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-success" id="sendAOPODBtn">ส่งข้อมูล</button>
        </div>
        </div>
    </div>
    </div>
    <br>
    <!-- แจ้ง Git Pull -->
    <script>
        document.getElementById('gitPullBtn').addEventListener('click', function () {
            if (!confirm("คุณแน่ใจว่าจะ Git Pull ใช่ไหม?")) return;

            let outputBox = document.getElementById('gitOutput');
            outputBox.textContent = 'กำลังดำเนินการ...';

            fetch("{{ route('admin.git.pull') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
            })
            .then(response => response.json())
            .then(data => {
                outputBox.textContent = data.output || data.error || 'ไม่มีข้อมูล';
                // ตรวจสอบว่า git pull สำเร็จหรือไม่
                if (data.output && data.output.includes('Updating') || data.output.includes('Already up to date')) {
                    setTimeout(() => {
                        window.location.href = "{{ route('admin.main_setting') }}"; // เปลี่ยนเป็น route ที่คุณต้องการ redirect ไป
                    }, 5000); // รอ 5 วินาทีก่อน redirect
                }
            })
            .catch(error => {
                outputBox.textContent = "เกิดข้อผิดพลาด: " + error;
            });
        });
    </script>

    <!-- SweetAlert สำหรับ ปรับโครงสร้าง -->
    <script>
        function confirmAction(event) {
            event.preventDefault(); // ป้องกัน submit ทันที

            Swal.fire({
                title: 'ยืนยันการดำเนินการ?',
                text: "คุณต้องการ Upgrade Structure หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ใช่, ดำเนินการ!',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('structureForm').submit(); // submit ฟอร์ม
                }
            });
        }
    </script>

    <!-- SweetAlert สำหรับ Success -->
    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif

    <!-- JavaScript  Set ข้อมูลใน Edit Modal-->
    <script>        
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const value = this.dataset.value;                

                document.getElementById('editValue').value = value;
                document.getElementById('editForm').action = `/admin/main_setting/${id}`;
            });
        });
    </script>

    <!-- JavaScript ส่งข้อมูล AOPOD-->
    <script>
        document.getElementById('sendAOPODBtn').addEventListener('click', function() {
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;

            if (!start || !end) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาเลือกวันที่ให้ครบ',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            Swal.fire({
                title: 'ยืนยันการส่งข้อมูล?',
                text: `ช่วงวันที่ ${start} ถึง ${end}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ส่งข้อมูล',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {

                    // 🌀 แสดงสถานะหมุนระหว่างรอ
                    Swal.fire({
                        title: 'กำลังส่งข้อมูล...',
                        html: 'กรุณารอสักครู่',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`{{ url('api/amnosend') }}?start_date=${start}&end_date=${end}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(async response => {
                        const text = await response.text();
                        try {
                            const data = JSON.parse(text);

                            // ✅ แสดงเฉพาะข้อมูลสำคัญ
                            const summaryText = `
                                <b>Hospcode:</b> ${data.hospcode}<br>
                                <b>ช่วงวันที่:</b> ${data.start_date} ถึง ${data.end_date}<br>
                                <b>สถานะ:</b> ${data.ok ? '✅ สำเร็จ' : '❌ ล้มเหลว'}<br>
                                <b>จำนวนข้อมูลที่ส่ง:</b><br>
                                - OPD: ${data.received.opd}<br>
                                - IPD: ${data.received.ipd}<br>
                                - IPD Bed: ${data.received.ipd_bed}<br>
                                - Hospital: ${data.received.hospital}
                            `;

                            Swal.fire({
                                icon: data.ok ? 'success' : 'warning',
                                title: 'ส่งข้อมูลสำเร็จ ✅',
                                html: summaryText,
                                confirmButtonText: 'ปิด',  // 🟢 เพิ่มปุ่มปิด
                                showConfirmButton: true
                            });

                        } catch (e) {
                            Swal.fire({
                                icon: 'info',
                                title: 'ผลการทำงาน',
                                html: `<pre style="text-align:left;white-space:pre-wrap;">${text}</pre>`,
                                confirmButtonText: 'ปิด',
                                showConfirmButton: true
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: error,
                            confirmButtonText: 'ปิด'
                        });
                    });
                }
            });
        });
    </script>

</div>

@endsection