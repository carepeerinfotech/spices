<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\Mail\TemplateMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class DashboardController extends Controller
{
    public function index(CartService $cartService)
    {
        $user = auth()->user()->load(['addresses', 'orders' => fn ($q) => $q->latest()->take(5)]);

        return view('account.dashboard', [
            'user' => $user,
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->update($data);

        return response()->json(['success' => true, 'message' => 'Profile updated.']);
    }

    /**
     * Mail the signed-in customer a reset link instead of asking for their
     * current password — an account created during checkout has a generated
     * password its owner has never seen.
     */
    public function sendPasswordResetLink(Request $request, TemplateMailer $mailer)
    {
        // The reset link rides Laravel's own notification, which does not know
        // about the SMTP credentials held in admin settings.
        $mailer->applyMailConfig();

        $status = Password::sendResetLink(['email' => $request->user()->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json(['success' => false, 'message' => __($status)], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'We have emailed you a link to set a new password.',
        ]);
    }

    /**
     * Fallback for stores running with the password-reset feature switched off,
     * where no emailed link would resolve.
     */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['success' => true, 'message' => 'Password changed.']);
    }
}
