<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramService
{
    protected ?string $botToken;

    public function __construct(?string $botToken = null)
    {
        $this->botToken = $botToken ?: (config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN'));
    }

    /**
     * Get the configured Bot Token.
     */
    public function getBotToken(): ?string
    {
        return $this->botToken;
    }

    /**
     * Send a text message with optional inline keyboard via Telegram Bot API.
     */
    public function sendMessage(
        string|int $chatId,
        string $text,
        ?array $replyMarkup = null,
        ?string $parseMode = 'Markdown'
    ): array {
        if (empty($this->botToken) || $this->botToken === 'dummy:token') {
            Log::warning('TelegramService: Telegram bot token not configured or dummy, skipped sendMessage.');
            return ['ok' => false, 'error' => 'Bot token not configured'];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => false,
        ];

        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = $replyMarkup;
        }

        try {
            $response = Http::timeout(10)->post($url, $payload);

            // If markdown parsing failed with 400, retry without parse_mode as safe fallback
            if (!$response->successful() && $parseMode !== null) {
                $errJson = $response->json();
                if (isset($errJson['error_code']) && $errJson['error_code'] == 400) {
                    Log::warning('TelegramService sendMessage markdown failed, retrying plain text: ' . ($errJson['description'] ?? ''));
                    unset($payload['parse_mode']);
                    $response = Http::timeout(10)->post($url, $payload);
                }
            }

            $data = $response->json();

            if (!$response->successful()) {
                Log::warning('TelegramService: Failed to send Telegram message', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'response' => $data,
                ]);
            }

            return is_array($data) ? $data : ['ok' => false, 'raw' => $response->body()];

        } catch (Exception $e) {
            Log::error('TelegramService: Exception during sendMessage: ' . $e->getMessage(), [
                'chat_id' => $chatId,
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a document file directly into Telegram chat.
     */
    public function sendDocument(
        string|int $chatId,
        string $filePath,
        ?string $caption = null,
        ?array $replyMarkup = null
    ): array {
        if (empty($this->botToken) || $this->botToken === 'dummy:token') {
            Log::warning('TelegramService: Bot token not configured, skipped sendDocument.');
            return ['ok' => false, 'error' => 'Bot token not configured'];
        }

        if (!file_exists($filePath)) {
            Log::warning("TelegramService sendDocument: File '{$filePath}' does not exist.");
            return ['ok' => false, 'error' => 'File not found'];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendDocument";
        $filename = basename($filePath);

        try {
            $request = Http::timeout(30)->attach('document', file_get_contents($filePath), $filename);

            $data = [
                'chat_id' => $chatId,
            ];
            if ($caption) {
                $data['caption'] = $caption;
                $data['parse_mode'] = 'Markdown';
            }
            if (!empty($replyMarkup)) {
                $data['reply_markup'] = json_encode($replyMarkup);
            }

            $response = $request->post($url, $data);
            return $response->json() ?? ['ok' => false];

        } catch (Exception $e) {
            Log::error('TelegramService sendDocument error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Resolve the target Telegram Chat ID for an order or IPN payload.
     * Supports:
     * - Explicit chat_id / telegram_id passed
     * - Order's associated user model (telegram_id / telegram_username)
     * - Order's payment_meta (chat_id / telegram_id / user_telegram)
     * - Custom IPN data payload (custom.chat_id, custom.telegram_id, custom.user_telegram)
     * - Direct IPN payload parameters (chat_id, telegram_id, user_telegram)
     * - Telegram username matching User model record
     */
    public function resolveChatId(Order $order, array $ipnData = [], ?string $explicitChatId = null): ?string
    {
        // 1. Explicit chat_id passed
        if (!empty($explicitChatId)) {
            $resolved = $this->resolveToNumericChatId($explicitChatId);
            if ($resolved) {
                return $resolved;
            }
        }

        // 2. Custom JSON in IPN payload
        $custom = $ipnData['custom'] ?? null;
        if (!empty($custom)) {
            $customData = is_array($custom) ? $custom : json_decode($custom, true);
            if (is_array($customData)) {
                $customChatId = $customData['chat_id'] ?? ($customData['telegram_id'] ?? ($customData['user_telegram'] ?? null));
                if (!empty($customChatId)) {
                    $resolved = $this->resolveToNumericChatId((string) $customChatId);
                    if ($resolved) {
                        return $resolved;
                    }
                }
            }
        }

        // 3. Direct fields in IPN payload
        foreach (['chat_id', 'telegram_id', 'user_telegram'] as $key) {
            if (!empty($ipnData[$key])) {
                $resolved = $this->resolveToNumericChatId((string) $ipnData[$key]);
                if ($resolved) {
                    return $resolved;
                }
            }
        }

        // 4. Order payment_meta
        if (is_array($order->payment_meta)) {
            foreach (['chat_id', 'telegram_id', 'user_telegram'] as $key) {
                if (!empty($order->payment_meta[$key])) {
                    $resolved = $this->resolveToNumericChatId((string) $order->payment_meta[$key]);
                    if ($resolved) {
                        return $resolved;
                    }
                }
            }
        }

        // 5. Associated User model on the order
        $user = $order->user;
        if ($user) {
            if (!empty($user->telegram_id)) {
                return (string) $user->telegram_id;
            }
            if (!empty($user->telegram_username)) {
                $resolved = $this->resolveToNumericChatId($user->telegram_username);
                if ($resolved) {
                    return $resolved;
                }
            }
        }

        return null;
    }

    /**
     * Resolve a chat_id or telegram username string to an actual chat ID.
     */
    public function resolveToNumericChatId(string $identifier): ?string
    {
        $trimmed = trim($identifier);
        if (empty($trimmed)) {
            return null;
        }

        // If numeric or telegram group/channel ID (-100...)
        if (is_numeric($trimmed) || preg_match('/^-?\d+$/', $trimmed)) {
            return $trimmed;
        }

        // If username (with or without @)
        $cleanUsername = ltrim($trimmed, '@');
        $user = User::where('telegram_username', $cleanUsername)
            ->whereNotNull('telegram_id')
            ->first();

        if ($user && !empty($user->telegram_id)) {
            return (string) $user->telegram_id;
        }

        // If no user record found, return with @ prefix for Telegram username target
        return '@' . $cleanUsername;
    }

    /**
     * Send order confirmation notification to user on Telegram when IPN is confirmed.
     * Shows product details and Download button.
     */
    public function sendOrderConfirmation(Order $order, ?string $explicitChatId = null, array $ipnData = []): bool
    {
        $order->loadMissing(['user', 'product']);

        // Check if already notified to prevent duplicate notifications
        $meta = is_array($order->payment_meta) ? $order->payment_meta : [];
        if (!empty($meta['telegram_notified_at'])) {
            Log::info("TelegramService: Order #{$order->invoice} already notified at {$meta['telegram_notified_at']}, skipping.");
            return true;
        }

        // Resolve chat_id / user telegram
        $chatId = $this->resolveChatId($order, $ipnData, $explicitChatId);

        if (empty($chatId)) {
            Log::info("TelegramService: No Telegram chat_id or user telegram found for Order #{$order->invoice}. Notification skipped.");
            return false;
        }

        // Ensure order has a download_token
        if (empty($order->download_token)) {
            $order->download_token = Str::random(40);
            $order->save();
        }

        $product = $order->product ?: ($order->product_id ? Product::find($order->product_id) : null);

        if ($product) {
            // Product Order Notification
            $productName = $product->name;
            $productVersion = $product->version ?: '1.0.0';
            $category = $product->category ?: 'Website Script';
            $price = number_format((float) ($order->amount ?? ($order->price ?? $product->price)), 2);
            $orderNumber = $order->order_number ?: 'ORD-' . $order->id;
            $invoice = $order->invoice ?: 'INV-' . $order->id;
            $downloadFile = $product->download_file ?: 'package.zip';
            $md5Checksum = $product->md5_checksum ?: 'N/A';

            // Generate direct download URL
            $downloadUrl = url('/api/telegram/downloads/file') . '?' . http_build_query([
                'telegram_id' => $chatId,
                'product_id' => $product->id,
                'token' => $order->download_token,
            ]);

            $text = "🎉 *Congratulations! You order was successfully confirmed*\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "📦 *PRODUCT DETAILS*\n"
                . "• *Product Name:* {$productName}\n"
                . "• *Version:* `v{$productVersion}`\n"
                . "• *Category:* {$category}\n"
                . "• *Price:* `\${$price} USD`\n"
                . "• *Order Number:* `{$orderNumber}`\n"
                . "• *Invoice:* `{$invoice}`\n"
                . "• *Release File:* `{$downloadFile}`\n"
                . "• *MD5 Checksum:* `{$md5Checksum}`\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Click the *Download* button below to download your product:";

            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '📥 Download',
                            'url' => $downloadUrl,
                        ],
                        [
                            'text' => '📦 Download in Telegram',
                            'callback_data' => "dl_file_{$product->id}_{$productVersion}",
                        ],
                    ],
                    [
                        [
                            'text' => '📂 All Versions',
                            'callback_data' => "dl_prod_{$product->id}",
                        ],
                        [
                            'text' => '🏠 Main Menu',
                            'callback_data' => 'main_menu',
                        ],
                    ],
                ],
            ];
        } else {
            // Balance Top-up Order Notification
            $amount = number_format((float) ($order->amount ?? $order->price), 2);
            $orderNumber = $order->order_number ?: 'ORD-' . $order->id;
            $invoice = $order->invoice ?: 'INV-' . $order->id;
            $paymentMethod = $order->payment_method ?: 'CoinPayments Crypto Gateway';
            $txnId = $order->txn_id ?: 'N/A';
            $userBalance = $order->user ? number_format((float) $order->user->fresh()->balance, 2) : $amount;

            $text = "🎉 *Congratulations! You order was successfully confirmed*\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "💰 *BALANCE TOP-UP DETAILS*\n"
                . "• *Invoice:* `{$invoice}`\n"
                . "• *Order Number:* `{$orderNumber}`\n"
                . "• *Amount Credited:* `\${$amount} USD`\n"
                . "• *Payment Method:* {$paymentMethod}\n"
                . "• *TXN ID:* `{$txnId}`\n"
                . "• *New Balance:* `\${$userBalance} USD`\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Your account balance has been successfully updated!";

            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '🛍️ Browse Products',
                            'callback_data' => 'menu_products',
                        ],
                        [
                            'text' => '💳 My Balance',
                            'callback_data' => 'menu_balance',
                        ],
                    ],
                    [
                        [
                            'text' => '🏠 Main Menu',
                            'callback_data' => 'main_menu',
                        ],
                    ],
                ],
            ];
        }

        $res = $this->sendMessage($chatId, $text, $replyMarkup);

        // Update payment_meta with notification timestamp and result
        $meta['telegram_notified_at'] = now()->toIso8601String();
        $meta['telegram_chat_id'] = $chatId;
        $meta['telegram_response_ok'] = !empty($res['ok']);

        $order->update(['payment_meta' => $meta]);

        Log::info("TelegramService: Order #{$order->invoice} confirmation notification dispatched to chat_id: {$chatId} (ok: " . ($meta['telegram_response_ok'] ? 'yes' : 'no') . ")");

        return true;
    }
}
