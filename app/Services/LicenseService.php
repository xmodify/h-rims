<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LicenseService
{
    /**
     * Get the licensed hospital code from .env / config
     */
    public static function getLicensedHospcode()
    {
        return config('licensing.hospcode', '10989');
    }

    /**
     * Get the current hospital code from HOSxP settings
     */
    public static function getCurrentHospcode()
    {
        return Cache::remember('hospitalcode_current', 86400, function() {
            try {
                return DB::connection('hosxp')->table('opdconfig')->value('hospitalcode');
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    /**
     * Check if the current hospital is licensed
     */
    public static function isLicensed()
    {
        $licensed = self::getLicensedHospcode();
        $current = self::getCurrentHospcode();
        return !empty($current) && $current === $licensed;
    }
}
