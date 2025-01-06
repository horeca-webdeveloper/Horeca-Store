<?php

// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Botble\Menu\Models\Menu;
// use Botble\Menu\Models\MenuNode;
// use Illuminate\Support\Facades\Validator;
// use Illuminate\Http\JsonResponse;


// class MenuController extends Controller
// {
//     /**
//      * Get all menus with their nodes.
//      */

//      public function index(Request $request)
//     {
//         $id = $request->query('id');

//         // Fetch the menu with nodes if an ID is provided
//         if ($id) {
//             $menu = Menu::with('menuNodes')->find($id);
            
//             if (!$menu) {
//                 return response()->json(['message' => 'Menu not found'], 404);
//             }

//             return response()->json($menu);
//         }

//         // Fetch all menus without nodes if no ID is provided
//         $menus = Menu::all();
//         return response()->json($menus);
//     }
     
//     /**
//      * Get a specific menu by ID.
//      */
//     public function show($id)
//     {
//         $menu = Menu::with('nodes')->find($id);

//         if (!$menu) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Menu not found',
//             ], 404);
//         }

//         return response()->json([
//             'success' => true,
//             'data' => $menu,
//         ]);
//     }

//     /**
//      * Create a new menu.
//      */
//     public function store(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'name' => 'required|string|max:255',
//             'slug' => 'required|string|max:255|unique:menus,slug',
//             'status' => 'required|in:published,draft,pending',  // Ensure these values match the expected status
//         // Other validation rules
//             'nodes' => 'array',
//             'nodes.*.title' => 'required|string|max:255',
//             'nodes.*.url' => 'required|string|max:255',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         // Create the menu
//         $menu = Menu::create([
//             'name' => $request->name,
//             'slug' => $request->slug,
//             'status' => $request->status,
//         ]);

//         // Create menu nodes if provided
//         if ($request->has('nodes')) {
//             foreach ($request->nodes as $node) {
//                 MenuNode::create([
//                     'menu_id' => $menu->id,
//                     'title' => $node['title'],
//                     'url' => $node['url'],
//                     'icon_font' => $node['icon_font'] ?? null,
//                     'css_class' => $node['css_class'] ?? null,
//                     'target' => $node['target'] ?? '_self',
//                     'position' => $node['position'] ?? 0,
//                 ]);
//             }
//         }

//         return response()->json([
//             'success' => true,
//             'message' => 'Menu created successfully',
//             'data' => $menu,
//         ], 201);
//     }

//     /**
//      * Update an existing menu.
//      */
//     public function update(Request $request, $id)
//     {
//         $menu = Menu::find($id);

//         if (!$menu) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Menu not found',
//             ], 404);
//         }

//         $validator = Validator::make($request->all(), [
//             'name' => 'required|string|max:255',
//             'slug' => 'required|string|max:255|unique:menus,slug,' . $id,
//             'status' => 'required|string|in:active,inactive',
//             'nodes' => 'array',
//             'nodes.*.title' => 'required|string|max:255',
//             'nodes.*.url' => 'required|string|max:255',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'errors' => $validator->errors(),
//             ], 422);
//         }

//         // Update the menu
//         $menu->update([
//             'name' => $request->name,
//             'slug' => $request->slug,
//             'status' => $request->status,
//         ]);

//         // Update or create menu nodes
//         if ($request->has('nodes')) {
//             foreach ($request->nodes as $nodeData) {
//                 $node = MenuNode::updateOrCreate(
//                     ['menu_id' => $menu->id, 'position' => $nodeData['position']],
//                     [
//                         'title' => $nodeData['title'],
//                         'url' => $nodeData['url'],
//                         'icon_font' => $nodeData['icon_font'] ?? null,
//                         'css_class' => $nodeData['css_class'] ?? null,
//                         'target' => $nodeData['target'] ?? '_self',
//                     ]
//                 );
//             }
//         }

//         return response()->json([
//             'success' => true,
//             'message' => 'Menu updated successfully',
//             'data' => $menu,
//         ]);
//     }

//     /**
//      * Delete a menu.
//      */
//     public function destroy($id)
//     {
//         $menu = Menu::find($id);

//         if (!$menu) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Menu not found',
//             ], 404);
//         }

//         // Delete the menu and its nodes
//         $menu->nodes()->delete();
//         $menu->delete();

//         return response()->json([
//             'success' => true,
//             'message' => 'Menu deleted successfully',
//         ]);
//     }
// }




namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Menu\Models\Menu;
use Botble\Menu\Models\MenuNode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    /**
     * Get all menus with their nodes.
     */
    public function index(Request $request)
    {
        // Fetch all menus with their nodes
        $menus = Menu::with('menuNodes')->get();
        
        return response()->json($menus);
    }

    /**
     * Get a specific menu by ID.
     */
    public function show($id)
    {
        $menu = Menu::with('menuNodes')->find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }

    /**
     * Create a new menu.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:menus,slug',
            'status' => 'required|in:published,draft,pending',
            'nodes' => 'array',
            'nodes.*.title' => 'required|string|max:255',
            'nodes.*.url' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create the menu
        $menu = Menu::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'status' => $request->status,
        ]);

        // Create menu nodes if provided
        if ($request->has('nodes')) {
            foreach ($request->nodes as $node) {
                MenuNode::create([
                    'menu_id' => $menu->id,
                    'title' => $node['title'],
                    'url' => $node['url'],
                    'icon_font' => $node['icon_font'] ?? null,
                    'css_class' => $node['css_class'] ?? null,
                    'target' => $node['target'] ?? '_self',
                    'position' => $node['position'] ?? 0,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully',
            'data' => $menu,
        ], 201);
    }

    /**
     * Update an existing menu.
     */
    public function update(Request $request, $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:menus,slug,' . $id,
            'status' => 'required|string|in:active,inactive',
            'nodes' => 'array',
            'nodes.*.title' => 'required|string|max:255',
            'nodes.*.url' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update the menu
        $menu->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'status' => $request->status,
        ]);

        // Update or create menu nodes
        if ($request->has('nodes')) {
            foreach ($request->nodes as $nodeData) {
                MenuNode::updateOrCreate(
                    ['menu_id' => $menu->id, 'position' => $nodeData['position']],
                    [
                        'title' => $nodeData['title'],
                        'url' => $nodeData['url'],
                        'icon_font' => $nodeData['icon_font'] ?? null,
                        'css_class' => $nodeData['css_class'] ?? null,
                        'target' => $nodeData['target'] ?? '_self',
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully',
            'data' => $menu,
        ]);
    }

    /**
     * Delete a menu.
     */
    public function destroy($id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }

        // Delete the menu and its nodes
        $menu->menuNodes()->delete(); // Ensure this matches your relationship method
        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully',
        ]);
    }
}
