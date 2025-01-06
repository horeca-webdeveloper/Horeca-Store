<?php

namespace Botble\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Botble\Ecommerce\Models\Product;
use Illuminate\Support\Facades\File; // Import for file operations

class ProductDocumentController extends BaseController
{
    /**
     * Show the upload form.
     *
     * @return \Illuminate\View\View
     */
    public function showUploadForm()
    {
        return view('plugins/ecommerce::products.partials.upload-documents');
    }

    /**
     * Upload product documents from a ZIP file.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocuments(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'folder' => 'required|file|mimes:zip',
        ]);

        // Handle file upload and temporary storage
        $uploadedFile = $request->file('folder');
        $zipFileName = $uploadedFile->getClientOriginalName();
        $tempDirectory = storage_path('app/temp');
        $zipFilePath = $tempDirectory . '/' . $zipFileName;

        // Ensure the temp directory exists
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0777, true);
            \Log::info("Created temp directory: {$tempDirectory}");
        }

        // Move the uploaded file to the temp directory
        $uploadedFile->move($tempDirectory, $zipFileName);
        \Log::info("Uploaded ZIP file path: {$zipFilePath}");

        // Verify the ZIP file exists and is accessible
        if (!file_exists($zipFilePath)) {
            \Log::error("ZIP file does not exist at: {$zipFilePath}");
            return response()->json(['error' => 'ZIP file does not exist'], 400);
        }

        // Define the extraction directory
        $extractPath = $tempDirectory . '/extracted';

        // Ensure the extraction directory exists
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0777, true);
            \Log::info("Created extraction directory: {$extractPath}");
        } else {
            \Log::info("Extraction directory already exists: {$extractPath}");
        }

        // Attempt to open and extract the ZIP file
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath) === true) {
            // Log the number of files inside the ZIP archive for debugging
            $numFiles = $zip->numFiles;
            \Log::info("ZIP file contains {$numFiles} files");

            // Extract files to the specified directory
            $result = $zip->extractTo($extractPath);
            $zip->close();

            if ($result) {
                \Log::info("ZIP file extracted successfully to: {$extractPath}");
            } else {
                \Log::error("Failed to extract ZIP file to: {$extractPath}");
                return response()->json(['error' => 'Failed to extract ZIP file'], 400);
            }
        } else {
            \Log::error("Failed to open ZIP file: {$zipFilePath}");
            return response()->json(['error' => 'Failed to open ZIP file'], 400);
        }

        // Check the contents of the extracted folder
        $directories = scandir($extractPath);
        \Log::info("Extracted directories: " . implode(", ", $directories));

        foreach ($directories as $directory) {
            if ($directory === '.' || $directory === '..') {
                continue;
            }

            $skuId = $directory; // Folder name as SKU
            $product = Product::where('sku', $skuId)->first(); // Check if SKU exists in database

            if (!$product) {
                \Log::error("Product with SKU {$skuId} not found. Skipping folder.");
                continue; // Skip if product not found
            }

            $documents = [];
            $productFiles = scandir($extractPath . '/' . $skuId);
            \Log::info("Files for SKU {$skuId}: " . implode(", ", $productFiles));

            foreach ($productFiles as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $sourcePath = $extractPath . '/' . $skuId . '/' . $file;
                $destinationPath = 'products/documents/' . uniqid() . '_' . $file;

                // Store the file in the storage
                Storage::put($destinationPath, file_get_contents($sourcePath));

                $documents[] = [
                    'title' => pathinfo($file, PATHINFO_FILENAME),
                    'path' => $destinationPath,
                ];

                \Log::info("Stored document for SKU {$skuId}: {$file}");
            }

            // Update product with document paths
            $product->update(['documents' => json_encode($documents)]);
        }

        // Clean up temporary files
        \File::deleteDirectory($extractPath);
        unlink($zipFilePath);

        return response()->json(['message' => 'Documents uploaded successfully']);
    }
}


