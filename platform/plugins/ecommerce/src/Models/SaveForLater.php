<?php

namespace Botble\Ecommerce\Models;

use Botble\ACL\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Botble\Base\Models\BaseModel;
class SaveForLater extends BaseModel
{
    use HasFactory;

    protected $table = 'save_for_later';

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
    ];
    
        public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
