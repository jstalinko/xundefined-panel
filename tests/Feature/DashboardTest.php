<?php

use App\Models\Domain;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view domain management page', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Neural Net Agent',
        'price' => 150000,
        'description' => 'Autonomous reconnaissance agent',
        'contents' => [['file' => 'agent.zip', 'version' => '1.0.0', 'md5sum' => 'abc12345']],
        'active' => true,
    ]);

    Domain::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'domain' => 'node1.xundefined.local',
    ]);

    $response = $this->actingAs($user)->get('/dashboard/domain');

    $response->assertStatus(200);
    $response->assertSee('DOMAINS');
    $response->assertSee('node1.xundefined.local');
    $response->assertSee('REGISTER DOMAIN');
});

test('user can register a new valid domain', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Ghost Recon',
        'price' => 250000,
        'description' => 'Security payload scanner',
        'contents' => [['file' => 'ghost.zip', 'version' => '2.1.0', 'md5sum' => 'deadbeef']],
        'active' => true,
    ]);

    $response = $this->actingAs($user)->post('/dashboard/domain', [
        'domain' => 'recon.network.io',
        'product_id' => $product->id,
    ]);

    $response->assertRedirect('/dashboard/domain');
    $this->assertDatabaseHas('domains', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'domain' => 'recon.network.io',
    ]);
});

test('domain registration validates format and uniqueness', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Ghost Recon',
        'price' => 250000,
        'description' => 'Security payload scanner',
        'contents' => [['file' => 'ghost.zip', 'version' => '2.1.0', 'md5sum' => 'deadbeef']],
        'active' => true,
    ]);

    Domain::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'domain' => 'existing.domain.com',
    ]);

    // Test duplicate domain
    $responseDup = $this->actingAs($user)->post('/dashboard/domain', [
        'domain' => 'existing.domain.com',
        'product_id' => $product->id,
    ]);
    $responseDup->assertSessionHasErrors(['domain']);

    // Test invalid format
    $responseInvalid = $this->actingAs($user)->post('/dashboard/domain', [
        'domain' => 'invalid_domain_format',
        'product_id' => $product->id,
    ]);
    $responseInvalid->assertSessionHasErrors(['domain']);
});

test('user can delete their own domain', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Ghost Recon',
        'price' => 250000,
        'description' => 'Security payload scanner',
        'contents' => [['file' => 'ghost.zip', 'version' => '2.1.0', 'md5sum' => 'deadbeef']],
        'active' => true,
    ]);

    $domain = Domain::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'domain' => 'todelete.org',
    ]);

    $response = $this->actingAs($user)->delete("/dashboard/domain/{$domain->id}");

    $response->assertRedirect('/dashboard/domain');
    $this->assertDatabaseMissing('domains', [
        'id' => $domain->id,
    ]);
});

test('user can view cyber store and purchase an unowned product', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Cipher Breaker Tool',
        'price' => 300000,
        'description' => 'High speed hash brute-force engine',
        'contents' => [['file' => 'cipher.zip', 'version' => '1.0.0', 'md5sum' => '0123456789']],
        'active' => true,
    ]);

    // View store
    $response = $this->actingAs($user)->get('/dashboard/store');
    $response->assertStatus(200);
    $response->assertSee('Cipher Breaker Tool');
    $response->assertSee('BUY PRODUCT');

    // Purchase product
    $purchaseResponse = $this->actingAs($user)->post('/dashboard/store/purchase', [
        'product_id' => $product->id,
        'payment_method' => 'Instant Cyber Gateway',
    ]);

    $purchaseResponse->assertRedirect('/dashboard/download');

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'price' => 300000,
        'status' => 'completed',
    ]);

    // View store again, now should show Purchased status and link to download
    $storeResponse = $this->actingAs($user)->get('/dashboard/store');
    $storeResponse->assertStatus(200);
    $storeResponse->assertSee('Purchased');
    $storeResponse->assertSee('/dashboard/download');
});

test('user can view download vault and download purchased files', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Stealth Protocol Daemon',
        'price' => 450000,
        'description' => 'Encrypted proxy daemon',
        'contents' => [['file' => 'stealth-daemon.tar.gz', 'version' => '3.0.1', 'md5sum' => 'fedcba987654']],
        'active' => true,
    ]);

    Order::create([
        'invoice' => 'INV-TEST-001',
        'user_id' => $user->id,
        'product_id' => $product->id,
        'price' => $product->price,
        'payment_method' => 'CyberPay',
        'status' => 'completed',
    ]);

    // View download vault
    $downloadPageResponse = $this->actingAs($user)->get('/dashboard/download');
    $downloadPageResponse->assertStatus(200);
    $downloadPageResponse->assertSee('Stealth Protocol Daemon');
    $downloadPageResponse->assertSee('INV-TEST-001');

    // Trigger file download
    $fileResponse = $this->actingAs($user)->get("/dashboard/download/file/{$product->id}");
    $fileResponse->assertStatus(200);
    $fileResponse->assertHeader('Content-Disposition', 'attachment; filename="stealth-daemon.tar.gz"');
    expect($fileResponse->getContent())->toContain('XUNDEFINED ENCRYPTED PAYLOAD VAULT');
    expect($fileResponse->getContent())->toContain('Stealth Protocol Daemon');
});

test('user can download specific release version from multi-version contents', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'X-Sentinel Threat Bot',
        'price' => 175000,
        'description' => 'Threat bot with telemetry',
        'contents' => [
            [
                'file' => 'x-sentinel-v2.5.zip',
                'version' => '2.5.0',
                'changelog' => 'Added WebSocket live stream.',
                'md5sum' => 'a9f1b2c3d4e5f60718293a4b5c6d7e8f'
            ],
            [
                'file' => 'x-sentinel-v2.4.zip',
                'version' => '2.4.0',
                'changelog' => 'Initial neural heuristics.',
                'md5sum' => '7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b'
            ]
        ],
        'active' => true,
    ]);

    Order::create([
        'invoice' => 'INV-TEST-MULTI',
        'user_id' => $user->id,
        'product_id' => $product->id,
        'price' => $product->price,
        'payment_method' => 'CyberPay',
        'status' => 'completed',
    ]);

    // View download page and see both versions
    $downloadPageResponse = $this->actingAs($user)->get('/dashboard/download');
    $downloadPageResponse->assertStatus(200);
    $downloadPageResponse->assertSee('v2.5.0');
    $downloadPageResponse->assertSee('v2.4.0');
    $downloadPageResponse->assertSee('x-sentinel-v2.5.zip');
    $downloadPageResponse->assertSee('x-sentinel-v2.4.zip');

    // Download v2.4.0 specifically
    $fileResponse = $this->actingAs($user)->get("/dashboard/download/file/{$product->id}?version=2.4.0");
    $fileResponse->assertStatus(200);
    $fileResponse->assertHeader('Content-Disposition', 'attachment; filename="x-sentinel-v2.4.zip"');
    expect($fileResponse->getContent())->toContain('Release Ver   : 2.4.0');
    expect($fileResponse->getContent())->toContain('x-sentinel-v2.4.zip');
});

test('user can view xNotes list and filter by category or search', function () {
    $user = User::factory()->create();

    Post::create([
        'slug' => 'first-announcement',
        'title' => 'Important Security Notice',
        'content' => 'Please update your security tokens immediately.',
        'category' => 'announcement',
        'is_published' => true,
    ]);

    Post::create([
        'slug' => 'changelog-v1',
        'title' => 'Patch Notes Version 1.2',
        'content' => 'Fixed domain resolution latency issues.',
        'category' => 'changelog',
        'is_published' => true,
    ]);

    // View notes index
    $response = $this->actingAs($user)->get('/dashboard/notes');
    $response->assertStatus(200);
    $response->assertSee('xNOTES');
    $response->assertSee('Important Security Notice');
    $response->assertSee('Patch Notes Version 1.2');

    // Filter by category
    $filterResponse = $this->actingAs($user)->get('/dashboard/notes?category=announcement');
    $filterResponse->assertStatus(200);
    $filterResponse->assertSee('Important Security Notice');
    $filterResponse->assertDontSee('Patch Notes Version 1.2');

    // Search
    $searchResponse = $this->actingAs($user)->get('/dashboard/notes?q=latency');
    $searchResponse->assertStatus(200);
    $searchResponse->assertSee('Patch Notes Version 1.2');
    $searchResponse->assertDontSee('Important Security Notice');
});

test('user can view individual note detail by slug', function () {
    $user = User::factory()->create();

    $post = Post::create([
        'slug' => 'quantum-encryption-guide',
        'title' => 'Quantum Encryption Fundamentals',
        'content' => 'Detailed explanation of quantum key distribution and AES algorithms.',
        'category' => 'tutorial',
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)->get("/dashboard/notes/{$post->slug}");
    $response->assertStatus(200);
    $response->assertSee('Quantum Encryption Fundamentals');
    $response->assertSee('Detailed explanation of quantum key distribution');
    $response->assertSee('TUTORIAL');
});

test('domain model incrementHits atomically increments hits count', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'name' => 'Cyber Proxy',
        'price' => 100000,
        'description' => 'Proxy node',
        'contents' => [],
        'active' => true,
    ]);

    $domain = Domain::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'domain' => 'proxy.xundefined.local',
        'hits' => 0,
    ]);

    $domain->incrementHits();
    expect($domain->fresh()->hits)->toBe(1);

    $domain->incrementHits();
    expect($domain->fresh()->hits)->toBe(2);
});

test('domain validation API increments hits on successful validation', function () {
    $user = User::factory()->create([
        'invite_key' => 'INVITE-TEST-KEY-123',
    ]);

    $product = Product::create([
        'name' => 'API Shield',
        'price' => 50000,
        'description' => 'API Shield',
        'contents' => [],
        'active' => true,
    ]);

    $domain = Domain::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'domain' => 'api.shield.local',
        'hits' => 5,
    ]);

    $payload = base64_encode("api.shield.local|INVITE-TEST-KEY-123|{$product->id}");

    $response = $this->postJson('/api/domain-validation', [
        'payload' => $payload,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Domain registered',
        ]);

    expect($domain->fresh()->hits)->toBe(6);
});
