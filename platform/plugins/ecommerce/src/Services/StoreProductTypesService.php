<?php

namespace Botble\Ecommerce\Services;

use Botble\Base\Events\CreatedContentEvent;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductTypes;
use Illuminate\Http\Request;

class StoreProductTypesService
{
    public function execute(Request $request, Product $product): void
    {
        $productTypeIds = $request->producttypes ?? [];
        $product->producttypes()->sync($productTypeIds);
    }
}
