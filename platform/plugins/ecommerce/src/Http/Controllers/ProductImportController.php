<?php

namespace Botble\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;

use Botble\Base\Supports\Breadcrumb;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;

use Botble\Ecommerce\Models\TempProduct;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\UnitOfMeasurement;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\DiscountProduct;
use Botble\Ecommerce\Models\TempProductComment;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\ProductTypes;
use Botble\Marketplace\Models\Store;
use App\Models\TransactionLog;
use DB, Carbon\Carbon, Validator;

use App\Jobs\ImportProductJob;

class ProductImportController extends BaseController
{
	// protected function breadcrumb(): Breadcrumb
	// {
	// 	return parent::breadcrumb()
	// 	->add(trans('plugins/ecommerce::products.name'), route('products.index'));
	// }

	public function index()
	{
		$logs = TransactionLog::all();
		$this->pageTitle(trans('plugins/ecommerce::products.import_products'));
		return view('plugins/ecommerce::product-import.index', compact('logs'));
	}

	public function store(Request $request)
	{
		try {
			$rules = [
				'upload_file' => 'required|max:5120|mimes:csv,txt'
			];
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				session()->put('error', implode(', ', $validator->errors()->all()));
				return back();
			}

			# Storing file on temp location
			$file = $request->file('upload_file');
			$extension = $file->getClientOriginalExtension();
			$fileName = 'temp_'.time().'.'.$extension;
			$saveDirectory = storage_path('temp');
			if (!file_exists($saveDirectory)) {
				mkdir($saveDirectory, 0777, true);
			}
			$file->move($saveDirectory, $fileName);
			$fileNameWithPath = storage_path('temp/').$fileName;

			$data = [];

			/* Open the CSV file and read its content */
			if (($handle = fopen($fileNameWithPath, "r")) !== false) {
				while (($row = fgetcsv($handle, 0, ",", '"', "\\")) !== false) {
					/* Sanitize and encode each row to ensure UTF-8 compatibility */
					$row = array_map(function ($value) {
						/* Check if the value is UTF-8 encoded */
						if (!mb_check_encoding($value, 'UTF-8')) {
							/* Attempt to convert to UTF-8, fallback to ISO-8859-1 if detection fails */
							$value = @mb_convert_encoding($value, 'UTF-8', 'auto') ?: utf8_encode($value);
						}

						/* Remove invalid characters and trim spaces */
						$value = preg_replace('/[^\x20-\x7E\xA0-\xFF]/u', '', $value);
						return trim($value);
					}, $row);

					/* Skip blank rows */
					if (array_filter($row)) {
						$data[] = $row;
					}
				}
				fclose($handle);
			}

			/* Remove the header row */
			$header = array_shift($data);

			/* Get the total record count */
			$totalRecords = count($data);
			if ($totalRecords == 0) {
				session()->put('error', "The uploaded CSV file does not contain any records. Please ensure the file has valid data and try again.");
				return back();
			}

			/* Chunk the data into manageable portions (e.g., 500 rows per chunk) */
			$chunkSize = 100;
			$chunks = array_chunk($data, $chunkSize);

			/* Start import process */
			$batch = Bus::batch([])
			->before(function (Batch $batch) use ($totalRecords) {
				$descArray = [
					"Total Count" => $totalRecords,
					"Success Count" => 0,
					"Failed Count" => 0,
					"Errors" => []
				];
				/* Save transaction log */
				$log = new TransactionLog();
				$log->module = "Product";
				$log->action = "Import";
				$log->identifier = $batch->id;
				$log->status = 'In-progress';
				$log->description = json_encode($descArray, JSON_UNESCAPED_UNICODE);
				$log->created_by = auth()->id() ?? null;
				$log->created_at = now();
				$log->save();
			})
			->finally(function (Batch $batch) use ($fileNameWithPath) {
				$log = TransactionLog::where('identifier', $batch->id)->first();
				TransactionLog::where('id', $log->id)->update([
					'status' => 'Completed',
				]);

				/* Delete the imported file after processing */
				if (file_exists($fileNameWithPath)) {
					unlink($fileNameWithPath);
				}
			})
			->name("Product Import")
			->dispatch();

			/* Add jobs to the batch for processing chunks */
			foreach ($chunks as $chunk) {
				$data = [
					'header' => $header,
					'chunk' => $chunk,
					'userId' => auth()->id()
				];
				$batch->add(new ImportProductJob($data));
			}


			session()->put('success', 'The import process has been scheduled successfully. Please track it under import log.');
			return back();
		} catch(Exception $exception) {
			# Exception
			session()->put('error', $exception->getMessage());
			return redirect('schools')->with('error', $exception->getMessage());
		}
	}

	/**
	 * Display the specified resource.
	 */
	public function show($transactionLogId)
	{
		/* parent::breadcrumb()->add('Import Products', route('tools.data-synchronize.import.products.import')); */
		$log = TransactionLog::find($transactionLogId);

		return view('plugins/ecommerce::product-import.show', compact('log'));

	}
}