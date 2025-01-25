<?php

// namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Models\EcCustomer;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Auth; // Import Auth facade

// class CustomerController extends Controller
// {
//     public function register(Request $request)
//     {
//         // Validate request data
//         $validator = Validator::make($request->all(), [
//             'name' => 'required|string|max:255',
//             'email' => 'required|string|email|max:255|unique:ec_customers',
//             'password' => 'required|string|min:8',
//             'is_vendor' => 'required|boolean',
//             'dob' => 'nullable|date',
//             'phone' => 'nullable|string|max:20',
//             'avatar' => 'nullable|string|max:255',
//         ]);
    
//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 422);
//         }
    
//         try {
//             // Create a new customer/vendor
//             $customer = new EcCustomer([
//                 'name' => $request->name,
//                 'email' => $request->email,
//                 'password' => Hash::make($request->password),
//                 'is_vendor' => $request->is_vendor,
//                 'status' => $request->is_vendor ? 'pending' : 'activated',
//                 'dob' => $request->dob,
//                 'phone' => $request->phone,
//                 'avatar' => $request->avatar,
//                 'email_verify_token' => \Str::random(32), // assuming you have an email verification process
//             ]);
    
//             $customer->save();
    
//             return response()->json(['message' => 'User registered successfully!', 'user' => $customer], 201);
//         } catch (\Exception $e) {
//             \Log::error('Error registering user: ' . $e->getMessage());
//             return response()->json(['error' => 'Failed to register user'], 500);
//         }
//     }
    
    
//     public function index()
//     {
//         try {
//             // Fetch all customers with selected fields
//             $customers = EcCustomer::select('id', 'name', 'email', 'avatar', 'dob', 'phone', 'status', 'is_vendor', 'vendor_verified_at')
//                                     ->get();
    
//             return response()->json($customers, 200);
//         } catch (\Exception $e) {
//             \Log::error('Error fetching customers: ' . $e->getMessage());
//             return response()->json(['error' => 'Failed to fetch customers'], 500);
//         }
//     }
//  // App\Http\Controllers\API\CustomerController.php
// public function getProfile(Request $request)
// {
//     // Get the authenticated user
//     $user = $request->user(); // or auth()->user();

//     // Return the authenticated user's profile data
//     return response()->json(['user' => $user], 200);
// }



// // App\Http\Controllers\API\CustomerController.php
// public function updateProfile(Request $request)
// {
//     // Get the authenticated user
//     $user = $request->user(); // or auth()->user();

//     // Check if the user is authenticated
//     if (!$user) {
//         return response()->json(['error' => 'Unauthorized'], 401);
//     }

//     // Validate the incoming request
//     $validator = Validator::make($request->all(), [
//         'name' => 'sometimes|string|max:255',
//         'email' => 'sometimes|string|email|max:255|unique:ec_customers,email,' . $user->id,
//         'password' => 'sometimes|string|min:8|confirmed',
//         'avatar' => 'sometimes|string|max:255',
//         'dob' => 'sometimes|date',
//         'phone' => 'sometimes|string|max:20',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // Update the user's profile
//     if ($request->has('name')) {
//         $user->name = $request->name;
//     }
//     if ($request->has('email')) {
//         $user->email = $request->email;
//     }
//     if ($request->has('password')) {
//         $user->password = Hash::make($request->password);
//     }
//     if ($request->has('avatar')) {
//         $user->avatar = $request->avatar;
//     }
//     if ($request->has('dob')) {
//         $user->dob = $request->dob;
//     }
//     if ($request->has('phone')) {
//         $user->phone = $request->phone;
//     }

//     // Save the changes
//     $user->save();

//     return response()->json(['message' => 'Profile updated successfully!', 'user' => $user], 200);
// }

// // public function login(Request $request)
// // {
// //     // Validate request data
// //     $validator = Validator::make($request->all(), [
// //         'email' => 'required|string|email|max:255',
// //         'password' => 'required|string|min:8',
// //     ]);

// //     if ($validator->fails()) {
// //         return response()->json(['errors' => $validator->errors()], 422);
// //     }

// //     // Attempt to authenticate the user
// //     if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
// //         $user = Auth::user();
// //         $token = $user->createToken('Personal Access Token')->plainTextToken;

// //         return response()->json([
// //             'token' => $token,
// //             'user' => $user
// //         ], 200);
// //     }

// //     return response()->json(['error' => 'Unauthorized'], 401);
// // }

// // public function login(Request $request)
// // {
// //     // Validate request data
// //     $validator = Validator::make($request->all(), [
// //         'email' => 'sometimes|string|email|max:255,',
// //         'password' => 'sometimes|string|min:8',
     
// //     ]);

// //     if ($validator->fails()) {
// //         return response()->json(['errors' => $validator->errors()], 422);
// //     }

// //     // Attempt to authenticate the user
// //     $user = EcCustomer::where('email', $request->email)->first();

// //     // Log for debugging
// //     \Log::info('Attempting to login user:', [
// //         'email' => $request->email,
// //         'user_found' => $user !== null,
// //         'input_password' => $request->password,
// //         'stored_password' => $user ? $user->password : 'User not found',
// //     ]);

// //     if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
// //                 $user = Auth::user();
// //                 $token = $user->createToken('Personal Access Token')->plainTextToken;
        
// //                 return response()->json([
// //                     'token' => $token,
// //                     'user' => $user
// //                 ], 200);
// //             }
        
// //             return response()->json(['error' => 'Unauthorized'], 401);
// //         }



// // }


// public function login(Request $request)
// {
//     // Validate request data
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|string|email|max:255',
//         'password' => 'required|string|min:8',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // Attempt to find the user
//     $user = EcCustomer::where('email', $request->email)->first();

//     // Log for debugging
//     \Log::info('Attempting to login user:', [
//         'email' => $request->email,
//         'user_found' => $user !== null,
//         'input_password' => $request->password,
//         'stored_password' => $user ? $user->password : 'User not found',
//     ]);

//     // Check if user exists and if the password matches
//     if ($user && Hash::check($request->password, $user->password)) {
//         $token = $user->createToken('Personal Access Token')->plainTextToken;

//         return response()->json(['token' => $token, 'user' => $user], 200);
//     }

//     return response()->json(['error' => 'Unauthorized'], 401);
// }

// public function logout(Request $request)
// {
//     // Get the authenticated user
//     $user = $request->user();

//     // Revoke the user's token
//     $user->currentAccessToken()->delete();

//     return response()->json(['message' => 'Logged out successfully'], 200);
// }

// }





namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EcCustomer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class CustomerController extends Controller
{
    public function register(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:ec_customers',
            'password' => 'required|string|min:8',
            'is_vendor' => 'required|boolean',
            'dob' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        try {
            // Create a new customer/vendor
            $customer = new EcCustomer([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_vendor' => $request->is_vendor,
                'status' => $request->is_vendor ? 'pending' : 'activated',
                'dob' => $request->dob,
                'phone' => $request->phone,
                'avatar' => $request->avatar,
                'email_verify_token' => \Str::random(32), // assuming you have an email verification process
            ]);
    
            $customer->save();
    
            return response()->json(['message' => 'User registered successfully!', 'user' => $customer], 201);
        } catch (\Exception $e) {
            \Log::error('Error registering user: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to register user'], 500);
        }
    }
    
    
    public function index()
    {
        try {
            // Fetch all customers with selected fields
            $customers = EcCustomer::select('id', 'name', 'email', 'avatar', 'dob', 'phone', 'status', 'is_vendor', 'vendor_verified_at')
                                    ->get();
    
            return response()->json($customers, 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching customers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch customers'], 500);
        }
    }
 // App\Http\Controllers\API\CustomerController.php
public function getProfile(Request $request)
{
    // Get the authenticated user
    $user = $request->user(); // or auth()->user();

    // Return the authenticated user's profile data
    return response()->json(['user' => $user], 200);
}



// App\Http\Controllers\API\CustomerController.php
public function updateProfile(Request $request)
{
    // Get the authenticated user
    $user = $request->user(); // or auth()->user();

    // Check if the user is authenticated
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|string|email|max:255|unique:ec_customers,email,' . $user->id,
        'password' => 'sometimes|string|min:8|confirmed',
        'avatar' => 'sometimes|string|max:255',
        'dob' => 'sometimes|date',
        'phone' => 'sometimes|string|max:20',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Update the user's profile
    if ($request->has('name')) {
        $user->name = $request->name;
    }
    if ($request->has('email')) {
        $user->email = $request->email;
    }
    if ($request->has('password')) {
        $user->password = Hash::make($request->password);
    }
    if ($request->has('avatar')) {
        $user->avatar = $request->avatar;
    }
    if ($request->has('dob')) {
        $user->dob = $request->dob;
    }
    if ($request->has('phone')) {
        $user->phone = $request->phone;
    }

    // Save the changes
    $user->save();

    return response()->json(['message' => 'Profile updated successfully!', 'user' => $user], 200);
}

// public function login(Request $request)
// {
//     // Validate request data
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|string|email|max:255',
//         'password' => 'required|string|min:8',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // Attempt to authenticate the user
//     if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
//         $user = Auth::user();
//         $token = $user->createToken('Personal Access Token')->plainTextToken;

//         return response()->json([
//             'token' => $token,
//             'user' => $user
//         ], 200);
//     }

//     return response()->json(['error' => 'Unauthorized'], 401);
// }

// public function login(Request $request)
// {
//     // Validate request data
//     $validator = Validator::make($request->all(), [
//         'email' => 'sometimes|string|email|max:255,',
//         'password' => 'sometimes|string|min:8',
     
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // Attempt to authenticate the user
//     $user = EcCustomer::where('email', $request->email)->first();

//     // Log for debugging
//     \Log::info('Attempting to login user:', [
//         'email' => $request->email,
//         'user_found' => $user !== null,
//         'input_password' => $request->password,
//         'stored_password' => $user ? $user->password : 'User not found',
//     ]);

//     if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
//                 $user = Auth::user();
//                 $token = $user->createToken('Personal Access Token')->plainTextToken;
        
//                 return response()->json([
//                     'token' => $token,
//                     'user' => $user
//                 ], 200);
//             }
        
//             return response()->json(['error' => 'Unauthorized'], 401);
//         }



// }


// public function login(Request $request)
// {
//     // Validate request data
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|string|email|max:255',
//         'password' => 'required|string|min:8',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     // Attempt to find the user
//     $user = EcCustomer::where('email', $request->email)->first();

//     // Log for debugging
//     \Log::info('Attempting to login user:', [
//         'email' => $request->email,
//         'user_found' => $user !== null,
//         'input_password' => $request->password,
//         'stored_password' => $user ? $user->password : 'User not found',
//     ]);

//     // Check if user exists and if the password matches
//     if ($user && Hash::check($request->password, $user->password)) {
//         $token = $user->createToken('Personal Access Token')->plainTextToken;

//         return response()->json(['token' => $token, 'user' => $user], 200);
//     }

//     return response()->json(['error' => 'Unauthorized'], 401);
// }


public function login(Request $request)
{
    // Validate request data
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255',
        'password' => 'required|string|min:8',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Attempt to find the user
    $user = EcCustomer::where('email', $request->email)->first();

    // Log for debugging
    \Log::info('Attempting to login user:', [
        'email' => $request->email,
        'user_found' => $user !== null,
        'input_password' => $request->password,
        'stored_password' => $user ? $user->password : 'User not found',
    ]);

    // Check if user exists and if the password matches
    if ($user && Hash::check($request->password, $user->password)) {
        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 200);
    }

    return response()->json(['error' => 'Unauthorized'], 401);
}

public function logout(Request $request)
{
    // Get the authenticated user
    $user = $request->user();

    // Revoke the user's token
    $user->currentAccessToken()->delete();

    return response()->json(['message' => 'Logged out successfully'], 200);
}

}