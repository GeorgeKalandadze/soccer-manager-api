<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
        ->assertJsonPath('message', 'User logged in successfully.')
        ->assertJsonStructure(['message', 'token']);
    $response->assertHeader('Authorization');
    expect($response->json('token'))->toBe(substr($response->headers->get('Authorization'), 7));

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

it('rejects login for non-existent user', function () {
    $this->postJson('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('requires email and password for login', function () {
    $this->postJson('/api/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});
