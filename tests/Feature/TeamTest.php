<?php

use App\Models\Country;
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
