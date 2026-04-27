<?php

namespace App\Repositories\Contracts;

use App\Models\Team;

interface TeamRepositoryInterface
{
    public function find(int $id): ?Team;

    public function findOrFail(int $id): Team;

    public function findForUpdateOrFail(int $id): Team;

    public function create(array $data): Team;

    public function update(Team $team, array $data): Team;

    public function delete(Team $team): bool;
}
