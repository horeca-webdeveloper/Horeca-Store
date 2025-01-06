<?php
namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Botble\ACL\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;

class RecentlyViewedProduct extends BaseModel
{
    use HasFactory;

    protected $table = 'ec_customer_recently_viewed_products';

    protected $fillable = [
        'customer_id',
        'product_id',
    ];

    // You can add relationships here, for example, to get product info
   public function product()
{
    return $this->belongsTo(Product::class);
}
}
