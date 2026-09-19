<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_missing_parameters_returns_error(): void
    {
        $response = $this->postJson('/api/domain-validation', []);
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'status' => 'FAILED',
        ]);
    }

    public function test_invalid_account_key_returns_error(): void
    {
        $response = $this->postJson('/api/domain-validation', [
            'account_key' => 'INVALID-KEY',
            'domain' => 'my-app.example.com',
            'pid' => 1,
        ]);
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 'FAILED',
            'message' => 'User not found or invalid account key.',
        ]);
    }

    public function test_no_order_found_returns_error(): void
    {
        $user = User::first();
        
        $response = $this->postJson('/api/domain-validation', [
            'account_key' => $user->account_key,
            'domain' => 'my-app.example.com',
            'pid' => 99999, // non-existent product
        ]);
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'status' => 'FAILED',
            'message' => 'No order found for this user and product.',
        ]);
    }

    public function test_existing_registered_domain_returns_success(): void
    {
        $user = User::first();
        $order = Order::where('user_id', $user->id)->firstOrFail();

        // DatabaseSeeder seeds domain "sentinel.xundefined.io"
        $response = $this->postJson('/api/domain-validation', [
            'account_key' => $user->account_key,
            'domain' => 'sentinel.xundefined.io',
            'pid' => $order->product_id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'OK',
            'message' => 'Domain is valid and registered.',
        ]);

        $domain = Domain::where('domain', 'sentinel.xundefined.io')->first();
        $this->assertGreaterThan(42, $domain->hits);
    }

    public function test_new_domain_auto_registers_when_quota_available(): void
    {
        $user = User::first();
        $order = Order::where('user_id', $user->id)->firstOrFail();
        $newDomain = 'newsite-' . time() . '.test';

        $response = $this->postJson('/api/domain-validation', [
            'account_key' => $user->account_key,
            'domain' => $newDomain,
            'pid' => $order->product_id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'OK',
            'message' => 'Domain registered and validated successfully.',
        ]);

        $this->assertDatabaseHas('domains', [
            'user_id' => $user->id,
            'domain' => $newDomain,
            'product_id' => $order->product_id,
        ]);
    }

    public function test_domain_quota_exceeded_returns_error(): void
    {
        $user = User::first();
        $order = Order::where('user_id', $user->id)->firstOrFail();
        $order->domain_quota = 1; // Limit to 1
        $order->save();

        // sentinel.xundefined.io is already registered (1 domain registered)
        $response = $this->postJson('/api/domain-validation', [
            'account_key' => $user->account_key,
            'domain' => 'extra-domain.com',
            'pid' => $order->product_id,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'status' => 'FAILED',
            'message' => 'Domain quota limit exceeded.',
        ]);
    }
}
