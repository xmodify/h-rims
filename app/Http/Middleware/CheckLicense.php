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
    public function handle(Request $request, Closure $next, $module = null)
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

        // 1. Auto-detect module from request path if not explicitly passed
        if ($module === null) {
            $path = $request->path();
            if (str_starts_with($path, 'claim_op/sss_export')) {
                $module = 'export_ssop';
            } elseif (str_starts_with($path, 'claim_op/csop_export')) {
                $module = 'export_csop';
            } elseif (str_contains($path, 'aipn')) {
                $module = 'export_aipn';
            } elseif (str_contains($path, 'cipn')) {
                $module = 'export_cipn';
            } elseif (
                str_starts_with($path, 'debtor/acc_ledger') || 
                str_contains($path, '_confirm') || 
                str_contains($path, '/lock') || 
                str_contains($path, '/unlock') || 
                str_contains($path, 'lock_debtor')
            ) {
                $module = 'debtor_control';
            }
        }

        // 2. If this route is not associated with any guarded module, allow it to pass freely
        if ($module === null) {
            return $next($request);
        }

        // 3. Verify general license status for guarded modules
        $licenseInfo = LicenseVerificationService::getLicenseStatusInfo();

        if ($licenseInfo['status'] !== 'active') {
            return $this->blockRequest($request, $licenseInfo);
        }

        // 4. Verify specific module status
        if (!LicenseVerificationService::isModuleLicensed($module)) {
            $moduleMetaNames = [
                'export_ssop' => 'ระบบส่งออกข้อมูลประกันสังคม SSOP',
                'export_aipn' => 'ระบบส่งออกข้อมูลประกันสังคม AIPN',
                'export_csop' => 'ระบบส่งออกข้อมูลสวัสดิการข้าราชการ CSOP',
                'export_cipn' => 'ระบบส่งออกข้อมูลสวัสดิการข้าราชการ CIPN',
                'nhso_checkright' => 'ระบบตรวจสอบสิทธิ์การรักษา (สปสช.)',
                'debtor_control' => 'ระบบทะเบียนคุมลูกหนี้ (DebtorControl)',
            ];

            $moduleName = $moduleMetaNames[$module] ?? $module;
            
            $blockInfo = [
                'status' => 'module_inactive',
                'message' => "ระบบงาน \"{$moduleName}\" ไม่ได้รับการเปิดสิทธิ์ใช้งาน หรือสิ้นสุดระยะเวลาการใช้งานภายใต้ลิขสิทธิ์ปัจจุบัน",
                'expires_at' => null
            ];

            return $this->blockRequest($request, $blockInfo);
        }

        return $next($request);
    }

    /**
     * Helper to render the block response
     */
    protected function blockRequest(Request $request, array $licenseInfo)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => 'ฟังก์ชันนี้ถูกระงับการใช้งานชั่วคราวเนื่องจากข้อจำกัดด้านลิขสิทธิ์โปรแกรม (RimS)',
                'status' => $licenseInfo['status'],
                'message' => $licenseInfo['message'] ?? ''
            ], 403);
        }

        return response()->view('errors.license_error', [
            'licenseInfo' => $licenseInfo
        ], 403);
    }
}
