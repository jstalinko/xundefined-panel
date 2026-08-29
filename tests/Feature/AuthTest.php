<?php

use App\Models\Invitecode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login page can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('XUNDEFINED DASHBOARD');
    $response->assertSee('OPERATIVE IDENTITY');
    $response->assertSee('SECURITY PASSCODE');
});

test('register page can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertSee('CLEARANCE REGISTRATION');
    $response->assertSee('INVITE KEY');
});

test('unauthenticated users are redirected to login when accessing dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('users can register with valid invite code', function () {
    $invite = Invitecode::create([
        'code' => 'CIPHER99',
        'used' => false,
    ]);

    $response = $this->post('/register', [
        'name' => 'Agent Shadow',
        'email' => 'shadow@xundefined.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'invite_key' => 'CIPHER99',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'name' => 'Agent Shadow',
        'email' => 'shadow@xundefined.local',
        'role' => User::ROLE_MEMBER,
        'invite_key' => 'CIPHER99',
    ]);

    $invite->refresh();
    expect($invite->used)->toBeTrue();
});

test('registration rejects invalid non-existent invite code', function () {
    $response = $this->post('/register', [
        'name' => 'Fake Agent',
        'email' => 'fake@xundefined.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'invite_key' => 'NON-EXISTENT-CODE',
    ]);

    $response->assertSessionHasErrors('invite_key');
    $this->assertGuest();
});

test('registration requires invite_key and matching password', function () {
    $response = $this->post('/register', [
        'name' => 'Agent Rogue',
        'email' => 'rogue@xundefined.local',
        'password' => 'password123',
        'password_confirmation' => 'mismatch_pass',
        'invite_key' => '',
    ]);

    $response->assertSessionHasErrors(['invite_key', 'password']);
    $this->assertGuest();
});

test('checkEmail endpoint accurately checks availability', function () {
    User::factory()->create(['email' => 'taken@xundefined.local']);

    $takenRes = $this->getJson('/auth/check-email?email=taken@xundefined.local');
    $takenRes->assertStatus(200);
    $takenRes->assertJson(['valid' => true, 'exists' => true]);

    $availRes = $this->getJson('/auth/check-email?email=fresh@xundefined.local');
    $availRes->assertStatus(200);
    $availRes->assertJson(['valid' => true, 'exists' => false]);
});

test('checkInvite endpoint accurately checks validity', function () {
    Invitecode::create([
        'code' => 'XU-VALID-CODE',
        'used' => false,
    ]);

    Invitecode::create([
        'code' => 'XU-USED-CODE',
        'used' => true,
    ]);

    $validRes = $this->getJson('/auth/check-invite?code=XU-VALID-CODE');
    $validRes->assertStatus(200);
    $validRes->assertJson(['valid' => true]);

    $usedRes = $this->getJson('/auth/check-invite?code=XU-USED-CODE');
    $usedRes->assertStatus(200);
    $usedRes->assertJson(['valid' => false]);

    $invalidRes = $this->getJson('/auth/check-invite?code=UNKNOWN');
    $invalidRes->assertStatus(200);
    $invalidRes->assertJson(['valid' => false]);
});

test('users can authenticate using email and password', function () {
    $user = User::factory()->create([
        'email' => 'operative@xundefined.local',
        'password' => Hash::make('secretpass123'),
        'role' => User::ROLE_MEMBER,
        'invite_key' => 'KEY12345',
    ]);

    $response = $this->post('/login', [
        'email' => 'operative@xundefined.local',
        'password' => 'secretpass123',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create([
        'email' => 'target@xundefined.local',
        'password' => Hash::make('correctpass'),
    ]);

    $this->post('/login', [
        'email' => 'target@xundefined.local',
        'password' => 'wrongpass',
    ]);

    $this->assertGuest();
});

test('authenticated user can view dashboard with cyber sidebar and user identity', function () {
    $user = User::factory()->create([
        'name' => 'Commander Neo',
        'email' => 'neo@xundefined.local',
        'role' => User::ROLE_ADMIN,
        'invite_key' => 'MATRIX01',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Commander Neo');
    $response->assertSee('YOUR INVITE CODE :');
    $response->assertSee('MATRIX01');
    $response->assertSee('ACCESS OUR FREE TOOLS');
    $response->assertSee('cyber-sidebar');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/login');
});
