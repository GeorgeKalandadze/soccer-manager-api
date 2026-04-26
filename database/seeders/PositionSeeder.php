<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => json_encode(['en' => 'goalkeeper', 'ka' => 'მეკარე']),
                'abbreviation' => 'GK',
            ],
            [
                'name' => json_encode(['en' => 'defender', 'ka' => 'მცველი']),
                'abbreviation' => 'DF',
            ],
            [
                'name' => json_encode(['en' => 'midfielder', 'ka' => 'ნახევარმცველი']),
                'abbreviation' => 'MF',
            ],
            [
                'name' => json_encode(['en' => 'attacker', 'ka' => 'თავდამსხმელი']),
                'abbreviation' => 'AT',
            ],
        ];

        if (! DB::table('positions')->count()) {
            DB::table('positions')->insert($positions);
        }
    }
}
