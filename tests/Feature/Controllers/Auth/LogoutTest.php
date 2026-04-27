<?php

use App\Models\User;

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

it('returns 401 when logging out without a token', function () {
    $this->postJson('/api/logout')
        ->assertUnauthorized();
});
