<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    // public function create()
    // {
    //     return view('admin.users.create');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'fdh_user' => 'nullable|string|max:255',
            'fdh_pass' => 'nullable|string|max:255',
            'fdh_secretKey' => 'nullable|string|max:255',
            'eclaim_user' => 'nullable|string|max:255',
            'eclaim_pass' => 'nullable|string|max:255',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'active' => $request->active,
            'status' => 'user',
            'password' => Hash::make($request->password),
            'allow_home' => $request->has('allow_home') ? 'Y' : 'N',
            'allow_import' => $request->has('allow_import') ? 'Y' : 'N',
            'allow_check' => $request->has('allow_check') ? 'Y' : 'N',
            'allow_emr' => $request->has('allow_emr') ? 'Y' : 'N',
            'allow_claim_op' => $request->has('allow_claim_op') ? 'Y' : 'N',
            'allow_claim_ip' => $request->has('allow_claim_ip') ? 'Y' : 'N',
            'allow_mishos' => $request->has('allow_mishos') ? 'Y' : 'N',
            'allow_debtor' => $request->has('allow_debtor') ? 'Y' : 'N',
            'allow_debtor_lock' => $request->has('allow_debtor_lock') ? 'Y' : 'N',
            'allow_debtor_acc' => $request->has('allow_debtor_acc') ? 'Y' : 'N',
            'allow_receipt' => $request->has('allow_receipt') ? 'Y' : 'N',
            'cid' => $request->cid,
            'allow_nhso_endpoint' => $request->has('allow_nhso_endpoint') ? 'Y' : 'N',
            'allow_aopod_death' => $request->has('allow_aopod_death') ? 'Y' : 'N',
            'allow_check_right' => $request->has('allow_check_right') ? 'Y' : 'N',
            'allow_hosfin' => $request->has('allow_hosfin') ? 'Y' : 'N',
            'allow_ai_copilot' => $request->has('allow_ai_copilot') ? 'Y' : 'N',
            'allow_export_f16_eclaim' => $request->has('allow_export_f16_eclaim') ? 'Y' : 'N',
            'allow_export_f16_fdh' => $request->has('allow_export_f16_fdh') ? 'Y' : 'N',
            'allow_export_ssop' => $request->has('allow_export_ssop') ? 'Y' : 'N',
            'allow_export_aipn' => $request->has('allow_export_aipn') ? 'Y' : 'N',
            'allow_export_csop' => $request->has('allow_export_csop') ? 'Y' : 'N',
            'allow_export_cipn' => $request->has('allow_export_cipn') ? 'Y' : 'N',
            'fdh_user' => $request->filled('fdh_user') ? trim($request->fdh_user) : null,
            'fdh_pass' => $request->filled('fdh_pass') ? trim($request->fdh_pass) : null,
            'fdh_secretKey' => $request->filled('fdh_secretKey') ? trim($request->fdh_secretKey) : null,
            'eclaim_user' => $request->filled('eclaim_user') ? trim($request->eclaim_user) : null,
            'eclaim_pass' => $request->filled('eclaim_pass') ? trim($request->eclaim_pass) : null,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'เพิ่มข้อมูลสำเร็จ');
    }

    // public function edit(User $user)
    // {
    //     return view('admin.users.edit', compact('user'));
    // }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
            'fdh_user' => 'nullable|string|max:255',
            'fdh_pass' => 'nullable|string|max:255',
            'fdh_secretKey' => 'nullable|string|max:255',
            'eclaim_user' => 'nullable|string|max:255',
            'eclaim_pass' => 'nullable|string|max:255',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'active' => $request->has('active') ? 'Y' : 'N',
            'status' => $request->status,
            'allow_home' => $request->has('allow_home') ? 'Y' : 'N',
            'allow_import' => $request->has('allow_import') ? 'Y' : 'N',
            'allow_check' => $request->has('allow_check') ? 'Y' : 'N',
            'allow_emr' => $request->has('allow_emr') ? 'Y' : 'N',
            'allow_claim_op' => $request->has('allow_claim_op') ? 'Y' : 'N',
            'allow_claim_ip' => $request->has('allow_claim_ip') ? 'Y' : 'N',
            'allow_mishos' => $request->has('allow_mishos') ? 'Y' : 'N',
            'allow_debtor' => $request->has('allow_debtor') ? 'Y' : 'N',
            'allow_debtor_lock' => $request->has('allow_debtor_lock') ? 'Y' : 'N',
            'allow_debtor_acc' => $request->has('allow_debtor_acc') ? 'Y' : 'N',
            'allow_receipt' => $request->has('allow_receipt') ? 'Y' : 'N',
            'cid' => $request->cid,
            'allow_nhso_endpoint' => $request->has('allow_nhso_endpoint') ? 'Y' : 'N',
            'allow_aopod_death' => $request->has('allow_aopod_death') ? 'Y' : 'N',
            'allow_check_right' => $request->has('allow_check_right') ? 'Y' : 'N',
            'allow_hosfin' => $request->has('allow_hosfin') ? 'Y' : 'N',
            'allow_ai_copilot' => $request->has('allow_ai_copilot') ? 'Y' : 'N',
            'allow_export_f16_eclaim' => $request->has('allow_export_f16_eclaim') ? 'Y' : 'N',
            'allow_export_f16_fdh' => $request->has('allow_export_f16_fdh') ? 'Y' : 'N',
            'allow_export_ssop' => $request->has('allow_export_ssop') ? 'Y' : 'N',
            'allow_export_aipn' => $request->has('allow_export_aipn') ? 'Y' : 'N',
            'allow_export_csop' => $request->has('allow_export_csop') ? 'Y' : 'N',
            'allow_export_cipn' => $request->has('allow_export_cipn') ? 'Y' : 'N',
            'fdh_user' => $request->filled('fdh_user') ? trim($request->fdh_user) : null,
            'fdh_pass' => $request->filled('fdh_pass') ? trim($request->fdh_pass) : null,
            'fdh_secretKey' => $request->filled('fdh_secretKey') ? trim($request->fdh_secretKey) : null,
            'eclaim_user' => $request->filled('eclaim_user') ? trim($request->eclaim_user) : null,
            'eclaim_pass' => $request->filled('eclaim_pass') ? trim($request->eclaim_pass) : null,
        ];

        // ถ้ามีการกรอก password ใหม่ ให้ hash แล้วอัปเดต
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('12345678')
        ]);
        return redirect()->route('admin.users.index')->with('success', 'รีเซ็ตรหัสผ่านเป็น 12345678 สำเร็จ');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'ลบข้อมูลสำเร็จ');
    }
    
}
