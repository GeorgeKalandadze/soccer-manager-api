<?php

use App\Models\Country;
use App\Models\User;
use App\Services\TeamService;
use App\Services\TransferService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([CountrySeeder::class, PositionSeeder::class]);

    $country = Country::inRandomOrder()->first();
    $teamService = app(TeamService::class);

    $this->seller = User::factory()->create();
    $teamService->createWithPlayers($this->seller, $country);
    $this->sellerTeam = $this->seller->team;

    $this->buyer = User::factory()->create();
    $teamService->createWithPlayers($this->buyer, $country);
    $this->buyerTeam = $this->buyer->team;

    $this->transferService = app(TransferService::class);
});

it('lists a player on the transfer market', function () {
    $player = $this->sellerTeam->players->first();

    $listing = $this->transferService->listPlayer($player, $this->sellerTeam, 2_000_000);

    expect($listing->player_id)->toBe($player->id)
        ->and($listing->seller_team_id)->toBe($this->sellerTeam->id)
        ->and($listing->asking_price)->toBe(2_000_000)
        ->and($listing->status->value)->toBe('active');
});

it('throws when listing a player that belongs to another team', function () {
    $player = $this->sellerTeam->players->first();

    $this->transferService->listPlayerById($player->id, $this->buyerTeam, 2_000_000);
})->throws(AuthorizationException::class);

it('throws when listing an already listed player', function () {
    $player = $this->sellerTeam->players->first();

    $this->transferService->listPlayer($player, $this->sellerTeam, 2_000_000);
    $this->transferService->listPlayer($player, $this->sellerTeam, 3_000_000);
})->throws(ValidationException::class);

it('purchases a player and transfers ownership', function () {
    $player = $this->sellerTeam->players->first();

    $listing = $this->transferService->listPlayer($player, $this->sellerTeam, 2_000_000);
    $transfer = $this->transferService->purchasePlayer($listing, $this->buyerTeam);

    $player->refresh();

    expect($transfer->price)->toBe(2_000_000)
        ->and($transfer->previous_market_value)->toBe(1_000_000)
        ->and($player->team_id)->toBe($this->buyerTeam->id);
});

it('increases market value between 10 and 100 percent after purchase', function () {
    $player = $this->sellerTeam->players->first();
    $originalValue = $player->market_value;

    $listing = $this->transferService->listPlayer($player, $this->sellerTeam, 1_000_000);
    $transfer = $this->transferService->purchasePlayer($listing, $this->buyerTeam);

    $minExpected = (int) round($originalValue * 1.10);
    $maxExpected = (int) round($originalValue * 2.0);

    expect($transfer->new_market_value)->toBeGreaterThanOrEqual($minExpected)
        ->and($transfer->new_market_value)->toBeLessThanOrEqual($maxExpected);
});

it('updates buyer and seller budgets after purchase', function () {
    $player = $this->sellerTeam->players->first();

    $listing = $this->transferService->listPlayer($player, $this->sellerTeam, 2_000_000);
    $this->transferService->purchasePlayer($listing, $this->buyerTeam);

    $this->buyerTeam->refresh();
    $this->sellerTeam->refresh();

    expect($this->buyerTeam->budget)->toBe(3_000_000)
        ->and($this->sellerTeam->budget)->toBe(7_000_000);
});

it('throws when purchasing own player', function () {
    $player = $this->sellerTeam->players->first();

    $listing = $this->transferService->listPlayer($player, $this->sellerTeam, 1_000_000);
    $this->transferService->purchasePlayer($listing, $this->sellerTeam);
})->throws(ValidationException::class);

it('throws when buyer has insufficient budget', function () {
    $player = $this->sellerTeam->players->first();

    $listing = $this->transferService->listPlayer($player, $this->sellerTeam, 10_000_000);
    $this->transferService->purchasePlayer($listing, $this->buyerTeam);
})->throws(ValidationException::class);

it('can cancel a listing', function () {
    $player = $this->sellerTeam->players->first();

    $listing = $this->transferService->listPlayer($player, $this->sellerTeam, 2_000_000);
    $cancelled = $this->transferService->cancelListing($listing);

    expect($cancelled->status->value)->toBe('cancelled');
});

it('can relist a player after cancellation', function () {
    $player = $this->sellerTeam->players->first();

    $listing = $this->transferService->listPlayer($player, $this->sellerTeam, 2_000_000);
    $this->transferService->cancelListing($listing);

    $newListing = $this->transferService->listPlayer($player, $this->sellerTeam, 3_000_000);

    expect($newListing->asking_price)->toBe(3_000_000)
        ->and($newListing->status->value)->toBe('active');
});
