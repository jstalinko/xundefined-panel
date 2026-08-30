<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of orders for admin.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('q');
        $status = $request->query('status');

        $query = Order::with(['user', 'product'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('email', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Order::count(),
            'completed' => Order::where('status', 'completed')->count(),
            'revenue' => Order::where('status', 'completed')->sum('price'),
        ];

        return view('admin.order.index', compact('user', 'orders', 'search', 'status', 'stats'));
    }

    /**
     * Show order details.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $order = Order::with(['user', 'product'])->findOrFail($id);

        return view('admin.order.show', compact('user', 'order'));
    }

    /**
     * Update order details (domain_quota, status).
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'domain_quota' => ['required', 'integer', 'min:0', 'max:9999'],
            'status' => ['nullable', 'string', 'in:pending,processing,completed,cancelled'],
        ]);

        $updateData = [
            'domain_quota' => (int) $validated['domain_quota'],
        ];

        if (!empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        $order->update($updateData);

        return redirect()->route('order.index')
            ->with('status', "Order '{$order->invoice}' domain quota updated to {$order->domain_quota} successfully!");
    }
}
