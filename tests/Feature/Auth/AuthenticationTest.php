<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a user and returns a success message with a sanctum bearer token header', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'User registered successfully.');
    $response->assertHeader('Authorization');

    expect($response->headers->get('Authorization'))->toStartWith('Bearer ');
    $this->assertDatabaseHas('users', [
        'name' => 'George',
        'email' => 'george@example.com',
    ]);

    $this
        ->withHeader('Authorization', $response->headers->get('Authorization'))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', 'george@example.com');
});

it('logs in a user and returns a success message with a sanctum bearer token header', function () {
    User::factory()->create([
        'email' => 'george@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'george@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'User logged in successfully.');
    $response->assertHeader('Authorization');

    $this
        ->withHeader('Authorization', $response->headers->get('Authorization'))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('email', 'george@example.com');
});

it('rejects invalid login credentials', function () {
    User::factory()->create([
        'email' => 'george@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'george@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('logs out the current sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken(config('app.name'))->plainTextToken;

    $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'User logged out successfully.')
        ->assertHeaderMissing('Authorization');

    $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/user')
        ->assertUnauthorized();
});
