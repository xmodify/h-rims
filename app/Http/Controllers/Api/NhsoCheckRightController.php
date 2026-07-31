<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\LicenseVerificationService;

class NhsoCheckRightController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_check_right !== 'Y') {
                    return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ใช้งานโมดูลตรวจสอบสิทธินี้'], 403);
                }
                
                if (!LicenseVerificationService::isLicensed()) {
                    return response()->json(['status' => 'error', 'message' => 'ระบบระงับการทำงานชั่วคราว: สำหรับ License เท่านั้น กรุณาลงทะเบียน License ที่เมนูตั้งค่าระบบ'], 403);
                }
                
                return $next($request);
            }
        ]);
    }

    /**
     * ดึงข้อมูล Token ท้องถิ่นส่งให้หน้าบ้าน
     */
    public function loadLocalToken(Request $request)
    {
        $localTokens = $this->getLocalTokens();
        if ($localTokens && !empty($localTokens['access-token'])) {
            $accessToken = $localTokens['access-token'];
            $expiresAt = null;
            
            // Parse JWT payload to extract expiration time (exp)
            $parts = explode('.', $accessToken);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                if (isset($payload['exp'])) {
                    $expiresAt = $payload['exp'];
                }
            }

            return response()->json([
                'status' => 'success',
                'access_token' => $accessToken,
                'refresh_token' => $localTokens['refresh-token'] ?? '',
                'expires_at' => $expiresAt
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'ไม่พบไฟล์ token.txt หรือไม่สามารถอ่านไฟล์คีย์ในเครื่องท้องถิ่นได้'
        ], 404);
    }

    /**
     * ค้นหาสิทธิ์ผู้รับบริการผ่าน API สปสช. (SRM)
     */
    public function search(Request $request)
    {
        $request->validate([
            'pid' => 'required|digits:13',
            'environment' => 'required|in:production,test'
        ]);

        $pid = $request->input('pid');
        $env = $request->input('environment');
        
        // 1. ดึง Token: จาก request หรือจากไฟล์ในระบบ
        $accessToken = $request->input('access_token');
        $refreshToken = $request->input('refresh_token');
        
        $localTokens = $this->getLocalTokens();
        
        if (empty($accessToken) && $localTokens) {
            $accessToken = $localTokens['access-token'] ?? null;
        }
        if (empty($refreshToken) && $localTokens) {
            $refreshToken = $localTokens['refresh-token'] ?? null;
        }

        if (empty($accessToken)) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบ Access Token ในระบบ กรุณาอัปโหลดหรือนำเข้าไฟล์ token.txt'
            ], 400);
        }

        // 2. เรียกใช้งาน API สปสช.
        $baseUrl = ($env === 'test') ? 'https://tsrm.nhso.go.th' : 'https://srm.nhso.go.th';
        $endpoint = "{$baseUrl}/api/ucws/v1/right-search";

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->timeout(10)
            ->get($endpoint, ['pid' => $pid]);

        // 3. จัดการกรณี Token หมดอายุ (401 Unauthorized หรือ Response แจ้งว่า expired)
        if ($response->status() === 401 || (isset($response->json()['message']) && strpos(strtolower($response->json()['message']), 'token') !== false)) {
            if (!empty($refreshToken)) {
                // พยายาม Refresh Token อัตโนมัติ
                $refreshResult = $this->performTokenRefresh($refreshToken, $env);
                if ($refreshResult && isset($refreshResult['access-token'])) {
                    $newAccessToken = $refreshResult['access-token'];
                    $newRefreshToken = $refreshResult['refresh-token'] ?? $refreshToken;

                    // เขียนลงไฟล์ %userprofile% หากใช้งานไฟล์ในเครื่อง
                    if ($localTokens) {
                        $this->saveLocalTokens($newAccessToken, $newRefreshToken);
                    }

                    // เรียกใช้ API สปสช. อีกครั้งด้วย Token ใหม่
                    $retryResponse = Http::withoutVerifying()
                        ->withToken($newAccessToken)
                        ->timeout(10)
                        ->get($endpoint, ['pid' => $pid]);

                    if ($retryResponse->successful()) {
                        return response()->json([
                            'status' => 'success',
                            'data' => $retryResponse->json(),
                            'token_refreshed' => true,
                            'access_token' => $newAccessToken,
                            'refresh_token' => $newRefreshToken
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => 'error',
                'error_type' => 'token_expired',
                'message' => 'Access Token หมดอายุ หรือไม่ถูกต้อง กรุณาตรวจสอบหรือขอ Token ใหม่จากระบบ Smart Card SSO'
            ], 401);
        }

        if ($response->failed()) {
            $errData = $response->json();
            return response()->json([
                'status' => 'error',
                'message' => $errData['message'] ?? 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์ สปสช. (HTTP ' . $response->status() . ')'
            ], $response->status() ?: 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $response->json()
        ]);
    }

    /**
     * ต่ออายุ Token (Refresh Token)
     */
    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required',
            'environment' => 'required|in:production,test'
        ]);

        $refreshToken = $request->input('refresh_token');
        $env = $request->input('environment');

        $result = $this->performTokenRefresh($refreshToken, $env);
        if ($result && isset($result['access-token'])) {
            // เขียนลงไฟล์หากเครื่องเป็น Local
            $localTokens = $this->getLocalTokens();
            if ($localTokens) {
                $this->saveLocalTokens($result['access-token'], $result['refresh-token'] ?? $refreshToken);
            }

            return response()->json([
                'status' => 'success',
                'access_token' => $result['access-token'],
                'refresh_token' => $result['refresh-token'] ?? $refreshToken
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'ไม่สามารถต่ออายุ Token ได้ กรุณาเชื่อมต่อโปรแกรม Smart Card SSO ใหม่อีกครั้ง'
        ], 400);
    }

    /**
     * ส่งคำขอ Refresh Token ไปยัง สปสช.
     */
    private function performTokenRefresh($refreshToken, $env)
    {
        $host = ($env === 'test') ? 'https://tsrmportal.nhso.go.th' : 'https://srmportal.nhso.go.th';
        $endpoint = "{$host}/api/scard/access-token";

        try {
            $response = Http::withoutVerifying()
                ->asForm()
                ->timeout(10)
                ->post($endpoint, [
                    'refresh_token' => $refreshToken
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'access-token' => $data['access-token'] ?? ($data['access_token'] ?? null),
                    'refresh-token' => $data['refresh-token'] ?? ($data['refresh_token'] ?? $refreshToken)
                ];
            }
        } catch (\Throwable $e) {
            Log::error('NHSO Token Refresh Network Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * อ่านไฟล์ token.txt ท้องถิ่น (ถ้ามีสิทธิ์เข้าถึง)
     */
    private function getLocalTokens()
    {
        $userprofile = getenv('USERPROFILE') ?: ($_SERVER['USERPROFILE'] ?? null);
        if (empty($userprofile)) {
            // ลองใช้ home drive + path
            $homedrive = getenv('HOMEDRIVE') ?: 'C:';
            $homepath = getenv('HOMEPATH') ?: '';
            if ($homepath) {
                $userprofile = $homedrive . $homepath;
            }
        }

        if ($userprofile) {
            $path = $userprofile . DIRECTORY_SEPARATOR . 'SRM Smart Card Single Sign-On' . DIRECTORY_SEPARATOR . 'token.txt';
            if (file_exists($path)) {
                try {
                    $content = file_get_contents($path);
                    $lines = explode("\n", str_replace("\r", "", $content));
                    $tokens = [];
                    foreach ($lines as $line) {
                        if (strpos($line, '=') !== false) {
                            list($k, $v) = explode('=', $line, 2);
                            $tokens[trim($k)] = trim($v);
                        }
                    }
                    if (isset($tokens['access-token']) || isset($tokens['refresh-token'])) {
                        return $tokens;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Cannot read local token.txt: ' . $e->getMessage());
                }
            }
        }

        return null;
    }

    /**
     * บันทึกไฟล์ token.txt ท้องถิ่น
     */
    private function saveLocalTokens($accessToken, $refreshToken)
    {
        $userprofile = getenv('USERPROFILE') ?: ($_SERVER['USERPROFILE'] ?? null);
        if ($userprofile) {
            $path = $userprofile . DIRECTORY_SEPARATOR . 'SRM Smart Card Single Sign-On' . DIRECTORY_SEPARATOR . 'token.txt';
            if (file_exists($path) && is_writable($path)) {
                try {
                    $content = "access-token={$accessToken}\nrefresh-token={$refreshToken}\n";
                    file_put_contents($path, $content);
                    return true;
                } catch (\Throwable $e) {
                    Log::warning('Cannot write local token.txt: ' . $e->getMessage());
                }
            }
        }
        return false;
    }
}
