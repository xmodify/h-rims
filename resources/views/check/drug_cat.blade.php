@extends('layouts.app')    
    
@section('content')
    <style>
        .btn-outline-purple {
            color: #6f42c1;
            border: 1px solid #6f42c1;
            background-color: transparent;
        }
        .btn-outline-purple:hover {
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }
    </style>

<div class="container-fluid">
    <div class="card">
        <div class="alert alert-success" role="alert">
            <div class="row"> 
                <div class="col-md-4" align="left">   
                    <strong>ตรวจสอบ Drug Catalog</strong>              
                </div>  
                <div class="col-md-4" align="center">
                    @if ($message = Session::get('success'))<strong class="text-center">ImportFile {{ $message }} Success</strong> @endif
                </div>  
                <div class="col-md-4" align="right">                    
                </div> 
            </div>
        </div>                
        <div class="card-body"> 
            <div class="row">   
                <div class="col-md-6" align="left">                   
                    <form action="{{ url('check/drug_cat_nhso_save') }}" method="POST" enctype="multipart/form-data">
                        @csrf  
                        <div class="row" >
                            <div class="col-md-6" >
                                <input class="form-control form-control" id="formFile" name="file" type="file" required>
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            </div>
                            <div class="col-md-3" >
                                <button type="submit" onclick="simulateProcess()" 
                                class="btn btn-outline-success">ImportDrugCat NHSO</button>
                            </div> 
                            <div class="col-md-3">                                                             
                            </div>      
                        </div>                                         
                    </form>   
                </div>  
                <div class="col-md-6" align="right">  
                    {{-- <form action="{{ url('check/drug_cat_aipn_save') }}" method="POST" enctype="multipart/form-data">
                        @csrf  
                        <div class="row" >
                            <div class="col-md-3">                                
                            </div>   
                            <div class="col-md-6" >
                                <input class="form-control form-control" id="formFile" name="file" type="file" required>
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            </div>
                            <div class="col-md-3" >
                                <button type="submit" class="btn btn-outline-primary">ImportDrugCat AIPN</button>
                            </div>    
                        </div>                                          --}}
                    </form>  
                </div>  
            </div> 
            <br> 
            <div class="row">   
                <div class="col-md-6" align="left">
                    <a class="btn btn-outline-primary" href="{{ url('check/drug_cat') }}">
                        ทั้งหมดที่ HOSxP
                    </a>  
                    <a class="btn btn-outline-purple" href="{{ url('check/drug_cat_non_nhso') }}">
                        ไม่พบที่ NHSO
                    </a>  
                    <a class="btn btn-outline-purple" href="{{ url('check/drug_cat_nhso_price_notmatch_hosxp') }}">
                        ราคาไม่ตรง HOSxP
                    </a> 
                    <a class="btn btn-outline-purple" href="{{ url('check/drug_cat_nhso_tmt_notmatch_hosxp') }}">
                        รหัส TMT ไม่ตรง HOSxP
                    </a> 
                    <a class="btn btn-outline-purple" href="{{ url('check/drug_cat_nhso_code24_notmatch_hosxp') }}">
                        รหัส 24 หลักไม่ตรง HOSxP
                    </a> 
                </div>   
                <div class="col-md-6" align="right"> 
                    
                </div>              
            </div>
        </div>
        <div class="card-body">
            <div style="overflow-x:auto;">          
                <div class="row">
                    <div class="col-md-12">                          
                        <table id="drug" class="table table-bordered table-striped" width = "100%" >
                            <thead>
                                <tr class="table-primary">
                                    <th class="text-center" rowspan="2">NHSO</th>   
                                    <th class="text-center" rowspan="2">รหัส HOSxP</th>             
                                    <th class="text-center" rowspan="2">ชื่อยา</th>                   
                                    <th class="text-center" rowspan="2">หน่วยนับ</th>
                                    <th class="text-center" colspan="2" style="background-color: #4fc3f7">ราคา</th>                   
                                    <th class="text-center" colspan="2" style="background-color: #81d4fa">รหัส TMT</th> 
                                    <th class="text-center" colspan="2" style="background-color: #b3e5fc">ยาสมุนไพร</th>                  
                                    <th class="text-center" colspan="2" style="background-color: #b3e5fc">รหัส 24 หลัก</th>                                      
                                </tr>
                                <tr class="table-primary">                    
                                    <th class="text-center" style="background-color: #4fc3f7">HOSxP</th>   
                                    <th class="text-center" style="background-color: #4fc3f7">NHSO</th> 
                                    <th class="text-center" style="background-color: #81d4fa">HOSxP</th> 
                                    <th class="text-center" style="background-color: #81d4fa">HNSO</th>
                                    <th class="text-center" style="background-color: #b3e5fc">TTMT</th> 
                                    <th class="text-center" style="background-color: #b3e5fc">HERB</th>   
                                    <th class="text-center" style="background-color: #b3e5fc">HOSxP</th> 
                                    <th class="text-center" style="background-color: #b3e5fc">HNSO</th>  
                                                                                                                     
                                </tr>
                            </thead>                          
                            @foreach($drug as $row)          
                                <tr>          
                                    <td align="center">{{ $row->chk_nhso_drugcat }}</td>                 
                                    <td align="center">{{ $row->icode }}</td>                          
                                    <td align="left" width = "15%">{{ $row->dname }}</td>                        
                                    <td align="left">{{ $row->units }}</td>
                                    <td align="right">{{ number_format($row->price_hos,2) }}</td>
                                    <td align="right" @if($row->price_nhso != $row->price_hos) style="color:red" @endif>{{ number_format($row->price_nhso,2) }}</td> 
                                    <td align="right">{{ $row->code_tmt_hos }}</td>
                                    <td align="right" @if($row->code_tmt_nhso != $row->code_tmt_hos) style="color:red" @endif>{{ $row->code_tmt_nhso }}</td>                                    
                                    <td align="right">{{ $row->ttmt_code }}</td>
                                    <td align="center">{{ $row->herb }}</td>
                                    <td align="right">{{ $row->code_24_hos }}</td>
                                    <td align="right" @if($row->code_24_nhso != $row->code_24_hos) style="color:red" @endif>{{ $row->code_24_nhso }}</td>
                                </tr>      
                            @endforeach 
                        </table> 
                    </div> 
                </div>        
            </div>
        </div>
    </div>
</div>
<br>

@if (session('success'))
<script>
    Swal.fire({
        title: 'นำเข้าสำเร็จ!',
        text: '{{ session('success') }}',
        icon: 'success',
        confirmButtonText: 'ตกลง'
    });
</script>
@endif

@endsection

<script>
    function showLoadingAlert() {
        Swal.fire({
            title: 'กำลังนำเข้าข้อมูล...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading()
            }
        });
    }

    function simulateProcess() {
        const fileInput = document.querySelector('input[type="file"]');
                // ตรวจสอบว่าไม่ได้เลือกไฟล์
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                title: 'แจ้งเตือน',
                text: 'กรุณาเลือกไฟล์ก่อนนำเข้า',
                icon: 'warning',
                confirmButtonText: 'ตกลง'
            });
            return; // ❌ หยุดการทำงาน ไม่ส่งฟอร์ม
        }

        showLoadingAlert();
        document.getElementById('importForm').submit();
    }
</script>

@push('scripts')  
  <script>
    $(document).ready(function () {
      $('#drug').DataTable({
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + // Show รายการ
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + // Search + Export
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + // Info
                '<"col-md-6"p>' + // Pagination
              '>',
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel',
              className: 'btn btn-success',
              title: 'ตรวจสอบ Drug Catalog'
            }
        ],
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: {
              previous: "ก่อนหน้า",
              next: "ถัดไป"
            }
        }
      });
    });
  </script>
@endpush