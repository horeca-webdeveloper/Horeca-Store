<?php

namespace Botble\Ecommerce\Events;

use Botble\Base\Events\Event;
use Botble\Ecommerce\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductVariationCreated extends Event
{
    use SerializesModels;
    use Dispatchable;

    public function __construct(public Product $product)
    {
    }
}
