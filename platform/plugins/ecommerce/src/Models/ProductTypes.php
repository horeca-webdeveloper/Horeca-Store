<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductTypes extends BaseModel
{
    protected $table = 'ec_products_product_types';

    protected $fillable = [
        'name',
        'description',
        'images',
        'status',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
        'name' => SafeContent::class,
        'description' => SafeContent::class,
    ];

    public function products(): BelongsToMany
    {
        return $this
            ->belongsToMany(Product::class, 'ec_products_product_types_product', 'producttypes_id', 'product_id')
            ->where('is_variation', 0);
    }
}
