<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Paginator::useBootstrapFive();
          
        // ตรวจสอบว่ามีข้อมูลในตาราง lookup_icode มีรายการฟอกไต หรือไม่
        $hasLookupIcode_kidney = DB::table('lookup_icode')
            ->where('kidney', '<>', '')
            ->exists();

        // แชร์ตัวแปรนี้ไปยังทุก view
        View::share('hasLookupIcode_kidney', $hasLookupIcode_kidney);
    }

}
