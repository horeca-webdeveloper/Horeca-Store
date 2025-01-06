<?php

namespace Botble\Ecommerce\Listeners;

use Botble\Base\Facades\BaseHelper;
use Botble\Ecommerce\Events\ProductVariationCreated;
use Botble\Ecommerce\Models\Customer;
use Throwable;

class UpdateProductVariationInfo
{
    public function handle(ProductVariationCreated $event): void
    {
        try {
            if (! auth('customer')->check()) {
                return;
            }

            $product = $event->product;

            if ($product->store_id !== null) {
                return;
            }

            $product->store_id = auth('customer')->user()->store->id;
            $product->created_by_id = auth('customer')->id();
            $product->created_by_type = Customer::class;

            $product->save();
        } catch (Throwable $exception) {
            BaseHelper::logError($exception);
        }
    }
}
