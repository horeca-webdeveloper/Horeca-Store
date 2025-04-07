<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeValue extends BaseModel
{
    protected $table = 'attribute_values';

    protected $fillable = [
        'attribute_id',
        'attribute_value',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_id');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttributes::class, 'attribute_id');
    }
}
