<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Position;
use App\Models\Team;
use App\Repositories\Contracts\PlayerRepositoryInterface;
use Faker\Factory as Faker;

class PlayerService
{
    public function __construct(
        private readonly PlayerRepositoryInterface $playerRepository,
    ) {}

    public function generateForTeam(Team $team): void
    {
        $faker = Faker::create();

        $squad = [
            'GK' => 3,
            'DF' => 6,
            'MF' => 6,
            'AT' => 5,
        ];

        $positions = Position::whereIn('abbreviation', array_keys($squad))
            ->pluck('id', 'abbreviation');

        $countryIds = Country::pluck('id')->all();

        $players = [];

        foreach ($squad as $abbreviation => $count) {
            for ($i = 0; $i < $count; $i++) {
                $firstName = $faker->firstName();
                $lastName = $faker->lastName();

                $players[] = [
                    'first_name' => json_encode(['en' => $firstName, 'ka' => $firstName]),
                    'last_name' => json_encode(['en' => $lastName, 'ka' => $lastName]),
                    'age' => $faker->numberBetween(18, 40),
                    'market_value' => 1_000_000,
                    'team_id' => $team->id,
                    'position_id' => $positions[$abbreviation],
                    'country_id' => $countryIds[array_rand($countryIds)],
                ];
            }
        }

        $this->playerRepository->bulkInsert($players);
    }
}
