<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Controller;
use Botble\Blog\Models\Category;


class PostCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with(['posts'])->get(); // Load posts relation if needed

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }
}
