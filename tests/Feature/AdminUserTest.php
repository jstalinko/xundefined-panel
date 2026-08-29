<?php

use App\Models\Domain;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non-admin users cannot access user admin routes', function () {
    $guestResponse = $this->get('/admin/user');
    $guestResponse->assertRedirect('/login');

    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $memberResponse = $this->actingAs($member)->get('/admin/user');
    $memberResponse->assertRedirect('/');
});

test('admin can view users directory and see user stats', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $member = User::factory()->create([
        'name' => 'Agent Matrix',
        'email' => 'matrix@xundefined.local',
        'role' => User::ROLE_MEMBER,
        'invite_key' => 'KEY-MATRIX-01',
    ]);

    $response = $this->actingAs($admin)->get('/admin/user');
    $response->assertStatus(200);
    $response->assertSee('OPERATIVE DIRECTORY MATRIX');
    $response->assertSee('Agent Matrix');
    $response->assertSee('matrix@xundefined.local');
    $response->assertSee('KEY-MATRIX-01');
});

test('admin can create a new user operative', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post('/admin/user', [
        'name' => 'Agent Vector',
        'email' => 'vector@xundefined.local',
        'password' => 'Passcode#Vector99',
        'role' => 2,
        'invite_key' => 'XU-VECT-8899',
    ]);

    $response->assertRedirect('/admin/user');

    $this->assertDatabaseHas('users', [
        'name' => 'Agent Vector',
        'email' => 'vector@xundefined.local',
        'role' => 2,
        'invite_key' => 'XU-VECT-8899',
    ]);
});

test('admin can view user details show page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
    $product = Product::create([
        'name' => 'Rootkit Sentinel',
        'price' => 150000,
        'active' => true,
    ]);

    Domain::create([
        'user_id' => $member->id,
        'product_id' => $product->id,
        'domain' => 'sentinel.operative.local',
    ]);

    $response = $this->actingAs($admin)->get("/admin/user/{$member->id}");
    $response->assertStatus(200);
    $response->assertSee($member->name);
    $response->assertSee('sentinel.operative.local');
});

test('admin can update user profile and role', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $member = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@xundefined.local',
        'role' => 2,
    ]);

    $response = $this->actingAs($admin)->put("/admin/user/{$member->id}", [
        'name' => 'Promoted Commander',
        'email' => 'commander@xundefined.local',
        'role' => 1,
        'invite_key' => 'ROOT-KEY',
    ]);

    $response->assertRedirect('/admin/user');

    $this->assertDatabaseHas('users', [
        'id' => $member->id,
        'name' => 'Promoted Commander',
        'email' => 'commander@xundefined.local',
        'role' => 1,
    ]);
});

test('admin can delete other user but cannot delete self', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    // Delete other user
    $deleteOther = $this->actingAs($admin)->delete("/admin/user/{$member->id}");
    $deleteOther->assertRedirect('/admin/user');
    $this->assertDatabaseMissing('users', ['id' => $member->id]);

    // Attempt self deletion
    $deleteSelf = $this->actingAs($admin)->delete("/admin/user/{$admin->id}");
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});
