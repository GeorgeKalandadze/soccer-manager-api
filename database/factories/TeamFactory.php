<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->city() . ' FC';

        return [
            'name' => ['en' => $name, 'ka' => $name],
            'budget' => 5_000_000,
            'user_id' => User::factory(),
            'country_id' => Country::inRandomOrder()->value('id') ?? 1,
        ];
    }
}
