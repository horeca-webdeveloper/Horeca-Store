<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Blog\Models\Post;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PopularPostsController extends Controller
{
    /**
     * Get the top 5 featured posts
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopFeaturedPosts()
    {
        return Post::with(['tags', 'categories', 'author'])
                    ->where('is_featured', 1)
                    ->take(5) // Limit to top 5 posts
                    ->get();
    }

    /**
     * Process posts to extract images, remove CSS, and modify content
     * 
     * @param \Illuminate\Database\Eloquent\Collection $posts
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function processPosts($posts)
    {
        return $posts->map(function ($post) {
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
            
            // Remove all occurrences of non-breaking spaces (&nbsp;), including encoded variations
            $post->content = preg_replace('/(&nbsp;|&#160;|&#xA0;|&#x20;|&#xA0;)+/', '', $post->content); // Removes &nbsp; and its variations
    
            // If content is empty after cleaning, set it to null or an empty string
            if (empty($post->content)) {
                $post->content = null; // or "" to make it an empty string
            }
    
            return $post;
        });
    }
    
    /**
     * Get the top 5 featured posts and process them
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Fetch and process the posts
        $posts = $this->getTopFeaturedPosts();
        $posts = $this->processPosts($posts);

        return response()->json([
            'status' => 'success',
            'data' => $posts,
        ], Response::HTTP_OK);
    }
}
