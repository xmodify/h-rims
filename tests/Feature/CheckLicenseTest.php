<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\LicenseVerificationService;
use Illuminate\Support\Facades\Cache;

class CheckLicenseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear license verification cache
        Cache::forget(LicenseVerificationService::CACHE_KEY);
    }

    public function test_whitelisted_route_bypasses_license_check()
    {
        // Mock license status as inactive
        Cache::put(LicenseVerificationService::CACHE_KEY, [
            'status' => 'inactive',
            'message' => 'License expired',
            'expires_at' => '2020-01-01'
        ], 3600);

        // Request the newly whitelisted route
        $response = $this->get('/check/sss_equipdev_aipn');

        // It should NOT be blocked with 403. It should redirect to login (302) because of auth middleware.
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }

    public function test_guarded_route_is_blocked_when_license_inactive()
    {
        // Mock license status as inactive
        Cache::put(LicenseVerificationService::CACHE_KEY, [
            'status' => 'inactive',
            'message' => 'License expired',
            'expires_at' => '2020-01-01'
        ], 3600);

        // Request a guarded route, e.g. claim_op/sss_export_ssop (which is auto-detected as export_ssop)
        $response = $this->get('/claim_op/sss_export_ssop');

        // It should be blocked with 403 status code
        $response->assertStatus(403);
    }
}
