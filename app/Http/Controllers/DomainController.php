<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DomainController extends Controller
{
    /**
     * Display a listing of all registered domains for admin.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $search = $request->query('q');
        $statusFilter = $request->query('status');

        $query = Domain::with(['user', 'product', 'order'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', '%' . $search . '%')
                  ->orWhere('ip_address', 'like', '%' . $search . '%')
                  ->orWhere('status', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('email', 'like', '%' . $search . '%')
                         ->orWhere('telegram_username', 'like', '%' . $search . '%')
                         ->orWhere('telegram_id', 'like', '%' . $search . '%')
                         ->orWhere('account_key', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('pid', 'like', '%' . $search . '%')
                         ->orWhere('slug', 'like', '%' . $search . '%');
                  });

                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $domains = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Domain::count(),
            'active' => Domain::where('status', 'active')->count(),
            'inactive' => Domain::where('status', '!=', 'active')->count(),
            'total_hits' => (int) Domain::sum('hits'),
        ];

        return view('admin.domain.index', compact('user', 'domains', 'search', 'statusFilter', 'stats'));
    }

    /**
     * Display the specified domain details.
     */
    public function show(Request $request, string $id): View|JsonResponse
    {
        $user = Auth::user();
        $domain = Domain::with(['user', 'product', 'order'])->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'domain' => $domain,
            ]);
        }

        return view('admin.domain.show', compact('user', 'domain'));
    }

    /**
     * Update domain parameters (domain hostname, status, ip_address).
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $domain = Domain::findOrFail($id);

        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive,suspended'],
            'ip_address' => ['nullable', 'string', 'max:64'],
        ]);

        $updateData = [
            'status' => $validated['status'],
        ];

        if (!empty($validated['domain'])) {
            $cleanDomain = strtolower(trim($validated['domain']));
            $cleanDomain = preg_replace('/^https?:\/\//i', '', $cleanDomain);
            $cleanDomain = explode('/', $cleanDomain)[0];
            $cleanDomain = explode(':', $cleanDomain)[0];
            if (!empty($cleanDomain)) {
                $updateData['domain'] = $cleanDomain;
            }
        }

        if ($request->has('ip_address')) {
            $updateData['ip_address'] = $validated['ip_address'] ? trim($validated['ip_address']) : null;
        }

        $domain->update($updateData);

        return redirect()->back()
            ->with('status', "Domain '{$domain->domain}' updated successfully!");
    }

    /**
     * Remove the specified domain from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $domain = Domain::findOrFail($id);
        $domainName = $domain->domain;
        $domain->delete();

        return redirect()->route('domain.index')
            ->with('status', "Domain '{$domainName}' has been deleted.");
    }

    /**
     * Handle the incoming domain validation request.
     *
     * Retrieve POST input:
     * - account_key
     * - domain
     * - pid (product)
     *
     * Logic flow:
     * 1. Check User from account_key.
     * 2. Check orders where user_id and pid.
     * 3. Check domain from Domain table:
     *    - If exists: return response success.
     *    - If does not exist: check domain_quota from orders.
     *      - If limit exceeded: return error.
     *      - Else: register/add domain and return response success.
     *
     * JSON Response format:
     * "success" => true/false, "status" => "OK/FAILED", "message" => response message
     */
    public function __invoke(Request $request): JsonResponse
    {
        $accountKey = $request->input('account_key');
        $domainInput = $request->input('domain');
        $pid = $request->input('pid') ?? $request->input('product_id');

        if (empty($accountKey) || empty($domainInput) || empty($pid)) {
            return response()->json([
                'success' => false,
                'status' => 'FAILED',
                'message' => 'Missing required parameter(s): account_key, domain, and pid are required.',
            ], 400);
        }

        // 1. First check User from account_key
        $user = User::where('account_key', $accountKey)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'status' => 'FAILED',
                'message' => 'User not found or invalid account key.',
            ], 404);
        }

        // 2. Check orders where user_id and pid
        $product = Product::where('id', $pid)->orWhere('pid', $pid)->first();
        $productId = $product ? $product->id : $pid;

        $order = Order::where('user_id', $user->id)
            ->where(function ($query) use ($pid, $productId) {
                $query->where('product_id', $productId);
                if (!is_null($pid)) {
                    $query->orWhere('product_id', $pid);
                }
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'status' => 'FAILED',
                'message' => 'No order found for this user and product.',
            ], 404);
        }

        // Clean & normalize domain input
        $cleanDomain = strtolower(trim($domainInput));
        $cleanDomain = preg_replace('/^https?:\/\//i', '', $cleanDomain);
        $cleanDomain = explode('/', $cleanDomain)[0];
        $cleanDomain = explode(':', $cleanDomain)[0];

        if (empty($cleanDomain)) {
            return response()->json([
                'success' => false,
                'status' => 'FAILED',
                'message' => 'Invalid domain format.',
            ], 400);
        }

        // 3. Check domain from Domain table
        $existingDomain = Domain::where('user_id', $user->id)
            ->where('domain', $cleanDomain)
            ->first();

        if ($existingDomain) {
            $existingDomain->incrementHits();
            return response()->json([
                'success' => true,
                'status' => 'OK',
                'message' => 'Domain is valid and registered.',
            ]);
        }

        // 4. Before adding domain, check domain_quota from orders
        $domainQuota = (int) ($order->domain_quota ?? 3);
        $currentDomainCount = Domain::where('user_id', $user->id)
            ->where(function ($query) use ($productId, $order) {
                $query->where('product_id', $productId)
                      ->orWhere('order_id', $order->id);
            })
            ->count();

        if ($currentDomainCount >= $domainQuota) {
            return response()->json([
                'success' => false,
                'status' => 'FAILED',
                'message' => 'Domain quota limit exceeded.',
            ], 403);
        }

        // Register / add domain
        Domain::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'order_id' => $order->id,
            'domain' => $cleanDomain,
            'status' => 'active',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'status' => 'OK',
            'message' => 'Domain registered and validated successfully.',
        ]);
    }
}
