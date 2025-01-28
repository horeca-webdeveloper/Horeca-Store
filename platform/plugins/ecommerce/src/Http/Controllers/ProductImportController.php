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

			$productFileFormatArray = [
				'Id' => 'id',
				'URL' => 'url',
				'Name' => 'name',
				'Content' => 'content',
				'Description' => 'description',
				'Warranty Information' => 'warrantyInformation',
				'SKU' => 'sku',
				'Brand' => 'brand',
				'Vendor' => 'vendor',
				'Product Types' => 'productTypes',
				'Categories' => 'category',
				'Tags' => 'tags',
				'Stock Status' => 'stockStatus',
				'With Storehouse Management' => 'withStorehouseManagement',
				'Quantity' => 'quantity',
				'Cost Per Item' => 'costPerItem',
				'Unit of Measurement' => 'unitOfMeasurement',
				'Price' => 'price',
				'Sale Price' => 'salePrice',
				'Start Date Sale Price' => 'startDateSalePrice',
				'End Date Sale Price' => 'endDateSalePrice',
				'Minimum Order Quantity' => 'minimumOrderQuantity',
				'Box Quantity' => 'boxQuantity',
				'Delivery Days' => 'deliveryDays',
				'Variant Requires Shipping' => 'variantRequiresShipping',
				'Images' => 'images',
				'Upload Video' => 'uploadVideo',
				'Seo Title' => 'seoTitle',
				'Seo Description' => 'seoDescription',
				'Barcode (ISBN, UPC, GTIN, etc.)' => 'barcode',
				'Refund Policy' => 'refundPolicy',
				'Status' => 'status',
				'Google Shopping Category' => 'googleShoppingCategory',
				'Google Shopping Mpn' => 'googleShoppingMpn',
				'Is Featured' => 'isFeatured',
				'Weight Option' => 'weightOption',
				'Weight' => 'weight',
				'Dimension Option' => 'dimensionOption',
				'Length' => 'length',
				'Width' => 'width',
				'Height' => 'height',
				'Depth' => 'depth',
				'Shipping Weight Option' => 'shippingWeightOption',
				'Shipping Weight' => 'shippingWeight',
				'Shipping Dimension Option' => 'shippingDimensionOption',
				'Shipping Width' => 'shippingWidth',
				'Shipping Depth' => 'shippingDepth',
				'Shipping Height' => 'shippingHeight',
				'Shipping Length' => 'shippingLength',
				'Frequently Bought Together' => 'frequentlyBoughtTogether',
				'Compare Products' => 'compareProducts',
				'Variant 1 Title' => 'variant1Title',
				'Variant 1 Value' => 'variant1Value',
				'Variant 1 Products' => 'variant1Products',
				'Variant 2 Title' => 'variant2Title',
				'Variant 2 Value' => 'variant2Value',
				'Variant 2 Products' => 'variant2Products',
				'Variant 3 Title' => 'variant3Title',
				'Variant 3 Value' => 'variant3Value',
				'Variant 3 Products' => 'variant3Products',
				'Variant Color Title' => 'variantColorTitle',
				'Variant Color Value' => 'variantColorValue',
				'Variant Color Products' => 'variantColorProducts',
				'Buying Quantity1' => 'buyingQuantity1',
				'Discount1' => 'discount1',
				'Start Date1' => 'startDate1',
				'End Date1' => 'endDate1',
				'Buying Quantity2' => 'buyingQuantity2',
				'Discount2' => 'discount2',
				'Start Date2' => 'startDate2',
				'End Date2' => 'endDate2',
				'Buying Quantity3' => 'buyingQuantity3',
				'Discount3' => 'discount3',
				'Start Date3' => 'startDate3',
				'End Date3' => 'endDate3',
				'Name (AR)' => 'nameAr',
				'Description (AR)' => 'descriptionAr',
				'Content (AR)' => 'contentAr',
				'Warranty Information (AR)' => 'warrantyInformationAr',
			];

			$requiredRowCount = count($productFileFormatArray);

			/* Open the CSV file and read its content */
			$rowIndex = 1;
			if (($handle = fopen($fileNameWithPath, "r")) !== false) {
				while (($row = fgetcsv($handle, 0, ",", '"', "\\")) !== false) {
					/* Fix unquoted fields and escape special characters */
					$row = array_map(function ($value) {
						/* Add quotes around multiline fields */
						if (strpos($value, "\n") !== false || strpos($value, "\r") !== false) {
							$value = '"' . str_replace('"', '""', $value) . '"';
						}

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
						if (count($row) != $requiredRowCount) {
							$message = "The data in row $rowIndex is not compatible for import.";

							# To delete imported excel file
							$command = "rm -rf ".$fileNameWithPath;
							shell_exec($command);

							session()->put('error', $message);
							return back();
						}
						$data[] = $row;
					}
					$rowIndex++;
				}
				fclose($handle);
			}

			/* Remove the header row */
			$header = array_shift($data);

			$requiredHeaderArray = array_keys($productFileFormatArray);

			if ($missingColumns = array_diff($requiredHeaderArray, $header)) {
				$columns = implode(', ', array_values($missingColumns));
				$missingCount = count($missingColumns);
				$message = $missingCount > 1 ? "The uploaded file has an incorrect header. $columns columns are missing." : "The uploaded file has an incorrect header. $columns column is missing.";

				# To delete imported excel file
				$command = "rm -rf ".$fileNameWithPath;
				shell_exec($command);

				session()->put('error', $message);
				return back();
			}

			/* Get the total record count */
			$totalRecords = count($data);
			if ($totalRecords == 0) {
				# To delete imported excel file
				$command = "rm -rf ".$fileNameWithPath;
				shell_exec($command);

				session()->put('error', "The uploaded CSV file does not contain any records. Please ensure the file has valid data and try again.");
				return back();
			}

			/* Chunk the data into manageable portions (e.g., 100 rows per chunk) */
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

				// /* Delete the imported file after processing */
				// if (file_exists($fileNameWithPath)) {
				// 	unlink($fileNameWithPath);
				// }
				# To delete imported excel file
				$command = "rm -rf ".$fileNameWithPath;
				shell_exec($command);
			})
			->name("Product Import")
			->dispatch();

			/* Add jobs to the batch for processing chunks */
			foreach ($chunks as $chunk) {
				$data = [
					'productFileFormatArray' => $productFileFormatArray,
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