@extends('layouts.app')

@section('content')

<div class="container-fluid"> 
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
              <button onclick="fetchData()" type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button>
          </div>
      </div>
  </form> 
  <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการ UC-OP บริการเฉพาะ CR วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
  
  <div class="card-body">
    <div style="overflow-x:auto;">            
      <table id="t_search" class="table table-striped table-bordered" width = "100%">
        <thead>
          <tr class="table-primary">
              <th class="text-center">ลำดับ</th>
              <th class="text-center" width="5%">Action</th>  
              <th class="text-center">Authen</th>  
              <th class="text-center">ปิดสิทธิ</th>
              <th class="text-center" width="6%">วันที่รับบริการ</th>
              <th class="text-center">Queue</th>     
              <th class="text-center" width="10%">ชื่อ-สกุล</th>    
              <th class="text-center">CID</th>     
              <th class="text-center">เบอร์โทร</th>
              <th class="text-center" width="10%">สิทธิการรักษา</th>
              <th class="text-center">PDX</th>
              <th class="text-center">ค่ารักษาทั้งหมด</th>
              <th class="text-center">ชำระเอง</th>
              <th class="text-center">เบิกได้</th>
              <th class="text-center">รายการเรียกเก็บ</th>
              <th class="text-center">เรียกเก็บ</th> 
              <th class="text-center">Project</th> 
              <th class="text-center text-primary" width="5%">Rep NHSO</th> 
              <th class="text-center text-primary" width="5%">Error</th> 
              <th class="text-center text-success" width="5%">STM ชดเชย</th> 
              <th class="text-center text-success" width="5%">ผลต่าง</th> 
              <th class="text-center text-success" width="5%">REP</th> 
          </tr>
        </thead> 
        <tbody> 
          <?php $count = 1 ; ?>
          <?php $sum_income = 0 ; ?>  
          <?php $sum_rcpt_money  = 0 ; ?> 
          <?php $sum_debtor  = 0 ; ?>
          <?php $sum_claim_price  = 0 ; ?>
          <?php $sum_rep_nhso = 0 ; ?>  
          <?php $sum_receive_total = 0 ; ?>
          @foreach($search as $row) 
          <tr>
            <td align="center">{{ $count }}</td>
            <td align="center" width="5%">                  
              <button onclick="pullNhsoData('{{ $row->vstdate }}', '{{ $row->cid }}')" class="btn btn-outline-info btn-sm w-100">
                  ดึงปิดสิทธิ
              </button>
            </td>  
            <td align="center" @if($row->auth_code == 'Y') style="color:green"
              @elseif($row->auth_code == 'N') style="color:red" @endif>
              <strong>{{ $row->auth_code }}</strong></td>               
            <td align="center" @if($row->endpoint == 'Y') style="color:green"
              @elseif($row->endpoint == 'N') style="color:red" @endif>
              <strong>{{ $row->endpoint }}</strong></td>
            <td align="center" width="6%">{{ DateThai($row->vstdate) }} {{$row->vsttime}}</td>
            <td align="center">{{ $row->oqueue }}</td>   
            <td align="left" width="10%">{{$row->ptname}}</td> 
            <td align="center">{{$row->cid}}</td>
            <td align="center">{{$row->mobile_phone_number}}</td>
            <td align="left" width="10%">{{$row->pttype}} [{{$row->hospmain}}]</td>
            <td align="right">{{ $row->pdx }}</td>
            <td align="right">{{ number_format($row->income,2) }}</td> 
            <td align="right">{{ number_format($row->rcpt_money,2) }}</td> 
            <td align="right">{{ number_format($row->debtor,2) }}</td> 
            <td align="left">{{$row->claim_list}}</td>
            <td align="right">{{ number_format($row->claim_price,2) }}</td>     
            <td align="right">{{ $row->project }}</td>
            <td class= "text-primary" align="right">{{ number_format($row->rep_nhso,2) }}</td>
            <td class= "text-primary" align="center">{{ $row->rep_error }}</td>
            <td align="center">{{ number_format($row->receive_total,2) }}</td>
            <td align="center" @if($row->receive_total-$row->claim_price > 0) style="color:green" 
                @elseif($row->receive_total-$row->claim_price < 0) style="color:red" @endif>
                {{ number_format($row->receive_total-$row->claim_price,2) }}</td>
            <td class= "text-primary" align="center">{{ $row->repno }}</td>                   
          </tr>
          <?php $count++; ?>
          <?php $sum_income += $row->income ; ?>
          <?php $sum_rcpt_money += $row->rcpt_money ; ?>
          <?php $sum_debtor += $row->debtor ; ?>
          <?php $sum_claim_price += $row->claim_price ; ?>
          <?php $sum_rep_nhso += $row->rep_nhso ; ?>
          <?php $sum_receive_total += $row->receive_total ; ?>
          @endforeach                 
        </tbody>
      </table>
      <div>
        <h5 class="text-primary text-center">
          รักษาทั้งหมด: <strong>{{ number_format($sum_income,2)}}</strong> บาท |
          ชำระเอง: <strong>{{ number_format($sum_rcpt_money,2)}}</strong> บาท |
          เรียกเก็บ: <strong>{{ number_format($sum_claim_price,2)}}</strong> บาท |
          ชดเชย: <strong  @if($sum_receive_total > 0) style="color:green" 
                    @elseif($sum_receive_total < 0) style="color:red" @endif>
                    {{ number_format($sum_receive_total,2)}}</strong> บาท |
          ผลต่าง: <strong  @if($sum_receive_total-$sum_claim_price > 0) style="color:green" 
                    @elseif($sum_receive_total-$sum_claim_price < 0) style="color:red" @endif>
                    {{ number_format($sum_receive_total-$sum_claim_price,2)}}</strong> บาท
        </h5>
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
      $('#t_search').DataTable({
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
              title: 'รายชื่อผู้มารับบริการ UC-OP บริการเฉพาะ CR วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

