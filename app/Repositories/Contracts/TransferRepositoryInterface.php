<?php

namespace App\Repositories\Contracts;

use App\Models\Transfer;

interface TransferRepositoryInterface
{
    public function find(int $id): ?Transfer;

    public function findOrFail(int $id): Transfer;

    public function create(array $data): Transfer;
}
