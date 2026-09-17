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

// Create a mock summary excel file
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'รายงานการโอนเงินกองทุน');
$sheet->setCellValue('A2', 'ลำดับ');
$sheet->setCellValue('B2', 'วันที่โอน');
$sheet->setCellValue('C2', 'Batch No.');
$sheet->setCellValue('D2', 'งวด/เลขที่เบิกจ่าย');
$sheet->setCellValue('E2', 'รหัสผังบัญชี');
$sheet->setCellValue('F2', 'กองทุน');
$sheet->setCellValue('G2', 'กองทุนย่อย');
$sheet->setCellValue('H2', 'จำนวนเงิน');
$sheet->setCellValue('I2', 'ชะลอการโอน');
$sheet->setCellValue('J2', 'รายการหัก');
$sheet->setCellValue('K2', 'หลักประกัน');
$sheet->setCellValue('L2', 'ภาษี');
$sheet->setCellValue('M2', 'คงเหลือ');
$sheet->setCellValue('N2', 'รอหักกลบ');
$sheet->setCellValue('O2', 'เงินโอนเข้าบัญชี');

$sheet->setCellValue('A3', '1');
$sheet->setCellValue('B3', '15 ก.ย. 2569');
$sheet->setCellValue('C3', '9999');
$sheet->setCellValue('D3', '6909_TEST');
$sheet->setCellValue('E3', '4301020105.228');
$sheet->setCellValue('F3', 'กองทุนทดสอบ');
$sheet->setCellValue('G3', 'ย่อยทดสอบ');
$sheet->setCellValue('H3', '5000.00');
$sheet->setCellValue('I3', '0.00');
$sheet->setCellValue('J3', '0.00');
$sheet->setCellValue('K3', '0.00');
$sheet->setCellValue('L3', '0.00');
$sheet->setCellValue('M3', '5000.00');
$sheet->setCellValue('N3', '0.00');
$sheet->setCellValue('O3', '5000.00');

$tmpPath = storage_path('app/test_mock_summary.xlsx');
$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($tmpPath);

echo "Created test excel at $tmpPath (" . filesize($tmpPath) . " bytes)\n";

$uploadedFile = new UploadedFile($tmpPath, 'test_mock_summary.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

$req = Request::create('/import/smart-money/import-summary', 'POST', [
    'budget_year' => 2569
], [], [
    'excel_file' => $uploadedFile
]);
$req->headers->set('Accept', 'application/json');

$controller = new SmartMoneyController();
$res = $controller->importSummaryExcel($req);

echo "Response status code: " . $res->getStatusCode() . "\n";
echo "Response content: " . $res->getContent() . "\n";
