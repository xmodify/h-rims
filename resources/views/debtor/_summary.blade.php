@extends('layouts.app')

@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i>
                สรุปลูกหนี้ค่ารักษาพยาบาล
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกช่วงวันที่</span>
                    <div class="input-group input-group-sm">
                        <input type="date" name="start_date" class="form-control" value="{{ $start_date }}" style="width: 130px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>
                        <input type="date" name="end_date" class="form-control" value="{{ $end_date }}" style="width: 130px;">
                        <button type="submit" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-search me-1"></i> ค้นหา
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
                ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}
            </h6>
        </div>
        <div class="card-body px-4 pb-4 pt-3">
            <div class="table-responsive">
                <table id="debtor" class="table table-hover table-modern align-middle mb-0">
                    <thead>  
                    <tr class="table-secondary">
                        <th class="text-center">ลำดับ</th>
                        <th class="text-center">รหัสผังบัญชี</th>
                        <th class="text-center">ชื่อผังบัญชี</th>
                        <th class="text-center">จำนวน</th>
                        <th class="text-center">ลูกหนี้</th> 
                        <th class="text-center">ชดเชย</th>
                        <th class="text-center">ผลต่าง</th>
                        <th class="text-center">
                            <a class="btn btn-danger btn-sm shadow-sm" href="{{ url('debtor/summary_pdf')}}" target="_blank">
                                <i class="bi bi-file-pdf me-1"></i> พิมพ์สรุป
                            </a>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th class="text-success bg-light" colspan = "8">
                            <i class="bi bi-person-fill me-2"></i>ผู้ป่วยนอก
                        </th>        
                    </tr>
                    <tr>            
                        <?php $sum_1102050101_103_debtor = 0 ; ?>
                        <?php $sum_1102050101_103_receive = 0 ; ?>
                        @foreach($_1102050101_103 as $row)
                        <td align="center">1</td>
                        <td align="right">1102050101.103</td>
                        <td class="text-left">ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_103_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>  
                        <?php $sum_1102050101_103_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_103_receive += $row->receive ; ?>
                        @endforeach   
                    </tr>    
                    <tr>
                        <?php $sum_1102050101_109_debtor = 0 ; ?>
                        <?php $sum_1102050101_109_receive = 0 ; ?>
                        @foreach($_1102050101_109 as $row)
                        <td align="center">2</td>
                        <td align="right">1102050101.109</td>
                        <td class="text-left">ลูกหนี้-ระบบปฏิบัติการฉุกเฉิน</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_109_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>   
                        <?php $sum_1102050101_109_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_109_receive += $row->receive ; ?>
                        @endforeach    
                    </tr>   
                    <tr>
                        <?php $sum_1102050101_201_debtor = 0 ; ?>
                        <?php $sum_1102050101_201_receive = 0 ; ?>
                        @foreach($_1102050101_201 as $row)
                        <td align="center">3</td>
                        <td align="right">1102050101.201</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา UC-OP ใน CUP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_201_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_201_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_201_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_203_debtor = 0 ; ?>
                        <?php $sum_1102050101_203_receive = 0 ; ?>
                        @foreach($_1102050101_203 as $row)
                        <td align="center">4</td>
                        <td align="right">1102050101.203</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา UC-OP นอก CUP (ในจังหวัดสังกัด สธ.)</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_203_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_203_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_203_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_209_debtor = 0 ; ?>
                        <?php $sum_1102050101_209_receive = 0 ; ?>
                        @foreach($_1102050101_209 as $row)
                        <td align="center">5</td>
                        <td align="right">1102050101.209</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ด้านการสร้างเสริมสุขภาพและป้องกันโรค (P&P)</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_209_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_209_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_209_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_216_debtor = 0 ; ?>
                        <?php $sum_1102050101_216_receive = 0 ; ?>
                        @foreach($_1102050101_216 as $row)
                        <td align="center">6</td>
                        <td align="right">1102050101.216</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR)</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">            
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_216_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_216_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_216_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_301_debtor = 0 ; ?>
                        <?php $sum_1102050101_301_receive = 0 ; ?>
                        @foreach($_1102050101_301 as $row)
                        <td align="center">7</td>
                        <td align="right">1102050101.301</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_301_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_301_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_301_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_303_debtor = 0 ; ?>
                        <?php $sum_1102050101_303_receive = 0 ; ?>
                        @foreach($_1102050101_303 as $row)
                        <td align="center">8</td>
                        <td align="right">1102050101.303</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ประกันสังคม OP-นอกเครือข่าย สังกัด สป.สธ.</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_303_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_303_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_303_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_307_debtor = 0 ; ?>
                        <?php $sum_1102050101_307_receive = 0 ; ?>
                        @foreach($_1102050101_307 as $row)
                        <td align="center">9</td>
                        <td align="right">1102050101.307</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ประกันสังคม-กองทุนทดแทน</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>     
                        <td align="center">                
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_307_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_307_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_307_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_309_debtor = 0 ; ?>
                        <?php $sum_1102050101_309_receive = 0 ; ?>
                        @foreach($_1102050101_309 as $row)
                        <td align="center">10</td>
                        <td align="right">1102050101.309</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ประกันสังคม-ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_309_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_309_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_309_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_401_debtor = 0 ; ?>
                        <?php $sum_1102050101_401_receive = 0 ; ?>
                        @foreach($_1102050101_401 as $row)
                        <td align="center">11</td>
                        <td align="right">1102050101.401</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกจ่ายตรงกรมบัญชีกลาง OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_401_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_401_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_401_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_501_debtor = 0 ; ?>
                        <?php $sum_1102050101_501_receive = 0 ; ?>
                        @foreach($_1102050101_501 as $row)
                        <td align="center">12</td>
                        <td align="right">1102050101.501</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_501_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_501_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_501_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>
                    <tr>
                        <?php $sum_1102050101_503_debtor = 0 ; ?>
                        <?php $sum_1102050101_503_receive = 0 ; ?>
                        @foreach($_1102050101_503 as $row)
                        <td align="center">13</td>
                        <td align="right">1102050101.503</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP นอก CUP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_503_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_503_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_503_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>
                    <tr>
                        <?php $sum_1102050101_701_debtor = 0 ; ?>
                        <?php $sum_1102050101_701_receive = 0 ; ?>
                        @foreach($_1102050101_701 as $row)
                        <td align="center">14</td>
                        <td align="right">1102050101.701</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ OP ใน CUP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_701_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_701_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_701_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                      <tr>
                        <?php $sum_1102050101_702_debtor = 0 ; ?>
                        <?php $sum_1102050101_702_receive = 0 ; ?>
                        @foreach($_1102050101_702 as $row)
                        <td align="center">15</td>
                        <td align="right">1102050101.702</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ OP นอก CUP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_702_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_702_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_702_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>    
                    <tr>
                        <?php $sum_1102050101_703_debtor = 0 ; ?>
                        <?php $sum_1102050101_703_receive = 0 ; ?>
                        @foreach($_1102050101_703 as $row)
                        <td align="center">16</td>
                        <td align="right">1102050101.703</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ เบิกจากส่วนกลาง OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_703_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_703_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_703_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_106_debtor = 0 ; ?>
                        <?php $sum_1102050102_106_receive = 0 ; ?>
                        @foreach($_1102050102_106 as $row)
                        <td align="center">17</td>
                        <td align="right">1102050102.106</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ชําระเงิน OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">             
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_106_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_106_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_106_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_108_debtor = 0 ; ?>
                        <?php $sum_1102050102_108_receive = 0 ; ?>
                        @foreach($_1102050102_108 as $row)
                        <td align="center">18</td>
                        <td align="right">1102050102.108</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>     
                        <td align="center">             
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_108_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_108_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_108_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_110_debtor = 0 ; ?>
                        <?php $sum_1102050102_110_receive = 0 ; ?>
                        @foreach($_1102050102_110 as $row)
                        <td align="center">19</td>
                        <td align="right">1102050102.110</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>     
                        <td align="center">             
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_110_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_110_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_110_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_602_debtor = 0 ; ?>
                        <?php $sum_1102050102_602_receive = 0 ; ?>
                        @foreach($_1102050102_602 as $row)
                        <td align="center">20</td>
                        <td align="right">1102050102.602</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา พรบ.รถ OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_602_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_602_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_602_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_801_debtor = 0 ; ?>
                        <?php $sum_1102050102_801_receive = 0 ; ?>
                        @foreach($_1102050102_801 as $row)
                        <td align="center">21</td>
                        <td align="right">1102050102.801</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_801_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_801_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_801_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_803_debtor = 0 ; ?>
                        <?php $sum_1102050102_803_receive = 0 ; ?>
                        @foreach($_1102050102_803 as $row)
                        <td align="center">22</td>
                        <td align="right">1102050102.803</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.รูปแบบพิเศษ OP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_803_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_803_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_803_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>
                     <tr>
                        <td align="right" colspan = "4"><strong>รวมลูกหนี้ค่ารักษาพยาบาลผู้ป่วยนอก OPD &nbsp;</strong><br></td>   
                        <td align="right" class="text-primary"><strong>{{number_format($sum_1102050101_103_debtor+$sum_1102050101_109_debtor+$sum_1102050101_201_debtor
                            +$sum_1102050101_203_debtor+$sum_1102050101_209_debtor+$sum_1102050101_216_debtor+$sum_1102050101_301_debtor+$sum_1102050101_303_debtor
                            +$sum_1102050101_307_debtor+$sum_1102050101_309_debtor+$sum_1102050101_401_debtor+$sum_1102050101_501_debtor+$sum_1102050101_503_debtor
                            +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_debtor+$sum_1102050102_106_debtor+$sum_1102050102_108_debtor
                            +$sum_1102050102_110_debtor+$sum_1102050102_602_debtor+$sum_1102050102_801_debtor+$sum_1102050102_803_debtor,2)}}&nbsp;</strong>
                        </td>
                        <td align="right" class="text-success"><strong>{{number_format($sum_1102050101_103_receive+$sum_1102050101_109_receive+$sum_1102050101_201_receive
                            +$sum_1102050101_203_receive+$sum_1102050101_209_receive+$sum_1102050101_216_receive+$sum_1102050101_301_receive+$sum_1102050101_303_receive
                            +$sum_1102050101_307_receive+$sum_1102050101_309_receive+$sum_1102050101_401_receive+$sum_1102050101_501_receive+$sum_1102050101_503_debtor
                            +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_receive+$sum_1102050102_106_receive+$sum_1102050102_108_receive
                            +$sum_1102050102_110_receive+$sum_1102050102_602_receive+$sum_1102050102_801_receive+$sum_1102050102_803_receive,2)}}&nbsp;</strong>
                        </td> 
                        <td align="right" style="color:red"><strong>{{number_format(($sum_1102050101_103_receive+$sum_1102050101_109_receive+$sum_1102050101_201_receive
                            +$sum_1102050101_203_receive+$sum_1102050101_209_receive+$sum_1102050101_216_receive+$sum_1102050101_301_receive+$sum_1102050101_303_receive
                            +$sum_1102050101_307_receive+$sum_1102050101_309_receive+$sum_1102050101_401_receive+$sum_1102050101_501_receive+$sum_1102050101_503_debtor
                            +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_receive+$sum_1102050102_106_receive+$sum_1102050102_108_receive
                            +$sum_1102050102_110_receive+$sum_1102050102_602_receive+$sum_1102050102_801_receive+$sum_1102050102_803_receive)
                            -($sum_1102050101_103_debtor+$sum_1102050101_109_debtor+$sum_1102050101_201_debtor+$sum_1102050101_203_debtor+$sum_1102050101_209_debtor
                            +$sum_1102050101_216_debtor+$sum_1102050101_301_debtor+$sum_1102050101_303_debtor+$sum_1102050101_307_debtor+$sum_1102050101_309_debtor
                            +$sum_1102050101_401_debtor+$sum_1102050101_501_debtor+$sum_1102050101_503_debtor+$sum_1102050101_701_debtor+$sum_1102050101_702_debtor
                            +$sum_1102050101_703_debtor+$sum_1102050102_106_debtor+$sum_1102050102_108_debtor+$sum_1102050102_110_debtor+$sum_1102050102_602_debtor
                            +$sum_1102050102_801_debtor+$sum_1102050102_803_debtor),2)}}&nbsp;</strong>
                        </td>              
                    </tr>   
                    <tr>
                        <th class="text-primary" colspan = "8">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ผู้ป่วยใน</th>        
                    </tr>
                    <tr>
                        <?php $sum_1102050101_202_debtor = 0 ; ?>
                        <?php $sum_1102050101_202_receive = 0 ; ?>
                        @foreach($_1102050101_202 as $row)
                        <td align="center">23</td>
                        <td align="right">1102050101.202</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา UC-IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_202_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_202_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_202_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_217_debtor = 0 ; ?>
                        <?php $sum_1102050101_217_receive = 0 ; ?>
                        @foreach($_1102050101_217 as $row)
                        <td align="center">24</td>
                        <td align="right">1102050101.217</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา UC-IP บริการเฉพาะ (CR)</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_217_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_217_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_217_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_302_debtor = 0 ; ?>
                        <?php $sum_1102050101_302_receive = 0 ; ?>
                        @foreach($_1102050101_302 as $row)
                        <td align="center">25</td>
                        <td align="right">1102050101.302</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ประกันสังคม IP เครือข่าย</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_302_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_302_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_302_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_304_debtor = 0 ; ?>
                        <?php $sum_1102050101_304_receive = 0 ; ?>
                        @foreach($_1102050101_304 as $row)
                        <td align="center">26</td>
                        <td align="right">1102050101.304</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ประกันสังคม IP นอกเครือข่าย สังกัด สป.สธ.</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_304_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_304_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_304_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_308_debtor = 0 ; ?>
                        <?php $sum_1102050101_308_receive = 0 ; ?>
                        @foreach($_1102050101_308 as $row)
                        <td align="center">27</td>
                        <td align="right">1102050101.308</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ประกันสังคม 72 ชั่วโมงแรก</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_308_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_308_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_308_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_310_debtor = 0 ; ?>
                        <?php $sum_1102050101_310_receive = 0 ; ?>
                        @foreach($_1102050101_310 as $row)
                        <td align="center">28</td>
                        <td align="right">1102050101.310</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ประกันสังคม ค่าใช้จ่ายสูง IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_310_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_310_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_310_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_402_debtor = 0 ; ?>
                        <?php $sum_1102050101_402_receive = 0 ; ?>
                        @foreach($_1102050101_402 as $row)
                        <td align="center">29</td>
                        <td align="right">1102050101.402</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา-เบิกจ่ายตรง กรมบัญชีกลาง IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_402_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_402_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_402_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_502_debtor = 0 ; ?>
                        <?php $sum_1102050101_502_receive = 0 ; ?>
                        @foreach($_1102050101_502 as $row)
                        <td align="center">30</td>
                        <td align="right">1102050101.502</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">                
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_502_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_502_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_502_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_504_debtor = 0 ; ?>
                        <?php $sum_1102050101_504_receive = 0 ; ?>
                        @foreach($_1102050101_504 as $row)
                        <td align="center">31</td>
                        <td align="right">1102050101.504</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว IP นอก CUP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">                
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_504_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_504_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_504_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050101_704_debtor = 0 ; ?>
                        <?php $sum_1102050101_704_receive = 0 ; ?>
                        @foreach($_1102050101_704 as $row)
                        <td align="center">32</td>
                        <td align="right">1102050101.704</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ เบิกจากส่วนกลาง IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_704_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050101_704_debtor += $row->debtor ; ?>
                        <?php $sum_1102050101_704_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_107_debtor = 0 ; ?>
                        <?php $sum_1102050102_107_receive = 0 ; ?>
                        @foreach($_1102050102_107 as $row)
                        <td align="center">33</td>
                        <td align="right">1102050102.107</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา ชําระเงิน IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_107_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_107_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_107_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_109_debtor = 0 ; ?>
                        <?php $sum_1102050102_109_receive = 0 ; ?>
                        @foreach($_1102050102_109 as $row)
                        <td align="center">34</td>
                        <td align="right">1102050102.109</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกต้นสังกัด IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_109_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_109_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_109_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_111_debtor = 0 ; ?>
                        <?php $sum_1102050102_111_receive = 0 ; ?>
                        @foreach($_1102050102_111 as $row)
                        <td align="center">35</td>
                        <td align="right">1102050102.111</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_111_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_111_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_111_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_603_debtor = 0 ; ?>
                        <?php $sum_1102050102_603_receive = 0 ; ?>
                        @foreach($_1102050102_603 as $row)
                        <td align="center">36</td>
                        <td align="right">1102050102.603</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา พรบ.รถ IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_603_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_603_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_603_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_802_debtor = 0 ; ?>
                        <?php $sum_1102050102_802_receive = 0 ; ?>
                        @foreach($_1102050102_802 as $row)
                        <td align="center">37</td>
                        <td align="right">1102050102.802</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">               
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_802_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_802_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_802_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <?php $sum_1102050102_804_debtor = 0 ; ?>
                        <?php $sum_1102050102_804_receive = 0 ; ?>
                        @foreach($_1102050102_804 as $row)
                        <td align="center">38</td>
                        <td align="right">1102050102.804</td>
                        <td class="text-left">ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.รูปแบบพิเศษ IP</td>
                        <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                        <td align="right" class="text-primary">{{number_format($row->debtor,2)}}&nbsp;</td>
                        <td align="right" @if($row->receive > 0) style="color:green"
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{number_format($row->receive,2)}}&nbsp;
                        </td> 
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{number_format($row->receive-$row->debtor,2)}}&nbsp;
                        </td>    
                        <td align="center">              
                            <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_804_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                        </td>
                        <?php $sum_1102050102_804_debtor += $row->debtor ; ?>
                        <?php $sum_1102050102_804_receive += $row->receive ; ?>
                        @endforeach     
                    </tr>  
                    <tr>
                        <td align="right" colspan = "4"><strong>รวมลูกหนี้ค่ารักษาพยาบาลผู้ป่วยใน IPD &nbsp;</strong><br></td>   
                        <td align="right" class="text-primary"><strong>{{number_format($sum_1102050101_202_debtor+$sum_1102050101_217_debtor
                            +$sum_1102050101_302_debtor+$sum_1102050101_304_debtor+$sum_1102050101_308_debtor+$sum_1102050101_310_debtor
                            +$sum_1102050101_402_debtor+$sum_1102050101_502_debtor+$sum_1102050101_504_debtor+$sum_1102050101_704_debtor
                            +$sum_1102050102_107_debtor+$sum_1102050102_109_debtor+$sum_1102050102_111_debtor+$sum_1102050102_603_debtor
                            +$sum_1102050102_802_debtor+$sum_1102050102_804_debtor,2)}}&nbsp;</strong>
                        </td>
                        <td align="right" class="text-success"><strong>{{number_format($sum_1102050101_202_receive+$sum_1102050101_217_receive
                            +$sum_1102050101_302_receive+$sum_1102050101_304_receive+$sum_1102050101_308_receive+$sum_1102050101_310_receive
                            +$sum_1102050101_402_receive+$sum_1102050101_502_receive+$sum_1102050101_504_receive+$sum_1102050101_704_receive
                            +$sum_1102050102_107_receive+$sum_1102050102_109_receive+$sum_1102050102_111_receive+$sum_1102050102_603_receive
                            +$sum_1102050102_802_receive+$sum_1102050102_804_receive,2)}}&nbsp;</strong>
                        </td> 
                        <td align="right" style="color:red"><strong>{{number_format(($sum_1102050101_202_receive+$sum_1102050101_217_receive
                            +$sum_1102050101_302_receive+$sum_1102050101_304_receive+$sum_1102050101_308_receive+$sum_1102050101_310_receive
                            +$sum_1102050101_402_receive+$sum_1102050101_502_receive+$sum_1102050101_504_receive+$sum_1102050101_704_receive
                            +$sum_1102050102_107_receive+$sum_1102050102_109_receive+$sum_1102050102_111_receive+$sum_1102050102_603_receive
                            +$sum_1102050102_802_receive+$sum_1102050102_804_receive)
                            -($sum_1102050101_202_debtor+$sum_1102050101_217_debtor+$sum_1102050101_302_debtor+$sum_1102050101_304_debtor
                            +$sum_1102050101_308_debtor+$sum_1102050101_310_debtor+$sum_1102050101_402_debtor+$sum_1102050101_502_debtor
                            +$sum_1102050101_504_debtor+$sum_1102050101_704_debtor+$sum_1102050102_107_debtor+$sum_1102050102_109_debtor
                            +$sum_1102050102_111_debtor+$sum_1102050102_603_debtor+$sum_1102050102_802_debtor+$sum_1102050102_804_debtor),2)}}&nbsp;</strong>
                        </td>              
                    </tr>    
                    <tr>
                        <td colspan = "8">
                            <hr>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" colspan = "4"><strong>รวมลูกหนี้ค่ารักษาพยาบาลทั้งหมด &nbsp;</strong><br></td>   
                        <td align="right" class="text-primary"><strong>{{number_format($sum_1102050101_103_debtor+$sum_1102050101_109_debtor+$sum_1102050101_201_debtor
                            +$sum_1102050101_203_debtor+$sum_1102050101_209_debtor+$sum_1102050101_216_debtor+$sum_1102050101_301_debtor+$sum_1102050101_303_debtor
                            +$sum_1102050101_307_debtor+$sum_1102050101_309_debtor+$sum_1102050101_401_debtor+$sum_1102050101_501_debtor+$sum_1102050101_503_debtor
                            +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_debtor+$sum_1102050102_106_debtor+$sum_1102050102_108_debtor
                            +$sum_1102050102_110_debtor+$sum_1102050102_602_debtor+$sum_1102050102_801_debtor+$sum_1102050102_803_debtor+$sum_1102050101_202_debtor
                            +$sum_1102050101_217_debtor+$sum_1102050101_302_debtor+$sum_1102050101_304_debtor+$sum_1102050101_308_debtor+$sum_1102050101_310_debtor
                            +$sum_1102050101_402_debtor+$sum_1102050101_502_debtor+$sum_1102050101_504_debtor+$sum_1102050101_704_debtor+$sum_1102050102_107_debtor
                            +$sum_1102050102_109_debtor+$sum_1102050102_111_debtor+$sum_1102050102_603_debtor+$sum_1102050102_802_debtor+$sum_1102050102_804_debtor,2)}}&nbsp;</strong>
                        </td>
                        <td align="right" class="text-success"><strong>{{number_format($sum_1102050101_103_receive+$sum_1102050101_109_receive+$sum_1102050101_201_receive
                            +$sum_1102050101_203_receive+$sum_1102050101_209_receive+$sum_1102050101_216_receive+$sum_1102050101_301_receive+$sum_1102050101_303_receive
                            +$sum_1102050101_307_receive+$sum_1102050101_309_receive+$sum_1102050101_401_receive+$sum_1102050101_501_receive+$sum_1102050101_503_debtor
                            +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_receive+$sum_1102050102_106_receive+$sum_1102050102_108_receive
                            +$sum_1102050102_110_receive+$sum_1102050102_602_receive+$sum_1102050102_801_receive+$sum_1102050102_803_receive+$sum_1102050101_202_receive
                            +$sum_1102050101_217_receive+$sum_1102050101_302_receive+$sum_1102050101_304_receive+$sum_1102050101_308_receive+$sum_1102050101_310_receive
                            +$sum_1102050101_402_receive+$sum_1102050101_502_receive+$sum_1102050101_504_receive+$sum_1102050101_704_receive+$sum_1102050102_107_receive
                            +$sum_1102050102_109_receive+$sum_1102050102_111_receive+$sum_1102050102_603_receive+$sum_1102050102_802_receive+$sum_1102050102_804_receive,2)}}&nbsp;</strong>
                        </td> 
                        <td align="right" style="color:red"><strong>{{number_format(($sum_1102050101_103_receive+$sum_1102050101_109_receive+$sum_1102050101_201_receive
                            +$sum_1102050101_203_receive+$sum_1102050101_209_receive+$sum_1102050101_216_receive+$sum_1102050101_301_receive+$sum_1102050101_303_receive
                            +$sum_1102050101_307_receive+$sum_1102050101_309_receive+$sum_1102050101_401_receive+$sum_1102050101_501_receive+$sum_1102050101_503_debtor
                            +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_receive+$sum_1102050102_106_receive+$sum_1102050102_108_receive
                            +$sum_1102050102_110_receive+$sum_1102050102_602_receive+$sum_1102050102_801_receive+$sum_1102050102_803_receive+$sum_1102050101_202_receive
                            +$sum_1102050101_217_receive+$sum_1102050101_302_receive+$sum_1102050101_304_receive+$sum_1102050101_308_receive+$sum_1102050101_310_receive
                            +$sum_1102050101_402_receive+$sum_1102050101_502_receive+$sum_1102050101_504_receive+$sum_1102050101_704_receive+$sum_1102050102_107_receive
                            +$sum_1102050102_109_receive+$sum_1102050102_111_receive+$sum_1102050102_603_receive+$sum_1102050102_802_receive+$sum_1102050102_804_receive)
                            -($sum_1102050101_103_debtor+$sum_1102050101_109_debtor+$sum_1102050101_201_debtor+$sum_1102050101_203_debtor+$sum_1102050101_209_debtor
                            +$sum_1102050101_216_debtor+$sum_1102050101_301_debtor+$sum_1102050101_303_debtor+$sum_1102050101_307_debtor+$sum_1102050101_309_debtor
                            +$sum_1102050101_401_debtor+$sum_1102050101_501_debtor+$sum_1102050101_503_debtor+$sum_1102050101_701_debtor+$sum_1102050101_702_debtor
                            +$sum_1102050101_703_debtor+$sum_1102050102_106_debtor+$sum_1102050102_108_debtor+$sum_1102050102_110_debtor+$sum_1102050102_602_debtor
                            +$sum_1102050102_801_debtor+$sum_1102050102_803_debtor+$sum_1102050101_202_debtor+$sum_1102050101_217_debtor+$sum_1102050101_302_debtor
                            +$sum_1102050101_304_debtor+$sum_1102050101_308_debtor+$sum_1102050101_310_debtor+$sum_1102050101_402_debtor+$sum_1102050101_502_debtor
                            +$sum_1102050101_504_debtor+$sum_1102050101_704_debtor+$sum_1102050102_107_debtor+$sum_1102050102_109_debtor+$sum_1102050102_111_debtor+$sum_1102050102_603_debtor
                            +$sum_1102050102_802_debtor+$sum_1102050102_804_debtor),2)}}&nbsp;</strong>
                        </td>              
                    </tr>    
                </table>
            </div> 
        </div>
    </div>
@endsection
