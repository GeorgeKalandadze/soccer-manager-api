<?php

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfer_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Player::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Team::class, 'seller_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedBigInteger('asking_price')->comment('price in dollars');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['seller_team_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX transfer_listings_active_player_unique ON transfer_listings (player_id) WHERE status = 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_listings');
    }
};
