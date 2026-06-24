<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\Api\AopodSendController;
use App\Http\Controllers\Api\NhsoEndpointController;
use App\Http\Controllers\Api\FdhClaimStatusController;
use App\Http\Controllers\NotifyController;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
| Define your scheduled tasks directly in the code using the Schedule facade.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ฟังก์ชันช่วยเขียน Log และจำกัดจำนวนแถวเพื่อไม่ให้ไฟล์โตเกินไป
if (!function_exists('appendAndLimitLog')) {
    function appendAndLimitLog($filename, $logMessage, $limit = 30) {
        $filePath = storage_path('logs/' . $filename);
        
        // เขียนต่อท้ายไฟล์
        \Illuminate\Support\Facades\File::append($filePath, $logMessage);
        
        // ตรวจสอบและตัดให้เหลือเฉพาะจำนวนที่กำหนด
        if (\Illuminate\Support\Facades\File::exists($filePath)) {
            $content = \Illuminate\Support\Facades\File::get($filePath);
            $lines = array_filter(explode("\n", trim($content)));
            
            if (count($lines) > $limit) {
                $latestLines = array_slice($lines, -$limit);
                \Illuminate\Support\Facades\File::put($filePath, implode("\n", $latestLines) . "\n");
            }
        }
    }
}

// สั่งให้ส่งข้อมูล AOPOD ทำงานทุก 1 ชั่วโมง เริ่มเวลา hh:15
Schedule::call(function () {
    $res = app(AopodSendController::class)->send(request());
    $logMessage = "[" . now()->toDateTimeString() . "] AOPOD output: " . json_encode($res->getData(), JSON_UNESCAPED_UNICODE) . "\n";
    appendAndLimitLog('aopod_schedule.log', $logMessage, 24); // เก็บ 24 รายการล่าสุด (1 วัน)
})->cron('15 * * * *');

// ดึงข้อมูลปิดสิทธิการเคลม สปสช. (ของเมื่อวาน) ทุกวัน เวลา 00:05 น.
Schedule::call(function () {
    $res = app(NhsoEndpointController::class)->pullYesterday();
    $logMessage = "[" . now()->toDateTimeString() . "] NHSO Endpoint output: " . json_encode($res->getData(), JSON_UNESCAPED_UNICODE) . "\n";
    appendAndLimitLog('nhso_endpoint_schedule.log', $logMessage, 30); // เก็บ 30 รายการล่าสุด (30 วัน)
})->dailyAt('00:05');

// ดึงและอัปเดตสถานะการส่งเคลม FDH ย้อนหลัง 15 วัน (ดึงข้อมูลทีละวัน) ทุกวัน เวลา 00:30 น.
Schedule::call(function () {
    $res = app(FdhClaimStatusController::class)->checkLastDays();
    $logMessage = "[" . now()->toDateTimeString() . "] FDH Claim Status output: " . json_encode($res->getData(), JSON_UNESCAPED_UNICODE) . "\n";
    appendAndLimitLog('fdh_claim_status_schedule.log', $logMessage, 30); // เก็บ 30 รายการล่าสุด (30 วัน)
})->dailyAt('00:30');

// ส่งแจ้งเตือนสรุปบริการประจำวัน (Notify Summary) ทุกวัน เวลา 08:00 น.
Schedule::call(function () {
    $res = app(NotifyController::class)->notify_summary(request());
    $logMessage = "[" . now()->toDateTimeString() . "] Notify Summary output: " . json_encode($res->getData(), JSON_UNESCAPED_UNICODE) . "\n";
    appendAndLimitLog('notify_schedule.log', $logMessage, 30); // เก็บ 30 รายการล่าสุด (30 วัน)
})->dailyAt('08:00');
