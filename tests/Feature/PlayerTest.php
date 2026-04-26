<?php

use App\Models\Country;
use App\Models\Player;
use App\Models\Team;
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

it('returns a player belonging to the user\'s team', function () {
    $player = $this->user->team->players->first();

    $this->withHeader('Authorization', $this->token)
        ->getJson("/api/v1/players/{$player->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'first_name',
                'last_name',
                'age',
                'market_value',
                'position' => ['id', 'name', 'abbreviation'],
                'country' => ['id', 'name', 'code'],
            ],
        ])
        ->assertJsonPath('data.id', $player->id);
});

it('returns 403 for a player not belonging to the user\'s team', function () {
    $otherUser = User::factory()->create();
    $otherTeam = Team::factory()->create(['user_id' => $otherUser->id]);
    $otherPlayer = Player::factory()->create(['team_id' => $otherTeam->id]);

    $this->withHeader('Authorization', $this->token)
        ->getJson("/api/v1/players/{$otherPlayer->id}")
        ->assertForbidden();
});
