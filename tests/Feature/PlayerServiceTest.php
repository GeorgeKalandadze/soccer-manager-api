<?php

use App\Models\Team;
use App\Services\PlayerService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\PositionSeeder;

beforeEach(function () {
    $this->seed([CountrySeeder::class, PositionSeeder::class]);
});

it('generates a full squad for a team', function () {
    $team = Team::factory()->create();

    app(PlayerService::class)->generateForTeam($team);

    expect($team->players()->count())->toBe(20)
        ->and($team->players()->whereRelation('position', 'abbreviation', 'GK')->count())->toBe(3)
        ->and($team->players()->whereRelation('position', 'abbreviation', 'DF')->count())->toBe(6)
        ->and($team->players()->whereRelation('position', 'abbreviation', 'MF')->count())->toBe(6)
        ->and($team->players()->whereRelation('position', 'abbreviation', 'AT')->count())->toBe(5);
});
