<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\TransferListing;
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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TransferListing::class)->unique()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Player::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Team::class, 'seller_team_id')->constrained('teams')->restrictOnDelete();
            $table->foreignIdFor(Team::class, 'buyer_team_id')->constrained('teams')->restrictOnDelete();
            $table->unsignedBigInteger('price')->comment('price in dollars');
            $table->unsignedBigInteger('previous_market_value')->comment('value in dollars');
            $table->unsignedBigInteger('new_market_value')->comment('value in dollars');
            $table->timestamps();

            $table->index(['player_id', 'created_at']);
            $table->index(['seller_team_id', 'created_at']);
            $table->index(['buyer_team_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
