<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;

class Blog extends BaseModel
{
    protected $table = 'blogs';

    protected $fillable = [
        'name', 'slug', 'desktop_banner', 'desktop_banner_alt', 'mobile_banner',
        'mobile_banner_alt', 'thumbnail', 'thumbnail_alt', 'description', 'created_by',
        'blog_category_id', 'faqs', 'tags', 'total_views', 'total_likes',
        'total_shares', 'status', 'is_featured'
    ];

    protected $casts = [
        'faqs' => 'array',
        'tags' => 'array',
        'description' => 'array',
    ];
  

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }
}