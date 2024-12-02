<?php

use App\Exceptions\Auth\GoogleInvalidUserSecretKeyException;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('authenticates a user without 2FA and returns an access token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'two_factor_secret' => false,
    ]);

    $response = $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'user',
                'token',
            ],
        ]);
});

it('authenticates a user with 2FA enabled and does not return an access token', function () {
    $user = User::factory()->create([
        'email' => 'test2@example.com',
        'password' => bcrypt('password123'),
        'two_factor_secret' => true,
    ]);

    $response = $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'user',
            ],
        ])
        ->assertJsonMissing(['token']);
});

it('fails if email field is missing', function () {
    $response = $this->postJson(route('login'), [
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails if email format is invalid', function () {
    $response = $this->postJson(route('login'), [
        'email' => 'invalid-email',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('fails if password field is missing', function () {
    $response = $this->postJson(route('login'), [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
it('fails if the password is incorrect', function () {
    $user = User::factory()->create([
        'email' => 'test4@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'error' => __('auth.failed'),
        ]);
});

it('updates last_login and ip_address in the users table after successful login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email' => $user->email,
    ]);

    $updatedUser = User::find($user->id);
    expect($updatedUser->last_login)->not->toBeNull();
    expect($updatedUser->last_ip_address)->not->toBeNull();
});

it('creates a new entry in personal_access_tokens table for successful login without 2FA', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'desktop',
    ]);
});

it('does not create a token in personal_access_tokens table for users with 2FA enabled', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'two_factor_secret' => encrypt('2fa-secret'),
    ]);

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);
});

it('does not update users table or insert token for invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $updatedUser = User::find($user->id);
    expect($updatedUser->last_login)->toBeNull();
    expect($updatedUser->last_ip_address)->toBeNull();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);
});
it('sets the correct expiration date for the token in personal_access_tokens table', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $token = DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->where('tokenable_type', User::class)
        ->first();

    expect($token)->not->toBeNull()
        ->and((string) $token->expires_at)->toBe((string) now()->addMinutes(60));
});
it('does not update or create token for nonexistent users', function () {
    $this->postJson(route('login'), [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    $this->assertDatabaseCount('users', User::query()->count()); // Ensure no user is added or updated
    $this->assertDatabaseCount('personal_access_tokens', DB::table('personal_access_tokens')->count());
});
it('logs the correct IP address in the users table on successful login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ], [
        'REMOTE_ADDR' => '192.168.1.1',
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'last_ip_address' => '192.168.1.1',
    ]);
});

it('creates a new token on every successful login without 2FA', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $this->postJson(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $this->assertDatabaseCount('personal_access_tokens', 2);
});

it('validates a correct 2FA code and returns an access token', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret-key'),
    ]);
    $this->withoutMiddleware(\App\Http\Middleware\DecryptTokenLoginMiddleware::class);
    Sanctum::actingAs($user);

    // Mock Two-Factor Service
    $this->mock(TwoFactorService::class)
        ->shouldReceive('checkTwoFactor')
        ->once()
        ->andReturnTrue();

    $response = $this->postJson(route('2fa.verify-login'), [
        'google2fa' => 'valid-2fa-code',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'token',
            ],
        ]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'desktop',
    ]);
});
it('returns an error if 2FA code is missing', function () {
    $this->withoutMiddleware(\App\Http\Middleware\DecryptTokenLoginMiddleware::class);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret-key'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson(route('2fa.verify-login'), []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['google2fa']);
});
it('rejects an invalid 2FA code', function () {
    $this->withoutMiddleware(\App\Http\Middleware\DecryptTokenLoginMiddleware::class);

    //    $this->withoutExceptionHandling();
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret-key'),
    ]);

    Sanctum::actingAs($user);

    // Mock Two-Factor Service
    $this->mock(TwoFactorService::class)
        ->shouldReceive('checkTwoFactor')
        ->once()
        ->andThrow(new GoogleInvalidUserSecretKeyException);

    $response = $this->postJson(route('2fa.verify-login'), [
        'google2fa' => 'invalid-2fa-code',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => __('exceptions.'.GoogleInvalidUserSecretKeyException::class),
        ]);
});

it('rejects the request if the user has no 2FA setup', function () {
    $this->withoutMiddleware(\App\Http\Middleware\DecryptTokenLoginMiddleware::class);

    $user = User::factory()->create([
        'two_factor_secret' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson(route('2fa.verify-login'), [
        'google2fa' => 'any-code',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['google2fa']);
});

it('generates a token with correct expiration after successful 2FA validation', function () {
    $this->withoutMiddleware(\App\Http\Middleware\DecryptTokenLoginMiddleware::class);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret-key'),
    ]);

    Sanctum::actingAs($user);

    // Mock Two-Factor Service
    $this->mock(TwoFactorService::class)
        ->shouldReceive('checkTwoFactor')
        ->once()
        ->andReturnTrue();

    $response = $this->postJson(route('2fa.verify-login'), [
        'google2fa' => 'valid-2fa-code',
    ]);

    $response->assertStatus(200);

    $token = DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->first();

    expect($token)->not->toBeNull()
        ->and((string) $token->expires_at)->toBe((string) now()->addMinutes(60));
});

it('returns an error if the user is not authenticated', function () {

    $response = $this->postJson(route('2fa.verify-login'), [
        'google2fa' => 'valid-2fa-code',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => __('auth.login.encrypted_token_invalid'),
        ]);
});

it('enforces rate limiting on 2FA verification attempts', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('secret-key'),
    ]);

    Sanctum::actingAs($user);

    for ($i = 0; $i < 10; $i++) {
        $this->postJson(route('2fa.verify-login'), [
            'google2fa' => 'invalid-2fa-code',
        ]);
    }

    $response = $this->postJson(route('2fa.verify-login'), [
        'google2fa' => 'invalid-2fa-code',
    ]);

    $response->assertStatus(429);
});
