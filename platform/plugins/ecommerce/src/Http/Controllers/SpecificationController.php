<?php
namespace Botble\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Botble\Ecommerce\Models\Specification;
use Botble\Base\Http\Controllers\BaseController;

class SpecificationController extends BaseController
{
    public function showUploadForm()
    {
        //$this->authorize('ecommerce::partials.upload-specifications');
        return view('plugins/ecommerce::products.partials.upload-specifications');
    }


    public function upload(Request $request)
{
    // Validate the uploaded CSV file
    $validator = Validator::make($request->all(), [
        'csv_file' => 'required|file|mimes:csv,txt|max:2048',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Read the CSV file
    $file = $request->file('csv_file');
    $path = $file->getRealPath();
    $data = array_map('str_getcsv', file($path));

           // Skip the header row
        if (!empty($data)) {
            array_shift($data); // Remove the first row (header)
        }
    foreach ($data as $row) {
        // Ensure there are enough columns (3 expected)
        if (count($row) < 3) {
            continue; // Skip rows that do not have enough data
        }

        // Trim values from the row
        $productId = (int)trim($row[0]);
        $specName = trim($row[1]);
        $specValue = trim($row[2], '"'); // Remove double quotes from spec_value

        // Check if the spec_value contains multiple values (e.g., "Red,Green,Blue")
        if (strpos($specValue, ',') !== false) {
            $specValue = trim($specValue); // Trim whitespace
        }

        // Create or update the specification for the product
        $specification = Specification::updateOrCreate(
            [
                'product_id' => $productId,
                'spec_name' => $specName,
            ],
            [
                'spec_value' => $specValue, // Store combined values as a single string
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    return redirect()->back()->with('success', 'Specifications uploaded successfully.');
}

    // public function upload(Request $request)
    // {
    //    // $this->authorize('ecommerce::partials.upload-specifications');
    //     // Validate the uploaded CSV file
    //     $validator = Validator::make($request->all(), [
    //         'csv_file' => 'required|file|mimes:csv,txt|max:2048',
    //     ]);
    
    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }
    
    //     // Read the CSV file
    //     $file = $request->file('csv_file');
    //     $path = $file->getRealPath();
    //     $data = array_map('str_getcsv', file($path));
    
    //     // Skip the header row
    //     if (!empty($data)) {
    //         array_shift($data); // Remove the first row (header)
    //     }
    
    //     // Process each row of the CSV
    //     foreach ($data as $row) {
    //         if (count($row) < 3) {
    //             continue; // Skip rows that do not have enough data
    //         }
    
    //         // Ensure the product_id is an integer
    //         $productId = (int)trim($row[0]);
    //         $specName = trim($row[1]);
    //         $specValue = trim($row[2]);
    
    //         // Validate if the product exists (optional)
    //         // You may want to check if the product_id exists in your products table before saving
    //         // $productExists = Product::find($productId);
    //         // if (!$productExists) continue; // Skip if the product doesn't exist
    
    //         // Create a new specification instance
    //         $specification = new Specification();
    //         $specification->product_id = $productId; // First column: product_id
    //         $specification->spec_name = $specName;    // Second column: spec_name
    //         $specification->spec_value = $specValue;  // Third column: spec_value
    //         $specification->created_at = now();        // Set created_at timestamp
    //         $specification->updated_at = now();        // Set updated_at timestamp
    
    //         // Save the specification
    //         $specification->save();
    //     }
    
    //     return redirect()->back()->with('success', 'Specifications uploaded successfully.');
    // }
    
}

