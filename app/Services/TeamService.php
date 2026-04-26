<?php

namespace App\Services;

use App\Repositories\Contracts\PlayerRepositoryInterface;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Models\Country;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function __construct(
        private readonly TeamRepositoryInterface $teamRepository,
        private readonly PlayerRepositoryInterface $playerRepository,
    ) {}

    public function createWithPlayers(User $user, Country $country): Team
    {
        return DB::transaction(function () use ($user, $country) {
            $team = $this->teamRepository->create([
                'name' => ['en' => $user->name . ' FC', 'ka' => $user->name . ' FC'],
                'budget' => 5_000_000,
                'user_id' => $user->id,
                'country_id' => $country->id,
            ]);

            $this->generatePlayers($team);

            return $team;
        });
    }

    private function generatePlayers(Team $team): void
    {
        $faker = Faker::create();
        $now = now();

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
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->playerRepository->bulkInsert($players);
    }
}
