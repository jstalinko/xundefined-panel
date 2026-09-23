<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Domain;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TelegramApiController extends Controller
{
    /**
     * Auto register or fetch user when /start or new chat occurs.
     */
    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string',
            'telegram_username' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
        ]);

        $telegramId = (string) $validated['telegram_id'];
        $telegramUsername = $validated['telegram_username'] ?? null;
        $name = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        if (empty($name)) {
            $name = $telegramUsername ?: 'User #' . $telegramId;
        }

        $user = User::where('telegram_id', $telegramId)->first();
        $isNew = false;

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => 'tg_' . $telegramId . '@telegram.local',
                'password' => Hash::make(Str::random(32)),
                'telegram_id' => $telegramId,
                'telegram_username' => $telegramUsername,
                'account_key' => 'XU-' . strtoupper(Str::random(8)),
                'balance' => 0.00,
                'role' => 'user',
            ]);
            $isNew = true;

            Activity::create([
                'user_id' => $user->id,
                'action' => 'REGISTER',
                'description' => 'Account registered automatically via Telegram Bot (/start)',
                'properties' => [
                    'telegram_id' => $telegramId,
                    'telegram_username' => $telegramUsername,
                ],
            ]);
        } else {
            // Update username or name if changed
            $updates = [];
            if ($telegramUsername && $user->telegram_username !== $telegramUsername) {
                $updates['telegram_username'] = $telegramUsername;
            }
            if ($name && $user->name !== $name) {
                $updates['name'] = $name;
            }
            if (empty($user->account_key)) {
                $updates['account_key'] = 'XU-' . strtoupper(Str::random(8));
            }
            if (!empty($updates)) {
                $user->update($updates);
            }
        }

        $userStats = [
            'id' => $user->id,
            'name' => $user->name,
            'telegram_id' => $user->telegram_id,
            'telegram_username' => $user->telegram_username,
            'balance' => (float) $user->balance,
            'role' => $user->role,
            'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
            'orders_count' => $user->orders()->count(),
            'domains_count' => $user->domains()->count(),
        ];

        return response()->json([
            'success' => true,
            'is_new' => $isNew,
            'message' => $isNew ? 'Welcome to Xundefined Digital Products Store!' : 'Welcome back!',
            'user' => $userStats,
        ]);
    }

    /**
     * List all available products (script websites).
     */
    public function products(Request $request): JsonResponse
    {
        $products = Product::where('status', 'active')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'pid' => $product->pid,
                    'category' => $product->category,
                    'price' => (float) $product->price,
                    'description' => $product->description,
                    'version' => $product->version,
                    'contents' => $product->contents,
                    'latest_release' => $product->latest_release,
                    'file' => $product->download_file,
                    'md5checksum' => $product->md5_checksum,
                ];
            });

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    /**
     * Get single product detail.
     */
    public function productDetail(Request $request, $id): JsonResponse
    {
        $product = Product::where('status', 'active')
            ->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('slug', $id);
            })
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $prodData = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'pid' => $product->pid,
            'category' => $product->category,
            'price' => (float) $product->price,
            'description' => $product->description,
            'version' => $product->version,
            'contents' => $product->contents,
            'latest_release' => $product->latest_release,
            'file' => $product->download_file,
            'md5checksum' => $product->md5_checksum,
        ];

        return response()->json([
            'success' => true,
            'product' => $prodData,
        ]);
    }

    /**
     * Purchase a product using user's balance.
     */
    public function buyProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string',
            'product_id' => 'required|integer',
        ]);

        $user = User::where('telegram_id', $validated['telegram_id'])->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found. Please send /start first.',
            ], 404);
        }

        $product = Product::where('id', $validated['product_id'])
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or unavailable.',
            ], 404);
        }

        if ((float) $user->balance < (float) $product->price) {
            return response()->json([
                'success' => false,
                'insufficient_balance' => true,
                'current_balance' => (float) $user->balance,
                'required_amount' => (float) $product->price,
                'shortage' => (float) ($product->price - $user->balance),
                'message' => 'Insufficient balance! You need $' . number_format($product->price, 2) . ' but only have $' . number_format($user->balance, 2) . '.',
            ], 400);
        }

        return DB::transaction(function () use ($user, $product) {
            // Deduct balance
            $user->balance = (float) $user->balance - (float) $product->price;
            $user->save();

            // Generate order
            $orderNumber = 'XUOR-' . date('dmYHi') . '-' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            $downloadToken = Str::random(40);

            $order = Order::create([
                'order_number' => $orderNumber,
                'invoice' => $orderNumber,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'amount' => $product->price,
                'price' => $product->price,
                'payment_method' => 'Balance',
                'status' => 'completed',
                'download_token' => $downloadToken,
                'notes' => 'Purchased via Telegram Bot',
            ]);

            // Log activity
            Activity::create([
                'user_id' => $user->id,
                'action' => 'PURCHASE',
                'description' => "Purchased script '{$product->name}' ({$product->version}) for $" . number_format($product->price, 2),
                'properties' => [
                    'order_id' => $order->id,
                    'order_number' => $orderNumber,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'amount' => $product->price,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Order #{$orderNumber} successful! You have purchased {$product->name}.",
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'product_name' => $product->name,
                    'version' => $product->version,
                    'amount' => (float) $order->amount,
                    'file' => $product->download_file,
                    'md5checksum' => $product->md5_checksum,
                    'contents' => $product->contents,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                ],
                'new_balance' => (float) $user->balance,
            ]);
        });
    }

    /**
     * Get user's purchased downloads.
     */
    public function downloads(Request $request): JsonResponse
    {
        $telegramId = $request->query('telegram_id') ?? $request->input('telegram_id');
        if (!$telegramId) {
            return response()->json(['success' => false, 'message' => 'telegram_id is required'], 400);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $downloads = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('product')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($order) {
                $prod = $order->product;
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice' => $order->invoice,
                    'product_id' => $order->product_id,
                    'product_name' => $prod ? $prod->name : 'Unknown Script',
                    'price' => (float) ($order->amount ?? ($order->price ?? ($prod ? $prod->price : 0))),
                    'category' => $prod ? $prod->category : 'Website Script',
                    'version' => $prod ? $prod->version : '1.0.0',
                    'file' => $prod ? $prod->download_file : null,
                    'md5checksum' => $prod ? $prod->md5_checksum : null,
                    'contents' => $prod ? ($prod->contents ?? []) : [],
                    'purchased_at' => $order->created_at->toFormattedDateString(),
                ];
            });

        return response()->json([
            'success' => true,
            'downloads' => $downloads,
        ]);
    }

    /**
     * Download attachment file for a purchased product version.
     */
    public function downloadFile(Request $request)
    {
        $telegramId = $request->query('telegram_id') ?? $request->input('telegram_id');
        $productId = $request->query('product_id') ?? $request->input('product_id');
        $filename = $request->query('file') ?? $request->input('file');
        $version = $request->query('version') ?? $request->input('version');
        $token = $request->query('token') ?? $request->input('token');

        $purchased = false;
        $user = null;

        // 1. Verify by download token if provided
        if (!empty($token)) {
            $order = Order::with(['user', 'product'])
                ->where('download_token', $token)
                ->where('status', 'completed')
                ->first();

            if ($order) {
                $user = $order->user;
                if (empty($productId)) {
                    $productId = $order->product_id;
                }
                if (empty($telegramId) && $user) {
                    $telegramId = $user->telegram_id;
                }
                if (empty($productId) || (int) $order->product_id === (int) $productId) {
                    $purchased = true;
                }
            }
        }

        if (!empty($token) && !$purchased && empty($telegramId)) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired download token.'], 403);
        }

        // 2. Verify by telegram_id and product_id
        if (!$purchased) {
            if (!$telegramId || !$productId) {
                return response()->json(['success' => false, 'message' => 'telegram_id and product_id (or token) are required'], 400);
            }

            $user = User::where('telegram_id', $telegramId)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            // Verify user purchased this product
            $purchased = Order::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->where('status', 'completed')
                ->exists();
        }

        if (!$purchased) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Product not purchased'], 403);
        }

        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Determine filename from version or direct filename
        $targetFile = $filename;
        if (empty($targetFile) && !empty($version)) {
            $contents = $product->contents ?? [];
            foreach ($contents as $rel) {
                if (($rel['version'] ?? '') === $version) {
                    $targetFile = $rel['file'] ?? null;
                    break;
                }
            }
        }

        if (empty($targetFile)) {
            $targetFile = $product->download_file;
        }

        if (empty($targetFile)) {
            return response()->json(['success' => false, 'message' => 'No file specified for this product.'], 404);
        }

        $targetFile = basename($targetFile);

        $possiblePaths = [
            storage_path('app/private/products/' . $targetFile),
            storage_path('app/private/' . $targetFile),
        ];

        $filePath = null;
        foreach ($possiblePaths as $p) {
            if (file_exists($p)) {
                $filePath = $p;
                break;
            }
        }

        if (!$filePath) {
            return response()->json(['success' => false, 'message' => "File '{$targetFile}' not found in storage."], 404);
        }

        return response()->download($filePath, $targetFile);
    }

    /**
     * Get user's orders history.
     */
    public function orders(Request $request): JsonResponse
    {
        $telegramId = $request->query('telegram_id') ?? $request->input('telegram_id');
        if (!$telegramId) {
            return response()->json(['success' => false, 'message' => 'telegram_id is required'], 400);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $orders = Order::where('user_id', $user->id)
            ->with('product')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($order) {
                $payment = $order->payment_currency ?: ($order->payment_method ?: 'Balance');
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice' => $order->invoice,
                    'product_name' => $order->product ? $order->product->name : 'Unknown Script',
                    'amount' => (float) ($order->amount ?? $order->price),
                    'payment_method' => $payment,
                    'payment_currency' => $order->payment_currency,
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Get user's activities (defaults to 10 recent).
     */
    public function activities(Request $request): JsonResponse
    {
        $telegramId = $request->query('telegram_id') ?? $request->input('telegram_id');
        if (!$telegramId) {
            return response()->json(['success' => false, 'message' => 'telegram_id is required'], 400);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $limit = $request->boolean('all') ? 500 : 10;
        $activities = Activity::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($act) {
                return [
                    'id' => $act->id,
                    'action' => $act->action,
                    'description' => $act->description,
                    'date' => $act->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ]);
    }

    /**
     * Download all activities as txt data.
     */
    public function downloadActivitiesTxt(Request $request)
    {
        $telegramId = $request->query('telegram_id') ?? $request->input('telegram_id');
        if (!$telegramId) {
            return response()->json(['success' => false, 'message' => 'telegram_id is required'], 400);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $activities = Activity::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        $content = "====================================================\n";
        $content .= "       XUNDEFINED - ACCOUNT ACTIVITY LOGS\n";
        $content .= "====================================================\n";
        $content .= "User: {$user->name} (Telegram ID: {$telegramId})\n";
        $content .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "Total Records: {$activities->count()}\n";
        $content .= "====================================================\n\n";

        foreach ($activities as $idx => $act) {
            $num = $idx + 1;
            $content .= "[#{$num}] {$act->created_at->format('Y-m-d H:i:s')} | {$act->action}\n";
            $content .= "    {$act->description}\n\n";
        }

        $filename = "activities-{$telegramId}.txt";

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get user's registered domains for script websites and domain quotas.
     */
    public function domains(Request $request): JsonResponse
    {
        $telegramId = $request->query('telegram_id') ?? $request->input('telegram_id');
        if (!$telegramId) {
            return response()->json(['success' => false, 'message' => 'telegram_id is required'], 400);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (empty($user->account_key)) {
            $user->account_key = 'XU-' . strtoupper(Str::random(8));
            $user->save();
        }

        // Get completed orders for products to compute domain quotas
        $productOrders = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('product_id')
            ->with('product')
            ->get();

        $quotas = [];
        $seenProducts = [];

        foreach ($productOrders as $order) {
            $prod = $order->product;
            if (!$prod || isset($seenProducts[$prod->id])) {
                continue;
            }
            $seenProducts[$prod->id] = true;

            $quota = (int) ($order->domain_quota ?: 3);
            $used = Domain::where('user_id', $user->id)
                ->where(function ($q) use ($prod, $order) {
                    $q->where('product_id', $prod->id)
                      ->orWhere('order_id', $order->id);
                })
                ->count();

            $quotas[] = [
                'product_id' => $prod->id,
                'product_name' => $prod->name,
                'used' => $used,
                'quota' => $quota,
                'display' => "{$prod->name} {$used}/{$quota} Domains.",
            ];
        }

        $domains = Domain::where('user_id', $user->id)
            ->with('product')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($dom) {
                return [
                    'id' => $dom->id,
                    'domain' => $dom->domain,
                    'status' => $dom->status,
                    'product_name' => $dom->product ? $dom->product->name : 'All Scripts License',
                    'created_at' => $dom->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'account_key' => $user->account_key,
            'quotas' => $quotas,
            'domains' => $domains,
        ]);
    }

    /**
     * Register a new domain for user's scripts.
     */
    public function addDomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string',
            'domain' => 'required|string',
            'product_id' => 'nullable|integer',
        ]);

        $user = User::where('telegram_id', $validated['telegram_id'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Clean domain format
        $cleanDomain = strtolower(trim($validated['domain']));
        $cleanDomain = preg_replace('#^https?://#', '', $cleanDomain);
        $cleanDomain = preg_replace('#/.*$#', '', $cleanDomain);
        $cleanDomain = trim($cleanDomain);

        if (empty($cleanDomain) || !str_contains($cleanDomain, '.')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid domain format. Example: example.com or app.example.com',
            ], 422);
        }

        // Check if domain already exists for this user
        $exists = Domain::where('user_id', $user->id)->where('domain', $cleanDomain)->first();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "Domain '{$cleanDomain}' is already registered in your account.",
            ], 409);
        }

        $domain = Domain::create([
            'user_id' => $user->id,
            'product_id' => $validated['product_id'] ?? null,
            'domain' => $cleanDomain,
            'status' => 'active',
        ]);

        Activity::create([
            'user_id' => $user->id,
            'action' => 'BIND_DOMAIN',
            'description' => "Bound domain license for {$cleanDomain}",
            'properties' => ['domain' => $cleanDomain, 'id' => $domain->id],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Domain '{$cleanDomain}' successfully registered and activated!",
            'domain' => [
                'id' => $domain->id,
                'domain' => $domain->domain,
                'status' => $domain->status,
                'created_at' => $domain->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete/unregister a domain.
     */
    public function deleteDomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string',
            'domain_id' => 'required|integer',
        ]);

        $user = User::where('telegram_id', $validated['telegram_id'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $domain = Domain::where('user_id', $user->id)->where('id', $validated['domain_id'])->first();
        if (!$domain) {
            return response()->json(['success' => false, 'message' => 'Domain not found or not owned by user.'], 404);
        }

        $domainName = $domain->domain;
        $domain->delete();

        Activity::create([
            'user_id' => $user->id,
            'action' => 'REMOVE_DOMAIN',
            'description' => "Unregistered domain {$domainName}",
            'properties' => ['domain' => $domainName],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Domain '{$domainName}' removed successfully.",
        ]);
    }

    /**
     * Get user profile details.
     */
    public function profile(Request $request): JsonResponse
    {
        $telegramId = $request->query('telegram_id') ?? $request->input('telegram_id');
        if (!$telegramId) {
            return response()->json(['success' => false, 'message' => 'telegram_id is required'], 400);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $ordersCount = $user->orders()->count();
        $domainsCount = $user->domains()->count();
        $totalSpent = (float) $user->orders()->where('status', 'completed')->sum('amount');

        return response()->json([
            'success' => true,
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telegram_id' => $user->telegram_id,
                'telegram_username' => $user->telegram_username,
                'balance' => (float) $user->balance,
                'role' => $user->role,
                'orders_count' => $ordersCount,
                'domains_count' => $domainsCount,
                'total_spent' => $totalSpent,
                'member_since' => $user->created_at->format('M d, Y'),
            ],
        ]);
    }

    /**
     * Get user balance information & deposit instructions.
     */
    public function balance(Request $request): JsonResponse
    {
        $telegramId = $request->query('telegram_id') ?? $request->input('telegram_id');
        if (!$telegramId) {
            return response()->json(['success' => false, 'message' => 'telegram_id is required'], 400);
        }

        $user = User::where('telegram_id', $telegramId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        return response()->json([
            'success' => true,
            'balance' => (float) $user->balance,
            'currency' => 'USD ($)',
            'topup_methods' => [
                'Crypto (USDT TRC20 / BEP20, BTC, ETH)',
                'PayPal / Credit Card',
                'Bank Transfer / QRIS',
            ],
            'admin_support' => '@XundefinedAdmin',
            'note' => 'To top up your balance, contact admin or use the automated payment gateway.',
        ]);
    }

    /**
     * Top-up balance (supports demo top-up or automated payment confirmation).
     */
    public function topupDemo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string',
            'amount' => 'required|numeric|min:1|max:1000',
        ]);

        $user = User::where('telegram_id', $validated['telegram_id'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $amount = (float) $validated['amount'];
        $user->balance = (float) $user->balance + $amount;
        $user->save();

        Activity::create([
            'user_id' => $user->id,
            'action' => 'BALANCE_TOPUP',
            'description' => "Deposited $" . number_format($amount, 2) . " into balance",
            'properties' => ['amount' => $amount, 'new_balance' => (float) $user->balance],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Successfully added $" . number_format($amount, 2) . " to your balance!",
            'new_balance' => (float) $user->balance,
        ]);
    }

    /**
     * Create real CoinPayments cryptocurrency deposit invoice.
     */
    public function createTopup(Request $request, CoinPaymentsController $coinPaymentsController): JsonResponse
    {
        $request->merge(['is_topup' => true]);
        $response = $coinPaymentsController->createTransaction($request);
        if ($response instanceof JsonResponse) {
            return $response;
        }
        return response()->json([
            'success' => false,
            'message' => 'Failed to initialize crypto top-up.',
        ], 500);
    }

    /**
     * Check crypto deposit invoice status.
     */
    public function checkTopupStatus(Request $request, string $invoice, CoinPaymentsController $coinPaymentsController): JsonResponse
    {
        return $coinPaymentsController->checkStatus($request, $invoice);
    }

    /**
     * Get accepted cryptocurrencies list for top-up.
     */
    public function currencies(Request $request, CoinPaymentsController $coinPaymentsController): JsonResponse
    {
        return $coinPaymentsController->getCurrencies($request);
    }

    /**
     * Get published posts/news for Telegram Bot Info menu.
     */
    public function posts(Request $request): JsonResponse
    {
        $posts = Post::where('is_published', true)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($post) {
                $rawContent = strip_tags($post->content);
                $clean = trim(preg_replace('/\s+/', ' ', $rawContent));
                $shortDescription = Str::limit($clean, 120);

                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'category' => $post->category,
                    'short_description' => $shortDescription,
                    'url' => url('/news/' . $post->slug),
                    'created_at' => $post->created_at ? $post->created_at->format('Y-m-d H:i') : '-',
                ];
            });

        return response()->json([
            'success' => true,
            'posts' => $posts,
        ]);
    }
}
