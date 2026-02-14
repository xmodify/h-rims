@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-file-earmark-text-fill text-success me-2"></i>
                รายละเอียด Statement ประกันสุขภาพ UCS [IPD]
            </h5>
            <div class="text-muted small mt-1">รายละเอียดข้อมูลการเบิกจ่ายแยกตามสถานะ ผู้ป่วยใน</div>
            <div class="mt-2">
                <a href="{{ url('import/stm_ucs') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
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

    <!-- IPD Data Table Card -->
    <div class="card dash-card accent-11 mb-4">
        <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-hospital me-2 text-danger"></i> ผู้ป่วยใน IP</h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="stm_ucs_list_ip" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">Dep</th>
                            <th class="text-center">Filename</th>
                            <th class="text-center">REP</th> 
                            <th class="text-center">HN</th>
                            <th class="text-center">AN</th>
                            <th class="text-center">ชื่อ-สกุล</th>
                            <th class="text-center">วันเข้ารักษา</th>
                            <th class="text-center">จำหน่าย</th>
                            <th class="text-center">PROJCODE</th>  
                            <th class="text-center">เรียกเก็บ</th>                                         
                            <th class="text-center">ชดเชยสุทธิ</th> 
                            <th class="text-center">OP</th>
                            <th class="text-center">IP</th>
                            <th class="text-center">HC</th>
                            <th class="text-center">AE</th> 
                            <th class="text-center">PP</th>
                            <th class="text-center">FS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stm_ucs_list_ip as $row)
                        <tr>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->dep }}</span></td>
                            <td class="small fw-bold text-dark">{{ $row->stm_filename }}</td>
                            <td class="text-center">{{ $row->repno }}</td>                            
                            <td class="text-center fw-bold">{{ $row->hn }}</td>
                            <td class="text-center">{{ $row->an }}</td>
                            <td>{{ $row->pt_name }}</td>
                            <td class="text-center small">{{ $row->datetimeadm }}</td>
                            <td class="text-center small text-muted">{{ $row->datetimedch }}</td>
                            <td class="text-center small">{{ $row->projcode }}</td>
                            <td class="text-end text-muted">{{ number_format($row->charge,2) }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($row->receive_total,2) }}</td>    
                            <td class="text-end">{{ number_format($row->receive_op,2) }}</td>
                            <td class="text-end text-muted">{{ number_format($row->receive_ip_compensate_pay,2) }}</td>
                            <td class="text-end">{{ number_format($row->receive_hc_hc,2) }}</td>
                            <td class="text-end text-muted">{{ number_format($row->receive_ae_ae,2) }}</td>
                            <td class="text-end">{{ number_format($row->receive_pp,2) }}</td>
                            <td class="text-end text-muted">{{ number_format($row->receive_fs,2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
      $('#stm_ucs_list_ip').DataTable({
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
              title: 'Statement ประกันสุขภาพ UCS รายละเอียด IPD'
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
