<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\SmartMoneyController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Create mock detail excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'รายงานสรุปการขอเบิกชดเชยค่าการรักษาผู้ป่วยรายบุคคล');
$sheet->setCellValue('A2', 'งวด : 6908_OP_02  รหัสผังบัญชี : 4301020105.228');
$sheet->setCellValue('A3', 'ลำดับ');
$sheet->setCellValue('B3', 'วันที่โอน');
$sheet->setCellValue('C3', 'HN');
$sheet->setCellValue('D3', 'AN');
$sheet->setCellValue('E3', 'ประเภท');
$sheet->setCellValue('F3', 'เลขบัตรประชาชน');
$sheet->setCellValue('G3', 'ชื่อ-สกุล');
$sheet->setCellValue('H3', 'วันที่เข้ารับบริการ');
$sheet->setCellValue('I3', 'จ่ายชดเชยสุทธิ');
$sheet->setCellValue('J3', 'REP_NO');
$sheet->setCellValue('K3', 'กองทุนหลัก');
$sheet->setCellValue('L3', 'กองทุนย่อย');
$sheet->setCellValue('M3', 'รายละเอียด');

$sheet->setCellValue('A4', '1');
$sheet->setCellValue('B4', '10 ก.ย. 2569');
$sheet->setCellValue('C4', '12345');
$sheet->setCellValue('D4', '');
$sheet->setCellValue('E4', 'OP');
$sheet->setCellValue('F4', '1341800000000');
$sheet->setCellValue('G4', 'นายทดสอบ ระบบ');
$sheet->setCellValue('H4', '01 ก.ย. 2569');
$sheet->setCellValue('I4', '500.00');
$sheet->setCellValue('J4', 'REP001');
$sheet->setCellValue('K4', 'กองทุนทดสอบ');
$sheet->setCellValue('L4', 'กองทุนย่อยทดสอบ');
$sheet->setCellValue('M4', 'รายละเอียดทดสอบ');

$tmpPath = storage_path('app/test_mock_detail.xlsx');
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($tmpPath);

echo "Created test detail excel at $tmpPath (" . filesize($tmpPath) . " bytes)\n";

$uploadedFile = new UploadedFile($tmpPath, 'test_mock_detail.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

$req = Request::create('/import/smart-money/import-detail', 'POST', [
    'budget_year' => 2569,
    'batch_no' => '3167'
], [], [
    'detail_excel' => $uploadedFile
]);
$req->headers->set('Accept', 'application/json');

$controller = new SmartMoneyController();
$res = $controller->importDetailExcel($req);

echo "Response status code: " . $res->getStatusCode() . "\n";
echo "Response content: " . $res->getContent() . "\n";
