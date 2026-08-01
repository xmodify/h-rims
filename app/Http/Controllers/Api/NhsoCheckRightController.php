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
        // 1. ลองอ่านไฟล์ในเครื่องก่อน (กรณีใช้งานแบบ Localhost)
        $localTokens = $this->getLocalTokens();
        $accessToken = null;
        $refreshToken = null;

        if ($localTokens && !empty($localTokens['access-token'])) {
            $accessToken = $localTokens['access-token'];
            $refreshToken = $localTokens['refresh-token'] ?? '';
        }

        // 2. ถ้าไฟล์ไม่มี หรือเป็นคีย์ที่หมดอายุ -> ลองดึงจากฐานข้อมูล HOSxP ตาราง nhso_token ของผู้ใช้นี้
        if (empty($accessToken) || $this->isJwtExpired($accessToken)) {
            $cid = Auth::user()->cid ?? null;
            $dbTokens = $this->getHosxpToken($cid, 'production');
            if ($dbTokens) {
                $accessToken = $dbTokens['access_token'];
                $refreshToken = $dbTokens['refresh_token'];
            }
        }

        if (!empty($accessToken)) {
            $expiresAt = null;
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
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'ไม่พบคีย์ในเครื่องท้องถิ่น และไม่พบคีย์ที่พร้อมใช้งานในตาราง nhso_token ของ HOSxP'
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
        
        $accessToken = $request->input('access_token');
        $refreshToken = $request->input('refresh_token');
        $tokenRefreshed = false;

        // 1. ดึง Token: จาก request (ถ้ารับมาแล้วยังไม่หมดอายุ)
        if (empty($accessToken) || $this->isJwtExpired($accessToken)) {
            // 2. ถ้าไม่มี หรือคีย์หมดอายุ ให้ลองอ่านจากไฟล์ท้องถิ่น
            $localTokens = $this->getLocalTokens();
            if ($localTokens && !empty($localTokens['access-token']) && !$this->isJwtExpired($localTokens['access-token'])) {
                $accessToken = $localTokens['access-token'];
                $refreshToken = $localTokens['refresh-token'] ?? null;
                $tokenRefreshed = true;
            } else {
                // 3. ถ้าในไฟล์ท้องถิ่นไม่มี หรือหมดอายุ ให้ดึงจากฐานข้อมูล HOSxP nhso_token
                $cid = Auth::user()->cid ?? null;
                $dbTokens = $this->getHosxpToken($cid, $env);
                if ($dbTokens) {
                    $accessToken = $dbTokens['access_token'];
                    $refreshToken = $dbTokens['refresh_token'];
                    $tokenRefreshed = true;
                }
            }
        }

        if (empty($accessToken)) {
            return response()->json([
                'status' => 'error',
                'error_type' => 'token_expired',
                'message' => 'ไม่พบ Access Token ในระบบ กรุณาซิงค์คีย์เชื่อมต่อจากโปรแกรม SRM Smart Card'
            ], 401);
        }

        // 4. เรียกใช้งาน API สปสช.
        $baseUrl = ($env === 'test') ? 'https://tsrm.nhso.go.th' : 'https://srm.nhso.go.th';
        $endpoint = "{$baseUrl}/api/ucws/v1/right-search";

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->timeout(10)
            ->get($endpoint, ['pid' => $pid]);

        // 5. จัดการกรณี Token หมดอายุ (401 Unauthorized)
        if ($response->status() === 401 || (isset($response->json()['message']) && strpos(strtolower($response->json()['message']), 'token') !== false)) {
            if (!empty($refreshToken)) {
                // พยายาม Refresh Token อัตโนมัติ
                $refreshResult = $this->performTokenRefresh($refreshToken, $env);
                if ($refreshResult && isset($refreshResult['access-token'])) {
                    $newAccessToken = $refreshResult['access-token'];
                    $newRefreshToken = $refreshResult['refresh-token'] ?? $refreshToken;
                    $tokenRefreshed = true;

                    // บันทึกกลับลงไฟล์เครื่อง
                    $localTokens = $this->getLocalTokens();
                    if ($localTokens) {
                        $this->saveLocalTokens($newAccessToken, $newRefreshToken);
                    }

                    // บันทึกกลับลงตาราง nhso_token ใน HOSxP
                    $cid = Auth::user()->cid ?? null;
                    if (!empty($cid)) {
                        try {
                            DB::connection('hosxp')
                                ->table('nhso_token')
                                ->where('cid', $cid)
                                ->update([
                                    'token' => $newAccessToken,
                                    'refresh_token' => $newRefreshToken,
                                    'update_datetime' => now()->format('Y-m-d H:i:s'),
                                    'access_token_expire' => $this->getJwtExpiryDate($newAccessToken),
                                    'refresh_token_expire' => $this->getJwtExpiryDate($newRefreshToken),
                                ]);
                        } catch (\Throwable $e) {
                            Log::warning('HOSxP nhso_token update failed: ' . $e->getMessage());
                        }
                    }

                    // เรียกใช้ API สปสช. อีกครั้งด้วย Token ใหม่
                    $retryResponse = Http::withoutVerifying()
                        ->withToken($newAccessToken)
                        ->timeout(10)
                        ->get($endpoint, ['pid' => $pid]);

                    if ($retryResponse->successful()) {
                        $expiresAt = null;
                        $parts = explode('.', $newAccessToken);
                        if (count($parts) === 3) {
                            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                            $expiresAt = $payload['exp'] ?? null;
                        }

                        return response()->json([
                            'status' => 'success',
                            'data' => $retryResponse->json(),
                            'token_refreshed' => true,
                            'access_token' => $newAccessToken,
                            'refresh_token' => $newRefreshToken,
                            'expires_at' => $expiresAt
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

        // คืนค่าสำเร็จ
        $responseData = [
            'status' => 'success',
            'data' => $response->json()
        ];

        // ถ้ามีการโหลด/รีเฟรช Token ใหม่ ให้ส่งกลับไปอัปเดตหน้าบ้านด้วย
        if ($tokenRefreshed) {
            $expiresAt = null;
            $parts = explode('.', $accessToken);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                $expiresAt = $payload['exp'] ?? null;
            }

            $responseData['token_refreshed'] = true;
            $responseData['access_token'] = $accessToken;
            $responseData['refresh_token'] = $refreshToken;
            $responseData['expires_at'] = $expiresAt;
        }

        return response()->json($responseData);
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
     * ดึง Token จากตาราง nhso_token ของ HOSxP สำหรับเจ้าหน้าที่ปัจจุบัน
     */
    private function getHosxpToken($cid, $env = 'production')
    {
        if (empty($cid)) {
            return null;
        }

        try {
            $hosxpToken = DB::connection('hosxp')
                ->table('nhso_token')
                ->where('cid', $cid)
                ->first();

            if ($hosxpToken) {
                $possibleAccess = $hosxpToken->token;
                $possibleRefresh = $hosxpToken->refresh_token;

                // 1. ถ้าคีย์หลักใน DB เป็น JWT และยังไม่หมดอายุ -> นำไปใช้ได้เลย
                if (str_starts_with($possibleAccess, 'eyJ') && !$this->isJwtExpired($possibleAccess)) {
                    return [
                        'access_token' => $possibleAccess,
                        'refresh_token' => $possibleRefresh
                    ];
                }

                // 2. ถ้าคีย์หลักหมดอายุ/ไม่ใช่ JWT แต่มี Refresh Token ที่ยังไม่หมดอายุ -> ลองต่ออายุ
                if (str_starts_with($possibleRefresh, 'eyJ') && !$this->isJwtExpired($possibleRefresh)) {
                    $refreshResult = $this->performTokenRefresh($possibleRefresh, $env);
                    if ($refreshResult && !empty($refreshResult['access-token'])) {
                        $accessToken = $refreshResult['access-token'];
                        $refreshToken = $refreshResult['refresh-token'] ?? $possibleRefresh;

                        // อัปเดตข้อมูลกลับลงฐานข้อมูล HOSxP
                        DB::connection('hosxp')
                            ->table('nhso_token')
                            ->where('cid', $cid)
                            ->update([
                                'token' => $accessToken,
                                'refresh_token' => $refreshToken,
                                'update_datetime' => now()->format('Y-m-d H:i:s'),
                                'access_token_expire' => $this->getJwtExpiryDate($accessToken),
                                'refresh_token_expire' => $this->getJwtExpiryDate($refreshToken),
                            ]);

                        return [
                            'access_token' => $accessToken,
                            'refresh_token' => $refreshToken
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('HOSxP nhso_token lookup failed: ' . $e->getMessage());
        }

        return null;
    }

    private function isJwtExpired($token)
    {
        if (empty($token) || !str_starts_with($token, 'eyJ')) {
            return true;
        }
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (isset($payload['exp'])) {
                return time() > ($payload['exp'] - 10); // ลบ 10 วินาทีกันเหนียว
            }
        }
        return true;
    }

    private function getJwtExpiryDate($token)
    {
        if (empty($token) || !str_starts_with($token, 'eyJ')) {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (isset($payload['exp'])) {
                return date('Y-m-d H:i:s', $payload['exp']);
            }
        }
        return null;
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
