<?php

use App\Models\Invitecode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('non-admin users cannot access invitecode admin routes', function () {
    $guestResponse = $this->get('/admin/invitecode');
    $guestResponse->assertRedirect('/login');

    $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

    $memberResponse = $this->actingAs($member)->get('/admin/invitecode');
    $memberResponse->assertRedirect('/');
});

test('admin can view invite code matrix', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $code = Invitecode::create([
        'code' => 'XU-TEST-9988',
        'used' => false,
        'expired_at' => now()->addDays(5),
        'generate_via' => 'admin',
    ]);

    $response = $this->actingAs($admin)->get('/admin/invitecode');
    $response->assertStatus(200);
    $response->assertSee('INVITE CODE MATRIX');
    $response->assertSee('XU-TEST-9988');
    $response->assertSee('ACTIVE');
});

test('admin can create random or custom invite code with expiry', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->post('/admin/invitecode', [
        'code' => 'XU-VIP-CODE-2026',
        'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect('/admin/invitecode');

    $this->assertDatabaseHas('invitecodes', [
        'code' => 'XU-VIP-CODE-2026',
        'used' => false,
    ]);
});

test('admin can update an existing invite code', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $code = Invitecode::create([
        'code' => 'XU-OLD-KEY',
        'used' => false,
    ]);

    $response = $this->actingAs($admin)->put("/admin/invitecode/{$code->id}", [
        'code' => 'XU-NEW-KEY',
        'used' => '1',
    ]);

    $response->assertRedirect('/admin/invitecode');

    $this->assertDatabaseHas('invitecodes', [
        'id' => $code->id,
        'code' => 'XU-NEW-KEY',
        'used' => true,
    ]);
});

test('admin can delete an invite code', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $code = Invitecode::create([
        'code' => 'XU-DELETE-ME',
        'used' => false,
    ]);

    $response = $this->actingAs($admin)->delete("/admin/invitecode/{$code->id}");
    $response->assertRedirect('/admin/invitecode');

    $this->assertDatabaseMissing('invitecodes', [
        'id' => $code->id,
    ]);
});

test('admin can call random code generator endpoint', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->getJson('/admin/invitecode/generate-random');
    $response->assertStatus(200);
    $response->assertJsonStructure(['code']);
});

test('user registering with valid invite code claims it and marks as used', function () {
    $invite = Invitecode::create([
        'code' => 'XU-REG-VALID',
        'used' => false,
    ]);

    $response = $this->post('/register', [
        'name' => 'Agent Zero',
        'email' => 'agentzero@example.com',
        'password' => 'Passcode#123',
        'password_confirmation' => 'Passcode#123',
        'invite_key' => 'XU-REG-VALID',
    ]);

    $response->assertRedirect('/dashboard');

    $invite->refresh();
    expect($invite->used)->toBeTrue();
    expect($invite->used_by_user_id)->not->toBeNull();
});

test('user cannot register with expired or claimed invite code', function () {
    $claimedInvite = Invitecode::create([
        'code' => 'XU-CLAIMED-KEY',
        'used' => true,
    ]);

    $response = $this->post('/register', [
        'name' => 'Agent Late',
        'email' => 'agentlate@example.com',
        'password' => 'Passcode#123',
        'password_confirmation' => 'Passcode#123',
        'invite_key' => 'XU-CLAIMED-KEY',
    ]);

    $response->assertSessionHasErrors('invite_key');
    $this->assertGuest();
});
