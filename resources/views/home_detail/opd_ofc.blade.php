@extends('layouts.app')

@section('content')

<div class="container-fluid py-4"> 
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white pt-4 pb-0 border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title text-primary mb-0">
          <i class="bi bi-file-earmark-person mr-2"></i> 
          รายงานสิทธิข้าราชการ (OFC)
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
                <th class="text-center" width="6%">ดึงปิดสิทธิ</th>
                <th class="text-center text-nowrap">Authen</th>  
                <th class="text-center text-nowrap">ปิดสิทธิ</th>
                <th class="text-center">PPFS</th>
                <th class="text-center">EDC</th>
                <th class="text-center">วันที่รับบริการ/เวลา</th> 
                <th class="text-center text-nowrap">ชื่อ-สกุล | CID | HN</th>    
                <th class="text-center">การติดต่อ</th>
                <th class="text-center">สิทธิ | Hmain</th>
                <th class="text-center">PDX</th>
                <th class="text-center text-nowrap">ค่ารักษาทั้งหมด</th>
                <th class="text-center text-nowrap">ชำระเอง</th>
                <th class="text-center text-nowrap">ที่เบิกได้</th>
            </tr>
          </thead> 
          <tbody> 
            @foreach($sql as $index => $row) 
            <tr>
              <td align="center" class="text-muted">{{ $index + 1 }}</td>
              <td align="center">                                 
                  <button onclick="pullNhsoData('{{ $row->vstdate }}', '{{ $row->cid }}')" class="btn btn-outline-info btn-sm">
                      <i class="bi bi-cloud-download"></i>
                  </button>              
              </td> 
              <td align="center">
                @if($row->auth_code == 'Y')
                  <span class="badge bg-success shadow-sm">Y</span>
                @else
                  <span class="badge bg-danger shadow-sm">N</span>
                @endif
              </td>               
              <td align="center">
                @if($row->endpoint == 'Y')
                  <span class="badge bg-success shadow-sm">Y</span>
                @else
                  <span class="badge bg-danger shadow-sm">N</span>
                @endif
              </td> 
              <td align="center">
                @if($row->ppfs == 'Y')
                  <span class="badge bg-success shadow-sm">Y</span>
                @else
                  <span class="badge bg-danger shadow-sm">N</span>
                @endif
              </td> 
              <td align="center">
                @if($row->edc)
                  <span class="badge bg-info text-dark shadow-sm">{{$row->edc}}</span>
                @endif
              </td>                
              <td align="left">
                <small class="d-block fw-bold text-dark">{{ DateThai($row->vstdate) }}</small>
                <small class="text-muted">{{ $row->vsttime }}</small>
              </td>                
              <td align="left">
                <div class="fw-bold text-dark">{{ $row->ptname }}</div>
                <small class="text-muted">CID: {{ $row->cid }} | HN: {{ $row->hn }}</small>
              </td> 
              <td align="left">
                <div class="mt-1"><small class="text-muted"><i class="bi bi-phone"></i> {{ $row->mobile_phone_number ?: '-' }}</small></div>
              </td>
              <td align="left">
                <small class="d-block text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</small>
                <span class="badge bg-secondary shadow-sm">H: {{ $row->hospmain ?: 'N/A' }}</span>
              </td>
              <td align="center"><span class="badge bg-secondary shadow-sm">{{$row->pdx}}</span></td>
              <td align="right" class="fw-bold">{{ number_format($row->income, 2) }}</td>
              <td align="right" class="text-danger">{{ number_format($row->rcpt_money, 2) }}</td>
              <td align="right" class="text-primary fw-bold">{{ number_format($row->debtor, 2) }}</td>
            
            </tr>
            @endforeach                 
          </tbody>
        </table>     
      </div>          
    </div> 
  </div>    
</div>      

<script>
  function pullNhsoData(vstdate, cid) {
      Swal.fire({
          title: 'กำลังดึงข้อมูล...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading()
          }
      });

      fetch("{{ url('nhso_endpoint_pull') }}/" + vstdate + "/" + cid)
          .then(async response => {
              const data = await response.json();
              if (!response.ok) {
                  throw new Error(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
              }
              return data;
          })
          .then(data => {
              Swal.fire({
                  icon: 'success',
                  title: 'ดึงข้อมูลสำเร็จ',
                  text: data.message || 'ข้อมูลถูกบันทึกเรียบร้อยแล้ว',
                  timer: 2000,
                  showConfirmButton: false
              }).then(() => {
                  location.reload();
              });
          })
          .catch(error => {
              Swal.fire({
                  icon: 'error',
                  title: 'เกิดข้อผิดพลาด',
                  text: error.message || 'ไม่สามารถเชื่อมต่อกับระบบได้',
              });
          });
  }
</script>

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
              title: 'OFC_Reports_{{ $start_date }}_{{ $end_date }}'
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "ค้นหาข้อมูล...",
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