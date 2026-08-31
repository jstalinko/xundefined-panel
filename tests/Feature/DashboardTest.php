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

test('user can view download vault and download purchased files when present in storage', function () {
    $user = User::factory()->create();

    $privateDir = storage_path('app/private');
    if (!is_dir($privateDir)) {
        @mkdir($privateDir, 0755, true);
    }
    file_put_contents($privateDir . '/stealth-daemon.tar.gz', 'RAW BINARY PAYLOAD CONTENTS');

    $product = Product::create([
        'name' => 'Stealth Protocol Daemon',
        'price' => 450000,
        'description' => 'Encrypted proxy daemon',
        'contents' => [['file' => 'stealth-daemon.tar.gz', 'version' => '3.0.1', 'md5sum' => md5_file($privateDir . '/stealth-daemon.tar.gz')]],
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
    $fileResponse->assertHeader('Content-Disposition', 'attachment; filename=stealth-daemon.tar.gz');
    expect($fileResponse->getFile()->getContent())->toEqual('RAW BINARY PAYLOAD CONTENTS');

    @unlink($privateDir . '/stealth-daemon.tar.gz');
});

test('download fails gracefully when package file does not exist in storage', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Non Existent Package Tool',
        'price' => 100000,
        'description' => 'Tool without physical file on disk',
        'contents' => [['file' => 'missing-file-404.zip', 'version' => '1.0.0', 'md5sum' => '123456']],
        'active' => true,
    ]);

    Order::create([
        'invoice' => 'INV-TEST-MISSING',
        'user_id' => $user->id,
        'product_id' => $product->id,
        'price' => 100000,
        'payment_method' => 'CyberPay',
        'status' => 'completed',
    ]);

    $fileResponse = $this->actingAs($user)->get("/dashboard/download/file/{$product->id}");
    $fileResponse->assertRedirect('/dashboard/download');
    $fileResponse->assertSessionHas('error');
});

test('user can download specific release version from multi-version contents', function () {
    $user = User::factory()->create();

    $privateDir = storage_path('app/private');
    if (!is_dir($privateDir)) {
        @mkdir($privateDir, 0755, true);
    }
    file_put_contents($privateDir . '/x-sentinel-v2.5.zip', 'VERSION 2.5 BINARY');
    file_put_contents($privateDir . '/x-sentinel-v2.4.zip', 'VERSION 2.4 BINARY');

    $product = Product::create([
        'name' => 'X-Sentinel Threat Bot',
        'price' => 175000,
        'description' => 'Threat bot with telemetry',
        'contents' => [
            [
                'file' => 'x-sentinel-v2.5.zip',
                'version' => '2.5.0',
                'changelog' => 'Added WebSocket live stream.',
                'md5sum' => md5_file($privateDir . '/x-sentinel-v2.5.zip')
            ],
            [
                'file' => 'x-sentinel-v2.4.zip',
                'version' => '2.4.0',
                'changelog' => 'Initial neural heuristics.',
                'md5sum' => md5_file($privateDir . '/x-sentinel-v2.4.zip')
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
    $fileResponse->assertHeader('Content-Disposition', 'attachment; filename=x-sentinel-v2.4.zip');
    expect($fileResponse->getFile()->getContent())->toEqual('VERSION 2.4 BINARY');

    @unlink($privateDir . '/x-sentinel-v2.5.zip');
    @unlink($privateDir . '/x-sentinel-v2.4.zip');
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

    $payload = base64_encode("api.shield.local|INVITE-TEST-KEY-123|{$product->pid}");

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

test('api ping endpoint returns ping status for a domain', function () {
    $response = $this->getJson('/api/ping/127.0.0.1');

    $response->assertStatus(200)
        ->assertJsonStructure(['online', 'latency', 'message']);
});

test('domain validation API returns success false when domain quota limit is reached', function () {
    $user = User::factory()->create([
        'invite_key' => 'QUOTA-TEST-KEY-999',
    ]);

    $product = Product::create([
        'name' => 'Quota Product',
        'price' => 50000,
        'description' => 'Quota test product',
        'contents' => [],
        'active' => true,
    ]);

    Order::create([
        'invoice' => 'INV-QUOTA-1',
        'user_id' => $user->id,
        'product_id' => $product->id,
        'price' => 50000,
        'domain_quota' => 2,
        'payment_method' => 'CyberPay',
        'status' => 'completed',
    ]);

    // Create 2 existing domains for quota of 2
    Domain::create(['user_id' => $user->id, 'product_id' => $product->id, 'domain' => 'dom1.quota.test']);
    Domain::create(['user_id' => $user->id, 'product_id' => $product->id, 'domain' => 'dom2.quota.test']);

    // Attempting validation for an unregistered domain when quota limit is reached
    $payload = base64_encode("unregistered.quota.test|QUOTA-TEST-KEY-999|{$product->pid}");

    $response = $this->postJson('/api/domain-validation', [
        'payload' => $payload,
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
        ]);

    expect($response->json('message'))->toContain('Domain quota limit reached');
});

test('user can register localhost, 127.0.0.1, and custom port domains', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Local Sentinel',
        'price' => 100000,
        'description' => 'Local node test',
        'contents' => [],
        'active' => true,
    ]);

    // Test localhost with port
    $res1 = $this->actingAs($user)->post('/dashboard/domain', [
        'domain' => 'localhost:8080',
        'product_id' => $product->id,
    ]);
    $res1->assertRedirect('/dashboard/domain');
    $this->assertDatabaseHas('domains', ['domain' => 'localhost:8080']);

    // Test 127.0.0.1 IP
    $res2 = $this->actingAs($user)->post('/dashboard/domain', [
        'domain' => '127.0.0.1',
        'product_id' => $product->id,
    ]);
    $res2->assertRedirect('/dashboard/domain');
    $this->assertDatabaseHas('domains', ['domain' => '127.0.0.1']);

    // Test 127.0.0.0 IP
    $res3 = $this->actingAs($user)->post('/dashboard/domain', [
        'domain' => '127.0.0.0:3000',
        'product_id' => $product->id,
    ]);
    $res3->assertRedirect('/dashboard/domain');
    $this->assertDatabaseHas('domains', ['domain' => '127.0.0.0:3000']);
});

test('activity logs are recorded on auth events and domain register/delete events', function () {
    $user = User::factory()->create(['password' => bcrypt('Passcode#123')]);

    $product = Product::create([
        'name' => 'Log Test Product',
        'price' => 50000,
        'description' => 'Product for logging tests',
        'contents' => [],
        'active' => true,
    ]);

    // Login activity
    $this->post('/login', [
        'email' => $user->email,
        'password' => 'Passcode#123',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'auth',
        'user_id' => $user->id,
    ]);

    // Register domain activity
    $this->actingAs($user)->post('/dashboard/domain', [
        'domain' => 'logtest.example.com',
        'product_id' => $product->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'domain',
        'user_id' => $user->id,
    ]);

    $domain = Domain::where('domain', 'logtest.example.com')->first();

    // Delete domain activity
    $this->actingAs($user)->delete("/dashboard/domain/{$domain->id}");

    $this->assertDatabaseHas('activity_logs', [
        'type' => 'domain',
        'user_id' => $user->id,
    ]);

    // View activity logs on dashboard
    $dashRes = $this->actingAs($user)->get('/dashboard');
    $dashRes->assertStatus(200);
    $dashRes->assertSee('ACTIVITY LOGS');
    $dashRes->assertSee('logtest.example.com');
});

test('user can update profile name without changing password', function () {
    $user = User::factory()->create([
        'name' => 'Agent OldName',
        'password' => bcrypt('OldPass#123'),
    ]);

    $response = $this->actingAs($user)->put('/dashboard/profile', [
        'name' => 'Agent NewName',
    ]);

    $response->assertRedirect();
    $user->refresh();
    expect($user->name)->toBe('Agent NewName');
    expect(\Illuminate\Support\Facades\Hash::check('OldPass#123', $user->password))->toBeTrue();
});

test('user can update profile password with valid current password', function () {
    $user = User::factory()->create([
        'name' => 'Agent PassUpdate',
        'password' => bcrypt('OldPass#123'),
    ]);

    $response = $this->actingAs($user)->put('/dashboard/profile', [
        'name' => 'Agent PassUpdate',
        'current_password' => 'OldPass#123',
        'password' => 'NewPass#456',
        'password_confirmation' => 'NewPass#456',
    ]);

    $response->assertRedirect();
    $user->refresh();
    expect(\Illuminate\Support\Facades\Hash::check('NewPass#456', $user->password))->toBeTrue();
});

test('profile password update fails with invalid current password', function () {
    $user = User::factory()->create([
        'name' => 'Agent WrongCurrent',
        'password' => bcrypt('OldPass#123'),
    ]);

    $response = $this->actingAs($user)->put('/dashboard/profile', [
        'name' => 'Agent WrongCurrent',
        'current_password' => 'WrongCurrent#999',
        'password' => 'NewPass#456',
        'password_confirmation' => 'NewPass#456',
    ]);

    $response->assertSessionHasErrors('current_password');
    $user->refresh();
    expect(\Illuminate\Support\Facades\Hash::check('OldPass#123', $user->password))->toBeTrue();
});

test('store only shows products where published is true and renders html description in accordion', function () {
    $user = User::factory()->create();

    $publishedProduct = Product::create([
        'name' => 'Published Cyber Tool',
        'price' => 150000,
        'description' => '<p>This is a <strong>strong</strong> feature list with <em>rich HTML</em>.</p>',
        'contents' => [['file' => 'tool.zip', 'version' => '1.0.0', 'md5sum' => 'abc123']],
        'active' => true,
        'published' => true,
    ]);

    $unpublishedProduct = Product::create([
        'name' => 'Draft Unreleased Module',
        'price' => 200000,
        'description' => '<p>Secret unreleased payload.</p>',
        'contents' => [['file' => 'secret.zip', 'version' => '1.0.0', 'md5sum' => 'def456']],
        'active' => true,
        'published' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard/store');
    $response->assertStatus(200);
    $response->assertSee('Published Cyber Tool');
    $response->assertDontSee('Draft Unreleased Module');
    $response->assertSee('Show Description');
    $response->assertSee('<p>This is a <strong>strong</strong> feature list with <em>rich HTML</em>.</p>', false);
});

test('download page displays show description accordion and renders html description', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'HTML Desc Module',
        'price' => 300000,
        'description' => '<p>Full package with <code>code snippets</code> &amp; feature list.</p>',
        'contents' => [['file' => 'html-tool.zip', 'version' => '1.0.0', 'md5sum' => 'abcdef1234567890']],
        'active' => true,
        'published' => true,
    ]);

    Order::create([
        'invoice' => 'INV-HTML-TEST',
        'user_id' => $user->id,
        'product_id' => $product->id,
        'price' => 300000,
        'payment_method' => 'CyberPay',
        'status' => 'completed',
    ]);

    $response = $this->actingAs($user)->get('/dashboard/download');
    $response->assertStatus(200);
    $response->assertSee('HTML Desc Module');
    $response->assertSee('Show Description');
    $response->assertSee('<p>Full package with <code>code snippets</code> &amp; feature list.</p>', false);
});

