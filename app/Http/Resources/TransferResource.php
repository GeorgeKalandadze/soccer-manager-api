<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => $this->price,
            'previous_market_value' => $this->previous_market_value,
            'new_market_value' => $this->new_market_value,
            'player' => new PlayerResource($this->whenLoaded('player')),
            'seller_team' => new TeamResource($this->whenLoaded('sellerTeam')),
            'buyer_team' => new TeamResource($this->whenLoaded('buyerTeam')),
            'created_at' => $this->created_at,
        ];
    }
}
