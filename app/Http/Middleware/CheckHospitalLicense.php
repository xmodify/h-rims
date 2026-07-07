<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CheckHospitalLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!\App\Services\LicenseService::isLicensed()) {
            if ($request->ajax()) {
                return response()->json(['error' => 'ฟังก์ชันนี้ยังไม่ได้เปิดใช้งานลิขสิทธิ์สำหรับโรงพยาบาลของท่าน'], 403);
            }
            abort(403, 'ระบบส่งออก SSOP ยังไม่เปิดใช้งานสำหรับสิทธิ์การใช้งานของโรงพยาบาลท่าน');
        }

        return $next($request);
    }
}
