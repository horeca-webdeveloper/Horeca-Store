<?php

namespace Botble\Ecommerce\Models;

use Botble\ACL\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Enums\DiscountTargetEnum;
use Botble\Ecommerce\Enums\DiscountTypeEnum;
use Botble\Ecommerce\Enums\ProductTypeEnum;
use Botble\Ecommerce\Enums\StockStatusEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Services\Products\UpdateDefaultProductService;
use Botble\Faq\Models\Faq;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @method notOutOfStock()
 */
class CategoryFilter extends BaseModel
{
    use Concerns\ProductPrices;

    protected $table = 'temp_products';

    protected $fillable = [
        'name',
        'description',
        'content',
        'image', // Featured image
        'images',
        'sku',
        'order',
        'quantity' => 'required|integer|min:10', // or any other rules that apply
        'allow_checkout_when_out_of_stock',
        'with_storehouse_management',
        'is_featured',
        'brand_id',
        'is_variation',
        'sale_type',
        'price',
        'sale_price',
        'start_date',
        'end_date',
        'length',
        'width',
        'height',
        'weight',
        'tax_id',
        'views',
        'stock_status',
        'barcode',
        'cost_per_item',
       // 'generate_license_code',
        'minimum_order_quantity',
        'maximum_order_quantity',
        'specs_sheet_heading',
        'specs_sheet',
        'product_id',
        'approval_status',
        'box_quantity',
        'discount',
        'margin',
        'remarks',
        'rejection_count'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
 }
