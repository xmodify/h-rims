@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-people-fill text-primary me-2"></i> User Management
            </h4>
            <p class="text-muted small mb-0">จัดการข้อมูลผู้ใช้งานระบบและกำหนดสิทธิการเข้าถึง</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success px-4 shadow-sm hover-scale rounded-pill" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add New User
            </button>
        </div>
    </div>

    <!-- User Table Card -->
    <div class="dash-card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="userTable">
                    <thead class="bg-light text-primary border-bottom">
                        <tr>
                            <th class="ps-4">ชื่อ - นามสกุล</th>
                            <th>อีเมล (Email)</th>
                            <th class="text-center">สถานะใช้งาน</th>
                            <th class="text-center">ประเภทผู้ใช้</th>
                            <th class="text-center pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box icon-bg-2 me-3 mb-0" style="width: 35px; height: 35px; border-radius: 50%;">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <span class="fw-bold">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td class="text-center">
                                    @if($user->active === 'Y')
                                        <span class="badge rounded-pill bg-success-subtle text-success px-3">
                                            <i class="bi bi-check-circle-fill me-1"></i> Active
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-danger-subtle text-danger px-3">
                                            <i class="bi bi-x-circle-fill me-1"></i> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill {{ $user->status === 'admin' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} px-3">
                                        {{ strtoupper($user->status) }}
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <button class="btn btn-white btn-sm px-3 btn-edit border-end" 
                                            data-id="{{ $user->id }}"
                                            data-name="{{ $user->name }}"
                                            data-email="{{ $user->email }}"
                                            data-active="{{ $user->active }}"
                                            data-status="{{ $user->status }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            title="แก้ไข">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <form class="d-inline delete-form" method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-white btn-sm px-3 btn-delete" title="ลบ">
                                                <i class="bi bi-trash3 text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Create -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.users.store') }}" class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header bg-success text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill me-2"></i> Create New User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                            <input name="name" type="text" class="form-control bg-light border-start-0 ps-0" placeholder="กรอกชื่อ-นามสกุล" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                            <input name="email" type="email" class="form-control bg-light border-start-0 ps-0" placeholder="example@mail.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Initial Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-key"></i></span>
                            <input name="password" type="password" class="form-control bg-light border-start-0 ps-0" placeholder="ไม่ต่ำกว่า 6 ตัวอักษร" required minlength="6">
                        </div>
                    </div>
                    <input type="hidden" name="active" value="Y">
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success px-4 rounded-pill">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="editForm" class="modal-content border-0 shadow-lg">
                @csrf @method('PUT')
                <div class="modal-header bg-primary text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i> Edit User Information
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                            <input class="form-control bg-light border-start-0 ps-0" id="editName" name="name" type="text" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                            <input class="form-control bg-light border-start-0 ps-0" id="editEmail" name="email" type="email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">User Status</label>
                            <select class="form-select bg-light" id="editStatus" name="status">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold d-block">Account Active</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="active" id="editActive" value="Y">
                                <label class="form-check-label ms-2" for="editActive" id="activeLabel">เปิดใช้งาน</label>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4 opacity-10">
                    <div class="mb-2">
                        <label class="form-label fw-bold text-danger">Change Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock"></i></span>
                            <input class="form-control bg-light border-start-0 ps-0" type="password" name="password" placeholder="ทิ้งว่างไว้หากไม่ต้องการเปลี่ยน">
                        </div>
                        <div class="form-text small">ระบุรหัสผ่านใหม่หากต้องการเปลี่ยนแปลงสิทธิ์การเข้าถึง</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">อัปเดตข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .hover-scale { transition: transform 0.2s; }
    .hover-scale:hover { transform: scale(1.02); }
    .btn-white { background: #fff; border: 1px solid #edf2f7; }
    .btn-white:hover { background: #f8fafc; }
    .bg-success-subtle { background-color: #d1fae5; }
    .bg-danger-subtle { background-color: #fee2e2; }
    .bg-primary-subtle { background-color: #dbeafe; }
    .bg-secondary-subtle { background-color: #f1f5f9; }
</style>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // Initialize DataTable
        $('#userTable').DataTable({
            pageLength: 10,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json' },
            order: [[0, 'asc']],
            dom: "<'row px-4 pt-4'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row px-4 pb-4'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

        // Set ข้อมูลใน Edit Modal
        $('.btn-edit').on('click', function () {
            const data = $(this).data();
            $('#editName').val(data.name);
            $('#editEmail').val(data.email);
            $('#editStatus').val(data.status);
            $('#editActive').prop('checked', data.active === 'Y');
            updateActiveLabel(data.active === 'Y');
            $('#editForm').attr('action', "{{ url('admin/users') }}/" + data.id);
        });

        $('#editActive').on('change', function() {
            updateActiveLabel($(this).is(':checked'));
        });

        function updateActiveLabel(isActive) {
            $('#activeLabel').text(isActive ? 'เปิดใช้งาน' : 'ระงับการใช้งาน').toggleClass('text-success', isActive).toggleClass('text-danger', !isActive);
        }

        // SweetAlert ยืนยันลบ
        $('.btn-delete').on('click', function () {
            const form = $(this).closest('form');
            Swal.fire({
                title: 'ยืนยันการลบผู้ใช้?',
                text: "ข้อมูลนี้จะหายไปอย่างถาวรและไม่สามารถเรียกคืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ลบทันที!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
                borderRadius: '15px'
            }).then(result => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false,
                borderRadius: '15px'
            });
        @endif
    });
</script>
@endpush