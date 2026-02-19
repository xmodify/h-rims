<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    /**
     * Register any application services.
     */
    public function register(): void
    {
        \Illuminate\Database\Connection::resolverFor('mariadb', function ($connection, $database, $prefix, $config) {
            return new \App\Database\LegacyMariaDbConnection($connection, $database, $prefix, $config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        // 1. บังคับ URL พื้นฐานตามที่ตั้งค่าใน .env (APP_URL)
        // วิธีนี้จะช่วยแก้ปัญหาเวลา Proxy หรือ Alias แล้ว Link เจนออกมาผิด Port หรือผิด Path
        if (!app()->runningInConsole()) {
            URL::forceRootUrl(config('app.url'));

            // 2. ถ้า Server จริงใช้ HTTPS (moph.go.th) แต่หน้าเว็บแสดงผลไม่สมบูรณ์
            // ให้บังคับ Scheme เป็น https ด้วยครับ
            if (str_contains(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // // แชร์ตัวแปรนี้ไปยังทุก view
        // View::share('hasLookupIcode_kidney', $hasLookupIcode_kidney);
        //----------------------------------------------------------------------------------------
        View::composer('*', function ($view) {
            $hasLookupIcode_kidney = false;
            // เฉพาะหน้าที่ยกเว้น ไม่โหลดจาก DB แต่ยังแชร์ค่าดีฟอลต์
            if (!request()->is('admin/main_setting')) {
                if (Schema::hasColumn('lookup_icode', 'kidney')) {
                    $hasLookupIcode_kidney = DB::table('lookup_icode')
                        ->where('kidney', '<>', '')
                        ->exists();
                }
            }
            $view->with('hasLookupIcode_kidney', $hasLookupIcode_kidney);
        });

    }

}
