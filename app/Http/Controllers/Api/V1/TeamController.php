<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Services\TeamService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    public function show(Request $request): TeamResource
    {
        $team = $request->user()->team()
            ->with(['country', 'players.position', 'players.country'])
            ->firstOrFail();

        return new TeamResource($team);
    }

    public function update(UpdateTeamRequest $request): TeamResource
    {
        $team = $request->user()->team()->firstOrFail();

        $this->teamService->update($team, $request->validated());

        $team->load(['country', 'players.position', 'players.country']);

        return new TeamResource($team);
    }
}
