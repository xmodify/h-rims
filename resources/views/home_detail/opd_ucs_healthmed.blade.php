@extends('layouts.app')

@section('content')

<div class="container-fluid py-4"> 
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white pt-4 pb-0 border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title text-primary mb-0">
          <i class="bi bi-flower1 mr-2"></i> 
          รายงานผู้มารับบริการแพทย์แผนไทย
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
                <button type="submit" onclick="showLoading()" class="btn btn-primary btn-sm px-3">{{ __('ค้นหา') }}</button>
            </div>
        </form>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="table-responsive">            
        <table id="list" class="table table-hover table-bordered align-middle" width="100%">
          <thead class="bg-light">
            <tr>
                <th class="text-center">ลำดับ</th>
                <th class="text-center" width="5%">ดึง</th>  
                <th class="text-center">Authen</th>  
                <th class="text-center">ปิดสิทธิ</th>
                <th class="text-center">วันที่รับบริการ/เวลา</th>
                <th class="text-center">Queue</th>     
                <th class="text-center">ชื่อ-สกุล | CID | HN</th>    
                <th class="text-center">การติดต่อ</th>
                <th class="text-center">สิทธิ | Hmain</th>
                <th class="text-center">ค่ารักษาทั้งหมด</th>
                <th class="text-center">ชำระเอง</th>
                <th class="text-center">ที่เบิกได้</th>
                <th class="text-center">จุดบริการ</th>
                <th class="text-center">หัตถการแพทย์แผนไทย</th>    
            </tr>
          </thead> 
          <tbody> 
            <?php 
              $sum_income = 0; 
              $sum_rcpt_money = 0;
              $sum_debtor = 0;
            ?>
            @foreach($search as $index => $row) 
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
              <td align="left">
                <small class="d-block fw-bold text-dark">{{ DateThai($row->vstdate) }}</small>
                <small class="text-muted">{{$row->vsttime}}</small>
              </td>
              <td align="center"><span class="badge bg-secondary shadow-sm">{{ $row->oqueue }}</span></td>   
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
              <td align="right" class="fw-bold">{{ number_format($row->income, 2) }}</td>
              <td align="right" class="text-danger">{{ number_format($row->rcpt_money, 2) }}</td>
              <td align="right" class="text-primary fw-bold">{{ number_format($row->debtor, 2) }}</td>
              <td align="left"><small class="text-muted">{{$row->department}}</small></td>
              <td align="left">
                <div style="font-size: 0.8rem; line-height: 1.2;">{{$row->operation}}</div>
              </td>                  
            </tr>
            <?php 
              $sum_income += $row->income;
              $sum_rcpt_money += $row->rcpt_money;
              $sum_debtor += $row->debtor;
            ?>
            @endforeach                 
          </tbody>
        </table>  
      </div>          
      <!-- Summary Footer -->
      <div class="row g-0 bg-light border-top p-3 text-center">
        <div class="col-md-4 border-end">
          <small class="text-muted d-block">ค่ารักษาจริง</small>
          <span class="h6 mb-0">{{ number_format($sum_income,2)}}</span>
        </div>
        <div class="col-md-4 border-end">
          <small class="text-muted d-block">ชำระเอง</small>
          <span class="h6 mb-0 text-danger">{{ number_format($sum_rcpt_money,2)}}</span>
        </div>
        <div class="col-md-4">
          <small class="text-muted d-block">ที่เบิกได้</small>
          <span class="h6 mb-0 text-primary">{{ number_format($sum_debtor,2)}}</span>
        </div>
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

function showLoading() {
    Swal.fire({
        title: 'กำลังค้นหา...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
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
              title: 'HealthMed_Reports_{{ $start_date }}_{{ $end_date }}'
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
