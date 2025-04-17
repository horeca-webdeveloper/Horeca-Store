<?php
namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;


class SecondaryKeyword extends Model
{
    protected $table = 'seo_secondary_keywords';

    protected $fillable = [
        'primary_keyword_id', 'secondary_keyword', 'monthly_search_volume',
    ];

    public function seoManagement()
    {
        return $this->belongsTo(SEOManagement::class, 'primary_keyword_id', 'id');
    }
}