<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use App\Services\PlayerService;
use Illuminate\Http\Request;
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
