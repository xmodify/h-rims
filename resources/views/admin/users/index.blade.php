@extends('layouts.app')
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">

@section('content')
<div class="container">

    <h3 class="text-primary">User Management</h3>
    <!-- ปุ่มเปิด Modal เพิ่ม -->
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
        ➕ Add User
    </button>

    <!-- ตารางผู้ใช้ -->
    <table class="table table-bordered" id ="data">
        <thead class="table-primary">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th class="text-center" width = "5%">Active</th>
                <th class="text-center" width = "10%">Status</th>
                <th class="text-center" width = "20%">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td class="text-center">{{ $user->active }}</td>
                    <td class="text-center">{{ $user->status }}</td>
                    <td>
                        <!-- ปุ่ม Edit -->
                        <button class="btn btn-warning btn-sm btn-edit" 
                            data-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            data-email="{{ $user->email }}"
                            data-active="{{ $user->active }}"
                            data-status="{{ $user->status }}"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal">
                            Edit
                        </button>

                        <!-- ปุ่ม Delete -->
                        <form class="d-inline delete-form" method="POST" action="{{ route('admin.users.destroy', $user) }}">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Modal Create -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.users.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input name="name" type="text" class="form-control mb-2" placeholder="Name" required>
                    <input name="email" type="email" class="form-control mb-2" placeholder="Email" required>                  
                    <input name="password" type="password" class="form-control mb-2" placeholder="Password" required>
                    <input type="hidden" name="active" value="Y">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="editForm" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input class="form-control mb-2" id="editName" name="name" type="text"  required>
                    <input class="form-control mb-2" id="editEmail" name="email" type="email"   required>
                    <select class="form-select" id="editStatus" name="status" >
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>    
                    <br>                                  
                    <input type="checkbox" name="active" id="editActive" value="Y"
                        {{ $user->active === 'Y' ? 'checked' : '' }}>
                    <label for="editActive">เปิดใช้งาน</label>
                    <input class="form-control mb-2" type="password" name="password" placeholder="New Password (ไม่กรอก = ไม่เปลี่ยน)">
                </div>
  
                <div class="modal-footer">
                    <button class="btn btn-primary">Update</button>
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
        // Set ข้อมูลใน Edit Modal
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const email = this.dataset.email; 
                const status = this.dataset.status;
                const activeCheckbox = document.getElementById('editActive');
                activeCheckbox.checked = (this.dataset.active === 'Y');

                document.getElementById('editName').value = name;
                document.getElementById('editEmail').value = email; 
                document.getElementById('editStatus').value = status;
                document.getElementById('editForm').action = `/admin/users/${id}`;
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

<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" class="init">
    $(document).ready(function () {
        $('#data').DataTable();
    });
</script>