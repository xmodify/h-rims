@extends('layouts.app')
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">

@section('content')
<div class="container">
    
        <h3 class="text-primary">รายการ ICODE</h3>
        <!-- ปุ่มเปิด Modal เพิ่ม -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
            ➕ Add Lookup iCode
        </button>

        <!-- ตาราง -->
        <table class="table table-bordered" id="data">
            <thead class="table-primary">
                <tr>
                    <th class="text-center">icode</th>
                    <th class="text-center">ชื่อรายการ</th>
                    <th class="text-center">uc_cr</th>
                    <th class="text-center">ppfs</th>
                    <th class="text-center">herb32</th>
                    <th class="text-center" width = "20%">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $item)
                    <tr>
                        <td>{{ $item->icode }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="text-center">{{ $item->uc_cr }}</td>
                        <td class="text-center">{{ $item->ppfs }}</td>
                        <td class="text-center">{{ $item->herb32 }}</td>
                        <td>
                            <!-- ปุ่ม Edit -->
                            <button class="btn btn-warning btn-sm btn-edit" 
                                data-icode="{{ $item->icode }}"    
                                data-name="{{ $item->name }}"
                                data-uc_cr="{{ $item->uc_cr }}"
                                data-ppfs="{{ $item->ppfs }}"
                                data-herb32="{{ $item->herb32 }}"                        
                                data-bs-toggle="modal"
                                data-bs-target="#editModal">
                                Edit
                            </button>

                            <!-- ปุ่ม Delete -->
                            <form class="d-inline delete-form" method="POST" action="{{ route('admin.lookup_icode.destroy', $item) }}">
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
                <form method="POST" action="{{ route('admin.lookup_icode.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Lookup iCode</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input class="form-control mb-2" name="icode" type="text" placeholder="icode" required>
                        <input class="form-control mb-2" name="name" type="text" placeholder="Name" required>                             
                        <input type="checkbox" name="uc_cr" value="Y">
                        <label for="edituc_cr">uc_cr</label>
                        <br>
                        <input type="checkbox" name="ppfs" value="Y">
                        <label for="editppfs">ppfs</label>
                        <br>
                        <input type="checkbox" name="herb32" value="Y">
                        <label for="editherb32">herb32</label>
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
                        <h5 class="modal-title">Edit Lookup iCode</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input class="form-control mb-2" id="icode" name="icode" type="text" readonly>
                        <input class="form-control mb-2" id="editName" name="name" type="text"  required>                             
                        <input type="checkbox" name="uc_cr" id="edituc_cr" value="Y"
                            {{ $item->uc_cr === 'Y' ? 'checked' : '' }}>
                        <label for="edituc_cr">uc_cr</label>
                        <br>
                        <input type="checkbox" name="ppfs" id="editppfs" value="Y"
                            {{ $item->ppfs === 'Y' ? 'checked' : '' }}>
                        <label for="editppfs">ppfs</label>
                        <br>
                        <input type="checkbox" name="herb32" id="editherb32" value="Y"
                            {{ $item->uc_cr === 'Y' ? 'checked' : '' }}>
                        <label for="editherb32">herb32</label>
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
                    const icode = this.dataset.icode;
                    const name = this.dataset.name; 
                    const uc_cr = this.dataset.uc_cr; 
                    const ppfs = this.dataset.ppfs; 
                    const herb32 = this.dataset.herb32; 

                    document.getElementById('icode').value = this.dataset.icode;
                    document.getElementById('editName').value = this.dataset.name;
                    document.getElementById('edituc_cr').checked = (this.dataset.uc_cr === 'Y');
                    document.getElementById('editppfs').checked = (this.dataset.ppfs === 'Y');
                    document.getElementById('editherb32').checked = (this.dataset.herb32 === 'Y');
                    document.getElementById('editForm').action = `/admin/lookup_icode/${icode}`;      
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
        $('#stm_ucs').DataTable();
    });
</script>
<script type="text/javascript" class="init">
    $(document).ready(function () {
        $('#data').DataTable();
    });
</script>