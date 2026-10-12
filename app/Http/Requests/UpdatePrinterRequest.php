<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePrinterRequest extends StorePrinterRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['required', 'string', 'max:128', Rule::unique('printers', 'name')->ignore($this->route('printer'))];

        return $rules;
    }
}
