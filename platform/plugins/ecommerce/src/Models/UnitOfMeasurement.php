<?php


namespace Botble\Ecommerce\Models;
use Botble\Base\Models\BaseModel;
class UnitOfMeasurement extends BaseModel
{
    protected $fillable = ['name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'unit_of_measurement_id');
    }
}
