<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferListingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'asking_price' => ['required', 'integer', 'min:1'],
        ];
    }
}
