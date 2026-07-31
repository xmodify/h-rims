<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseVerificationService;

class CheckLicense
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
        // Whitelist routes that shouldn't lock out administrators from applying settings/license
        $whitelist = [
            'login',
            'logout',
            'register',
            'admin/main_setting',
            'admin/main_setting/*',
            'admin/license/request',
            'admin/license/verify'
        ];

        foreach ($whitelist as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Verify license
        $licenseInfo = LicenseVerificationService::getLicenseStatusInfo();

        if ($licenseInfo['status'] !== 'active') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'ฟังก์ชันนี้ถูกระงับการใช้งานชั่วคราวเนื่องจากปัญหาด้านลิขสิทธิ์โปรแกรม (RimS)',
                    'status' => $licenseInfo['status'],
                    'message' => $licenseInfo['message'] ?? ''
                ], 403);
            }

            return response()->view('errors.license_error', [
                'licenseInfo' => $licenseInfo
            ], 403);
        }

        return $next($request);
    }
}
