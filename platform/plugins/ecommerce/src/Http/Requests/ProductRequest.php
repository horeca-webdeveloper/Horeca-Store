<?php

namespace Botble\Ecommerce\Http\Requests;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Ecommerce\Enums\CrossSellPriceType;
use Botble\Ecommerce\Enums\GlobalOptionEnum;
use Botble\Ecommerce\Enums\ProductTypeEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Product;
use Botble\Media\Facades\RvMedia;
use Botble\Support\Http\Requests\Request;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth; // Ensure you have this import for Auth
class ProductRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $options = $this->input('options');

        if (! empty($options)) {
            foreach ($options as $key => $option) {
                if (! empty($option['values'])) {
                    foreach ($option['values'] as $valueKey => $value) {
                        if (isset($value['order']) && $value['order'] === 'undefined') {
                            $options[$key]['values'][$valueKey]['order'] = 0;
                        }

                        if (isset($value['id']) && $value['id'] === 'undefined') {
                            $options[$key]['values'][$valueKey]['id'] = 0;
                        }
                    }
                }
            }
        }

        $this->merge(['options' => $options]);
    }


public function rules(): array
{
    $isAdmin = Auth::guard('web')->check(); // Check if the user is an admin

    $rules = [
        'name' => ['required', 'string', 'max:250'],
        'description' => ['nullable', 'string', 'max:300000'],
        'content' => ['nullable', 'string', 'max:300000'],
        'price' => [
            'numeric',
            'nullable',
            'min:0',
            Rule::when($this->input('sale_price'), function () {
                return 'gt:sale_price';
            }),
        ],
        'sale_price' => ['numeric', 'nullable', 'min:0'],
        'start_date' => ['date', 'nullable', 'required_if:sale_type,1'],
        'end_date' => 'date|nullable|after:' . ($this->input('start_date') ?? Carbon::now()->toDateTimeString()),
        'width' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'height' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'weight' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'depth' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'shipping_width' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'shipping_length' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'shipping_height' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'shipping_depth' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'length' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'images' => ['sometimes', 'array'],
        'images.*' => ['nullable', 'string'],
        'quantity' => ['numeric', 'nullable', 'min:0', 'max:100000000'],
        'status' => Rule::in(BaseStatusEnum::values()),
        'product_type' => Rule::in(ProductTypeEnum::values()),
        'product_files_input' => ['nullable', 'array'],
        'product_files_input.*' => 'nullable|file|mimes:' . (config('plugins.ecommerce.general.digital_products.allowed_mime_types') ?: RvMedia::getConfig('allowed_mime_types')),
        'product_files_external' => ['nullable', 'array'],
        'product_files_external.*.name' => ['nullable', 'string', 'max:120'],
        'product_files_external.*.link' => ['required', 'url', 'max:400'],
        'product_files_external.*.size' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        'taxes' => ['nullable', 'array'],
        'barcode' => [
            'nullable',
            'string',
            'max:150',
            // Rule::unique((new Product())->getTable())->ignore($this->route('product.id')),
        ],
        'sku' => [
            'nullable',
            'string',
            'max:150',
        ],
        'cost_per_item' => ['nullable', 'numeric', 'min:0'],
        'general_license_code' => ['nullable', 'in:0,1'],
        'categories' => ['nullable', 'array'],
        'categories.*' => ['nullable', 'exists:categories,id'],
        'product_collections' => ['nullable', 'array'],
        'product_collections.*' => ['nullable', 'exists:ec_product_collections,id'],
        'cross_sale_products' => ['nullable', 'array'],
        'cross_sale_products.*.id' => ['nullable', 'string', 'exists:ec_products,id'],
        'cross_sale_products.*.price' => ['nullable', 'numeric', 'min:0', 'max:100000000000'],
        'cross_sale_products.*.price_type' => ['nullable', 'string', Rule::in(CrossSellPriceType::values())],
        'minimum_order_quantity' => ['nullable', 'numeric', 'min:0'],
        'maximum_order_quantity' => ['nullable', 'numeric', 'min:0'],
        'testimonials.*.star' => $isAdmin ? 'nullable|integer' : 'required|integer', // Conditionally set rules
    ];

    if (EcommerceHelper::isEnabledProductOptions()) {
        $options = $this->input('options');

        if (! empty($options)) {
            $productOptionRules = $this->getRuleProductOptionRequest($options);
            $rules = array_merge($rules, $productOptionRules);
        }
    }

    return $rules;
}
    public function messages(): array
    {
        return [
            'name.required' => trans('plugins/ecommerce::products.product_create_validate_name_required'),
            'sale_price.max' => trans('plugins/ecommerce::products.product_create_validate_sale_price_max'),
            'sale_price.required_if' => trans('plugins/ecommerce::products.product_create_validate_sale_price_required_if'),
            'end_date.after' => trans('plugins/ecommerce::products.product_create_validate_end_date_after'),
            'start_date.required_if' => trans('plugins/ecommerce::products.product_create_validate_start_date_required_if'),
            'sale_price' => trans('plugins/ecommerce::products.product_create_validate_sale_price'),
        ];
    }

    public function attributes(): array
    {
        $options = $this->input('options');
        $attrs = [
            'product_files_external.*.link' => trans('plugins/ecommerce::products.digital_attachments.external_link_download'),
        ];

        if (! empty($options)) {
            foreach ($options as $key => $option) {
                $name = sprintf('options.%s.name', $key);
                $type = sprintf('options.%s.option_type', $key);
                $value = sprintf('options.%s.values', $key);
                $optionNumber = intval($key) + 1;
                $attrs[$name] = trans('plugins/ecommerce::product-option.option_name_attribute', ['key' => $optionNumber]);
                $attrs[$type] = trans('plugins/ecommerce::product-option.option_type_attribute', ['key' => $optionNumber]);
                $attrs[$value] = trans('plugins/ecommerce::product-option.option_value_name_attribute', ['key' => $optionNumber]);
                if (! empty($option['values'])) {
                    $attrs = array_merge($attrs, $this->getAttributeValue($key, $option['values']));
                }
            }
        }

        return $attrs;
    }

    protected function getAttributeValue(string $optionKey, array $values = []): array
    {
        $attrs = [];
        foreach ($values as $key => $value) {
            foreach ($value as $valueKey => $item) {
                $attrs['options.' . $optionKey . '.values.' . $key . '.' . $valueKey] = trans('plugins/ecommerce::product-option.option_value_attribute', ['option_key' => $optionKey, 'value_key' => $valueKey, 'item' => $item]);
            }
        }

        return $attrs;
    }

    protected function getRuleProductOptionRequest(array $options = []): array
    {
        $rules = [];
        foreach ($options as $key => $option) {
            $name = sprintf('options.%s.name', $key);
            $type = sprintf('options.%s.option_type', $key);
            $value = sprintf('options.%s.values', $key);
            $rules[$name] = 'required';
            $rules[$type] = 'required';
            $rules[$value] = 'required';
            $optionRules = [];

            if (isset($option['values'])) {
                $optionRules = $this->getRulesOfProductOptionValues(sprintf('options.%s', $key), $option['option_type'], array_filter($option['values']));
            }

            $rules = array_merge($rules, $optionRules);
        }

        return $rules;
    }

    protected function getRulesOfProductOptionValues(string $baseName, string $optionType, array $values = []): array
    {
        $rules = [];

        foreach ($values as $key => $value) {
            $rules[$baseName . '.values.' . $key . '.affect_price'] = 'numeric|min:0';
            if (isset($value['affect_type']) && $value['affect_type'] == GlobalOptionEnum::TYPE_PERCENT) {
                $rules[$baseName . '.values.' . $key . '.affect_price'] = 'numeric|between:1,100';
            }

            if ($optionType != GlobalOptionEnum::FIELD) {
                $rules[$baseName . '.values.' . $key . '.option_value'] = 'required';
            }
        }

        return $rules;
    }
}
