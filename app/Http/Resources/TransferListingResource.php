<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asking_price' => $this->asking_price,
            'status' => $this->status,
            'player' => new PlayerResource($this->whenLoaded('player')),
            'seller_team' => new TeamResource($this->whenLoaded('sellerTeam')),
            'created_at' => $this->created_at,
        ];
    }
}
