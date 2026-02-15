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
        function toggle_ae(source) {
            checkboxes = document.getElementsByName('checkbox_ae[]');
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
                1102050101.309-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก-เธเนเธฒเนเธเนเธเนเธฒเธขเธชเธนเธ/เธญเธธเธเธฑเธ•เธดเน€เธซเธ•เธธ/เธเธธเธเน€เธเธดเธ OP
            </h4>
            <small class="text-muted">เธเนเธญเธกเธนเธฅเธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}</small>
        </div>

        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050101_309') }}" enctype="multipart/form-data" class="m-0 d-flex align-items-center gap-2">
                    @csrf
                    
                    <!-- Date Range -->
                    <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">เธงเธฑเธเธ—เธตเน</span>
                        <input type="date" name="start_date" class="form-control border-start-0 rounded-0" value="{{ $start_date }}" style="width: 170px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">เธ–เธถเธ</span>
                        <input type="date" name="end_date" class="form-control border-start-0 rounded-end" value="{{ $end_date }}" style="width: 170px;">
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input id="search" type="text" class="form-control border-start-0" name="search" value="{{ $search }}" placeholder="เธเนเธเธซเธฒ เธเธทเนเธญ-เธชเธเธธเธฅ, HN">
                    </div>

                    <button onclick="showLoading()" type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
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
    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        
        <!-- Section: Tabs -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="debtor-tab" data-bs-toggle="pill" data-bs-target="#debtor-pane" type="button" role="tab">
                        <i class="bi bi-person-lines-fill me-1 text-success"></i> <span class="text-success fw-bold">เธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน</span>
                        <span class="badge bg-primary-soft text-primary ms-2">{{ count($debtor) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="kidney-tab" data-bs-toggle="pill" data-bs-target="#kidney-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> เธฃเธญเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน เธเธญเธเนเธ•
                        <span class="badge bg-warning-soft text-warning ms-2">{{ count($debtor_search) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ae-tab" data-bs-toggle="pill" data-bs-target="#ae-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> เธฃเธญเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน เธญเธธเธเธฑเธ•เธดเน€เธซเธ•เธธ/เธเธธเธเน€เธเธดเธ OP
                        <span class="badge bg-warning-soft text-warning ms-2">{{ count($debtor_search_ae) }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: เธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน -->
                <div class="tab-pane fade show active" id="debtor-pane" role="tabpanel">
                    <form action="{{ url('debtor/1102050101_309_delete') }}" method="POST" enctype="multipart/form-data">
                        @csrf   
                        @method('DELETE')
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                                <i class="bi bi-trash-fill me-1"></i> เธฅเธเธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน
                            </button>
                            <div>
                                <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_309_indiv_excel')}}" target="_blank">
                                     <i class="bi bi-file-earmark-excel me-1"></i> เธชเนเธเธญเธญเธเธฃเธฒเธขเธ•เธฑเธง
                                </a>                
                                <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_309_daily_pdf')}}" target="_blank">
                                     <i class="bi bi-printer me-1"></i> เธเธดเธกเธเนเธฃเธฒเธขเธงเธฑเธ
                                </a> 
                            </div>
                        </div>
                        <table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                                <tr class="table-success">
                                    <th class="text-center"><input type="checkbox" name="checkbox_d[]" onClick="toggle_d(this)"> All</th> 
                                    <th class="text-center">เธงเธฑเธเธ—เธตเน</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th>
                                    <th class="text-center">เธชเธดเธ—เธเธด</th>
                                    <th class="text-center">ICD10</th>
                                    <th class="text-center">เธเนเธฒเธฃเธฑเธเธฉเธฒเธ—เธฑเนเธเธซเธกเธ”</th>  
                                    <th class="text-center">เธเธณเธฃเธฐเน€เธญเธ</th>  
                                    <th class="text-center">เธเธญเธเนเธ•</th>        
                                    <th class="text-center text-primary">เธฅเธนเธเธซเธเธตเน</th>
                                    <th class="text-center text-primary">เธเธ”เน€เธเธข</th>                       
                                    <th class="text-center text-primary">เธเธฅเธ•เนเธฒเธ</th>
                                    <th class="text-center text-primary">REP</th>                           
                                    <th class="text-center text-primary">เธญเธฒเธขเธธเธซเธเธตเน</th>
                                    <th class="text-center text-primary" width="6%">Action</th> 
                                    <th class="text-center text-primary">Lock</th>                                       
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $count = 1;
                                    $sum_income = 0;
                                    $sum_rcpt_money = 0;
                                    $sum_kidney = 0;
                                    $sum_debtor = 0;
                                    $sum_receive = 0;
                                ?>
                                @foreach($debtor as $row) 
                                <tr>
                                    <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->vn}}"></td>   
                                    <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                    <td align="center">{{ $row->hn }}</td>
                                    <td align="left">{{ $row->ptname }}</td>
                                    <td align="left">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                                    <td align="right">{{ $row->pdx }}</td>                      
                                    <td align="right">{{ number_format($row->income,2) }}</td>
                                    <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td align="right">{{ number_format($row->kidney,2) }}</td>
                                    <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td> 
                                    <td align="right" @if($row->receive > 0) style="color:green" 
                                        @elseif($row->receive < 0) style="color:red" @endif>
                                        {{ number_format($row->receive,2) }}
                                    </td>                                                
                                    <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                                        @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                                        {{ number_format($row->receive-$row->debtor,2) }}
                                    </td>                       
                                    <td align="right">{{ $row->repno }} {{ $row->rid }}</td>                         
                                    <td align="right" @if($row->days < 90) style="background-color: #90EE90;"
                                        @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;"
                                        @else style="background-color: #FF7F7F;" @endif >
                                        {{ $row->days }} เธงเธฑเธ
                                    </td>  
                                    <td align="center">         
                                        <button type="button" class="btn btn-outline-warning btn-sm text-primary receive" data-bs-toggle="modal" data-bs-target="#receive-{{ str_replace(['/','.'], '-', $row->vn) }}"> 
                                            <i class="bi bi-cash-stack"></i> เธเธ”เน€เธเธข
                                        </button>                            
                                    </td> 
                                    <td align="center" style="color:blue">{{ $row->debtor_lock }}</td>                            
                                <?php 
                                    $count++;
                                    $sum_income += $row->income;
                                    $sum_rcpt_money += $row->rcpt_money;
                                    $sum_kidney += $row->kidney;
                                    $sum_debtor += $row->debtor;
                                    $sum_receive += $row->receive;
                                ?>     
                                @endforeach 
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="6" class="text-end">เธฃเธงเธก</td>
                                    <td class="text-end">{{ number_format($sum_income,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_kidney,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                                    <td class="text-end" style="color:green">{{ number_format($sum_receive,2) }}</td>
                                    <td class="text-end" style="color:red">
                                        {{ number_format($sum_receive - $sum_debtor, 2) }}
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>

                <div class="tab-pane fade" id="kidney-pane" role="tabpanel">
                    <form action="{{ url('debtor/1102050101_309_confirm') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="confirmSubmit()">
                                <i class="bi bi-check-circle me-1"></i> เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน
                            </button>
                            <div></div>
                        </div>                
                        <table id="debtor_search" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                                <tr class="table-secondary">
                                    <th class="text-center"><input type="checkbox" onClick="toggle(this)"> All</th> 
                                    <th class="text-center">เธงเธฑเธเธ—เธตเน</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th>
                                    <th class="text-center">เธชเธดเธ—เธเธด</th>
                                    <th class="text-center">ICD10</th>
                                    <th class="text-center">เธเนเธฒเธฃเธฑเธเธฉเธฒเธ—เธฑเนเธเธซเธกเธ”</th>  
                                    <th class="text-center">เธเธณเธฃเธฐเน€เธญเธ</th>    
                                    <th class="text-center">เธเธญเธเนเธ•</th>                                    
                                    <th class="text-center">เธฅเธนเธเธซเธเธตเน</th>
                                    <th class="text-center" width = "10%">เธฃเธฒเธขเธเธฒเธฃเธเธญเธเนเธ•</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $count = 1; 
                                    $sum_income_search = 0;
                                    $sum_rcpt_money_search = 0;
                                    $sum_kidney_search = 0;
                                    $sum_debtor_search = 0;
                                ?>
                                @foreach($debtor_search as $row)
                                <tr>
                                    <td class="text-center"><input type="checkbox" name="checkbox[]" value="{{$row->vn}}"></td> 
                                    <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                    <td align="center">{{ $row->hn }}</td>
                                    <td align="left">{{ $row->ptname }}</td>
                                    <td align="left">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                                    <td align="right">{{ $row->pdx }}</td>                  
                                    <td align="right">{{ number_format($row->income,2) }}</td>
                                    <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td align="right">{{ number_format($row->kidney,2) }}</td>
                                    <td align="right">{{ number_format($row->debtor,2) }}</td>
                                    <td align="left" width = "10%">{{ $row->kidney_list }}</td>
                                <?php 
                                    $count++; 
                                    $sum_income_search += $row->income;
                                    $sum_rcpt_money_search += $row->rcpt_money;
                                    $sum_kidney_search += $row->kidney;
                                    $sum_debtor_search += $row->debtor;
                                ?>
                                @endforeach 
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="6" class="text-end">เธฃเธงเธก</td>
                                    <td class="text-end">{{ number_format($sum_income_search,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money_search,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_kidney_search,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor_search,2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>

                <!-- Tab 3: เธฃเธญเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน เธญเธธเธเธฑเธ•เธดเน€เธซเธ•เธธ/เธเธธเธเน€เธเธดเธ OP -->
                <div class="tab-pane fade" id="ae-pane" role="tabpanel">
                    <form action="{{ url('debtor/1102050101_309_confirm_ae') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="confirmSubmit_ae()">
                                <i class="bi bi-check-circle me-1"></i> เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน
                            </button>
                            <div></div>
                        </div>                
                        <table id="debtor_search_ae" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                                <tr class="table-secondary">
                                    <th class="text-center"><input type="checkbox" onClick="toggle_ae(this)"> All</th>  
                                    <th class="text-center">เธงเธฑเธเธ—เธตเน</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th>
                                    <th class="text-center">เธชเธดเธ—เธเธด</th>
                                    <th class="text-center">ICD10</th>
                                    <th class="text-center">เธเนเธฒเธฃเธฑเธเธฉเธฒเธ—เธฑเนเธเธซเธกเธ”</th>  
                                    <th class="text-center">เธเธณเธฃเธฐเน€เธญเธ</th>   
                                    <th class="text-center">เธเธญเธเธ—เธธเธเธญเธทเนเธ</th>                    
                                    <th class="text-center">เธฅเธนเธเธซเธเธตเน</th>
                                    <th class="text-center">เธฃเธฒเธขเธเธฒเธฃเธเธญเธเธ—เธธเธเธญเธทเนเธ</th>   
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $count = 1; 
                                    $sum_income_ae = 0;
                                    $sum_rcpt_money_ae = 0;
                                    $sum_other_ae = 0;
                                    $sum_debtor_ae = 0;
                                ?>
                                @foreach($debtor_search_ae as $row)
                                <tr>
                                    <td class="text-center"><input type="checkbox" name="checkbox_ae[]" value="{{$row->vn}}"></td>                   
                                    <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                    <td align="center">{{ $row->hn }}</td>
                                    <td align="left">{{ $row->ptname }}</td>
                                    <td align="left">{{ $row->pttype }} </td>
                                    <td align="right">{{ $row->pdx }}</td>                  
                                    <td align="right">{{ number_format($row->income,2) }}</td>
                                    <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td align="right">{{ number_format($row->other,2) }}</td>
                                    <td align="right">{{ number_format($row->debtor,2) }}</td>
                                    <td align="left">{{ $row->other_list }} </td>
                                <?php 
                                    $count++; 
                                    $sum_income_ae += $row->income;
                                    $sum_rcpt_money_ae += $row->rcpt_money;
                                    $sum_other_ae += $row->other;
                                    $sum_debtor_ae += $row->debtor;
                                ?>
                                @endforeach 
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="6" class="text-end">เธฃเธงเธก</td>
                                    <td class="text-end">{{ number_format($sum_income_ae,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money_ae,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_other_ae,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor_ae,2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>
            </div> <!-- End Tab Content -->
        </div> <!-- End Card Body -->
    </div> <!-- End Card -->


        <!-- Modal เธเธฑเธเธ—เธถเธเธเธ”เน€เธเธข -->
        @foreach($debtor as $row)
            <div id="receive-{{ str_replace(['/','.'], '-', $row->vn) }}" class="modal fade" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-primary text-white border-0 py-3">
                            <h5 class="modal-title d-flex align-items-center">
                                <i class="bi bi-cash-stack me-2"></i>
                                เธฃเธฒเธขเธเธฒเธฃเธเธฒเธฃเธเธ”เน€เธเธขเน€เธเธดเธ/เธฅเธนเธเธซเธเธตเน (VN: {{ $row->vn }})
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>         
                        <form action="{{ url('debtor/1102050101_309/update', $row->vn) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body p-4">
                                <input type="hidden" id="vn" name="vn">
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
                                            <input type="date" class="form-control rounded-pill px-3" name="charge_date" value="{{ $row->charge_date ?? '' }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">เน€เธฅเธเธ—เธตเนเธซเธเธฑเธเธชเธทเธญเน€เธฃเธตเธขเธเน€เธเนเธ</label>
                                            <input type="text" class="form-control rounded-pill px-3" name="charge_no" value="{{ $row->charge_no ?? '' }}" placeholder="เธฃเธฐเธเธธเน€เธฅเธเธ—เธตเนเธซเธเธฑเธเธชเธทเธญ">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">เธเธณเธเธงเธเน€เธเธดเธเธ—เธตเนเน€เธฃเธตเธขเธเน€เธเนเธ</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control rounded-pill-start px-3" name="charge" value="{{ $row->charge ?? '' }}">
                                                <span class="input-group-text rounded-pill-end small bg-light">เธเธฒเธ—</span>
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label small fw-bold">เธชเธ–เธฒเธเธฐเธฅเธนเธเธซเธเธตเน</label>
                                            <select class="form-select rounded-pill px-3" name="status">                                                       
                                                <option value="เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน" @if (($row->status ?? '') == 'เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน') selected @endif>เธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน</option>                                           
                                                <option value="เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเน€เธฃเธตเธขเธเน€เธเนเธ" @if (($row->status ?? '')  == 'เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเน€เธฃเธตเธขเธเน€เธเนเธ') selected @endif>เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเน€เธฃเธตเธขเธเน€เธเนเธ</option> 
                                                <option value="เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเธเธฒเธฃเธเธญเธญเธธเธ—เธเธฃเธ“เน" @if (($row->status ?? '') == 'เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเธเธฒเธฃเธเธญเธญเธธเธ—เธเธฃเธ“เน') selected @endif>เธญเธขเธนเนเธฃเธฐเธซเธงเนเธฒเธเธเธฒเธฃเธเธญเธญเธธเธ—เธเธฃเธ“เน</option>
                                                <option value="เธเธฃเธฐเธ—เธเธขเธญเธ”เนเธฅเนเธง" @if (($row->status ?? '') == 'เธเธฃเธฐเธ—เธเธขเธญเธ”เนเธฅเนเธง') selected @endif>เธเธฃเธฐเธ—เธเธขเธญเธ”เนเธฅเนเธง</option>  
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
                                            <input type="date" class="form-control rounded-pill px-3 border-success-soft" name="receive_date" value="{{ $row->receive_date ?? '' }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">เน€เธฅเธเธ—เธตเนเธซเธเธฑเธเธชเธทเธญเธเธ”เน€เธเธข</label>
                                            <input type="text" class="form-control rounded-pill px-3 border-success-soft" name="receive_no" value="{{ $row->receive_no ?? '' }}" placeholder="เธฃเธฐเธเธธเน€เธฅเธเธ—เธตเนเนเธญเธ">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">เธเธณเธเธงเธเน€เธเธดเธเธ—เธตเนเนเธ”เนเธฃเธฑเธ</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control rounded-pill-start px-3 border-success-soft" name="receive" value="{{ $row->receive ?? '' }}">
                                                <span class="input-group-text rounded-pill-end small bg-success-soft text-success border-success-soft">เธเธฒเธ—</span>
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label small fw-bold">เน€เธฅเธเธ—เธตเนเนเธเน€เธชเธฃเนเธ</label>
                                            <input type="text" class="form-control rounded-pill px-3 border-success-soft" name="repno" value="{{ $row->repno ?? ($row->repno_pp ?? '') }}" placeholder="เธฃเธฐเธเธธเน€เธฅเธเธ—เธตเนเนเธเน€เธชเธฃเนเธ">
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
                    document.querySelector("form[action='{{ url('debtor/1102050101_309_delete') }}']").submit();
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
                    document.querySelector("form[action='{{ url('debtor/1102050101_309_confirm') }}']").submit();
                }
            });
        }
    </script>
    <script>
        function confirmSubmit_ae() {
            const selected = [...document.querySelectorAll('input[name="checkbox_ae[]"]:checked')].map(e => e.value);    
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
                    document.querySelector("form[action='{{ url('debtor/1102050101_309_confirm_ae') }}']").submit();
                }
            });
        }
    </script>

@endsection

<!-- Modal -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

@push('scripts')
    <script>
        $(document).ready(function () {
            // Tab 1: เธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน (No DataTables Buttons, Manual Buttons in HTML)
            $('#debtor').DataTable({
                dom: '<"row mb-3"' +
                        '<"col-md-6"l>' + 
                        '<"col-md-6"f>' + 
                    '>' +
                    'rt' +
                    '<"row mt-3"' +
                        '<"col-md-6"i>' + 
                        '<"col-md-6"p>' + 
                    '>',            
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

            // Tab 2: เธฃเธญเธ•เธฃเธงเธเธชเธญเธ เธเธญเธเนเธ• (With DataTables Excel Button)
            $('#debtor_search').DataTable({
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
                        title: '1102050101.309-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก-เธเนเธฒเนเธเนเธเนเธฒเธขเธชเธนเธ เธฃเธญเธขเธทเธเธขเธฑเธ เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}'
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

            // Tab 3: เธฃเธญเธ•เธฃเธงเธเธชเธญเธ AE (With DataTables Excel Button)
            $('#debtor_search_ae').DataTable({
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
                        title: '1102050101.309-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก-เธญเธธเธเธฑเธ•เธดเน€เธซเธ•เธธ/เธเธธเธเน€เธเธดเธ OP เธฃเธญเธขเธทเธเธขเธฑเธ เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}'
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



