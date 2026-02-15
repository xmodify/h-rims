@extends('layouts.app')
    <script>
        function toggle_d(source) {
            checkbox = document.getElementsByName('checkbox_d[]');
            for (var i = 0; i < checkbox.length; i++) {
                checkbox[i].checked = source.checked;
            }
        }
    </script>
    <script>
        function toggle(source) {
            checkboxes = document.getElementsByName('checkbox[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>    
    <script>
        function toggle_iclaim(source) {
            checkboxes = document.getElementsByName('checkbox_iclaim[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script> 
@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                1102050102.107-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเนเธฒเธฃเธฐเน€เธเธดเธ IP
            </h4>
            <small class="text-muted">เธเนเธญเธกเธนเธฅเธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center gap-2">
                    @csrf
                    
                    <!-- Date Range -->
                    <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start"><i class="bi bi-calendar-event me-1"></i> เธงเธฑเธเธ—เธตเน</span>
                        <input type="date" name="start_date" class="form-control border-start-0 rounded-0" value="{{ $start_date }}" style="width: 170px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">เธ–เธถเธ</span>
                        <input type="date" name="end_date" class="form-control border-start-0 rounded-end" value="{{ $end_date }}" style="width: 170px;">
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input id="search" type="text" class="form-control border-start-0" name="search" value="{{ $search }}" placeholder="เธเนเธเธซเธฒ เธเธทเนเธญ-เธชเธเธธเธฅ, HN, AN">
                    </div>

                    <button onclick="fetchData()" type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-search me-1"></i> เธเนเธเธซเธฒ
                    </button>
                    <a href="{{ url('debtor/forget_search') }}" class="btn btn-warning btn-sm px-3 shadow-sm text-dark">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> เธฃเธตเน€เธเนเธ•
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div class="card dash-card border-0">
        
        <!-- Section: Tabs -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="debtor-tab" data-bs-toggle="pill" data-bs-target="#debtor-pane" type="button" role="tab">
                        <i class="bi bi-person-lines-fill me-1 text-success"></i> <span class="text-success fw-bold">เธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน</span>
                        <span class=" badge bg-primary-soft text-primary ms-2">{{ count($debtor) }}</span>
                    </button>
                </li>       
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab">
                        <i class="bi bi-cash-stack me-1"></i> เธเธณเธฃเธฐเน€เธเธดเธ IP
                        <span class="badge bg-warning-soft text-warning ms-2">{{ count($debtor_search) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="iclaim-tab" data-bs-toggle="pill" data-bs-target="#iclaim-pane" type="button" role="tab">
                        <i class="bi bi-shield-check me-1"></i> iClaim
                        <span class="badge bg-info-soft text-info ms-2">{{ count($debtor_search_iclaim) }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: เธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน -->
                <div class="tab-pane fade show active" id="debtor-pane" role="tabpanel"> 
                    <form action="{{ url('debtor/1102050102_107_delete') }}" method="POST" enctype="multipart/form-data">
                        @csrf   
                        @method('DELETE')
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                                <i class="bi bi-trash-fill me-1"></i> เธฅเธเธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน
                            </button>
                            <div>
                                <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050102_107_indiv_excel')}}" target="_blank">
                                     <i class="bi bi-file-earmark-excel me-1"></i> เธชเนเธเธญเธญเธเธฃเธฒเธขเธ•เธฑเธง
                                </a>                
                                <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_107_daily_pdf')}}" target="_blank">
                                     <i class="bi bi-printer me-1"></i> เธเธดเธกเธเนเธฃเธฒเธขเธงเธฑเธ
                                </a> 
                            </div>
                        </div>
                        <table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-success">
                                <th class="text-left text-primary" colspan = "12">1102050102.107-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเนเธฒเธฃเธฐเน€เธเธดเธ IP เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}</th> 
                                <th class="text-center text-primary" colspan = "8">เธเธฒเธฃเธเธ”เน€เธเธข</th>                                                 
                            </tr>
                            <tr class="table-success">
                                <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"></th>
                                <th class="text-center">HN</th>
                                <th class="text-center">AN</th>
                                <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th>   
                                <th class="text-center">เน€เธเธญเธฃเนเนเธ—เธฃ.</th>
                                <th class="text-center">เธชเธดเธ—เธเธด</th>
                                <th class="text-center">Admit</th> 
                                <th class="text-center">Discharge</th> 
                                <th class="text-center">ICD10</th> 
                                <th class="text-center">เธเนเธฒเนเธเนเธเนเธฒเธขเธ—เธฑเนเธเธซเธกเธ”</th> 
                                <th class="text-center">เธ•เนเธญเธเธเธณเธฃเธฐเน€เธเธดเธ</th> 
                                <th class="text-center">เธเธณเธฃเธฐเน€เธญเธ</th>
                                <th class="text-center text-primary">เธฅเธนเธเธซเธเธตเน</th>
                                <th class="text-center text-primary">เธเธ”เน€เธเธข</th> 
                                <th class="text-center text-primary">เธเธฅเธ•เนเธฒเธ</th> 
                                <th class="text-center text-primary" width="6%">เธชเธ–เธฒเธเธฐ</th> 
                                <th class="text-center text-primary">เน€เธฅเธเธ—เธตเนเนเธเน€เธชเธฃเนเธ</th>   
                                <th class="text-center text-primary">เธญเธฒเธขเธธเธซเธเธตเน</th>   
                                <th class="text-center text-primary" width="6%">Action</th> 
                                <th class="text-center text-primary">Lock</th>   
                            </tr>
                            </thead>
                            <?php $count = 1 ; ?>
                            <?php $sum_income = 0 ; ?>
                            <?php $sum_paid_money  = 0 ; ?>
                            <?php $sum_rcpt_money = 0 ; ?>
                            <?php $sum_debtor = 0 ; ?>
                            <?php $sum_receive  = 0 ; ?>
                            @foreach($debtor as $row)
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->an}}"></td> 
                                <td align="center">{{ $row->hn }}</td>
                                <td align="center">{{ $row->an }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="right">{{ $row->mobile_phone_number }}</td>
                                <td align="left">{{ $row->pttype }}</td>
                                <td align="right">{{ DateThai($row->regdate) }}</td>
                                <td align="right">{{ DateThai($row->dchdate) }}</td>           
                                <td align="center">{{ $row->pdx }}</td>     
                                <td align="right">{{ number_format($row->income,2) }}</td>   
                                <td align="right">{{ number_format($row->paid_money,2) }}</td>   
                                <td align="right">{{ number_format($row->rcpt_money,2) }} </td> 
                                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td> 
                                <td align="right" @if($row->receive > 0) style="color:green" 
                                    @elseif($row->receive < 0) style="color:red" @endif>
                                    {{ number_format($row->receive,2) }}
                                </td>
                                <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                                    @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                                    {{ number_format($row->receive-$row->debtor,2) }}
                                </td> 
                                <td align="right">{{ $row->repno ?? '' }} {{ $row->rcpno ?? '' }}</td>                    
                                <td align="right" width="7%">{{ $row->status ?? '' }}</td> 
                                <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  
                                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" 
                                    @else style="background-color: #FF7F7F;" @endif >
                                    {{ $row->days }} เธงเธฑเธ
                                </td>   
                                <td align="center" width="9%">
                                    <div class="d-flex flex-column gap-1">
                                        @if($row->bill_amount == '')          
                                            <button type="button" class="btn btn-outline-warning btn-sm px-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#receive-{{ str_replace('/', '-', $row->an) }}"> 
                                                <i class="bi bi-cash-stack"></i> เธเธ”เน€เธเธข
                                            </button>
                                        @endif 
                                        <a class="btn btn-outline-info btn-sm" href="{{ url('debtor/1102050102_107/tracking', $row->an) }}" target="_blank"> 
                                            <i class="bi bi-geo-alt me-1"></i> เธ•เธดเธ”เธ•เธฒเธก ({{ $row->visit }})
                                        </a> 
                                    </div>
                                </td> 
                                <td align="center" style="color:blue">{{ $row->debtor_lock }}</td>                         
                            <?php $count++; ?>
                            <?php $sum_income += $row->income ; ?>
                            <?php $sum_paid_money  += $row->paid_money ; ?>
                            <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                            <?php $sum_debtor += $row->debtor ; ?> 
                            <?php $sum_receive += $row->receive ; ?>       
                            @endforeach 
                            </tr>   
                            <tfoot>
                                <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="9" class="text-end">เธฃเธงเธก</td>
                                    <td class="text-end">{{ number_format($sum_income,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_paid_money,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                                    <td class="text-end" style="@if($sum_receive > 0) color:green @elseif($sum_receive < 0) color:red @endif">{{ number_format($sum_receive,2) }}</td>
                                    <td class="text-end" style="@if(($sum_receive-$sum_debtor) > 0) color:green @elseif(($sum_receive-$sum_debtor) < 0) color:red @endif">{{ number_format($sum_receive-$sum_debtor,2) }}</td>
                                    <td colspan="5"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>

                <!-- Tab 2: เธเธณเธฃเธฐเน€เธเธดเธ IP -->
                <div class="tab-pane fade" id="pay-pane" role="tabpanel"> 
                    <form action="{{ url('debtor/1102050102_107_confirm') }}" method="POST" enctype="multipart/form-data">
                        @csrf                
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit()">
                                <i class="bi bi-check-circle me-1"></i> เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน
                            </button>
                            <div></div>
                        </div>
                        <table id="debtor_search" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-secondary">
                                <th class="text-left text-primary" colspan = "18">1102050102.107-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเนเธฒเธฃเธฐเน€เธเธดเธ IP เธฃเธญเธขเธทเธเธขเธฑเธ เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }} เธฃเธญเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน</th>                         
                            </tr>
                            <tr class="table-secondary">
                                <th class="text-center"><input type="checkbox" onClick="toggle(this)"></th>  
                                <th class="text-center">เธ•เธถเธเธเธนเนเธเนเธงเธข</th>
                                <th class="text-center">HN</th>
                                <th class="text-center">AN</th>
                                <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th>              
                                <th class="text-center">เธญเธฒเธขเธธ</th>
                                <th class="text-center" width ="8%">เธชเธดเธ—เธเธด</th>
                                <th class="text-center" width ="6%">Admit</th>
                                <th class="text-center" width ="6%">Discharge</th>
                                <th class="text-center">ICD10</th>
                                <th class="text-center">AdjRW</th>
                                <th class="text-center">เธเนเธฒเธฃเธฑเธเธฉเธฒเธ—เธฑเนเธเธซเธกเธ”</th>  
                                <th class="text-center">เธ•เนเธญเธเธเธณเธฃเธฐ</th>   
                                <th class="text-center">เธเธณเธฃเธฐเน€เธญเธ</th>                                      
                                <th class="text-center">เธฅเธนเธเธซเธเธตเน</th>
                                <th class="text-center">เธเนเธฒเธเธเธณเธฃเธฐ</th>
                                <th class="text-center">เธเธฒเธเธกเธฑเธ”เธเธณ</th>
                                <th class="text-center">เธ–เธญเธเธกเธฑเธ”เธเธณ</th>
                            </tr>
                            </thead>
                            <?php $count = 1 ; ?>
                            @foreach($debtor_search as $row)
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox[]" value="{{$row->an}}"></td> 
                                <td align="left">{{$row->ward}}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="center">{{ $row->an }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="center">{{ $row->age_y }}</td>
                                <td align="left" width ="8%">{{ $row->pttype }}</td>
                                <td align="right" width ="6%">{{ DateThai($row->regdate) }}</td>
                                <td align="right" width ="6%">{{ DateThai($row->dchdate) }}</td>
                                <td align="right">{{ $row->pdx }}</td>      
                                <td align="right">{{ $row->adjrw }}</td>                        
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->paid_money,2) }}</td>  
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>  
                                <td align="right">{{ number_format($row->paid_money-$row->rcpt_money,2) }}</td>
                                <td align="right">{{ number_format($row->arrear_amount,2) }}</td>               
                                <td align="right">{{ number_format($row->deposit_amount,2) }}</td>    
                                <td align="right">{{ number_format($row->debit_amount,2) }}</td>   
                            <?php $count++; ?>
                            @endforeach 
                            </tr> 
                        </table>
                    </form>
                </div>

                <!-- Tab 3: iClaim -->
                <div class="tab-pane fade" id="iclaim-pane" role="tabpanel"> 
                    <form action="{{ url('debtor/1102050102_107_confirm_iclaim') }}" method="POST" enctype="multipart/form-data"> 
                        @csrf                
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_iclaim()">
                                <i class="bi bi-check-circle me-1"></i> เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน (iClaim)
                            </button>
                            <div></div>
                        </div>
                        <table id="debtor_search_iclaim" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-secondary">
                                <th class="text-center" colspan = "15">เธเธนเนเธกเธฒเธฃเธฑเธเธเธฃเธดเธเธฒเธฃเนเธเนเธเธฃเธฐเธเธฑเธเธเธตเธงเธดเธ• iClaim เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }} เธฃเธญเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน</th>      
                            </tr>
                            <tr class="table-secondary">
                                <th class="text-center"><input type="checkbox" onClick="toggle_iclaim(this)"></th>  
                                <th class="text-center">เธ•เธถเธเธเธนเนเธเนเธงเธข</th>
                                <th class="text-center">HN</th>
                                <th class="text-center">AN</th>
                                <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th>              
                                <th class="text-center">เธญเธฒเธขเธธ</th>
                                <th class="text-center" width = "15%">เธชเธดเธ—เธเธด</th>
                                <th class="text-center">Admit</th>
                                <th class="text-center">Discharge</th>
                                <th class="text-center">ICD10</th>
                                <th class="text-center">AdjRW</th>
                                <th class="text-center">เธเนเธฒเธฃเธฑเธเธฉเธฒเธ—เธฑเนเธเธซเธกเธ”</th>  
                                <th class="text-center">เธเธณเธฃเธฐเน€เธญเธ</th>
                                <th class="text-center">เธเธญเธเธ—เธธเธเธญเธทเนเธ</th>
                                <th class="text-center">เธฅเธนเธเธซเธเธตเน</th> 
                            </tr>
                            </thead>
                            <?php $count = 1 ; ?>
                            @foreach($debtor_search_iclaim as $row)
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox_iclaim[]" value="{{$row->an}}"></td>                   
                                <td align="right">{{$row->ward}}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="center">{{ $row->an }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="center">{{ $row->age_y }}</td>
                                <td align="left">{{ $row->pttype }}</td>
                                <td align="right">{{ DateThai($row->regdate) }}</td>
                                <td align="right">{{ DateThai($row->dchdate) }}</td>
                                <td align="right">{{ $row->pdx }}</td>      
                                <td align="right">{{ $row->adjrw }}</td>                        
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                <td align="right">{{ number_format($row->other,2) }}</td>
                                <td align="right">{{ number_format($row->debtor,2) }}</td>
                            <?php $count++; ?>
                            @endforeach 
                            </tr>   
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
<!-- Pills Tabs -->
        
        <!-- Modal เธเธฑเธเธ—เธถเธเธเธ”เน€เธเธข -->
    @foreach($debtor as $row)
        <div id="receive-{{ str_replace('/', '-', $row->an) }}" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white border-0 py-3">
                        <h5 class="modal-title d-flex align-items-center">
                            <i class="bi bi-cash-stack me-2"></i>
                            เธฃเธฒเธขเธเธฒเธฃเธเธฒเธฃเธเธ”เน€เธเธขเน€เธเธดเธ/เธฅเธนเธเธซเธเธตเน (AN: {{ $row->an }})
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>         
                    <form action="{{ url('debtor/1102050102_107/update', $row->an) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4">
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="p-3 rounded-3 bg-primary-soft mb-2">
                                        <div class="row align-items-center">
                                            <div class="col-md-7">
                                                <label class="text-muted small d-block">เธเธทเนเธญ-เธชเธเธธเธฅ</label>
                                                <span class="fw-bold text-primary fs-5">{{ $row->ptname }}</span>
                                            </div>
                                            <div class="col-md-5 text-md-end">
                                                <label class="text-muted small d-block">เธขเธญเธ”เธฅเธนเธเธซเธเธตเนเธเธเน€เธซเธฅเธทเธญ</label>
                                                <span class="fw-bold text-primary fs-5">{{ number_format($row->debtor, 2) }} เธเธฒเธ—</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Left Column: เธเธฒเธฃเน€เธฃเธตเธขเธเน€เธเนเธ -->
                                <div class="col-md-6 border-end">
                                    <h6 class="text-secondary fw-bold mb-3 d-flex align-items-center">
                                        <i class="bi bi-send-fill me-2 text-primary"></i> เธเนเธญเธกเธนเธฅเธเธฒเธฃเธชเนเธเน€เธเธดเธ (Charge)
                                    </h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">เธงเธฑเธเธ—เธตเนเน€เธฃเธตเธขเธเน€เธเนเธ</label>
                                        <input type="date" class="form-control rounded-pill px-3" name="charge_date" value="{{ $row->charge_date }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">เน€เธฅเธเธ—เธตเนเธซเธเธฑเธเธชเธทเธญเน€เธฃเธตเธขเธเน€เธเนเธ</label>
                                        <input type="text" class="form-control rounded-pill px-3" name="charge_no" value="{{ $row->charge_no }}" placeholder="เธฃเธฐเธเธธเน€เธฅเธเธ—เธตเนเธซเธเธฑเธเธชเธทเธญ">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">เธเธณเธเธงเธเน€เธเธดเธเธ—เธตเนเน€เธฃเธตเธขเธเน€เธเนเธ</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control rounded-pill-start px-3" name="charge" value="{{ $row->charge }}">
                                            <span class="input-group-text rounded-pill-end small bg-light">เธเธฒเธ—</span>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label small fw-bold">เธชเธ–เธฒเธเธฐเธฅเธนเธเธซเธเธตเน</label>
                                        <select class="form-select rounded-pill px-3" name="status">                                                       
                                            <option value="เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน" @if ($row->status == 'เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน') selected @endif>เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน</option>                                           
                                            <option value="เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเน€เธฃเธตเธขเธเน€เธเนเธ" @if ($row->status  == 'เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเน€เธฃเธตเธขเธเน€เธเนเธ') selected @endif>เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเน€เธฃเธตเธขเธเน€เธเนเธ</option> 
                                            <option value="เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเธเธฒเธฃเธเธญเธญเธธเธ—เธเธฃเธ“เน" @if ($row->status == 'เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเธเธฒเธฃเธเธญเธญเธธเธ—เธเธฃเธ“เน') selected @endif>เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเธเธฒเธฃเธเธญเธญเธธเธ—เธเธฃเธ“เน</option>
                                            <option value="เธเธฃเธฐเธ—เธเธขเธญเธ”เนเธฅเนเธง" @if ($row->status == 'เธเธฃเธฐเธ—เธเธขเธญเธ”เนเธฅเนเธง') selected @endif>เธเธฃเธฐเธ—เธเธขเธญเธ”เนเธฅเนเธง</option>  
                                        </select> 
                                    </div>
                                </div>

                                <!-- Right Column: เธเธฒเธฃเธเธ”เน€เธเธข -->
                                <div class="col-md-6">
                                    <h6 class="text-secondary fw-bold mb-3 d-flex align-items-center">
                                        <i class="bi bi-wallet2 me-2 text-success"></i> เธเนเธญเธกเธนเธฅเธเธฒเธฃเธเธ”เน€เธเธข (Receive)
                                    </h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">เธงเธฑเธเธ—เธตเนเธเธ”เน€เธเธข</label>
                                        <input type="date" class="form-control rounded-pill px-3 border-success-soft" name="receive_date" value="{{ $row->receive_date }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">เน€เธฅเธเธ—เธตเนเธซเธเธฑเธเธชเธทเธญเธเธ”เน€เธเธข</label>
                                        <input type="text" class="form-control rounded-pill px-3 border-success-soft" name="receive_no" value="{{ $row->receive_no }}" placeholder="เธฃเธฐเธเธธเน€เธฅเธเธ—เธตเนเนเธญเธ">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">เธเธณเธเธงเธเน€เธเธดเธเธ—เธตเนเนเธ”เนเธฃเธฑเธ</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control rounded-pill-start px-3 border-success-soft" name="receive" value="{{ $row->receive }}">
                                            <span class="input-group-text rounded-pill-end small bg-success-soft text-success border-success-soft">เธเธฒเธ—</span>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label small fw-bold">เน€เธฅเธเธ—เธตเนเนเธเน€เธชเธฃเนเธ</label>
                                        <input type="text" class="form-control rounded-pill px-3 border-success-soft" name="repno" value="{{ $row->repno }}" placeholder="เธฃเธฐเธเธธเน€เธฅเธเธ—เธตเนเนเธเน€เธชเธฃเนเธ">
                                    </div>
                                </div>
                            </div> 
                        </div>
                        <div class="modal-footer bg-light border-0 p-3">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">เธขเธเน€เธฅเธดเธ</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm" onclick="showLoading()">
                                <i class="bi bi-save me-1"></i> เธเธฑเธเธ—เธถเธเธเนเธญเธกเธนเธฅ
                            </button>
                        </div>
                    </form>     
                </div>
            </div>
        </div>
    @endforeach
        <!-- end modal -->
    </div>

<!-- เธชเธณเน€เธฃเนเธ -->
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'เธชเธณเน€เธฃเนเธ',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif
 <!-- เธเธณเธฅเธฑเธเนเธซเธฅเธ” -->
    <script>
        function showLoading() {
            Swal.fire({
                title: 'เธเธณเธฅเธฑเธเนเธซเธฅเธ”...',
                text: 'เธเธฃเธธเธ“เธฒเธฃเธญเธชเธฑเธเธเธฃเธนเน',
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
<!-- เธฅเธเธฅเธนเธเธซเธเธตเน -->
    <script>
        function confirmDelete() { 
            const selected = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('เนเธเนเธเน€เธ•เธทเธญเธ', 'เธเธฃเธธเธ“เธฒเน€เธฅเธทเธญเธเธฃเธฒเธขเธเธฒเธฃเธ—เธตเนเธเธฐเธฅเธ', 'warning');
                return;
            }
            Swal.fire({
            title: 'เธขเธทเธเธขเธฑเธ?',
            text: "เธ•เนเธญเธเธเธฒเธฃเธฅเธเธฅเธนเธเธซเธเธตเนเธฃเธฒเธขเธเธฒเธฃเธ—เธตเนเน€เธฅเธทเธญเธเนเธเนเธซเธฃเธทเธญเนเธกเน?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'เนเธเน, เธฅเธเน€เธฅเธข!',
            cancelButtonText: 'เธขเธเน€เธฅเธดเธ'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050102_107_delete') }}']").submit();
                }
            });
        }
    </script>
<!-- เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน -->
    <script>
        function confirmSubmit() {
            const selected = [...document.querySelectorAll('input[name="checkbox[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('เนเธเนเธเน€เธ•เธทเธญเธ', 'เธเธฃเธธเธ“เธฒเน€เธฅเธทเธญเธเธฃเธฒเธขเธเธฒเธฃเธ—เธตเนเธเธฐเธขเธทเธเธขเธฑเธ', 'warning');
                return;
            }
            Swal.fire({
                title: 'เธขเธทเธเธขเธฑเธ?',
                text: "เธ•เนเธญเธเธเธฒเธฃเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเนเธฃเธฒเธขเธเธฒเธฃเธ—เธตเนเน€เธฅเธทเธญเธเนเธเนเธซเธฃเธทเธญเนเธกเน?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'เธขเธทเธเธขเธฑเธ',
                cancelButtonText: 'เธขเธเน€เธฅเธดเธ'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050102_107_confirm') }}']").submit();
                }
            });
        }
    </script>
    <script>
        function confirmSubmit_iclaim() {
            const selected = [...document.querySelectorAll('input[name="checkbox_iclaim[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('เนเธเนเธเน€เธ•เธทเธญเธ', 'เธเธฃเธธเธ“เธฒเน€เธฅเธทเธญเธเธฃเธฒเธขเธเธฒเธฃเธ—เธตเนเธเธฐเธขเธทเธเธขเธฑเธ', 'warning');
                return;
            }
            Swal.fire({
                title: 'เธขเธทเธเธขเธฑเธ?',
                text: "เธ•เนเธญเธเธเธฒเธฃเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเนเธฃเธฒเธขเธเธฒเธฃเธ—เธตเนเน€เธฅเธทเธญเธเนเธเนเธซเธฃเธทเธญเนเธกเน?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'เธขเธทเธเธขเธฑเธ',
                cancelButtonText: 'เธขเธเน€เธฅเธดเธ'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050102_107_confirm_iclaim') }}']").submit();
                }
            });
        }
    </script>

@endsection

<!-- Modal -->


@push('scripts')
    <script>
        $(document).ready(function () {
            $('#debtor').DataTable({
                dom: '<"row mb-3"' +
                        '<"col-md-6"l>' + // Show เธฃเธฒเธขเธเธฒเธฃ
                    '>' +
                    'rt' +
                    '<"row mt-3"' +
                        '<"col-md-6"i>' + // Info
                        '<"col-md-6"p>' + // Pagination
                    '>',            
                language: {
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
    <script>
        $(document).ready(function () {
            $('#debtor_search').DataTable({
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
                    title: '1102050102.107-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเนเธฒเธฃเธฐเน€เธเธดเธ IP เธฃเธญเธขเธทเธเธขเธฑเธ เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}'
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
    <script>
        $(document).ready(function () {
            $('#debtor_search_iclaim').DataTable({
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
                    title: '1102050102.107-เธเธนเนเธกเธฒเธฃเธฑเธเธเธฃเธดเธเธฒเธฃเนเธเนเธเธฃเธฐเธเธฑเธเธเธตเธงเธดเธ• iClaim เธฃเธญเธขเธทเธเธขเธฑเธ เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}'
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



