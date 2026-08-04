<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscriber::firstOrCreate(['email' => $data['email']]);

        return response()->json([
            'success' => true,
            'message' => "Thanks for subscribing! Watch your inbox for your welcome discount.",
        ]);
    }
}
