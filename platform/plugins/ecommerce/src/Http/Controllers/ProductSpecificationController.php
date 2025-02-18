<?php

namespace Botble\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;

use Botble\Base\Supports\Breadcrumb;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batch;

use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\CategorySpecification;

use Botble\Ecommerce\Models\TempProduct;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\UnitOfMeasurement;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\DiscountProduct;
use Botble\Ecommerce\Models\TempProductComment;
use Botble\Ecommerce\Models\ProductTypes;
use Botble\Marketplace\Models\Store;
use App\Models\TransactionLog;
use DB, Carbon\Carbon, Validator;

use App\Jobs\ImportProductJob;

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
		return view('plugins/ecommerce::product-specification.index', compact('parentCategories'));
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
			$col = 'A';
			$sheet->setCellValue($col++ . $row, $product->id);
			$sheet->setCellValue($col++ . $row, $product->sku);
			$sheet->setCellValue($col++ . $row, $product->name);

			foreach ($specNames as $specName) {
				$cell = $col++ . $row;
				if ($catSpecs[$specName]['is_fixed'] != 0) {
					$this->excel->setDropdown($sheet, $cell, $catSpecs[$specName]['specification_values']);
				} else {
					$sheet->setCellValue($cell, "");
				}
			}
			$row++;
		}

		/* Download file */
		$this->excel->downloadFile('abc.xlsx', $spreadsheet);
	}
}