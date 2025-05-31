<?php

// App/Models/ProductYouMayLike.php
namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductYouMayLike extends BaseModel
{
    use HasFactory;

    protected $table = 'product_you_may_likes';

    protected $fillable = [
        'product_id'
    ];

    /**
     * Get the product that owns this "you may like" entry
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get all the related product items for this "you may like" entry
     */
    public function items()
    {
        return $this->hasMany(ProductYouMayLikeItem::class, 'product_you_may_like_id')
                    ->orderBy('priority', 'asc');
    }

    /**
     * Get the related products through the items relationship
     */
    public function relatedProducts()
    {
        return $this->hasManyThrough(
            Product::class,
            ProductYouMayLikeItem::class,
            'product_you_may_like_id', // Foreign key on product_you_may_like_items table
            'id', // Foreign key on products table
            'id', // Local key on product_you_may_likes table
            'product_id' // Local key on product_you_may_like_items table
        )->orderBy('product_you_may_like_items.priority', 'asc');
    }
}
