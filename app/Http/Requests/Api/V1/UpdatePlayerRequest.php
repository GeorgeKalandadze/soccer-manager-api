<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'array'],
            'first_name.en' => ['required_with:first_name', 'string', 'max:255'],
            'first_name.ka' => ['required_with:first_name', 'string', 'max:255'],
            'last_name' => ['sometimes', 'array'],
            'last_name.en' => ['required_with:last_name', 'string', 'max:255'],
            'last_name.ka' => ['required_with:last_name', 'string', 'max:255'],
            'country_id' => ['sometimes', 'integer', 'exists:countries,id'],
        ];
    }
}
