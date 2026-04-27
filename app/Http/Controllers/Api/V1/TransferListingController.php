<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTransferListingRequest;
use App\Http\Resources\TransferListingResource;
use App\Http\Resources\TransferResource;
use App\Models\Player;
use App\Models\TransferListing;
use App\Repositories\Contracts\TransferListingRepositoryInterface;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TransferListingController extends Controller
{
    public function __construct(
        private readonly TransferService $transferService,
        private readonly TransferListingRepositoryInterface $transferListingRepository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->transferListingRepository->getActiveListings()
            ->with(['player.position', 'player.country', 'sellerTeam.country'])
            ->when($request->filled('position_id'), fn ($q) => $q->forPosition($request->integer('position_id')))
            ->when($request->filled('country_id'), fn ($q) => $q->forCountry($request->integer('country_id')))
            ->when($request->filled('team_id'), fn ($q) => $q->forTeam($request->integer('team_id')))
            ->when($request->filled('min_price'), fn ($q) => $q->minPrice($request->integer('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->maxPrice($request->integer('max_price')));

        return TransferListingResource::collection($query->latest()->paginate());
    }

    public function store(StoreTransferListingRequest $request): JsonResponse
    {
        $team = $request->user()->team;
        $player = Player::findOrFail($request->validated('player_id'));

        if ($player->team_id !== $team->id) {
            throw new AccessDeniedHttpException(__('transfers.not_your_player'));
        }

        $listing = $this->transferService->listPlayer(
            $player,
            $team,
            $request->validated('asking_price'),
        );

        $listing->load(['player.position', 'player.country', 'sellerTeam.country']);

        return (new TransferListingResource($listing))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, TransferListing $listing): TransferListingResource
    {
        if ($listing->seller_team_id !== $request->user()->team?->id) {
            throw new AccessDeniedHttpException;
        }

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
