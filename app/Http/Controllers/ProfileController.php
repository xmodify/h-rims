<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'cid' => ['nullable', 'string', 'max:13'],
            'fdh_user' => ['nullable', 'string', 'max:255'],
            'fdh_pass' => ['nullable', 'string', 'max:255'],
            'fdh_secretKey' => ['nullable', 'string', 'max:255'],
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'cid' => $request->cid ? trim($request->cid) : null,
            'fdh_user' => $request->fdh_user ? trim($request->fdh_user) : null,
            'fdh_pass' => $request->fdh_pass ? trim($request->fdh_pass) : null,
            'fdh_secretKey' => $request->fdh_secretKey ? trim($request->fdh_secretKey) : null,
        ];

        $user->update($updateData);

        return back()->with('success', 'บันทึกข้อมูลโปรไฟล์สำเร็จแล้ว!');
    }
}
