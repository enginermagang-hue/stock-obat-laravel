<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guest is redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated user can open dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

test('login endpoint issues a sanctum token', function () {
    User::factory()->create([
        'email' => 'api@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'api@example.com',
        'password' => 'secret123',
    ])->assertOk()
        ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email'], 'token', 'token_type']]);
});

test('login endpoint rejects invalid credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong',
    ])->assertUnauthorized();
});

test('login endpoint validates input', function () {
    $this->postJson('/api/v1/auth/login', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

test('me endpoint requires authentication', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('me endpoint returns current user', function () {
    $user = User::factory()->create(['email' => 'me@example.com']);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'me@example.com');
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
    expect($user->tokens()->count())->toBe(0);

    Illuminate\Support\Facades\Auth::forgetGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
});
