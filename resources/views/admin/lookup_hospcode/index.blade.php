@extends('layouts.app')
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">

@section('content')
<div class="container">
    
        <h3 class="text-primary">Lookup Hospcode</h3>
        <!-- ปุ่มเปิด Modal เพิ่ม -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
            ➕ Add Lookup Hospcode
        </button>

        <!-- ตาราง -->
        <table class="table table-bordered" id="data">
            <thead class="table-primary">
                <tr>
                    <th class="text-center">Hospcode</th>
                    <th class="text-center">Name</th>
                    <th class="text-center">Hmain UCS</th>
                    <th class="text-center">Hmain SSS</th>
                    <th class="text-center">ในจังหวัด</th>
                    <th class="text-center" width = "20%">Action</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($data))
                    @foreach ($data as $item)
                        <tr>
                            <td>{{ $item->hospcode }}</td>
                            <td>{{ $item->hospcode_name }}</td>
                            <td class="text-center">{{ $item->hmain_ucs }}</td>
                            <td class="text-center">{{ $item->hmain_sss }}</td>
                            <td class="text-center">{{ $item->in_province }}</td>
                            <td>
                                <!-- ปุ่ม Edit -->
                                <button class="btn btn-warning btn-sm btn-edit" 
                                    data-hospcode="{{ $item->hospcode }}"    
                                    data-hospcode_name="{{ $item->hospcode_name }}"
                                    data-hmain_ucs="{{ $item->hmain_ucs }}"
                                    data-hmain_sss="{{ $item->hmain_sss }}"
                                    data-in_province="{{ $item->in_province }}"                          
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal">
                                    Edit
                                </button>

                                <!-- ปุ่ม Delete -->
                                <form class="d-inline delete-form" method="POST" action="{{ route('admin.lookup_hospcode.destroy', $item) }}">
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
                <form method="POST" action="{{ route('admin.lookup_hospcode.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Lookup Hospcode</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input class="form-control mb-2" name="hospcode" type="text" placeholder="hospcode" required>
                        <input class="form-control mb-2" name="hospcode_name" type="text" placeholder="hospcode_name" required>                               
                        <input type="checkbox" name="hmain_ucs" value="Y">
                        <label for="hmain_ucs">Hmain UCS</label>
                        <br>
                        <input type="checkbox" name="hmain_sss" value="Y">
                        <label for="hmain_sss">Hmain SSS</label>
                        <br>
                        <input type="checkbox" name="in_province" value="Y">
                        <label for="in_province">ในจังหวัด</label>                        
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
                        <h5 class="modal-title">Edit Lookup Hospcode</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if (!empty($item))
                            <input class="form-control mb-2" id="edithospcode" name="hospcode" type="text" readonly>
                            <input class="form-control mb-2" id="edithospcode_name" name="hospcode_name" type="text"  required>                                
                            <input type="checkbox" name="hmain_ucs" id="edithmain_ucs" value="Y"
                                {{ $item->hmain_ucs === 'Y' ? 'checked' : '' }}>
                            <label for="hmain_ucs">Hmain UCS</label>
                            <br>
                            <input type="checkbox" name="hmain_sss" id="edithmain_sss" value="Y"
                                {{ $item->hmain_sss === 'Y' ? 'checked' : '' }}>
                            <label for="edithmain_sss">Hmain SSS</label>
                            <br>
                            <input type="checkbox" name="in_province" id="editin_province" value="Y"
                                {{ $item->in_province === 'Y' ? 'checked' : '' }}>
                            <label for="editin_province">ในจังหวัด</label>                             
                        @endif
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
                    const hospcode = this.dataset.hospcode;
                    const hospcode_name = this.dataset.hospcode_name; 
                    const hmain_ucs = this.dataset.hmain_ucs; 
                    const hmain_sss = this.dataset.hmain_sss; 
                    const in_province = this.dataset.in_province; 

                    document.getElementById('edithospcode').value = this.dataset.hospcode;
                    document.getElementById('edithospcode_name').value = this.dataset.hospcode_name;
                    document.getElementById('edithmain_ucs').checked = (this.dataset.hmain_ucs === 'Y');
                    document.getElementById('edithmain_sss').checked = (this.dataset.hmain_sss === 'Y');
                    document.getElementById('editin_province').checked = (this.dataset.in_province === 'Y');
                    document.getElementById('editForm').action = `/admin/lookup_hospcode/${hospcode}`;      
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