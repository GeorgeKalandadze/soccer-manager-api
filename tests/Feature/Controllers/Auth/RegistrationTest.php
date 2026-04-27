<?php

use App\Models\Country;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\PositionSeeder;

beforeEach(function () {
    $this->seed([CountrySeeder::class, PositionSeeder::class]);
    $this->countryId = Country::inRandomOrder()->value('id');
});

it('registers a user and returns a success message with a sanctum bearer token header', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country_id' => $this->countryId,
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
    $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country_id' => $this->countryId,
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
    $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('country_id');
});

it('requires a valid email for registration', function () {
    $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country_id' => $this->countryId,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('requires a unique email for registration', function () {
    User::factory()->create(['email' => 'george@example.com']);

    $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country_id' => $this->countryId,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('requires password confirmation for registration', function () {
    $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'country_id' => $this->countryId,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

it('returns Georgian validation messages when requested', function () {
    $this->withHeader('X-App-Locale', 'ka')
        ->postJson('/api/register', [
            'name' => 'George',
            'email' => 'george@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertUnprocessable()
        ->assertHeader('X-App-Locale', 'ka')
        ->assertJsonPath('errors.country_id.0', 'ქვეყანა სავალდებულოა.');
});
