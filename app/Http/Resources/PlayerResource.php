<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'age' => $this->age,
            'market_value' => $this->market_value,
            'position' => new PositionResource($this->whenLoaded('position')),
            'country' => new CountryResource($this->whenLoaded('country')),
        ];
    }
}
