<?php

use App\Models\Country;
use App\Models\Position;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Position::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(Country::class)->constrained()->restrictOnDelete();
            $table->json('first_name');
            $table->json('last_name');
            $table->unsignedTinyInteger('age');
            $table->unsignedBigInteger('market_value')->comment('value in dollars');
            $table->timestamps();

            $table->index(['position_id', 'country_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
