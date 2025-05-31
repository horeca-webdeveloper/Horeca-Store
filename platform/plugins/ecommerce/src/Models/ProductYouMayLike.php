<?php
// In ProductYouMayLike.php
namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductYouMayLike extends BaseModel
{
    use HasFactory;

    protected $table = 'product_you_may_likes';

    protected $fillable = [
        'product_id',
    ];

    // The product this recommendation belongs to
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    // Items linked to this "you may like" entry
    public function items()
    {
        return $this->hasMany(ProductYouMayLikeItem::class, 'product_you_may_like_id', 'id')
                    ->orderBy('priority', 'asc');
    }

    // The related products recommended (via the items table)
    public function relatedProducts()
    {
        return $this->hasManyThrough(
            Product::class,
            ProductYouMayLikeItem::class,
            'product_you_may_like_id', // Foreign key on product_you_may_like_items table
            'id',                      // Foreign key on products table (local key of product)
            'id',                      // Local key on product_you_may_likes table
            'product_id'               // Local key on product_you_may_like_items table
        )->orderBy('product_you_may_like_items.priority', 'asc');
    }
}
