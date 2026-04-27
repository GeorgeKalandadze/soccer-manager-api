<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferListingFactory extends Factory
{
    public function definition(): array
    {
        $team = Team::factory();

        return [
            'player_id' => Player::factory(),
            'seller_team_id' => $team,
            'asking_price' => fake()->numberBetween(500_000, 5_000_000),
            'status' => 'active',
        ];
    }
}
