<?php

namespace Botble\Ecommerce\Importers;

use Botble\DataSynchronize\DataTransferObjects\ChunkImportResponse;
use Botble\DataSynchronize\Importer\Importer;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\TempProduct; // Ensure the TempProduct model is imported
use Botble\DataSynchronize\Importer\ImportColumn;

class ProductImageImporter extends Importer
{
    /**
     * Create a new instance of the ProductImageImporter.
     *
     * @return static
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * Define the columns expected in the CSV file.
     *
     * @return array
     */
    public function columns(): array
    {
        return [
            new ImportColumn('id', 'id'), // First parameter is the display name, second is the key
            new ImportColumn('Images', 'images'), // Second parameter as identifier/key for the images
        ];
    }

    /**
     * Return the URL for validating the CSV data.
     *
     * @return string
     */
    public function getValidateUrl(): string
    {
        return route('product-images.import.validate');
    }

    /**
     * Return the URL for importing the CSV data.
     *
     * @return string
     */
    public function getImportUrl(): string
    {
        return route('product-images.import.store');
    }

    /**
     * Handle the import process.
     *
     * @param array $data The data from the CSV file
     * @return int The number of rows processed
     */
    public function handle(array $data): int
    {
        $importedCount = 0;

        foreach ($data as $row) {
            $productId = (string) $row['id'];

            // Check if 'images' key exists and is not empty
            if (!isset($row['images']) || empty($row['images'])) {
                continue;
            }

            // Split images into an array (assuming comma-separated values)
            $images = explode(',', $row['images']);

            // Check if the product exists in the ec_products table
            $product = Product::find($productId);

            if ($product) {
                // Save images to temp_products table with status pending
                TempProduct::create([
                    'product_id' => $productId,
                    'images' => json_encode(array_map('trim', $images)),
                    'status' => 'pending', // Set status to pending
                ]);

                $importedCount++;
            }
        }

        return $importedCount; // Return the number of imported rows
    }
}
