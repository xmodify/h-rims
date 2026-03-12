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
            top: 0.5cm;
            left: 2cm;  /* ปรับให้ตรงกับ body */
            right: 1.5cm;
            height: 3cm;
            text-align: center;
            font-family: "THSarabunNew";
        }

        footer {
            position: fixed;
            bottom: 1cm;
            left: 2cm;
            right: 1.5cm;
            height: 3cm;
            font-family: "THSarabunNew";
        }

        body {
            font-family: "THSarabunNew";
            font-size: 12px; /* ปรับเหลือ 12px ตามคำขอ */
            line-height: 1.0;
            margin-top: 3.0cm; /* ลดขอบบนลงเพื่อให้ตารางขยับขึ้นไปใกล้ Header อีก 1 บรรทัด */
            margin-bottom: 4cm;
            margin-left: 2cm; /* ขยับซ้ายเข้าอีกเพื่อเผื่อเย็บ */
            margin-right: 1.5cm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
        }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        
        .header-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header-sub {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        /* สไตล์ตารางลายเซ็นใน Footer */
        .signature-table {
            width: 100%;
            border: none;
        }
        .signature-table td {
            border: none;
            text-align: center;
            width: 33%;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-title">ทะเบียนคุมลูกหนี้ค่ารักษาพยาบาล</div>
        <div class="header-sub">
            หน่วยบริการ: {{ $hospital_name }} ({{ $hospital_code }}) &nbsp;&nbsp;&nbsp; ประจำเดือน {{ $month_name }} ปีงบประมาณ {{ $budget_year }}
        </div>
    </header>

    <footer>
        <table class="signature-table">
            <tr>
                <td>
                    (...................................................) <br>
                    ผู้จัดทำรายงาน <br>
                    วันที่ ....../....../......
                </td>
                <td>
                    (...................................................) <br>
                    ผู้ตรวจสอบ <br>
                    วันที่ ....../....../......
                </td>
                <td>
                    (...................................................) <br>
                    ผู้บันทึกบัญชี <br>
                    วันที่ ....../....../......
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <table>
            <thead>
                <tr>
                    <th width="8%">รหัสบัญชี</th>
                    <th>ชื่อผังบัญชี</th>
                    <th width="10%">ยอดยกมา</th>
                    <th width="10%">ตั้งหนี้</th>
                    <th width="10%">ล้างหนี้/รับ</th>
                    <th width="9%">ปรับลด</th>
                    <th width="9%">ปรับเพิ่ม</th>
                    <th width="11%">คงเหลือยกไป</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sum_old = 0; $sum_new = 0; $sum_receive = 0; $sum_dec = 0; $sum_inc = 0; $sum_total = 0;
                @endphp
                @foreach($data as $row)
                    @php
                        $sum_old += floatval($row->balance_old);
                        $sum_new += floatval($row->debt_new);
                        $sum_receive += floatval($row->debt_receive);
                        $sum_dec += floatval($row->debt_adj_dec);
                        $sum_inc += floatval($row->debt_adj_inc);
                        $sum_total += floatval($row->balance_total);
                    @endphp
                    <tr>
                        <td class="text-center">{{ $row->acc_code }}</td>
                        <td class="text-start">{{ $row->acc_name }}</td>
                        <td class="text-end">{{ number_format($row->balance_old, 2) }}</td>
                        <td class="text-end">{{ number_format($row->debt_new, 2) }}</td>
                        <td class="text-end">{{ number_format($row->debt_receive, 2) }}</td>
                        <td class="text-end">{{ number_format($row->debt_adj_dec, 2) }}</td>
                        <td class="text-end">{{ number_format($row->debt_adj_inc, 2) }}</td>
                        <td class="text-end fw-bold">{{ number_format($row->balance_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td colspan="2" class="text-end">รวมทั้งหมด:</td>
                    <td class="text-end">{{ number_format($sum_old, 2) }}</td>
                    <td class="text-end">{{ number_format($sum_new, 2) }}</td>
                    <td class="text-end">{{ number_format($sum_receive, 2) }}</td>
                    <td class="text-end">{{ number_format($sum_dec, 2) }}</td>
                    <td class="text-end">{{ number_format($sum_inc, 2) }}</td>
                    <td class="text-end">{{ number_format($sum_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </main>
</body>
</html>
