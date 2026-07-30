<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['success' => true, 'message' => 'Password changed.']);
    }
}
