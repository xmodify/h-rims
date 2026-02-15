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
                margin-top:    3.5cm;
                margin-bottom: 3.5cm;
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
                        เนเธเธเธฃเธฒเธขเธเธฒเธเธชเธฃเธธเธเธเธฑเธเธเธตเธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒเธเธขเธฒเธเธฒเธฅเนเธขเธเธ•เธฒเธกเธเธฑเธเธเธฑเธเธเธต<br>
                        เธซเธเนเธงเธขเธเธฃเธดเธเธฒเธฃ: {{$hospital_name}} ({{$hospital_code}}) <br>                   
                        เธงเธฑเธเธ—เธตเน {{dateThaifromFull($start_date)}} เธ–เธถเธ {{dateThaifromFull($end_date)}} <br>
                    </p>
                </strong>
            </div>
        </header>

        <footer> 
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>เธเธนเนเธเธฑเธ”เธ—เธณเธฃเธฒเธขเธเธฒเธ</strong>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>เธฃเธฑเธเธฃเธญเธเธเนเธญเธกเธนเธฅเธ–เธนเธเธ•เนเธญเธ</strong>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>เธเธนเนเธเธฑเธเธ—เธถเธเธเธฑเธเธเธต</strong><br><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;เธฅเธเธเธทเนเธญ....................................เธเธนเนเธเธฑเธ”เธ—เธณเธฃเธฒเธขเธเธฒเธ
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธฅเธเธเธทเนเธญ....................................เธเธนเนเธ•เธฃเธงเธเธชเธญเธ&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธฅเธเธเธทเนเธญ....................................เธเธนเนเธเธฑเธเธ—เธถเธเธเธฑเธเธเธต<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(...................................................)
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            (...................................................)
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;   
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            (...................................................)<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;เธ•เธณเนเธซเธเนเธ.........................................................
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธ•เธณเนเธซเธเนเธ....................................................
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธ•เธณเนเธซเธเนเธ....................................................<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;เธงเธฑเธเธ—เธตเนเธฃเธฒเธขเธเธฒเธ..................................................
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธงเธฑเธเธ—เธตเนเธ•เธฃเธงเธเธชเธญเธ...........................................
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            เธงเธฑเธเธ—เธตเนเธเธฑเธเธ—เธถเธเธเธฑเธเธเธต........................................
        </footer>

        <main>
            <div class="container">
                <div class="row justify-content-center">            
                    <table width="100%" >
                        <thead>
                        <tr>
                            <td align="center" width="5%"><strong>เธฅเธณเธ”เธฑเธ</strong></td>
                            <td align="center" width="5%"><strong>เธฃเธซเธฑเธชเธเธฑเธเธเธฑเธเธเธต</strong></td>                   
                            <td align="center" width="35%"><strong>เธเธทเนเธญเธเธฑเธเธเธฑเธเธเธต</strong></td>
                            <td align="center" width="10%"><strong>เธเธณเธเธงเธ</strong></td>
                            <td align="center" width="15%"><strong>เธฅเธนเธเธซเธเธตเน</strong></td>
                            <td align="center" width="15%"><strong>เธเธ”เน€เธเธข</strong></td>
                            <td align="center" width="15%"><strong>เธเธฅเธ•เนเธฒเธ</strong></td>
                        </tr>     
                        </thead>     
                        <tr>            
                            <?php $sum_1102050101_103_debtor = 0 ; ?>
                            <?php $sum_1102050101_103_receive = 0 ; ?>
                            @foreach($_1102050101_103 as $row)
                            <td align="center">1</td>
                            <td align="right">1102050101.103</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธ•เธฃเธงเธเธชเธธเธเธ เธฒเธ เธซเธเนเธงเธขเธเธฒเธเธ เธฒเธเธฃเธฑเธ</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>

                            <?php $sum_1102050101_103_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_103_receive += $row->receive ; ?>
                            @endforeach   
                        </tr>    
                        <tr>
                            <?php $sum_1102050101_109_debtor = 0 ; ?>
                            <?php $sum_1102050101_109_receive = 0 ; ?>
                            @foreach($_1102050101_109 as $row)
                            <td align="center">2</td>
                            <td align="right">1102050101.109</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเน-เธฃเธฐเธเธเธเธเธดเธเธฑเธ•เธดเธเธฒเธฃเธเธธเธเน€เธเธดเธ</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td> 

                            <?php $sum_1102050101_109_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_109_receive += $row->receive ; ?>
                            @endforeach    
                        </tr>   
                        <tr>
                            <?php $sum_1102050101_201_debtor = 0 ; ?>
                            <?php $sum_1102050101_201_receive = 0 ; ?>
                            @foreach($_1102050101_201 as $row)
                            <td align="center">3</td>
                            <td align="right">1102050101.201</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-OP เนเธ CUP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  

                            <?php $sum_1102050101_201_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_201_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_203_debtor = 0 ; ?>
                            <?php $sum_1102050101_203_receive = 0 ; ?>
                            @foreach($_1102050101_203 as $row)
                            <td align="center">4</td>
                            <td align="right">1102050101.203</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-OP เธเธญเธ CUP (เนเธเธเธฑเธเธซเธงเธฑเธ”เธชเธฑเธเธเธฑเธ” เธชเธ.)</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  

                            <?php $sum_1102050101_203_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_203_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_209_debtor = 0 ; ?>
                            <?php $sum_1102050101_209_receive = 0 ; ?>
                            @foreach($_1102050101_209 as $row)
                            <td align="center">5</td>
                            <td align="right">1102050101.209</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธ”เนเธฒเธเธเธฒเธฃเธชเธฃเนเธฒเธเน€เธชเธฃเธดเธกเธชเธธเธเธ เธฒเธเนเธฅเธฐเธเนเธญเธเธเธฑเธเนเธฃเธ (P&P)</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  

                            <?php $sum_1102050101_209_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_209_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_216_debtor = 0 ; ?>
                            <?php $sum_1102050101_216_receive = 0 ; ?>
                            @foreach($_1102050101_216 as $row)
                            <td align="center">6</td>
                            <td align="right">1102050101.216</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-OP เธเธฃเธดเธเธฒเธฃเน€เธเธเธฒเธฐ (CR)</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  

                            <?php $sum_1102050101_216_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_216_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_301_debtor = 0 ; ?>
                            <?php $sum_1102050101_301_receive = 0 ; ?>
                            @foreach($_1102050101_301 as $row)
                            <td align="center">7</td>
                            <td align="right">1102050101.301</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก OP-เน€เธเธฃเธทเธญเธเนเธฒเธข</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
     
                            <?php $sum_1102050101_301_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_301_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_303_debtor = 0 ; ?>
                            <?php $sum_1102050101_303_receive = 0 ; ?>
                            @foreach($_1102050101_303 as $row)
                            <td align="center">8</td>
                            <td align="right">1102050101.303</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก OP-เธเธญเธเน€เธเธฃเธทเธญเธเนเธฒเธข เธชเธฑเธเธเธฑเธ” เธชเธ.เธชเธ.</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
           
                            <?php $sum_1102050101_303_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_303_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_307_debtor = 0 ; ?>
                            <?php $sum_1102050101_307_receive = 0 ; ?>
                            @foreach($_1102050101_307 as $row)
                            <td align="center">9</td>
                            <td align="right">1102050101.307</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก-เธเธญเธเธ—เธธเธเธ—เธ”เนเธ—เธ</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
    
                            <?php $sum_1102050101_307_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_307_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_309_debtor = 0 ; ?>
                            <?php $sum_1102050101_309_receive = 0 ; ?>
                            @foreach($_1102050101_309 as $row)
                            <td align="center">10</td>
                            <td align="right">1102050101.309</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก-เธเนเธฒเนเธเนเธเนเธฒเธขเธชเธนเธ/เธญเธธเธเธฑเธ•เธดเน€เธซเธ•เธธ/เธเธธเธเน€เธเธดเธ OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
    
                            <?php $sum_1102050101_309_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_309_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_401_debtor = 0 ; ?>
                            <?php $sum_1102050101_401_receive = 0 ; ?>
                            @foreach($_1102050101_401 as $row)
                            <td align="center">11</td>
                            <td align="right">1102050101.401</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธเธเธฃเธกเธเธฑเธเธเธตเธเธฅเธฒเธ OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
      
                            <?php $sum_1102050101_401_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_401_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_501_debtor = 0 ; ?>
                            <?php $sum_1102050101_501_receive = 0 ; ?>
                            @foreach($_1102050101_501 as $row)
                            <td align="center">12</td>
                            <td align="right">1102050101.501</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
           
                            <?php $sum_1102050101_501_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_501_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_503_debtor = 0 ; ?>
                            <?php $sum_1102050101_503_receive = 0 ; ?>
                            @foreach($_1102050101_503 as $row)
                            <td align="center">13</td>
                            <td align="right">1102050101.503</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง OP เธเธญเธ CUP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
           
                            <?php $sum_1102050101_503_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_503_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>
                        <tr>
                            <?php $sum_1102050101_701_debtor = 0 ; ?>
                            <?php $sum_1102050101_701_receive = 0 ; ?>
                            @foreach($_1102050101_701 as $row)
                            <td align="center">14</td>
                            <td align="right">1102050101.701</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธธเธเธเธฅเธ—เธตเนเธกเธตเธเธฑเธเธซเธฒเธชเธ–เธฒเธเธฐเนเธฅเธฐเธชเธดเธ—เธเธด OP เนเธ CUP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
           
                            <?php $sum_1102050101_701_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_701_receive += $row->receive ; ?>
                            @endforeach     
                        </tr> 
                        <tr>
                            <?php $sum_1102050101_702_debtor = 0 ; ?>
                            <?php $sum_1102050101_702_receive = 0 ; ?>
                            @foreach($_1102050101_702 as $row)
                            <td align="center">15</td>
                            <td align="right">1102050101.702</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธธเธเธเธฅเธ—เธตเนเธกเธตเธเธฑเธเธซเธฒเธชเธ–เธฒเธเธฐเนเธฅเธฐเธชเธดเธ—เธเธด OP เธเธญเธ CUP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
           
                            <?php $sum_1102050101_702_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_702_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>   
                        <tr>
                            <?php $sum_1102050101_703_debtor = 0 ; ?>
                            <?php $sum_1102050101_703_receive = 0 ; ?>
                            @foreach($_1102050101_703 as $row)
                            <td align="center">16</td>
                            <td align="right">1102050101.703</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธธเธเธเธฅเธ—เธตเนเธกเธตเธเธฑเธเธซเธฒเธชเธ–เธฒเธเธฐเนเธฅเธฐเธชเธดเธ—เธเธด เน€เธเธดเธเธเธฒเธเธชเนเธงเธเธเธฅเธฒเธ OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
           
                            <?php $sum_1102050101_703_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_703_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_106_debtor = 0 ; ?>
                            <?php $sum_1102050102_106_receive = 0 ; ?>
                            @foreach($_1102050102_106 as $row)
                            <td align="center">17</td>
                            <td align="right">1102050102.106</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเนเธฒเธฃเธฐเน€เธเธดเธ OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
    
                            <?php $sum_1102050102_106_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_106_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_108_debtor = 0 ; ?>
                            <?php $sum_1102050102_108_receive = 0 ; ?>
                            @foreach($_1102050102_108 as $row)
                            <td align="center">18</td>
                            <td align="right">1102050102.108</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธ•เนเธเธชเธฑเธเธเธฑเธ” OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
       
                            <?php $sum_1102050102_108_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_108_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_110_debtor = 0 ; ?>
                            <?php $sum_1102050102_110_receive = 0 ; ?>
                            @foreach($_1102050102_110 as $row)
                            <td align="center">19</td>
                            <td align="right">1102050102.110</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธเธซเธเนเธงเธขเธเธฒเธเธญเธทเนเธ OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
       
                            <?php $sum_1102050102_110_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_110_receive += $row->receive ; ?>
                            @endforeach     
                        </tr> 
                        <tr>
                            <?php $sum_1102050102_602_debtor = 0 ; ?>
                            <?php $sum_1102050102_602_receive = 0 ; ?>
                            @foreach($_1102050102_602 as $row)
                            <td align="center">20</td>
                            <td align="right">1102050102.602</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธ.เธฃเธ– OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
         
                            <?php $sum_1102050102_602_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_602_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_801_debtor = 0 ; ?>
                            <?php $sum_1102050102_801_receive = 0 ; ?>
                            @foreach($_1102050102_801 as $row)
                            <td align="center">21</td>
                            <td align="right">1102050102.801</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
        
                            <?php $sum_1102050102_801_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_801_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_803_debtor = 0 ; ?>
                            <?php $sum_1102050102_803_receive = 0 ; ?>
                            @foreach($_1102050102_803 as $row)
                            <td align="center">22</td>
                            <td align="right">1102050102.803</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.เธฃเธนเธเนเธเธเธเธดเน€เธจเธฉ OP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
       
                            <?php $sum_1102050102_803_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_803_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>                         
                        <tr>
                            <?php $sum_1102050101_202_debtor = 0 ; ?>
                            <?php $sum_1102050101_202_receive = 0 ; ?>
                            @foreach($_1102050101_202 as $row)
                            <td align="center">23</td>
                            <td align="right">1102050101.202</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  

                            <?php $sum_1102050101_202_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_202_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_217_debtor = 0 ; ?>
                            <?php $sum_1102050101_217_receive = 0 ; ?>
                            @foreach($_1102050101_217 as $row)
                            <td align="center">24</td>
                            <td align="right">1102050101.217</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-IP เธเธฃเธดเธเธฒเธฃเน€เธเธเธฒเธฐ (CR)</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
      
                            <?php $sum_1102050101_217_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_217_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_302_debtor = 0 ; ?>
                            <?php $sum_1102050101_302_receive = 0 ; ?>
                            @foreach($_1102050101_302 as $row)
                            <td align="center">25</td>
                            <td align="right">1102050101.302</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก IP เน€เธเธฃเธทเธญเธเนเธฒเธข</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
       
                            <?php $sum_1102050101_302_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_302_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_304_debtor = 0 ; ?>
                            <?php $sum_1102050101_304_receive = 0 ; ?>
                            @foreach($_1102050101_304 as $row)
                            <td align="center">26</td>
                            <td align="right">1102050101.304</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก IP เธเธญเธเน€เธเธฃเธทเธญเธเนเธฒเธข เธชเธฑเธเธเธฑเธ” เธชเธ.เธชเธ.</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
     
                            <?php $sum_1102050101_304_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_304_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_308_debtor = 0 ; ?>
                            <?php $sum_1102050101_308_receive = 0 ; ?>
                            @foreach($_1102050101_308 as $row)
                            <td align="center">27</td>
                            <td align="right">1102050101.308</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก 72 เธเธฑเนเธงเนเธกเธเนเธฃเธ</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
       
                            <?php $sum_1102050101_308_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_308_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_310_debtor = 0 ; ?>
                            <?php $sum_1102050101_310_receive = 0 ; ?>
                            @foreach($_1102050101_310 as $row)
                            <td align="center">28</td>
                            <td align="right">1102050101.310</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก เธเนเธฒเนเธเนเธเนเธฒเธขเธชเธนเธ IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
   
                            <?php $sum_1102050101_310_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_310_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_402_debtor = 0 ; ?>
                            <?php $sum_1102050101_402_receive = 0 ; ?>
                            @foreach($_1102050101_402 as $row)
                            <td align="center">29</td>
                            <td align="right">1102050101.402</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ-เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธเธฃเธกเธเธฑเธเธเธตเธเธฅเธฒเธ IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
      
                            <?php $sum_1102050101_402_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_402_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_502_debtor = 0 ; ?>
                            <?php $sum_1102050101_502_receive = 0 ; ?>
                            @foreach($_1102050101_502 as $row)
                            <td align="center">30</td>
                            <td align="right">1102050101.502</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
          
                            <?php $sum_1102050101_502_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_502_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050101_504_debtor = 0 ; ?>
                            <?php $sum_1102050101_504_receive = 0 ; ?>
                            @foreach($_1102050101_504 as $row)
                            <td align="center">31</td>
                            <td align="right">1102050101.504</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง IP เธเธญเธ CUP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
          
                            <?php $sum_1102050101_504_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_504_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>
                        <tr>
                            <?php $sum_1102050101_704_debtor = 0 ; ?>
                            <?php $sum_1102050101_704_receive = 0 ; ?>
                            @foreach($_1102050101_704 as $row)
                            <td align="center">32</td>
                            <td align="right">1102050101.704</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธธเธเธเธฅเธ—เธตเนเธกเธตเธเธฑเธเธซเธฒเธชเธ–เธฒเธเธฐเนเธฅเธฐเธชเธดเธ—เธเธด เน€เธเธดเธเธเธฒเธเธชเนเธงเธเธเธฅเธฒเธ IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
    
                            <?php $sum_1102050101_704_debtor += $row->debtor ; ?>
                            <?php $sum_1102050101_704_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_107_debtor = 0 ; ?>
                            <?php $sum_1102050102_107_receive = 0 ; ?>
                            @foreach($_1102050102_107 as $row)
                            <td align="center">33</td>
                            <td align="right">1102050102.107</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเนเธฒเธฃเธฐเน€เธเธดเธ IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
     
                            <?php $sum_1102050102_107_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_107_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_109_debtor = 0 ; ?>
                            <?php $sum_1102050102_109_receive = 0 ; ?>
                            @foreach($_1102050102_109 as $row)
                            <td align="center">34</td>
                            <td align="right">1102050102.109</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธ•เนเธเธชเธฑเธเธเธฑเธ” IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
     
                            <?php $sum_1102050102_109_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_109_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_111_debtor = 0 ; ?>
                            <?php $sum_1102050102_111_receive = 0 ; ?>
                            @foreach($_1102050102_111 as $row)
                            <td align="center">35</td>
                            <td align="right">1102050102.111</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธเธซเธเนเธงเธขเธเธฒเธเธญเธทเนเธ IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
     
                            <?php $sum_1102050102_111_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_111_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_603_debtor = 0 ; ?>
                            <?php $sum_1102050102_603_receive = 0 ; ?>
                            @foreach($_1102050102_603 as $row)
                            <td align="center">36</td>
                            <td align="right">1102050102.603</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธ.เธฃเธ– IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
     
                            <?php $sum_1102050102_603_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_603_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_802_debtor = 0 ; ?>
                            <?php $sum_1102050102_802_receive = 0 ; ?>
                            @foreach($_1102050102_802 as $row)
                            <td align="center">37</td>
                            <td align="right">1102050102.802</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
      
                            <?php $sum_1102050102_802_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_802_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>  
                        <tr>
                            <?php $sum_1102050102_804_debtor = 0 ; ?>
                            <?php $sum_1102050102_804_receive = 0 ; ?>
                            @foreach($_1102050102_804 as $row)
                            <td align="center">38</td>
                            <td align="right">1102050102.804</td>
                            <td class="text-left">เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.เธฃเธนเธเนเธเธเธเธดเน€เธจเธฉ IP</td>
                            <td align="right">{{number_format($row->anvn)}}&nbsp;</td>
                            <td align="right">{{number_format($row->debtor,2)}}&nbsp;</td>
                            <td align="right">{{number_format($row->receive,2)}}&nbsp;</td> 
                            <td align="right">{{number_format($row->receive-$row->debtor,2)}}&nbsp;</td>  
  
                            <?php $sum_1102050102_804_debtor += $row->debtor ; ?>
                            <?php $sum_1102050102_804_receive += $row->receive ; ?>
                            @endforeach     
                        </tr>
                        <tr> 
                            <td align="right" colspan = "4"><strong>เธฃเธงเธกเธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒเธเธขเธฒเธเธฒเธฅเธเธนเนเธเนเธงเธขเธเธญเธ OPD &nbsp;</strong><br></td>   
                            <td align="right" class="text-primary"><strong>{{number_format($sum_1102050101_103_debtor+$sum_1102050101_109_debtor+$sum_1102050101_201_debtor
                                +$sum_1102050101_203_debtor+$sum_1102050101_209_debtor+$sum_1102050101_216_debtor+$sum_1102050101_301_debtor+$sum_1102050101_303_debtor
                                +$sum_1102050101_307_debtor+$sum_1102050101_309_debtor+$sum_1102050101_401_debtor+$sum_1102050101_501_debtor+$sum_1102050101_503_debtor
                                +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_debtor+$sum_1102050102_106_debtor+$sum_1102050102_108_debtor
                                +$sum_1102050102_110_debtor+$sum_1102050102_602_debtor+$sum_1102050102_801_debtor+$sum_1102050102_803_debtor,2)}}&nbsp;</strong>
                            </td>
                            <td align="right" class="text-success"><strong>{{number_format($sum_1102050101_103_receive+$sum_1102050101_109_receive+$sum_1102050101_201_receive
                                +$sum_1102050101_203_receive+$sum_1102050101_209_receive+$sum_1102050101_216_receive+$sum_1102050101_301_receive+$sum_1102050101_303_receive
                                +$sum_1102050101_307_receive+$sum_1102050101_309_receive+$sum_1102050101_401_receive+$sum_1102050101_501_receive+$sum_1102050101_503_debtor
                                +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_receive+$sum_1102050102_106_receive+$sum_1102050102_108_receive
                                +$sum_1102050102_110_receive+$sum_1102050102_602_receive+$sum_1102050102_801_receive+$sum_1102050102_803_receive,2)}}&nbsp;</strong>
                            </td> 
                            <td align="right" style="color:red"><strong>{{number_format(($sum_1102050101_103_receive+$sum_1102050101_109_receive+$sum_1102050101_201_receive
                                +$sum_1102050101_203_receive+$sum_1102050101_209_receive+$sum_1102050101_216_receive+$sum_1102050101_301_receive+$sum_1102050101_303_receive
                                +$sum_1102050101_307_receive+$sum_1102050101_309_receive+$sum_1102050101_401_receive+$sum_1102050101_501_receive+$sum_1102050101_503_debtor
                                +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_receive+$sum_1102050102_106_receive+$sum_1102050102_108_receive
                                +$sum_1102050102_110_receive+$sum_1102050102_602_receive+$sum_1102050102_801_receive+$sum_1102050102_803_receive)
                                -($sum_1102050101_103_debtor+$sum_1102050101_109_debtor+$sum_1102050101_201_debtor+$sum_1102050101_203_debtor+$sum_1102050101_209_debtor
                                +$sum_1102050101_216_debtor+$sum_1102050101_301_debtor+$sum_1102050101_303_debtor+$sum_1102050101_307_debtor+$sum_1102050101_309_debtor
                                +$sum_1102050101_401_debtor+$sum_1102050101_501_debtor+$sum_1102050101_503_debtor+$sum_1102050101_701_debtor+$sum_1102050101_702_debtor
                                +$sum_1102050101_703_debtor+$sum_1102050102_106_debtor+$sum_1102050102_108_debtor+$sum_1102050102_110_debtor+$sum_1102050102_602_debtor
                                +$sum_1102050102_801_debtor+$sum_1102050102_803_debtor),2)}}&nbsp;</strong>
                            </td>                       
                        </tr>   
                        <tr> 
                            <td align="right" colspan = "4"><strong>เธฃเธงเธกเธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒเธเธขเธฒเธเธฒเธฅเธเธนเนเธเนเธงเธขเนเธ IPD &nbsp;</strong><br></td>   
                            <td align="right" class="text-primary"><strong>{{number_format($sum_1102050101_202_debtor+$sum_1102050101_217_debtor
                                +$sum_1102050101_302_debtor+$sum_1102050101_304_debtor+$sum_1102050101_308_debtor+$sum_1102050101_310_debtor
                                +$sum_1102050101_402_debtor+$sum_1102050101_502_debtor+$sum_1102050101_504_debtor+$sum_1102050101_704_debtor
                                +$sum_1102050102_107_debtor+$sum_1102050102_109_debtor+$sum_1102050102_111_debtor+$sum_1102050102_603_debtor
                                +$sum_1102050102_802_debtor+$sum_1102050102_804_debtor,2)}}&nbsp;</strong>
                            </td>
                            <td align="right" class="text-success"><strong>{{number_format($sum_1102050101_202_receive+$sum_1102050101_217_receive
                                +$sum_1102050101_302_receive+$sum_1102050101_304_receive+$sum_1102050101_308_receive+$sum_1102050101_310_receive
                                +$sum_1102050101_402_receive+$sum_1102050101_502_receive+$sum_1102050101_504_receive+$sum_1102050101_704_receive
                                +$sum_1102050102_107_receive+$sum_1102050102_109_receive+$sum_1102050102_111_receive+$sum_1102050102_603_receive
                                +$sum_1102050102_802_receive+$sum_1102050102_804_receive,2)}}&nbsp;</strong>
                            </td> 
                            <td align="right" style="color:red"><strong>{{number_format(($sum_1102050101_202_receive+$sum_1102050101_217_receive
                                +$sum_1102050101_302_receive+$sum_1102050101_304_receive+$sum_1102050101_308_receive+$sum_1102050101_310_receive
                                +$sum_1102050101_402_receive+$sum_1102050101_502_receive+$sum_1102050101_504_receive+$sum_1102050101_704_receive
                                +$sum_1102050102_107_receive+$sum_1102050102_109_receive+$sum_1102050102_111_receive+$sum_1102050102_603_receive
                                +$sum_1102050102_802_receive+$sum_1102050102_804_receive)
                                -($sum_1102050101_202_debtor+$sum_1102050101_217_debtor+$sum_1102050101_302_debtor+$sum_1102050101_304_debtor
                                +$sum_1102050101_308_debtor+$sum_1102050101_310_debtor+$sum_1102050101_402_debtor+$sum_1102050101_502_debtor
                                +$sum_1102050101_504_debtor+$sum_1102050101_704_debtor+$sum_1102050102_107_debtor+$sum_1102050102_109_debtor
                                +$sum_1102050102_111_debtor+$sum_1102050102_603_debtor+$sum_1102050102_802_debtor+$sum_1102050102_804_debtor),2)}}&nbsp;</strong>
                            </td>                       
                        </tr> 
                        <tr>
                            <td colspan = "7">
                                <br>
                            </td>
                        </tr>
                        <tr> 
                            <td align="right" colspan = "4"><strong>เธฃเธงเธกเธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒเธเธขเธฒเธเธฒเธฅเธ—เธฑเนเธเธซเธกเธ” &nbsp;</strong><br></td>   
                            <td align="right" class="text-primary"><strong>{{number_format($sum_1102050101_103_debtor+$sum_1102050101_109_debtor+$sum_1102050101_201_debtor
                                +$sum_1102050101_203_debtor+$sum_1102050101_209_debtor+$sum_1102050101_216_debtor+$sum_1102050101_301_debtor+$sum_1102050101_303_debtor
                                +$sum_1102050101_307_debtor+$sum_1102050101_309_debtor+$sum_1102050101_401_debtor+$sum_1102050101_501_debtor+$sum_1102050101_503_debtor
                                +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_debtor+$sum_1102050102_106_debtor+$sum_1102050102_108_debtor
                                +$sum_1102050102_110_debtor+$sum_1102050102_602_debtor+$sum_1102050102_801_debtor+$sum_1102050102_803_debtor+$sum_1102050101_202_debtor
                                +$sum_1102050101_217_debtor+$sum_1102050101_302_debtor+$sum_1102050101_304_debtor+$sum_1102050101_308_debtor+$sum_1102050101_310_debtor
                                +$sum_1102050101_402_debtor+$sum_1102050101_502_debtor+$sum_1102050101_504_debtor+$sum_1102050101_704_debtor+$sum_1102050102_107_debtor
                                +$sum_1102050102_109_debtor+$sum_1102050102_111_debtor+$sum_1102050102_603_debtor+$sum_1102050102_802_debtor+$sum_1102050102_804_debtor,2)}}&nbsp;</strong>
                            </td>
                            <td align="right" class="text-success"><strong>{{number_format($sum_1102050101_103_receive+$sum_1102050101_109_receive+$sum_1102050101_201_receive
                                +$sum_1102050101_203_receive+$sum_1102050101_209_receive+$sum_1102050101_216_receive+$sum_1102050101_301_receive+$sum_1102050101_303_receive
                                +$sum_1102050101_307_receive+$sum_1102050101_309_receive+$sum_1102050101_401_receive+$sum_1102050101_501_receive+$sum_1102050101_503_debtor
                                +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_receive+$sum_1102050102_106_receive+$sum_1102050102_108_receive
                                +$sum_1102050102_110_receive+$sum_1102050102_602_receive+$sum_1102050102_801_receive+$sum_1102050102_803_receive+$sum_1102050101_202_receive
                                +$sum_1102050101_217_receive+$sum_1102050101_302_receive+$sum_1102050101_304_receive+$sum_1102050101_308_receive+$sum_1102050101_310_receive
                                +$sum_1102050101_402_receive+$sum_1102050101_502_receive+$sum_1102050101_504_receive+$sum_1102050101_704_receive+$sum_1102050102_107_receive
                                +$sum_1102050102_109_receive+$sum_1102050102_111_receive+$sum_1102050102_603_receive+$sum_1102050102_802_receive+$sum_1102050102_804_receive,2)}}&nbsp;</strong>
                            </td> 
                            <td align="right" style="color:red"><strong>{{number_format(($sum_1102050101_103_receive+$sum_1102050101_109_receive+$sum_1102050101_201_receive
                                +$sum_1102050101_203_receive+$sum_1102050101_209_receive+$sum_1102050101_216_receive+$sum_1102050101_301_receive+$sum_1102050101_303_receive
                                +$sum_1102050101_307_receive+$sum_1102050101_309_receive+$sum_1102050101_401_receive+$sum_1102050101_501_receive+$sum_1102050101_503_debtor
                                +$sum_1102050101_701_debtor+$sum_1102050101_702_debtor+$sum_1102050101_703_receive+$sum_1102050102_106_receive+$sum_1102050102_108_receive
                                +$sum_1102050102_110_receive+$sum_1102050102_602_receive+$sum_1102050102_801_receive+$sum_1102050102_803_receive+$sum_1102050101_202_receive
                                +$sum_1102050101_217_receive+$sum_1102050101_302_receive+$sum_1102050101_304_receive+$sum_1102050101_308_receive+$sum_1102050101_310_receive
                                +$sum_1102050101_402_receive+$sum_1102050101_502_receive+$sum_1102050101_504_receive+$sum_1102050101_704_receive+$sum_1102050102_107_receive
                                +$sum_1102050102_109_receive+$sum_1102050102_111_receive+$sum_1102050102_603_receive+$sum_1102050102_802_receive+$sum_1102050102_804_receive)
                                -($sum_1102050101_103_debtor+$sum_1102050101_109_debtor+$sum_1102050101_201_debtor+$sum_1102050101_203_debtor+$sum_1102050101_209_debtor
                                +$sum_1102050101_216_debtor+$sum_1102050101_301_debtor+$sum_1102050101_303_debtor+$sum_1102050101_307_debtor+$sum_1102050101_309_debtor
                                +$sum_1102050101_401_debtor+$sum_1102050101_501_debtor+$sum_1102050101_503_debtor+$sum_1102050101_701_debtor+$sum_1102050101_702_debtor
                                +$sum_1102050101_703_debtor+$sum_1102050102_106_debtor+$sum_1102050102_108_debtor+$sum_1102050102_110_debtor+$sum_1102050102_602_debtor
                                +$sum_1102050102_801_debtor+$sum_1102050102_803_debtor+$sum_1102050101_202_debtor+$sum_1102050101_217_debtor+$sum_1102050101_302_debtor
                                +$sum_1102050101_304_debtor+$sum_1102050101_308_debtor+$sum_1102050101_310_debtor+$sum_1102050101_402_debtor+$sum_1102050101_502_debtor
                                +$sum_1102050101_504_debtor+$sum_1102050101_704_debtor+$sum_1102050102_107_debtor+$sum_1102050102_109_debtor+$sum_1102050102_111_debtor
                                +$sum_1102050102_603_debtor+$sum_1102050102_802_debtor+$sum_1102050102_804_debtor),2)}}&nbsp;</strong>
                            </td>                       
                        </tr> 
                    </table> 
                </div>
            </div> 
        </main>    
    </body>
</html>



