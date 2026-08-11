<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use App\Support\Features;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('account.auth.login');
    }

    public function login(Request $request, CartService $cartService)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Auth::attempt regenerates the session id; capture guest cart key first.
        $guestSessionId = $request->session()->getId();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
                'errors' => ['email' => ['Invalid email or password.']],
            ], 422);
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();

            return response()->json(['success' => false, 'message' => 'Account is inactive.'], 403);
        }

        $cartService->mergeSessionCartIntoUser($user->id, $guestSessionId);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Welcome back.',
            'redirect' => $request->session()->pull('url.intended', route('account.dashboard')),
        ]);
    }

    public function showRegister()
    {
        return view('account.auth.register');
    }

    public function register(Request $request, CartService $cartService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
            'is_customer' => true,
        ]);

        $guestSessionId = $request->session()->getId();
        $verificationEnabled = Features::emailVerification();

        if ($verificationEnabled) {
            event(new Registered($user));
        }

        Auth::login($user);
        $cartService->mergeSessionCartIntoUser($user->id, $guestSessionId);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => $verificationEnabled
                ? 'Account created. Please verify your email.'
                : 'Account created.',
            'redirect' => $verificationEnabled
                ? route('verification.notice')
                : route('account.dashboard'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop.home');
    }
}
