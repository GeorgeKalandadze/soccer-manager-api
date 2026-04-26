<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'array'],
            'name.en' => ['required_with:name', 'string', 'max:255'],
            'name.ka' => ['required_with:name', 'string', 'max:255'],
            'country_id' => ['sometimes', 'integer', 'exists:countries,id'],
        ];
    }
}
