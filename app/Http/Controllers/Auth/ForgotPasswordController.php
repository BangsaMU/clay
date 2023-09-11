<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

use Illuminate\Support\Facades\Password;
use Bangsamu\Sso\Controllers\SsoController;
class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        if (config('SsoConfig.main.ACTIVE')) {
            //sso reset
            $sso = new SsoController();
            $attempt = $sso->forgot($request);
            $sso_cek = (object)$attempt;

            // dd(config('SsoConfig.main.URL', url('/')) . 'forgot_password',$sso_cek->object());

            switch (@$sso_cek->status) {
                case "sukses":
                    $response = 'passwords.sent';
                    break;
                case "gagal":
                    $response = 'passwords.user';
                    break;
                default:
                    $response = 'passwords.throttled';
            }

            // dd($response);
            // "passwords.user" user ga ada
            // "passwords.throttled"
            // dd(Password::RESET_LINK_SENT);
            // "passwords.sent"
            // 'reset' => 'Your password has been reset!',
            // 'sent' => 'We have emailed your password reset link!',
            // 'throttled' => 'Please wait before retrying.',
            // 'token' => 'This password reset token is invalid.',
            // 'user' => "We can't find a user with that email address.",

            return $response == Password::RESET_LINK_SENT
                ? $this->sendResetLinkResponse($request, $response)
                : $this->sendResetLinkFailedResponse($request, $response);
        } else {
            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );
            // dd($response);
            return $response == Password::RESET_LINK_SENT
                ? $this->sendResetLinkResponse($request, $response)
                : $this->sendResetLinkFailedResponse($request, $response);
        }
    }
}
