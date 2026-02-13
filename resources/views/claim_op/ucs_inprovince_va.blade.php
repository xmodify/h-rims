@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-person-badge-fill me-2"></i>
                สถิติการชดเชยค่าบริการ UC-OP ในจังหวัด VA 
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf            
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกวันที่รับบริการ</span>
                    <div class="input-group input-group-sm">
                        <input type="date" name="start_date" class="form-control" value="{{ $start_date }}" style="width: 140px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>
                        <input type="date" name="end_date" class="form-control" value="{{ $end_date }}" style="width: 140px;">
                        <button onclick="fetchData()" type="submit" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-search me-1"></i> ค้นหา
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div class="card dash-card border-0 mb-4" style="height: auto !important; overflow: visible !important;">
        <!-- Section 1: Summary Table -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <h6 class="fw-bold text-dark mb-3">
                <i class="bi bi-table text-primary me-2"></i> สรุปตามสถานพยาบาลหลัก (Hmain)
            </h6>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="table-responsive">
                <table id="t_sum" class="table table-modern w-100">
                    <thead>          
                        <tr> 
                            <th class="text-center" rowspan="2">Hmain</th> 
                            <th class="text-center" rowspan="2">Visit ทั้งหมด</th> 
                            <th class="text-center" rowspan="2">ค่ารักษา</th> 
                            <th class="text-center" rowspan="2">ชำระเอง</th>
                            <th class="text-center" rowspan="2">กองทุนอื่น</th>
                            <th class="text-center" rowspan="2">เรียกเก็บรวม</th> 
                            <th class="text-center bg-info-soft" colspan="2">อุบัติเหตุฉุกเฉิน</th>
                            <th class="text-center bg-primary-soft" colspan="2">ผู้ป่วยทั่วไป</th>   
                        </tr>
                        <tr> 
                            <th class="text-center bg-info-soft small">Visit</th>
                            <th class="text-center bg-info-soft small">เรียกเก็บ</th> 
                            <th class="text-center bg-primary-soft small">Visit</th>
                            <th class="text-center bg-primary-soft small">เรียกเก็บ</th>   
                        </tr>
                    </thead> 
                    <tbody>          
                        @foreach($sum as $row) 
                        <tr>
                            <td class="text-start fw-bold small">{{$row->hospmain}}</td>
                            <td class="text-center">{{$row->visit}}</td>
                            <td class="text-end fw-bold">{{ number_format($row->income,2) }}</td>              
                            <td class="text-end">{{ number_format($row->rcpt_money,2) }}</td>
                            <td class="text-end">{{ number_format($row->other_price,2) }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td> 
                            <td class="text-center bg-info-soft">{{ number_format($row->er_visit) }}</td> 
                            <td class="text-end bg-info-soft small">{{ number_format($row->er_price,2) }}</td> 
                            <td class="text-center bg-primary-soft">{{ number_format($row->normal_visit) }}</td> 
                            <td class="text-end bg-primary-soft small">{{ number_format($row->normal_price,2) }}</td> 
                        </tr>
                        @endforeach                 
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Individual Details -->
    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-list-ul text-primary me-2"></i> รายละเอียดผู้มารับบริการ (Individual)
            </h6>
            <p class="text-muted small mt-1 mb-3">ช่วงวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</p>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="table-responsive">            
                <table id="t_search" class="table table-modern w-100">
                    <thead>
                        <tr> 
                            <th class="text-center">Hmain | ประเภท</th> 
                            <th class="text-center">วัน-เวลา | Q</th>  
                            <th class="text-center">HN</th>    
                            <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                            <th class="text-center" width="10%">อาการสำคัญ</th>
                            <th class="text-center">PDX | ICD9</th>
                            <th class="text-center small">ค่ารักษา</th> 
                            <th class="text-center small">ชำระเอง</th>
                            <th class="text-center small">กองทุนอื่น</th>
                            <th class="text-center small">เรียกเก็บ</th> 
                            <th class="text-center small">รายการกองทุนอื่น</th>                
                        </tr>
                    </thead> 
                    <tbody>          
                        @php 
                            $sum_income = 0; 
                            $sum_rcpt_money = 0;  
                            $sum_other_price = 0;
                            $sum_claim_price = 0; 
                        @endphp
                        @foreach($search as $row) 
                        <tr>
                            <td class="text-start">
                                <div class="fw-bold small text-truncate" style="max-width: 120px;" title="{{$row->hospmain}}">{{$row->hospmain}}</div>
                                <div class="badge bg-light text-dark fw-normal" style="font-size: 0.65rem;">{{$row->pt_status}}</div>
                            </td>
                            <td class="text-start">
                                <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                            </td>            
                            <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                            <td class="text-start">
                                <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                <div class="small text-muted">{{$row->pttype}}</div>
                            </td> 
                            <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                            <td class="text-center small">
                                <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                            </td>
                            <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                            <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                            <td class="text-end small">{{ number_format($row->other_price,2) }}</td>
                            <td class="text-end small fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td> 
                            <td class="text-start small text-muted text-wrap" style="font-size: 0.65rem;">{{ $row->other_list }}</td>      
                        </tr>
                        @php 
                            $sum_income += $row->income; 
                            $sum_rcpt_money += $row->rcpt_money; 
                            $sum_other_price += $row->other_price; 
                            $sum_claim_price += $row->claim_price; 
                        @endphp
                        @endforeach                 
                    </tbody>
                    <tfoot class="bg-light-soft">
                        <tr>
                            <th colspan="6" class="text-end text-muted small px-3">รวมที่ค้นพบ:</th>
                            <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                            <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                            <th class="text-end small">{{ number_format($sum_other_price,2) }}</th>
                            <th class="text-end small fw-bold text-primary">{{ number_format($sum_claim_price,2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

<script>
  function showLoading() {
      Swal.fire({
          title: 'กำลังโหลด...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });
  }
  function fetchData() {
      showLoading();
  }
</script>

@endsection

@push('scripts')
  <script>
    $(document).ready(function () {
      $('#t_sum').DataTable({
        paging: false,
        searching: false,
        info: false,
        lengthChange: false,
        dom: '<"d-flex justify-content-end mb-2"B>t',
        buttons: [
          {
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
            className: 'btn btn-success btn-sm shadow-sm',
            title: 'สรุปผู้มารับบริการ UC-OP ในจังหวัด VA แยกสถานพยาบาลหลัก วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
          }
        ]
      });

      $('#t_search').DataTable({
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
              text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
              className: 'btn btn-success btn-sm shadow-sm',
              title: 'รายชื่อผู้มารับบริการ UC-OP ในจังหวัด VA วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

