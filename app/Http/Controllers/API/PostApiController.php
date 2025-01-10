<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

use Botble\Blog\Models\Post;

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
    
            // Remove all inline CSS styles from the content using regex
            $post->content = preg_replace('/<[^>]+style=".*?"[^>]*>/', '<$1>', $post->content); // Removes inline styles
    
            // Remove empty or invalid content like "<><>"
            $post->content = preg_replace('/<[^>]*>/s', '', $post->content); // Removes any malformed or empty tags
            $post->content = trim($post->content); // Remove any leading/trailing spaces
            
            // Remove all occurrences of non-breaking spaces (&nbsp;)
            $post->content = preg_replace('/(&nbsp;)+/', '', $post->content); // Removes one or more &nbsp;
    
            // If content is empty after cleaning, set it to null or an empty string
            if (empty($post->content)) {
                $post->content = null; // or "" to make it an empty string
            }
    
            return $post;
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $posts,
        ], Response::HTTP_OK);
    }
    
	public function getlikes(Request $request)
	{
		// Fetch all posts, you can modify this to include pagination if necessary
		$posts = Post::with(['tags', 'categories', 'author'])->get();
	
		// Process posts to include only the required fields
		$posts = $posts->map(function ($post) {
			// Include only the required fields (id, views, likes, shares)
			return $post->only(['id', 'views', 'likes', 'shares']);
		});
	
		return response()->json([
			'status' => 'success',
			'data' => $posts,
		], Response::HTTP_OK);
	}
	
	public function show($id)
{
    // Fetch the post by ID or slug, including related tags, categories, and author
    $post = Post::with(['tags', 'categories', 'author'])->findOrFail($id);

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

    // Remove all inline CSS styles from the content using regex
    $post->content = preg_replace('/<[^>]+style=".*?"[^>]*>/', '<$1>', $post->content);

    // Remove empty or invalid content like "<><>"
    $post->content = preg_replace('/<[^>]*>/s', '', $post->content);
    $post->content = trim($post->content);

    // Remove all occurrences of non-breaking spaces (&nbsp;)
    $post->content = preg_replace('/(&nbsp;)+/', '', $post->content);

    // If content is empty after cleaning, set it to null or an empty string
    if (empty($post->content)) {
        $post->content = null; // or "" to make it an empty string
    }

    return response()->json([
        'status' => 'success',
        'data' => $post,
    ], Response::HTTP_OK);
}


	public function update(Request $request, $id) {
		/* Validate incoming request data*/
		$validator = Validator::make($request->all(), [
			'type' => ['required', Rule::in(['like', 'view', 'share'])],
			'action' => ['required_if:type,like', 'boolean'],
		]);

		if ($validator->fails()) {
			/* Return validation errors as a response*/
			return response()->json([
				'status' => 'error',
				'message' => $validator->errors()->first(),
				'errors' => $validator->errors(),
			], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$post = Post::findOrFail($id); /* Use findOrFail to handle invalid IDs*/

		/* Process the request based on the type*/
		if ($request->type === 'like') {
			if ($request->action) {
				$post->increment('likes'); /* Action `true` means increment*/
			} else {
				$post->decrement('likes'); /* Action `false` means decrement*/
			}
		} elseif ($request->type === 'view') {
			$post->increment('views');
		} elseif ($request->type === 'share') {
			$post->increment('shares');
		}

		return response()->json([
			'success' => true,
			'message' => ucfirst(Str::plural($request->type)) . ' updated successfully.',
			'data' => $post,
		], Response::HTTP_OK);
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
