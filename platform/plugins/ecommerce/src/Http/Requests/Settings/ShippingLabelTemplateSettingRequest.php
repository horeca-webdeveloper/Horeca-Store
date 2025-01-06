<?php

namespace Botble\Ecommerce\Http\Requests\Settings;

use Botble\Support\Http\Requests\Request;

class ShippingLabelTemplateSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:1000000'],
        ];
    }
}
