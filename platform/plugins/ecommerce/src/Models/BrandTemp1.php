<?php
namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Botble\Base\Models\BaseModel;
use Botble\Base\Enums\BaseStatusEnum;

class BrandTemp1 extends BaseModel
{
    protected $table = 'brand_temp_1';

    protected $guarded = [];

    protected $casts = [
        'page_top_banners_desktop' => 'array',
        'page_top_banners_desktop_alt_text' => 'array',
        'page_top_banners_desktop_file_name' => 'array',
        'page_top_banners_mobile' => 'array',
        'page_top_banners_mobile_alt_text' => 'array',
        'page_top_banners_mobile_file_name' => 'array',
        'category_banners' => 'array',
        'category_banners_alt_text' => 'array',
        'category_banners_file_name' => 'array',
        'page_middle_banners_desktop' => 'array',
        'page_middle_banners_desktop_alt_text' => 'array',
        'page_middle_banners_desktop_file_name' => 'array',
        'page_middle_banners_mobile' => 'array',
        'page_middle_banners_mobile_alt_text' => 'array',
        'page_middle_banners_mobile_file_name' => 'array',
    ];

    public function getCategoryIdAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setCategoryIdAttribute($value)
    {
        $this->attributes['category_id'] = is_array($value) ? json_encode($value) : $value;
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}