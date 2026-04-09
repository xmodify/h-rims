<?php
$files = "ลูกหนี้รายตัวผังบัญชี-1102050102.603-ลูกหนี้ค่ารักษา พรบ.รถ IP.xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=".$files); //ชื่อไฟล์
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 
<div>        
    <strong>
        <p align=center>
            แบบรายงานบัญชีลูกหนี้ค่ารักษาพยาบาลแยกแยกรายตัว<br>
            รหัสผังบัญชี 1102050102.603-ลูกหนี้ค่ารักษา พรบ.รถ IP<br>
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
                <th class="text-center">กองทุนอื่น</th>
                <th class="text-center">ลูกหนี้</th>
                <th class="text-center">ชดเชย</th>
                <th class="text-center">ปรับเพิ่ม</th>
                <th class="text-center">ปรับลด</th>
                <th class="text-center">ยอดคงเหลือ</th>
                <th class="text-center">เลขที่ใบเสร็จ</th>
                <th class="text-center">วันที่ออกใบเสร็จ</th>                
                <th class="text-center">อายุหนี้</th> 
            </tr>     
            </thead> 
            <tbody>
            <?php 
                $count = 1; $sum_income = 0; $sum_rcpt = 0; $sum_other = 0; $sum_debtor = 0;
                $sum_receive = 0; $sum_adj_inc = 0; $sum_adj_dec = 0; $sum_balance = 0;
            ?>
            @foreach($debtor as $row)          
            @php 
                $balance = (($row->receive ?? 0) + ($row->adj_inc ?? 0) - ($row->adj_dec ?? 0)) - ($row->debtor ?? 0);
            @endphp
            <tr>
                <td align="center">{{ $count }}</td>
                <td align="center">{{ $row->hn }}</td>
                <td align="center">{{ $row->an }}</td>
                <td align="center" style='mso-number-format:"\@"' >{{ $row->cid }}</td>
                <td align="left">{{ $row->ptname }}</td>
                <td align="left">{{ $row->pttype }} </td>
                <td align="right">{{ DateThai($row->regdate) }}</td>
                <td align="right">{{ DateThai($row->dchdate) }}</td>
                <td align="right">{{ $row->pdx }}</td>  
                <td align="right">{{ $row->adjrw }}</td>                        
                <td align="right">{{ number_format($row->income ?? 0,2) }}</td>
                <td align="right">{{ number_format($row->rcpt_money ?? 0,2) }}</td>
                <td align="right">{{ number_format($row->other ?? 0,2) }}</td>
                <td align="right" class="text-primary">{{ number_format($row->debtor ?? 0,2) }}</td>  
                <td align="right">{{ number_format($row->receive ?? 0,2) }}</td>
                <td align="right">{{ number_format($row->adj_inc ?? 0,2) }}</td>
                <td align="right">{{ number_format($row->adj_dec ?? 0,2) }}</td>
                <td align="right">{{ number_format($balance,2) }}</td>
                <td align="center">{{ $row->repno }}</td>
                <td align="center">{{ $row->receive_date ? DateThai($row->receive_date) : '' }}</td>
                <td align="right">{{ $row->days }} วัน</td>    
            </tr>                
            <?php 
                $count++; $sum_income += ($row->income ?? 0); $sum_rcpt += ($row->rcpt_money ?? 0);
                $sum_other += ($row->other ?? 0); $sum_debtor += ($row->debtor ?? 0);
                $sum_receive += ($row->receive ?? 0); $sum_adj_inc += ($row->adj_inc ?? 0);
                $sum_adj_dec += ($row->adj_dec ?? 0); $sum_balance += $balance;
            ?>
            @endforeach   
            </tbody>
            <tfoot>
            <tr style="font-weight:bold;">
                <td align="right" colspan = "10">รวมทั้งสิ้น</td> 
                <td align="right">{{number_format($sum_income ?? 0,2)}}</td>  
                <td align="right">{{number_format($sum_rcpt ?? 0,2)}}</td>  
                <td align="right">{{number_format($sum_other ?? 0,2)}}</td>  
                <td align="right">{{number_format($sum_debtor ?? 0,2)}}</td>               
                <td align="right">{{number_format($sum_receive ?? 0,2)}}</td>  
                <td align="right">{{number_format($sum_adj_inc ?? 0,2)}}</td>
                <td align="right">{{number_format($sum_adj_dec ?? 0,2)}}</td>
                <td align="right">{{number_format($sum_balance ?? 0,2)}}</td>
                <td colspan="3"></td>
            </tr>          
            </tfoot>
        </table> 
    </div>
</div>    
