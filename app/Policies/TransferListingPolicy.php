<?php

namespace App\Policies;

use App\Models\TransferListing;
use App\Models\User;

class TransferListingPolicy
{
    public function cancel(User $user, TransferListing $listing): bool
    {
        return $listing->seller_team_id === $user->team?->id;
    }
}
