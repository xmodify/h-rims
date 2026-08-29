<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProviderIdAuthController extends Controller
{
    protected $healthIdUrl = 'https://moph.id.th';
    protected $providerIdUrl = 'https://provider.id.th';

    /**
     * Redirect the user to the Health ID OAuth Provider.
     */
    public function redirectToProvider(Request $request)
    {
        // 1. Fetch credentials from main_setting or central license server
        $clientId = \App\Services\LicenseVerificationService::getConfig('health_id_client_id', 'health_id_client_id');
        $isActive = \App\Services\LicenseVerificationService::getConfig('provider_id_active', 'provider_id_active') === 'Y';

        if (!$isActive || empty($clientId)) {
            return redirect()->route('login')->with('error', 'ระบบเข้าสู่ระบบด้วย Provider ID ยังไม่เปิดใช้งานหรือตั้งค่าไม่สมบูรณ์');
        }

        // Generate redirect URI dynamically or read from settings/license server
        $redirectUri = \App\Services\LicenseVerificationService::getConfig('health_id_redirect_uri', 'health_id_redirect_uri') ?: route('auth.health-id.callback');

        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
        ]);

        return redirect($this->healthIdUrl . '/oauth/redirect?' . $query);
    }

    /**
     * Obtain the user information from Health ID / Provider ID and login the user.
     */
    public function handleProviderCallback(Request $request)
    {
        // 1. Verify CSRF State
        $state = $request->session()->pull('oauth_state');
        if (empty($state) || $state !== $request->state) {
            return redirect()->route('login')->with('error', 'การยืนยันตัวตนหมดอายุ หรือข้อมูลตรวจสอบความปลอดภัยไม่ถูกต้อง (Invalid State)');
        }

        $code = $request->code;
        if (!$code) {
            return redirect()->route('login')->with('error', 'ไม่ได้รับข้อมูลรหัสยืนยันจากทางกระทรวงฯ');
        }

        try {
            // 2. Load configurations from database or central license server
            $healthIdClientId = \App\Services\LicenseVerificationService::getConfig('health_id_client_id', 'health_id_client_id');
            $healthIdClientSecret = \App\Services\LicenseVerificationService::getConfig('health_id_client_secret', 'health_id_client_secret');
            $providerIdClientId = \App\Services\LicenseVerificationService::getConfig('provider_id_client_id', 'provider_id_client_id');
            $providerIdSecretKey = \App\Services\LicenseVerificationService::getConfig('provider_id_secret_key', 'provider_id_secret_key');
            
            $redirectUri = \App\Services\LicenseVerificationService::getConfig('health_id_redirect_uri', 'health_id_redirect_uri') ?: route('auth.health-id.callback');

            if (empty($healthIdClientId) || empty($healthIdClientSecret) || empty($providerIdClientId) || empty($providerIdSecretKey)) {
                return redirect()->route('login')->with('error', 'ระบบตั้งค่าการเชื่อมต่อในส่วนของผู้ดูแลระบบไม่สมบูรณ์');
            }

            // 3. Trade authorization code for Health ID access token
            $healthIdResponse = Http::withoutVerifying()->asForm()->post($this->healthIdUrl . '/api/v1/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $healthIdClientId,
                'client_secret' => $healthIdClientSecret,
            ]);

            if ($healthIdResponse->failed()) {
                Log::error('Health ID Exchange Token Failed: ' . $healthIdResponse->body());
                return redirect()->route('login')->with('error', 'ไม่สามารถขอสิทธิ์เข้าใช้งาน Health ID ได้: ' . ($healthIdResponse->json('message') ?? 'Unknown error'));
            }

            $healthIdToken = $healthIdResponse->json('data.access_token');

            // 4. Trade Health ID token for Provider ID access token
            $providerResponse = Http::withoutVerifying()->post($this->providerIdUrl . '/api/v1/services/token', [
                'client_id' => $providerIdClientId,
                'secret_key' => $providerIdSecretKey,
                'token_by' => 'Health ID',
                'token' => $healthIdToken,
            ]);

            if ($providerResponse->status() === 400) {
                return $this->returnAuthResponse(false, 'บัญชีนี้ยังไม่ได้รับสิทธิ์หรือไม่มีข้อมูลเลขผู้ให้บริการ (Provider ID) ในระบบกระทรวงสาธารณสุข');
            }

            if ($providerResponse->failed()) {
                Log::error('Provider ID Exchange Token Failed: ' . $providerResponse->body());
                return $this->returnAuthResponse(false, 'ไม่สามารถตรวจสอบสิทธิ์ผู้ให้บริการกับทางกระทรวงฯ ได้');
            }

            $providerToken = $providerResponse->json('data.access_token');

            // 5. Get Provider Profile Details
            $profileResponse = Http::withoutVerifying()->withHeaders([
                'Authorization' => 'Bearer ' . $providerToken,
                'client-id' => $providerIdClientId,
                'secret-key' => $providerIdSecretKey,
            ])->get($this->providerIdUrl . '/api/v1/services/profile', [
                'moph_idp_permission' => 1
            ]);

            if ($profileResponse->failed()) {
                Log::error('Get Provider Profile Failed: ' . $profileResponse->body());
                return $this->returnAuthResponse(false, 'ไม่สามารถเชื่อมต่อเพื่อดึงประวัติผู้ให้บริการได้');
            }

            $profileData = $profileResponse->json('data');
            $hashCid = $profileData['hash_cid'] ?? null;

            if (empty($hashCid)) {
                return $this->returnAuthResponse(false, 'ข้อมูลเลขบัตรประชาชนที่เข้ารหัสไม่ถูกต้อง');
            }

            // 6. Match user via SHA2(cid, 256) in H-RiMS users database
            $user = DB::table('users')
                ->whereRaw('SHA2(cid, 256) = ?', [$hashCid])
                ->where('active', 'Y')
                ->first();

            if (!$user) {
                return $this->returnAuthResponse(false, 'ไม่พบรายชื่อหรือผู้ใช้งานนี้ในระบบ RiMS (เลขบัตรประชาชนของท่านยังไม่ผ่านการลงทะเบียนประวัติในระบบ)');
            }

            // 7. Log in the user into Laravel session
            Auth::loginUsingId($user->id);

            // Store FDH token in session and database for hospital claims API calls if available
            $orgData = $profileData['organization'] ?? [];
            $mophFdhToken = null;
            $currentHcode = DB::table('main_setting')->where('name', 'hcode')->value('value') 
                ?? (DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?? '10989');

            if (!empty($orgData) && is_array($orgData)) {
                // 1. ค้นหาองค์กรที่ตรงกับรหัสโรงพยาบาลปัจจุบันก่อน
                foreach ($orgData as $org) {
                    $orgHcode = $org['hospital_code'] ?? ($org['hcode'] ?? ($org['unit_code'] ?? null));
                    if ($orgHcode == $currentHcode && !empty($org['moph_access_token_idp_fdh'])) {
                        $mophFdhToken = $org['moph_access_token_idp_fdh'];
                        break;
                    }
                }
                // 2. ถ้าไม่พบที่ตรง hcode ให้เอาองค์กรแรกที่มี FDH Token
                if (empty($mophFdhToken)) {
                    foreach ($orgData as $org) {
                        if (!empty($org['moph_access_token_idp_fdh'])) {
                            $mophFdhToken = $org['moph_access_token_idp_fdh'];
                            break;
                        }
                    }
                }
            }

            // Fallback to Provider ID Access Token or Health ID Token
            if (empty($mophFdhToken)) {
                $mophFdhToken = $providerToken ?? ($healthIdToken ?? null);
            }

            if (!empty($mophFdhToken)) {
                session(['moph_fdh_token' => $mophFdhToken]);
            }

            // Update user record with provider details and token
            try {
                DB::table('users')->where('id', $user->id)->update([
                    'provider_id' => $profileData['provider_id'] ?? ($profileData['id'] ?? ($user->provider_id ?? null)),
                    'moph_token' => $mophFdhToken,
                    'moph_token_expire' => now()->addHours(12),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Could not update moph_token in users table: ' . $e->getMessage());
            }

            return $this->returnAuthResponse(true, 'ยินดีต้อนรับคุณ ' . $user->name, route('home'));

        } catch (\Exception $e) {
            Log::error('Provider ID Authentication Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->returnAuthResponse(false, 'ระบบขัดข้องชั่วคราวในการยืนยันตัวตนกับทางกระทรวงฯ');
        }
    }

    /**
     * ส่งคำตอบกลับที่ฉลาด: หากเปิดในหน้าต่าง Pop-up ให้ปิดตัวเองและแจ้ง Parent ทันที
     */
    private function returnAuthResponse(bool $isSuccess, string $message, ?string $redirectUrl = null)
    {
        $redirectUrl = $redirectUrl ?: ($isSuccess ? route('home') : route('login'));
        $statusStr = $isSuccess ? 'success' : 'error';
        $titleStr = $isSuccess ? 'ยืนยันตัวตน Provider ID สำเร็จ' : 'การยืนยันตัวตนไม่สำเร็จ';
        $color = $isSuccess ? '#0b7379' : '#dc2626';

        return response()->make('
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>' . $titleStr . '</title>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f8fafc; color: #1e293b; text-align: center; }
                    .card { background: white; padding: 2rem; border-radius: 14px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); max-width: 420px; width: 90%; }
                    .spinner { border: 3px solid #e2e8f0; border-top: 3px solid ' . $color . '; border-radius: 50%; width: 28px; height: 28px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
                    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                </style>
            </head>
            <body>
                <div class="card">
                    ' . ($isSuccess ? '<div class="spinner"></div>' : '') . '
                    <h3 style="color: ' . $color . '; margin: 0 0 0.5rem; font-size: 1.25rem;">' . $titleStr . '</h3>
                    <p style="color: #64748b; font-size: 0.95rem; margin: 0 0 1rem;">' . htmlspecialchars($message) . '</p>
                    <p style="color: #94a3b8; font-size: 0.8rem; margin: 0;">กำลังปิดหน้าต่างนี้อัตโนมัติ...</p>
                </div>
                <script>
                    if (window.opener && !window.opener.closed) {
                        try {
                            window.opener.postMessage({ status: "' . $statusStr . '", type: "PROVIDER_ID_AUTH_' . strtoupper($statusStr) . '", message: "' . addslashes($message) . '" }, "*");
                        } catch(e) {}
                        setTimeout(() => window.close(), 1000);
                    } else {
                        window.location.href = "' . $redirectUrl . '";
                    }
                </script>
            </body>
            </html>
        ');
    }
}
