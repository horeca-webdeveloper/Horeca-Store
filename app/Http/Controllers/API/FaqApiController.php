<?php
namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Botble\Faq\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqApiController extends Controller
{
    public function getFaqsByProduct($product_id): JsonResponse
    {
        $faqs = Faq::where('product_id', $product_id)
            ->where('status', 'published')
            ->get(['id', 'question', 'answer', 'product_id']);

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }
}
