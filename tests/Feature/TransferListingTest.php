<?php

use App\Models\Country;
use App\Models\Position;
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

it('can list a player on the transfer market', function () {
    Sanctum::actingAs($this->user);
    $player = $this->team->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 2_000_000,
    ])
        ->assertCreated()
        ->assertJsonPath('data.asking_price', 2_000_000)
        ->assertJsonPath('data.player.id', $player->id)
        ->assertJsonStructure([
            'data' => [
                'id',
                'asking_price',
                'status',
                'player' => ['id', 'first_name', 'last_name'],
                'seller_team' => ['id', 'name'],
                'created_at',
            ],
        ]);

    $this->assertDatabaseHas('transfer_listings', [
        'player_id' => $player->id,
        'seller_team_id' => $this->team->id,
        'asking_price' => 2_000_000,
        'status' => 'active',
    ]);
});

it('cannot list a player that does not belong to the user', function () {
    Sanctum::actingAs($this->user);
    $otherPlayer = $this->otherTeam->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $otherPlayer->id,
        'asking_price' => 2_000_000,
    ])
        ->assertForbidden();
});

it('cannot list an already listed player', function () {
    Sanctum::actingAs($this->user);
    $player = $this->team->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 2_000_000,
    ])
        ->assertCreated();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 3_000_000,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('player_id');
});

it('can view all active transfer listings', function () {
    Sanctum::actingAs($this->user);
    $player = $this->team->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 2_000_000,
    ])
        ->assertCreated();

    $this->getJson('/api/v1/transfer-listings')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asking_price', 2_000_000)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'asking_price',
                    'player' => ['id', 'first_name', 'last_name', 'position', 'country'],
                    'seller_team',
                ],
            ],
        ]);
});

it('can filter market by position', function () {
    Sanctum::actingAs($this->user);

    $gkPosition = Position::where('abbreviation', 'GK')->first();
    $dfPosition = Position::where('abbreviation', 'DF')->first();

    $gkPlayer = $this->team->players()->where('position_id', $gkPosition->id)->first();
    $dfPlayer = $this->team->players()->where('position_id', $dfPosition->id)->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $gkPlayer->id,
        'asking_price' => 1_000_000,
    ]);

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $dfPlayer->id,
        'asking_price' => 1_500_000,
    ]);

    $this->getJson("/api/v1/transfer-listings?position_id={$gkPosition->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.player.id', $gkPlayer->id);
});

it('can filter market by min and max price', function () {
    Sanctum::actingAs($this->user);
    $players = $this->team->players->take(2);

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $players[0]->id,
        'asking_price' => 1_000_000,
    ]);

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $players[1]->id,
        'asking_price' => 3_000_000,
    ]);

    $this->getJson('/api/v1/transfer-listings?min_price=2000000')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asking_price', 3_000_000);

    $this->getJson('/api/v1/transfer-listings?max_price=2000000')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asking_price', 1_000_000);
});

it('can purchase a listed player', function () {
    Sanctum::actingAs($this->otherUser);
    $player = $this->otherTeam->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 2_000_000,
    ])
        ->assertCreated();

    $listing = TransferListing::where('player_id', $player->id)->first();

    Sanctum::actingAs($this->user);

    $this->postJson("/api/v1/transfer-listings/{$listing->id}/purchase")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'price',
                'previous_market_value',
                'new_market_value',
                'player',
                'seller_team',
                'buyer_team',
                'created_at',
            ],
        ])
        ->assertJsonPath('data.price', 2_000_000)
        ->assertJsonPath('data.previous_market_value', 1_000_000);
});

it('updates budgets after purchase', function () {
    Sanctum::actingAs($this->otherUser);
    $player = $this->otherTeam->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 2_000_000,
    ]);

    $listing = TransferListing::where('player_id', $player->id)->first();

    Sanctum::actingAs($this->user);

    $this->postJson("/api/v1/transfer-listings/{$listing->id}/purchase")
        ->assertOk();

    $this->team->refresh();
    $this->otherTeam->refresh();

    expect($this->team->budget)->toBe(3_000_000)
        ->and($this->otherTeam->budget)->toBe(7_000_000);
});

it('transfers player ownership to buyer team', function () {
    Sanctum::actingAs($this->otherUser);
    $player = $this->otherTeam->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 1_000_000,
    ]);

    $listing = TransferListing::where('player_id', $player->id)->first();

    Sanctum::actingAs($this->user);

    $this->postJson("/api/v1/transfer-listings/{$listing->id}/purchase")
        ->assertOk();

    $player->refresh();
    expect($player->team_id)->toBe($this->team->id);
});

it('increases player market value by 10 to 100 percent after purchase', function () {
    Sanctum::actingAs($this->otherUser);
    $player = $this->otherTeam->players->first();
    $originalValue = $player->market_value;

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 1_000_000,
    ]);

    $listing = TransferListing::where('player_id', $player->id)->first();

    Sanctum::actingAs($this->user);

    $response = $this->postJson("/api/v1/transfer-listings/{$listing->id}/purchase")
        ->assertOk();

    $newValue = $response->json('data.new_market_value');
    $minExpected = (int) round($originalValue * 1.10);
    $maxExpected = (int) round($originalValue * 2.0);

    expect($newValue)->toBeGreaterThanOrEqual($minExpected)
        ->and($newValue)->toBeLessThanOrEqual($maxExpected);
});

it('cannot purchase own player', function () {
    Sanctum::actingAs($this->user);
    $player = $this->team->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 1_000_000,
    ]);

    $listing = TransferListing::where('player_id', $player->id)->first();

    $this->postJson("/api/v1/transfer-listings/{$listing->id}/purchase")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('listing');
});

it('cannot purchase with insufficient budget', function () {
    Sanctum::actingAs($this->otherUser);
    $player = $this->otherTeam->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 10_000_000,
    ]);

    $listing = TransferListing::where('player_id', $player->id)->first();

    Sanctum::actingAs($this->user);

    $this->postJson("/api/v1/transfer-listings/{$listing->id}/purchase")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('listing');
});

it('can cancel own listing', function () {
    Sanctum::actingAs($this->user);
    $player = $this->team->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 2_000_000,
    ]);

    $listing = TransferListing::where('player_id', $player->id)->first();

    $this->deleteJson("/api/v1/transfer-listings/{$listing->id}")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('cannot cancel another team listing', function () {
    Sanctum::actingAs($this->otherUser);
    $player = $this->otherTeam->players->first();

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $player->id,
        'asking_price' => 2_000_000,
    ]);

    $listing = TransferListing::where('player_id', $player->id)->first();

    Sanctum::actingAs($this->user);

    $this->deleteJson("/api/v1/transfer-listings/{$listing->id}")
        ->assertForbidden();
});

it('does not show cancelled or completed listings in market', function () {
    Sanctum::actingAs($this->user);
    $players = $this->team->players->take(2);

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $players[0]->id,
        'asking_price' => 1_000_000,
    ]);

    $this->postJson('/api/v1/transfer-listings', [
        'player_id' => $players[1]->id,
        'asking_price' => 2_000_000,
    ]);

    $listing1 = TransferListing::where('player_id', $players[0]->id)->first();
    $this->deleteJson("/api/v1/transfer-listings/{$listing1->id}");

    $listing2 = TransferListing::where('player_id', $players[1]->id)->first();

    Sanctum::actingAs($this->otherUser);
    $this->postJson("/api/v1/transfer-listings/{$listing2->id}/purchase");

    Sanctum::actingAs($this->user);
    $this->getJson('/api/v1/transfer-listings')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
