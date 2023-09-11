<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

use Bangsamu\Sso\Controllers\SsoController;
use App\Models\User;
// use app\Http\Controllers\Auth\RegisterController;
class LoginController extends Controller
{

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function attemptLoginSso(Request $request)
    {
        $sso = new SsoController();
        $attempt = $sso->auth($request);

        if ($attempt) {
            $user = @(object)$attempt['data'];
            $is_login = @$user->is_active == 1 ? true : false;
            $user_id = @$user->id;

            if ($user_id) {
                $token = $attempt['data']['token'];
                if ($request->hasSession()) {
                    $request->session()->put('auth.token', $token);
                }

                // $loginbyId =  \Auth::loginUsingId($user_id);
                $loginbyId = user::where('email', $user->email)->first();

                if ($loginbyId){
                    $user_id =  $loginbyId->id;
                    \Auth::loginUsingId($user_id);
                }else {
                    // $data['id'] = $user->id;
                    $data['name'] = $user->name;
                    $data['email'] = $user->email;
                    $data['is_active'] = $user->is_active;
                    $data['password'] = \Hash::make($request->password);

                    // $register = new RegisterController;
                    $create_user = $sso->create($data);
                    if ($create_user) {
                        $user_id =  $create_user->id;
                        \Auth::loginUsingId($user_id);
                    } else {
                        //reset password
                        $response = 'Your account has not been registered, please contact the administrator';
                        abort(403, $response);
                    }
                }
            }
        } else {
            $is_login = false;
        }
        return $is_login;
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (
            method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)
        ) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }
        // dd(config('SsoConfig.main.ACTIVE'));
        if (config('SsoConfig.main.ACTIVE')) {
            $attemptLogin = $this->attemptLoginSso($request);
        } else {
            $attemptLogin = $this->attemptLogin($request);
        }
        // dd(1,$attemptLogin);
        // if ($this->attemptLogin($request)) {
        if ($attemptLogin) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

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
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
