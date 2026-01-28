@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card-body">            
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
                    <div class="col-md-2" >                            
                        <button type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button>                        
                    </div>
                </div>
            </form> 
            <div class="alert alert-primary text-primary" role="alert"><strong>ตรวจสอบค่ารักษาพยาบาลก่อนดึงลูกหนี้ วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
            <div class="row">
                <div class="col-md-6">   
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="table-success">
                            <th class="text-center" colspan = "8">ผู้ป่วยนอก</th>            
                        </tr>  
                        <tr class="table-secondary">
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด [ใบสั่งยา]</th>
                            <th class="text-center">ต้องชำระเงิน [ใบสั่งยา]</th>
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด [สรุป]</th>
                            <th class="text-center">ต้องชำระเงิน [สรุป]</th>  
                            <th class="text-center">ชำระเงินแล้ว [สรุป]</th> 
                            <th class="text-center">ลูกหนี้ [สรุป]</th>                           
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">รายตัว</th>
                        </tr>     
                        </thead>                         
                        @foreach($check_income as $row) 
                        @php
                            $op = number_format($row->op_income ?? 0, 2, '.', '');
                            $vn = number_format($row->vn_income ?? 0, 2, '.', '');
                        @endphp           
                        <tr>  
                            <td align="right" style="color: {{ $op === $vn ? 'green' : 'red' }}">
                                {{ number_format($row->op_income,2) }}
                            </td>
                            <td align="right">{{ number_format($row->op_paid,2) }}</td>
                            <td align="right" style="color: {{ $op === $vn ? 'green' : 'red' }}">
                                {{ number_format($row->vn_income,2) }}
                            </td>
                            <td align="right">{{ number_format($row->vn_paid,2) }}</td>  
                            <td align="right">{{ number_format($row->vn_rcpt,2) }}</td>  
                            <td align="right" class="text-success"><strong>{{ number_format($row->vn_debtor,2) }}</strong></td>                         
                            <td class="text-center"@if($row->status_check == 'Success') style="color:green"
                                @elseif($row->status_check == 'Resync VN') style="color:red" @endif>
                                {{ $row->status_check }}
                            </td>
                            <td class="text-center">
                                <button type="button"
                                    class="btn btn-warning btn-sm btn-detail"
                                    data-type="opd"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal">
                                    ตรวจสอบ
                                </button>
                            </td>
                        </tr>                        
                        @endforeach 
                    </table> 
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="table-success">
                            <th class="text-center" colspan = "8">ผู้ป่วยนอก แยกกลุ่มสิทธิ</th>            
                        </tr>  
                        <tr class="table-secondary">
                            <th class="text-center">INSCL</th>
                            <th class="text-center">กลุ่มสิทธิ</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด</th>
                            <th class="text-center">ต้องชำระเงิน</th>  
                            <th class="text-center">ชำระเงินแล้ว</th> 
                            <th class="text-center">PPFS</th> 
                            <th class="text-center">ลูกหนี้</th> 
                        </tr>     
                        </thead>
                        @php
                            $sum_visit = 0;
                            $sum_income = 0;
                            $sum_paid = 0;
                            $sum_rcpt = 0;
                            $sum_ppfs = 0;
                            $sum_debtor = 0;
                        @endphp
                        @foreach($check_income_pttype as $row)          
                        <tr>  
                            <td class="text-center">{{ $row->inscl }}</td>
                            <td class="text-left">{{ $row->pttype_group }}</td>
                            <td class="text-end">{{ number_format($row->visit) }}</td>
                            <td align="right">{{ number_format($row->income,2) }}</td>
                            <td align="right">{{ number_format($row->paid_money,2) }}</td>  
                            <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                            <td align="right">{{ number_format($row->ppfs,2) }}</td>  
                            <td align="right" class="text-success">{{ number_format($row->debtor,2) }}</td> 
                        </tr>
                        @php
                            $sum_visit += $row->visit;
                            $sum_income += $row->income;
                            $sum_paid += $row->paid_money;
                            $sum_rcpt += $row->rcpt_money;
                            $sum_ppfs += $row->ppfs;
                            $sum_debtor += $row->debtor;
                        @endphp
                        @endforeach 
                        <tr>
                            <td class="text-end" colspan="2"><strong>รวม</strong></td>
                            <td align="right"><strong>{{ number_format($sum_visit) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_income,2) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_paid,2) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_rcpt,2) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_ppfs,2) }}</strong></td>
                            <td align="right" class="text-success"><strong>{{ number_format($sum_debtor,2) }}</strong></td>
                        </tr>
                    </table> 
                </div>
                <div class="col-md-6">   
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="table-danger">
                            <th class="text-center" colspan = "8">ผู้ป่วยใน</th>            
                        </tr>  
                        <tr class="table-secondary">
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด [ใบสั่งยา]</th>
                            <th class="text-center">ต้องชำระเงิน [ใบสั่งยา]</th>
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด [สรุป]</th>
                            <th class="text-center">ต้องชำระเงิน [สรุป]</th>  
                            <th class="text-center">ชำระเงินแล้ว [สรุป]</th> 
                            <th class="text-center">ลูกหนี้ [สรุป]</th>                           
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">รายตัว</th>
                        </tr>         
                        </thead>
                        @foreach($check_income_ipd as $row) 
                        @php
                            $op = number_format($row->op_income ?? 0, 2, '.', '');
                            $an = number_format($row->an_income ?? 0, 2, '.', '');
                        @endphp         
                        <tr> 
                            <td align="right" style="color: {{ $op === $an ? 'green' : 'red' }}">
                                {{ number_format($row->op_income,2) }}
                            </td>
                            <td align="right">{{ number_format($row->op_paid,2) }}</td>
                            <td align="right" style="color: {{ $op === $an ? 'green' : 'red' }}">
                                {{ number_format($row->an_income,2) }}
                            </td>
                            <td align="right">{{ number_format($row->an_paid,2) }}</td>  
                            <td align="right">{{ number_format($row->an_rcpt,2) }}</td>  
                            <td align="right" class="text-success"><strong>{{ number_format($row->an_debtor,2) }}</strong></td>                         
                            <td class="text-center" @if($row->status_check == 'Success') style="color:green"
                                @elseif($row->status_check == 'Resync AN') style="color:red" @endif>
                                {{ $row->status_check }}
                            </td>
                            <td class="text-center">
                                <button type="button"
                                class="btn btn-warning btn-sm btn-detail"
                                data-type="ipd"
                                data-bs-toggle="modal"
                                data-bs-target="#detailModal">
                                ตรวจสอบ
                            </button>
                            </td>
                        </tr>
                        @endforeach 
                    </table> 
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="table-danger">
                            <th class="text-center" colspan = "7">ผู้ป่วยใน แยกกลุ่มสิทธิ</th>            
                        </tr>  
                        <tr class="table-secondary">
                            <th class="text-center">INSCL</th>
                            <th class="text-center">กลุ่มสิทธิ</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด</th>
                            <th class="text-center">ต้องชำระเงิน</th>  
                            <th class="text-center">ชำระเงินแล้ว</th> 
                            <th class="text-center">ลูกหนี้</th> 
                        </tr>     
                        </thead>
                        @php
                            $sum_an = 0;
                            $sum_income = 0;
                            $sum_paid = 0;
                            $sum_rcpt = 0;
                            $sum_debtor = 0;
                        @endphp
                        @foreach($check_income_ipd_pttype as $row)          
                        <tr>  
                            <td class="text-center">{{ $row->inscl }}</td>
                            <td class="text-left">{{ $row->pttype_group }}</td>
                            <td align="right">{{ number_format($row->an) }}</td>
                            <td align="right">{{ number_format($row->income,2) }}</td>
                            <td align="right">{{ number_format($row->paid_money,2) }}</td>  
                            <td align="right">{{ number_format($row->rcpt_money,2) }}</td> 
                            <td align="right" class="text-success">{{ number_format($row->debtor,2) }}</td> 
                        </tr>
                        @php
                            $sum_an += $row->an;
                            $sum_income += $row->income;
                            $sum_paid += $row->paid_money;
                            $sum_rcpt += $row->rcpt_money;
                            $sum_debtor += $row->debtor;
                        @endphp
                        @endforeach 
                        <tr>
                            <td class="text-end" colspan="2"><strong>รวม</strong></td>
                            <td align="right"><strong>{{ number_format($sum_an) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_income,2) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_paid,2) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_rcpt,2) }}</strong></td>
                            <td align="right" class="text-success"><strong>{{ number_format($sum_debtor,2) }}</strong></td>
                        </tr>
                    </table> 
                </div>
            </div>
        </div>    
    </div>

{{-- Modal --}}
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-primary" id="detailModalTitle">รายละเอียดรายตัว</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered w-100" id="detailTable">
                        <thead class="table-primary">
                            <tr>
                                <th id="th-date" class="text-center"></th>
                                <th id="th-anvn" class="text-center"></th>
                                <th class="text-center">HN</th>
                                <th id="th-stat" class="text-end"></th>
                                <th class="text-end">opitemrece [ใบสั่งยา]</th>
                                <th class="text-end">Diff</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    กำลังโหลดข้อมูล...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
{{-- End Modal --}}

@endsection

<script>
    let detailDT = null;
    let currentType = null;
    document.addEventListener("DOMContentLoaded", function () {
        // จำ type ตอนกดปุ่ม
        document.querySelectorAll('.btn-detail').forEach(btn => {
            btn.addEventListener('click', function () {
                currentType = this.dataset.type; // opd | ipd
            });
        });
        // เมื่อ modal แสดงเสร็จแล้ว
        $('#detailModal').on('shown.bs.modal', function () {
            // ตั้งหัวตาราง
            if (currentType === 'opd') {
                $('#detailModalTitle').text('รายละเอียดรายตัว (OPD)');
                $('#th-date').text('วันที่รับบริการ');
                $('#th-anvn').text('VN');
                $('#th-stat').text('vn_stat [สรุป]');
            } else {
                $('#detailModalTitle').text('รายละเอียดรายตัว (IPD)');
                $('#th-date').text('วันที่จำหน่าย');
                $('#th-anvn').text('AN');
                $('#th-stat').text('an_stat [สรุป]');
            }
            // ถ้ามี DataTable เดิม → destroy
            if (detailDT) {
                detailDT.destroy();
                detailDT = null;
            }
            let tbody = $('#detailTable tbody');
            tbody.html('<tr><td colspan="6" class="text-center text-muted">กำลังโหลดข้อมูล...</td></tr>');

            fetch("{{ url('debtor/check_income_detail') }}?type=" + currentType)
                .then(res => res.json())
                .then(data => {
                    tbody.empty();
                    if (!data.length) {
                        tbody.html('<tr><td colspan="6" class="text-center text-muted">ไม่พบข้อมูล</td></tr>');
                        return;
                    }
                    data.forEach(row => {
                        tbody.append(`
                            <tr>
                                <td class="text-center">${row.date_serv}</td>
                                <td class="text-center">${row.anvn}</td>
                                <td class="text-center">${row.hn}</td>
                                <td class="text-end">${Number(row.income).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                                <td class="text-end">${Number(row.sum_price).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                                <td class="text-end text-danger">${Number(row.diff).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                            </tr>
                        `);
                    });
                    // init DataTable หลัง data มาแล้ว
                    detailDT = $('#detailTable').DataTable({
                        paging: true,
                        searching: true,
                        ordering: true,
                        pageLength: 10,
                        autoWidth: false,
                        responsive: true
                    });
                });
        });
        // ปิด modal → destroy
        $('#detailModal').on('hidden.bs.modal', function () {
            if (detailDT) {
                detailDT.destroy();
                detailDT = null;
            }
            $('#detailTable tbody').empty();
        });
    });
</script>

