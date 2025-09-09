<?php
$files = "ลูกหนี้รายตัวผังบัญชี-1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR).xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=".$files); //ชื่อไฟล์
?>
 
<div>        
    <strong>
        <p align=center> 
            แบบรายงานบัญชีลูกหนี้ค่ารักษาพยาบาลแยกแยกรายตัว<br>
            รหัสผังบัญชี 1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR)<br>
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
                <th class="text-center">ฟอกไต</th>   
                <th class="text-center">บริการเฉพาะ</th>
                <th class="text-center">OP Anywhere</th>        
                <th class="text-center text-primary">ลูกหนี้</th>
                <th class="text-center text-primary">ชดเชย</th> 
                <th class="text-center text-primary">ผลต่าง</th>
                <th class="text-center text-primary">REP</th> 
                <th class="text-center text-primary">อายุหนี้</th>    
            </tr>     
            </thead> 
            <?php $count = 1 ; ?>
            <?php $sum_income = 0 ; ?>
            <?php $sum_rcpt_money = 0 ; ?>
            <?php $sum_kidney = 0 ; ?>
            <?php $sum_cr = 0 ; ?>
            <?php $sum_anywhere = 0 ; ?>
            <?php $sum_debtor = 0 ; ?>
            <?php $sum_receive = 0 ; ?>
            @foreach($debtor as $row)          
            <tr>
                <td align="center">{{ $count }}</td>
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
                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>  
                <td align="right" @if($row->receive > 0) style="color:green" 
                    @elseif($row->receive < 0) style="color:red" @endif>
                    {{ number_format($row->receive,2) }}
                </td>
                <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                    @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                    {{ number_format($row->receive-$row->debtor,2) }}
                </td>         
                <td align="right">{{ $row->repno }}</td> 
                <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                    @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                    {{ $row->days }} วัน
                </td> 
            </tr>                
            <?php $count++; ?>
            <?php $sum_income += $row->income ; ?>
            <?php $sum_rcpt_money += $row->rcpt_money ; ?>
            <?php $sum_kidney += $row->kidney ; ?> 
            <?php $sum_cr += $row->cr ; ?> 
            <?php $sum_anywhere += $row->anywhere ; ?> 
            <?php $sum_debtor += $row->debtor ; ?> 
            <?php $sum_receive += $row->receive ; ?>  
            @endforeach   
            <tr>
                <td align="right" colspan = "6"><strong>รวมค่ารักษาพยาบาลทั้งสิ้น &nbsp;</strong><br></td> 
                <td align="right"><strong>{{number_format($sum_income,2)}}&nbsp;</strong></td>  
                <td align="right"><strong>{{number_format($sum_rcpt_money,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_kidney,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_cr,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_anywhere,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_debtor,2)}}&nbsp;</strong></td>
                <td align="right"><strong>{{number_format($sum_receive,2)}}&nbsp;</strong></td> 
                <td align="right"><strong>{{number_format($sum_receive-$sum_debtor,2)}}&nbsp;</strong></td>
            </tr>          
        </table> 
    </div>
</div>    




