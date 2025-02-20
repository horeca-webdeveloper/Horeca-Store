<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Specification;
use Botble\Ecommerce\Models\CategorySpecification;
use App\Models\TransactionLog;

class ImportProductSpecificationJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

	protected $header;
	protected $chunk;

	public function __construct($data)
	{
		$this->header = $data['header'];
		$this->chunk = $data['chunk'];
	}

	public function handle()
	{
		$log = TransactionLog::where('identifier', $this->batch()->id)->first();
		$descArray = json_decode($log->description, true) ?? ["Errors" => ''];
		$previousSuccessCount = $descArray["Success Count"] ?? 0;
		$previousFailedCount = $descArray["Failed Count"] ?? 0;

		$errorArray = [];
		$success = 0;
		$failed = 0;

		foreach ($this->chunk as $row) {
			$rowData = [];
			$rowError = [];

			/* Validate column count */
			if (count($this->header) === count($row)) {
				$rowData = array_combine($this->header, $row);
			} else {
				$rowError[] = 'Column mismatch: The data in this row is not compatible for import.';
				$this->logError($rowError, $failed, $success, $previousSuccessCount, $previousFailedCount, $errorArray);
				$failed++;
				continue;
			}

			/* Validate Product ID */
			if (empty($rowData['ID'])) {
				$rowError[] = "The ID field is missing.";
				$this->logError($rowError, $failed, $success, $previousSuccessCount, $previousFailedCount, $errorArray);
				$failed++;
				continue;
			}

			$product = Product::find($rowData['ID']);
			if (!$product) {
				$rowError[] = 'Product not found for the given ID.';
				$this->logError($rowError, $failed, $success, $previousSuccessCount, $previousFailedCount, $errorArray);
				$failed++;
				continue;
			}

			/* Validate Required Specifications */
			$productCategorySpecifications = $product->latestCategorySpecifications->pluck('specification_name')->toArray();
			$missingSpecifications = array_diff($productCategorySpecifications, $this->header);

			if (!empty($missingSpecifications)) {
				$rowError[] = "Missing specifications: " . implode(', ', $missingSpecifications);
				$this->logError($rowError, $failed, $success, $previousSuccessCount, $previousFailedCount, $errorArray);
				$failed++;
				continue;
			}

			/* Start Transaction */
			DB::beginTransaction();

			try {
				/* Delete existing specifications */
				$product->specifications()->delete();

				/* Insert new specifications */
				foreach ($productCategorySpecifications as $spec) {
					if (!empty($spec) && isset($rowData[$spec]) && trim($rowData[$spec]) !== '') {
						Specification::create([
							'product_id' => $product->id,
							'spec_name' => $spec,
							'spec_value' => trim($rowData[$spec]),
						]);

						/* Get latest category ID */
						$latestCategoryId = $product->categories()->orderByRaw('COALESCE(ec_product_category_product.created_at, "1970-01-01 00:00:00") DESC')->orderBy('ec_product_category_product.category_id', 'DESC')->value('category_id');

						if ($latestCategoryId) {
							/* Check existing specification */
							$categorySpec = CategorySpecification::where('category_id', $latestCategoryId)->where('specification_name', $spec)->first();

							if ($categorySpec) {
								$existingValues = explode('|', $categorySpec->specification_values);
								$newValue = trim($rowData[$spec]);

								/* Append new value if it's not already present */
								if (!in_array($newValue, $existingValues)) {
									$existingValues[] = $newValue;
									$categorySpec->update([
										'specification_values' => implode('|', $existingValues),
									]);
								}
							}
						}
					}
				}


				DB::commit();
				$success++;
			} catch (Throwable $e) {
				DB::rollBack();

				$rowError[] = 'Error processing row: ' . $e->getMessage();
				$rowError[] = 'File: ' . $e->getFile();
				$rowError[] = 'Line: ' . $e->getLine();

				$this->logError($rowError, $failed, $success, $previousSuccessCount, $previousFailedCount, $errorArray);
				$failed++;
			}
		}

		/* Update Transaction Log */
		$this->updateTransactionLog($log, $success, $failed, $errorArray);
	}

	/**
	 * Log errors for a specific row.
	 */
	private function logError(&$rowError, $failed, $success, $previousSuccessCount, $previousFailedCount, &$errorArray)
	{
		$errorArray[] = [
			"Row Number" => $failed + $success + 2 + $previousSuccessCount + $previousFailedCount,
			"Error" => implode(' | ', $rowError),
		];
	}

	/**
	 * Update Transaction Log.
	 */
	private function updateTransactionLog($log, $success, $failed, $errorArray)
	{
		$descArray = json_decode($log->description, true) ?? ["Errors" => ''];
		$descArray["Success Count"] = ($descArray["Success Count"] ?? 0) + $success;
		$descArray["Failed Count"] = ($descArray["Failed Count"] ?? 0) + $failed;
		$descArray["Errors"] = array_merge($descArray["Errors"], $errorArray);

		$log->update([
			'description' => json_encode($descArray),
		]);
	}

	/**
	 * Handle a job failure.
	 */
	public function failed(Throwable $exception): void
	{
		$error = $exception->getMessage() . "\n" . $exception->getTraceAsString();
		Log::error("Product Specification Import Error: " . $error);
	}
}
