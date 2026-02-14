@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-file-earmark-text-fill text-success me-2"></i>
                รายละเอียด Statement เบิกจ่ายตรงกรมบัญชีกลาง OFC [ฟอกไต]
            </h5>
            <div class="text-muted small mt-1">รายละเอียดข้อมูลการเบิกจ่ายแยกตามสถานะ</div>
            <div class="mt-2">
                <a href="{{ url('import/stm_ofc_kidney') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">วันที่:</span>
                <input type="date" name="start_date" class="form-control form-control-sm" style="width: 150px; border-radius: 8px;" value="{{ $start_date }}">
                <span class="text-muted small">ถึง:</span>
                <input type="date" name="end_date" class="form-control form-control-sm" style="width: 150px; border-radius: 8px;" value="{{ $end_date }}">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card accent-9 mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="stm_ofc_kidney_list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th>FileName</th>
                            <th>Station</th>
                            <th>Hreg</th>                      
                            <th>HN</th>
                            <th>InvNo</th>                    
                            <th>วันที่รับบริการ</th>
                            <th>RID</th>
                            <th>HD</th>                  
                            <th>ค่ารักษาพยาบาลที่เบิก</th> 
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stm_ofc_kidney_list as $row)          
                        <tr>
                            <td class="small fw-bold text-dark">{{ $row->stmdoc }}</td>
                            <td class="small">{{ $row->station }}</td>
                            <td class="small text-muted">{{ $row->hreg }}</td>
                            <td class="text-center fw-bold">{{ $row->hn }}</td>
                            <td class="text-center small text-muted">{{ $row->invno }}</td>
                            <td class="text-center">{{ $row->dttran }}</td>
                            <td class="text-center small">RID: {{ $row->rid }}</td>
                            <td class="text-center small text-muted">HD: {{ $row->hdflag }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($row->amount,2) }}</td> 
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
        </div> 
    </div> 
</div> 
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
      $('#stm_ofc_kidney_list').DataTable({
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
              title: 'Statement เบิกจ่ายตรงกรมบัญชีกลาง OFC [ฟอกไต] รายละเอียด'
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
