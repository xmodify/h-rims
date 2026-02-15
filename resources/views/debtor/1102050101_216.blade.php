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
        function toggle_kidney(source) {
            checkboxes = document.getElementsByName('checkbox_kidney[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
    <script>
        function toggle_cr(source) {
            checkboxes = document.getElementsByName('checkbox_cr[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
    <script>
        function toggle_anywhere(source) {
            checkboxes = document.getElementsByName('checkbox_anywhere[]');
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
                1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR)
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050101_216') }}" enctype="multipart/form-data" class="m-0 d-flex align-items-center gap-2">
                    @csrf
                    
                    <!-- Date Range -->
                    <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">วันที่</span>
                        <input type="date" name="start_date" class="form-control border-start-0 rounded-0" value="{{ $start_date }}" style="width: 170px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="date" name="end_date" class="form-control border-start-0 rounded-end" value="{{ $end_date }}" style="width: 170px;">
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="width: 220px;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input id="search" type="text" class="form-control border-start-0" name="search" value="{{ $search }}" placeholder="ค้นหา ชื่อ-สกุล, HN, AN">
                    </div>

                    <button onclick="showLoading()" type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                    <a href="{{ url('debtor/forget_search') }}" class="btn btn-warning btn-sm px-3 shadow-sm text-dark">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> รีเซ็ต
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
                        <i class="bi bi-person-lines-fill me-1 text-success"></i> <span class="text-success fw-bold">รายการลูกหนี้</span>
                        <span class="badge bg-primary-soft text-primary ms-2">{{ count($debtor) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="kidney-tab" data-bs-toggle="pill" data-bs-target="#kidney-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ ฟอกไต
                        <span class="badge bg-warning-soft text-warning ms-2">{{ count($debtor_search_kidney) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cr-tab" data-bs-toggle="pill" data-bs-target="#cr-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ บริการเฉพาะ
                        <span class="badge bg-warning-soft text-warning ms-2">{{ count($debtor_search_cr) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="anywhere-tab" data-bs-toggle="pill" data-bs-target="#anywhere-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ Anywhere
                        <span class="badge bg-warning-soft text-warning ms-2">{{ count($debtor_search_anywhere) }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: รายการลูกหนี้ -->
                <div class="tab-pane fade show active" id="debtor-pane" role="tabpanel"> 

            <form action="{{ url('debtor/1102050101_216_delete') }}" method="POST" enctype="multipart/form-data">
                @csrf   
                @method('DELETE')
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                        <i class="bi bi-trash-fill me-1"></i> ลบรายการลูกหนี้
                    </button>
                    <div>
                        <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_216_indiv_excel')}}" target="_blank">
                             <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                        </a>                
                        <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_216_daily_pdf')}}" target="_blank">
                             <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                        </a> 
                    </div>
                </div>
                <table id="debtor" class="table table-bordered table-striped my-3" width = "100%">
                    <thead>
                    <tr class="table-success">
                        <th class="text-left text-primary" colspan = "12">1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR) วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                        <th class="text-center text-primary" colspan = "6">การชดเชย</th>                                                 
                    </tr>
                    <tr class="table-success" >
                        <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"> All</th> 
                        <th class="text-center">วันที่</th>
                        <th class="text-center">HN</th>
                        <th class="text-center">ชื่อ-สกุล</th>
                        <th class="text-center">สิทธิ</th>
                        <th class="text-center">ICD10</th>
                        <th class="text-center">ค่ารักษาทั้งหมด</th>  
                        <th class="text-center">ชำระเอง</th>  
                        <th class="text-center">ฟอกไต</th>   
                        <th class="text-center">บริการเฉพาะ</th>
                        <th class="text-center">OP Anywhere</th> 
                        <th class="text-center">PPFS</th>       
                        <th class="text-center text-primary">ลูกหนี้</th>
                        <th class="text-center text-primary">ชดเชย</th> 
                        <th class="text-center text-primary">ผลต่าง</th>
                        <th class="text-center text-primary">REP</th> 
                        <th class="text-center text-primary">อายุหนี้</th>                         
                        <th class="text-center text-primary">Lock</th>                                       
                    </tr>
                    </thead>
                    <?php $count = 1 ; ?>
                    <?php $sum_income = 0 ; ?>
                    <?php $sum_rcpt_money = 0 ; ?>
                    <?php $sum_kidney = 0 ; ?>
                    <?php $sum_cr = 0 ; ?>
                    <?php $sum_anywhere = 0 ; ?>
                    <?php $sum_ppfs = 0 ; ?>
                    <?php $sum_debtor = 0 ; ?>
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
                        <td align="right">{{ number_format($row->kidney,2) }}</td>
                        <td align="right">{{ number_format($row->cr,2) }}</td>
                        <td align="right">{{ number_format($row->anywhere,2) }}</td>
                        <td align="right">{{ number_format($row->ppfs,2) }}</td>
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
                        <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                            @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                            @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                            {{ $row->days }} วัน
                        </td>  
                        <td align="center" style="color:blue">{{ $row->debtor_lock }}</td>                            
                    <?php $count++; ?>
                    <?php $sum_income += $row->income ; ?>
                    <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                    <?php $sum_kidney += $row->kidney ; ?> 
                    <?php $sum_cr += $row->cr ; ?> 
                    <?php $sum_anywhere += $row->anywhere ; ?> 
                    <?php $sum_ppfs += $row->ppfs ; ?> 
                    <?php $sum_debtor += $row->debtor ; ?> 
                    <?php $sum_receive += $row->receive ; ?>       
                    @endforeach 
                    </tr>   
                    
                    <tfoot>

                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="6" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                            <td class="text-end">{{ number_format($sum_kidney,2) }}</td>
                            <td class="text-end">{{ number_format($sum_cr,2) }}</td>
                            <td class="text-end">{{ number_format($sum_anywhere,2) }}</td>
                            <td class="text-end">{{ number_format($sum_ppfs,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                            <td class="text-end" style="color:green">{{ number_format($sum_receive,2) }}</td>
                            <td class="text-end" style="color:red">
                                {{ number_format($sum_receive - $sum_debtor, 2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </form>
            </div>
            
            <!-- Tab 2: Confirm Kidney -->
            <div class="tab-pane fade" id="kidney-pane" role="tabpanel">
                <form action="{{ url('debtor/1102050101_216_confirm_kidney') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center mb-2">
                         <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_kidney()">ยืนยันลูกหนี้</button>
                         <div></div>
                    </div>
                    <table id="debtor_search_kidney" class="table table-bordered table-striped my-3" width="100%">
                        <thead>
                        <tr class="table-secondary">
                            <th class="text-left text-primary" colspan = "12">ผู้มารับบริการ UC-OP บริการเฉพาะ (CR) ฟอกไต วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>
                        </tr>
                        <tr class="table-secondary">
                            <th class="text-center"><input type="checkbox" onClick="toggle_kidney(this)"> All</th>  
                            <th class="text-center" width="6%">วันที่</th>
                            <th class="text-center">HN</th>
                            <th class="text-center" width="10%">ชื่อ-สกุล</th>
                            <th class="text-center" width="10%">สิทธิ</th>
                            <th class="text-center">ICD10</th>
                            <th class="text-center">ค่ารักษาทั้งหมด</th>  
                            <th class="text-center">ชำระเอง</th>                        
                            <th class="text-center">เรียกเก็บ</th>
                            <th class="text-center" width="25%">รายการเรียกเก็บ</th>
                        </tr>
                        </thead>
                        <?php $count = 1 ; ?>
                        <?php 
                            $sum_income_kidney = 0;
                            $sum_rcpt_money_kidney = 0;
                            $sum_debtor_kidney = 0;
                        ?>
                        @foreach($debtor_search_kidney as $row)
                        <tr>
                            <td class="text-center"><input type="checkbox" name="checkbox_kidney[]" value="{{$row->vn}}"></td>                   
                            <td align="center">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>                                
                            <td align="center">{{ $row->hn }}</td>
                            <td align="left" width="10%">{{ $row->ptname }}</td>
                            <td align="right" width="10%">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                            <td align="right">{{ $row->pdx }}</td>                  
                            <td align="right">{{ number_format($row->income,2) }}</td>
                            <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                            <td align="right">{{ number_format($row->debtor,2) }}</td>
                            <td align="left">{{ $row->claim_list }}</td> 
                        <?php $count++; ?>
                        <?php $sum_income_kidney += $row->income; ?>
                        <?php $sum_rcpt_money_kidney += $row->rcpt_money; ?>
                        <?php $sum_debtor_kidney += $row->debtor; ?>
                        @endforeach 
                    </tr>
                    <tfoot>
                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="6" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income_kidney,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money_kidney,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor_kidney,2) }}</td>
                            <td colspan="1"></td>
                        </tr>
                    </tfoot>
                    </table>
                </form>
            </div>
        
            <!-- Tab 3: Confirm CR -->
            <div class="tab-pane fade" id="cr-pane" role="tabpanel">
                <form action="{{ url('debtor/1102050101_216_confirm_cr') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                     <div class="d-flex justify-content-between align-items-center mb-2">
                        <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_cr()">ยืนยันลูกหนี้</button>
                        <div></div>
                    </div>
                    <table id="debtor_search_cr" class="table table-bordered table-striped my-3" width="100%">
                        <thead>
                        <tr class="table-secondary">
                            <th class="text-left text-primary" colspan = "12">ผู้มารับบริการ UC-OP บริการเฉพาะ (CR) วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                                                         
                        </tr>
                        <tr class="table-secondary">
                            <th class="text-center"><input type="checkbox" onClick="toggle_cr(this)"> All</th>  
                            <th class="text-center" width="6%">วันที่</th>
                            <th class="text-center">HN</th>
                            <th class="text-center" width="10%">ชื่อ-สกุล</th>
                            <th class="text-center" width="10%">สิทธิ</th>
                            <th class="text-center">ICD10</th>
                            <th class="text-center">ค่ารักษาทั้งหมด</th>  
                            <th class="text-center">ชำระเอง</th>                        
                            <th class="text-center">เรียกเก็บ</th>
                            <th class="text-center" width="25%">รายการเรียกเก็บ</th>
                            <th class="text-center">ส่ง Claim</th>
                        </tr>
                        </thead>
                        <?php $count = 1 ; ?>
                        <?php 
                            $sum_income_cr = 0;
                            $sum_rcpt_money_cr = 0;
                            $sum_debtor_cr = 0;
                        ?>
                        @foreach($debtor_search_cr as $row)
                        <tr>
                            <td class="text-center"><input type="checkbox" name="checkbox_cr[]" value="{{$row->vn}}"></td>                   
                            <td align="center">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>                                
                            <td align="center">{{ $row->hn }}</td>
                            <td align="left" width="10%">{{ $row->ptname }}</td>
                            <td align="right" width="10%">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                            <td align="right">{{ $row->pdx }}</td>                  
                            <td align="right">{{ number_format($row->income,2) }}</td>
                            <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                            <td align="right">{{ number_format($row->debtor,2) }}</td>
                            <td align="left">{{ $row->claim_list }}</td>
                            <td align="center" style="color:green">{{ $row->send_claim }}</td> 
                        <?php $count++; ?>
                        <?php $sum_income_cr += $row->income; ?>
                        <?php $sum_rcpt_money_cr += $row->rcpt_money; ?>
                        <?php $sum_debtor_cr += $row->debtor; ?>
                        @endforeach 
                        </tr> 
                    <tfoot>
                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="6" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income_cr,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money_cr,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor_cr,2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    </table>
                </form>
            </div>

            <!-- Tab 4: Confirm Anywhere -->
            <div class="tab-pane fade" id="anywhere-pane" role="tabpanel">
                <form action="{{ url('debtor/1102050101_216_confirm_anywhere') }}" method="POST" enctype="multipart/form-data">
                    @csrf  
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_anywhere()">ยืนยันลูกหนี้</button>
                        <div></div>
                    </div>
                    <table id="debtor_search_anywhere" class="table table-bordered table-striped my-3" width="100%">
                        <thead>
                        <tr class="table-secondary">
                            <th class="text-left text-primary" colspan = "14">ผู้มารับบริการ UC-OP บริการเฉพาะ (CR) OP Anywhere วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                                                          
                        </tr>
                        <tr class="table-secondary">
                            <th class="text-center"><input type="checkbox" onClick="toggle_anywhere(this)"> All</th>  
                            <th class="text-center" width="6%">วันที่</th>
                            <th class="text-center">HN</th>
                            <th class="text-center" width="10%">ชื่อ-สกุล</th>
                            <th class="text-center" width="10%">สิทธิ</th>
                            <th class="text-center">ICD10</th>
                            <th class="text-center">ค่ารักษาทั้งหมด</th>  
                            <th class="text-center">ชำระเอง</th>
                            <th class="text-center">กองทุนอื่น</th>   
                            <th class="text-center">PPFS</th>                         
                            <th class="text-center">ลูกหนี้</th>
                            <th class="text-center" width = "10%">รายการกองทุนอื่น</th> 
                            <th class="text-center" width = "10%">รายการ PPFS</th>
                            <th class="text-center">ส่ง Claim</th>
                        </tr>
                        </thead>
                        <?php $count = 1 ; ?>
                        <?php 
                            $sum_income_anywhere = 0;
                            $sum_rcpt_money_anywhere = 0;
                            $sum_other_anywhere = 0;
                            $sum_ppfs_anywhere = 0;
                            $sum_debtor_anywhere = 0;
                        ?>
                        @foreach($debtor_search_anywhere as $row)
                        <tr>
                            <td class="text-center"><input type="checkbox" name="checkbox_anywhere[]" value="{{$row->vn}}"></td>                   
                            <td align="center">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>                                
                            <td align="center">{{ $row->hn }}</td>
                            <td align="left" width="10%">{{ $row->ptname }}</td>
                            <td align="right" width="10%">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                            <td align="right">{{ $row->pdx }}</td>                  
                            <td align="right">{{ number_format($row->income,2) }}</td>
                            <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                            <td align="right">{{ number_format($row->other,2) }}</td>
                            <td align="right">{{ number_format($row->ppfs,2) }}</td>
                            <td align="right">{{ number_format($row->debtor,2) }}</td>
                            <td align="left" width="10%">{{ $row->other_list }}</td>
                            <td align="left" width="10%">{{ $row->ppfs_list }}</td>
                            <td align="center" style="color:green">{{ $row->send_claim }}</td> 
                        <?php $count++; ?>
                        <?php $sum_income_anywhere += $row->income; ?>
                        <?php $sum_rcpt_money_anywhere += $row->rcpt_money; ?>
                        <?php $sum_other_anywhere += $row->other; ?>
                        <?php $sum_ppfs_anywhere += $row->ppfs; ?>
                        <?php $sum_debtor_anywhere += $row->debtor; ?>
                        @endforeach 
                    </tr> 
                    <tfoot>
                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="6" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income_anywhere,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money_anywhere,2) }}</td>
                            <td class="text-end">{{ number_format($sum_other_anywhere,2) }}</td>
                            <td class="text-end">{{ number_format($sum_ppfs_anywhere,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor_anywhere,2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    </table>
                </form>
            </div>

        </div> <!-- End Tab Content -->
    </div> <!-- End Card Body -->
</div> <!-- End Card -->
 
<!-- สำเร็จ -->
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif
 <!-- กำลังโหลด -->
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
<!-- ลบลูกหนี้ -->
    <script>
        function confirmDelete() { 
            const selected = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะลบ', 'warning');
                return;
            }
            Swal.fire({
            title: 'ยืนยัน?',
            text: "ต้องการลบลูกหนี้รายการที่เลือกใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050101_216_delete') }}']").submit();
                }
            });
        }
    </script>
<!-- ยืนยันลูกหนี้ -->
    <script>
        function confirmSubmit_kidney() {
            const selected = [...document.querySelectorAll('input[name="checkbox_kidney[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning');
                return;
            }
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050101_216_confirm_kidney') }}']").submit();
                }
            });
        }
    </script>
    <script>
        function confirmSubmit_cr() {
            const selected = [...document.querySelectorAll('input[name="checkbox_cr[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning');
                return;
            }
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050101_216_confirm_cr') }}']").submit();
                }
            });
        }
    </script>
    <script>
        function confirmSubmit_anywhere() {
            const selected = [...document.querySelectorAll('input[name="checkbox_anywhere[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning');
                return;
            }
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050101_216_confirm_anywhere') }}']").submit();
                }
            });
        }
    </script>

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#debtor').DataTable({
                dom: '<"row mb-3"' +
                        '<"col-md-6"l>' + // Show รายการ
                    '>' +
                    'rt' +
                    '<"row mt-3"' +
                        '<"col-md-6"i>' + // Info
                        '<"col-md-6"p>' + // Pagination
                    '>',            
                language: {
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
    <script>
        $(document).ready(function () {
            $('#debtor_search_kidney').DataTable({
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
                    title: '1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR) ฟอกไต รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
    <script>
        $(document).ready(function () {
            $('#debtor_search_cr').DataTable({
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
                    title: '1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR) รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
    <script>
        $(document).ready(function () {
            $('#debtor_search_anywhere').DataTable({
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
                    title: '1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR) OP Anywhere รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

