@extends('layouts.app')

@section('content')
<div class="container">
    
        <h3 class="text-primary">ปีงบประมาณ</h3>
        <!-- ปุ่มเปิด Modal เพิ่ม -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
            ➕ เพิ่ม ปีงบประมาณ
        </button>

        <!-- ตาราง -->
        <table class="table table-bordered" id="data">
            <thead class="table-primary">
                <tr>
                    <th class="text-center">ปีงบประมาณ</th>
                    <th class="text-center">ชื่อปีงบประมาณ</th>
                    <th class="text-center">วันที่เริ่ม</th>
                    <th class="text-center">วันที่สิ้นสุด</th> 
                    <th class="text-center" width = "20%">Action</th>                
                </tr>
            </thead>
            <tbody>
                @if(!empty($budget_year))
                    @foreach ($budget_year as $item)
                        <tr>
                            <td class="text-center">{{ $item->LEAVE_YEAR_ID }}</td>
                            <td class="text-center">{{ $item->LEAVE_YEAR_NAME }}</td>
                            <td class="text-center">{{ $item->DATE_BEGIN }}</td>
                            <td class="text-center">{{ $item->DATE_END }}</td>
                            <td>
                                <!-- ปุ่ม Edit -->
                                <button type="button" class="btn btn-warning btn-sm btn-edit"
                                    data-leave-year-id="{{ $item->LEAVE_YEAR_ID }}"
                                    data-leave-year-name="{{ $item->LEAVE_YEAR_NAME }}"
                                    data-date-begin="{{ $item->DATE_BEGIN }}"
                                    data-date-end="{{ $item->DATE_END }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal">
                                    Edit
                                </button>

                                <!-- ปุ่ม Delete -->
                                <form class="d-inline delete-form" method="POST" action="{{ route('admin.budget_year.destroy', $item) }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-sm btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table> 
    
        <!-- Modal Create -->
        <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.budget_year.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มปีงบประมาณ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label">ปีงบประมาณ</label>
                    <input class="form-control mb-2" name="LEAVE_YEAR_ID" type="text" placeholder="เช่น 2567" required>

                    <label class="form-label">ชื่อปีงบประมาณ</label>
                    <input class="form-control mb-2" name="LEAVE_YEAR_NAME" type="text" placeholder="เช่น ปีงบประมาณ 2567" required>

                    <label class="form-label">วันที่เริ่ม</label>
                    <input class="form-control mb-2" name="DATE_BEGIN" type="date" required>

                    <label class="form-label">วันที่สิ้นสุด</label>
                    <input class="form-control mb-2" name="DATE_END" type="date" required>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Save</button>
                </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" id="editForm" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขปีงบประมาณ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <!-- ไม่ต้อง if เพราะค่าจะถูกใส่ด้วย JS -->
                    <label class="form-label mb-1">ปีงบประมาณ</label>
                    <input class="form-control mb-2" id="eLEAVE_YEAR_ID" name="LEAVE_YEAR_ID" type="text" readonly>

                    <label class="form-label mb-1">ชื่อปีงบประมาณ</label>
                    <input class="form-control mb-2" id="eLEAVE_YEAR_NAME" name="LEAVE_YEAR_NAME" type="text">

                    <label class="form-label mb-1">วันที่เริ่ม</label>
                    <input class="form-control mb-2" id="eDATE_BEGIN" name="DATE_BEGIN" type="date">

                    <label class="form-label mb-1">วันที่สิ้นสุด</label>
                    <input class="form-control mb-2" id="eDATE_END" name="DATE_END" type="date">
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Update</button>
                </div>
                </form>
            </div>
        </div>

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

        <!-- JavaScript -->
        <script>
            // ผูกอีเวนต์กับปุ่มทุกปุ่ม
            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function () {
                    // อ่านค่าจาก data-* (ใช้รูปแบบ camelCase ของ dataset)
                    const leaveYearId   = this.dataset.leaveYearId;
                    const leaveYearName = this.dataset.leaveYearName;
                    const dateBegin     = this.dataset.dateBegin; // ควรเป็นรูปแบบ YYYY-MM-DD
                    const dateEnd       = this.dataset.dateEnd;

                    // เซ็ตค่าลง input
                    document.getElementById('eLEAVE_YEAR_ID').value   = leaveYearId;
                    document.getElementById('eLEAVE_YEAR_NAME').value = leaveYearName;
                    document.getElementById('eDATE_BEGIN').value      = dateBegin;
                    document.getElementById('eDATE_END').value        = dateEnd;

                    // เซ็ต action ให้ form เป็นเส้นทางอัปเดต
                    document.getElementById('editForm').action = `/admin/budget_year/${leaveYearId}`;
                });
            });

            // SweetAlert ยืนยันลบ
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function () {
                    const form = this.closest('form');
                    Swal.fire({
                        title: 'Are you sure?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then(result => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        </script>
 
</div>
@endsection
