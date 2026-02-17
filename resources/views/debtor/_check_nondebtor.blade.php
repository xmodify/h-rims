@extends('layouts.app')

@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-person-exclamation me-2"></i>
                รายชื่อผู้มารับบริการที่ยังไม่ยืนยันลูกหนี้
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
                <div class="col-md-12">   
                    <div class="table-responsive">
                        <table id="nondebtor" class="table table-hover table-modern align-middle mb-0" style="width:100%">
                            <thead> 
                                <tr class="table-secondary">
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

