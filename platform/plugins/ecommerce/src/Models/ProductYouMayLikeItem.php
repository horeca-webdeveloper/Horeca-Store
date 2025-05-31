<?php
// In ProductYouMayLikeItem.php
namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductYouMayLikeItem extends BaseModel
{
    use HasFactory;

    protected $table = 'product_you_may_like_items';

    protected $fillable = [
        'product_you_may_like_id',
        'product_id',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    // The parent "you may like" entry
    public function productYouMayLike()
    {
        return $this->belongsTo(ProductYouMayLike::class, 'product_you_may_like_id', 'id');
    }

    // The recommended product itself
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    // Scope to order by priority
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
