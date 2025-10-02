@extends('layouts.app')

@section('content')
<div class="container">
    
        <h3 class="text-primary">Lookup Ward</h3>
        <!-- ปุ่มเปิด Modal เพิ่ม -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
            ➕ Add Lookup Ward
        </button>
        <form method="POST" action="{{ route('admin.insert_lookup_ward') }}" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-primary mb-3">นำเข้า Ward</button>
        </form>
        <!-- ตาราง -->
        <table class="table table-bordered" id="data">
            <thead class="table-primary">
                <tr>
                    <th class="text-center">Ward</th>
                    <th class="text-center">Ward Name</th>
                    <th class="text-center">จำนวนเตียงจริง</th>
                    <th class="text-center">ทั่วไป</th>
                    <th class="text-center">ชาย</th>
                    <th class="text-center">หญิง</th>
                    <th class="text-center">VIP</th>
                    <th class="text-center">ห้องคลอด</th>
                    <th class="text-center">Homeward</th>
                    <th class="text-center" width = "20%">Action</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($data))
                    @foreach ($data as $item)
                        <tr>
                            <td>{{ $item->ward }}</td>
                            <td>{{ $item->ward_name }}</td>
                            <td class="text-center">{{ $item->bed_qty }}</td>
                            <td class="text-center">{{ $item->ward_normal }}</td>
                            <td class="text-center">{{ $item->ward_m }}</td>
                            <td class="text-center">{{ $item->ward_f }}</td>
                            <td class="text-center">{{ $item->ward_vip }}</td>
                            <td class="text-center">{{ $item->ward_lr }}</td>
                            <td class="text-center">{{ $item->ward_homeward }}</td>
                            <td>
                                <!-- ปุ่ม Edit -->
                                <button class="btn btn-warning btn-sm btn-edit" 
                                    data-ward="{{ $item->ward }}"    
                                    data-ward_name="{{ $item->ward_name }}"
                                    data-bed_qty="{{ $item->bed_qty }}"
                                    data-ward_normal="{{ $item->ward_normal }}"
                                    data-ward_m="{{ $item->ward_m }}"
                                    data-ward_f="{{ $item->ward_f }}"
                                    data-ward_vip="{{ $item->ward_vip }}"
                                    data-ward_lr="{{ $item->ward_lr }}"   
                                    data-ward_homeward="{{ $item->ward_homeward }}"                            
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal">
                                    Edit
                                </button>

                                <!-- ปุ่ม Delete -->
                                <form class="d-inline delete-form" method="POST" action="{{ route('admin.lookup_ward.destroy', $item) }}">
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
                <form method="POST" action="{{ route('admin.lookup_ward.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Lookup Ward</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input class="form-control mb-2" name="ward" type="text" placeholder="ward" required>
                        <input class="form-control mb-2" name="ward_name" type="text" placeholder="ward_name" required>  
                        <input class="form-control mb-2" name="bed_qty" type="text" placeholder="bed_qty" required>                              
                        <input type="checkbox" name="ward_normal" value="Y">
                        <label for="ward_normal">ทั่วไป</label>
                        <br>
                        <input type="checkbox" name="ward_m" value="Y">
                        <label for="ward_m">ชาย</label>
                        <br>
                        <input type="checkbox" name="ward_f" value="Y">
                        <label for="ward_f">หญิง</label>
                        <br>
                        <input type="checkbox" name="ward_vip" value="Y">
                        <label for="ward_vip">VIP</label>
                        <br>
                        <input type="checkbox" name="ward_lr" value="Y">
                        <label for="ward_lr">ห้องคลอด</label>
                        <br>
                        <input type="checkbox" name="ward_homeward" value="Y">
                        <label for="ward_homeward">Homeward</label>
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
                        <h5 class="modal-title">Edit Lookup Ward</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if (!empty($item))
                            <input class="form-control mb-2" id="editward" name="ward" type="text" readonly>
                            <input class="form-control mb-2" id="editward_name" name="ward_name" type="text"  readonly>
                            <input class="form-control mb-2" id="editbed_qty" name="bed_qty" type="text" >                                 
                            <input type="checkbox" name="ward_normal" id="editward_normal" value="Y"
                                {{ $item->ward_normal === 'Y' ? 'checked' : '' }}>
                            <label for="editward_normal">ทั่วไป</label>
                            <br>
                            <input type="checkbox" name="ward_m" id="editward_m" value="Y"
                                {{ $item->ward_m === 'Y' ? 'checked' : '' }}>
                            <label for="editward_m">ชาย</label>
                            <br>
                            <input type="checkbox" name="ward_f" id="editward_f" value="Y"
                                {{ $item->ward_f === 'Y' ? 'checked' : '' }}>
                            <label for="editward_f">หญิง</label>
                            <br>
                            <input type="checkbox" name="ward_vip" id="editward_vip" value="Y"
                                {{ $item->ward_vip === 'Y' ? 'checked' : '' }}>
                            <label for="editward_vip">VIP</label>  
                            <br>  
                            <input type="checkbox" name="ward_lr" id="editward_lr" value="Y"
                                {{ $item->ward_lr === 'Y' ? 'checked' : '' }}>
                            <label for="editward_lr">ห้องคลอด</label> 
                            <br>
                            <input type="checkbox" name="ward_homeward" id="editward_homeward" value="Y"
                                {{ $item->ward_homeward === 'Y' ? 'checked' : '' }}>
                            <label for="editward_homeward">Homeward</label>  
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
                    const ward = this.dataset.ward;
                    const ward_name = this.dataset.ward_name; 
                    const bed_qty = this.dataset.bed_qty; 
                    const ward_normal = this.dataset.ward_normal; 
                    const ward_m = this.dataset.ward_m; 
                    const ward_f = this.dataset.ward_f; 
                    const ward_vip = this.dataset.ward_vip; 
                    const ward_lr = this.dataset.ward_lr; 
                    const ward_homeward = this.dataset.ward_homeward; 

                    document.getElementById('editward').value = this.dataset.ward;
                    document.getElementById('editward_name').value = this.dataset.ward_name;
                    document.getElementById('editbed_qty').value = this.dataset.bed_qty;
                    document.getElementById('editward_normal').checked = (this.dataset.ward_normal === 'Y');
                    document.getElementById('editward_m').checked = (this.dataset.ward_m === 'Y');
                    document.getElementById('editward_f').checked = (this.dataset.ward_f === 'Y');
                    document.getElementById('editward_vip').checked = (this.dataset.ward_vip === 'Y');
                    document.getElementById('editward_lr').checked = (this.dataset.ward_lr === 'Y');
                    document.getElementById('editward_homeward').checked = (this.dataset.ward_homeward === 'Y');
                    document.getElementById('editForm').action = `/admin/lookup_ward/${ward}`;      
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

