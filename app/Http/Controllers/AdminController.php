<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Display the admin hub overview with statistics, latest orders, and registered domains.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Stats overview metrics
        $stats = [
            'total_users' => User::count(),
            'total_domains' => Domain::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'completed')->sum('price'),
            'total_products' => Product::count(),
            'active_products' => Product::where('active', true)->count(),
        ];

        // Latest Orders (all statuses, max 10)
        $latestOrders = Order::with(['user', 'product'])
            ->latest()
            ->take(10)
            ->get();

        // All Registered Domains with user email and product
        $allDomains = Domain::with(['user', 'product'])
            ->latest()
            ->get();

        return view('admin.index', compact('user', 'stats', 'latestOrders', 'allDomains'));
    }
}
