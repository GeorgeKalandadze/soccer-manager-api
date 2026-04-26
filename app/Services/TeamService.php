<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function __construct(
        private readonly TeamRepositoryInterface $teamRepository,
        private readonly PlayerService $playerService,
    ) {}

    public function createWithPlayers(User $user, Country $country): Team
    {
        return DB::transaction(function () use ($user, $country) {
            $team = $this->teamRepository->create([
                'name' => ['en' => $user->name.' FC', 'ka' => $user->name.' FC'],
                'budget' => 5_000_000,
                'user_id' => $user->id,
                'country_id' => $country->id,
            ]);

            $this->playerService->generateForTeam($team);

            return $team;
        });
    }
}
