<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Invitecode;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Services\CoinPaymentsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class TelegramBotAndLegacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_telegram_auto_registration(): void
    {
        $uniqueId = 'test_' . time() . '_' . rand(100, 999);
        $response = $this->postJson('/api/telegram/init', [
            'telegram_id' => $uniqueId,
            'telegram_username' => 'test_operative',
            'first_name' => 'Agent',
            'last_name' => 'Zero',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['telegram_id' => $uniqueId]);
    }

    public function test_telegram_products_catalog_and_contents_structure(): void
    {
        $response = $this->getJson('/api/telegram/products');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('products');
        $this->assertNotEmpty($data);

        $first = $data[0];
        $this->assertArrayHasKey('contents', $first);
        $this->assertArrayHasKey('latest_release', $first);
        $this->assertArrayHasKey('version', $first);
        $this->assertArrayHasKey('file', $first);
        $this->assertArrayHasKey('md5checksum', $first);

        $firstId = $first['id'];
        $detailResponse = $this->getJson('/api/telegram/products/' . $firstId);
        $detailResponse->assertStatus(200);
        $detailResponse->assertJson(['success' => true]);
        $detailProduct = $detailResponse->json('product');
        $this->assertIsArray($detailProduct['contents']);
        $this->assertNotEmpty($detailProduct['contents']);

        // Check release keys inside contents
        $release = $detailProduct['contents'][0];
        $this->assertArrayHasKey('file', $release);
        $this->assertArrayHasKey('version', $release);
        $this->assertTrue(isset($release['md5checksum']) || isset($release['md5sum']));
    }

    public function test_products_table_has_dropped_unwanted_columns(): void
    {
        $this->assertFalse(Schema::hasColumn('products', 'version'));
        $this->assertFalse(Schema::hasColumn('products', 'demo_url'));
        $this->assertFalse(Schema::hasColumn('products', 'download_url'));
        $this->assertFalse(Schema::hasColumn('products', 'documentation_url'));
        $this->assertFalse(Schema::hasColumn('products', 'stock'));

        // Columns that must exist
        $this->assertTrue(Schema::hasColumn('products', 'name'));
        $this->assertTrue(Schema::hasColumn('products', 'pid'));
        $this->assertTrue(Schema::hasColumn('products', 'price'));
        $this->assertTrue(Schema::hasColumn('products', 'contents'));
        $this->assertTrue(Schema::hasColumn('products', 'description'));
        $this->assertTrue(Schema::hasColumn('products', 'active'));
        $this->assertTrue(Schema::hasColumn('products', 'published'));
    }

    public function test_telegram_buy_flow_with_balance(): void
    {
        $uniqueId = 'buyer_' . time() . '_' . rand(100, 999);
        $this->postJson('/api/telegram/init', [
            'telegram_id' => $uniqueId,
            'telegram_username' => 'buyer_test',
        ]);

        // Top-up demo balance
        $topupResponse = $this->postJson('/api/telegram/balance/topup-demo', [
            'telegram_id' => $uniqueId,
            'amount' => 150,
        ]);
        $topupResponse->assertStatus(200);
        $this->assertEquals(150, (float) $topupResponse->json('new_balance'));

        // Buy first product
        $product = Product::where('status', 'active')->first();
        $this->assertNotNull($product);

        $buyResponse = $this->postJson('/api/telegram/orders/buy', [
            'telegram_id' => $uniqueId,
            'product_id' => $product->id,
        ]);
        $buyResponse->assertStatus(200);
        $buyResponse->assertJson(['success' => true]);

        $orderNumber = $buyResponse->json('order.order_number');
        $order = Order::where('order_number', $orderNumber)->first();
        $this->assertNotNull($order);
        $this->assertNotEmpty($order->invoice);
        $this->assertEquals(3, $order->domain_quota);
        $this->assertTrue($order->isCompleted());

        // Check downloads
        $downResponse = $this->getJson('/api/telegram/downloads?telegram_id=' . $uniqueId);
        $downResponse->assertStatus(200);
        $this->assertCount(1, $downResponse->json('downloads'));
        $firstDown = $downResponse->json('downloads.0');
        $this->assertEquals($product->name, $firstDown['product_name']);
        $this->assertEquals((float) $product->price, (float) $firstDown['price']);
        $this->assertIsArray($firstDown['contents']);
        $this->assertNotEmpty($firstDown['contents']);
        $this->assertArrayNotHasKey('license_key', $firstDown);

        // Check orders history
        $ordersResponse = $this->getJson('/api/telegram/orders?telegram_id=' . $uniqueId);
        $ordersResponse->assertStatus(200);
        $this->assertCount(1, $ordersResponse->json('orders'));
        $firstOrd = $ordersResponse->json('orders.0');
        $this->assertArrayNotHasKey('license_key', $firstOrd);
    }

    public function test_coinpayments_real_topup_and_ipn_flow(): void
    {
        $uniqueId = 'crypto_user_' . time() . '_' . rand(100, 999);
        $initRes = $this->postJson('/api/telegram/init', ['telegram_id' => $uniqueId]);
        $initRes->assertStatus(200);
        $user = User::where('telegram_id', $uniqueId)->firstOrFail();

        // 1. Create Top-up Transaction via Telegram API
        $topupRes = $this->postJson('/api/telegram/balance/create-topup', [
            'telegram_id' => $uniqueId,
            'amount' => 50,
            'currency2' => 'LTCT',
        ]);
        $topupRes->assertStatus(200);
        $topupRes->assertJson(['success' => true]);

        $invoice = $topupRes->json('transaction.invoice');
        $txnId = $topupRes->json('transaction.txn_id');
        $this->assertNotEmpty($invoice);
        $this->assertNotEmpty($txnId);

        $order = Order::where('invoice', $invoice)->first();
        $this->assertNotNull($order);
        $this->assertNull($order->product_id); // Topup has no product_id
        $this->assertEquals(50, (float) $order->price);
        $this->assertTrue($order->isPending());

        // 2. Check Status endpoint
        $statusRes = $this->getJson('/api/telegram/balance/topup-status/' . $invoice);
        $statusRes->assertStatus(200);
        $statusRes->assertJson([
            'success' => true,
            'invoice' => $invoice,
            'is_pending' => true,
        ]);

        // 3. Web Payment Gateway view
        $viewRes = $this->get('/payment/' . $invoice);
        $viewRes->assertStatus(200);
        $viewRes->assertSee($invoice);

        // 4. Send CoinPayments IPN Callback confirming the payment
        $mockService = Mockery::mock(CoinPaymentsService::class);
        $mockService->shouldReceive('validateIpn')->andReturn([
            'valid' => true,
            'error' => null,
            'data' => [
                'txn_id' => $txnId,
                'status' => 100,
                'status_text' => 'Complete',
                'invoice' => $invoice,
                'amount1' => 50,
                'amount2' => '50.00000000',
                'currency1' => 'USD',
                'currency2' => 'USDT.TRC20',
            ],
        ]);
        $this->app->instance(CoinPaymentsService::class, $mockService);

        $ipnRes = $this->post('/api/coinpayments/ipn', [
            'txn_id' => $txnId,
            'status' => 100,
            'invoice' => $invoice,
        ]);
        $ipnRes->assertStatus(200);
        $ipnRes->assertSeeText('IPN OK');

        // Verify Order is marked completed and user balance is credited
        $order->refresh();
        $this->assertTrue($order->isCompleted());
        $user->refresh();
        $this->assertEquals(50.00, (float) $user->balance);
    }

    public function test_telegram_domain_management(): void
    {
        $uniqueId = 'domain_user_' . time() . '_' . rand(100, 999);
        $this->postJson('/api/telegram/init', ['telegram_id' => $uniqueId]);

        $domainName = 'test-' . time() . '.xundefined.io';
        $addResponse = $this->postJson('/api/telegram/domains/add', [
            'telegram_id' => $uniqueId,
            'domain' => $domainName,
        ]);
        $addResponse->assertStatus(200);
        $addResponse->assertJson(['success' => true]);

        $domainId = $addResponse->json('domain.id');
        $domain = Domain::find($domainId);
        $this->assertNotNull($domain);
        $domain->incrementHits();
        $domain->refresh();
        $this->assertEquals(1, $domain->hits);

        // Delete domain
        $delResponse = $this->postJson('/api/telegram/domains/delete', [
            'telegram_id' => $uniqueId,
            'domain_id' => $domainId,
        ]);
        $delResponse->assertStatus(200);
        $this->assertNull(Domain::find($domainId));
    }

    public function test_legacy_models_and_database_features(): void
    {
        // 1. User
        $user = User::where('email', 'admin@admin.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isAdmin());
        $this->assertEquals('System Admin', $user->role_name);
        $this->assertEquals('XU-ROOT-7789', $user->account_key);
        $this->assertTrue(Schema::hasColumn('users', 'account_key'));
        $this->assertFalse(Schema::hasColumn('users', 'invite_key'));
        $this->assertFalse(Schema::hasColumn('orders', 'license_key'));

        // 2. Product
        $sentinel = Product::where('slug', 'x-sentinel-threat-bot')->first();
        $this->assertNotNull($sentinel);
        $this->assertNotEmpty($sentinel->pid);
        $this->assertIsArray($sentinel->contents);
        $this->assertTrue($sentinel->active);
        $this->assertTrue($sentinel->published);

        // 3. Invitecode
        $code = Invitecode::where('code', 'XU-ROOT-7789')->first();
        $this->assertNotNull($code);
        $this->assertEquals('CLAIMED', $code->status_label);
        $this->assertFalse($code->isValid());

        $activeCode = Invitecode::where('code', 'XU-OPERATIVE-2026')->first();
        $this->assertNotNull($activeCode);
        $this->assertEquals('ACTIVE', $activeCode->status_label);
        $this->assertTrue($activeCode->isValid());

        $randomCode = Invitecode::generateCode('XU');
        $this->assertStringStartsWith('XU-', $randomCode);

        // 4. Post
        $postsCount = Post::where('is_published', true)->count();
        $this->assertGreaterThanOrEqual(4, $postsCount);

        // 5. ActivityLog
        $this->assertGreaterThanOrEqual(1, ActivityLog::count());
    }

    public function test_telegram_topup_amounts_and_cryptocurrencies(): void
    {
        $uniqueId = 'crypto_tester_' . time() . '_' . rand(100, 999);
        $this->postJson('/api/telegram/init', ['telegram_id' => $uniqueId]);

        $amounts = [60, 80, 100, 200];
        $currency = 'LTCT';

        foreach ($amounts as $amount) {
            $res = $this->postJson('/api/telegram/balance/create-topup', [
                'telegram_id' => $uniqueId,
                'amount' => $amount,
                'currency2' => $currency,
            ]);

            $res->assertStatus(200);
            $res->assertJson(['success' => true]);
            $this->assertEquals($amount, (float) $res->json('transaction.amount'));
            $this->assertEquals($currency, $res->json('transaction.payment_currency'));
            $this->assertNotEmpty($res->json('transaction.payment_address'));
        }
    }

    public function test_telegram_download_file_endpoint_authorization_and_delivery(): void
    {
        $uniqueId = 'dl_user_' . time() . '_' . rand(100, 999);
        $initRes = $this->postJson('/api/telegram/init', ['telegram_id' => $uniqueId]);
        $initRes->assertStatus(200);
        $user = User::where('telegram_id', $uniqueId)->firstOrFail();

        $product = Product::first();
        if (!$product) {
            $product = Product::create([
                'name' => 'Sentinel Test Suite',
                'slug' => 'sentinel-test-suite',
                'description' => 'Test suite for downloads',
                'price' => 50,
                'category' => 'Web App',
                'status' => 'active',
                'contents' => [
                    [
                        'file' => 'x-sentinel-v2.5.0.zip',
                        'version' => '2.5.0',
                        'md5checksum' => 'c99c3b634ef19ce88a9eb4539ca4bab5',
                    ]
                ],
            ]);
        }

        // 1. Attempt download without purchasing -> 403 Forbidden
        $unauthRes = $this->getJson("/api/telegram/downloads/file?telegram_id={$uniqueId}&product_id={$product->id}&version=2.5.0");
        $unauthRes->assertStatus(403);
        $unauthRes->assertJson(['success' => false]);

        // 2. Add balance and purchase product
        $user->balance = 100;
        $user->save();

        $buyRes = $this->postJson('/api/telegram/orders/buy', [
            'telegram_id' => $uniqueId,
            'product_id' => $product->id,
        ]);
        $buyRes->assertStatus(200);

        // 3. User now owns product -> Download file endpoint succeeds
        $downRes = $this->get("/api/telegram/downloads/file?telegram_id={$uniqueId}&product_id={$product->id}&version=2.5.0");
        $downRes->assertStatus(200);
        $downRes->assertDownload('x-sentinel-v2.5.0.zip');
    }
}

