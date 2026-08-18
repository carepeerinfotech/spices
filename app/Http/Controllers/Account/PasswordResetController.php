<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Mail\TemplateMailer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function showLinkRequest()
    {
        return view('account.auth.forgot-password');
    }

    public function sendResetLink(Request $request, TemplateMailer $mailer)
    {
        $request->validate(['email' => ['required', 'email']]);

        // The reset link rides Laravel's own notification, which does not know
        // about the SMTP credentials held in admin settings.
        $mailer->applyMailConfig();

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => false,
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __($status),
        ]);
    }

    public function showReset(Request $request, string $token)
    {
        $email = (string) $request->query('email', '');

        // Check the token before rendering, so a used or expired link says so
        // straight away instead of failing after the customer fills the form in.
        if (! $this->tokenIsValid($email, $token)) {
            $back = $request->user() ? route('account.dashboard') : route('password.request');

            return redirect($back)->with('error', 'That password reset link has expired or has already been used. Please request a new one.');
        }

        return view('account.auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    private function tokenIsValid(string $email, string $token): bool
    {
        if ($email === '' || $token === '') {
            return false;
        }

        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker();
        $user = $broker->getUser(['email' => $email]);

        return $user !== null && $broker->tokenExists($user, $token);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __($status),
            // Someone who reset from inside their account is still signed in;
            // sending them to the login page would only bounce them home.
            'redirect' => $request->user() ? route('account.dashboard') : route('login'),
        ]);
    }
}
