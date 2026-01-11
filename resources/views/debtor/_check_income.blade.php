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
                            <th class="text-center" colspan = "7">ผู้ป่วยนอก</th>            
                        </tr>  
                        <tr class="table-secondary">
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด [ใบสั่งยา]</th>
                            <th class="text-center">ต้องชำระเงิน [ใบสั่งยา]</th>
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด [สรุป]</th>
                            <th class="text-center">ต้องชำระเงิน [สรุป]</th>  
                            <th class="text-center">ชำระเงินแล้ว [สรุป]</th> 
                            <th class="text-center">ลูกหนี้ [สรุป]</th>                           
                            <th class="text-center">สถานะ</th>
                        </tr>     
                        </thead>                         
                        @foreach($check_income as $row)          
                        <tr>  
                            <td align="right" @if( (float)($row->op_income ?? 0) == (float)($row->vn_income ?? 0) ) style="color:green"
                                @elseif( (float)($row->op_income ?? 0) !== (float)($row->vn_income ?? 0) ) style="color:red" @endif>
                                {{ number_format($row->op_income,2) }}
                            </td>
                            <td align="right">{{ number_format($row->op_paid,2) }}</td>
                            <td align="right" @if( (float)($row->op_income ?? 0) == (float)($row->vn_income ?? 0) ) style="color:green"
                                @elseif( (float)($row->op_income ?? 0) !== (float)($row->vn_income ?? 0) ) style="color:red" @endif>
                                {{ number_format($row->vn_income,2) }}
                            </td>
                            <td align="right">{{ number_format($row->vn_paid,2) }}</td>  
                            <td align="right">{{ number_format($row->vn_rcpt,2) }}</td>  
                            <td align="right" class="text-success"><strong>{{ number_format($row->vn_debtor,2) }}</strong></td>                         
                            <td class="text-center"@if($row->status_check == 'Success') style="color:green"
                                @elseif($row->status_check == 'Resync VN') style="color:red" @endif>
                                {{ $row->status_check }}
                            </td>
                        </tr>                        
                        @endforeach 
                    </table> 
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="table-success">
                            <th class="text-center" colspan = "7">ผู้ป่วยนอก แยกกลุ่มสิทธิ</th>            
                        </tr>  
                        <tr class="table-secondary">
                            <th class="text-center">INSCL</th>
                            <th class="text-center">กลุ่มสิทธิ</th>
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด</th>
                            <th class="text-center">ต้องชำระเงิน</th>  
                            <th class="text-center">ชำระเงินแล้ว</th> 
                            <th class="text-center">ลูกหนี้</th> 
                        </tr>     
                        </thead>
                        @php
                            $sum_income = 0;
                            $sum_paid = 0;
                            $sum_rcpt = 0;
                            $sum_debtor = 0;
                        @endphp
                        @foreach($check_income_pttype as $row)          
                        <tr>  
                            <td class="text-center">{{ $row->inscl }}</td>
                            <td class="text-left">{{ $row->pttype_group }}</td>
                            <td align="right">{{ number_format($row->income,2) }}</td>
                            <td align="right">{{ number_format($row->paid_money,2) }}</td>  
                            <td align="right">{{ number_format($row->rcpt_money,2) }}</td> 
                            <td align="right" class="text-success">{{ number_format($row->debtor,2) }}</td> 
                        </tr>
                        @php
                            $sum_income += $row->income;
                            $sum_paid += $row->paid_money;
                            $sum_rcpt += $row->rcpt_money;
                            $sum_debtor += $row->debtor;
                        @endphp
                        @endforeach 
                        <tr>
                            <td class="text-end" colspan="2"><strong>รวม</strong></td>
                            <td align="right"><strong>{{ number_format($sum_income,2) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_paid,2) }}</strong></td>
                            <td align="right"><strong>{{ number_format($sum_rcpt,2) }}</strong></td>
                            <td align="right" class="text-success"><strong>{{ number_format($sum_debtor,2) }}</strong></td>
                        </tr>
                    </table> 
                </div>
                <div class="col-md-6">   
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="table-danger">
                            <th class="text-center" colspan = "7">ผู้ป่วยใน</th>            
                        </tr>  
                        <tr class="table-secondary">
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด [ใบสั่งยา]</th>
                            <th class="text-center">ต้องชำระเงิน [ใบสั่งยา]</th>
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด [สรุป]</th>
                            <th class="text-center">ต้องชำระเงิน [สรุป]</th>  
                            <th class="text-center">ชำระเงินแล้ว [สรุป]</th> 
                            <th class="text-center">ลูกหนี้ [สรุป]</th>                           
                            <th class="text-center">สถานะ</th>
                        </tr>         
                        </thead>
                        @foreach($check_income_ipd as $row)          
                        <tr>  
                            <td align="right" @if( (float)($row->op_income ?? 0) == (float)($row->an_income ?? 0) ) style="color:green"
                                @elseif( (float)($row->op_income ?? 0) !== (float)($row->an_income ?? 0) ) style="color:red" @endif>
                                {{ number_format($row->op_income,2) }}
                            </td>
                            <td align="right">{{ number_format($row->op_paid,2) }}</td>
                            <td align="right" @if( (float)($row->op_income ?? 0) == (float)($row->an_income ?? 0) ) style="color:green"
                                @elseif( (float)($row->op_income ?? 0) !== (float)($row->an_income ?? 0) ) style="color:red" @endif>
                                {{ number_format($row->an_income,2) }}
                            </td>
                            <td align="right">{{ number_format($row->an_paid,2) }}</td>  
                            <td align="right">{{ number_format($row->an_rcpt,2) }}</td>  
                            <td align="right" class="text-success"><strong>{{ number_format($row->an_debtor,2) }}</strong></td>                         
                            <td class="text-center" @if($row->status_check == 'Success') style="color:green"
                                @elseif($row->status_check == 'Resync AN') style="color:red" @endif>
                                {{ $row->status_check }}
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
                            <th class="text-center">ค่าใช้จ่ายทั้งหมด</th>
                            <th class="text-center">ต้องชำระเงิน</th>  
                            <th class="text-center">ชำระเงินแล้ว</th> 
                            <th class="text-center">ลูกหนี้</th> 
                        </tr>     
                        </thead>
                        @php
                            $sum_income = 0;
                            $sum_paid = 0;
                            $sum_rcpt = 0;
                            $sum_debtor = 0;
                        @endphp
                        @foreach($check_income_ipd_pttype as $row)          
                        <tr>  
                            <td class="text-center">{{ $row->inscl }}</td>
                            <td class="text-left">{{ $row->pttype_group }}</td>
                            <td align="right">{{ number_format($row->income,2) }}</td>
                            <td align="right">{{ number_format($row->paid_money,2) }}</td>  
                            <td align="right">{{ number_format($row->rcpt_money,2) }}</td> 
                            <td align="right" class="text-success">{{ number_format($row->debtor,2) }}</td> 
                        </tr>
                        @php
                            $sum_income += $row->income;
                            $sum_paid += $row->paid_money;
                            $sum_rcpt += $row->rcpt_money;
                            $sum_debtor += $row->debtor;
                        @endphp
                        @endforeach 
                        <tr>
                            <td class="text-end" colspan="2"><strong>รวม</strong></td>
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
@endsection


