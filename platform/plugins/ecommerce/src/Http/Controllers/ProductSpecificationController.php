<?php

namespace Botble\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;

use Botble\Base\Supports\Breadcrumb;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;

use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\CategorySpecification;

use Botble\Ecommerce\Models\Product;
use App\Models\TransactionLog;
use Validator;

use App\Jobs\ImportProductSpecificationJob;

use App\Repository\ExcelRepository;

class ProductSpecificationController extends BaseController
{
	/**
	 * The excel repository instance.
	 */
	protected $excel;

	/**
	 * Create a new job instance.
	 */
	public function __construct(ExcelRepository $excel)
	{
		$this->excel = $excel;
	}
	// protected function breadcrumb(): Breadcrumb
	// {
	// 	return parent::breadcrumb()
	// 	->add(trans('plugins/ecommerce::products.name'), route('products.index'));
	// }

	public function index()
	{
		// $logs = TransactionLog::all();
		$parentCategories = ProductCategory::where('parent_id', 0)->pluck('name')->all();
		$this->pageTitle(trans('plugins/ecommerce::products.export_product_specification'));
		return view('plugins/ecommerce::product-specification.export', compact('parentCategories'));
	}

	public function store(Request $request)
	{
		/* Validation rules */
		$rules = [
			'category' => 'required|string',
			'range_from' => 'required|integer|min:1',
			'range_to' => 'required|integer|gte:range_from|max:' . ($request->range_from + 2000),
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			session()->put('error', implode(', ', $validator->errors()->all()));
			return back();
		}
		$fileName = "$request->category Products $request->range_from-$request->range_to.xlsx";
		$fileName = strtolower(str_replace(' ', '_', trim($fileName)));

		/* Fetch leaf categories based on super parent category name */
		$leafCategories = ProductCategory::getLeafCategoriesBySuperParentName($request->category);
		$leafCategoryIds = $leafCategories ? $leafCategories->pluck('id')->toArray() : [];

		/* Fetch category specifications and transform */
		$catSpecs = CategorySpecification::whereIn('category_id', $leafCategoryIds)
		->get(['category_id', 'specification_type', 'specification_name', 'specification_values', 'is_fixed'])
		->groupBy('specification_name')
		->map(fn($items) => $items->sortByDesc(fn($item) => substr_count($item->specification_values, '|'))->first())
		->map(fn($item) => [
			'category_id' => $item->category_id,
			'specification_type' => $item->specification_type,
			'specification_name' => $item->specification_name,
			'specification_values' => explode('|', $item->specification_values),
			'is_fixed' => $item->is_fixed,
		])->toArray();

		/* Fetch products with range */
		$products = Product::whereHas('categories', fn($query) => $query->whereIn('category_id', $leafCategoryIds))
		->offset($request->range_from - 1)
		->limit($request->range_to - $request->range_from + 1)
		->orderBy('id', 'asc')
		->get(['id', 'sku', 'name'])
		->makeHidden(['original_price', 'front_sale_price']); /* Hide appended attributes */

		/* Prepare spreadsheet */
		$specNames = array_keys($catSpecs);
		$header = array_merge(['ID', 'SKU', 'Name'], $specNames);

		$spreadsheet = $this->excel->newSpreadsheet();
		$spreadsheet->setActiveSheetIndex(0);
		$sheet = $spreadsheet->getActiveSheet();

		/* Set headers */
		$this->excel->setHeader($sheet, $header);

		/* Populate data */
		$row = 2;
		foreach ($products as $product) {
			$existingSpecs = $product->specifications->pluck('spec_value', 'spec_name')->toArray();
			$col = 'A';

			/* Set basic product details */
			$sheet->setCellValue($col++ . $row, $product->id);
			$sheet->setCellValue($col++ . $row, $product->sku);
			$sheet->setCellValue($col++ . $row, $product->name);

			foreach ($specNames as $specName) {
				$existingVal = $existingSpecs[$specName] ?? ''; // Use null coalescing operator

				$cell = $col++ . $row;
				if (!empty($catSpecs[$specName]) && $catSpecs[$specName]['is_fixed'] != 0) {
					$this->excel->setDropdown($sheet, $cell, $catSpecs[$specName]['specification_values'], $existingVal);
				} else {
					$sheet->setCellValue($cell, $existingVal);
				}
			}
			$row++;
		}


		/* Download file */
		$this->excel->downloadFile($fileName, $spreadsheet);
	}

	public function import()
	{
		$logs = TransactionLog::where('module', 'Product Specification')->where('action', 'Import')->get();
		$this->pageTitle(trans('plugins/ecommerce::products.import_product_specification'));
		return view('plugins/ecommerce::product-specification.import', compact('logs'));
	}

	public function postImport(Request $request)
	{
		try {
			$rules = [
				'upload_file' => 'required|mimes:xlsx,xls|max:5120'
			];
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				session()->put('error', implode(', ', $validator->errors()->all()));
				return back();
			}

			$mandatoryHeaders = ['ID', 'SKU', 'Name'];

			$file = $request->file('upload_file');
			$spreadsheet = $this->excel->loadFile($file->getRealPath());
			$sheet = $spreadsheet->getActiveSheet();
			$data = $sheet->toArray();
			$header = array_shift($data);

			/* Check required header */
			$missingHeaders = array_diff($mandatoryHeaders, $header);
			if (!empty($missingHeaders)) {
				return back()->with('error', 'Missing mandatory columns: ' . implode(', ', $missingHeaders));
			}

			$totalRecords = count($data);
			if ($totalRecords == 0) {
				session()->put('error', "The uploaded CSV file does not contain any records. Please ensure the file has valid data and try again.");
				return back();
			}

			/* Create batch */
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
				$log->module = "Product Specification";
				$log->action = "Import";
				$log->identifier = $batch->id;
				$log->status = 'In-progress';
				$log->description = json_encode($descArray, JSON_UNESCAPED_UNICODE);
				$log->created_by = auth()->id() ?? null;
				$log->created_at = now();
				$log->save();
			})
			->finally(function (Batch $batch) {
				$log = TransactionLog::where('identifier', $batch->id)->first();
				TransactionLog::where('id', $log->id)->update([
					'status' => 'Completed',
				]);
			})
			->name("Product Specification Import")
			->dispatch();

			/* Chunk the data into manageable portions (e.g., 100 rows per chunk) */
			$chunkSize = 100;
			$chunks = array_chunk($data, $chunkSize);

			foreach ($chunks as $chunk) {
				$data = [
					'header' => $header,
					'chunk' => $chunk
				];
				$batch->add(new ImportProductSpecificationJob($data));
			}

			session()->put('success', 'The import process has been scheduled successfully. Please track it under import log.');
			return back();
		} catch(\Exception $exception) {
			# Exception
			session()->put('error', $exception->getMessage());
			return back();
		}
	}

	/**
	 * Display the specified resource.
	 */
	public function show($transactionLogId)
	{
		/* parent::breadcrumb()->add('Import Products', route('tools.data-synchronize.import.products.import')); */
		$log = TransactionLog::find($transactionLogId);

		return view('plugins/ecommerce::product-specification.show', compact('log'));

	}
}