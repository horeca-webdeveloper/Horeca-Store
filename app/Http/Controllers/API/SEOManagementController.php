<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\SEOManagement;

class SEOManagementController extends Controller
{
    public function index()
    {
        $seoData = SEOManagement::with('seo_secondary_keywords')->get()->map(function ($item) {
            return $this->filterFields($item);
        });

        return response()->json([
            'status' => true,
            'data' => $seoData
        ]);
    }

    public function getByRelationalId($relational_id)
    {
        $seoData = SEOManagement::with('seo_secondary_keywords')
            ->where('relational_id', $relational_id)
            ->get()
            ->map(function ($item) {
                return $this->filterFields($item);
            });

        return response()->json([
            'status' => true,
            'data' => $seoData
        ]);
    }

    private function filterFields($item)
    {
        return [
            'id' => $item->id,
            'relational_id' => $item->relational_id,
            'relational_type' => $item->relational_type,
            'url' => $item->url,
            'primary_keyword' => $item->primary_keyword,
            'title_tag' => $item->title_tag,
            'meta_title' => $item->meta_title,
            'meta_description' => $item->meta_description,
            'internal_links' => $item->internal_links,
            'indexing' => $item->indexing,
            'og_title' => $item->og_title,
            'og_description' => $item->og_description,
            'og_image_url' => $item->og_image_url,
            'og_image_alt_text' => $item->og_image_alt_text,
            'og_image_name' => $item->og_image_name,
            'tags' => $item->tags,
            'schema' => $item->schema
        ];
    }

    // public function getParagraphData($relational_id)
    // {
    //     $seoData = SEOManagement::where('relational_id', $relational_id)
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'id' => $item->id,
    //                 'relational_id' => $item->relational_id,
    //                 'relational_type' => $item->relational_type,
    //                 'internal_links' => $item->internal_links,
    //                 'paragraph_1' => $item->paragraph_1,
    //                 'paragraph_2' => $item->paragraph_2,
    //                 'paragraph_3' => $item->paragraph_3,
    //                 'paragraph_4' => $item->paragraph_4,
    //                 'popular_tags' => $item->popular_tags,
    //             ];
    //         });

    //     return response()->json([
    //         'status' => true,
    //         'data' => $seoData
    //     ]);
    // }

    public function getParagraphData($relational_id)
    {
        $seoData = SEOManagement::where('relational_id', $relational_id)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'relational_id' => $item->relational_id,
                    'relational_type' => $item->relational_type,
                    'internal_links' => $item->internal_links,
                    'paragraph_1' => $item->paragraph_1,
                    'paragraph_2' => $item->paragraph_2,
                    'paragraph_3' => $item->paragraph_3,
                    'paragraph_4' => $item->paragraph_4,
                    'popular_tags' => is_string($item->popular_tags)
                        ? json_decode($item->popular_tags, true)
                        : ($item->popular_tags ?? []),
                ];
            });
    
        return response()->json([
            'status' => true,
            'data' => $seoData
        ]);
    }
    


}
