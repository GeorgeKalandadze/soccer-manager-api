<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Position;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'first_name' => ['en' => $firstName, 'ka' => $firstName],
            'last_name' => ['en' => $lastName, 'ka' => $lastName],
            'age' => fake()->numberBetween(18, 40),
            'market_value' => 1_000_000,
            'team_id' => Team::factory(),
            'position_id' => Position::inRandomOrder()->value('id') ?? 1,
            'country_id' => Country::inRandomOrder()->value('id') ?? 1,
        ];
    }
}
