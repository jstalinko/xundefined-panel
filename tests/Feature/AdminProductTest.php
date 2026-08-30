<?php

use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non-admin users and guests are blocked from admin routes', function () {
    $guestResponse = $this->get('/admin');
    $guestResponse->assertRedirect('/login');

    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $memberResponse = $this->actingAs($member)->get('/admin');
    $memberResponse->assertRedirect('/');

    $memberProductResponse = $this->actingAs($member)->get('/admin/product');
    $memberProductResponse->assertRedirect('/');
});

test('admin can access admin hub dashboard and see stats', function () {
    $admin = User::factory()->create([
        'name' => 'Root Administrator',
        'email' => 'root@xundefined.local',
        'role' => User::ROLE_ADMIN
    ]);

    $response = $this->actingAs($admin)->get('/admin');
    $response->assertStatus(200);
    $response->assertSee('SYSTEM ADMIN CONTROL CENTER');
    $response->assertSee('TOTAL USERS');
    $response->assertSee('TOTAL DOMAINS');
    $response->assertSee('TOTAL ORDERS');
    $response->assertSee('REVENUE');
    $response->assertSee('LATEST ORDERS');
    $response->assertSee('ALL REGISTERED DOMAINS');
});

test('admin can view products list and search products', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $p1 = Product::create([
        'name' => 'Cyber Packet Sniffer',
        'price' => 250000,
        'description' => 'Real-time packet capture tool',
        'active' => true,
    ]);

    $p2 = Product::create([
        'name' => 'DNS Tunnel Gateway',
        'price' => 190000,
        'description' => 'Encrypted DNS tunneling binary',
        'active' => true,
    ]);

    $response = $this->actingAs($admin)->get('/admin/product');
    $response->assertStatus(200);
    $response->assertSee('Cyber Packet Sniffer');
    $response->assertSee('DNS Tunnel Gateway');
    $response->assertSee('PRODUCT CATALOG MANAGEMENT');

    // Search query test
    $searchResponse = $this->actingAs($admin)->get('/admin/product?q=Sniffer');
    $searchResponse->assertStatus(200);
    $searchResponse->assertSee('Cyber Packet Sniffer');
    $searchResponse->assertDontSee('DNS Tunnel Gateway');
});

test('admin can view create page and store a new product with release packages', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // View create form
    $createResponse = $this->actingAs($admin)->get('/admin/product/create');
    $createResponse->assertStatus(200);
    $createResponse->assertSee('PRODUCT SPECIFICATIONS');

    // Submit new product
    $postResponse = $this->actingAs($admin)->post('/admin/product', [
        'name' => 'X-Exploit Framework v4',
        'price' => 450000,
        'description' => 'Advanced penetration testing and payload delivery framework.',
        'active' => '1',
        'releases' => [
            [
                'file' => 'x-framework-v4.0.0.zip',
                'version' => '4.0.0',
                'changelog' => 'Initial kernel architecture.',
                'md5sum' => '11223344556677889900aabbccddeeff'
            ],
            [
                'file' => 'x-framework-v4.1.0.zip',
                'version' => '4.1.0',
                'changelog' => 'Added WebSocket live stream.',
                'md5sum' => 'aabbccddeeff00112233445566778899'
            ]
        ]
    ]);

    $postResponse->assertRedirect('/admin/product');

    $this->assertDatabaseHas('products', [
        'name' => 'X-Exploit Framework v4',
        'price' => 450000,
        'active' => true,
    ]);

    $product = Product::where('name', 'X-Exploit Framework v4')->first();
    expect($product->contents)->toBeArray();
    expect(count($product->contents))->toBe(2);
    expect($product->contents[0]['version'])->toBe('4.0.0');
    expect($product->contents[1]['file'])->toBe('x-framework-v4.1.0.zip');
});

test('admin can view product details and edit product', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $product = Product::create([
        'name' => 'Quantum Crypter',
        'price' => 300000,
        'description' => 'Polymorphic shellcode crypter',
        'contents' => [
            [
                'file' => 'crypter-v1.0.zip',
                'version' => '1.0.0',
                'changelog' => 'Base build',
                'md5sum' => 'abcde12345'
            ]
        ],
        'active' => true,
    ]);

    // View show
    $showResponse = $this->actingAs($admin)->get("/admin/product/{$product->id}");
    $showResponse->assertStatus(200);
    $showResponse->assertSee('Quantum Crypter');
    $showResponse->assertSee('crypter-v1.0.zip');

    // View edit
    $editResponse = $this->actingAs($admin)->get("/admin/product/{$product->id}/edit");
    $editResponse->assertStatus(200);
    $editResponse->assertSee('Quantum Crypter');

    // Update product
    $updateResponse = $this->actingAs($admin)->put("/admin/product/{$product->id}", [
        'name' => 'Quantum Crypter Enterprise',
        'price' => 350000,
        'description' => 'Updated polymorphic engine with AV evasion.',
        'active' => '1',
        'releases' => [
            [
                'file' => 'crypter-v2.0.zip',
                'version' => '2.0.0',
                'changelog' => 'Upgraded engine',
                'md5sum' => 'fedcba54321'
            ]
        ]
    ]);

    $updateResponse->assertRedirect('/admin/product');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Quantum Crypter Enterprise',
        'price' => 350000,
    ]);
});

test('admin can delete a product', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $product = Product::create([
        'name' => 'Legacy Trojan Disinfector',
        'price' => 50000,
        'description' => 'Old tool',
        'active' => false,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/product/{$product->id}");
    $response->assertRedirect('/admin/product');

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('admin can view orders matrix and search orders', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $member = User::factory()->create(['name' => 'Buyer Smith', 'email' => 'buyer@example.com']);
    $product = Product::create([
        'name' => 'Stealth Proxy Tool',
        'price' => 120000,
        'active' => true,
    ]);

    $order = Order::create([
        'invoice' => 'INV-TEST-8899',
        'user_id' => $member->id,
        'product_id' => $product->id,
        'price' => 120000,
        'payment_method' => 'CyberPay Instant Gateway',
        'status' => 'completed',
    ]);

    $response = $this->actingAs($admin)->get('/admin/order');
    $response->assertStatus(200);
    $response->assertSee('CUSTOMER ORDERS');
    $response->assertSee('INV-TEST-8899');
    $response->assertSee('Buyer Smith');
    $response->assertSee('Stealth Proxy Tool');
});

test('admin can view posts management matrix and create new post', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $post = Post::create([
        'slug' => 'release-v3',
        'title' => 'Release Notes Version 3.0',
        'content' => 'Full kernel rewrite with async IO.',
        'category' => 'changelog',
        'is_published' => true,
    ]);

    $response = $this->actingAs($admin)->get('/admin/post');
    $response->assertStatus(200);
    $response->assertSee('POSTS MANAGEMENT');
    $response->assertSee('Release Notes Version 3.0');

    // Create new post
    $createResponse = $this->actingAs($admin)->post('/admin/post', [
        'title' => 'Important Maintenance Notice',
        'content' => 'Server optimization schedule.',
        'category' => 'announcement',
        'is_published' => '1',
    ]);

    $createResponse->assertRedirect('/admin/post');
    $this->assertDatabaseHas('posts', [
        'title' => 'Important Maintenance Notice',
        'category' => 'announcement',
    ]);
});

test('admin can update order domain quota', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $member = User::factory()->create(['name' => 'Buyer QuotaTest']);
    $product = Product::create([
        'name' => 'Quota Managed Module',
        'price' => 150000,
        'active' => true,
    ]);

    $order = Order::create([
        'invoice' => 'INV-QUOTA-1001',
        'user_id' => $member->id,
        'product_id' => $product->id,
        'price' => 150000,
        'payment_method' => 'CyberPay',
        'domain_quota' => 1,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($admin)->put("/admin/order/{$order->id}", [
        'domain_quota' => 5,
    ]);

    $response->assertRedirect('/admin/order');
    $order->refresh();
    expect($order->domain_quota)->toBe(5);
});
