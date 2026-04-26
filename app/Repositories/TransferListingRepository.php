<?php

namespace App\Repositories;

use App\Models\TransferListing;
use App\Repositories\Contracts\TransferListingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class TransferListingRepository implements TransferListingRepositoryInterface
{
    public function find(int $id): ?TransferListing
    {
        return TransferListing::find($id);
    }

    public function findOrFail(int $id): TransferListing
    {
        return TransferListing::findOrFail($id);
    }

    public function create(array $data): TransferListing
    {
        return TransferListing::create($data);
    }

    public function update(TransferListing $listing, array $data): TransferListing
    {
        $listing->update($data);

        return $listing;
    }

    public function getActiveListings(): Builder
    {
        return TransferListing::active();
    }
}
