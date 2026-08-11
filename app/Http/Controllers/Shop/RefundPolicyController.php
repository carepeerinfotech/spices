<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;

class RefundPolicyController extends Controller
{
    public function show(CartService $cartService)
    {
        return view('shop.refund-policy', [
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
