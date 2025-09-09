<?php
$files = "ลูกหนี้รายตัวผังบัญชี-1102050101.202 ลูกหนี้ค่ารักษา UC - IP.xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=".$files); //ชื่อไฟล์
?>
 
 
<div>        
    <strong>
        <p align=center>
            แบบรายงานบัญชีลูกหนี้ค่ารักษาพยาบาลแยกแยกรายตัว<br>
            รหัสผังบัญชี 1102050101.202 ลูกหนี้ค่ารักษา UC - IP <br>
            วันที่ {{dateThaifromFull($start_date)}} ถึง {{dateThaifromFull($end_date)}} <br><br>
        </p>
    </strong>
</div>

<div class="container">
    <div class="row justify-content-center">            
        <table width="100%" >
            <thead>
            <tr>
                <th class="text-center">ลำดับ</th>
                <th class="text-center">HN</th>
                <th class="text-center">AN</th>
                <th class="text-center">ชื่อ-สกุล</th>  
                <th class="text-center">สิทธิ</th>
                <th class="text-center">Admit</th>
                <th class="text-center">Discharge</th>
                <th class="text-center">ICD10</th>
                <th class="text-center">AdjRW</th>
                <th class="text-center">ค่ารักษาทั้งหมด</th>  
                <th class="text-center">ชำระเอง</th>
                <th class="text-center">บริการเฉพาะ</th>
                <th class="text-center text-primary">ลูกหนี้</th>
                <th class="text-center text-primary">อัตราจ่าย/RW</th>
                <th class="text-center text-primary">ชดเชย RW</th> 
                <th class="text-center text-primary">ชดเชย CR</th>
                <th class="text-center text-primary">ชดเชย ทั้งหมด</th>
                <th class="text-center text-primary">ผลต่าง</th>
                <th class="text-center text-primary">REP</th> 
                <th class="text-center text-primary">อายุหนี้</th>  
            </tr>     
            </thead> 
            <?php $count = 1 ; ?>
            <?php $sum_income = 0 ; ?>
            <?php $sum_rcpt_money = 0 ; ?>
            <?php $sum_other = 0 ; ?>
            <?php $sum_debtor = 0 ; ?>
            <?php $sum_receive_ip_compensate_pay = 0 ; ?>
            <?php $sum_receive_total = 0 ; ?>
            @foreach($debtor as $row)          
            <tr>
                <td align="center">{{ $count }}</td>
                <td align="center">{{ $row->hn }}</td>
                <td align="center">{{ $row->an }}</td>
                <td align="left">{{ $row->ptname }}</td>
                <td align="center">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                <td align="right">{{ DateThai($row->regdate) }}</td>
                <td align="right">{{ DateThai($row->dchdate) }}</td>
                <td align="right">{{ $row->pdx }}</td>  
                <td align="right">{{ $row->adjrw }}</td>                        
                <td align="right">{{ number_format($row->income,2) }}</td>
                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                <td align="right">{{ number_format($row->other,2) }}</td> 
                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>  
                <td align="right" @if($row->fund_ip_payrate > 0) style="color:green" 
                    @elseif($row->fund_ip_payrate < 0) style="color:red" @endif>
                    {{ number_format($row->fund_ip_payrate,2) }}
                </td>
                <td align="right" @if($row->receive_ip_compensate_pay > 0) style="color:green" 
                    @elseif($row->receive_ip_compensate_pay < 0) style="color:red" @endif>
                    {{ number_format($row->receive_ip_compensate_pay,2) }}
                </td>
                <td align="right" @if($row->receive_total-$row->receive_ip_compensate_pay > 0) style="color:green" 
                    @elseif($row->receive_total-$row->receive_ip_compensate_pay < 0) style="color:red" @endif>
                    {{ number_format($row->receive_total-$row->receive_ip_compensate_pay,2) }}
                </td>
                <td align="right" @if($row->receive_total > 0) style="color:green" 
                    @elseif($row->receive_total < 0) style="color:red" @endif>
                    {{ number_format($row->receive_total,2) }}
                </td>
                <td align="right" @if(($row->receive_ip_compensate_pay-$row->debtor) > 0) style="color:green" 
                    @elseif(($row->receive_ip_compensate_pay-$row->debtor) < 0) style="color:red" @endif>
                    {{ number_format($row->receive_ip_compensate_pay-$row->debtor,2) }}
                </td>                                        
                <td align="center">{{ $row->repno }}</td>
                <td align="right" 
                    @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                    @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                    {{ $row->days }} วัน
                </td> 
            </tr>                
            <?php $count++; ?>
            <?php $sum_income += $row->income ; ?>
            <?php $sum_rcpt_money += $row->rcpt_money ; ?>
            <?php $sum_other += $row->other ; ?>
            <?php $sum_debtor += $row->debtor ; ?> 
            <?php $sum_receive_ip_compensate_pay += $row->receive_ip_compensate_pay ; ?>      
            <?php $sum_receive_total += $row->receive_total ; ?>      
            @endforeach   
            <tr>
                <td align="right" colspan = "9"><strong>รวมค่ารักษาพยาบาลทั้งสิ้น &nbsp;</strong><br></td> 
                <td align="right"><strong>{{number_format($sum_income,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_rcpt_money,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_other,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_debtor,2)}}&nbsp;</strong></td>
                <td align="right"><strong></td>
                <td align="right"><strong>{{number_format($sum_receive_ip_compensate_pay,2)}}&nbsp;</strong></td> 
                <td align="right"><strong>{{number_format($sum_receive_total-$sum_receive_ip_compensate_pay,2)}}&nbsp;</strong></td> 
                <td align="right"><strong>{{number_format($sum_receive_total,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_receive_ip_compensate_pay-$sum_debtor,2)}}&nbsp;</strong></td>
            </tr>          
        </table> 
    </div>
</div>    




