<?php

namespace Theme\Martfury\Http\Requests;

use Botble\Support\Http\Requests\Request;

class SendDownloadAppLinksRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }
}
