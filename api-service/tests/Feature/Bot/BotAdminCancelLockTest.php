<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['smart-bot.internal_admin_token' => 'test-token']);
});

it('rejects an admin cancel-all while another cancel for the same user is running', function () {
    $user = User::factory()->create();
    $running = Cache::lock("bot:admin-cancel:user:{$user->id}", 60);
    expect($running->get())->toBeTrue();

    $this->postJson("/api/internal/bot-admin/users/{$user->id}/cancel-all", [], ['X-Internal-Token' => 'test-token'])
        ->assertStatus(409)
        ->assertJson(['ok' => false]);

    $running->release();
});

it('releases the user cancel lock once a cancel-all finishes', function () {
    $user = User::factory()->create();

    $this->postJson("/api/internal/bot-admin/users/{$user->id}/cancel-all", [], ['X-Internal-Token' => 'test-token'])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $lock = Cache::lock("bot:admin-cancel:user:{$user->id}", 60);
    expect($lock->get())->toBeTrue();
    $lock->release();
});
