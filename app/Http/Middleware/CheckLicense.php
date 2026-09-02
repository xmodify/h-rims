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
            'admin/license/verify',
            'import/stm_ofc_cipn*'
        ];

        foreach ($whitelist as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // 1. Auto-detect module from request path if not explicitly passed
        if ($module === null) {
            $path = $request->path();
            if (str_starts_with($path, 'claim_op/sss_export_ssop')) {
                $module = 'export_ssop';
            } elseif (str_starts_with($path, 'claim_op/csop_export') && !str_starts_with($path, 'claim_op/csop_export_preview')) {
                $module = 'export_csop';
            } elseif (
                str_starts_with($path, 'claim_ip/sss_export_aipn') ||
                str_starts_with($path, 'check/sss_equipdev_aipn')
            ) {
                $module = 'export_aipn';
            } elseif (
                str_starts_with($path, 'claim_ip/cipn_export') ||
                str_starts_with($path, 'claim_ip/sss_export_cipn')
            ) {
                $module = 'export_cipn';
            } elseif (
                str_starts_with($path, 'f16_eclaim_export/export-data') ||
                str_starts_with($path, 'f16_eclaim_export/send-api') ||
                str_starts_with($path, 'f16_eclaim_export/check-token')
            ) {
                $module = 'export_f16_eclaim';
            } elseif (
                str_starts_with($path, 'f16_fdh_export/export-data') ||
                str_starts_with($path, 'f16_fdh_export/send-api') ||
                str_starts_with($path, 'f16_fdh_export/check-token')
            ) {
                $module = 'export_f16_fdh';
            } elseif (
                str_starts_with($path, 'import/eclaim-bot/thaid-qr') ||
                str_starts_with($path, 'import/eclaim-bot/generate-qr') ||
                str_starts_with($path, 'import/eclaim-bot/verify-login')
            ) {
                $module = 'sync_eclaim_thaid';
            } elseif (
                str_starts_with($path, 'debtor/acc_ledger')
            ) {
                $module = 'debtor_control';
            } elseif (
                str_starts_with($path, 'hosfin/trial_balance/import') ||
                str_starts_with($path, 'hosfin/trial_balance/analyze_mdb') ||
                str_starts_with($path, 'hosfin/trial_balance/delete') ||
                str_starts_with($path, 'hosfin/mappings/store') ||
                str_starts_with($path, 'hosfin/mappings/delete')
            ) {
                $module = 'hosfin';
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
                'export_f16_eclaim' => 'ระบบส่งออกข้อมูล 16 แฟ้ม e-Claim',
                'export_f16_fdh' => 'ระบบส่งออกข้อมูล 16 แฟ้ม FDH MOPH Claim',
                'sync_eclaim_thaid' => 'ระบบเชื่อมต่อและดึงข้อมูล e-Claim ด้วย ThaID QR',
                'debtor_control' => 'ระบบทะเบียนคุมลูกหนี้ (DebtorControl)',
                'hosfin' => 'ระบบรายงานสถานะการเงินการคลัง (HosFin)',
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
