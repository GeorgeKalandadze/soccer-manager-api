<?php

namespace App\Repositories;

use App\Models\Player;
use App\Repositories\Contracts\PlayerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PlayerRepository implements PlayerRepositoryInterface
{
    public function find(int $id): ?Player
    {
        return Player::find($id);
    }

    public function findOrFail(int $id): Player
    {
        return Player::findOrFail($id);
    }

    public function create(array $data): Player
    {
        return Player::create($data);
    }

    public function update(Player $player, array $data): Player
    {
        $player->update($data);

        return $player;
    }

    public function delete(Player $player): bool
    {
        return $player->delete();
    }

    public function bulkInsert(array $rows): void
    {
        DB::table('players')->insert($rows);
    }
}
