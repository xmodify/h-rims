@extends('layouts.app')

@section('content')

<div class="container-fluid py-4">  
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white pt-4 pb-0 border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title text-primary mb-0">
          <i class="bi bi-person-x-fill mr-2"></i> 
          รายชื่อผู้มารับบริการ (ไม่ขอ AuthenCode)
          <small class="text-muted d-block mt-1" style="font-size: 0.8rem;">
            วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}
          </small>
        </h5>
        
        <form method="POST" class="d-flex gap-2 align-items-center">
            @csrf            
            <div class="d-flex align-items-center gap-2">
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start_date }}" > 
                <span class="text-muted">ถึง</span>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end_date }}" > 
                <button type="submit" class="btn btn-primary btn-sm px-3">{{ __('ค้นหา') }}</button>
            </div>
        </form>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">            
        <table id="list" class="table table-hover table-bordered align-middle" width="100%">
          <thead class="bg-light">
            <tr>
              <th class="text-center">ลำดับ</th>
              <th class="text-center">รับบริการ</th>
              <th class="text-center">Queue</th>
              <th class="text-center">ชื่อ-สกุล | CID | HN</th>
              <th class="text-center">การติดต่อ</th>
              <th class="text-center">สิทธิ | Hmain</th>
              <th class="text-center">ค่ารักษาทั้งหมด</th>
              <th class="text-center">ชำระเอง</th>
              <th class="text-center">ที่เบิกได้</th>
              <th class="text-center">จุดบริการ</th>            
            </tr>     
          </thead> 
          <tbody> 
            @foreach($sql as $index => $row) 
            <tr>
              <td align="center" class="text-muted">{{ $index + 1 }}</td> 
              <td align="left">
                <small class="d-block fw-bold text-dark">{{ DateThai($row->vstdate) }}</small>
                <small class="text-muted">{{ $row->vsttime }}</small>
              </td>
              <td align="center">
                <span class="badge bg-secondary shadow-sm">{{ $row->oqueue }}</span>
              </td>
              <td align="left">
                <div class="fw-bold text-dark">{{ $row->ptname }}</div>
                <small class="text-muted">CID: {{ $row->cid }} | HN: {{ $row->hn }}</small>
              </td>
              <td align="left">
                <small class="d-block"><i class="bi bi-phone mr-1"></i>{{ $row->mobile_phone_number ?: '-' }}</small>
                <small class="text-muted"><i class="bi bi-telephone mr-1"></i>{{ $row->hometel ?: '-' }}</small>
              </td>
              <td align="left">
                <small class="d-block text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</small>
                <span class="badge bg-secondary shadow-sm">H: {{ $row->hospmain ?: 'N/A' }}</span>
              </td> 
              <td align="right" class="fw-bold">{{ number_format($row->income, 2) }}</td>
              <td align="right" class="text-danger">{{ number_format($row->rcpt_money, 2) }}</td>
              <td align="right" class="text-primary fw-bold">{{ number_format($row->debtor, 2) }}</td>
              <td align="right">
                <span class="badge outline-primary text-primary shadow-sm" style="border: 1px solid #0d6efd;">{{ $row->department }}</span>
              </td>
 
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
      $('#list').DataTable({
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + 
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + 
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + 
                '<"col-md-6"p>' + 
              '>',
        buttons: [
            {
              extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel mr-1"></i> Excel',
              className: 'btn btn-success btn-sm',
              title: 'Non_Authen_Reports_{{ $start_date }}_{{ $end_date }}'
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "ค้นหา...",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: {
              previous: '<i class="bi bi-chevron-left"></i>',
              next: '<i class="bi bi-chevron-right"></i>'
            }
        }
      });
    });
  </script>
@endpush