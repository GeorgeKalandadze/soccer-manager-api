<?php

namespace App\Repositories\Contracts;

use App\Models\Player;

interface PlayerRepositoryInterface
{
    public function find(int $id): ?Player;

    public function findOrFail(int $id): Player;

    public function create(array $data): Player;

    public function update(Player $player, array $data): Player;

    public function delete(Player $player): bool;

    public function bulkInsert(array $rows): void;
}
