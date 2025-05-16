<?php
// app/Models/ProductAttribute.php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;

class ProductAttributes extends BaseModel
{
    protected $table = 'product_attributes';

    protected $fillable = ['product_id', 'attribute_id', 'attribute_value' , 'attribute_value_id']; // Ensure this is correct

    // Define the relationship with the Product model
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Define the relationship with the Attribute model
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id', 'id');  // Ensure 'attribute_id' is correct
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }

    public function attributeGroup()
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id');
    }
    

}