<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Http\Resources\TransferResource;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PlayerController extends Controller
{
    public function __construct(
        private readonly PlayerService $playerService,
    ) {}

    public function show(Request $request, Player $player): PlayerResource
    {
        if ($player->team_id !== $request->user()->team?->id) {
            throw new AccessDeniedHttpException;
        }

        $player->load(['position', 'country']);

        return new PlayerResource($player);
    }

    public function transfers(Request $request, Player $player): AnonymousResourceCollection
    {
        if ($player->team_id !== $request->user()->team?->id) {
            throw new AccessDeniedHttpException;
        }

        $transfers = $player->transfers()
            ->with(['sellerTeam.country', 'buyerTeam.country'])
            ->latest()
            ->paginate();

        return TransferResource::collection($transfers);
    }

    public function update(UpdatePlayerRequest $request, Player $player): PlayerResource
    {
        if ($player->team_id !== $request->user()->team?->id) {
            throw new AccessDeniedHttpException;
        }

        $this->playerService->update($player, $request->validated());

        $player->load(['position', 'country']);

        return new PlayerResource($player);
    }
}
