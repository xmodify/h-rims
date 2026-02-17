@extends('layouts.app')

@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-currency-exchange me-2"></i>
                ตรวจสอบค่ารักษาพยาบาลก่อนดึงลูกหนี้
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกช่วงวันที่</span>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                        <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">

                        <input type="text" id="start_date_picker" class="form-control datepicker_th" value="{{ $start_date }}" style="width: 130px;" readonly>
                        <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>
                        <input type="text" id="end_date_picker" class="form-control datepicker_th" value="{{ $end_date }}" style="width: 130px;" readonly>
                        
                        <button type="submit" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-search me-1"></i> ค้นหา
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
    <script>
        $(document).ready(function () {
            // Initialize Datepicker Thai
            $('.datepicker_th').datepicker({
                format: 'yyyy-mm-dd',
                todayBtn: "linked",
                todayHighlight: true,
                autoclose: true,
                language: 'th-th',
                thaiyear: true
            });

            // Set initial values for Datepickers
            var start_date_val = "{{ $start_date }}";
            var end_date_val = "{{ $end_date }}";

            if(start_date_val) {
                $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
            }
            if(end_date_val) {
                $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
            }

            // Sync Changes from Picker to Hidden Input
            $('#start_date_picker').on('changeDate', function(e) {
                var date = e.date;
                if(date) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear();
                    $('#start_date').val(year + "-" + month + "-" + day);
                } else {
                    $('#start_date').val('');
                }
            });

            $('#end_date_picker').on('changeDate', function(e) {
                var date = e.date;
                if(date) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear();
                    $('#end_date').val(year + "-" + month + "-" + day);
                } else {
                    $('#end_date').val('');
                }
            });
        });
    </script>
@endpush

    <!-- Main Content Card -->
    <div class="card dash-card border-0">
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-calendar-check-fill text-primary me-2"></i>
                ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}
            </h6>
        </div>
        <div class="card-body px-4 pb-4 pt-3">
            <div class="row">
                <div class="col-md-6 border-end">   
                    <h6 class="fw-bold text-success mb-3 border-bottom pb-2">
                        <i class="bi bi-person-fill me-2"></i>ผู้ป่วยนอก
                    </h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <thead>
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
                                        class="btn btn-warning btn-sm shadow-sm btn-detail"
                                        data-type="opd"
                                        data-bs-toggle="modal"
                                        data-bs-target="#detailModal">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </td>
                            </tr>                        
                            @endforeach 
                        </table>
                    </div>
                    
                    <h6 class="fw-bold text-success mb-3 border-bottom pb-2">
                        <i class="bi bi-layers-fill me-2"></i>ผู้ป่วยนอก แยกกลุ่มสิทธิ
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <thead>
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
                                $sum_vn = 0;
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
                                <td class="text-end">{{ number_format($row->vn) }}</td>
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->paid_money,2) }}</td>  
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                <td align="right">{{ number_format($row->ppfs,2) }}</td>  
                                <td align="right" class="text-success">{{ number_format($row->debtor,2) }}</td> 
                            </tr>
                            @php
                                $sum_vn += $row->vn;
                                $sum_income += $row->income;
                                $sum_paid += $row->paid_money;
                                $sum_rcpt += $row->rcpt_money;
                                $sum_ppfs += $row->ppfs;
                                $sum_debtor += $row->debtor;
                            @endphp
                            @endforeach 
                            <tfoot>
                            <tr class="table-success fw-bold">
                                <td class="text-end" colspan="2">รวม</td>
                                <td align="right">{{ number_format($sum_vn) }}</td>
                                <td align="right">{{ number_format($sum_income,2) }}</td>
                                <td align="right">{{ number_format($sum_paid,2) }}</td>
                                <td align="right">{{ number_format($sum_rcpt,2) }}</td>
                                <td align="right">{{ number_format($sum_ppfs,2) }}</td>
                                <td align="right" class="text-success">{{ number_format($sum_debtor,2) }}</td>
                            </tr>
                            </tfoot>
                        </table> 
                    </div>
                </div>

                <div class="col-md-6">   
                    <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">
                        <i class="bi bi-person-fill-add me-2"></i>ผู้ป่วยใน
                    </h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <thead>
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
                                    class="btn btn-warning btn-sm shadow-sm btn-detail"
                                    data-type="ipd"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal">
                                    <i class="bi bi-search"></i>
                                </button>
                                </td>
                            </tr>
                            @endforeach 
                        </table>
                    </div>

                    <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">
                        <i class="bi bi-layers-fill me-2"></i>ผู้ป่วยใน แยกกลุ่มสิทธิ
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <thead>
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
                            <tfoot>
                            <tr class="table-danger fw-bold">
                                <td class="text-end" colspan="2">รวม</td>
                                <td align="right">{{ number_format($sum_an) }}</td>
                                <td align="right">{{ number_format($sum_income,2) }}</td>
                                <td align="right">{{ number_format($sum_paid,2) }}</td>
                                <td align="right">{{ number_format($sum_rcpt,2) }}</td>
                                <td align="right" class="text-success">{{ number_format($sum_debtor,2) }}</td>
                            </tr>
                            </tfoot>
                        </table> 
                    </div>
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

@push('scripts')
<script>
    let detailDT = null;
    let currentType = null;
    $(document).ready(function () {
        // จำ type ตอนกดปุ่ม
        $(document).on('click', '.btn-detail', function () {
            currentType = $(this).data('type'); // opd | ipd
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
@endpush

