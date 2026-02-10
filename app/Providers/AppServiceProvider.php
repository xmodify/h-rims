<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

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
        // บังคับ Root URL กรณีใช้ Reverse Proxy (Sub-path)
        // ตรวจสอบจาก APP_URL ใน .env โดยตรงเพื่อให้ชัวร์
        $appUrl = config('app.url');
        if (str_contains($appUrl, '192.168') || str_contains($appUrl, 'http')) {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
        }

        //  Paginator::useBootstrapFive();

        // // ตรวจสอบว่ามีข้อมูลในตาราง lookup_icode มีรายการฟอกไต หรือไม่
        // $hasLookupIcode_kidney = DB::table('lookup_icode')
        //     ->where('kidney', '<>', '')
        //     ->exists();

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
