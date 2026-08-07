<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;

class TermsController extends Controller
{
    public function show(CartService $cartService)
    {
        return view('shop.terms-conditions', [
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
