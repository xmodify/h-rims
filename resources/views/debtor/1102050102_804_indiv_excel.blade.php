<?php
$files = "ลูกหนี้รายตัวผังบัญชี-1102050102.804-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.รูปแบบพิเศษ IP.xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=".$files); //ชื่อไฟล์
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<div>        
    <strong>
        <p align=center>
            แบบรายงานบัญชีลูกหนี้ค่ารักษาพยาบาลแยกแยกรายตัว<br>
            รหัสผังบัญชี 1102050102.804-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.รูปแบบพิเศษ IP<br>
            วันที่ {{dateThaifromFull($start_date)}} ถึง {{dateThaifromFull($end_date)}} <br><br>
        </p>
    </strong>
</div>

<div class="container">
    <div class="row justify-content-center">            
        <table width="100%" border="1">
            <thead>
            <tr>
                <th class="text-center">ลำดับ</th>
                <th class="text-center">HN</th>
                <th class="text-center">AN</th>
                <th class="text-center">CID</th>
                <th class="text-center">ชื่อ-สกุล</th>  
                <th class="text-center">สิทธิ</th>
                <th class="text-center">Admit</th>
                <th class="text-center">Discharge</th>
                <th class="text-center">ICD10</th>
                <th class="text-center">AdjRW</th>
                <th class="text-center">ค่ารักษาทั้งหมด</th>  
                <th class="text-center">ชำระเอง</th>
                <th class="text-center">ฟอกไต</th>
                <th class="text-center text-primary">ลูกหนี้</th>
                <th class="text-center text-primary">ชดเชย</th>
                <th class="text-center" style="color: #9c27b0;">ปรับเพิ่ม</th>
                <th class="text-center" style="color: #673ab7;">ปรับลด</th>
                <th class="text-center text-primary">ยอดคงเหลือ</th>
                <th class="text-center text-primary">REP</th>
                <th class="text-center text-primary">เลขงวด</th>
                <th class="text-center text-primary">วันที่ออกใบเสร็จ</th>
                <th class="text-center text-primary">เลขที่ใบเสร็จ</th>
                <th class="text-center text-primary">อายุหนี้</th>  
            </tr>     
            </thead> 
            <?php 
                $count = 1 ; 
                $sum_income = 0 ; $sum_rcpt_money = 0 ; $sum_kidney = 0 ; $sum_debtor = 0 ; 
                $sum_receive = 0 ; $sum_adj_inc = 0; $sum_adj_dec = 0; $sum_balance = 0;
            ?>

            @foreach($debtor as $row)          
            @php
                $total_received = ($row->receive ?? 0);
                $balance = ($total_received + ($row->adj_inc ?? 0) - ($row->adj_dec ?? 0)) - $row->debtor;
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
                <td align="right" @if($total_received > 0) style="color:green" 
                    @elseif($total_received < 0) style="color:red" @endif>
                    {{ number_format($total_received,2) }}
                </td>
                <td align="right" style="color: #9c27b0;">{{ number_format($row->adj_inc ?? 0, 2) }}</td>
                <td align="right" style="color: #673ab7;">{{ number_format($row->adj_dec ?? 0, 2) }}</td>
                <td align="right" style="color:@if($balance < -0.01) red @elseif($balance > 0.01) green @else black @endif">
                    {{ number_format($balance,2) }}
                </td>                        
                <td align="center">{{ $row->repno }} {{ $row->cipn_rid ?? '' }} {{ $row->csop_rid ?? '' }}</td>
                <td align="center">{{ $row->stm_round_no }}</td>
                <td align="center">{{ $row->stm_receipt_date }}</td>
                <td align="center">{{ $row->stm_receive_no }}</td>
                <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  
                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" 
                    @else style="background-color: #FF7F7F;" @endif >
                    {{ $row->days }} วัน
                </td> 
            </tr>                
            <?php 
                $count++; 
                $sum_income += $row->income ; 
                $sum_rcpt_money += $row->rcpt_money ; 
                $sum_kidney += $row->kidney ; 
                $sum_debtor += $row->debtor ; 
                $sum_receive += $total_received ; 
                $sum_adj_inc += ($row->adj_inc ?? 0) ;
                $sum_adj_dec += ($row->adj_dec ?? 0) ;
                $sum_balance += $balance ;
            ?>
            @endforeach   
            <tr>
                <td align="right" colspan = "10"><strong>รวมทั้งสิ้น &nbsp;</strong><br></td> 
                <td align="right"><strong>{{number_format($sum_income,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_rcpt_money,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_kidney,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_debtor,2)}}&nbsp;</strong></td>                
                <td align="right"><strong>{{number_format($sum_receive,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_adj_inc,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_adj_dec,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_balance,2)}}&nbsp;</strong></td>
                <td colspan="5"></td>
            </tr>          
        </table> 
    </div>
</div>    
