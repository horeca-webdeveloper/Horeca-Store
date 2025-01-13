{!! apply_filters('ecommerce_product_variation_form_start', null, $product) !!}
@php
    $user = Auth::user(); // Get the logged-in user
    $userRoles = $user->roles->pluck('name')->all() ?? [];

    // Check if the user's role ID is 6 (Product Specialist)
    $productspec = in_array('Product Specialist', $userRoles);

    // Check if the user's role ID is 22 (Pricing)
    $pricingUser = in_array('Pricing', $userRoles);
@endphp
@if($pricingUser)

<div class="row price-group">
    <input
        class="detect-schedule d-none"
        name="sale_type"
        type="hidden"
        value="{{ old('sale_type', $product ? $product->sale_type : 0) }}"
    >

    <div class="col-md-4">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.sku')"
            name="sku"
            :value="old('sku', $product ? $product->sku : (new Botble\Ecommerce\Models\Product()))"
        />

        @if (($isVariation && !$product) || ($product && $product->is_variation && !$product->sku))
            <x-core::form.checkbox
                :label="trans('plugins/ecommerce::products.form.auto_generate_sku')"
                name="auto_generate_sku"
            />
        @endif
    </div>

    <div class="col-md-4">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.price')"
            name="price"
            :data-thousands-separator="EcommerceHelper::getThousandSeparatorForInputMask()"
            :data-decimal-separator="EcommerceHelper::getDecimalSeparatorForInputMask()"
            :value="old('price', $product ? $product->price : $originalProduct->price ?? 0)"
            step="any"
            class="input-mask-number"
            :group-flat="true"
        >
            <x-slot:prepend>
                <span class="input-group-text">{{ get_application_currency()->symbol }}</span>
            </x-slot:prepend>
        </x-core::form.text-input>
    </div>
    <div class="col-md-4">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.price_sale')"
            class="input-mask-number"
            name="sale_price"
            :data-thousands-separator="EcommerceHelper::getThousandSeparatorForInputMask()"
            :data-decimal-separator="EcommerceHelper::getDecimalSeparatorForInputMask()"
            :value="old('sale_price', $product ? $product->sale_price : $originalProduct->sale_price ?? null)"
            :group-flat="true"
            :data-sale-percent-text="trans('plugins/ecommerce::products.form.price_sale_percent_helper')"
        >
            <x-slot:helper-text>
                {!! trans('plugins/ecommerce::products.form.price_sale_percent_helper', ['percent' => '<strong>' . ($product ? $product->sale_percent : 0) . '%</strong>']) !!}
            </x-slot:helper-text>

            <x-slot:prepend>
                <span class="input-group-text">{{ get_application_currency()->symbol }}</span>
            </x-slot:prepend>
            <x-slot:labelDescription>
                <a
                    class="turn-on-schedule"
                    @style(['display: none' => old('sale_type', $product ? $product->sale_type : $originalProduct->sale_type ?? 0) == 1])
                    href="javascript:void(0)"
                >
                    {{ trans('plugins/ecommerce::products.form.choose_discount_period') }}
                </a>
                <a
                    class="turn-off-schedule"
                    @style(['display: none' => old('sale_type', $product ? $product->sale_type : $originalProduct->sale_type ?? 0) == 0])
                    href="javascript:void(0)"
                >
                    {{ trans('plugins/ecommerce::products.form.cancel') }}
                </a>
            </x-slot:labelDescription>
        </x-core::form.text-input>
    </div>

    <div class="col-md-6 scheduled-time" @style(['display: none' => old('sale_type', $product ? $product->sale_type : $originalProduct->sale_type ?? 0) == 0])>
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.date.start')"
            name="start_date"
            class="form-date-time"
            :value="old('start_date', $product ? $product->start_date : $originalProduct->start_date ?? null)"
            :placeholder="BaseHelper::getDateTimeFormat()"
        />
    </div>
    <div class="col-md-6 scheduled-time" @style(['display: none' => old('sale_type', $product ? $product->sale_type : $originalProduct->sale_type ?? 0) == 0])>
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.date.end')"
            name="end_date"
            :value="old('end_date', $product ? $product->end_date : $originalProduct->end_date ?? null)"
            :placeholder="BaseHelper::getDateTimeFormat()"
            class="form-date-time"
        />
    </div>

    <div class="col-md-6">
        <x-core::form.label for="stock_status">
            {{ trans('plugins/ecommerce::products.unit_of_measurement') }}
        </x-core::form.label>

        <!-- Call the function and bind it to 'shipping_length_id' -->
        {!! measurement_unit_dropdown('unit_of_measurement_id', old('unit_of_measurement_id', $product->unit_of_measurement_id ?? null)) !!}
    </div>

    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.cost_per_item')"
            name="cost_per_item"
            id="cost-per-item"
            :value="old('cost_per_item', $product ? $product->cost_per_item : $originalProduct->cost_per_item ?? 0)"
            :placeholder="trans('plugins/ecommerce::products.form.cost_per_item_placeholder')"
            step="any"
            class="input-mask-number"
            :group-flat="true"
            :helper-text="trans('plugins/ecommerce::products.form.cost_per_item_helper')"
        >
            <x-slot:prepend>
                <span class="input-group-text">{{ get_application_currency()->symbol }}</span>
            </x-slot:prepend>
        </x-core::form.text-input>
    </div>

    <!-- Margin Calculation Display -->
    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('Profit Margin')"
            name="margin"
            id="margin"
            value="0%"
            :readonly="true"
            class="form-control bg-light"
        />
    </div>

    <input
        name="product_id"
        type="hidden"
        value="{{ $product->id ?? null }}"
    >

    {!! apply_filters('ecommerce_product_variation_form_middle', null, $product) !!}

    <x-core::form.on-off.checkbox
        :label="trans('plugins/ecommerce::products.form.storehouse.storehouse')"
        name="with_storehouse_management"
        class="storehouse-management-status"
        :checked="old('with_storehouse_management', $product ? $product->with_storehouse_management : $originalProduct->with_storehouse_management ?? 0) == 1"
    />

    <x-core::form.fieldset class="storehouse-info" @style(['display: none' => old('with_storehouse_management', $product ? $product->with_storehouse_management : $originalProduct->with_storehouse_management ?? 0) == 0])>
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.storehouse.quantity')"
            name="quantity"
            :value="old('quantity', $product ? $product->quantity : $originalProduct->quantity ?? 0)"
            class="input-mask-number"
        />

        <x-core::form.on-off.checkbox
            :label="trans('plugins/ecommerce::products.form.stock.allow_order_when_out')"
            name="allow_checkout_when_out_of_stock"
            :checked="old('allow_checkout_when_out_of_stock', $product ? $product->allow_checkout_when_out_of_stock : $originalProduct->allow_checkout_when_out_of_stock ?? 0) == 1"
        />
    </x-core::form.fieldset>

    <x-core::form.fieldset class="stock-status-wrapper" @style(['display: none' => old('with_storehouse_management', $product ? $product->with_storehouse_management : $originalProduct->with_storehouse_management ?? 0) == 1])>
        <x-core::form.label for="stock_status">
            {{ trans('plugins/ecommerce::products.form.stock_status') }}
        </x-core::form.label>
        @foreach (Botble\Ecommerce\Enums\StockStatusEnum::labels() as $status => $label)
            <x-core::form.checkbox
                :label="$label"
                name="stock_status"
                type="radio"
                :value="$status"
                :checked="old('stock_status', $product ? $product->stock_status : 'in_stock') == $status"
                :inline="true"
            />
        @endforeach
    </x-core::form.fieldset>
</div>

@if($product)
<x-core::form.fieldset>
    <legend>
        <h3>Buy more Save more</h3>
    </legend>

    <div id="discount-group">
        @if($product && $product->discounts && $product->discounts->count())
            @foreach ($product->discounts as $index => $discount)
                <div class="discount-item">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <input type="hidden" name="discount[{{ $index }}][discount_id]" value="{{ $discount->id }}">
                            <label for="product_quantity_{{ $index }}" class="form-label quantity-label">Buying Quantity</label>
                            <input type="number" class="form-control product-quantity"
                                   name="discount[{{ $index }}][product_quantity]"
                                   value="{{ old('discount.' . $index . '.product_quantity', $discount->product_quantity) }}"
                                   onchange="calculateDiscount(this)">
                        </div>

                        <div class="col-md-6">
                            <label for="discount_{{ $index }}" class="form-label">Discount</label>
                            <input type="number" class="form-control discount-percentage"
                                   name="discount[{{ $index }}][discount]"
                                   value="{{ old('discount.' . $index . '.discount', $discount->value) }}"
                                   onchange="calculateDiscount(this)">
                        </div>

                        <div class="col-md-6">
                            <label for="price_after_discount_{{ $index }}" class="form-label">Price after Discount</label>
                            <input type="number" class="form-control price-after-discount"
                                   name="discount[{{ $index }}][price_after_discount]"
                                   value="{{ old('discount.' . $index . '.price_after_discount') }}" readonly>
                        </div>

                        <div class="col-md-6">
                            <label for="margin_{{ $index }}" class="form-label">Margin</label>
                            <input type="number" class="form-control margin"
                                   name="discount[{{ $index }}][margin]"
                                   value="{{ old('discount.' . $index . '.margin') }}" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="fromDate_{{ $index }}" class="form-label">From Date</label>
                            <input type="datetime-local" class="form-control"
                                   name="discount[{{ $index }}][discount_from_date]"
                                   value="{{ old('discount.' . $index . '.discount_from_date', \Carbon\Carbon::parse($discount->start_date)) }}">
                        </div>

                        <div class="col-md-4">
                            <label for="toDate_{{ $index }}" class="form-label">To Date</label>
                            <input type="datetime-local" class="form-control to-date"
                                   name="discount[{{ $index }}][discount_to_date]"
                                   value="{{ old('discount.' . $index . '.discount_to_date', $discount->end_date ? \Carbon\Carbon::parse($discount->end_date) : '') }}">
                        </div>

                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input me-2 never-expired-checkbox"
                                       type="checkbox"
                                       name="discount[{{ $index }}][never_expired]"
                                       value="1"
                                       {{ old('discount.' . $index . '.never_expired', $discount->end_date ? '' : 'checked') }}
                                       onchange="toggleToDateField(this)">
                                <label class="form-check-label" for="never_expired_{{ $index }}">Never Expired</label>
                            </div>
                        </div>
                    </div>

                    @if ($loop->iteration < 2)
                        <div class="row g-3 my-3">
                            <div class="col-md-12">&nbsp;
                            </div>
                        </div>

                    @elseif ($loop->iteration == 2)
                        <div class="row g-3 mb-3">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-success add-btn {{$product->discounts->count() >= 3 ? 'disabled':''}}"><i class="fas fa-plus"></i> Add</button>
                            </div>
                        </div>

                    @elseif($loop->iteration > 2 && $loop->iteration)
                        <div class="row g-3 mb-3">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-danger remove-btn1"><i class="fas fa-minus"></i> Remove</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            @for($i=0; $i<2; $i++)
            <div class="discount-item">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="product_quantity" class="form-label quantity-label">Buying Quantity</label>
                        <input type="number" class="form-control product-quantity" name="discount[{{$i}}][product_quantity]" onchange="calculateDiscount(this)">
                    </div>

                    <div class="col-md-6">
                        <label for="discount" class="form-label">Discount</label>
                        <input type="number" class="form-control discount-percentage" name="discount[{{$i}}][discount]" onchange="calculateDiscount(this)">
                    </div>

                    <div class="col-md-6">
                        <label for="price_after_discount" class="form-label">Price after Discount</label>
                        <input type="number" class="form-control price-after-discount" name="discount[{{$i}}][price_after_discount]" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="margin" class="form-label">Margin</label>
                        <input type="number" class="form-control margin" name="discount[{{$i}}][margin]" readonly>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="fromDate" class="form-label">From Date</label>
                        <input type="datetime-local" class="form-control" name="discount[{{$i}}][discount_from_date]">
                    </div>

                    <div class="col-md-4">
                        <label for="toDate" class="form-label">To Date</label>
                        <input type="datetime-local" class="form-control to-date" name="discount[{{$i}}][discount_to_date]">
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input me-2 never-expired-checkbox" type="checkbox" name="discount[{{$i}}][never_expired]" value="1" onchange="toggleToDateField(this)">
                            <label class="form-check-label" for="never_expired">Never Expired</label>
                        </div>
                    </div>
                </div>
                @if ($i < 1)
                    <div class="row g-3 my-3">
                        <div class="col-md-12">&nbsp;
                        </div>
                    </div>

                @elseif ($i == 1)
                    <div class="row g-3 mb-3">
                        <div class="col-md-12 text-end">
                            <button type="button" class="btn btn-success add-btn"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </div>
                @endif
            </div>
            @endfor
        @endif
    </div>
</x-core::form.fieldset>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-4 d-flex align-items-center">
        <div class="form-check">
            <input class="form-check-input me-2" type="checkbox" name="in_process" value="1" checked>
            <label class="form-check-label" for="in_process">Is Draft</label>
        </div>
    </div>
</div>

@else
@if (!$productspec)



<div class="row price-group">
    <input
        class="detect-schedule d-none"
        name="sale_type"
        type="hidden"
        value="{{ old('sale_type', $product ? $product->sale_type : 0) }}"
    >

    <div class="col-md-4">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.sku')"
            name="sku"
            :value="old('sku', $product ? $product->sku : (new Botble\Ecommerce\Models\Product()))"
        />

        @if (($isVariation && !$product) || ($product && $product->is_variation && !$product->sku))
            <x-core::form.checkbox
                :label="trans('plugins/ecommerce::products.form.auto_generate_sku')"
                name="auto_generate_sku"
            />
        @endif
    </div>

    <div class="col-md-4">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.price')"
            name="price"
            :data-thousands-separator="EcommerceHelper::getThousandSeparatorForInputMask()"
            :data-decimal-separator="EcommerceHelper::getDecimalSeparatorForInputMask()"
            :value="old('price', $product ? $product->price : $originalProduct->price ?? 0)"
            step="any"
            class="input-mask-number"
            :group-flat="true"
        >
            <x-slot:prepend>
                <span class="input-group-text">{{ get_application_currency()->symbol }}</span>
            </x-slot:prepend>
        </x-core::form.text-input>
    </div>
    <div class="col-md-4">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.price_sale')"
            class="input-mask-number"
            name="sale_price"
            :data-thousands-separator="EcommerceHelper::getThousandSeparatorForInputMask()"
            :data-decimal-separator="EcommerceHelper::getDecimalSeparatorForInputMask()"
            :value="old('sale_price', $product ? $product->sale_price : $originalProduct->sale_price ?? null)"
            :group-flat="true"
            :data-sale-percent-text="trans('plugins/ecommerce::products.form.price_sale_percent_helper')"
        >
            <x-slot:helper-text>
                {!! trans('plugins/ecommerce::products.form.price_sale_percent_helper', ['percent' => '<strong>' . ($product ? $product->sale_percent : 0) . '%</strong>']) !!}
            </x-slot:helper-text>

            <x-slot:prepend>
                <span class="input-group-text">{{ get_application_currency()->symbol }}</span>
            </x-slot:prepend>
            <x-slot:labelDescription>
                <a
                    class="turn-on-schedule"
                    @style(['display: none' => old('sale_type', $product ? $product->sale_type : $originalProduct->sale_type ?? 0) == 1])
                    href="javascript:void(0)"
                >
                    {{ trans('plugins/ecommerce::products.form.choose_discount_period') }}
                </a>
                <a
                    class="turn-off-schedule"
                    @style(['display: none' => old('sale_type', $product ? $product->sale_type : $originalProduct->sale_type ?? 0) == 0])
                    href="javascript:void(0)"
                >
                    {{ trans('plugins/ecommerce::products.form.cancel') }}
                </a>
            </x-slot:labelDescription>
        </x-core::form.text-input>
    </div>

    <div class="col-md-6 scheduled-time" @style(['display: none' => old('sale_type', $product ? $product->sale_type : $originalProduct->sale_type ?? 0) == 0])>
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.date.start')"
            name="start_date"
            class="form-date-time"
            :value="old('start_date', $product ? $product->start_date : $originalProduct->start_date ?? null)"
            :placeholder="BaseHelper::getDateTimeFormat()"
        />
    </div>
    <div class="col-md-6 scheduled-time" @style(['display: none' => old('sale_type', $product ? $product->sale_type : $originalProduct->sale_type ?? 0) == 0])>
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.date.end')"
            name="end_date"
            :value="old('end_date', $product ? $product->end_date : $originalProduct->end_date ?? null)"
            :placeholder="BaseHelper::getDateTimeFormat()"
            class="form-date-time"
        />
    </div>

    <div class="col-md-6">
        <x-core::form.label for="stock_status">
            {{ trans('plugins/ecommerce::products.unit_of_measurement') }}
        </x-core::form.label>

        <!-- Call the function and bind it to 'shipping_length_id' -->
        {!! measurement_unit_dropdown('unit_of_measurement_id', old('unit_of_measurement_id', $product->unit_of_measurement_id ?? null)) !!}
    </div>


    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.cost_per_item')"
            name="cost_per_item"
            id="cost-per-item"
            :value="old('cost_per_item', $product ? $product->cost_per_item : $originalProduct->cost_per_item ?? 0)"
            :placeholder="trans('plugins/ecommerce::products.form.cost_per_item_placeholder')"
            step="any"
            class="input-mask-number"
            :group-flat="true"
            :helper-text="trans('plugins/ecommerce::products.form.cost_per_item_helper')"
        >
            <x-slot:prepend>
                <span class="input-group-text">{{ get_application_currency()->symbol }}</span>
            </x-slot:prepend>
        </x-core::form.text-input>
    </div>

    <!-- Margin Calculation Display -->
    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('Profit Margin')"
            name="margin"
            id="margin"
            value="0%"
            :readonly="true"
            class="form-control bg-light"
        />
    </div>

    <input
        name="product_id"
        type="hidden"
        value="{{ $product->id ?? null }}"
    >
    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('plugins/ecommerce::products.form.barcode')"
            name="barcode"
            type="text"
            :value="old('barcode', $product ? $product->barcode : $originalProduct->barcode ?? null)"
            step="any"
            :placeholder="trans('plugins/ecommerce::products.form.barcode_placeholder')"
        />
    </div>
</div>

{!! apply_filters('ecommerce_product_variation_form_middle', null, $product) !!}

<x-core::form.on-off.checkbox
    :label="trans('plugins/ecommerce::products.form.storehouse.storehouse')"
    name="with_storehouse_management"
    class="storehouse-management-status"
    :checked="old('with_storehouse_management', $product ? $product->with_storehouse_management : $originalProduct->with_storehouse_management ?? 0) == 1"
/>

<x-core::form.fieldset class="storehouse-info" @style(['display: none' => old('with_storehouse_management', $product ? $product->with_storehouse_management : $originalProduct->with_storehouse_management ?? 0) == 0])>
    <x-core::form.text-input
        :label="trans('plugins/ecommerce::products.form.storehouse.quantity')"
        name="quantity"
        :value="old('quantity', $product ? $product->quantity : $originalProduct->quantity ?? 0)"
        class="input-mask-number"
    />

    <x-core::form.on-off.checkbox
        :label="trans('plugins/ecommerce::products.form.stock.allow_order_when_out')"
        name="allow_checkout_when_out_of_stock"
        :checked="old('allow_checkout_when_out_of_stock', $product ? $product->allow_checkout_when_out_of_stock : $originalProduct->allow_checkout_when_out_of_stock ?? 0) == 1"
    />
</x-core::form.fieldset>

<x-core::form.fieldset class="stock-status-wrapper" @style(['display: none' => old('with_storehouse_management', $product ? $product->with_storehouse_management : $originalProduct->with_storehouse_management ?? 0) == 1])>
    <x-core::form.label for="stock_status">
        {{ trans('plugins/ecommerce::products.form.stock_status') }}
    </x-core::form.label>
    @foreach (Botble\Ecommerce\Enums\StockStatusEnum::labels() as $status => $label)
        <x-core::form.checkbox
            :label="$label"
            name="stock_status"
            type="radio"
            :value="$status"
            :checked="old('stock_status', $product ? $product->stock_status : 'in_stock') == $status"
            :inline="true"
        />
    @endforeach
</x-core::form.fieldset>

@if($product)

<x-core::form.fieldset>

    <legend>
        <h3>Buy more Save more</h3>
    </legend>
    <div id="discount-group">

        @if($product && $product->discounts && $product->discounts->count())
            @foreach ($product->discounts as $index => $discount)
                <div class="discount-item">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <input type="hidden" name="discount[{{ $index }}][discount_id]" value="{{ $discount->id }}">
                            <label for="product_quantity_{{ $index }}" class="form-label quantity-label">Buying Quantity</label>
                            <input type="number" class="form-control product-quantity"
                                   name="discount[{{ $index }}][product_quantity]"
                                   value="{{ old('discount.' . $index . '.product_quantity', $discount->product_quantity) }}"
                                   onchange="calculateDiscount(this)">
                        </div>

                        <div class="col-md-6">
                            <label for="discount_{{ $index }}" class="form-label">Discount</label>
                            <input type="number" class="form-control discount-percentage"
                                   name="discount[{{ $index }}][discount]"
                                   value="{{ old('discount.' . $index . '.discount', $discount->value) }}"
                                   onchange="calculateDiscount(this)">
                        </div>

                        <div class="col-md-6">
                            <label for="price_after_discount_{{ $index }}" class="form-label">Price after Discount</label>
                            <input type="number" class="form-control price-after-discount"
                                   name="discount[{{ $index }}][price_after_discount]"
                                   value="{{ old('discount.' . $index . '.price_after_discount') }}" readonly>
                        </div>

                        <div class="col-md-6">
                            <label for="margin_{{ $index }}" class="form-label">Margin</label>
                            <input type="number" class="form-control margin"
                                   name="discount[{{ $index }}][margin]"
                                   value="{{ old('discount.' . $index . '.margin') }}" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="fromDate_{{ $index }}" class="form-label">From Date</label>
                            <input type="datetime-local" class="form-control"
                                   name="discount[{{ $index }}][discount_from_date]"
                                   value="{{ old('discount.' . $index . '.discount_from_date', \Carbon\Carbon::parse($discount->start_date)) }}">
                        </div>

                        <div class="col-md-4">
                            <label for="toDate_{{ $index }}" class="form-label">To Date</label>
                            <input type="datetime-local" class="form-control to-date"
                                   name="discount[{{ $index }}][discount_to_date]"
                                   value="{{ old('discount.' . $index . '.discount_to_date', $discount->end_date ? \Carbon\Carbon::parse($discount->end_date) : '') }}">
                        </div>

                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input me-2 never-expired-checkbox"
                                       type="checkbox"
                                       name="discount[{{ $index }}][never_expired]"
                                       value="1"
                                       {{ old('discount.' . $index . '.never_expired', $discount->end_date ? '' : 'checked') }}
                                       onchange="toggleToDateField(this)">
                                <label class="form-check-label" for="never_expired_{{ $index }}">Never Expired</label>
                            </div>
                        </div>
                    </div>

                    @if ($loop->iteration < 2)
                        <div class="row g-3 my-3">
                            <div class="col-md-12">&nbsp;
                            </div>
                        </div>

                    @elseif ($loop->iteration == 2)
                        <div class="row g-3 mb-3">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-success add-btn {{$product->discounts->count() >= 3 ? 'disabled':''}}"><i class="fas fa-plus"></i> Add</button>
                            </div>
                        </div>

                    @elseif($loop->iteration > 2 && $loop->iteration)
                        <div class="row g-3 mb-3">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-danger remove-btn1"><i class="fas fa-minus"></i> Remove</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            @for($i=0; $i<2; $i++)
            <div class="discount-item">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="product_quantity" class="form-label quantity-label">Buying Quantity</label>
                        <input type="number" class="form-control product-quantity" name="discount[{{$i}}][product_quantity]" onchange="calculateDiscount(this)">
                    </div>

                    <div class="col-md-6">
                        <label for="discount" class="form-label">Discount</label>
                        <input type="number" class="form-control discount-percentage" name="discount[{{$i}}][discount]" onchange="calculateDiscount(this)">
                    </div>

                    <div class="col-md-6">
                        <label for="price_after_discount" class="form-label">Price after Discount</label>
                        <input type="number" class="form-control price-after-discount" name="discount[{{$i}}][price_after_discount]" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="margin" class="form-label">Margin</label>
                        <input type="number" class="form-control margin" name="discount[{{$i}}][margin]" readonly>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="fromDate" class="form-label">From Date</label>
                        <input type="datetime-local" class="form-control" name="discount[{{$i}}][discount_from_date]">
                    </div>

                    <div class="col-md-4">
                        <label for="toDate" class="form-label">To Date</label>
                        <input type="datetime-local" class="form-control to-date" name="discount[{{$i}}][discount_to_date]">
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input me-2 never-expired-checkbox" type="checkbox" name="discount[{{$i}}][never_expired]" value="1" onchange="toggleToDateField(this)">
                            <label class="form-check-label" for="never_expired">Never Expired</label>
                        </div>
                    </div>
                </div>
                @if ($i < 1)
                    <div class="row g-3 my-3">
                        <div class="col-md-12">&nbsp;
                        </div>
                    </div>

                @elseif ($i == 1)
                    <div class="row g-3 mb-3">
                        <div class="col-md-12 text-end">
                            <button type="button" class="btn btn-success add-btn"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </div>
                @endif
            </div>
            @endfor
        @endif
    </div>
</x-core::form.fieldset>
@endif
@endif

@if (
    ! EcommerceHelper::isEnabledSupportDigitalProducts()
    || (!$product && !$originalProduct &&  request()->input('product_type') != Botble\Ecommerce\Enums\ProductTypeEnum::DIGITAL)
    || ($originalProduct && $originalProduct->isTypePhysical()) || ($product && $product->isTypePhysical())
)
    <x-core::form.fieldset>
        <legend>
            <h3>Product fields</h3>
        </legend>
        <div class="row">


            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.weight') }} "
                    name="weight"
                    :value="old('weight', $product->weight ?? $originalProduct->weight ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    <x-slot:prepend>
                        <!-- Call the function and bind it to 'weight_unit_id' -->
                        <span class="input-group-text">
                            {!! ecommerce_unit_dropdown('weight_unit_id', old('weight_unit_id', $product->weight_unit_id ?? null)) !!}
                        </span>
                    </x-slot:prepend>
                </x-core::form.text-input>
            </div>

            <div class="col-md-3 col-md-6">

                <x-core::form.label for="stock_status">
                    {{ trans('plugins/ecommerce::products.length_unit') }}
                </x-core::form.label>

                <!-- Call the function and bind it to 'length_unit_id' -->
                {!! ecommerce_unit_dropdown('length_unit_id', old('length_unit_id', $product->length_unit_id ?? null)) !!}
            </div>

            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.length') }} "
                    name="length"
                    :value="old('length', $product->length ?? $originalProduct->length ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    {{-- <x-slot:prepend>
                        <!-- Call the function and bind it to 'length_unit_id' -->
                        <span class="input-group-text">
                            {!! ecommerce_unit_dropdown('length_unit_id', old('length_unit_id', $product->length_unit_id ?? null)) !!}
                        </span>
                    </x-slot:prepend> --}}
                </x-core::form.text-input>
            </div>

            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.depth') }} "
                    name="depth"
                    :value="old('depth', $product->depth ?? $originalProduct->depth ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    {{-- <x-slot:prepend>
                        <!-- Call the function and bind it to 'depth_unit_id' -->
                        <span class="input-group-text">
                            {!! ecommerce_unit_dropdown('depth_unit_id', old('depth_unit_id', $product->depth_unit_id ?? null)) !!}
                        </span>
                    </x-slot:prepend> --}}
                </x-core::form.text-input>
            </div>


            {{-- <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.depth') }} "
                    name="depth"
                    :value="old('depth', $product ? $product->depth : $originalProduct->depth ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >


                 <x-slot:prepend>
                    <span class="input-group-text">{!! ecommerce_width_height_unit(true) !!} <!-- Render dropdown --></span>
                </x-slot:prepend>
                </x-core::form.text-input>
            </div> --}}
            {{-- <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.width') }} ({{ ecommerce_width_height_unit() }})"
                    name="width"
                    :value="old('width', $product ? $product->width : $originalProduct->width ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    <x-slot:prepend>
                        <span class="input-group-text">{{ ecommerce_width_height_unit() }}</span>
                    </x-slot:prepend>
                </x-core::form.text-input>
            </div> --}}
            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.height') }} "
                    name="height"
                    :value="old('height', $product->height ?? $originalProduct->height ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    {{-- <x-slot:prepend>
                        <!-- Call the function and bind it to 'depth_unit_id' -->
                        <span class="input-group-text">
                            {!! ecommerce_unit_dropdown('height_unit_id', old('height_unit_id', $product->height_unit_id ?? null)) !!}
                        </span>
                    </x-slot:prepend> --}}
                </x-core::form.text-input>
            </div>
            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.width') }} "
                    name="width"
                    :value="old('width', $product->width ?? $originalProduct->width ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    {{-- <x-slot:prepend>
                        <!-- Call the function and bind it to 'depth_unit_id' -->
                        <span class="input-group-text">
                            {!! ecommerce_unit_dropdown('width_unit_id', old('width_unit_id', $product->width_unit_id ?? null)) !!}
                        </span>
                    </x-slot:prepend> --}}
                </x-core::form.text-input>
            </div>





        </div>
    </x-core::form.fieldset>


    <x-core::form.fieldset>
        <legend>
            <h3>{{ trans('plugins/ecommerce::products.form.shipping.title') }}</h3>
        </legend>
        <div class="row">



            <div class="col-md-3 col-md-6">
                <x-core::form.label for="stock_status">
                    {{ trans('plugins/ecommerce::products.length_unit') }}
                </x-core::form.label>

                <!-- Call the function and bind it to 'shipping_length_id' -->
                {!! ecommerce_unit_dropdown('shipping_length_id', old('shipping_length_id', $product->shipping_length_id ?? null)) !!}
            </div>

            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.shipping_length') }} "
                    name="shipping length"
                    :value="old('shipping_length', $product->shipping_length ?? $originalProduct->shipping_length ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                </x-core::form.text-input>
            </div>

            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.shipping_depth') }} "
                    name="shipping depth"
                    :value="old('shipping_depth', $product->shipping_depth ?? $originalProduct->shipping_depth ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    {{-- <x-slot:prepend>
                        <!-- Call the function and bind it to 'depth_unit_id' -->
                        <span class="input-group-text">
                            {!! ecommerce_unit_dropdown('shipping_depth_id', old('shipping_depth_id', $product->shipping_depth_id ?? null)) !!}
                        </span>
                    </x-slot:prepend> --}}
                </x-core::form.text-input>
            </div>


            {{-- <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.depth') }} "
                    name="depth"
                    :value="old('depth', $product ? $product->depth : $originalProduct->depth ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >


                 <x-slot:prepend>
                    <span class="input-group-text">{!! ecommerce_width_height_unit(true) !!} <!-- Render dropdown --></span>
                </x-slot:prepend>
                </x-core::form.text-input>
            </div> --}}
            {{-- <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.width') }} ({{ ecommerce_width_height_unit() }})"
                    name="width"
                    :value="old('width', $product ? $product->width : $originalProduct->width ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    <x-slot:prepend>
                        <span class="input-group-text">{{ ecommerce_width_height_unit() }}</span>
                    </x-slot:prepend>
                </x-core::form.text-input>
            </div> --}}
            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.shipping_height') }} "
                    name="shipping height"
                    :value="old('shipping_height', $product->shipping_height ?? $originalProduct->shipping_height ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    {{-- <x-slot:prepend>
                        <!-- Call the function and bind it to 'depth_unit_id' -->
                        <span class="input-group-text">
                            {!! ecommerce_unit_dropdown('shipping_height_id', old('shipping_height_id', $product->shipping_height_id ?? null)) !!}
                        </span>
                    </x-slot:prepend> --}}
                </x-core::form.text-input>
            </div>
            <div class="col-md-3 col-md-6">
                <x-core::form.text-input
                    label="{{ trans('plugins/ecommerce::products.form.shipping.shipping_width') }} "
                    name="shipping_width"
                    :value="old('shipping_width', $product->shipping_width ?? $originalProduct->shipping_width ?? 0)"
                    class="input-mask-number"
                    :group-flat="true"
                >
                    {{-- <x-slot:prepend>
                        <!-- Call the function and bind it to 'depth_unit_id' -->
                        <span class="input-group-text">
                            {!! ecommerce_unit_dropdown('shipping_width_id', old('shipping_width_id', $product->shipping_width_id ?? null)) !!}
                        </span>
                    </x-slot:prepend> --}}
                </x-core::form.text-input>
            </div>





        </div>
    </x-core::form.fieldset>
@endif

@if (
    EcommerceHelper::isEnabledSupportDigitalProducts()
    && (
        (!$product &&  !$originalProduct && request()->input('product_type') == Botble\Ecommerce\Enums\ProductTypeEnum::DIGITAL)
        || ($originalProduct && $originalProduct->isTypeDigital()) || ($product && $product->isTypeDigital())
    )
)
    <x-core::form.on-off.checkbox
        :label="trans('plugins/ecommerce::products.digital_attachments.generate_license_code_after_purchasing_product')"
        name="generate_license_code"
        :checked="old('generate_license_code', $product ? $product->generate_license_code : $originalProduct->generate_license_code ?? 0)"
    />

    <x-core::form-group class="product-type-digital-management">
        <x-core::form.label for="product_file" class="mb-3">
            {{ trans('plugins/ecommerce::products.digital_attachments.title') }}

            <x-slot:description>
                <div class="btn-list">
                    <x-core::button type="button" class="digital_attachments_btn" size="sm" icon="ti ti-paperclip">
                        {{ trans('plugins/ecommerce::products.digital_attachments.add') }}
                    </x-core::button>

                    <x-core::button type="button" class="digital_attachments_external_btn" size="sm" icon="ti ti-link">
                        {{ trans('plugins/ecommerce::products.digital_attachments.add_external_link') }}
                    </x-core::button>
                </div>
            </x-slot:description>
        </x-core::form.label>

        <x-core::table>
            <x-core::table.header>
                <x-core::table.header.cell />
                <x-core::table.header.cell>
                    {{ trans('plugins/ecommerce::products.digital_attachments.file_name') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell>
                    {{ trans('plugins/ecommerce::products.digital_attachments.file_size') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell>
                    {{ trans('core/base::tables.created_at') }}
                </x-core::table.header.cell>
                <x-core::table.header.cell />
            </x-core::table.header>

            <x-core::table.body>
                @if($product)
                    @foreach ($product->productFiles as $file)
                        <x-core::table.body.row>
                            <x-core::table.body.cell>
                                <x-core::form.on-off.checkbox
                                    name="product_files[{{ $file->id }}]"
                                    class="digital-attachment-checkbox"
                                    :checked="true"
                                    :single="true"
                                />
                            </x-core::table.body.cell>
                            <x-core::table.body.cell>
                                @if ($file->is_external_link)
                                    <a href="{{ $file->url }}" target="_blank">
                                        <x-core::icon name="ti ti-link" />
                                        {{ $file->basename ? Str::limit($file->basename, 50) : $file->url }}
                                    </a>
                                @else
                                    <x-core::icon name="ti ti-paperclip" />
                                    {{ Str::limit($file->basename, 50) }}
                                @endif
                            </x-core::table.body.cell>
                            <x-core::table.body.cell>
                                {{ $file->file_size ? BaseHelper::humanFileSize($file->file_size) : '-' }}
                            </x-core::table.body.cell>
                            <x-core::table.body.cell>
                                {{ BaseHelper::formatDate($file->created_at) }}
                            </x-core::table.body.cell>
                            <x-core::table.body.cell />
                        </x-core::table.body.row>
                    @endforeach
                @endif
            </x-core::table.body>
        </x-core::table>

        <div class="digital_attachments_input">
            <input
                name="product_files_input[]"
                data-id="{{ Str::random(10) }}"
                type="file"
            >
        </div>
    </x-core::form-group>

    @if (request()->ajax())
        @include('plugins/ecommerce::products.partials.digital-product-file-template')
    @else
        @pushOnce('footer')
            @include('plugins/ecommerce::products.partials.digital-product-file-template')
        @endpushOnce
    @endif
@endif
@endif

{!! apply_filters('ecommerce_product_variation_form_end', null, $product) !!}


  <!-- Real-time Margin Calculation Script -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
        const costInput = document.querySelector('#cost-per-item');
        const priceInput = document.querySelector('#price');
        const marginInput = document.querySelector('#margin');

        // Function to calculate and update the margin
        const calculateMargin = () => {
            const cost = parseFloat(costInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;

            if (price > 0) {
                const margin = ((price - cost) / price) * 100;
                marginInput.value = `${margin.toFixed(2)}%`;
            } else {
                marginInput.value = '0%';
            }
        };

        // Attach real-time event listeners
        [costInput, priceInput].forEach(input => {
            input.addEventListener('input', calculateMargin);
        });

        // Initialize margin calculation on page load
        calculateMargin();

        // Run the calculation every second
        setInterval(calculateMargin, 1000);
    });
</script>



<script>
    /* JavaScript to handle add/remove functionality */
    document.addEventListener('DOMContentLoaded', () => {
        const unitOfMeasurementDropdown = document.getElementById('unit-of-measurement');
        const unitLabels = {
            1: 'Pieces',
            2: 'Dozen',
            3: 'Box',
            4: 'Case'
        };

        // Function to update all quantity labels
        function updateAllQuantityLabels() {
            const selectedValue = unitOfMeasurementDropdown.value;
            const unitText = unitLabels[selectedValue] || 'Units';

            // Update all labels in the discount group
            document.querySelectorAll('.quantity-label').forEach((label, index) => {
                label.textContent = `Buying Quantity Tier ${index+1} (in ${unitText})`;
            });
        }

        // Trigger label updates when the UoM dropdown changes
        unitOfMeasurementDropdown.addEventListener('change', updateAllQuantityLabels);

        // Initial update on page load
        updateAllQuantityLabels();


        const discountGroup = document.getElementById('discount-group');
        discountGroup.addEventListener('click', (event) => {
            if (event.target.classList.contains('add-btn')) {
                // Disable the "Add" button temporarily
                event.target.classList.add('disabled');
                event.target.disabled = true;

                /* Find the current count of discount items */
                const count = discountGroup.querySelectorAll('.discount-item').length;

                if (count < 3) {
                    /* Create a new input field group with updated name attributes */
                    const newField = document.createElement('div');
                    newField.classList.add('discount-item');
                    newField.innerHTML = `
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="product_quantity" class="form-label quantity-label">Buying Quantity</label>
                                <input type="number" class="form-control product-quantity" name="discount[${count}][product_quantity]" onchange="calculateDiscount(this)">
                            </div>
                            <div class="col-md-6">
                                <label for="discount" class="form-label">Discount</label>
                                <input type="number" class="form-control discount-percentage" name="discount[${count}][discount]" onchange="calculateDiscount(this)">
                            </div>
                            <div class="col-md-6">
                                <label for="price_after_discount" class="form-label">Price after Discount</label>
                                <input type="number" class="form-control price-after-discount" name="discount[${count}][price_after_discount]">
                            </div>
                            <div class="col-md-6">
                                <label for="margin" class="form-label">Margin</label>
                                <input type="number" class="form-control margin" name="discount[${count}][margin]">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="fromDate" class="form-label">From Date</label>
                                <input type="datetime-local" class="form-control" name="discount[${count}][discount_from_date]">
                            </div>
                            <div class="col-md-4">
                                <label for="toDate" class="form-label">To Date</label>
                                <input type="datetime-local" class="form-control to-date" name="discount[${count}][discount_to_date]">
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input me-2 never-expired-checkbox" type="checkbox" name="discount[${count}][never_expired]" value="1" onchange="toggleToDateField(this)">
                                    <label class="form-check-label" for="never_expired">Never Expired</label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-danger remove-btn1"><i class="fas fa-minus"></i> Remove</button>
                            </div>
                        </div>
                    `;
                    discountGroup.appendChild(newField);

                    // Ensure the new label reflects the current UoM
                    updateAllQuantityLabels();
                }
            } else if (event.target.classList.contains('remove-btn1')) {
                /* Remove input fields */
                const discountItem = event.target.closest('.discount-item');
                if (discountItem) {
                    discountItem.remove();
                }

                // Re-enable the Add button after a remove
                const addButton = discountGroup.querySelector('.add-btn');
                if (addButton) {
                    addButton.classList.remove('disabled');
                    addButton.disabled = false;
                }
            }
        });


        // Ensure all input fields are properly handled
        const discountItems = document.querySelectorAll('.discount-item');
        discountItems.forEach(item => {
            calculateDiscount(item);  // Call once on page load to set initial values
        });
    });

    function calculateDiscount(element) {
        const discountItem = element.closest('.discount-item');
        const productRequiredInput = discountItem.querySelector('.product-quantity');
        const discountPercentageInput = discountItem.querySelector('.discount-percentage');
        const priceAfterDiscountInput = discountItem.querySelector('.price-after-discount');
        const marginInput = discountItem.querySelector('.margin');

        const price = document.querySelector('input[name="sale_price"]').value || document.querySelector('input[name="price"]').value || 0;
        const costPerItem = document.querySelector('input[name="cost_per_item"]').value || 0;
        const productRequired = parseFloat(productRequiredInput.value) || 0;
        const discountPercentage = parseFloat(discountPercentageInput.value) || 0;

        // Ensure all inputs are valid
        if (price > 0 && productRequired > 0 && discountPercentage > 0) {
            // Calculate discount amount
            const discountAmount = price * (discountPercentage / 100);

            // Calculate final price after discount
            const priceAfterDiscount = price - discountAmount;

            // Set the result in the readonly input field
            priceAfterDiscountInput.value = priceAfterDiscount.toFixed(2);

            const marginValue = (priceAfterDiscountInput.value - costPerItem)*100/priceAfterDiscountInput.value;
            marginInput.value = marginValue.toFixed(2);
        } else {
            // Clear the price after discount field if inputs are invalid or missing
            priceAfterDiscountInput.value = '';
        }
    }

    // Function to toggle the "To Date" field for each discount group
    function toggleToDateField(checkbox) {
        // Find the discount item container (group) that contains the checkbox
        const discountItem = checkbox.closest('.discount-item');

        // Get the "To Date" input field within this group
        const toDateInput = discountItem.querySelector('.to-date');

        // If "Never Expired" is checked, disable the "To Date" field
        if (checkbox.checked) {
            toDateInput.disabled = true;
        } else {
            toDateInput.disabled = false;
        }
    }

    // Add event listeners to all "Never Expired" checkboxes when the document is loaded
    document.addEventListener('DOMContentLoaded', () => {
        // Select all the "Never Expired" checkboxes
        const neverExpiredCheckboxes = document.querySelectorAll('.never-expired-checkbox');

        // For each checkbox, trigger the toggle function to set the initial state
        neverExpiredCheckboxes.forEach(checkbox => {
            toggleToDateField(checkbox);
        });
    });
</script>


