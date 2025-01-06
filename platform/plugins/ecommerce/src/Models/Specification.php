<?php
// namespace Botble\Ecommerce\Models;

// use Illuminate\Database\Eloquent\Model;

// class Specification extends Model
// {
//     protected $fillable = [
//         'product_id',
//         'spec_name',
//         'spec_value',
//     ];

//     public function product()
//     {
//         return $this->belongsTo(Product::class);
//     }
// }


namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class Specification extends Model
{
    protected $fillable = ['product_id', 'spec_name', 'spec_value'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
