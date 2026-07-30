<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\PublicStorageLink;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard.index', [
            'stats' => [
                'products' => Product::count(),
                'orders' => Order::count(),
                'users' => User::count(),
                'pages' => CmsPage::count(),
                'revenue' => (float) Order::whereNotIn('status', ['cancelled'])->sum('total'),
                'pending_orders' => Order::where('status', 'pending')->count(),
            ],
            'recentOrders' => Order::latest()->take(5)->get(),
            'storageReady' => PublicStorageLink::isReady(),
        ]);
    }
}
