<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Bangsamu\Sso\Controllers\SsoController;
use App\Models\User;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request)
    {
        $token = $request->route()->parameter('token');
        // dd($token);

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }


    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $request->validate($this->rules(), $this->validationErrorMessages());


        if (config('SsoConfig.main.ACTIVE')) {


            $sso = new SsoController();
            $attempt = $sso->reset($request);
            $sso_cek = (object)$attempt;

            if (@$sso_cek->status == 'sukses') {
                $user = user::where('email',$sso_cek->data['email'])->first();
                if ($user) {
                    $this->guard()->login($user);
                } else {

                    $user = @(object)$attempt['data'];

                    $data['name'] = $user->name;
                    $data['email'] = $user->email;
                    $data['is_active'] = $user->is_active;
                    $data['password'] = Hash::make($request->password);

                    $register = new RegisterController;
                    $create_user = $register->create($data);
                    if ($create_user) {
                        $user_id =  $create_user->id;
                        \Auth::loginUsingId($user_id);
                    }else{
                        //reset password
                        $response = 'Your account has not been registered, please contact the administrator';
                        abort(403, $response);
                    }
                }
            }

            switch (@$sso_cek->status) {
                case "sukses":
                    $response = 'passwords.reset';
                    break;
                case "gagal":
                    if ($sso_cek->code == '401') {
                        $response = 'passwords.user';
                    } else {
                        $response = 'passwords.token';
                    };
                    break;
                default:
                    $response = 'passwords.token';
            }

            // set auto login jika sukses
        } else {
            // dd($h,$request->token,99, $c, $c1);
            // Here we will attempt to reset the user's password. If it is successful we
            // will update the password on an actual user model and persist it to the
            // database. Otherwise we will parse the error and return the response.
            $response = $this->broker()->reset(
                $a = $this->credentials($request),
                function ($user, $password) {
                    $a = $this->resetPassword($user, $password);
                    // dd($a);
                    //null
                }
            );
        }


        // dd($response);
        // passwords.token //expierd
        // passwords.user //gagal
        // passwords.reset //sukses
        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
            ? $this->sendResetResponse($request, $response)
            : $this->sendResetFailedResponse($request, $response);
    }
}
