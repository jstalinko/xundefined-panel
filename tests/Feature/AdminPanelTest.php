<?php

namespace Tests\Feature;

use App\Models\Invitecode;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@admin.com')->first();
        $this->regularUser = User::create([
            'name' => 'Regular Operative',
            'email' => 'operative@user.local',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_admin_panel(): void
    {
        $response = $this->get('/xingzheng-panel');
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/xingzheng-panel');
        $response->assertRedirect('/');
    }

    public function test_admin_can_access_dashboard_overview(): void
    {
        $response = $this->actingAs($this->admin)->get('/xingzheng-panel');
        $response->assertStatus(200);
        $response->assertSee('SYSTEM ADMIN CONTROL CENTER');
        $response->assertSee('TOTAL USERS');
        $response->assertSee('TOTAL ORDERS');
    }

    public function test_admin_can_manage_products(): void
    {
        // 1. Index
        $response = $this->actingAs($this->admin)->get('/xingzheng-panel/product');
        $response->assertStatus(200);
        $response->assertSee('PRODUCT CATALOG MANAGEMENT');

        // 2. Create form
        $createResponse = $this->actingAs($this->admin)->get('/xingzheng-panel/product/create');
        $createResponse->assertStatus(200);

        // 3. Store new product
        $storeResponse = $this->actingAs($this->admin)->post('/xingzheng-panel/product', [
            'name' => 'Zero-Day Shield Daemon',
            'pid' => 'PID-ZDAY-99',
            'price' => 120.00,
            'description' => 'Real-time proactive kernel memory isolation tool.',
            'active' => 1,
            'published' => 1,
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Zero-Day Shield Daemon']);

        $product = Product::where('name', 'Zero-Day Shield Daemon')->first();
        $this->assertNotNull($product);

        // 4. Show product
        $showResponse = $this->actingAs($this->admin)->get('/xingzheng-panel/product/' . $product->id);
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Zero-Day Shield Daemon');

        // 5. Edit form
        $editResponse = $this->actingAs($this->admin)->get('/xingzheng-panel/product/' . $product->id . '/edit');
        $editResponse->assertStatus(200);

        // 6. Update product
        $updateResponse = $this->actingAs($this->admin)->put('/xingzheng-panel/product/' . $product->id, [
            'name' => 'Zero-Day Shield Daemon Pro',
            'price' => 150.00,
            'description' => 'Updated memory defense engine.',
            'active' => 1,
            'published' => 1,
        ]);
        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Zero-Day Shield Daemon Pro']);

        // 7. Toggle publish
        $toggleResponse = $this->actingAs($this->admin)->patch('/xingzheng-panel/product/' . $product->id . '/toggle-publish');
        $toggleResponse->assertRedirect();

        // 8. Delete product
        $deleteResponse = $this->actingAs($this->admin)->delete('/xingzheng-panel/product/' . $product->id);
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_admin_can_manage_orders(): void
    {
        // 1. Orders Index
        $response = $this->actingAs($this->admin)->get('/xingzheng-panel/order');
        $response->assertStatus(200);
        $response->assertSee('TRANSACTION MATRIX');

        $order = Order::first();
        $this->assertNotNull($order);

        // 2. Order Show
        $showResponse = $this->actingAs($this->admin)->get('/xingzheng-panel/order/' . $order->id);
        $showResponse->assertStatus(200);
        $showResponse->assertSee($order->invoice);

        // 3. Order Update Quota / Status
        $updateResponse = $this->actingAs($this->admin)->put('/xingzheng-panel/order/' . $order->id, [
            'domain_quota' => 10,
            'status' => 'completed',
        ]);
        $updateResponse->assertRedirect();
        $this->assertEquals(10, $order->fresh()->domain_quota);
    }

    public function test_admin_can_manage_posts(): void
    {
        // 1. Posts Index
        $response = $this->actingAs($this->admin)->get('/xingzheng-panel/post');
        $response->assertStatus(200);

        // 2. Create form
        $createResponse = $this->actingAs($this->admin)->get('/xingzheng-panel/post/create');
        $createResponse->assertStatus(200);

        // 3. Store post
        $storeResponse = $this->actingAs($this->admin)->post('/xingzheng-panel/post', [
            'title' => 'Test Security Dispatch',
            'content' => 'System protocol analysis is complete.',
            'category' => 'announcement',
            'is_published' => 1,
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('posts', ['title' => 'Test Security Dispatch']);

        $post = Post::where('title', 'Test Security Dispatch')->first();

        // 4. Update post
        $updateResponse = $this->actingAs($this->admin)->put('/xingzheng-panel/post/' . $post->id, [
            'title' => 'Updated Security Dispatch',
            'content' => 'Revised bulletin description.',
            'category' => 'news',
            'is_published' => 1,
        ]);
        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('posts', ['title' => 'Updated Security Dispatch']);

        // 5. Delete post
        $delResponse = $this->actingAs($this->admin)->delete('/xingzheng-panel/post/' . $post->id);
        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_admin_can_manage_invite_codes(): void
    {
        // 1. Invitecode Index
        $response = $this->actingAs($this->admin)->get('/xingzheng-panel/invitecode');
        $response->assertStatus(200);

        // 2. Generate random code AJAX
        $randResponse = $this->actingAs($this->admin)->getJson('/xingzheng-panel/invitecode/generate-random');
        $randResponse->assertStatus(200);
        $this->assertNotEmpty($randResponse->json('code'));

        // 3. Store invitecode
        $code = 'XU-TEST-' . rand(1000, 9999);
        $storeResponse = $this->actingAs($this->admin)->post('/xingzheng-panel/invitecode', [
            'code' => $code,
            'generate_via' => 'admin',
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('invitecodes', ['code' => $code]);

        $invite = Invitecode::where('code', $code)->first();

        // 4. Delete unclaimed code
        $delResponse = $this->actingAs($this->admin)->delete('/xingzheng-panel/invitecode/' . $invite->id);
        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('invitecodes', ['id' => $invite->id]);
    }

    public function test_admin_can_manage_users(): void
    {
        // 1. Users Index
        $response = $this->actingAs($this->admin)->get('/xingzheng-panel/user');
        $response->assertStatus(200);
        $response->assertSee('OPERATIVE IDENTITY');

        // 2. Create User form
        $createResponse = $this->actingAs($this->admin)->get('/xingzheng-panel/user/create');
        $createResponse->assertStatus(200);

        // 3. Store new User
        $newUserEmail = 'agent_' . time() . '@xundefined.local';
        $storeResponse = $this->actingAs($this->admin)->post('/xingzheng-panel/user', [
            'name' => 'Agent Shadow',
            'email' => $newUserEmail,
            'password' => 'supersecretpass123',
            'role' => 2, // Member
            'account_key' => 'XU-SHADOW-KEY',
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $newUserEmail]);

        $newUser = User::where('email', $newUserEmail)->first();

        // 4. Show User
        $showResponse = $this->actingAs($this->admin)->get('/xingzheng-panel/user/' . $newUser->id);
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Agent Shadow');

        // 5. Edit User
        $editResponse = $this->actingAs($this->admin)->get('/xingzheng-panel/user/' . $newUser->id . '/edit');
        $editResponse->assertStatus(200);

        // 6. Update User
        $updateResponse = $this->actingAs($this->admin)->put('/xingzheng-panel/user/' . $newUser->id, [
            'name' => 'Agent Shadow Updated',
            'email' => $newUserEmail,
            'role' => 2,
        ]);
        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('users', ['name' => 'Agent Shadow Updated']);
    }

    public function test_admin_user_table_shows_telegram_and_balance_and_searches(): void
    {
        $testOperative = User::create([
            'name' => 'Zero Cool',
            'email' => 'zerocool@hackers.local',
            'password' => bcrypt('password123'),
            'role' => 2,
            'telegram_id' => '99887766',
            'telegram_username' => 'zerocool_tg',
            'balance' => 450.75,
            'account_key' => 'XU-ZERO-COOL',
        ]);

        // 1. Check user table displays telegram link and balance
        $response = $this->actingAs($this->admin)->get('/xingzheng-panel/user');
        $response->assertStatus(200);
        $response->assertSee('t.me/zerocool_tg');
        $response->assertSee('$450.75');
        $response->assertSee('TELEGRAM');
        $response->assertSee('BALANCE');

        // 2. Search by telegram username
        $searchTg = $this->actingAs($this->admin)->get('/xingzheng-panel/user?q=zerocool_tg');
        $searchTg->assertStatus(200);
        $searchTg->assertSee('Zero Cool');

        // 3. Search by name
        $searchName = $this->actingAs($this->admin)->get('/xingzheng-panel/user?q=Zero+Cool');
        $searchName->assertStatus(200);
        $searchName->assertSee('zerocool@hackers.local');

        // 4. Search by telegram ID
        $searchTgId = $this->actingAs($this->admin)->get('/xingzheng-panel/user?q=99887766');
        $searchTgId->assertStatus(200);
        $searchTgId->assertSee('Zero Cool');

        // 5. Search by user ID
        $searchId = $this->actingAs($this->admin)->get('/xingzheng-panel/user?q=' . $testOperative->id);
        $searchId->assertStatus(200);
        $searchId->assertSee('Zero Cool');

        // 6. Search by email
        $searchEmail = $this->actingAs($this->admin)->get('/xingzheng-panel/user?q=zerocool@hackers.local');
        $searchEmail->assertStatus(200);
        $searchEmail->assertSee('Zero Cool');
    }

    public function test_admin_product_search_and_public_news_page(): void
    {
        $product = Product::create([
            'name' => 'Quantum Bypass Exploiter',
            'slug' => 'quantum-bypass-exploiter',
            'pid' => 'PID-QBYPASS-77',
            'price' => 250.00,
            'category' => 'Exploit Kit',
            'description' => 'Advanced memory injection toolkit.',
            'active' => true,
            'published' => true,
        ]);

        // 1. Search product by name
        $searchProd = $this->actingAs($this->admin)->get('/xingzheng-panel/product?q=Quantum+Bypass');
        $searchProd->assertStatus(200);
        $searchProd->assertSee('PID-QBYPASS-77');

        // 2. Search product by PID
        $searchPid = $this->actingAs($this->admin)->get('/xingzheng-panel/product?q=QBYPASS-77');
        $searchPid->assertStatus(200);
        $searchPid->assertSee('Quantum Bypass Exploiter');

        // 3. Search product by category
        $searchCat = $this->actingAs($this->admin)->get('/xingzheng-panel/product?q=Exploit+Kit');
        $searchCat->assertStatus(200);
        $searchCat->assertSee('Quantum Bypass Exploiter');

        // 4. Test Public News page (using app.css, not admin layouts)
        $post = Post::create([
            'title' => 'Critical Zero-Day Protocol Advisory',
            'slug' => 'critical-zero-day-protocol-advisory',
            'category' => 'announcement',
            'content' => 'All nodes must upgrade to the latest TLS handshake patches immediately.',
            'is_published' => true,
        ]);

        $newsResponse = $this->get('/news/' . $post->slug);
        $newsResponse->assertStatus(200);
        $newsResponse->assertSee('Critical Zero-Day Protocol Advisory');
        $newsResponse->assertSee('app.css');
        $newsResponse->assertDontSee('OPERATIVE IDENTITY');
        $newsResponse->assertDontSee('dashboard-sidebar');

        // Check posts route alias
        $postsResponse = $this->get('/posts/' . $post->slug);
        $postsResponse->assertStatus(200);
        $postsResponse->assertSee('Critical Zero-Day Protocol Advisory');
    }

    public function test_auth_login_and_logout_flow(): void
    {
        // 1. Login view
        $viewResponse = $this->get('/login');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('XUNDEFINED DASHBOARD');

        // 2. Login submit
        $loginResponse = $this->post('/login', [
            'email' => 'admin@admin.com',
            'password' => 'password',
        ]);
        $loginResponse->assertRedirect();
        $this->assertAuthenticated();

        // 3. Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_can_manage_and_view_domains_list_and_details(): void
    {
        $owner = User::create([
            'name' => 'Domain Operative',
            'email' => 'domainop@user.local',
            'password' => bcrypt('password123'),
            'role' => 2,
            'telegram_id' => '77665544',
            'telegram_username' => 'domain_operative',
            'account_key' => 'XU-DOM-OP',
        ]);

        $product = Product::first();

        $domain = \App\Models\Domain::create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'domain' => 'node-alpha.xundefined.io',
            'status' => 'active',
            'ip_address' => '192.0.2.10',
            'hits' => 15,
        ]);

        // 1. List Domains (/xingzheng-panel/domain)
        $indexRes = $this->actingAs($this->admin)->get('/xingzheng-panel/domain');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('REGISTERED DOMAINS DIRECTORY');
        $indexRes->assertSee('node-alpha.xundefined.io');
        $indexRes->assertSee('t.me/domain_operative');
        $indexRes->assertSee($product->name);
        $indexRes->assertSee($domain->created_at->format('Y-m-d H:i'));
        $indexRes->assertSee('VIEW DETAILS');

        // 2. View Domain Details (/xingzheng-panel/domain/{id})
        $showRes = $this->actingAs($this->admin)->get('/xingzheng-panel/domain/' . $domain->id);
        $showRes->assertStatus(200);
        $showRes->assertSee('DOMAIN SPECIFICATIONS');
        $showRes->assertSee('node-alpha.xundefined.io');
        $showRes->assertSee('https://t.me/domain_operative');
        $showRes->assertSee($product->name);
        $showRes->assertSee('192.0.2.10');

        // 3. Update Domain Status
        $updateRes = $this->actingAs($this->admin)->put('/xingzheng-panel/domain/' . $domain->id, [
            'domain' => 'node-alpha.xundefined.io',
            'status' => 'suspended',
        ]);
        $updateRes->assertRedirect();
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_manage_free_tools_url_and_file_uploads(): void
    {
        // 1. Index page
        $indexRes = $this->actingAs($this->admin)->get('/xingzheng-panel/free-tool');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('FREE TOOLS REPOSITORY');

        // 2. Create page
        $createRes = $this->actingAs($this->admin)->get('/xingzheng-panel/free-tool/create');
        $createRes->assertStatus(200);
        $createRes->assertSee('CREATE NEW FREE TOOL');

        // 3. Store URL type Free Tool
        $storeUrlRes = $this->actingAs($this->admin)->post('/xingzheng-panel/free-tool', [
            'name' => 'Online Whois Scanner',
            'type' => 'url',
            'url' => 'https://whois.domaintools.com',
            'description' => 'Check domain whois records.',
            'is_active' => '1',
        ]);
        $storeUrlRes->assertRedirect('/xingzheng-panel/free-tool');
        $this->assertDatabaseHas('free_tools', [
            'name' => 'Online Whois Scanner',
            'type' => 'url',
            'url' => 'https://whois.domaintools.com',
        ]);

        // 4. Store File type Free Tools (.txt, .zip, .rar, .tar.gz)
        foreach (['wordlist.txt', 'toolkit.zip', 'archive.rar', 'bundle.tar.gz'] as $extFile) {
            $uploaded = \Illuminate\Http\UploadedFile::fake()->createWithContent($extFile, 'sample tool content ' . $extFile);
            $storeFileRes = $this->actingAs($this->admin)->post('/xingzheng-panel/free-tool', [
                'name' => 'File Tool ' . $extFile,
                'type' => 'file',
                'file' => $uploaded,
                'description' => 'Uploaded ' . $extFile,
                'is_active' => '1',
            ]);
            $storeFileRes->assertRedirect('/xingzheng-panel/free-tool');
            $this->assertDatabaseHas('free_tools', [
                'name' => 'File Tool ' . $extFile,
                'type' => 'file',
                'file_name' => $extFile,
            ]);
        }

        // 5. Disallowed file extension should fail validation
        $badFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('malware.exe', 'bad');
        $badRes = $this->actingAs($this->admin)->post('/xingzheng-panel/free-tool', [
            'name' => 'Invalid Tool',
            'type' => 'file',
            'file' => $badFile,
        ]);
        $badRes->assertSessionHasErrors('file');

        // 6. Download file tool from admin panel
        $tarTool = \App\Models\FreeTool::where('file_name', 'bundle.tar.gz')->firstOrFail();
        $dlRes = $this->actingAs($this->admin)->get('/xingzheng-panel/free-tool/' . $tarTool->id . '/download');
        $dlRes->assertStatus(200);
        $dlRes->assertDownload('bundle.tar.gz');

        // 7. Update & Delete Free Tool
        $editRes = $this->actingAs($this->admin)->get('/xingzheng-panel/free-tool/' . $tarTool->id . '/edit');
        $editRes->assertStatus(200);

        $updateRes = $this->actingAs($this->admin)->put('/xingzheng-panel/free-tool/' . $tarTool->id, [
            'name' => 'Updated TarGz Tool',
            'type' => 'file',
            'description' => 'Updated description',
            'is_active' => '1',
        ]);
        $updateRes->assertRedirect('/xingzheng-panel/free-tool');
        $this->assertDatabaseHas('free_tools', [
            'id' => $tarTool->id,
            'name' => 'Updated TarGz Tool',
        ]);

        $delRes = $this->actingAs($this->admin)->delete('/xingzheng-panel/free-tool/' . $tarTool->id);
        $delRes->assertRedirect('/xingzheng-panel/free-tool');
        $this->assertDatabaseMissing('free_tools', ['id' => $tarTool->id]);
    }
}
