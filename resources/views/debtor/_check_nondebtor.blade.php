@extends('layouts.app')

@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-person-exclamation me-2"></i>
                เธฃเธฒเธขเธเธทเนเธญเธเธนเนเธกเธฒเธฃเธฑเธเธเธฃเธดเธเธฒเธฃเธ—เธตเนเธขเธฑเธเนเธกเนเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เน€เธฅเธทเธญเธเธเนเธงเธเธงเธฑเธเธ—เธตเน</span>
                    <div class="input-group input-group-sm">
                        <input type="date" name="start_date" class="form-control" value="{{ $start_date }}" style="width: 130px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0">เธ–เธถเธ</span>
                        <input type="date" name="end_date" class="form-control" value="{{ $end_date }}" style="width: 130px;">
                        <button type="submit" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-search me-1"></i> เธเนเธเธซเธฒ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card dash-card border-0">
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-calendar-check-fill text-primary me-2"></i>
                เธเนเธญเธกเธนเธฅเธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}
            </h6>
        </div>
        <div class="card-body px-4 pb-4 pt-3">
            <div class="row">
                <div class="col-md-12">   
                    <div class="table-responsive">
                        <table id="nondebtor" class="table table-hover table-modern align-middle mb-0" style="width:100%">
                            <thead> 
                                <tr class="table-secondary">
                                    <th class="text-center">เธเธฃเธฐเน€เธ เธ—</th>
                                    <th class="text-center">เธงเธฑเธเธ—เธตเนเธกเธฒเธฃเธฑเธเธฃเธดเธเธฒเธฃ/เธเธณเธซเธเนเธฒเธข</th>
                                    <th class="text-center">VN/AN</th>
                                    <th class="text-center">HN</th>  
                                    <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th> 
                                    <th class="text-center">INSCL</th>                           
                                    <th class="text-center">เธชเธดเธ—เธเธดเธเธฒเธฃเธฃเธฑเธเธฉเธฒ</th>
                                    <th class="text-center">เธชเธ–เธฒเธเธเธขเธฒเธเธฒเธฅเธซเธฅเธฑเธ</th>
                                    <th class="text-center">PDX</th>
                                    <th class="text-center">เธเนเธฒเธฃเธฑเธเธฉเธฒเธ—เธฑเนเธเธซเธกเธ”</th>
                                    <th class="text-center">เธ•เนเธญเธเธเธณเธฃเธฐเน€เธเธดเธ</th>
                                    <th class="text-center">เธเธณเธฃเธฐเน€เธเธดเธเนเธฅเนเธง</th>
                                    <th class="text-center">PPFS</th>
                                    <th class="text-center">เธฅเธนเธเธซเธเธตเน</th>
                                    <th class="text-center">เธฃเธฒเธขเธเธฒเธฃ PPFS</th>
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
                                    <th colspan="9" class="text-end">เธฃเธงเธก</th>
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
                    '<"col-md-6"l>' + // Show เธฃเธฒเธขเธเธฒเธฃ
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
                title: 'เธฃเธฒเธขเธเธทเนเธญเธเธนเนเธกเธฒเธฃเธฑเธเธเธฃเธดเธเธฒเธฃเธ—เธตเนเธขเธฑเธเนเธกเนเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}'
                }
            ],
            language: {
                search: "เธเนเธเธซเธฒ:",
                lengthMenu: "เนเธชเธ”เธ _MENU_ เธฃเธฒเธขเธเธฒเธฃ",
                info: "เนเธชเธ”เธ _START_ เธ–เธถเธ _END_ เธเธฒเธเธ—เธฑเนเธเธซเธกเธ” _TOTAL_ เธฃเธฒเธขเธเธฒเธฃ",
                paginate: {
                previous: "เธเนเธญเธเธซเธเนเธฒ",
                next: "เธ–เธฑเธ”เนเธ"
                }
            }
        });
        });
    </script>
@endpush

