<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;

class PrivacyPolicyController extends Controller
{
    public function show(CartService $cartService)
    {
        return view('shop.privacy-policy', [
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
