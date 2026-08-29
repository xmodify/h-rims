<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ProfileController extends Controller
{
    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with('success', 'เปลี่ยนรหัสผ่านสำเร็จแล้ว!');
    }

    /**
     * Update the user's profile information and personal FDH credentials.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'cid' => ['nullable', 'string', 'max:13'],
            'fdh_user' => ['nullable', 'string', 'max:255'],
            'fdh_pass' => ['nullable', 'string', 'max:255'],
            'fdh_secretKey' => ['nullable', 'string', 'max:255'],
        ]);

        $updateData = [
            'cid' => $request->cid ? trim($request->cid) : null,
            'fdh_user' => $request->fdh_user ? trim($request->fdh_user) : null,
            'fdh_pass' => $request->fdh_pass ? trim($request->fdh_pass) : null,
            'fdh_secretKey' => $request->fdh_secretKey ? trim($request->fdh_secretKey) : null,
        ];

        $user->update($updateData);

        return back()->with('success', 'บันทึกข้อมูลโปรไฟล์สำเร็จแล้ว!');
    }

    /**
     * Test FDH Access Token generation with user credentials
     */
    public function testFdhToken(Request $request)
    {
        $settings = DB::table('main_setting')
            ->pluck('value', 'name')
            ->toArray();

        // ดึงเฉพาะจาก 3 ช่องของ User เท่านั้น ห้ามดึง user/pass จาก main_setting
        $user = $request->filled('fdh_user') 
            ? trim($request->fdh_user) 
            : (Auth::user()->fdh_user ?: null);

        $password = $request->filled('fdh_pass') 
            ? trim($request->fdh_pass) 
            : (Auth::user()->fdh_pass ?: null);

        $secretKey = $request->filled('fdh_secretKey')
            ? trim($request->fdh_secretKey)
            : (Auth::user()->fdh_secretKey ?: null);

        if (!$user || !$password || !$secretKey) {
            return response()->json([
                'status' => 'failed',
                'message' => 'กรุณากรอก FDH User, FDH Pass และ FDH Secret Key ใน 3 ช่องนี้ให้ครบถ้วนเพื่อทำการทดสอบ'
            ]);
        }

        // ดึงรหัสโรงพยาบาล (Hospital Code) จากชื่อ User เช่น aonpeeya.10987 หรือจาก main_setting
        $userParts = explode('.', $user);
        $hcode = (count($userParts) > 1 && is_numeric(end($userParts))) 
            ? end($userParts) 
            : ($settings['hospital_code'] ?? ($settings['hcode'] ?? null));

        if (!$hcode) {
            return response()->json([
                'status' => 'failed',
                'message' => 'ไม่พบรหัสโรงพยาบาล (Hospital Code)'
            ]);
        }

        $hash = strtoupper(hash_hmac('sha256', $password, $secretKey));
        $apiUrl = 'https://fdh.moph.go.th/token?Action=get_moph_access_token';

        try {
            $response = Http::withOptions([
                'verify' => false
            ])->withHeaders([
                "Accept" => "application/json",
                "Content-Type" => "application/json"
            ])->post($apiUrl, [
                'user'          => $user,
                'password_hash' => $hash,
                'hospital_code' => $hcode
            ]);

            if ($response->successful()) {
                $token = trim($response->body());
                return response()->json([
                    'status' => 'success',
                    'token' => $token,
                    'user' => $user
                ]);
            } else {
                $body = $response->json();
                $msg = $body['Message'] ?? ($body['message'] ?? 'User/Password Invalid หรือไม่สามารถเชื่อมต่อ FDH ได้');
                return response()->json([
                    'status' => 'failed',
                    'message' => $msg
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }
}
