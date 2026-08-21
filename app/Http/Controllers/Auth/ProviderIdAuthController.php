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
        // 1. Fetch credentials from main_setting
        $clientIdSetting = DB::table('main_setting')->where('name', 'health_id_client_id')->first();
        $activeSetting = DB::table('main_setting')->where('name', 'provider_id_active')->first();
        
        $clientId = $clientIdSetting ? trim($clientIdSetting->value) : '';
        $isActive = $activeSetting ? trim($activeSetting->value) === 'Y' : false;

        // Strip quotes if they were entered with double quotes (e.g. "client_id")
        $clientId = trim($clientId, '"\'');

        if (!$isActive || empty($clientId)) {
            return redirect()->route('login')->with('error', 'ระบบเข้าสู่ระบบด้วย Provider ID ยังไม่เปิดใช้งานหรือตั้งค่าไม่สมบูรณ์');
        }

        // Generate redirect URI dynamically or read from settings
        $redirectUriSetting = DB::table('main_setting')->where('name', 'health_id_redirect_uri')->value('value');
        $redirectUri = $redirectUriSetting ? trim($redirectUriSetting, '"\'') : route('auth.health-id.callback');
        if (empty($redirectUri)) {
            $redirectUri = route('auth.health-id.callback');
        }

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
            // 2. Load configurations from database
            $healthIdClientId = trim(DB::table('main_setting')->where('name', 'health_id_client_id')->value('value') ?? '', '"\'');
            $healthIdClientSecret = trim(DB::table('main_setting')->where('name', 'health_id_client_secret')->value('value') ?? '', '"\'');
            $providerIdClientId = trim(DB::table('main_setting')->where('name', 'provider_id_client_id')->value('value') ?? '', '"\'');
            $providerIdSecretKey = trim(DB::table('main_setting')->where('name', 'provider_id_secret_key')->value('value') ?? '', '"\'');
            
            $redirectUriSetting = DB::table('main_setting')->where('name', 'health_id_redirect_uri')->value('value');
            $redirectUri = $redirectUriSetting ? trim($redirectUriSetting, '"\'') : route('auth.health-id.callback');

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
                return redirect()->route('login')->with('error', 'บัญชีนี้ยังไม่ได้รับสิทธิ์หรือไม่มีข้อมูลเลขผู้ให้บริการ (Provider ID) ในระบบกระทรวงสาธารณสุข');
            }

            if ($providerResponse->failed()) {
                Log::error('Provider ID Exchange Token Failed: ' . $providerResponse->body());
                return redirect()->route('login')->with('error', 'ไม่สามารถตรวจสอบสิทธิ์ผู้ให้บริการกับทางกระทรวงฯ ได้');
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
                return redirect()->route('login')->with('error', 'ไม่สามารถเชื่อมต่อเพื่อดึงประวัติผู้ให้บริการได้');
            }

            $profileData = $profileResponse->json('data');
            $hashCid = $profileData['hash_cid'] ?? null;

            if (empty($hashCid)) {
                return redirect()->route('login')->with('error', 'ข้อมูลเลขบัตรประชาชนที่เข้ารหัสไม่ถูกต้อง');
            }

            // 6. Match user via SHA2(cid, 256) in H-RiMS users database
            $user = DB::table('users')
                ->whereRaw('SHA2(cid, 256) = ?', [$hashCid])
                ->where('active', 'Y')
                ->first();

            if (!$user) {
                return redirect()->route('login')->with('error', 'ไม่พบรายชื่อหรือผู้ใช้งานนี้ในระบบ H-RiMS (เลขบัตรประชาชนของท่านยังไม่ผ่านการลงทะเบียนประวัติในระบบ)');
            }

            // 7. Log in the user into Laravel session
            Auth::loginUsingId($user->id);

            // Store FDH token in session for hospital claims API calls if available
            $orgData = $profileData['organization'] ?? [];
            if (!empty($orgData) && is_array($orgData)) {
                $mophFdhToken = $orgData[0]['moph_access_token_idp_fdh'] ?? null;
                if (!empty($mophFdhToken)) {
                    session(['moph_fdh_token' => $mophFdhToken]);
                }
            }

            return redirect()->intended(route('home'))->with('provider_login_success', 'ยินดีต้อนรับคุณ ' . $user->name);

        } catch (\Exception $e) {
            Log::error('Provider ID Authentication Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('login')->with('error', 'ระบบขัดข้องชั่วคราวในการยืนยันตัวตนกับทางกระทรวงฯ');
        }
    }
}
