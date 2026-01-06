<?php
$files = "ลูกหนี้รายตัวผังบัญชี-1102050102.110-ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น OP.xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=".$files); //ชื่อไฟล์
?>
 
<div>        
    <strong>
        <p align=center> 
            แบบรายงานบัญชีลูกหนี้ค่ารักษาพยาบาลแยกแยกรายตัว<br>
            รหัสผังบัญชี 1102050102.110-ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น OP<br>
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
                <th class="text-center">วันที่</th>
                <th class="text-center">HN</th>
                <th class="text-center">ชื่อ-สกุล</th>
                <th class="text-center">สิทธิ</th>
                <th class="text-center">ICD10</th>
                <th class="text-center">ค่ารักษาทั้งหมด</th>  
                <th class="text-center">ชำระเอง</th>
                <th class="text-center">ทั่วไป</th> 
                <th class="text-center">ฟอกไต</th> 
                <th class="text-center">PPFS</th>  
                <th class="text-center">กองทุนอื่น</th>
                <th class="text-center">ลูกหนี้</th>
                <th class="text-center text-primary">ชดเชย ทั่วไป</th>
                <th class="text-center text-primary">ชดเชย ฟอกไต</th>
                <th class="text-center text-primary">ชดเชยรวม</th>
                <th class="text-center text-primary">ผลต่าง</th>
                <th class="text-center text-primary">ชดเชย PPFS</th>  
                <th class="text-center text-primary">REP</th>   
                <th class="text-center text-primary">อายุหนี้</th>
            </tr>     
            </thead> 
            <?php $count = 1 ; ?>
            <?php $sum_income = 0 ; ?>
            <?php $sum_rcpt_money = 0 ; ?>
            <?php $sum_ofc = 0 ; ?>
            <?php $sum_kidney = 0 ; ?>
            <?php $sum_ppfs = 0 ; ?>
            <?php $sum_other = 0 ; ?>
            <?php $sum_debtor = 0 ; ?>            
            <?php $sum_receive_ofc = 0 ; ?>
            <?php $sum_receive_kidney = 0 ; ?>           
            <?php $sum_receive = 0 ; ?>
            <?php $sum_receive_ppfs = 0 ; ?>
            @foreach($debtor as $row)          
            <tr>
                <td align="center">{{ $count }}</td>
                <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                <td align="center">{{ $row->hn }}</td>
                <td align="left">{{ $row->ptname }}</td>
                <td align="left">{{ $row->pttype }}</td>
                <td align="right">{{ $row->pdx }}</td>                      
                <td align="right">{{ number_format($row->income,2) }}</td>
                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                <td align="right">{{ number_format($row->ofc,2) }}</td>
                <td align="right">{{ number_format($row->kidney,2) }}</td>
                <td align="right">{{ number_format($row->ppfs,2) }}</td>
                <td align="right">{{ number_format($row->other,2) }}</td>
                <td align="right">{{ number_format($row->debtor,2) }}</td>                
                <td align="right">{{ number_format($row->receive_ofc,2) }}</td>
                <td align="right">{{ number_format($row->receive_kidney,2) }}</td>                
                <td align="right">{{ number_format($row->receive,2) }}</td>
                <td align="right">{{ number_format($row->receive-$row->debtor,2) }}</td>
                <td align="right">{{ number_format($row->receive_ppfs,2) }}</td>
                <td align="center">{{ $row->repno }}</td>
                <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                    @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                    {{ $row->days }} วัน
                </td> 
            </tr>                
            <?php $count++; ?>
            <?php $sum_income += $row->income ; ?>
            <?php $sum_rcpt_money += $row->rcpt_money ; ?>
            <?php $sum_ofc += $row->ofc ; ?> 
            <?php $sum_kidney += $row->kidney ; ?> 
            <?php $sum_ppfs += $row->ppfs ; ?> 
            <?php $sum_debtor += $row->debtor ; ?>
            <?php $sum_receive_ofc += $row->receive_ofc ; ?> 
            <?php $sum_receive_kidney += $row->receive_kidney ; ?>             
            <?php $sum_receive += $row->receive ; ?>   
            <?php $sum_receive_ppfs += $row->receive_ppfs ; ?>   
            @endforeach   
            <tr>
                <td align="right" colspan = "6"><strong>รวมค่ารักษาพยาบาลทั้งสิ้น &nbsp;</strong><br></td> 
                <td align="right"><strong>{{number_format($sum_income,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_rcpt_money,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_ofc,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_kidney,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_ppfs,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_other,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_debtor,2)}}&nbsp;</strong></td>                
                <td align="right"><strong>{{number_format($sum_receive_ofc,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_receive_kidney,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_receive,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_receive-$sum_debtor,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_receive_ppfs,2)}}&nbsp;</strong></td> 
            </tr>          
        </table> 
    </div>
</div>    




