<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CoinPaymentsService;
use App\Services\TelegramService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CoinPaymentsTelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_ipn_callback_sends_telegram_notification_with_product_details_and_download_button(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 12345,
                ],
            ], 200),
        ]);

        $telegramChatId = '987654321';
        $user = User::factory()->create([
            'telegram_id' => $telegramChatId,
            'telegram_username' => 'buyer_operative',
        ]);

        $product = Product::where('status', 'active')->firstOrFail();

        // Create pending product order
        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'invoice' => 'INV-TEST-001',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'price' => $product->price,
            'status' => Order::STATUS_PENDING,
            'txn_id' => 'CP_TXN_TEST_123',
            'payment_currency' => 'USDT.TRC20',
            'payment_amount' => (string) $product->price,
            'download_token' => 'test-download-token-xyz',
        ]);

        // Mock CoinPayments IPN validation
        $mockService = Mockery::mock(CoinPaymentsService::class);
        $mockService->shouldReceive('validateIpn')->andReturn([
            'valid' => true,
            'error' => null,
            'data' => [
                'txn_id' => $order->txn_id,
                'status' => 100,
                'status_text' => 'Complete',
                'invoice' => $order->invoice,
                'amount1' => (float) $product->price,
                'amount2' => (float) $product->price,
                'currency1' => 'USD',
                'currency2' => 'USDT.TRC20',
                'custom' => json_encode([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'chat_id' => $telegramChatId,
                ]),
            ],
        ]);
        $this->app->instance(CoinPaymentsService::class, $mockService);

        // Send IPN request
        $response = $this->post('/api/coinpayments/ipn', [
            'txn_id' => $order->txn_id,
            'status' => 100,
            'invoice' => $order->invoice,
        ]);

        $response->assertStatus(200);
        $response->assertSeeText('IPN OK');

        // Verify Order is marked completed
        $order->refresh();
        $this->assertTrue($order->isCompleted());
        $this->assertNotEmpty($order->payment_meta['telegram_notified_at'] ?? null);

        // Verify Telegram message was sent
        Http::assertSent(function (Request $request) use ($telegramChatId, $product, $order) {
            if (!str_contains($request->url(), '/sendMessage')) {
                return false;
            }

            $body = $request->data();

            // 1. Sent to correct chat_id
            $correctChatId = ((string) $body['chat_id'] === (string) $telegramChatId);

            // 2. Message contains the exact requested congratulations string
            $hasCongratulation = str_contains($body['text'], 'Congratulations! You order was successfully confirmed');

            // 3. Message contains product details
            $hasProductName = str_contains($body['text'], $product->name);
            $hasVersion = str_contains($body['text'], $product->version);
            $hasInvoice = str_contains($body['text'], $order->invoice);
            $hasOrderNumber = str_contains($body['text'], $order->order_number);
            $hasFile = str_contains($body['text'], (string) $product->download_file);

            // 4. Inline keyboard has "Download" button
            $replyMarkup = $body['reply_markup'] ?? [];
            $buttons = collect($replyMarkup['inline_keyboard'] ?? [])->flatten(1);

            $downloadButton = $buttons->first(function ($btn) {
                return isset($btn['text']) && str_contains($btn['text'], 'Download') && isset($btn['url']);
            });

            $hasDownloadButton = !empty($downloadButton);
            $validDownloadUrl = $hasDownloadButton && str_contains($downloadButton['url'], '/api/telegram/downloads/file');

            return $correctChatId
                && $hasCongratulation
                && $hasProductName
                && $hasVersion
                && $hasInvoice
                && $hasOrderNumber
                && $hasFile
                && $hasDownloadButton
                && $validDownloadUrl;
        });
    }

    public function test_ipn_callback_resolves_chat_id_from_custom_data_or_username(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'telegram_id' => '555123456',
            'telegram_username' => 'custom_trader',
        ]);

        $product = Product::where('status', 'active')->firstOrFail();

        $order = Order::create([
            'order_number' => 'ORD-CUSTOM-002',
            'invoice' => 'INV-CUSTOM-002',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'price' => $product->price,
            'status' => Order::STATUS_PENDING,
            'txn_id' => 'CP_CUSTOM_999',
            'payment_currency' => 'USDT.TRC20',
        ]);

        $mockService = Mockery::mock(CoinPaymentsService::class);
        $mockService->shouldReceive('validateIpn')->andReturn([
            'valid' => true,
            'error' => null,
            'data' => [
                'txn_id' => $order->txn_id,
                'status' => 100,
                'invoice' => $order->invoice,
                'custom' => json_encode([
                    'chat_id' => '555123456',
                ]),
            ],
        ]);
        $this->app->instance(CoinPaymentsService::class, $mockService);

        $response = $this->post('/api/coinpayments/ipn', [
            'txn_id' => $order->txn_id,
            'status' => 100,
        ]);

        $response->assertStatus(200);

        Http::assertSent(function (Request $request) {
            return (string) $request['chat_id'] === '555123456'
                && str_contains($request['text'], 'Congratulations! You order was successfully confirmed');
        });
    }

    public function test_ipn_topup_sends_notification_without_product_details(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'telegram_id' => '777888999',
            'balance' => 0,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TOPUP-003',
            'invoice' => 'TOPUP-TEST-003',
            'user_id' => $user->id,
            'product_id' => null,
            'amount' => 75.00,
            'price' => 75.00,
            'status' => Order::STATUS_PENDING,
            'txn_id' => 'CP_TOPUP_75',
            'payment_currency' => 'USDT.TRC20',
        ]);

        $mockService = Mockery::mock(CoinPaymentsService::class);
        $mockService->shouldReceive('validateIpn')->andReturn([
            'valid' => true,
            'error' => null,
            'data' => [
                'txn_id' => $order->txn_id,
                'status' => 100,
                'invoice' => $order->invoice,
            ],
        ]);
        $this->app->instance(CoinPaymentsService::class, $mockService);

        $response = $this->post('/api/coinpayments/ipn', [
            'txn_id' => $order->txn_id,
            'status' => 100,
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals(75.00, (float) $user->balance);

        Http::assertSent(function (Request $request) use ($order) {
            return (string) $request['chat_id'] === '777888999'
                && str_contains($request['text'], 'Congratulations! You order was successfully confirmed')
                && str_contains($request['text'], 'BALANCE TOP-UP DETAILS')
                && str_contains($request['text'], $order->invoice);
        });
    }

    public function test_duplicate_ipn_does_not_send_duplicate_telegram_notifications(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create(['telegram_id' => '111222333']);
        $product = Product::where('status', 'active')->firstOrFail();

        $order = Order::create([
            'order_number' => 'ORD-DUP-004',
            'invoice' => 'INV-DUP-004',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'price' => $product->price,
            'status' => Order::STATUS_PENDING,
            'txn_id' => 'CP_DUP_004',
        ]);

        $mockService = Mockery::mock(CoinPaymentsService::class);
        $mockService->shouldReceive('validateIpn')->andReturn([
            'valid' => true,
            'error' => null,
            'data' => [
                'txn_id' => $order->txn_id,
                'status' => 100,
                'invoice' => $order->invoice,
            ],
        ]);
        $this->app->instance(CoinPaymentsService::class, $mockService);

        // First IPN call
        $res1 = $this->post('/api/coinpayments/ipn', ['txn_id' => $order->txn_id, 'status' => 100]);
        $res1->assertStatus(200);

        // Second duplicate IPN call
        $res2 = $this->post('/api/coinpayments/ipn', ['txn_id' => $order->txn_id, 'status' => 100]);
        $res2->assertStatus(200);

        // Assert message sent exactly once
        Http::assertSentCount(1);
    }

    public function test_download_file_endpoint_with_token_and_telegram_id(): void
    {
        $user = User::factory()->create(['telegram_id' => '444555666']);
        $product = Product::where('status', 'active')->firstOrFail();

        $order = Order::create([
            'order_number' => 'ORD-DL-005',
            'invoice' => 'INV-DL-005',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'price' => $product->price,
            'status' => Order::STATUS_COMPLETED,
            'download_token' => 'secure-dl-token-12345',
        ]);

        // 1. Download via token
        $tokenRes = $this->get('/api/telegram/downloads/file?token=secure-dl-token-12345');
        $tokenRes->assertStatus(200);
        $tokenRes->assertHeader('content-disposition');

        // 2. Download via telegram_id and product_id
        $idRes = $this->get('/api/telegram/downloads/file?telegram_id=444555666&product_id=' . $product->id);
        $idRes->assertStatus(200);
        $idRes->assertHeader('content-disposition');

        // 3. Unauthorized user without purchase
        User::factory()->create(['telegram_id' => '999999999']);
        $failRes = $this->get('/api/telegram/downloads/file?telegram_id=999999999&product_id=' . $product->id);
        $failRes->assertStatus(403);

        // 4. Invalid token returns 403
        $badTokenRes = $this->get('/api/telegram/downloads/file?token=invalid-token-123');
        $badTokenRes->assertStatus(403);
    }
}
