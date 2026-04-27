<?php

use App\Models\Country;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\PositionSeeder;

beforeEach(function () {
    $this->seed([CountrySeeder::class, PositionSeeder::class]);

    $countryId = Country::inRandomOrder()->value('id');

    $response = $this->postJson('/api/register', [
        'name' => 'George',
        'email' => 'george@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country_id' => $countryId,
    ]);

    $this->token = $response->headers->get('Authorization');
    $this->user = User::where('email', 'george@example.com')->first();
});

it('returns the authenticated user\'s team', function () {
    $this->withHeader('Authorization', $this->token)
        ->getJson('/api/v1/team')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name' => ['en', 'ka'],
                'budget',
                'total_value',
                'country' => ['id', 'name', 'code'],
                'players' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'age',
                        'market_value',
                        'position' => ['id', 'name', 'abbreviation'],
                        'country' => ['id', 'name', 'code'],
                    ],
                ],
            ],
        ]);
});

it('returns team with 20 players', function () {
    $response = $this->withHeader('Authorization', $this->token)
        ->getJson('/api/v1/team')
        ->assertOk();

    expect($response->json('data.players'))->toHaveCount(20);
});

it('returns correct total value as sum of player market values', function () {
    $response = $this->withHeader('Authorization', $this->token)
        ->getJson('/api/v1/team')
        ->assertOk();

    expect($response->json('data.total_value'))->toBe(20_000_000)
        ->and($response->json('data.budget'))->toBe(5_000_000);
});

it('updates the authenticated user\'s team name and country', function () {
    $countryId = Country::where('id', '!=', $this->user->team->country_id)->value('id');

    $this->withHeader('Authorization', $this->token)
        ->patchJson('/api/v1/team', [
            'name' => [
                'en' => 'Tbilisi United',
                'ka' => 'Tbilisi United',
            ],
            'country_id' => $countryId,
        ])
        ->assertOk()
        ->assertJsonPath('data.name.en', 'Tbilisi United')
        ->assertJsonPath('data.name.ka', 'Tbilisi United')
        ->assertJsonPath('data.country.id', $countryId);

    $this->assertDatabaseHas('teams', [
        'id' => $this->user->team->id,
        'country_id' => $countryId,
    ]);
});

it('sets the application locale from the x app locale header for team requests', function () {
    $this->user->team->update([
        'name' => [
            'en' => 'Tbilisi United',
            'ka' => 'თბილისი იუნაიტედი',
        ],
    ]);

    $this->withHeader('Authorization', $this->token)
        ->withHeader('X-App-Locale', 'ka')
        ->getJson('/api/v1/team')
        ->assertOk()
        ->assertHeader('X-App-Locale', 'ka')
        ->assertJsonPath('data.name.ka', 'თბილისი იუნაიტედი')
        ->assertJsonPath('data.players.0.position.name.ka', 'მეკარე');
});

it('returns 401 when accessing team without authentication', function () {
    $this->refreshApplication();

    $this->getJson('/api/v1/team')
        ->assertUnauthorized();
});
