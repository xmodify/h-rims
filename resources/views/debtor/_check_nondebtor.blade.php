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
            <br> 
            <div class="alert alert-success text-primary" role="alert"><strong>รายชื่อผู้มารับบริการที่ยังไม่ยืนยันลูกหนี้ วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</strong></div>
            <div class="row">
                <div class="col-md-12">   
                    <table id="nondebtor" class="table table-bordered table-striped">
                        <thead> 
                            <tr class="table-primary">
                                <th class="text-center">ประเภท</th>
                                <th class="text-center">วันที่มารับริการ/จำหน่าย</th>
                                <th class="text-center">VN/AN</th>
                                <th class="text-center">HN</th>  
                                <th class="text-center">ชื่อ-สกุล</th> 
                                <th class="text-center">INSCL</th>                           
                                <th class="text-center">สิทธิการรักษา</th>
                                <th class="text-center">สถานพยาบาลหลัก</th>
                                <th class="text-center">PDX</th>
                                <th class="text-center">ค่ารักษาทั้งหมด</th>
                                <th class="text-center">ต้องชำระเงิน</th>
                                <th class="text-center">ชำระเงินแล้ว</th>
                                <th class="text-center">PPFS</th>
                                <th class="text-center">ลูกหนี้</th>
                                <th class="text-center">รายการ PPFS</th>
                            </tr>        
                        </thead>
                        <tbody>
                            <?php $sum_income = 0 ; ?>
                            <?php $sum_paid_money = 0 ; ?>
                            <?php $sum_rcpt_money = 0 ; ?>
                            <?php $sum_ppfs_price = 0 ; ?>
                            <?php $sum_debtor = 0 ; ?>
                            @foreach($check as $row)          
                            <tr>
                                <td align="center">{{ $row->dep }}</td>  
                                <td align="center">{{ DateThai($row->serv_date) }}</td>  
                                <td align="right">{{ $row->vnan }}</td>
                                <td align="center">{{ $row->hn }}</td>  
                                <td align="left">{{ $row->ptname }}</td> 
                                <td align="right">{{ $row->hipdata_code }}</td>
                                <td align="left">{{ $row->pttype }}</td> 
                                <td align="center">{{ $row->hospmain }}</td> 
                                <td align="right">{{ $row->pdx }}</td>
                                <td align="right">{{ number_format($row->income,2) }}</td>  
                                <td align="right">{{ number_format($row->paid_money,2) }}</td> 
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td> 
                                <td align="right">{{ number_format($row->ppfs_price,2) }}</td> 
                                <td align="right">{{ number_format($row->debtor,2) }}</td> 
                                <td align="left">{{ $row->ppfs_list }}</td> 
                            </tr>     
                            <?php $sum_income += $row->income ; ?>
                            <?php $sum_paid_money += $row->paid_money ; ?>
                            <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                            <?php $sum_ppfs_price += $row->ppfs_price ; ?>
                            <?php $sum_debtor += $row->debtor ; ?>                       
                            @endforeach                            
                        </tbody> 
                        <tfoot>
                            <tr class="table-primary fw-bold">
                                <th colspan="9" class="text-end">รวม</th>
                                <th class="text-end">{{ number_format($sum_income,2) }}</th>
                                <th class="text-end">{{ number_format($sum_paid_money,2) }}</th>
                                <th class="text-end">{{ number_format($sum_rcpt_money,2) }}</th>
                                <th class="text-end">{{ number_format($sum_ppfs_price,2) }}</th>
                                <th class="text-end">{{ number_format($sum_debtor,2) }}</th>
                                <th class="text-end"></th>
                            </tr>
                        </tfoot>
                    </table> 
                </div>                
            </div>
        </div>    
    </div>
@endsection

@push('scripts')    
    <script>
        $(document).ready(function () {
        $('#nondebtor').DataTable({
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
                className: 'btn btn-success btn-sm',
                title: 'รายชื่อผู้มารับบริการที่ยังไม่ยืนยันลูกหนี้ วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

