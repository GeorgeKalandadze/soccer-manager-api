<?php

use App\Models\Country;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Support\Facades\Hash;

it('registers a user and returns a success message with a sanctum bearer token header', function () {
    $this->seed([CountrySeeder::class, PositionSeeder::class]);
    $countryId = Country::inRandomOrder()->value('id');

    $response = $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country_id' => $countryId,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'User registered successfully.')
        ->assertJsonStructure(['message', 'token']);
    $response->assertHeader('Authorization');

    expect($response->headers->get('Authorization'))->toStartWith('Bearer ');
    expect($response->json('token'))->toBe(substr($response->headers->get('Authorization'), 7));
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

it('creates a team with 20 players on registration', function () {
    $this->seed([CountrySeeder::class, PositionSeeder::class]);
    $countryId = Country::inRandomOrder()->value('id');

    $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country_id' => $countryId,
    ])->assertOk();

    $user = User::where('email', 'george@example.com')->first();
    $team = $user->team;

    expect($team)->not->toBeNull()
        ->and($team->budget)->toBe(5_000_000)
        ->and($team->players)->toHaveCount(20);

    $positionCounts = $team->players->groupBy(fn ($p) => $p->position->abbreviation)
        ->map->count();

    expect($positionCounts->toArray())->toBe([
        'GK' => 3,
        'DF' => 6,
        'MF' => 6,
        'AT' => 5,
    ]);

    $team->players->each(function ($player) {
        expect($player->market_value)->toBe(1_000_000)
            ->and($player->age)->toBeGreaterThanOrEqual(18)
            ->and($player->age)->toBeLessThanOrEqual(40);
    });
});

it('requires country_id for registration', function () {
    $this->seed([CountrySeeder::class, PositionSeeder::class]);

    $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('country_id');
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
