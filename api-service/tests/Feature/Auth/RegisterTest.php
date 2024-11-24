<?php

//test('example', function () {
//    $response = $this->get('/');
//
//    $response->assertStatus(200);
//});

use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\PersonalAccessToken;

test('Register user successfully', function () {
    Event::fake(); // Fake all events
    $fakeUser = User::factory()->makeOne();

    // Send the registration request
    $response = $this->postJson(route('register'), [
        'first_name' => $fakeUser->first_name,
        'last_name' => $fakeUser->last_name,
        'email' => $fakeUser->email,
        'password' => 'password',
    ]);

    // Assert the response status
    $response->assertStatus(201);
    // Assert the Registered event was fired
    Event::assertDispatched(Registered::class, function ($event) use ($fakeUser) {
        return $event->user->email === $fakeUser->email;
    });
    // Assert the response structure
    $response->assertJsonStructure([
        'message',
        'data' => [
            'token' => [
                'access_token',
            ],
        ],
    ]);

    // Retrieve the newly created user
    $user = User::query()->where('email', $fakeUser->email)->first();
    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->toBeNull();

    // Retrieve the access token from the response
    $accessToken = $response->json('data.token.access_token');
    expect($accessToken)->not->toBeNull();

    // Decode and verify the access token using Sanctum
    $tokenModel = PersonalAccessToken::findToken($accessToken);
    expect($tokenModel)->not->toBeNull()
        ->and($tokenModel->tokenable_id)->toBe($user->id);
});

test('Try to register with not verified email', function () {
    Event::fake(); // Fake all events
    $fakeUser = User::factory()->unverified()->create();
    $fakeUser2 = User::factory()->unverified()->state(['email' => $fakeUser->email])->make();

    // Send the registration request
    $response = $this->postJson(route('register'), [
        'first_name' => $fakeUser2->first_name,
        'last_name' => $fakeUser2->last_name,
        'email' => $fakeUser2->email,
        'password' => 'password',
    ]);

    // Assert the response status
    $response->assertStatus(201);
    Event::assertDispatched(Registered::class, function ($event) use ($fakeUser) {
        return $event->user->email === $fakeUser->email;
    });
    // Assert the response structure
    $response->assertJsonStructure([
        'message',
        'data' => [
            'token' => [
                'access_token',
            ],
        ],
    ]);

    // Retrieve the newly created user
    $user = User::query()->where('email', $fakeUser2->email)->first();
    expect($user)->not->toBeNull()
        ->and($user->first_name)->toBe($fakeUser2->first_name)
        ->and($user->last_name)->toBe($fakeUser2->last_name)
        ->and($user->email_verified_at)->toBeNull();

    // Retrieve the access token from the response
    $accessToken = $response->json('data.token.access_token');
    expect($accessToken)->not->toBeNull();

    // Decode and verify the access token using Sanctum
    $tokenModel = PersonalAccessToken::findToken($accessToken);
    expect($tokenModel)->not->toBeNull()
        ->and($tokenModel->tokenable_id)->toBe($user->id);
});

test('Register a user with introducer code', function () {
    $fakeUser = User::factory()->makeOne();
    $referralCodeFake = ReferralCode::factory()->create();

    // Send the registration request
    $response = $this->postJson(route('register'), [
        'first_name' => $fakeUser->first_name,
        'last_name' => $fakeUser->last_name,
        'email' => $fakeUser->email,
        'introducer_code' => $referralCodeFake->code,
        'password' => 'password',
    ]);

    $response->assertStatus(201);
    $user = User::query()->where('email', $fakeUser->email)->first();
    expect($user)->not->toBeNull()
        ->and($user->introducer_code)->toBe($referralCodeFake->id);

});

test('Register request with invalid format data', function (array $data, string $errorKey) {
    $this->withoutMiddleware(ThrottleRequests::class);

    $response = $this->postJson(route('register'), $data);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors($errorKey);
})->with([
    'missing first name' => [
        ['last_name' => 'rajabi', 'email' => 'mehdints@gmail.com', 'password' => 'password'],
        'first_name',
    ],
    'missing last name' => [
        ['first_name' => 'mehdi', 'email' => 'mehdints@gmail.com', 'password' => 'password'],
        'last_name',
    ],
    'missing email' => [
        ['first_name' => 'mehdi', 'last_name' => 'rajabi', 'password' => 'password'],
        'email',
    ],
    'missing password' => [
        ['first_name' => 'mehdi', 'last_name' => 'rajabi', 'email' => 'mehdints@gmail.com'],
        'password',
    ],
    // Test case for password too short (assuming min length is 8)
    'latest 8 character password' => [
        ['first_name' => 'mehdi', 'last_name' => 'rajabi', 'email' => 'mehdints@gmail.com', 'password' => '123'],
        'password',
    ],
    // Test case for invalid email address format
    'invalid email address' => [
        ['first_name' => 'mehdi', 'last_name' => 'rajabi', 'email' => 'mehdints@gmail', 'password' => '123'],
        'password',
    ],
]);
