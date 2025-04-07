<?php
namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeGroup extends BaseModel
{
    protected $table = 'attribute_groups';

    protected $fillable = [
        'name',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id');
    }
}
