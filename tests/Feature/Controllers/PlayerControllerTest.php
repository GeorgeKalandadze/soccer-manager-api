<?php

use App\Models\Country;
use App\Models\TransferListing;
use App\Models\User;
use App\Services\TeamService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\PositionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed([CountrySeeder::class, PositionSeeder::class]);

    $country = Country::inRandomOrder()->first();
    $teamService = app(TeamService::class);

    $this->user = User::factory()->create();
    $teamService->createWithPlayers($this->user, $country);
    $this->team = $this->user->team;

    $this->otherUser = User::factory()->create();
    $teamService->createWithPlayers($this->otherUser, $country);
    $this->otherTeam = $this->otherUser->team;
});

it('returns a player belonging to the user\'s team', function () {
    Sanctum::actingAs($this->user);
    $player = $this->team->players->first();

    $this->getJson("/api/v1/players/{$player->id}")
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
    Sanctum::actingAs($this->user);
    $otherPlayer = $this->otherTeam->players->first();

    $this->getJson("/api/v1/players/{$otherPlayer->id}")
        ->assertForbidden();
});

it('updates a player belonging to the user\'s team', function () {
    Sanctum::actingAs($this->user);
    $player = $this->team->players->first();
    $countryId = Country::where('id', '!=', $player->country_id)->value('id');

    $this->patchJson("/api/v1/players/{$player->id}", [
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
    Sanctum::actingAs($this->user);
    $otherPlayer = $this->otherTeam->players->first();

    $this->patchJson("/api/v1/players/{$otherPlayer->id}", [
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
    Sanctum::actingAs($this->user);
    $player = $this->team->players->first();
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

    $this->withHeader('X-App-Locale', 'ka')
        ->getJson("/api/v1/players/{$player->id}")
        ->assertOk()
        ->assertHeader('X-App-Locale', 'ka')
        ->assertJsonPath('data.first_name.ka', 'გიორგი')
        ->assertJsonPath('data.last_name.ka', 'კვარა')
        ->assertJsonPath('data.position.name.ka', 'მეკარე');
});

it('returns 401 when accessing player without authentication', function () {
    $player = $this->team->players->first();

    $this->getJson("/api/v1/players/{$player->id}")
        ->assertUnauthorized();
});

it('returns transfer history for a player belonging to the user\'s team', function () {
    Sanctum::actingAs($this->otherUser);
    $player = $this->otherTeam->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 2_000_000,
    ]);

    $listing = TransferListing::where('player_id', $player->id)->first();

    Sanctum::actingAs($this->user);
    $this->postJson("/api/v1/transfer-listings/{$listing->id}/purchase");

    $this->getJson("/api/v1/players/{$player->id}/transfers")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'price',
                    'previous_market_value',
                    'new_market_value',
                    'seller_team',
                    'buyer_team',
                    'created_at',
                ],
            ],
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.price', 2_000_000);
});

it('returns 403 when accessing transfer history for another team\'s player', function () {
    Sanctum::actingAs($this->user);
    $otherPlayer = $this->otherTeam->players->first();

    $this->getJson("/api/v1/players/{$otherPlayer->id}/transfers")
        ->assertForbidden();
});
