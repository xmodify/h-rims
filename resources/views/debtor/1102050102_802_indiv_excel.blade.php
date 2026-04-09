<?php
$files = "ลูกหนี้รายตัวผังบัญชี-1102050102.802-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.IP.xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=".$files); //ชื่อไฟล์
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<div>        
    <strong>
        <p align=center>
            แบบรายงานบัญชีลูกหนี้ค่ารักษาพยาบาลแยกแยกรายตัว<br>
            รหัสผังบัญชี 1102050102.802-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.IP<br>
            วันที่ {{dateThaifromFull($start_date)}} ถึง {{dateThaifromFull($end_date)}} <br><br>
        </p>
    </strong>
</div>

<div class="container">
    <div class="row justify-content-center">            
        <table width="100%">
            <thead>
            <tr>
                <th class="text-center">ลำดับ</th>
                <th class="text-center">HN</th>
                <th class="text-center">AN</th>
                <th class="text-center">CID</th>
                <th class="text-center">ชื่อ-สกุล</th>  
                <th class="text-center">สิทธิ</th>
                <th class="text-center">Admit</th>
                <th class="text-center" width = "5%">Discharge</th>
                <th class="text-center">ICD10</th>
                <th class="text-center">AdjRW</th>
                <th class="text-center">ค่ารักษาทั้งหมด</th>  
                <th class="text-center">ชำระเอง</th>
                <th class="text-center">ฟอกไต</th>
                <th class="text-center text-primary">ลูกหนี้</th>
                <th class="text-center text-primary">LGO</th> 
                <th class="text-center text-primary">ฟอกไต</th>
                <th class="text-center" style="color: #9c27b0;">ปรับเพิ่ม</th>
                <th class="text-center" style="color: #673ab7;">ปรับลด</th>
                <th class="text-center">ยอดคงเหลือ</th>
                <th class="text-center text-primary">REP</th>
                <th class="text-center text-primary">อายุหนี้</th>   
            </tr>     
            </thead> 
            <tbody>
            <?php $count = 1 ; ?>
            <?php $sum_income = 0 ; ?>
            <?php $sum_rcpt_money = 0 ; ?>
            <?php $sum_kidney = 0 ; ?>
            <?php $sum_debtor = 0 ; ?>
            <?php $sum_receive_lgo = 0 ; ?>
            <?php $sum_receive_kidney = 0 ; ?>
            <?php $sum_receive = 0 ; ?>
            <?php $sum_adj_inc = 0 ; ?>
            <?php $sum_adj_dec = 0 ; ?>
            <?php $sum_balance = 0 ; ?>

            @foreach($debtor as $row)          
            @php 
                $balance = ($row->receive + ($row->adj_inc ?? 0) - ($row->adj_dec ?? 0)) - $row->debtor;
            @endphp
            <tr>
                <td align="center">{{ $count }}</td>
                <td align="center">{{ $row->hn }}</td>
                <td align="center">{{ $row->an }}</td>
                <td align="center" style='mso-number-format:"\@"' >{{ $row->cid }}</td>
                <td align="left">{{ $row->ptname }}</td>
                <td align="center">{{ $row->pttype }}</td>
                <td align="right">{{ DateThai($row->regdate) }}</td>
                <td align="right">{{ DateThai($row->dchdate) }}</td>
                <td align="right">{{ $row->pdx }}</td>  
                <td align="right">{{ $row->adjrw }}</td>                        
                <td align="right">{{ number_format($row->income,2) }}</td>
                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                <td align="right">{{ number_format($row->kidney,2) }}</td> 
                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td> 
                <td align="right" @if($row->receive_lgo > 0) style="color:green" 
                    @elseif($row->receive_lgo < 0) style="color:red" @endif>
                    {{ number_format($row->receive_lgo,2) }}
                </td>
                <td align="right" @if($row->receive_kidney > 0) style="color:green" 
                    @elseif($row->receive_kidney < 0) style="color:red" @endif>
                    {{ number_format($row->receive_kidney,2) }}
                </td>
                <td align="right" style="color: #9c27b0;">{{ number_format($row->adj_inc ?? 0, 2) }}</td>
                <td align="right" style="color: #673ab7;">{{ number_format($row->adj_dec ?? 0, 2) }}</td>
                <td align="right" style="color:@if($balance < -0.01) red @elseif($balance > 0.01) green @else black @endif">{{ number_format($balance, 2) }}</td>
                <td align="left">{{ $row->stm_repno ?? '' }} {{ $row->rid ?? '' }}</td>
                <td align="center" @if($row->days < 90) style="background-color: #90EE90;" 
                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" 
                    @else style="background-color: #FF7F7F;" @endif >
                    {{ $row->days }} วัน
                </td> 
            </tr>                
            <?php $count++; ?>
            <?php $sum_income += $row->income ; ?>
            <?php $sum_rcpt_money += $row->rcpt_money ; ?>
            <?php $sum_kidney += $row->kidney ; ?>
            <?php $sum_debtor += $row->debtor ; ?>
            <?php $sum_receive_lgo += $row->receive_lgo ; ?>
            <?php $sum_receive_kidney += $row->receive_kidney ; ?>
            <?php $sum_receive += $row->receive ; ?>
            <?php $sum_adj_inc += $row->adj_inc ?? 0; ?>
            <?php $sum_adj_dec += $row->adj_dec ?? 0; ?>
            <?php $sum_balance += $balance; ?>
            @endforeach   
            </tbody>
            <tfoot>
            <tr>
                <td align="right" colspan = "10"><strong>รวม &nbsp;</strong></td> 
                <td align="right"><strong>{{number_format($sum_income,2)}}</strong></td>  
                <td align="right"><strong>{{number_format($sum_rcpt_money,2)}}</strong></td>  
                <td align="right"><strong>{{number_format($sum_kidney,2)}}</strong></td>  
                <td align="right"><strong>{{number_format($sum_debtor,2)}}</strong></td>
                <td align="right"><strong>{{number_format($sum_receive_lgo,2)}}</strong></td> 
                <td align="right"><strong>{{number_format($sum_receive_kidney,2)}}</strong></td> 
                <td align="right"><strong>{{number_format($sum_adj_inc,2)}}</strong></td>
                <td align="right"><strong>{{number_format($sum_adj_dec,2)}}</strong></td>
                <td align="right"><strong>{{number_format($sum_balance,2)}}</strong></td>
                <td colspan="2"></td>
            </tr>          
            </tfoot>
        </table> 
    </div>
</div>    
