<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            @font-face {
                font-family: 'THSarabunNew';
                src: url('fonts/thsarabunnew-webfont.eot');
                src: url('fonts/thsarabunnew-webfont.eot?#iefix') format('embedded-opentype'),
                    url('fonts/thsarabunnew-webfont.woff') format('woff'),
                    url('fonts/thsarabunnew-webfont.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
            }        
            @font-face {
                font-family: 'THSarabunNew';
                src: url('fonts/thsarabunnew_bold-webfont.eot');
                src: url('fonts/thsarabunnew_bold-webfont.eot?#iefix') format('embedded-opentype'),
                    url('fonts/thsarabunnew_bold-webfont.woff') format('woff'),
                    url('fonts/thsarabunnew_bold-webfont.ttf') format('truetype');
                font-weight: bold;
                font-style: normal;
            } 
            @page {
                margin: 0cm 0cm;
            }
            header {
                position: fixed;
                font-family: "THSarabunNew";
                top: 0.5cm;
                left: 1.2cm;
                right: 1.2cm;          
                font-size: 11px;
                line-height: 1.0;  
                text-align: center; 
            }
            footer {
                position: fixed;
                font-family: "THSarabunNew";
                bottom: 0.5cm;
                left: 1.2cm;
                right: 1.2cm;          
                font-size: 12px;
                line-height: 0.95;               
            }
            body {
                font-family: "THSarabunNew";
                font-size: 11px;
                line-height: 1.0;  
                margin-top: 3.8cm;
                margin-bottom: 2.5cm;
                margin-left: 1.2cm;
                margin-right: 1.2cm;                     
            }
            table {
                border-collapse: collapse;
                width: 100%;
                font-size: 10.5px;
            }
            table, th, td {
                border: 1px solid black; 
            }   
            th {
                background-color: #f2f2f2;
                font-weight: bold;
                text-align: center;
                padding: 3px;
            }
            td {
                padding: 3px;
            }
        </style> 
    </head>
    <body>
        <header>
            <div>
                <strong>
                    <span style="font-size: 14px;">ใบแนบรายละเอียดการปรับปรุงยอดลูกหนี้แยกรายตัว</span><br>
                    หน่วยบริการ: {{$hospital_name}} ({{$hospital_code}}) <br>    
                    รหัสผังบัญชี 1102050101.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย <br>
                    วันที่ปรับยอด {{DateThai($start_date)}} ถึง {{DateThai($end_date)}} <br>
                </strong>
            </div>
        </header>

        <footer> 
            <table width="100%" style="border: none;">
                <tr style="border: none;">
                    <td width="33%" align="center" style="border: none;">
                        ลงชื่อ.......................................................ผู้จัดทำรายงาน<br>
                        (...................................................)<br>
                        ตำแหน่ง.......................................................<br>
                        วันที่........../........../..........
                    </td>
                    <td width="33%" align="center" style="border: none;">
                        ลงชื่อ.......................................................ผู้ตรวจสอบ<br>
                        (...................................................)<br>
                        ตำแหน่ง.......................................................<br>
                        วันที่........../........../..........
                    </td>
                    <td width="34%" align="center" style="border: none;">
                        ลงชื่อ.......................................................ผู้อนุมัติ<br>
                        (...................................................)<br>
                        ตำแหน่ง.......................................................<br>
                        วันที่........../........../..........
                    </td>
                </tr>
            </table>
        </footer>

        <main>
            <table>
                <thead>
                      <tr>
                        <th width="4%">ลำดับ</th>
                        <th width="11%">วันที่ปรับปรุง</th>
                        <th width="11%">วันที่บริการ</th>
                        <th width="8%">HN</th>
                        <th width="25%">ชื่อ-สกุล</th>
                        <th width="8%">ยอดลูกหนี้</th>
                        <th width="8%">ยอดชดเชย</th>
                        <th width="8%">ปรับเพิ่ม</th>
                        <th width="8%">ปรับลด</th>
                        <th width="19%">เหตุผลการปรับปรุง</th>
                    </tr>     
                </thead> 
                <tbody>
                    <?php $count = 1; ?>
                    <?php $sum_debtor = 0; $sum_receive = 0; $sum_adj_inc = 0; $sum_adj_dec = 0; ?>
                    @foreach($data as $row)                              
                    <tr>
                        <td align="center">{{$count}}</td> 
                        <td align="center" style="white-space: nowrap;">{{DateThai($row->adj_date)}}</td>
                        <td align="center" style="white-space: nowrap;">{{DateThai($row->vstdate)}}</td>
                        <td align="center">{{$row->hn}}</td>
                        <td align="left" style="white-space: nowrap;">{{$row->ptname}}</td>
                        <td align="right">{{number_format($row->debtor,2)}}</td>
                        <td align="right">{{number_format($row->receive,2)}}</td> 
                        <td align="right" style="color: purple;">{{number_format($row->adj_inc,2)}}</td>
                        <td align="right" style="color: blue;">{{number_format($row->adj_dec,2)}}</td>
                        <td align="left">{{$row->adj_note}}</td>
                    </tr>                
                    <?php 
                        $count++; 
                        $sum_debtor += $row->debtor; 
                        $sum_receive += $row->receive; 
                        $sum_adj_inc += $row->adj_inc;
                        $sum_adj_dec += $row->adj_dec;
                    ?>
                    @endforeach
                     <tr style="font-weight: bold; background-color: #f9f9f9;">
                        <td align="right" colspan="5">รวมทั้งสิ้น &nbsp;</td>   
                        <td align="right">{{number_format($sum_debtor,2)}}</td>
                        <td align="right">{{number_format($sum_receive,2)}}</td> 
                        <td align="right" style="color: purple;">{{number_format($sum_adj_inc,2)}}</td>
                        <td align="right" style="color: blue;">{{number_format($sum_adj_dec,2)}}</td>
                        <td></td>
                    </tr>          
                </tbody>
            </table> 
        </main>           
    </body>
</html>
