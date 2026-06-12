<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\Api\AmnosendController;
use App\Http\Controllers\Api\NhsoEndpointController;
use App\Http\Controllers\Api\FdhClaimStatusController;

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

// สั่งให้ส่งข้อมูล AOPOD ทำงานทุก 1 ชั่วโมง เริ่มเวลา hh:15
Schedule::call(function () {
    app(AmnosendController::class)->send(new \Illuminate\Http\Request());
})->cron('15 * * * *');

// ดึงข้อมูลปิดสิทธิการเคลม สปสช. (ของเมื่อวาน) ทุกวัน เวลา 00:05 น.
Schedule::call(function () {
    app(NhsoEndpointController::class)->pullYesterday();
})->dailyAt('00:05');

// ดึงและอัปเดตสถานะการส่งเคลม FDH ย้อนหลัง 15 วัน (ดึงข้อมูลทีละวัน) ทุกวัน เวลา 00:30 น.
Schedule::call(function () {
    app(FdhClaimStatusController::class)->checkLastDays();
})->dailyAt('00:30');
