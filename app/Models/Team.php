<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'budget',
    'user_id',
    'country_id',
])]
class Team extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'budget' => 'integer',
        ];
    }

    protected function totalValue(): Attribute
    {
        return Attribute::get(fn () => $this->players()->sum('market_value'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function transferListings(): HasMany
    {
        return $this->hasMany(TransferListing::class, 'seller_team_id');
    }

    public function soldTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'seller_team_id');
    }

    public function boughtTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'buyer_team_id');
    }
}
