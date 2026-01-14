@extends('layouts.app')

@section('content')
<form method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row" >
            <label class="col-md-3 col-form-label text-md-end my-1">{{ __('วันที่') }}</label>
        <div class="col-md-2">
            <input type="date" name="start_date" class="form-control my-1" placeholder="Date" value="{{ $start_date }}" >
        </div>
            <label class="col-md-1 col-form-label text-md-end my-1">{{ __('ถึง') }}</label>
        <div class="col-md-2">
            <input type="date" name="end_date" class="form-control my-1" placeholder="Date" value="{{ $end_date }}" >
        </div>
        <div class="col-md-1" >
            <button type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button>
        </div>
    </div>
</form>
<br>
<div class="container-fluid">
    <div class="row justify-content-center">  
        <div class="col-md-12"> 
            <div class="alert alert-success text-primary" role="alert">
                <strong>ข้อมูล Statement สวัสดิการข้าราชการ CIPN รายละเอียด</strong>
            </div>
            <div class="card-body">
                <div style="overflow-x:auto;">                           
                    <table id="stm_ofc_cipn_list" class="table table-bordered table-striped my-3">
                        <thead class="table-primary text-center align-middle">
                            <tr>
                                <th>Filename</th>
                                <th>AN</th>
                                <th>ชื่อ - สกุล</th>
                                <th>จำหน่าย</th>
                                <th>PT</th>
                                <th>DRG</th>
                                <th>AdjRW</th>
                                <th>ค่ารักษา¹</th>
                                <th>ค่าห้อง</th>
                                <th>ค่ารักษา²</th>
                                <th>พึงรับ</th>
                                <th>RepNo.</th>
                                <th>เลขที่ใบเสร็จ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stm_ofc_cipn_list as $row)
                            <tr>
                                <td class="text-center">{{ $row->stm_filename }}</td>
                                <td class="text-center">{{ $row->an }}</td>
                                <td>{{ $row->namepat }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($row->datedsc)->format('d/m/y') }}</td>
                                <td class="text-center">{{ $row->ptype }}</td>
                                <td class="text-center">{{ $row->drg }}</td>
                                <td class="text-end">{{ number_format($row->adjrw,4) }}</td>
                                <td class="text-end">{{ number_format($row->amreimb,2) }}</td>
                                <td class="text-end">{{ number_format($row->amlim,2) }}</td>
                                <td class="text-end">{{ number_format($row->pamreim,2) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($row->gtotal,2) }}</td>
                                <td class="text-center">{{ $row->rid }}</td>
                                <td class="text-center">{{ $row->receive_no }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> 
            </div> 
        </div> 
    </div> 
</div> 
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
      $('#stm_ofc_cipn_list').DataTable({
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
              title: 'ข้อมูล Statement สวัสดิการข้าราชการ CIPN รายละเอียด'
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
