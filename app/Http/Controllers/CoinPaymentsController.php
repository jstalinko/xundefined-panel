<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CoinPaymentsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CoinPaymentsController extends Controller
{
    protected CoinPaymentsService $coinPaymentsService;

    public function __construct(CoinPaymentsService $coinPaymentsService)
    {
        $this->coinPaymentsService = $coinPaymentsService;
    }

    /**
     * Create a new CoinPayments transaction for product purchase or balance top-up.
     */
    public function createTransaction(Request $request): JsonResponse|RedirectResponse
    {
        $isTopup = $request->boolean('is_topup') || $request->filled('amount') && !$request->filled('product_id');

        if ($isTopup) {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'currency2' => 'nullable|string|max:20',
                'currency1' => 'nullable|string|max:10',
                'telegram_id' => 'nullable|string',
            ]);
        } else {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'currency2' => 'nullable|string|max:20',
                'currency1' => 'nullable|string|max:10',
                'telegram_id' => 'nullable|string',
            ]);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user() ?? $request->user();
        if (!$user && $request->filled('telegram_id')) {
            $user = User::where('telegram_id', $request->input('telegram_id'))->first();
        }

        if (!$user) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized authentication required.'], 401);
            }
            return redirect()->route('login')->with('error', 'Please login to complete payment.');
        }

        $currency1 = strtoupper((string) ($request->input('currency1') ?: config('coinpayments.default_currency', 'USD')));
        $currency2 = strtoupper((string) ($request->input('currency2') ?: config('coinpayments.default_crypto', 'USDT.TRC20')));

        $product = null;
        if (!$isTopup) {
            $product = Product::where('active', true)->findOrFail($request->input('product_id'));

            // Check if user already owns this product
            $existingOrder = Order::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->where('status', Order::STATUS_COMPLETED)
                ->first();

            if ($existingOrder && !$user->isAdmin()) {
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You already own this module/script.',
                    ], 400);
                }
                return redirect()->back()->with('error', 'You already own ' . $product->name);
            }

            $amount = (float) $product->price;
            $itemName = $product->name;
            $invoice = 'INV-' . strtoupper(Str::random(6)) . '-' . date('ymd');
        } else {
            $amount = (float) $request->input('amount');
            $invoice = 'TOPUP-' . strtoupper(Str::random(6)) . '-' . date('ymd');
            $itemName = "Balance Topup #{$invoice}";
        }

        $ipnUrl = config('coinpayments.ipn_url') ?: route('coinpayments.ipn.web');

        try {
            // Create pending Order record in database first
            $order = Order::create([
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'invoice' => $invoice,
                'user_id' => $user->id,
                'product_id' => $product ? $product->id : null,
                'amount' => $amount,
                'price' => $amount,
                'domain_quota' => $product ? 3 : 0,
                'payment_method' => 'Crypto (' . $currency2 . ')',
                'payment_currency' => $currency2,
                'status' => Order::STATUS_PENDING,
                'notes' => $isTopup ? 'Crypto Balance Top-up' : 'Crypto Product Purchase',
            ]);

            $apiParams = [
                'amount' => $amount,
                'currency1' => $currency1,
                'currency2' => $currency2,
                'buyer_email' => $user->email,
                'buyer_name' => $user->name,
                'item_name' => $itemName,
                'item_number' => $product ? (string) $product->id : 'TOPUP',
                'invoice' => $order->invoice,
                'custom' => json_encode([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'invoice' => $order->invoice,
                    'is_topup' => $isTopup,
                ]),
                'ipn_url' => $ipnUrl,
            ];

            // If API keys are not configured or empty, fallback to sandbox/test crypto coordinates
            if (empty(config('coinpayments.public_key')) || empty(config('coinpayments.private_key'))) {
                $mockRate = match(true) {
                    $currency2 === 'BTC' => 65000,
                    $currency2 === 'ETH' => 3500,
                    $currency2 === 'SOL' => 150,
                    $currency2 === 'LTC' || $currency2 === 'LTCT' => 70,
                    default => 1,
                };
                $cryptoAmount = number_format($amount / $mockRate, 6, '.', '');
                $mockAddress = match(true) {
                    $currency2 === 'BTC' => 'bc1q' . strtolower(Str::random(34)),
                    $currency2 === 'ETH' || str_contains($currency2, 'ERC20') => '0x' . strtolower(Str::random(40)),
                    $currency2 === 'SOL' || str_contains($currency2, 'SOL') => Str::random(44),
                    $currency2 === 'LTCT' || $currency2 === 'LTC' => 'tltc1' . strtolower(Str::random(34)),
                    default => 'T' . Str::random(33),
                };

                $cpResult = [
                    'txn_id' => 'CP_DEV_' . strtoupper(Str::random(14)),
                    'address' => $mockAddress,
                    'dest_tag' => null,
                    'amount' => $cryptoAmount,
                    'confirms_needed' => 1,
                    'timeout' => 3600,
                    'status_url' => url('/payment/' . $order->invoice),
                    'qrcode_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($mockAddress . '?amount=' . $cryptoAmount),
                ];
            } else {
                $cpResult = $this->coinPaymentsService->createTransaction($apiParams);
            }

            // Update order with crypto transaction details
            $order->update([
                'txn_id' => $cpResult['txn_id'] ?? null,
                'payment_address' => $cpResult['address'] ?? null,
                'payment_dest_tag' => $cpResult['dest_tag'] ?? null,
                'payment_amount' => (string) ($cpResult['amount'] ?? ''),
                'payment_confirms_needed' => isset($cpResult['confirms_needed']) ? (int) $cpResult['confirms_needed'] : 1,
                'payment_timeout' => isset($cpResult['timeout']) ? (int) $cpResult['timeout'] : 3600,
                'payment_status_url' => $cpResult['status_url'] ?? null,
                'payment_qrcode_url' => $cpResult['qrcode_url'] ?? null,
                'payment_meta' => $cpResult,
            ]);

            ActivityLog::create([
                'type' => 'order',
                'description' => "Crypto payment initialized for {$itemName} (TXN: {$order->txn_id}, Amount: {$order->payment_amount} {$currency2})",
                'user_id' => $user->id,
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Crypto payment transaction initialized successfully.',
                    'order' => $order->fresh(),
                    'transaction' => [
                        'invoice' => $order->invoice,
                        'txn_id' => $order->txn_id,
                        'amount' => (float) $order->amount,
                        'payment_amount' => $order->payment_amount,
                        'payment_currency' => $order->payment_currency,
                        'payment_address' => $order->payment_address,
                        'payment_dest_tag' => $order->payment_dest_tag,
                        'payment_qrcode_url' => $order->payment_qrcode_url,
                        'payment_status_url' => $order->payment_status_url,
                        'payment_url' => url('/payment/' . $order->invoice),
                        'timeout' => $order->payment_timeout,
                    ],
                    'redirect_url' => route('dashboard.payment.show', $order->invoice),
                ]);
            }

            return redirect()->route('dashboard.payment.show', $order->invoice)
                ->with('status', 'Crypto payment initialized. Transfer cryptocurrency to the generated address to complete payment.');

        } catch (Exception $e) {
            Log::error('Failed to create Crypto transaction', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($order) && $order->exists) {
                $order->update(['status' => Order::STATUS_CANCELLED]);
            }

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize Crypto gateway: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Crypto gateway error: ' . $e->getMessage());
        }
    }

    /**
     * Dedicated endpoint for balance top-up.
     */
    public function createTopup(Request $request): JsonResponse|RedirectResponse
    {
        $request->merge(['is_topup' => true]);
        return $this->createTransaction($request);
    }

    /**
     * Handle incoming Instant Payment Notifications (IPN) from CoinPayments.
     */
    public function handleIpn(Request $request): Response
    {
        Log::info('CoinPayments IPN Received', [
            'ip' => $request->ip(),
            'headers' => [
                'hmac' => $request->header('HMAC') ? 'present' : 'missing',
            ],
            'payload' => $request->except(['key']),
        ]);

        $validation = $this->coinPaymentsService->validateIpn($request);
        if (!$validation['valid']) {
            Log::warning('CoinPayments IPN Validation Failed: ' . $validation['error']);
            return response('IPN Error: ' . $validation['error'], 400)->header('Content-Type', 'text/plain');
        }

        $data = $validation['data'];
        $txnId = $data['txn_id'] ?? ($data['id'] ?? ($data['deposit_id'] ?? null));
        $status = isset($data['status']) ? (int) $data['status'] : null;
        $statusText = $data['status_text'] ?? '';
        $custom = $data['custom'] ?? null;
        $invoice = $data['invoice'] ?? null;

        // Locate order
        $order = null;
        if (!empty($txnId)) {
            $order = Order::with(['user', 'product'])->where('txn_id', $txnId)->first();
        }
        if (!$order && !empty($invoice)) {
            $order = Order::with(['user', 'product'])->where('invoice', $invoice)->first();
        }
        if (!$order && !empty($custom)) {
            $customData = is_array($custom) ? $custom : json_decode($custom, true);
            if (!empty($customData['order_id'])) {
                $order = Order::with(['user', 'product'])->find($customData['order_id']);
            } elseif (!empty($customData['invoice'])) {
                $order = Order::with(['user', 'product'])->where('invoice', $customData['invoice'])->first();
            }
        }

        if (!$order) {
            Log::info("CoinPayments IPN received with no associated order (TXN: {$txnId}, Status: {$status}). Acknowledged.");
            return response('IPN OK: No order matched', 200)->header('Content-Type', 'text/plain');
        }

        $existingMeta = is_array($order->payment_meta) ? $order->payment_meta : [];
        $mergedMeta = array_merge($existingMeta, [
            'last_ipn_received_at' => now()->toIso8601String(),
            'last_ipn_status' => $status,
            'last_ipn_status_text' => $statusText,
            'ipn_data' => $data,
        ]);

        // Complete: status >= 100 or status === 2
        if ($status >= 100 || $status === 2) {
            $wasAlreadyCompleted = $order->isCompleted();

            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'txn_id' => $txnId ?: $order->txn_id,
                'payment_meta' => $mergedMeta,
            ]);

            if (!$wasAlreadyCompleted) {
                // If it's a balance topup (product_id is null)
                if (empty($order->product_id) && $order->user) {
                    $order->user->increment('balance', (float) ($order->amount ?? $order->price));
                    
                    Activity::create([
                        'user_id' => $order->user_id,
                        'action' => 'BALANCE_TOPUP',
                        'description' => "Deposited $" . number_format($order->amount, 2) . " via CoinPayments (INV: #{$order->invoice})",
                        'properties' => [
                            'invoice' => $order->invoice,
                            'txn_id' => $order->txn_id,
                            'amount' => (float) $order->amount,
                            'new_balance' => (float) $order->user->fresh()->balance,
                        ],
                    ]);

                    ActivityLog::create([
                        'type' => 'balance',
                        'description' => "Balance topped up by $" . number_format($order->amount, 2) . " USD via CoinPayments (INV: {$order->invoice})",
                        'user_id' => $order->user_id,
                    ]);
                } else {
                    // Product order completed
                    ActivityLog::create([
                        'type' => 'order',
                        'description' => "Crypto payment confirmed for Order #{$order->invoice} ({$order->product?->name}) - Status: {$status}",
                        'user_id' => $order->user_id,
                    ]);
                }

                Log::info("CoinPayments Order #{$order->invoice} marked as COMPLETED.");
            }
        } elseif ($status < 0) {
            if (!$order->isCompleted()) {
                $order->update([
                    'status' => Order::STATUS_CANCELLED,
                    'payment_meta' => $mergedMeta,
                ]);

                ActivityLog::create([
                    'type' => 'order',
                    'description' => "Crypto payment cancelled/failed for Order #{$order->invoice} ({$statusText})",
                    'user_id' => $order->user_id,
                ]);
            }
        } else {
            $newStatus = ($status > 0) ? Order::STATUS_PROCESSING : Order::STATUS_PENDING;
            if (!$order->isCompleted()) {
                $order->update([
                    'status' => $newStatus,
                    'txn_id' => $txnId ?: $order->txn_id,
                    'payment_meta' => $mergedMeta,
                ]);
            }
        }

        return response('IPN OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Display the crypto payment / invoice checkout page.
     */
    public function showPayment(Request $request, string $invoice): View|RedirectResponse
    {
        $order = Order::with(['product', 'user'])->where('invoice', $invoice)->firstOrFail();

        $createdAtTimestamp = $order->created_at ? $order->created_at->timestamp : time();
        $timeoutSeconds = $order->payment_timeout ?: 3600;
        $expiresAtTimestamp = $createdAtTimestamp + $timeoutSeconds;
        $remainingSeconds = max(0, $expiresAtTimestamp - time());

        return view('dashboard.payment', [
            'order' => $order,
            'user' => $order->user,
            'remainingSeconds' => $remainingSeconds,
            'expiresAtTimestamp' => $expiresAtTimestamp,
        ]);
    }

    /**
     * Check payment status endpoint for frontend polling or Telegram bot.
     */
    public function checkStatus(Request $request, string $invoice): JsonResponse
    {
        $order = Order::with(['product', 'user'])->where('invoice', $invoice)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice order not found.',
            ], 404);
        }

        // If requested with refresh=1, query live API or check simulate
        if ($request->query('refresh') == '1' && !$order->isCompleted() && !empty($order->txn_id)) {
            try {
                if (!empty(config('coinpayments.public_key')) && !empty(config('coinpayments.private_key'))) {
                    $txInfo = $this->coinPaymentsService->getTxInfo($order->txn_id, true);
                    if (isset($txInfo['status'])) {
                        $liveStatus = (int) $txInfo['status'];
                        if ($liveStatus >= 100 || $liveStatus === 2) {
                            $order->status = Order::STATUS_COMPLETED;
                            $order->save();

                            if (empty($order->product_id) && $order->user) {
                                $order->user->increment('balance', (float) ($order->amount ?? $order->price));

                                Activity::create([
                                    'user_id' => $order->user_id,
                                    'action' => 'BALANCE_TOPUP',
                                    'description' => "Deposited $" . number_format($order->amount, 2) . " via CoinPayments (INV: #{$order->invoice})",
                                    'properties' => [
                                        'invoice' => $order->invoice,
                                        'txn_id' => $order->txn_id,
                                        'amount' => (float) $order->amount,
                                        'new_balance' => (float) $order->user->fresh()->balance,
                                    ],
                                ]);

                                ActivityLog::create([
                                    'type' => 'balance',
                                    'description' => "Balance topped up by $" . number_format($order->amount, 2) . " USD via CoinPayments (INV: {$order->invoice})",
                                    'user_id' => $order->user_id,
                                ]);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning('Live status poll failed: ' . $e->getMessage());
            }
        }

        $order->refresh();

        return response()->json([
            'success' => true,
            'invoice' => $order->invoice,
            'status' => $order->status,
            'is_completed' => $order->isCompleted(),
            'is_processing' => $order->isProcessing(),
            'is_pending' => $order->isPending(),
            'is_cancelled' => $order->isCancelled(),
            'payment_currency' => $order->payment_currency,
            'payment_amount' => $order->payment_amount,
            'payment_address' => $order->payment_address,
            'txn_id' => $order->txn_id,
            'new_balance' => $order->user ? (float) $order->user->balance : null,
            'redirect_url' => route('admin.dashboard'),
        ]);
    }

    /**
     * Get list of accepted cryptocurrencies.
     */
    public function getCurrencies(Request $request): JsonResponse
    {
        $coins = $this->coinPaymentsService->getAcceptedCoins();

        return response()->json([
            'success' => true,
            'coins' => array_values($coins),
        ]);
    }
}
