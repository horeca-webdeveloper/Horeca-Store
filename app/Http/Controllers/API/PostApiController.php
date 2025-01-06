<?php
// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Botble\Blog\Models\Post;
// use Illuminate\Http\Request;
// use Symfony\Component\HttpFoundation\Response;

// class PostApiController extends Controller
// {
//     public function index(Request $request)
//     {
//         // Fetch all posts, you can modify this to include pagination if necessary
//         $posts = Post::with(['tags', 'categories', 'author'])->get();

//         return response()->json([
//             'status' => 'success',
//             'data' => $posts,
//         ], Response::HTTP_OK);
//     }
// }


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Blog\Models\Post;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostApiController extends Controller
{
    public function index(Request $request)
    {
        // Fetch all posts, you can modify this to include pagination if necessary
        $posts = Post::with(['tags', 'categories', 'author'])->get();

        // Process posts to extract images and remove the first one from content
        $posts = $posts->map(function ($post) {
            // Extract all image URLs from the content using regex
            preg_match_all('/<img[^>]+src="([^">]+)"/', $post->content, $matches);

            // If images are found, store them in a separate key
            $images = $matches[1]; // This will contain all image URLs

            // If there are images, remove the first image and its <figure> tag from the content
            if (!empty($images)) {
                // Store the first image and remove it from the content
                $post->images = [array_shift($images)]; // Only the first image

                // Remove the <figure class="image"> surrounding the first image and the image itself
                $post->content = preg_replace('/<figure class="image">.*?<img[^>]+src="' . preg_quote($post->images[0], '/') . '"[^>]*>.*?<\/figure>/s', '', $post->content, 1);
            } else {
                $post->images = [];
            }

            return $post;
        });

        return response()->json([
            'status' => 'success',
            'data' => $posts,
        ], Response::HTTP_OK);
    }
}
