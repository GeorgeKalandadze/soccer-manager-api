<?php

namespace App\Repositories\Contracts;

use App\Models\TransferListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransferListingRepositoryInterface
{
    public function find(int $id): ?TransferListing;

    public function findOrFail(int $id): TransferListing;

    public function findForUpdateOrFail(int $id): TransferListing;

    public function create(array $data): TransferListing;

    public function update(TransferListing $listing, array $data): TransferListing;

    public function paginateActive(array $filters = []): LengthAwarePaginator;
}
