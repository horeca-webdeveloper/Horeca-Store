<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\DataSynchronize\Http\Controllers\ImportController;
use Botble\DataSynchronize\Importer\Importer;
use Botble\Ecommerce\Importers\ProductImageImporter;
use Illuminate\Http\Request;

class ImportProductImageController extends ImportController
{
    public function index()
    {
        $this->pageTitle(trans('plugins/ecommerce::partials.import-images.name'));

        Assets::addScriptsDirectly('vendor/core/plugins/ecommerce/js/product-bulk-editable-table.js');

        return view('plugins/ecommerce::products.partials.import-images'); // Ensure this view exists
    }

    protected function getImporter(): Importer
    {
        return ProductImageImporter::make(); // Return an instance of the ProductImageImporter
    }

    public function validateCsv(Request $request)
    {
        // Validate the uploaded CSV file
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        return response()->json([
            'success' => true,
            'message' => __('CSV file validated successfully.'),
        ]);
    }


    public function store(Request $request)
    {
        // Validate the CSV file
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);
    
        // Get the uploaded file
        $csvFile = $request->file('csv_file');
    
        // Check if the file is uploaded successfully
        if (!$csvFile) {
            return response()->json(['message' => 'No file uploaded.']);
        }
    
        // Open the CSV file for reading directly
        try {
            $fileHandle = fopen($csvFile->getRealPath(), 'r');
    
            if ($fileHandle === false) {
                return response()->json(['message' => 'Failed to open the file.']);
            }
    
            $csvData = [];
    
            // Skip the header row if it exists
            $header = fgetcsv($fileHandle); // Assumes first row is header
    
            // Read each row of the CSV
            while (($row = fgetcsv($fileHandle, 1000, ',')) !== false) {
                $csvData[] = array_combine($header, $row); // Combine header with row data
            }
    
            fclose($fileHandle); // Close the file after reading
    
            // Log the parsed CSV data
            \Log::info('CSV Data: ', $csvData);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to process the CSV file. Error: ' . $e->getMessage()]);
        }
    
        // Check if csvData is populated
        if (empty($csvData)) {
            return response()->json(['message' => 'No valid data found in the CSV file.']);
        }
    
        // Process the CSV data
        try {
            $importer = $this->getImporter(); // Get your importer instance
            $importedCount = $importer->handle($csvData); // Pass the CSV data to the importer's handle method
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to import images. Error: ' . $e->getMessage()]);
        }
    
        return redirect()->back()->with('success', 'Added ' . $importedCount . ' images successfully.');

    }
}
