<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductYouMayLikeItem extends Model
{
    use HasFactory;

    protected $table = 'product_you_may_like_items';

    protected $fillable = [
        'product_you_may_like_id',
        'product_id',
        'priority'
    ];

    protected $casts = [
        'priority' => 'integer'
    ];

    /**
     * Get the "you may like" entry that owns this item
     */
    public function productYouMayLike()
    {
        return $this->belongsTo(ProductYouMayLike::class, 'product_you_may_like_id');
    }

    /**
     * Get the product associated with this item
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Scope to order by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}