<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Product;
use App\Services\CoinPaymentsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CoinPaymentsController extends Controller
{
    protected CoinPaymentsService $coinPaymentsService;

    public function __construct(CoinPaymentsService $coinPaymentsService)
    {
        $this->coinPaymentsService = $coinPaymentsService;
    }

    /**
     * Create a new CoinPayments transaction for a product purchase.
     *
     * @see https://legacy.coinpayments.net/apidoc-create-transaction
     */
    public function createTransaction(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'currency2'  => 'nullable|string|max:20',
            'currency1'  => 'nullable|string|max:10',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user() ?? $request->user();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized authentication required.'], 401);
            }
            return redirect()->route('login')->with('error', 'Please login to complete your order.');
        }

        $product = Product::where('active', true)->findOrFail($request->input('product_id'));

        // Check if user already owns this product
        $existingOrder = Order::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->first();

        if ($existingOrder && !$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'      => false,
                    'message'      => 'You already own this module.',
                    'redirect_url' => route('dashboard.download'),
                ], 400);
            }
            return redirect()->route('dashboard.download')
                ->with('status', 'You already own ' . $product->name . '! Redirected to your download vault.');
        }

        $currency1 = strtoupper((string) ($request->input('currency1') ?: $this->coinPaymentsService->getDefaultCurrency()));
        $currency2 = strtoupper((string) ($request->input('currency2') ?: $this->coinPaymentsService->getDefaultCrypto()));

        // Generate unique invoice number
        $invoice = 'INV-' . strtoupper(bin2hex(random_bytes(3))) . '-' . date('ymd');

        // Build callback IPN URL
        $ipnUrl = config('coinpayments.ipn_url') ?: route('coinpayments.ipn.web');

        try {
            // Create pending Order record in database first
            $order = Order::create([
                'invoice'          => $invoice,
                'user_id'          => $user->id,
                'product_id'       => $product->id,
                'price'            => (int) $product->price,
                'domain_quota'     => 3,
                'payment_method'   => 'Crypto (' . $currency2 . ')',
                'payment_currency' => $currency2,
                'status'           => Order::STATUS_PENDING,
            ]);

            // Prepare CoinPayments API parameters
            $apiParams = [
                'amount'      => (float) $product->price,
                'currency1'   => $currency1,
                'currency2'   => $currency2,
                'buyer_email' => $user->email,
                'buyer_name'  => $user->name,
                'item_name'   => $product->name,
                'item_number' => (string) $product->id,
                'invoice'     => $order->invoice,
                'custom'      => json_encode([
                    'order_id' => $order->id,
                    'user_id'  => $user->id,
                    'invoice'  => $order->invoice,
                ]),
                'ipn_url'     => $ipnUrl,
                'success_url' => route('dashboard.download'),
                'cancel_url'  => route('dashboard.store'),
            ];

            // Execute create_transaction API call to CoinPayments
            $cpResult = $this->coinPaymentsService->createTransaction($apiParams);

            // Update order with crypto transaction details
            $order->update([
                'txn_id'                  => $cpResult['txn_id'] ?? null,
                'payment_address'         => $cpResult['address'] ?? null,
                'payment_dest_tag'        => $cpResult['dest_tag'] ?? null,
                'payment_amount'          => (string) ($cpResult['amount'] ?? ''),
                'payment_confirms_needed' => isset($cpResult['confirms_needed']) ? (int) $cpResult['confirms_needed'] : 1,
                'payment_timeout'         => isset($cpResult['timeout']) ? (int) $cpResult['timeout'] : 3600,
                'payment_status_url'      => $cpResult['status_url'] ?? null,
                'payment_qrcode_url'      => $cpResult['qrcode_url'] ?? null,
                'payment_meta'            => $cpResult,
            ]);

            // Log activity
            ActivityLog::create([
                'type'        => 'order',
                'description' => "Crypto invoice created for {$product->name} (TXN: {$order->txn_id}, Amount: {$order->payment_amount} {$currency2})",
                'user_id'     => $user->id,
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Crypto payment transaction initialized successfully.',
                    'order'        => $order->fresh(),
                    'transaction'  => $cpResult,
                    'redirect_url' => route('dashboard.payment.show', $order->invoice),
                ]);
            }

            return redirect()->route('dashboard.payment.show', $order->invoice)
                ->with('status', 'Crypto payment initialized. Please transfer the specified cryptocurrency to complete your purchase.');

        } catch (Exception $e) {
            Log::error('Failed to create Crypto transaction', [
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
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

            return redirect()->back()
                ->with('error', 'Crypto gateway error: ' . $e->getMessage());
        }
    }

    /**
     * Handle incoming Instant Payment Notifications (IPN) from CoinPayments.
     *
     * @see https://legacy.coinpayments.net/downloads/cpipn.phps
     * @see https://legacy.coinpayments.net/merchant-tools-ipn#setup
     */
    public function handleIpn(Request $request): Response
    {
        Log::info('CoinPayments IPN Received', [
            'ip'      => $request->ip(),
            'headers' => [
                'hmac' => $request->header('HMAC') ? 'present' : 'missing',
            ],
            'payload' => $request->except(['key']),
        ]);

        // 1. Validate IPN HMAC Signature, Merchant ID, and payload authenticity
        $validation = $this->coinPaymentsService->validateIpn($request);
        if (!$validation['valid']) {
            Log::warning('CoinPayments IPN Validation Failed: ' . $validation['error']);
            return response('IPN Error: ' . $validation['error'], 400)
                ->header('Content-Type', 'text/plain');
        }

        $data = $validation['data'];

        $txnId      = $data['txn_id'] ?? ($data['id'] ?? ($data['deposit_id'] ?? null));
        $id         = $data['id'] ?? null;
        $depositId  = $data['deposit_id'] ?? null;
        $address    = $data['address'] ?? null;
        $status     = isset($data['status']) ? (int) $data['status'] : null;
        $statusText = $data['status_text'] ?? '';
        $currency1  = strtoupper((string) ($data['currency1'] ?? ($data['fiat_coin'] ?? '')));
        $currency2  = strtoupper((string) ($data['currency2'] ?? ($data['currency'] ?? '')));
        $amount1    = isset($data['amount1']) ? (float) $data['amount1'] : (isset($data['fiat_amount']) ? (float) $data['fiat_amount'] : null);
        $amount2    = isset($data['amount2']) ? (float) $data['amount2'] : (isset($data['amount']) ? (float) $data['amount'] : null);
        $invoice    = $data['invoice'] ?? null;
        $custom     = $data['custom'] ?? null;
        $ipnType    = $data['ipn_type'] ?? '';

        // 2. Locate the Order in database by txn_id, id, address, deposit_id, custom, or invoice
        $order = null;

        // Try lookup by txn_id directly
        if (!empty($txnId)) {
            $order = Order::with(['user', 'product'])->where('txn_id', $txnId)->first();
        }

        // Try lookup by id
        if (!$order && !empty($id)) {
            $order = Order::with(['user', 'product'])->where('txn_id', $id)->first();
            if (!$order && is_numeric($id)) {
                $order = Order::with(['user', 'product'])->find($id);
            }
        }

        // Try lookup by deposit_id
        if (!$order && !empty($depositId)) {
            $order = Order::with(['user', 'product'])->where('txn_id', $depositId)->first();
        }

        // Try lookup by receiving address
        if (!$order && !empty($address)) {
            $order = Order::with(['user', 'product'])->where('payment_address', $address)->first();
        }

        // Try lookup by custom JSON payload
        if (!$order && !empty($custom)) {
            $customData = is_array($custom) ? $custom : json_decode($custom, true);
            if (!empty($customData['order_id'])) {
                $order = Order::with(['user', 'product'])->find($customData['order_id']);
            } elseif (!empty($customData['invoice'])) {
                $order = Order::with(['user', 'product'])->where('invoice', $customData['invoice'])->first();
            } elseif (!empty($customData['txn_id'])) {
                $order = Order::with(['user', 'product'])->where('txn_id', $customData['txn_id'])->first();
            }
        }

        // Try lookup by invoice as fallback
        if (!$order && !empty($invoice)) {
            $order = Order::with(['user', 'product'])->where('invoice', $invoice)->first();
        }

        // If no matching order is found, acknowledge cleanly with IPN OK so CoinPayments does not keep retrying
        if (!$order) {
            Log::info("CoinPayments IPN received with no associated order (Type: {$ipnType}, TXN: {$txnId}, ID: {$id}, Status: {$status}). Acknowledging webhook.");
            return response('IPN OK: No order matched', 200)
                ->header('Content-Type', 'text/plain');
        }

        // 3. Security checks: currency & amount verification
        if ($amount1 !== null && $order->price > 0) {
            // Check if amount is less than expected (allow 1% tolerance for floating point conversions if applicable)
            if ($amount1 < ($order->price * 0.99)) {
                $msg = "Amount paid ({$amount1} {$currency1}) is less than order total ({$order->price}).";
                $this->coinPaymentsService->sendDebugReport('Underpaid Order', $msg, $data);
                Log::warning('Crypto IPN: ' . $msg);
                // We do not reject outright, but log and continue processing status
            }
        }

        // 4. Update order payment metadata
        $existingMeta = is_array($order->payment_meta) ? $order->payment_meta : [];
        $mergedMeta = array_merge($existingMeta, [
            'last_ipn_received_at' => now()->toIso8601String(),
            'last_ipn_status'      => $status,
            'last_ipn_status_text' => $statusText,
            'ipn_data'             => $data,
            'received_amount'      => $data['received_amount'] ?? ($data['amount2'] ?? null),
            'received_confirms'    => $data['received_confirms'] ?? ($data['confirms'] ?? null),
        ]);

        // 5. Process payment status logic according to CoinPayments specification
        // >= 100 or == 2: Payment Complete or Queued for nightly payout
        // < 0: Error / Cancelled / Refunded
        // 0 - 99: Pending / Awaiting confirmations
        if ($status >= 100 || $status === 2) {
            $wasAlreadyCompleted = $order->isCompleted();

            $order->update([
                'status'       => Order::STATUS_COMPLETED,
                'txn_id'       => $txnId ?: $order->txn_id,
                'payment_meta' => $mergedMeta,
            ]);

            if (!$wasAlreadyCompleted) {
                ActivityLog::create([
                    'type'        => 'order',
                    'description' => "Crypto payment confirmed for Order #{$order->invoice} ({$order->product->name}) - Status: {$status} ({$statusText})",
                    'user_id'     => $order->user_id,
                ]);

                Log::info("Crypto Order #{$order->invoice} marked as COMPLETED.");
            }
        } elseif ($status < 0) {
            // Payment error, cancelled, timed out
            if (!$order->isCompleted()) {
                $order->update([
                    'status'       => Order::STATUS_CANCELLED,
                    'payment_meta' => $mergedMeta,
                ]);

                ActivityLog::create([
                    'type'        => 'order',
                    'description' => "Crypto payment cancelled/failed for Order #{$order->invoice} ({$statusText})",
                    'user_id'     => $order->user_id,
                ]);

                Log::info("CoinPayments Order #{$order->invoice} marked as CANCELLED (Status: {$status}).");
            }
        } else {
            // Status between 0 and 99 (e.g. 0 = waiting funds, 1 = confirmed coin reception / confirming)
            $newStatus = ($status > 0) ? Order::STATUS_PROCESSING : Order::STATUS_PENDING;

            if (!$order->isCompleted()) {
                $order->update([
                    'status'       => $newStatus,
                    'txn_id'       => $txnId ?: $order->txn_id,
                    'payment_meta' => $mergedMeta,
                ]);

                Log::info("Crypto Order #{$order->invoice} updated to {$newStatus} (Status: {$status}, {$statusText}).");
            }
        }

        // Return standard CoinPayments IPN success response
        return response('IPN OK', 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Display the crypto payment / invoice checkout page.
     */
    public function showPayment(Request $request, string $invoice): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $orderQuery = Order::with(['product', 'user'])->where('invoice', $invoice);
        if (!$user->isAdmin()) {
            $orderQuery->where('user_id', $user->id);
        }

        $order = $orderQuery->firstOrFail();

        // If order already completed, redirect to downloads
        if ($order->isCompleted()) {
            return redirect()->route('dashboard.download')
                ->with('status', 'Order #' . $order->invoice . ' is completed! Your software payload is ready for download.');
        }

        // Calculate expiration timestamp and remaining seconds
        $createdAtTimestamp = $order->created_at ? $order->created_at->timestamp : time();
        $timeoutSeconds = $order->payment_timeout ?: 3600;
        $expiresAtTimestamp = $createdAtTimestamp + $timeoutSeconds;
        $remainingSeconds = max(0, $expiresAtTimestamp - time());

        return view('dashboard.payment', compact('user', 'order', 'remainingSeconds', 'expiresAtTimestamp'));
    }

    /**
     * Check payment status endpoint for frontend polling or manual sync.
     */
    public function checkStatus(Request $request, string $invoice): JsonResponse
    {
        $order = Order::with(['product'])->where('invoice', $invoice)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Check if user has permission
        $user = Auth::user() ?? $request->user();
        if ($user && !$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        // If requested, poll CoinPayments live API get_tx_info as a fallback/sync check
        if ($request->query('refresh') == '1' && !$order->isCompleted() && !empty($order->txn_id)) {
            try {
                $txInfo = $this->coinPaymentsService->getTxInfo($order->txn_id, true);
                if (isset($txInfo['status'])) {
                    $liveStatus = (int) $txInfo['status'];
                    $statusText = $txInfo['status_text'] ?? '';

                    $meta = is_array($order->payment_meta) ? $order->payment_meta : [];
                    $meta['live_tx_info'] = $txInfo;

                    if ($liveStatus >= 100 || $liveStatus === 2) {
                        $order->update([
                            'status'       => Order::STATUS_COMPLETED,
                            'payment_meta' => $meta,
                        ]);
                        ActivityLog::create([
                            'type'        => 'order',
                            'description' => "Payment verified via Crypto live check for Order #{$order->invoice}",
                            'user_id'     => $order->user_id,
                        ]);
                    } elseif ($liveStatus < 0) {
                        $order->update([
                            'status'       => Order::STATUS_CANCELLED,
                            'payment_meta' => $meta,
                        ]);
                    } elseif ($liveStatus > 0) {
                        $order->update([
                            'status'       => Order::STATUS_PROCESSING,
                            'payment_meta' => $meta,
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::warning('Failed to sync live tx_info from Crypto: ' . $e->getMessage());
            }
        }

        $order->refresh();

        return response()->json([
            'success'          => true,
            'invoice'          => $order->invoice,
            'status'           => $order->status,
            'is_completed'     => $order->isCompleted(),
            'is_processing'    => $order->isProcessing(),
            'is_pending'       => $order->isPending(),
            'is_cancelled'     => $order->isCancelled(),
            'payment_currency' => $order->payment_currency,
            'payment_amount'   => $order->payment_amount,
            'txn_id'           => $order->txn_id,
            'redirect_url'     => $order->isCompleted() ? route('dashboard.download') : null,
        ]);
    }

    /**
     * Get list of accepted cryptocurrencies for UI selection.
     */
    public function getCurrencies(Request $request): JsonResponse
    {
        $coins = $this->coinPaymentsService->getAcceptedCoins();

        return response()->json([
            'success' => true,
            'coins'   => array_values($coins),
        ]);
    }
}
