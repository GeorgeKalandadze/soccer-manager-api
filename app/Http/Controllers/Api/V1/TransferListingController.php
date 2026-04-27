<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTransferListingRequest;
use App\Http\Resources\TransferListingResource;
use App\Http\Resources\TransferResource;
use App\Models\TransferListing;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TransferListingController extends Controller
{
    public function __construct(
        private readonly TransferService $transferService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return TransferListingResource::collection(
            $this->transferService->getMarketListings($request->only([
                'position_id',
                'country_id',
                'team_id',
                'min_price',
                'max_price',
                'search',
            ]))
        );
    }

    public function store(StoreTransferListingRequest $request): JsonResponse
    {
        $team = $request->user()->team;

        $listing = $this->transferService->listPlayerById(
            $request->validated('player_id'),
            $team,
            $request->validated('asking_price'),
        );

        $listing->load(['player.position', 'player.country', 'sellerTeam.country']);

        return (new TransferListingResource($listing))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(TransferListing $listing): TransferListingResource
    {
        Gate::authorize('cancel', $listing);

        $this->transferService->cancelListing($listing);

        $listing->load(['player.position', 'player.country', 'sellerTeam.country']);

        return new TransferListingResource($listing);
    }

    public function purchase(Request $request, TransferListing $listing): JsonResponse
    {
        $buyerTeam = $request->user()->team;

        $transfer = $this->transferService->purchasePlayer($listing, $buyerTeam);

        $transfer->load(['player.position', 'player.country', 'sellerTeam.country', 'buyerTeam.country']);

        return (new TransferResource($transfer))
            ->response()
            ->setStatusCode(200);
    }
}
