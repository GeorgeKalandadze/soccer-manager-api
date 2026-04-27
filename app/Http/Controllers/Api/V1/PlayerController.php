<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Http\Resources\TransferResource;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PlayerController extends Controller
{
    public function __construct(
        private readonly PlayerService $playerService,
    ) {}

    public function show(Player $player): PlayerResource
    {
        Gate::authorize('manage', $player);

        $player->load(['position', 'country']);

        return new PlayerResource($player);
    }

    public function transfers(Player $player): AnonymousResourceCollection
    {
        Gate::authorize('manage', $player);

        $transfers = $player->transfers()
            ->with(['sellerTeam.country', 'buyerTeam.country'])
            ->latest()
            ->paginate();

        return TransferResource::collection($transfers);
    }

    public function update(UpdatePlayerRequest $request, Player $player): PlayerResource
    {
        Gate::authorize('manage', $player);

        $this->playerService->update($player, $request->validated());

        $player->load(['position', 'country']);

        return new PlayerResource($player);
    }
}
