<?php

namespace App\Models;

use App\Enums\TransferListingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TransferListingStatus::class,
        ];
    }

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
        return $query->where('status', TransferListingStatus::Active);
    }

    public function scopeForPosition(Builder $query, int $positionId): Builder
    {
        return $query->whereHas('player', fn (Builder $q) => $q->where('position_id', $positionId));
    }

    public function scopeForCountry(Builder $query, int $countryId): Builder
    {
        return $query->whereHas('player', fn (Builder $q) => $q->where('country_id', $countryId));
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('seller_team_id', $teamId);
    }

    public function scopeMinPrice(Builder $query, int $price): Builder
    {
        return $query->where('asking_price', '>=', $price);
    }

    public function scopeMaxPrice(Builder $query, int $price): Builder
    {
        return $query->where('asking_price', '<=', $price);
    }
}
