<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Botble\Ecommerce\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index()
    {
        Log::info('Entered index method in AddressController.');
        $userId = Auth::id(); // Get the authenticated user's ID

        if (!$userId) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

    
        $addresses = Address::where('customer_id', $userId)->get();
    
        Log::info('Fetched addresses: ', ['addresses' => $addresses]);
    
        return response()->json([
            'message' => 'Fetched addresses',
             'success'=>true,
            'data' => $addresses
        ]);
    }

    /**
     * Add a new address.
     */
   public function store(Request $request)
    {
        Log::info('Entered store method for AddressController.');

        $userId = Auth::id(); // Get the authenticated user's ID
        if (!$userId) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        try {
            // Validate incoming request data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'country' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'zip_code' => 'required|string|max:10',
            ]);

            Log::info('Validated data: ', $validatedData);

            $addressCount = Address::where('customer_id', $userId)->count();

            // If it's the first address, make it the default
            $isDefault = $addressCount === 0 ? 1 : 0;

            $addressData = array_merge($validatedData, [
                'customer_id' => $userId,
                'is_default' => $isDefault,
            ]);

            Log::info('Merging data for creation: ', $addressData);

            $address = Address::create($addressData);

            Log::info('Address successfully created: ', ['address' => $address]);

            return response()->json([
                'message' => 'Address created successfully.',
                'success' => true,
                'data' => $address,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating address: ', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    
     public function updateDefaultAddress(Request $request)
    {
        Log::info('Entered updateDefaultAddress method.');

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        $validatedData = $request->validate([
            'address_id' => 'required|integer|exists:ec_customer_addresses,id'
        ]);

        try {
            // Remove current default
            Address::where('customer_id', $userId)->where('is_default', 1)->update(['is_default' => 0]);

            // Set the requested address as default
            $updated = Address::where('id', $validatedData['address_id'])->update(['is_default' => 1]);

            if ($updated) {
                Log::info('Default address updated successfully.');
                return response()->json([
                    'message' => 'Default address updated successfully.',
                    'success' => true,
                ]);
            }

            Log::error('Failed to update the default address.');
            return response()->json([
                'error' => 'Failed to set default address.',
                'success' => false,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in updateDefaultAddress: ', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    
    

    /**
     * Update an existing address by ID.
     */
    public function update(Request $request, $id)
    {
        Log::info('Entered update method with ID: ', ['id' => $id]);
    
        $address = Address::where('id', $id)->first();
    
        if (!$address) {
            Log::warning('Address not found.', ['id' => $id]);
            return response()->json(['error' => 'Address not found'], 404);
        }
    
        $address->update($request->all());
        Log::info('Address updated: ', ['address' => $address]);
    
        return response()->json(
            ['message' => 'Address updated successfully.', 
              'success'=>true,
            'data' => $address]);
    }
    

    /**
     * Delete an address by ID.
     */
    // public function destroy($id)
    // {
    //     Log::info('Entered destroy method with ID: ', ['id' => $id]);
    
    //     $address = Address::where('id', $id)->first();
    
    //     if (!$address) {
    //         Log::warning('Address not found for deletion.', ['id' => $id]);
    //         return response()->json(['error' => 'Address not found'], 404);
    //     }
    
    //     $address->delete();
    //     Log::info('Address deleted: ', ['id' => $id]);
    
    //     return response()->json(['message' => 'Address deleted successfully',
    //       'success'=>true,]);
    // }
    public function destroy($id)
    {
        Log::info('Entered destroy method with ID: ', ['id' => $id]);
    
        // Attempt to delete the address directly
        $deleted = Address::where('id', $id)->delete();
        
        if ($deleted) {
            Log::info('Address deleted successfully.', ['id' => $id]);
            return response()->json([
                'message' => 'Address deleted successfully',
                'success' => true,
            ], 200);
        }
    
        Log::info('No address found to delete.', ['id' => $id]);
        return response()->json([
            'message' => 'Address deleted successfully',
            'success' => true,
        ], 200);
    }

  
}
