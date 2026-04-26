<?php

namespace App\Services;

use App\Enums\TransferListingStatus;
use App\Models\Player;
use App\Models\Team;
use App\Models\Transfer;
use App\Models\TransferListing;
use App\Repositories\Contracts\PlayerRepositoryInterface;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\TransferListingRepositoryInterface;
use App\Repositories\Contracts\TransferRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferService
{
    public function __construct(
        private readonly TransferListingRepositoryInterface $transferListingRepository,
        private readonly TransferRepositoryInterface $transferRepository,
        private readonly PlayerRepositoryInterface $playerRepository,
        private readonly TeamRepositoryInterface $teamRepository,
    ) {}

    public function listPlayer(Player $player, Team $sellerTeam, int $askingPrice): TransferListing
    {
        if ($player->activeTransferListing()->exists()) {
            throw ValidationException::withMessages([
                'player_id' => [__('transfers.already_listed')],
            ]);
        }

        return $this->transferListingRepository->create([
            'player_id' => $player->id,
            'seller_team_id' => $sellerTeam->id,
            'asking_price' => $askingPrice,
            'status' => TransferListingStatus::Active,
        ]);
    }

    public function cancelListing(TransferListing $listing): TransferListing
    {
        return $this->transferListingRepository->update($listing, [
            'status' => TransferListingStatus::Cancelled,
        ]);
    }

    public function purchasePlayer(TransferListing $listing, Team $buyerTeam): Transfer
    {
        return DB::transaction(function () use ($listing, $buyerTeam) {
            $listing = TransferListing::lockForUpdate()->findOrFail($listing->id);
            $buyerTeam = Team::lockForUpdate()->findOrFail($buyerTeam->id);
            $sellerTeam = Team::lockForUpdate()->findOrFail($listing->seller_team_id);

            $this->validatePurchase($listing, $buyerTeam);

            $this->transferListingRepository->update($listing, [
                'status' => TransferListingStatus::Completed,
            ]);

            $player = $listing->player;
            $previousMarketValue = $player->market_value;
            $newMarketValue = $this->calculateNewMarketValue($previousMarketValue);

            $this->transferPlayerOwnership($player, $buyerTeam, $newMarketValue);
            $this->updateBudgets($buyerTeam, $sellerTeam, $listing->asking_price);

            return $this->transferRepository->create([
                'transfer_listing_id' => $listing->id,
                'player_id' => $player->id,
                'seller_team_id' => $sellerTeam->id,
                'buyer_team_id' => $buyerTeam->id,
                'price' => $listing->asking_price,
                'previous_market_value' => $previousMarketValue,
                'new_market_value' => $newMarketValue,
            ]);
        });
    }

    private function validatePurchase(TransferListing $listing, Team $buyerTeam): void
    {
        if ($listing->status !== TransferListingStatus::Active) {
            throw ValidationException::withMessages([
                'listing' => [__('transfers.not_active')],
            ]);
        }

        if ($buyerTeam->id === $listing->seller_team_id) {
            throw ValidationException::withMessages([
                'listing' => [__('transfers.own_player')],
            ]);
        }

        if ($buyerTeam->budget < $listing->asking_price) {
            throw ValidationException::withMessages([
                'listing' => [__('transfers.insufficient_budget')],
            ]);
        }
    }

    private function calculateNewMarketValue(int $currentValue): int
    {
        $increasePercent = random_int(10, 100);

        return (int) round($currentValue * (1 + $increasePercent / 100));
    }

    private function transferPlayerOwnership(Player $player, Team $buyerTeam, int $newMarketValue): void
    {
        $this->playerRepository->update($player, [
            'team_id' => $buyerTeam->id,
            'market_value' => $newMarketValue,
        ]);
    }

    private function updateBudgets(Team $buyerTeam, Team $sellerTeam, int $price): void
    {
        $this->teamRepository->update($buyerTeam, [
            'budget' => $buyerTeam->budget - $price,
        ]);

        $this->teamRepository->update($sellerTeam, [
            'budget' => $sellerTeam->budget + $price,
        ]);
    }
}
