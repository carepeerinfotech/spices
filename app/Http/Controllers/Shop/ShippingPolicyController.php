<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;

class ShippingPolicyController extends Controller
{
    public function show(CartService $cartService)
    {
        return view('shop.shipping-policy', [
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
