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
                src: url('fonts/thsarabunnew_bolditalic-webfont.eot');
                src: url('fonts/thsarabunnew_bolditalic-webfont.eot?#iefix') format('embedded-opentype'),
                    url('fonts/thsarabunnew_bolditalic-webfont.woff') format('woff'),
                    url('fonts/thsarabunnew_bolditalic-webfont.ttf') format('truetype');
                font-weight: bold;
                font-style: italic;
            }
            @font-face {
                font-family: 'THSarabunNew';
                src: url('fonts/thsarabunnew_italic-webfont.eot');
                src: url('fonts/thsarabunnew_italic-webfont.eot?#iefix') format('embedded-opentype'),
                    url('fonts/thsarabunnew_italic-webfont.woff') format('woff'),
                    url('fonts/thsarabunnew_italic-webfont.ttf') format('truetype');
                font-weight: normal;
                font-style: italic;
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
                top: 1cm;
                left: 2cm;
                right: 1cm;          
                font-size: 13px;
                line-height: 0.75;  
                text-align: center; 
            }
            footer  {
                position: fixed;
                font-family: "THSarabunNew";
                bottom: 0.2cm;
                left: 2cm;
                right: 1cm;          
                font-size: 12px;
                line-height: 0.75;               
            }
            body {
                /* font-family: 'THSarabunNew', sans-serif;
                    font-size: 13px;
                line-height: 0.9;  
                margin-top:    0.2cm;
                margin-bottom: 0.2cm;
                margin-left:   1cm;
                margin-right:  1cm;  */
                font-family: "THSarabunNew";
                font-size: 12px;
                line-height: 0.75;  
                margin-top:    4cm;
                margin-bottom: 4cm;
                margin-left:   2cm;
                margin-right:  1cm;                     
            }
            #watermark {     
                position: fixed;
                        bottom:   0px;
                        left:     0px;                   
                        width:    29.5cm;
                        height:   21cm;
                        z-index:  -1000;
            }
            table,td {
                border: 1px solid rgb(5, 5, 5); 
                }   
                .text-pedding{
                /* padding-left:10px;
                padding-right:10px; */
                }                     
                table{
                    border-collapse: collapse;  //เธเธฃเธญเธเธ”เนเธฒเธเนเธเธซเธฒเธขเนเธ
                }
                table.one{
                border: 1px solid rgb(5, 5, 5);
                /* height: 800px; */
                /* padding: 15px; */
                }
                td {
                    margin: .2rem;
                /* height: 3px; */
                /* padding: 5px; */
                /* text-align: left; */
                }
                td.o{
                    border: 1px solid rgb(5, 5, 5); 
                    font-family: "THSarabunNew";
                    font-size: 12px;
                }
                td.b{
                    border: 1px solid rgb(5, 5, 5); 
                }
                td.d{
                    border: 1px solid rgb(5, 5, 5); 
                    height: 170px;
                }
                td.e{
                    border: 1px solid rgb(5, 5, 5);
                    
                }
                td.h{
                    border: 1px solid rgb(5, 5, 5); 
                    height: 10px;
                }
                .page-break {
                    page-break-after: always;
                } 
                
                input {
                    margin: .3rem;
                }
                .tsm{
                    font-family: "THSarabunNew";
                    font-size: 11px;
                }
                .tss{
                    font-family: "THSarabunNew";
                    font-size: 10px;
                }   
        </style> 
    </head>
    <body>
        <header>
            <div>
                <strong>
                    <p align=center>
                        เนเธเธเธฃเธฒเธขเธเธฒเธเธเธฑเธเธเธตเธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒเธเธขเธฒเธเธฒเธฅเนเธขเธเธ•เธฒเธกเธงเธฑเธเธ—เธตเนเธฃเธฑเธเธเธฃเธดเธเธฒเธฃ<br>
                        เธซเธเนเธงเธขเธเธฃเธดเธเธฒเธฃ: {{$hospital_name}} ({{$hospital_code}}) <br>
                        เธฃเธซเธฑเธชเธเธฑเธเธเธฑเธเธเธต 1102050102.804-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.เธฃเธนเธเนเธเธเธเธดเน€เธจเธฉ IP<br>
                        เธงเธฑเธเธ—เธตเน {{dateThaifromFull($start_date)}} เธ–เธถเธ {{dateThaifromFull($end_date)}} <br>
                    </p>
                </strong>
            </div>
        </header>

        <footer> 
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>เธเธนเนเธเธฑเธ”เธ—เธณเธฃเธฒเธขเธเธฒเธ</strong>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>เธฃเธฑเธเธฃเธญเธเธเนเธญเธกเธนเธฅเธ–เธนเธเธ•เนเธญเธ</strong>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>เธเธนเนเธเธฑเธเธ—เธถเธเธเธฑเธเธเธต</strong><br><br><br>
            เธฅเธเธเธทเนเธญ....................................เธเธนเนเธเธฑเธ”เธ—เธณเธฃเธฒเธขเธเธฒเธ&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธฅเธเธเธทเนเธญ....................................เธเธนเนเธ•เธฃเธงเธเธชเธญเธ&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธฅเธเธเธทเนเธญ....................................เธเธนเนเธเธฑเธเธ—เธถเธเธเธฑเธเธเธต<br>
            (...................................................)
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            (...................................................)
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;            
            (...................................................)<br>
            เธ•เธณเนเธซเธเนเธ.......................................................
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธ•เธณเนเธซเธเนเธ..................................................
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธ•เธณเนเธซเธเนเธ....................................................<br>
            เธงเธฑเธเธ—เธตเนเธฃเธฒเธขเธเธฒเธ.................................................
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธงเธฑเธเธ—เธตเนเธ•เธฃเธงเธเธชเธญเธ........................................
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธงเธฑเธเธ—เธตเนเธเธฑเธเธ—เธถเธเธเธฑเธเธเธต........................................
        </footer>

        <main>
            <div class="container">
                <div class="row justify-content-center">            
                    <table width="100%" >
                        <thead>
                        <tr>
                            <td align="center" width="10%"><strong>เธฅเธณเธ”เธฑเธ</strong></td>
                            <td align="center" width="20%"><strong>เธงเธฑเธเธ—เธตเน</strong></td>                   
                            <td align="center" width="10%"><strong>เธเธณเธเธงเธ</strong></td>
                            <td align="center" width="20%"><strong>เธฅเธนเธเธซเธเธตเน</strong></td>
                            <td align="center" width="20%"><strong>เธเธ”เน€เธเธข</strong></td>
                            <td align="center" width="20%"><strong>เธเธฅเธ•เนเธฒเธ</strong></td>
                        </tr>     
                        </thead> 
                        <?php $count = 1 ; ?>
                        <?php $sum_anvn = 0 ; ?>
                        <?php $sum_debtor = 0 ; ?>
                        <?php $sum_receive = 0 ; ?>
                        @foreach($debtor as $row)          
                        <tr>
                            <td align="center">{{$count}}</td> 
                            <td align="center">{{DateThai($row->vstdate)}}</td>                   
                            <td align="center">{{number_format($row->anvn)}}</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>              
                        </tr>                
                        <?php $count++; ?>
                        <?php $sum_anvn += $row->anvn ; ?>
                        <?php $sum_debtor += $row->debtor ; ?>
                        <?php $sum_receive += $row->receive ; ?>
                        @endforeach   
                        <tr>
                            <td align="right" colspan = "2"><strong>เธฃเธงเธก &nbsp;</strong><br></td>   
                            <td align="center"><strong>{{number_format($sum_anvn)}}</strong></td>
                            <td align="right"><strong>{{number_format($sum_debtor,2)}}&nbsp;</strong></td>
                            <td align="right"><strong>{{number_format($sum_receive,2)}}&nbsp;</strong></td> 
                            <td align="right"><strong>{{number_format($sum_receive-$sum_debtor,2)}}&nbsp;</strong></td>              
                        </tr>          
                    </table> 
                </div>
            </div> 
        </main>           
    </body>
</html>



