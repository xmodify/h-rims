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
            'checked_at' => time(),
            'expires_at' => '2020-01-01'
        ], 3600);

        $middleware = new \App\Http\Middleware\CheckLicense();
        
        // 1. import/stm_ofc_cipn
        $request1 = \Illuminate\Http\Request::create('/import/stm_ofc_cipn', 'GET');
        $passed1 = false;
        $response1 = $middleware->handle($request1, function ($req) use (&$passed1) {
            $passed1 = true;
            return response('OK');
        });
        $this->assertTrue($passed1);

        // 2. import/stm_ofc_cipndetail
        $request2 = \Illuminate\Http\Request::create('/import/stm_ofc_cipndetail', 'GET');
        $passed2 = false;
        $response2 = $middleware->handle($request2, function ($req) use (&$passed2) {
            $passed2 = true;
            return response('OK');
        });
        $this->assertTrue($passed2);

        // 3. import/stm_ofc_cipn_save
        $requestSave = \Illuminate\Http\Request::create('/import/stm_ofc_cipn_save', 'POST');
        $passedSave = false;
        $responseSave = $middleware->handle($requestSave, function ($req) use (&$passedSave) {
            $passedSave = true;
            return response('OK');
        });
        $this->assertTrue($passedSave);

        // 3. claim_op/sss_export_preview (allowed for previewing modal structure)
        $request3 = \Illuminate\Http\Request::create('/claim_op/sss_export_preview', 'POST');
        $passed3 = false;
        $response3 = $middleware->handle($request3, function ($req) use (&$passed3) {
            $passed3 = true;
            return response('OK');
        });
        $this->assertTrue($passed3);

        // 4. claim_ip/sss_export_preview_aipn (allowed for previewing AIPN modal structure)
        $request4 = \Illuminate\Http\Request::create('/claim_ip/sss_export_preview_aipn', 'POST');
        $passed4 = false;
        $response4 = $middleware->handle($request4, function ($req) use (&$passed4) {
            $passed4 = true;
            return response('OK');
        });
        $this->assertTrue($passed4);

        // 5. claim_ip/sss_an_details (allowed for previewing AN details)
        $request5 = \Illuminate\Http\Request::create('/claim_ip/sss_an_details', 'GET');
        $passed5 = false;
        $response5 = $middleware->handle($request5, function ($req) use (&$passed5) {
            $passed5 = true;
            return response('OK');
        });
        $this->assertTrue($passed5);

        // 7. hosfin and hosfin/trial_balance (allowed for previewing structure)
        $request7 = \Illuminate\Http\Request::create('/hosfin/trial_balance', 'GET');
        $passed7 = false;
        $response7 = $middleware->handle($request7, function ($req) use (&$passed7) {
            $passed7 = true;
            return response('OK');
        });
        $this->assertTrue($passed7);

        // 8. f16_eclaim_export/preview (allowed for previewing 17-tab structure and data)
        $request8 = \Illuminate\Http\Request::create('/f16_eclaim_export/preview', 'POST');
        $passed8 = false;
        $response8 = $middleware->handle($request8, function ($req) use (&$passed8) {
            $passed8 = true;
            return response('OK');
        });
        $this->assertTrue($passed8);

        // 9. f16_fdh_export/preview (allowed for previewing FDH 17-tab structure and data)
        $request9 = \Illuminate\Http\Request::create('/f16_fdh_export/preview', 'POST');
        $passed9 = false;
        $response9 = $middleware->handle($request9, function ($req) use (&$passed9) {
            $passed9 = true;
            return response('OK');
        });
        $this->assertTrue($passed9);
    }

    public function test_guarded_route_is_blocked_when_license_inactive()
    {
        // Mock license status as inactive
        Cache::put(LicenseVerificationService::CACHE_KEY, [
            'status' => 'inactive',
            'message' => 'License expired',
            'checked_at' => time(),
            'expires_at' => '2020-01-01'
        ], 3600);

        $middleware = new \App\Http\Middleware\CheckLicense();
        
        // 1. Export SSOP
        $request1 = \Illuminate\Http\Request::create('/claim_op/sss_export_ssop', 'GET');
        $this->app->instance('request', $request1);
        $response1 = $middleware->handle($request1, function ($req) {
            return response('OK');
        }, 'export_ssop');
        $this->assertEquals(403, $response1->getStatusCode());

        // 2. Export CSOP (guarded via export_csop auto-detection)
        $request2 = \Illuminate\Http\Request::create('/claim_op/csop_export', 'POST');
        $this->app->instance('request', $request2);
        $response2 = $middleware->handle($request2, function ($req) {
            return response('OK');
        });
        $this->assertEquals(403, $response2->getStatusCode());

        // 3. Export AIPN (guarded via export_aipn auto-detection)
        $request3 = \Illuminate\Http\Request::create('/claim_ip/sss_export_aipn', 'POST');
        $this->app->instance('request', $request3);
        $response3 = $middleware->handle($request3, function ($req) {
            return response('OK');
        });
        $this->assertEquals(403, $response3->getStatusCode());

        // 4. check/sss_equipdev_aipn (guarded via export_aipn auto-detection)
        $request4 = \Illuminate\Http\Request::create('/check/sss_equipdev_aipn', 'GET');
        $this->app->instance('request', $request4);
        $response4 = $middleware->handle($request4, function ($req) {
            return response('OK');
        });
        $this->assertEquals(403, $response4->getStatusCode());

        // 5. f16_eclaim_export (guarded via export_f16_eclaim auto-detection)
        $request5 = \Illuminate\Http\Request::create('/f16_eclaim_export/export-data', 'POST');
        $this->app->instance('request', $request5);
        $response5 = $middleware->handle($request5, function ($req) {
            return response('OK');
        });
        $this->assertEquals(403, $response5->getStatusCode());

        // 6. f16_fdh_export (guarded via export_f16_fdh auto-detection)
        $request6 = \Illuminate\Http\Request::create('/f16_fdh_export/export-data', 'POST');
        $this->app->instance('request', $request6);
        $response6 = $middleware->handle($request6, function ($req) {
            return response('OK');
        });
        $this->assertEquals(403, $response6->getStatusCode());

        // 7. eclaim-bot thaid-qr (guarded via sync_eclaim_thaid auto-detection)
        $request7 = \Illuminate\Http\Request::create('/import/eclaim-bot/thaid-qr/start', 'POST');
        $this->app->instance('request', $request7);
        $response7 = $middleware->handle($request7, function ($req) {
            return response('OK');
        });
        $this->assertEquals(403, $response7->getStatusCode());

        // 8. hosfin import trial balance (guarded via hosfin auto-detection)
        $request8 = \Illuminate\Http\Request::create('/hosfin/trial_balance/import', 'POST');
        $this->app->instance('request', $request8);
        $response8 = $middleware->handle($request8, function ($req) {
            return response('OK');
        });
        $this->assertEquals(403, $response8->getStatusCode());
    }
}
