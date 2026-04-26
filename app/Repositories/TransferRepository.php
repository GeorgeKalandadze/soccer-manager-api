<?php

namespace App\Repositories;

use App\Models\Transfer;
use App\Repositories\Contracts\TransferRepositoryInterface;

class TransferRepository implements TransferRepositoryInterface
{
    public function find(int $id): ?Transfer
    {
        return Transfer::find($id);
    }

    public function findOrFail(int $id): Transfer
    {
        return Transfer::findOrFail($id);
    }

    public function create(array $data): Transfer
    {
        return Transfer::create($data);
    }
}
