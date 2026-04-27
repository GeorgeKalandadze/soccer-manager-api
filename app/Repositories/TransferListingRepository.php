<?php

namespace App\Repositories;

use App\Models\TransferListing;
use App\Repositories\Contracts\TransferListingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function findForUpdateOrFail(int $id): TransferListing
    {
        return TransferListing::lockForUpdate()->findOrFail($id);
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

    public function paginateActive(array $filters = []): LengthAwarePaginator
    {
        return TransferListing::active()
            ->with(['player.position', 'player.country', 'sellerTeam.country'])
            ->when($this->hasFilter($filters, 'position_id'), fn ($query) => $query->forPosition((int) $filters['position_id']))
            ->when($this->hasFilter($filters, 'country_id'), fn ($query) => $query->forCountry((int) $filters['country_id']))
            ->when($this->hasFilter($filters, 'team_id'), fn ($query) => $query->forTeam((int) $filters['team_id']))
            ->when($this->hasFilter($filters, 'min_price'), fn ($query) => $query->minPrice((int) $filters['min_price']))
            ->when($this->hasFilter($filters, 'max_price'), fn ($query) => $query->maxPrice((int) $filters['max_price']))
            ->when($this->hasFilter($filters, 'search'), fn ($query) => $query->search($filters['search']))
            ->latest()
            ->paginate();
    }

    private function hasFilter(array $filters, string $key): bool
    {
        return isset($filters[$key]) && $filters[$key] !== '';
    }
}

