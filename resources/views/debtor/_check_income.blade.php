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
                        <?php $count = 1 ; ?>
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
                        <?php $count++; ?>
                        @endforeach 
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
                        <?php $count = 1 ; ?>
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
                        <?php $count++; ?>
                        @endforeach 
                    </table> 
                </div>
            </div>
        </div>    
    </div>
@endsection

