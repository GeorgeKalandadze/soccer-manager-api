<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'player_id',
    'seller_team_id',
    'asking_price',
    'status',
])]
class TransferListing extends Model
{
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function sellerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'seller_team_id');
    }

    public function transfer(): HasOne
    {
        return $this->hasOne(Transfer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
