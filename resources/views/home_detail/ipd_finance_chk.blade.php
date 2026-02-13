@extends('layouts.app')

@section('content')

<div class="container-fluid py-4"> 
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white pt-4 pb-0 border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title text-primary mb-0">
          <i class="bi bi-cash-stack mr-2"></i> 
          ตรวจสอบค่ารักษาพยาบาลผู้ป่วยใน
          <small class="text-muted d-block mt-1" style="font-size: 0.8rem;">
            รายการผู้ป่วยในปัจจุบัน
          </small>
        </h5>
        
        <div class="d-flex gap-2">
            <button onclick="location.reload()" class="btn btn-outline-primary btn-sm px-3">
                <i class="bi bi-arrow-clockwise"></i> รีเฟรช
            </button>
        </div>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="table-responsive">            
        <table id="list" class="table table-hover table-bordered align-middle" width="100%">
          <thead class="bg-light">
            <tr>
                <th class="text-center">ลำดับ</th>           
                <th class="text-center">Ward</th>  
                <th class="text-center">เตียง</th>
                <th class="text-center">AN / Admit</th>
                <th class="text-center">สิทธิ | Hmain</th>   
                <th class="text-center">โอนค่ารักษา</th> 
                <th class="text-center text-primary">OPD (รอโอน)</th>    
                <th class="text-center fw-bold">รวมทั้งหมด</th>  
                <th class="text-center text-danger">ลูกหนี้</th> 
                <th class="text-center text-success">ชำระแล้ว</th> 
                <th class="text-center text-warning">รอชำระ</th>     
            </tr>
          </thead> 
          <tbody>  
            @foreach($finance_chk as $index => $row) 
            <tr>
              <td align="center" class="text-muted">{{ $index + 1 }}</td>
              <td align="left"><small>{{$row->ward}}</small></td>             
              <td align="center"><span class="badge bg-light text-dark border">{{$row->bedno}}</span></td>
              <td align="left">
                <div class="fw-bold text-primary">{{$row->an}}</div>
                <small class="text-muted">{{DateThai($row->regdate)}}</small>
              </td> 
              <td align="left">
                <small class="d-block text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</small>
                <span class="badge bg-secondary shadow-sm">H: {{ $row->hospmain ?: 'N/A' }}</span>
              </td> 
              <td align="center">
                @if($row->finance_transfer == 'Y')
                  <span class="badge bg-success shadow-sm">Y</span>
                @else
                  <span class="badge bg-danger shadow-sm">N</span>
                @endif
              </td> 
              <td align="right" class="text-primary"><small>{{ number_format($row->opd_wait_money,2) }}</small></td>
              <td align="right" class="fw-bold">{{ number_format($row->item_money,2) }}</td>
              <td align="right" class="text-danger"><small>{{ number_format($row->wait_debt_money,2) }}</small></td>
              <td align="right" class="text-success"><small>{{ number_format($row->rcpt_money,2) }}</small></td>
              <td align="right" class="text-warning fw-bold">{{ number_format($row->wait_paid_money,2) }}</td>
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
              title: 'IPD_Finance_Check_{{ date("Y-m-d") }}'
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