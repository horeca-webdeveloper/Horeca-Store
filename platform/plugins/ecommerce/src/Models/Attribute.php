<?php
namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;

class Attribute extends BaseModel
{
    protected $table = 'attributes';

    // Define the inverse relationship with ProductAttributes
    public function productAttributes()
    {
        return $this->hasMany(ProductAttributes::class, 'attribute_id', 'id');  // Ensure the foreign key is correct
    }

    public function attributeGroup()
{
    return $this->belongsTo(\Botble\Ecommerce\Models\AttributeGroup::class, 'attribute_group_id');
}
    public function attributeGroups()
    {
        return $this->belongsToMany(
            AttributeGroup::class,
            'attribute_group_attributes',
            'attribute_id',
            'attribute_group_id'
        );
    }



}