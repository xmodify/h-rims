<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หนังสือทวงถามหนี้ - {{ $debtor->ptname }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'TH Sarabun New', 'TH Sarabun PSK', 'Sarabun', 'Helvetica Neue', 'Arial', sans-serif;
            font-size: 16pt;
            line-height: 1.6;
            color: #000;
            padding: 40px;
            margin: 0;
            background-color: #fff;
        }
        .letter-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        .header-section {
            position: relative;
            text-align: center;
            margin-bottom: 20px;
        }
        .garuda-logo {
            height: 3cm;
            width: auto;
            margin: 0 auto 10px auto;
            display: block;
        }
        .hospital-address {
            text-align: right;
            font-size: 16pt;
            line-height: 1.4;
            margin-bottom: 20px;
        }
        .letter-meta {
            margin-bottom: 20px;
        }
        .letter-date {
            text-align: center;
            margin: 20px 0;
        }
        .letter-content {
            text-align: justify;
            text-justify: inter-word;
            margin-bottom: 20px;
            text-indent: 2.5cm;
        }
        .signature-section {
            float: right;
            width: 300px;
            text-align: center;
            margin-top: 40px;
        }
        @page {
            size: A4;
            margin: 0; /* Removes default browser headers and footers */
        }
        @media print {
            body {
                padding: 2.5cm 1.5cm 2cm 2.5cm; /* Standard Thai royal letter margins: Top 2.5cm, Right 1.5cm, Bottom 2.0cm, Left 2.5cm */
                margin: 0;
                font-size: 16pt;
            }
            .letter-container {
                width: 100%;
                padding: 0;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background-color: #0d6efd;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-print:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body>
    <div class="print-btn-container no-print">
        <button class="btn-print" onclick="window.print()">พิมพ์หนังสือนำส่ง / หนังสือทวงหนี้</button>
        <button class="btn-print" style="background-color: #6c757d;" onclick="window.close()">ปิดหน้าต่าง</button>
    </div>
    
    <div class="letter-container">
        <div class="header-section">
            <img class="garuda-logo" src="{{ asset('images/krut-3-cm.png') }}" alt="ตราครุฑ">
        </div>

        <div class="hospital-address">
            {{ $hospital_name }}<br>
            เลขที่เอกสาร: {{ $tracking->tracking_no ?: '.........................' }}
        </div>

        <div class="letter-date">
            วันที่ {{ DateThai($tracking->tracking_date) }}
        </div>

        <div class="letter-meta">
            <strong>เรื่อง:</strong> ขอแจ้งเตือนให้ชำระค่ารักษาพยาบาลค้างชำระ<br>
            <strong>เรียน:</strong> {{ $debtor->ptname }}
        </div>

        <div class="letter-content">
            ตามที่ ท่านได้เข้ามารับการตรวจรักษาพยาบาล ณ {{ $hospital_name }} เมื่อวันที่ {{ DateThai($debtor->dchdate) }} ด้วยสิทธิการรักษา {{ preg_replace('/^\d+\s*/', '', $debtor->pttype) }} โดยมีค่าบริการทางการแพทย์และค่ารักษาพยาบาลทั้งหมดรวมเป็นเงินทั้งสิ้น {{ number_format($debtor->income, 2) }} บาท ซึ่งมีส่วนที่ต้องชำระเอง/ค้างชำระตามสิทธิเป็นจำนวนเงิน <strong>{{ number_format($debtor->debtor, 2) }} บาท ({{ convert(number_format($debtor->debtor, 2, '.', '')) }})</strong> นั้น
        </div>

        <div class="letter-content">
            เนื่องจากขณะนี้เลยกำหนดระยะเวลาการชำระเงินมาแล้ว ทาง{{ $hospital_name }}จึงขอความร่วมมือจากท่านโปรดติดต่อชำระเงินค้างชำระจำนวนดังกล่าว ณ งานการเงินและบัญชี ของ{{ $hospital_name }} ภายใน 15 วัน นับจากวันที่ได้รับหนังสือฉบับนี้ หากท่านได้ชำระเรียบร้อยแล้วต้องขออภัยมา ณ ที่นี้ หรือมีข้อสงสัยประการใดกรุณาติดต่อสอบถามกับทางโรงพยาบาลโดยตรง โทร {{ $hospital_phone_finance }}
        </div>

        <div class="letter-content" style="text-indent: 0;">
            จึงเรียนมาเพื่อโปรดพิจารณาดำเนินการชำระหนี้ดังกล่าวโดยด่วน และขอขอบคุณมา ณ โอกาสนี้
        </div>

        <div class="signature-section">
            ขอแสดงความนับถือ<br><br>
            (......................................................)<br>
            ตำแหน่ง......................................................
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
