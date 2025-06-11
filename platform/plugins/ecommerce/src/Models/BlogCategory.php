<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;

class BlogCategory extends BaseModel

{
    protected $table = 'blog_categories';

    protected $fillable = [
        'name', 'slug', 'parent_id', 'description', 'status',
        'created_by', 'order', 'is_featured',
    ];

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'blog_category_id');
    }
}
