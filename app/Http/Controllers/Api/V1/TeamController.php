<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function show(Request $request): TeamResource
    {
        $team = $request->user()->team()
            ->with(['country', 'players.position', 'players.country'])
            ->firstOrFail();

        return new TeamResource($team);
    }
}
