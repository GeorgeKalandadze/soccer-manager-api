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

it('updates a player belonging to the user\'s team', function () {
    $player = $this->user->team->players->first();
    $countryId = Country::where('id', '!=', $player->country_id)->value('id');

    $this->withHeader('Authorization', $this->token)
        ->patchJson("/api/v1/players/{$player->id}", [
            'first_name' => [
                'en' => 'Giorgi',
                'ka' => 'Giorgi',
            ],
            'last_name' => [
                'en' => 'Kvara',
                'ka' => 'Kvara',
            ],
            'country_id' => $countryId,
        ])
        ->assertOk()
        ->assertJsonPath('data.first_name.en', 'Giorgi')
        ->assertJsonPath('data.first_name.ka', 'Giorgi')
        ->assertJsonPath('data.last_name.en', 'Kvara')
        ->assertJsonPath('data.last_name.ka', 'Kvara')
        ->assertJsonPath('data.country.id', $countryId);

    $this->assertDatabaseHas('players', [
        'id' => $player->id,
        'country_id' => $countryId,
    ]);
});

it('returns 403 when updating a player not belonging to the user\'s team', function () {
    $otherUser = User::factory()->create();
    $otherTeam = Team::factory()->create(['user_id' => $otherUser->id]);
    $otherPlayer = Player::factory()->create(['team_id' => $otherTeam->id]);

    $this->withHeader('Authorization', $this->token)
        ->patchJson("/api/v1/players/{$otherPlayer->id}", [
            'first_name' => [
                'en' => 'Giorgi',
                'ka' => 'Giorgi',
            ],
            'last_name' => [
                'en' => 'Kvara',
                'ka' => 'Kvara',
            ],
            'country_id' => Country::query()->value('id'),
        ])
        ->assertForbidden();
});

it('sets the application locale from the x app locale header for player requests', function () {
    $player = $this->user->team->players->first();
    $player->update([
        'first_name' => [
            'en' => 'Giorgi',
            'ka' => 'გიორგი',
        ],
        'last_name' => [
            'en' => 'Kvara',
            'ka' => 'კვარა',
        ],
    ]);

    $this->withHeader('Authorization', $this->token)
        ->withHeader('X-App-Locale', 'ka')
        ->getJson("/api/v1/players/{$player->id}")
        ->assertOk()
        ->assertHeader('X-App-Locale', 'ka')
        ->assertJsonPath('data.first_name.ka', 'გიორგი')
        ->assertJsonPath('data.last_name.ka', 'კვარა')
        ->assertJsonPath('data.position.name.ka', 'მეკარე');
});
