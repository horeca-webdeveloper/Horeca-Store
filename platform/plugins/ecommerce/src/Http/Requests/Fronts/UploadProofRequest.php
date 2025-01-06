<?php

namespace Botble\Ecommerce\Http\Requests\Fronts;

use Botble\Support\Http\Requests\Request;

class UploadProofRequest extends Request
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:2048'],
        ];
    }
}
