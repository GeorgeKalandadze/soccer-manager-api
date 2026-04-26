<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transfer_listing_id',
    'player_id',
    'seller_team_id',
    'buyer_team_id',
    'price',
    'previous_market_value',
    'new_market_value',
])]
class Transfer extends Model
{
    public function listing(): BelongsTo
    {
        return $this->belongsTo(TransferListing::class, 'transfer_listing_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function sellerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'seller_team_id');
    }

    public function buyerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'buyer_team_id');
    }
}
