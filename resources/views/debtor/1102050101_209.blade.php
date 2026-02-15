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
@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                1102050101.209-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธ”เนเธฒเธเธเธฒเธฃเธชเธฃเนเธฒเธเน€เธชเธฃเธดเธกเธชเธธเธเธ เธฒเธเนเธฅเธฐเธเนเธญเธเธเธฑเธเนเธฃเธ (P&P)
            </h4>
            <small class="text-muted">เธเนเธญเธกเธนเธฅเธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050101_209') }}" enctype="multipart/form-data" class="m-0 d-flex align-items-center gap-2">
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
                    <button class="nav-link" id="confirm-tab" data-bs-toggle="pill" data-bs-target="#confirm-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> เธฃเธญเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน
                        <span class="badge bg-warning-soft text-warning ms-2">{{ count($debtor_search) }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: เธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน -->
                <div class="tab-pane fade show active" id="debtor-pane" role="tabpanel"> 

            <form action="{{ url('debtor/1102050101_209_delete') }}" method="POST" enctype="multipart/form-data">
                @csrf   
                @method('DELETE')
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                        <i class="bi bi-trash-fill me-1"></i> เธฅเธเธฃเธฒเธขเธเธฒเธฃเธฅเธนเธเธซเธเธตเน
                    </button>
                    <div>
                        <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_209_indiv_excel')}}" target="_blank">
                             <i class="bi bi-file-earmark-excel me-1"></i> เธชเนเธเธญเธญเธเธฃเธฒเธขเธ•เธฑเธง
                        </a>                
                        <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_209_daily_pdf')}}" target="_blank">
                             <i class="bi bi-printer me-1"></i> เธเธดเธกเธเนเธฃเธฒเธขเธงเธฑเธ
                        </a> 
                    </div>
                </div>
                <table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                    <thead>
                    <tr class="table-success">
                        <th class="text-left text-primary" colspan = "10">1102050101.209-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธ”เนเธฒเธเธเธฒเธฃเธชเธฃเนเธฒเธเน€เธชเธฃเธดเธกเธชเธธเธเธ เธฒเธเนเธฅเธฐเธเนเธญเธเธเธฑเธเนเธฃเธ (P&P) เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}</th> 
                        <th class="text-center text-primary" colspan = "6">เธเธฒเธฃเธเธ”เน€เธเธข</th>                                                 
                    </tr>
                    <tr class="table-success" >
                        <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"> All</th> 
                        <th class="text-center">เธงเธฑเธเธ—เธตเน</th>
                        <th class="text-center">HN</th>
                        <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th>
                        <th class="text-center">เธชเธดเธ—เธเธด</th>
                        <th class="text-center">ICD10</th>
                        <th class="text-center">เธเนเธฒเธฃเธฑเธเธฉเธฒเธ—เธฑเนเธเธซเธกเธ”</th>  
                        <th class="text-center">เธเธณเธฃเธฐเน€เธญเธ</th>  
                        <th class="text-center">เธเธญเธเธ—เธธเธเธญเธทเนเธ</th>   
                        <th class="text-center">PPFS</th>      
                        <th class="text-center text-primary">เธฅเธนเธเธซเธเธตเน</th>
                        <th class="text-center text-primary">เธเธ”เน€เธเธข PPFS</th> 
                        <th class="text-center text-primary">เธเธฅเธ•เนเธฒเธ</th>  
                        <th class="text-center text-primary" width="5%">เธชเธ–เธฒเธเธฐ</th>
                        <th class="text-center text-primary">REP</th>                         
                        <th class="text-center text-primary">Lock</th>                                       
                    </tr>
                    </thead>
                    <?php $count = 1 ; ?>
                    <?php $sum_income = 0 ; ?>
                    <?php $sum_rcpt_money = 0 ; ?>
                    <?php $sum_other = 0 ; ?>
                    <?php $sum_ppfs = 0 ; ?>
                    <?php $sum_debtor = 0 ; ?>
                    <?php $sum_receive_pp = 0 ; ?>
                    <?php $sum_receive = 0 ; ?>
                    @foreach($debtor as $row) 
                    <tr>
                        <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->vn}}"></td>   
                        <td align="left">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                        <td align="center">{{ $row->hn }}</td>
                        <td align="left">{{ $row->ptname }}</td>
                        <td align="left">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                        <td align="right">{{ $row->pdx }}</td>                      
                        <td align="right">{{ number_format($row->income,2) }}</td>
                        <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                        <td align="right">{{ number_format($row->other,2) }}</td>
                        <td align="right">{{ number_format($row->ppfs,2) }}</td>
                        <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>  
                        <td align="right" @if($row->receive_pp > 0) style="color:green" 
                            @elseif($row->receive_pp < 0) style="color:red" @endif>
                            {{ number_format($row->receive_pp,2) }}
                        </td>
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{ number_format($row->receive-$row->debtor,2) }}
                        </td>            
                        <td align="right">{{ $row->status }}</td> 
                        <td align="right">{{ $row->repno }}</td> 
                        <td align="center" style="color:blue">{{ $row->debtor_lock }}</td>                            
                    <?php $count++; ?>
                    <?php $sum_income += $row->income ; ?>
                    <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                    <?php $sum_other += $row->other ; ?> 
                    <?php $sum_ppfs += $row->ppfs ; ?> 
                    <?php $sum_debtor += $row->debtor ; ?> 
                    <?php $sum_receive_pp += $row->receive_pp ; ?>   
                    <?php $sum_receive += $row->receive ; ?>        
                    @endforeach 
                    </tr>   
                    <tfoot>
                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="6" class="text-end">เธฃเธงเธก</td>
                            <td class="text-end">{{ number_format($sum_income,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                            <td class="text-end">{{ number_format($sum_other,2) }}</td>
                            <td class="text-end">{{ number_format($sum_ppfs,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                            <td class="text-end" style="color:green">{{ number_format($sum_receive_pp,2) }}</td>
                            <td class="text-end" style="color:red">
                                {{ number_format($sum_receive - $sum_debtor, 2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </form>
                </div>
                
                <!-- Tab 2: เธฃเธญเธขเธทเธเธขเธฑเธ -->
                <div class="tab-pane fade" id="confirm-pane" role="tabpanel"> 
 
            <form action="{{ url('debtor/1102050101_209_confirm') }}" method="POST" enctype="multipart/form-data">
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
                        <th class="text-left text-primary" colspan = "13">1102050101.209-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธ”เนเธฒเธเธเธฒเธฃเธชเธฃเนเธฒเธเน€เธชเธฃเธดเธกเธชเธธเธเธ เธฒเธเนเธฅเธฐเธเนเธญเธเธเธฑเธเนเธฃเธ (P&P) เธฃเธญเธขเธทเธเธขเธฑเธ เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }} เธฃเธญเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน</th>                         
                    </tr>
                    <tr class="table-secondary">
                        <th class="text-center"><input type="checkbox" onClick="toggle(this)"> All</th> 
                        <th class="text-center">เธงเธฑเธเธ—เธตเน</th>
                        <th class="text-center">HN</th>
                        <th class="text-center">เธเธทเนเธญ-เธชเธเธธเธฅ</th>
                        <th class="text-center">เธชเธดเธ—เธเธด</th>
                        <th class="text-center">ICD10</th>
                        <th class="text-center">เธเนเธฒเธฃเธฑเธเธฉเธฒเธ—เธฑเนเธเธซเธกเธ”</th>  
                        <th class="text-center">เธเธณเธฃเธฐเน€เธญเธ</th>    
                        <th class="text-center">เธเธญเธเธ—เธธเธเธญเธทเนเธ</th> 
                        <th class="text-center">PPFS</th>                                       
                        <th class="text-center">เธฅเธนเธเธซเธเธตเน</th>
                        <th class="text-center" width = "10%">เธฃเธฒเธขเธเธฒเธฃเธเธญเธเธ—เธธเธเธญเธทเนเธ</th> 
                        <th class="text-center" width = "10%">เธฃเธฒเธขเธเธฒเธฃ PPFS</th>                         
                    </tr>
                    </thead>
                    <?php $count = 1 ; ?>
                    <?php 
                        $sum_income = 0;
                        $sum_rcpt_money = 0;
                        $sum_other = 0;
                        $sum_ppfs = 0;
                        $sum_debtor = 0;
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
                        <td align="right">{{ number_format($row->other,2) }}</td>
                        <td align="right">{{ number_format($row->ppfs,2) }}</td>
                        <td align="right">{{ number_format($row->debtor,2) }}</td>
                        <td align="left" width = "10%">{{ $row->other_list }}</td>
                        <td align="left" width = "10%">{{ $row->ppfs_list }}</td>
                    <?php $count++; ?>
                    <?php $sum_income += $row->income; ?>
                    <?php $sum_rcpt_money += $row->rcpt_money; ?>
                    <?php $sum_other += $row->other; ?>
                    <?php $sum_ppfs += $row->ppfs; ?>
                    <?php $sum_debtor += $row->debtor; ?>
                    @endforeach 
                    </tr>   
                    <tfoot>
                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="6" class="text-end">เธฃเธงเธก</td>
                            <td class="text-end">{{ number_format($sum_income,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                            <td class="text-end">{{ number_format($sum_other,2) }}</td>
                            <td class="text-end">{{ number_format($sum_ppfs,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </form>
            </div>
        </div>
    </div>
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
                    document.querySelector("form[action='{{ url('debtor/1102050101_209_delete') }}']").submit();
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
                    document.querySelector("form[action='{{ url('debtor/1102050101_209_confirm') }}']").submit();
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
                title: '1102050101.209-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธ”เนเธฒเธเธเธฒเธฃเธชเธฃเนเธฒเธเน€เธชเธฃเธดเธกเธชเธธเธเธ เธฒเธเนเธฅเธฐเธเนเธญเธเธเธฑเธเนเธฃเธ (P&P) เธฃเธญเธขเธทเธเธขเธฑเธ เธงเธฑเธเธ—เธตเน {{ DateThai($start_date) }} เธ–เธถเธ {{ DateThai($end_date) }}'
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
