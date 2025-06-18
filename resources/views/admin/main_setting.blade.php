@extends('layouts.app')

@section('content')
<div class="container mt-4">

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
                const value = this.dataset.value;                

                document.getElementById('editValue').value = value;
                document.getElementById('editForm').action = `/admin/main_setting/${id}`;
            });
        });
    </script>

</div>
@endsection