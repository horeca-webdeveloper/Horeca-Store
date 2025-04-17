<?php
namespace Botble\Ecommerce\Models;
use Illuminate\Database\Eloquent\Model;

class SEOManagement extends Model
{
    protected $table = 'seo_management';

    protected $fillable = [
        'paragraph_1', 'relational_id', 'relational_type', 'url', 'primary_keyword',
        'monthly_search_volume', 'title_tag', 'meta_title', 'meta_description',
        'internal_links', 'indexing', 'og_title', 'og_description', 'og_image_url',
        'og_image_alt_text', 'og_image_name', 'tags', 'schema', 'schema_rating',
        'schema_reviews_count', 'created_by', 'updated_by', 'paragraph_2',
        'paragraph_3', 'paragraph_4', 'popular_tags',
    ];

    protected $casts = [
        'internal_links' => 'array',
        'tags' => 'array',
        'schema' => 'array',
        'popular_tags' => 'array',
    ];

    public function seo_secondary_keywords()
    {
        return $this->hasMany(SecondaryKeyword::class, 'primary_keyword_id', 'id');
    }
}