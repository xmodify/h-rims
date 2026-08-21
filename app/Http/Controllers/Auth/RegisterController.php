<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;


class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */

    // protected $redirectTo = '/login';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('guest');
    // }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],            
            'cid' => ['nullable', 'string', 'digits:13', 'unique:users'],
        ], [
            'name.required' => 'โปรดกรอกชื่อ - นามสกุลจริง',
            'email.required' => 'โปรดกรอกที่อยู่อีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานในระบบแล้ว',
            'password.required' => 'โปรดกรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย :min ตัวอักษร',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
            'cid.digits' => 'เลขบัตรประชาชนต้องมีจำนวน 13 หลัก',
            'cid.unique' => 'เลขบัตรประชาชนนี้ถูกใช้งานในระบบแล้ว',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'active' => $data['active'] ?? 'N',
            'status' => $data['status'] ?? 'user',
            'password' => Hash::make($data['password']),
            'cid' => $data['cid'] ?? null,
        ]);               
    }

    protected function registered(Request $request, $user)
    {
        // logout ถ้าไม่ต้องการ login หลัง register
        Auth::logout();

        // ส่ง session flash
        return redirect('/login')->with('register_success', 'รอ Admin ดำเนินการ');
     
    }

}
