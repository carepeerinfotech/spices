<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;

class AboutController extends Controller
{
    public function show(CartService $cartService)
    {
        return view('shop.about', [
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
