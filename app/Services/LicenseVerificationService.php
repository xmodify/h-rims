<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseVerificationService
{
    const PROGRAM_CODE = 'rims_license';
    const CACHE_KEY = 'rims_license_status_info';
    const CACHE_TTL_DAYS = 7;
    const GRACE_PERIOD_DAYS = 15;

    /**
     * Get the 5-digit hospital code from HOSxP or main_setting fallback
     */
    public static function getHcode()
    {
        return Cache::remember('rims_hcode', 86400, function () {
            try {
                $code = DB::connection('hosxp')->table('opdconfig')->value('hospitalcode');
                if (!empty($code)) {
                    return $code;
                }
            } catch (\Throwable $e) {
                // Fallback to main_setting
            }

            try {
                $code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
                return $code ? trim($code, '" ') : '00000';
            } catch (\Throwable $e) {
                return '00000';
            }
        });
    }

    /**
     * Get the stored license key
     */
    public static function getLicenseKey()
    {
        try {
            $key = DB::table('main_setting')->where('name', 'rims_license_key')->value('value');
            return $key ? trim($key, '" ') : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Format date string to Thai short date (e.g. 31 ธ.ค. 69)
     */
    public static function formatThaiShortDate($dateStr)
    {
        if (empty($dateStr)) {
            return '';
        }
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        try {
            $time = strtotime($dateStr);
            $d = date('j', $time);
            $m = $months[intval(date('n', $time))];
            $y = intval(date('Y', $time)) + 543;
            $yShort = substr((string)$y, -2);
            return "{$d} {$m} {$yShort}";
        } catch (\Throwable $e) {
            return $dateStr;
        }
    }



    /**
     * Verify license with local caching and offline fallback
     */
    public static function getLicenseStatusInfo()
    {
        $key = self::getLicenseKey();
        if (empty($key)) {
            return [
                'status' => 'not_registered',
                'expires_at' => null,
                'checked_at' => time(),
                'offline' => false,
                'message' => 'ยังไม่ได้ลงทะเบียนลิขสิทธิ์'
            ];
        }

        $cached = Cache::get(self::CACHE_KEY);
        $now = time();

        // 1. If cache exists and is fresh (less than 7 days old)
        if ($cached && isset($cached['checked_at']) && ($now - $cached['checked_at'] < self::CACHE_TTL_DAYS * 86400)) {
            // Check if actual expiration date is in the past
            if (!empty($cached['expires_at']) && strtotime($cached['expires_at']) < strtotime(date('Y-m-d'))) {
                $cached['status'] = 'expired';
            }
            return $cached;
        }

        // 2. Cache is missing or expired, attempt to verify online
        $hcode = self::getHcode();
        try {
            $response = Http::withoutVerifying()->timeout(8)->post('https://huataphanhospital.go.th/smartdata/license/verify', [
                'license_key' => $key,
                'program_code' => self::PROGRAM_CODE,
                'hcode' => $hcode
            ]);

            $data = $response->json();
            // Handle wrapped/unwrapped response
            $resData = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;

            if ($response->successful() || (isset($resData['status']) && in_array($resData['status'], ['active', 'expired', 'suspended', 'pending', 'inactive']))) {
                $status = $resData['status'] ?? 'inactive'; // active, expired, suspended, inactive
                $expiresAt = $resData['expired_at'] ?? ($resData['expires_at'] ?? null);

                // Adjust status if local clock shows it is expired
                if (!empty($expiresAt) && strtotime($expiresAt) < strtotime(date('Y-m-d'))) {
                    $status = 'expired';
                }

                $info = [
                    'status' => $status,
                    'expires_at' => $expiresAt,
                    'checked_at' => $now,
                    'offline' => false,
                    'message' => $resData['message'] ?? '',
                    'license_type' => $resData['license_type'] ?? 'standard',
                    'modules' => $resData['modules'] ?? [],
                    'module_details' => $resData['module_details'] ?? [],
                    'configs' => $resData['configs'] ?? []
                ];

                Cache::put(self::CACHE_KEY, $info, 30 * 86400); // Store up to 30 days but logic checks 7 days TTL
                return $info;
            }
        } catch (\Throwable $e) {
            Log::warning('License verify network error: ' . $e->getMessage() . '. Fallback to offline grace period.');
        }

        // 3. Network call failed, attempt offline grace period
        if ($cached && isset($cached['checked_at'])) {
            $elapsedSeconds = $now - $cached['checked_at'];
            $maxGraceSeconds = self::GRACE_PERIOD_DAYS * 86400;

            if ($elapsedSeconds < $maxGraceSeconds) {
                // Extend cache by 1 day to prevent continuous timeouts, preserving the original checked_at for grace check
                $cached['offline'] = true;
                Cache::put(self::CACHE_KEY, $cached, 86400);
                return $cached;
            }
        }

        // 4. No cache or grace period expired
        return [
            'status' => 'network_timeout',
            'expires_at' => $cached['expires_at'] ?? null,
            'checked_at' => $cached['checked_at'] ?? $now,
            'offline' => true,
            'message' => 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ยืนยันลิขสิทธิ์ได้เกินเวลาที่กำหนด (15 วัน)'
        ];
    }

    /**
     * Check if license is active
     */
    public static function isLicensed()
    {
        $info = self::getLicenseStatusInfo();
        return isset($info['status']) && $info['status'] === 'active';
    }

    /**
     * Check if a specific module is licensed and active
     */
    public static function isModuleLicensed($moduleCode)
    {
        $info = self::getLicenseStatusInfo();
        
        // 1. If overall license is not active, everything is blocked
        if (!isset($info['status']) || $info['status'] !== 'active') {
            return false;
        }

        // 2. If it's a full license, all modules are allowed
        $licenseType = $info['license_type'] ?? 'standard';
        if (strtolower($licenseType) === 'full') {
            return true;
        }

        // 3. If it's a module license, check the specific module status & expiration
        $moduleDetails = $info['module_details'] ?? [];

        foreach ($moduleDetails as $detail) {
            if (isset($detail['code']) && $detail['code'] === $moduleCode) {
                $status = $detail['status'] ?? 'inactive';
                $expiredAt = $detail['expired_at'] ?? null;

                if ($status !== 'active') {
                    return false;
                }

                // Check module expiration date if specified
                if (!empty($expiredAt) && strtotime($expiredAt) < strtotime(date('Y-m-d'))) {
                    return false;
                }

                return true;
            }
        }

        // Check fallback modules array list if module_details is not populated
        $modules = $info['modules'] ?? [];
        return in_array($moduleCode, $modules);
    }

    /**
     * Get a configuration value from the central license server with local DB fallback.
     */
    public static function getConfig($keyName, $localSettingName = null)
    {
        // 1. Try reading the local DB setting first
        if ($localSettingName) {
            try {
                $localVal = DB::table('main_setting')->where('name', $localSettingName)->value('value');
                if (!is_null($localVal) && $localVal !== '') {
                    return trim($localVal, '" ');
                }
            } catch (\Throwable $e) {
                // Fail silently (e.g. table or setting not found)
            }
        }

        // 2. Fallback to cached license status configs from central server
        $info = self::getLicenseStatusInfo();
        if (isset($info['configs']) && is_array($info['configs']) && isset($info['configs'][$keyName])) {
            return trim($info['configs'][$keyName], '" ');
        }

        return '';
    }
}
