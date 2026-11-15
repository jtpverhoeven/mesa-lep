<?php

namespace App\Http\Requests;

use App\Models\Printer;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'printer' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                Rule::when($this->integer('printer') > 0, Rule::exists(Printer::class, 'id')),
            ],
        ];
    }
}
