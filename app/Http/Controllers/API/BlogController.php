<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Ecommerce\Models\Blog;
use Botble\Ecommerce\Models\BlogCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $blogs = Blog::with('category')
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $blogs->getCollection()->transform(function ($blog) {
            return $this->formatBlog($blog);
        });

        return response()->json($blogs);
    }

    public function show($slug)
    {
        $blog = Blog::with('category')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json($this->formatBlog($blog));
    }

    public function like($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->increment('total_likes');
        return response()->json(['total_likes' => $blog->total_likes]);
    }

    public function share($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->increment('total_shares');
        return response()->json(['total_shares' => $blog->total_shares]);
    }

    public function view($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->increment('total_views');
        return response()->json(['total_views' => $blog->total_views]);
    }

    public function categories()
    {
        $categories = BlogCategory::where('status', 'published')
            ->orderBy('order', 'asc')
            ->get();

        return response()->json($categories);
    }

    private function formatBlog($blog)
    {
        return [
            'id' => $blog->id,
            'name' => $blog->name,
            'slug' => $blog->slug,
            'description' => $blog->description,
            'desktop_banner' => $blog->desktop_banner,
            'desktop_banner_alt' => $blog->desktop_banner_alt,
            'mobile_banner' => $blog->mobile_banner,
            'mobile_banner_alt' => $blog->mobile_banner_alt,
            'thumbnail' => $blog->thumbnail,
            'thumbnail_alt' => $blog->thumbnail_alt,
            'faqs' => $blog->faqs,
            'tags' => $blog->tags,
            'total_views' => $blog->total_views,
            'total_likes' => $blog->total_likes,
            'total_shares' => $blog->total_shares,
            'is_featured' => $blog->is_featured,
            'created_at' => $blog->created_at,
            'category' => $blog->category ? [
                'id' => $blog->category->id,
                'name' => $blog->category->name,
                'slug' => $blog->category->slug,
                'description' => $blog->category->description,
            ] : null,
        ];
    }

    public function postComment(Request $request, $id) {
		/* Validate incoming request data*/
		$validator = Validator::make($request->all(), [
			'comment' => 'required|string', /* Comment must be a required text*/
			'parent_id' => 'nullable|integer', /* Parent ID can be null or an integer*/
			'created_by' => 'required|integer', /* Created by must be a required integer*/
		]);

		if ($validator->fails()) {
			/* Return validation errors as a response*/
			return response()->json([
				'status' => 'error',
				'message' => $validator->errors()->first(),
				'errors' => $validator->errors(),
			], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$post = Post::findOrFail($id); /* Ensure the post exists*/

		/* Add the comment*/
		$comment = $post->comments()->create([
			'comment' => $request->comment,
			'parent_id' => $request->parent_id ?? null,
			'created_by' => $request->created_by,
		]);

		return response()->json([
			'status' => 'success',
			'message' => $request->parent_id ? 'Replied successfully.' : 'Comment added successfully.',
			'data' => [
				'post' => $post,
				'comments' => $post->comments,
			],
		], Response::HTTP_OK);
	}
}
