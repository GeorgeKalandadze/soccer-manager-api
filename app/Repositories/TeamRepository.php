<?php

namespace App\Repositories;

use App\Models\Team;
use App\Repositories\Contracts\TeamRepositoryInterface;

class TeamRepository implements TeamRepositoryInterface
{
    public function find(int $id): ?Team
    {
        return Team::find($id);
    }

    public function findOrFail(int $id): Team
    {
        return Team::findOrFail($id);
    }

    public function findForUpdateOrFail(int $id): Team
    {
        return Team::lockForUpdate()->findOrFail($id);
    }

    public function create(array $data): Team
    {
        return Team::create($data);
    }

    public function update(Team $team, array $data): Team
    {
        $team->update($data);

        return $team;
    }

    public function delete(Team $team): bool
    {
        return $team->delete();
    }
}
