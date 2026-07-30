<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\CartService;

class PageController extends Controller
{
    public function show(string $slug, CartService $cartService)
    {
        $page = CmsPage::published()->where('slug', $slug)->firstOrFail();

        return view('shop.pages.show', [
            'page' => $page,
            'cartCount' => $cartService->getCart()->itemCount(),
        ]);
    }
}
