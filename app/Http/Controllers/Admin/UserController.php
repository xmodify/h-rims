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
