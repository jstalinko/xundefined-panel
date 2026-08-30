<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CoinPaymentsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'coinpayments.public_key'   => 'test_pub_key_123',
        'coinpayments.private_key'  => 'test_priv_key_456',
        'coinpayments.merchant_id'  => 'test_merchant_id_789',
        'coinpayments.ipn_secret'   => 'test_ipn_secret_abc',
        'coinpayments.default_currency' => 'IDR',
        'coinpayments.default_crypto'   => 'USDT.TRC20',
    ]);
});

test('coinpayments service validates correct IPN with valid HMAC signature', function () {
    $service = app(CoinPaymentsService::class);

    $merchantId = 'test_merchant_id_789';
    $secret = 'test_ipn_secret_abc';

    $params = [
        'ipn_version' => '1.0',
        'ipn_type'    => 'api',
        'ipn_mode'    => 'hmac',
        'ipn_id'      => 'ipn_test_001',
        'merchant'    => $merchantId,
        'status'      => '100',
        'status_text' => 'Complete',
        'txn_id'      => 'CP_TXN_TEST_123',
        'currency1'   => 'IDR',
        'currency2'   => 'USDT.TRC20',
        'amount1'     => '150000',
        'amount2'     => '9.50000000',
        'invoice'     => 'INV-TEST-999',
    ];

    $rawContent = http_build_query($params);
    $hmac = hash_hmac('sha512', $rawContent, $secret);

    $request = \Illuminate\Http\Request::create(
        '/api/coinpayments/ipn',
        'POST',
        $params,
        [],
        [],
        ['HTTP_HMAC' => $hmac],
        $rawContent
    );

    $validation = $service->validateIpn($request);

    expect($validation['valid'])->toBeTrue();
    expect($validation['error'])->toBeNull();
    expect($validation['data']['txn_id'])->toBe('CP_TXN_TEST_123');
});

test('coinpayments service rejects IPN with wrong signature or merchant', function () {
    $service = app(CoinPaymentsService::class);

    $params = [
        'ipn_type' => 'api',
        'ipn_mode' => 'hmac',
        'merchant' => 'test_merchant_id_789',
        'status'   => '100',
    ];
    $rawContent = http_build_query($params);

    // 1. Wrong signature
    $reqWrongSig = \Illuminate\Http\Request::create(
        '/api/coinpayments/ipn',
        'POST',
        $params,
        [],
        [],
        ['HTTP_HMAC' => 'invalid_signature_here'],
        $rawContent
    );
    $val1 = $service->validateIpn($reqWrongSig);
    expect($val1['valid'])->toBeFalse();
    expect($val1['error'])->toContain('HMAC signature does not match');

    // 2. Wrong merchant ID
    $paramsWrongMerchant = array_merge($params, ['merchant' => 'WRONG_MERCHANT']);
    $rawWrongMerchant = http_build_query($paramsWrongMerchant);
    $hmac2 = hash_hmac('sha512', $rawWrongMerchant, 'test_ipn_secret_abc');

    $reqWrongMerchant = \Illuminate\Http\Request::create(
        '/api/coinpayments/ipn',
        'POST',
        $paramsWrongMerchant,
        [],
        [],
        ['HTTP_HMAC' => $hmac2],
        $rawWrongMerchant
    );
    $val2 = $service->validateIpn($reqWrongMerchant);
    expect($val2['valid'])->toBeFalse();
    expect($val2['error'])->toContain('No or incorrect Merchant ID');

    // 3. Wrong ipn_mode
    $paramsWrongMode = array_merge($params, ['ipn_mode' => 'httpauth']);
    $rawWrongMode = http_build_query($paramsWrongMode);
    $hmac3 = hash_hmac('sha512', $rawWrongMode, 'test_ipn_secret_abc');

    $reqWrongMode = \Illuminate\Http\Request::create(
        '/api/coinpayments/ipn',
        'POST',
        $paramsWrongMode,
        [],
        [],
        ['HTTP_HMAC' => $hmac3],
        $rawWrongMode
    );
    $val3 = $service->validateIpn($reqWrongMode);
    expect($val3['valid'])->toBeFalse();
    expect($val3['error'])->toContain('IPN Mode is not HMAC');
});

test('user can create coinpayments transaction via controller', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name'        => 'Quantum Bot License',
        'price'       => 200000,
        'description' => 'Quantum bot software package',
        'contents'    => [['file' => 'quantum.zip', 'version' => '1.0.0', 'md5sum' => '123456']],
        'active'      => true,
    ]);

    // Mock CoinPaymentsService
    $mockService = Mockery::mock(CoinPaymentsService::class);
    $mockService->shouldReceive('getDefaultCurrency')->andReturn('IDR');
    $mockService->shouldReceive('getDefaultCrypto')->andReturn('USDT.TRC20');
    $mockService->shouldReceive('createTransaction')
        ->once()
        ->andReturn([
            'amount'          => '12.50000000',
            'txn_id'          => 'CP_TXN_TEST_MOCK_123',
            'address'         => 'TXYZ123456789TronAddress',
            'dest_tag'        => null,
            'confirms_needed' => 1,
            'timeout'         => 3600,
            'checkout_url'    => 'https://coinpayments.net/checkout/123',
            'status_url'      => 'https://coinpayments.net/status/123',
            'qrcode_url'      => 'https://coinpayments.net/qr/123',
        ]);

    app()->instance(CoinPaymentsService::class, $mockService);

    $response = $this->actingAs($user)->post('/dashboard/coinpayments/create', [
        'product_id' => $product->id,
        'currency2'  => 'USDT.TRC20',
    ]);

    $order = Order::where('user_id', $user->id)->first();
    expect($order)->not->toBeNull();
    expect($order->status)->toBe('pending');
    expect($order->txn_id)->toBe('CP_TXN_TEST_MOCK_123');
    expect($order->payment_address)->toBe('TXYZ123456789TronAddress');
    expect($order->payment_amount)->toBe('12.50000000');

    $response->assertRedirect(route('dashboard.payment.show', $order->invoice));
});

test('api endpoint can create coinpayments transaction and return json', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name'        => 'Matrix Exploit Kit',
        'price'       => 350000,
        'description' => 'Exploit kit software package',
        'contents'    => [],
        'active'      => true,
    ]);

    // Mock CoinPaymentsService
    $mockService = Mockery::mock(CoinPaymentsService::class);
    $mockService->shouldReceive('getDefaultCurrency')->andReturn('IDR');
    $mockService->shouldReceive('getDefaultCrypto')->andReturn('LTC');
    $mockService->shouldReceive('createTransaction')
        ->once()
        ->andReturn([
            'amount'          => '0.24500000',
            'txn_id'          => 'CP_TXN_LTC_999',
            'address'         => 'LTCAddress123',
            'confirms_needed' => 3,
            'timeout'         => 7200,
            'status_url'      => 'https://coinpayments.net/status/ltc',
            'qrcode_url'      => 'https://coinpayments.net/qr/ltc',
        ]);

    app()->instance(CoinPaymentsService::class, $mockService);

    $response = $this->actingAs($user)->postJson('/api/coinpayments/create-transaction', [
        'product_id' => $product->id,
        'currency2'  => 'LTC',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'transaction' => [
                'txn_id'  => 'CP_TXN_LTC_999',
                'amount'  => '0.24500000',
                'address' => 'LTCAddress123',
            ],
        ]);
});

test('ipn webhook marks order completed on status 100 or 2', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name'        => 'Stealth Scanner',
        'price'       => 150000,
        'description' => 'Stealth tool',
        'contents'    => [],
        'active'      => true,
    ]);

    $order = Order::create([
        'invoice'          => 'INV-IPN-SUCCESS-01',
        'user_id'          => $user->id,
        'product_id'       => $product->id,
        'price'            => 150000,
        'domain_quota'     => 3,
        'payment_method'   => 'CoinPayments (USDT.TRC20)',
        'payment_currency' => 'USDT.TRC20',
        'txn_id'           => 'CP_TXN_IPN_001',
        'status'           => 'pending',
    ]);

    $merchantId = 'test_merchant_id_789';
    $secret = 'test_ipn_secret_abc';

    $params = [
        'ipn_version' => '1.0',
        'ipn_type'    => 'api',
        'ipn_mode'    => 'hmac',
        'merchant'    => $merchantId,
        'status'      => '100',
        'status_text' => 'Complete',
        'txn_id'      => 'CP_TXN_IPN_001',
        'currency1'   => 'IDR',
        'currency2'   => 'USDT.TRC20',
        'amount1'     => '150000',
        'amount2'     => '9.50000000',
        'invoice'     => 'INV-IPN-SUCCESS-01',
    ];

    $rawContent = http_build_query($params);
    $hmac = hash_hmac('sha512', $rawContent, $secret);

    $response = $this->call(
        'POST',
        '/api/coinpayments/ipn',
        $params,
        [],
        [],
        ['HTTP_HMAC' => $hmac],
        $rawContent
    );

    $response->assertStatus(200);
    expect($response->getContent())->toBe('IPN OK');

    $order->refresh();
    expect($order->status)->toBe('completed');
    expect($order->isCompleted())->toBeTrue();
});

test('ipn webhook marks order cancelled on status less than 0', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name'        => 'Stealth Scanner',
        'price'       => 150000,
        'description' => 'Stealth tool',
        'contents'    => [],
        'active'      => true,
    ]);

    $order = Order::create([
        'invoice'          => 'INV-IPN-CANCEL-01',
        'user_id'          => $user->id,
        'product_id'       => $product->id,
        'price'            => 150000,
        'domain_quota'     => 3,
        'payment_method'   => 'CoinPayments (USDT.TRC20)',
        'payment_currency' => 'USDT.TRC20',
        'txn_id'           => 'CP_TXN_CANCEL_001',
        'status'           => 'pending',
    ]);

    $merchantId = 'test_merchant_id_789';
    $secret = 'test_ipn_secret_abc';

    $params = [
        'ipn_version' => '1.0',
        'ipn_type'    => 'api',
        'ipn_mode'    => 'hmac',
        'merchant'    => $merchantId,
        'status'      => '-1',
        'status_text' => 'Timed out / Cancelled',
        'txn_id'      => 'CP_TXN_CANCEL_001',
        'currency1'   => 'IDR',
        'currency2'   => 'USDT.TRC20',
        'amount1'     => '150000',
        'invoice'     => 'INV-IPN-CANCEL-01',
    ];

    $rawContent = http_build_query($params);
    $hmac = hash_hmac('sha512', $rawContent, $secret);

    $response = $this->call(
        'POST',
        '/api/coinpayments/ipn',
        $params,
        [],
        [],
        ['HTTP_HMAC' => $hmac],
        $rawContent
    );

    $response->assertStatus(200);
    expect($response->getContent())->toBe('IPN OK');

    $order->refresh();
    expect($order->status)->toBe('cancelled');
});

test('user can view payment page and poll status', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name'        => 'Test Product',
        'price'       => 100000,
        'description' => 'Test desc',
        'contents'    => [],
        'active'      => true,
    ]);

    $order = Order::create([
        'invoice'          => 'INV-POLL-001',
        'user_id'          => $user->id,
        'product_id'       => $product->id,
        'price'            => 100000,
        'domain_quota'     => 3,
        'payment_method'   => 'CoinPayments (USDT.TRC20)',
        'payment_currency' => 'USDT.TRC20',
        'payment_amount'   => '6.50000000',
        'payment_address'  => 'TXyzTestAddress',
        'payment_timeout'  => 3600,
        'status'           => 'pending',
    ]);

    // View payment invoice page
    $viewRes = $this->actingAs($user)->get("/dashboard/payment/{$order->invoice}");
    $viewRes->assertStatus(200);
    $viewRes->assertSee('INV-POLL-001');
    $viewRes->assertSee('TXyzTestAddress');
    $viewRes->assertSee('6.50000000');

    // Poll status via API
    $statusRes = $this->actingAs($user)->getJson("/api/coinpayments/status/{$order->invoice}");
    $statusRes->assertStatus(200)
        ->assertJson([
            'success'      => true,
            'invoice'      => 'INV-POLL-001',
            'status'       => 'pending',
            'is_completed' => false,
        ]);
});

test('ipn webhook matches order by txn_id when invoice is missing in transfer payload', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name'        => 'Transfer Product',
        'price'       => 250000,
        'description' => 'Transfer tool',
        'contents'    => [],
        'active'      => true,
    ]);

    $order = Order::create([
        'invoice'          => 'INV-NO-INVOICE-01',
        'user_id'          => $user->id,
        'product_id'       => $product->id,
        'price'            => 250000,
        'domain_quota'     => 3,
        'payment_method'   => 'CoinPayments (LTCT)',
        'payment_currency' => 'LTCT',
        'txn_id'           => 'CTKH2RPRS6VWDFDLSBTUJNCUCX',
        'status'           => 'pending',
    ]);

    $merchantId = 'test_merchant_id_789';
    $secret = 'test_ipn_secret_abc';

    // Payload exactly matching CoinPayments transfer format without invoice
    $params = [
        'amount'        => '10.00000000',
        'amounti'       => '1000000000',
        'currency'      => 'LTCT',
        'fee'           => '0.00000000',
        'feei'          => '0',
        'fiat_amount'   => '494.45771363',
        'fiat_amounti'  => '49445771363',
        'fiat_coin'     => 'USD',
        'from'          => '8ac9528d191f8fb3f5b9daa25538c4ae',
        'ipn_id'        => '04a56ddbeb1ff700aa339b75fc5034e5',
        'ipn_mode'      => 'hmac',
        'ipn_type'      => 'transfer',
        'ipn_version'   => '1.0',
        'merchant'      => $merchantId,
        'status'        => '2',
        'status_text'   => 'Complete',
        'txn_id'        => 'CTKH2RPRS6VWDFDLSBTUJNCUCX',
    ];

    $rawContent = http_build_query($params);
    $hmac = hash_hmac('sha512', $rawContent, $secret);

    $response = $this->call(
        'POST',
        '/api/coinpayments/ipn',
        $params,
        [],
        [],
        ['HTTP_HMAC' => $hmac],
        $rawContent
    );

    $response->assertStatus(200);
    expect($response->getContent())->toBe('IPN OK');

    $order->refresh();
    expect($order->status)->toBe('completed');
    expect($order->isCompleted())->toBeTrue();
});

test('ipn webhook cleanly acknowledges unmatched non-order events without failing', function () {
    $merchantId = 'test_merchant_id_789';
    $secret = 'test_ipn_secret_abc';

    $params = [
        'amount'        => '5.00000000',
        'currency'      => 'BTC',
        'ipn_id'        => 'ipn_random_transfer',
        'ipn_mode'      => 'hmac',
        'ipn_type'      => 'transfer',
        'merchant'      => $merchantId,
        'status'        => '2',
        'status_text'   => 'Complete',
        'txn_id'        => 'UNMATCHED_EXTERNAL_TXN_999',
    ];

    $rawContent = http_build_query($params);
    $hmac = hash_hmac('sha512', $rawContent, $secret);

    $response = $this->call(
        'POST',
        '/api/coinpayments/ipn',
        $params,
        [],
        [],
        ['HTTP_HMAC' => $hmac],
        $rawContent
    );

    $response->assertStatus(200);
    expect($response->getContent())->toContain('IPN OK');
});

test('currencies endpoint returns accepted coins list', function () {
    $response = $this->getJson('/api/coinpayments/currencies');
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'coins',
        ]);
});

