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
        'password' => 'nullable|min:6'
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
        ];

        // ถ้ามีการกรอก password ใหม่ ให้ hash แล้วอัปเดต
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'ลบข้อมูลสำเร็จ');
    }
    
}
