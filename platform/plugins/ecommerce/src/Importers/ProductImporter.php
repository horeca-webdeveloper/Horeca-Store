<?php

namespace Botble\Ecommerce\Importers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Facades\BaseHelper;
use Botble\DataSynchronize\Contracts\Importer\WithMapping;
use Botble\DataSynchronize\Importer\ImportColumn;
use Botble\DataSynchronize\Importer\Importer;
use Botble\Ecommerce\Enums\ProductTypeEnum;
use Botble\Ecommerce\Enums\StockStatusEnum;
use Botble\Ecommerce\Imports\ImportTrait;
use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\DiscountProduct;
use Botble\Ecommerce\Models\ProductAttributeSet;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Models\ProductCollection;
use Botble\Ecommerce\Models\ProductLabel;
use Botble\Ecommerce\Models\ProductVariation;
use Botble\Ecommerce\Models\Tax;
use Botble\Ecommerce\Services\Products\StoreProductService;
use Botble\Ecommerce\Services\StoreProductTagService;
use Botble\Ecommerce\Services\StoreProductTypesService;
use Botble\Ecommerce\Models\ProductTypes;
use Botble\Language\Facades\Language;
use Botble\Media\Facades\RvMedia;
use Botble\Slug\Facades\SlugHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Rules\JsonArrayRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage; // Add this line to import the Storage facade
use Illuminate\Http\UploadedFile; // Add this import for UploadedFile

use Intervention\Image\ImageManagerStatic as Image;



class ProductImporter extends Importer implements WithMapping
{
    use ImportTrait;

    protected Collection $brands;

    protected Collection $categories;

    protected Collection $tags;
    protected Collection $producttypes;

    protected Collection $taxes;

    protected Collection $stores;

    protected Collection $labels;

    protected Collection $productCollections;

    protected Collection $productLabels;

    protected Collection|Model $productAttributeSets;

    protected string $importType = 'all';

    protected Collection $allTaxes;

    protected Collection $barcodes;

    protected array $supportedLocales = [];

    protected string $defaultLanguage;


    public function __construct()
    {
        $this->categories = collect();
        $this->brands = collect();
        $this->taxes = collect();
        $this->labels = collect();
        $this->productCollections = collect();
        $this->productLabels = collect();
        $this->productAttributeSets = ProductAttributeSet::query()
            ->with('attributes')
            ->get();
        $this->allTaxes = Tax::query()->get();
        $this->barcodes = collect();

        if (defined('LANGUAGE_MODULE_SCREEN_NAME')) {
            $this->defaultLanguage = Language::getDefaultLanguage(['lang_code'])->lang_code;
            $this->supportedLocales = Language::getSupportedLocales();
        }
    }

    public function setImportType(string $importType): self
    {
        $this->importType = $importType;

        return $this;
    }

    public function getImportType(): string
    {
        return $this->importType;
    }

    public function label(): string
    {
        return trans('plugins/ecommerce::products.name');
    }

    public function chunkSize(): int
    {
        return 10;
    }

    public function columns(): array
    {
        if(auth()->user() && \DB::table('role_users')->where('user_id', auth()->user()->id)->where('role_id', 22)->exists() )
        {
            $columns = [
                ImportColumn::make('id')
                    ->rules(['numeric']),
                ImportColumn::make('name')
                    ->rules(['required', 'string', 'max:250'], trans('plugins/ecommerce::products.import.rules.required_string_max', ['attribute' => 'Name', 'max' => 250])),
                ImportColumn::make('url')
                    ->label('URL')
                    ->rules(['nullable', 'string', 'max:250'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'URL', 'max' => 250])),
                ImportColumn::make('sku')
                    ->label('SKU')
                    ->rules(['nullable', 'string', 'max:150'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'SKU', 'max' => 150])),
                ImportColumn::make('status')
                    ->rules([Rule::in(BaseStatusEnum::values())], trans('plugins/ecommerce::products.import.rules.in', ['attribute' => 'Status', 'values' => implode(', ', BaseStatusEnum::values())])),
                ImportColumn::make('brand')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Brand'])),
                ImportColumn::make('price')
                ->label('Product Price')
                    ->rules(['numeric', 'nullable', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Price'])),
                ImportColumn::make('quantity')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Quantity', 'min' => 0, 'max' => 100000000])),
                ImportColumn::make('sale_price')
                    ->rules(['numeric', 'nullable', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Sale price'])),
                ImportColumn::make('start_date')
                    ->rules(['date', 'nullable', 'required_if:sale_type,1'], trans('plugins/ecommerce::products.import.rules.nullable_date_required_if', ['attribute' => 'Start date', 'required' => 'Sale type'])),
                ImportColumn::make('end_date')
                    ->rules(['date', 'nullable', 'after:start_date'], trans('plugins/ecommerce::products.import.rules.nullable_date_after', ['attribute' => 'End date', 'after' => 'Start date'])),
                ImportColumn::make('cost_per_item')
                ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Cost per item'])),
                ImportColumn::make('refund')
                    ->label('Refund Policy')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Refund'])),
                ImportColumn::make('delivery_days')
                    ->label('Delivery Days')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Delivery Days'])),
                ImportColumn::make('minimum_order_quantity')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Minimum order quantity'])),
                ImportColumn::make('variant_requires_shipping')
                    ->rules(['nullable', 'bool'], trans('plugins/ecommerce::products.import.rules.nullable_bool', ['attribute' => 'Variant requires shipping'])),
                    ImportColumn::make('google_shopping_mpn')
                    ->label('Google Shopping Mpn')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Google Shopping Mpn'])),
                ImportColumn::make('box_quantity')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Box quantity'])),



                ImportColumn::make('buying_quantity_1')
                    ->label('Buying Quantity1')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Buying Quantity1'])),
                ImportColumn::make('discount_1')
                    ->label('Discount1')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Discount1'])),
                ImportColumn::make('start_date_1')
                    ->label('Start Date1')
                    ->rules(['nullable', 'date']),
                ImportColumn::make('end_date_1')
                    ->label('End Date1')
                    ->rules(['nullable', 'date']),

                ImportColumn::make('buying_quantity_2')
                    ->label('Buying Quantity2')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Buying Quantity2'])),
                ImportColumn::make('discount_2')
                    ->label('Discount2')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Discount2'])),
                ImportColumn::make('start_date_2')
                    ->label('Start Date2')
                    ->rules(['nullable', 'date']),
                ImportColumn::make('end_date_2')
                    ->label('End Date2')
                    ->rules(['nullable', 'date']),

                ImportColumn::make('buying_quantity_3')
                    ->label('Buying Quantity3')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Buying Quantity3'])),
                ImportColumn::make('discount_3')
                    ->label('Discount3')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Discount3'])),
                ImportColumn::make('start_date_3')
                    ->label('Start Date3')
                    ->rules(['nullable', 'date']),
                ImportColumn::make('end_date_3')
                    ->label('End Date3')
                    ->rules(['nullable', 'date']),

            ];
        } else {
            $columns = [
                ImportColumn::make('id')
                    ->rules(['numeric']),
                ImportColumn::make('name')
                    ->rules(['required', 'string', 'max:250'], trans('plugins/ecommerce::products.import.rules.required_string_max', ['attribute' => ' Name', 'max' => 250])),
                ImportColumn::make('description')

                     ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Description'])),
                // ImportColumn::make('slug')
                    // ->rules(['nullable', 'string', 'max:250'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'Slug', 'max' => 250])),
                ImportColumn::make('url')
                    ->label('URL')
                    ->rules(['nullable', 'string', 'max:250'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'URL', 'max' => 250])),
                ImportColumn::make('sku')
                    ->label('SKU')
                    ->rules(['nullable', 'string', 'max:150'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'SKU', 'max' => 150])),
                ImportColumn::make('categories')
                    ->rules(['nullable', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Categories'])),
                ImportColumn::make('status')
                    ->rules([Rule::in(BaseStatusEnum::values())], trans('plugins/ecommerce::products.import.rules.in', ['attribute' => 'Status', 'values' => implode(', ', BaseStatusEnum::values())])),
                ImportColumn::make('is_featured')
                    ->rules(['nullable', 'bool'], trans('plugins/ecommerce::products.import.rules.nullable_bool', ['attribute' => 'is featured'])),
                ImportColumn::make('brand')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Brand'])),
                ImportColumn::make('product_collections')
                    ->rules(['nullable', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Product collections'])),
                ImportColumn::make('labels')
                    ->rules(['nullable', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Labels'])),
                ImportColumn::make('taxes')
                    ->rules(['nullable', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Taxes'])),
                ImportColumn::make('image')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Image'])),
                ImportColumn::make('images')
                ->label('Product Images')
                    ->rules(['sometimes', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Images'])),
                ImportColumn::make('price')
                ->label('Product Price')
                    ->rules(['numeric', 'nullable', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Price'])),
                ImportColumn::make('product_attributes')
                    ->rules(['nullable', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Product attributes'])),
                ImportColumn::make('import_type')
                    ->rules([Rule::in(['product', 'variation'])], trans('plugins/ecommerce::products.import.rules.in', ['attribute' => 'Import type', 'values' => 'product, variation'])),
                ImportColumn::make('is_variation_default')
                    ->rules(['nullable', 'bool'], trans('plugins/ecommerce::products.import.rules.nullable_bool', ['attribute' => 'Is variation default'])),
                ImportColumn::make('stock_status')
                    ->rules([Rule::in(StockStatusEnum::values())], trans('plugins/ecommerce::products.import.rules.in', ['attribute' => 'Stock status', 'values' => implode(', ', StockStatusEnum::values())])),
                ImportColumn::make('with_storehouse_management')
                    ->rules(['nullable', 'bool'], trans('plugins/ecommerce::products.import.rules.nullable_bool', ['attribute' => 'With storehouse management'])),
                ImportColumn::make('quantity')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Quantity', 'min' => 0, 'max' => 100000000])),
                ImportColumn::make('sale_price')
                    ->rules(['numeric', 'nullable', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Sale price'])),
                ImportColumn::make('start_date')
                    ->rules(['date', 'nullable', 'required_if:sale_type,1'], trans('plugins/ecommerce::products.import.rules.nullable_date_required_if', ['attribute' => 'Start date', 'required' => 'Sale type'])),
                ImportColumn::make('end_date')
                    ->rules(['date', 'nullable', 'after:start_date'], trans('plugins/ecommerce::products.import.rules.nullable_date_after', ['attribute' => 'End date', 'after' => 'Start date'])),
                ImportColumn::make('weight')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Weight', 'min' => 0, 'max' => 100000000])),
                ImportColumn::make('length')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Length', 'min' => 0, 'max' => 100000000])),
                ImportColumn::make('width')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'width', 'min' => 0, 'max' => 100000000])),
                ImportColumn::make('height')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Height', 'min' => 0, 'max' => 100000000])),
                    ImportColumn::make('depth')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Height', 'min' => 0, 'max' => 100000000])),
                    ImportColumn::make('shipping_depth')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Height', 'min' => 0, 'max' => 100000000])),
                    ImportColumn::make('shipping_width')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Height', 'min' => 0, 'max' => 100000000])),
                    ImportColumn::make('shipping_length')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Height', 'min' => 0, 'max' => 100000000])),
                    ImportColumn::make('shipping_height')
                    ->rules(['numeric', 'nullable', 'min:0', 'max:100000000'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min_max', ['attribute' => 'Height', 'min' => 0, 'max' => 100000000])),


                    ImportColumn::make('cost_per_item')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Cost per item'])),
                ImportColumn::make('barcode')
                    ->rules(['nullable', 'string', 'unique:ec_products,barcode', 'max:50'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'Barcode', 'max' => 50])),
                ImportColumn::make('content')
                ->label('Features')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Features'])),
                ImportColumn::make('tags')
                    ->rules(['nullable', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Tags'])),
                    ImportColumn::make('producttypes')
                    ->rules(['nullable', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Product Types'])),
                // ImportColumn::make('product_type')
                //     ->rules([Rule::in(ProductTypeEnum::values())], trans('plugins/ecommerce::products.import.rules.in', ['attribute' => 'Product type', 'values' => implode(', ', ProductTypeEnum::values())])),
                ImportColumn::make('auto_generate_sku')
                    ->rules(['nullable', 'bool'], trans('plugins/ecommerce::products.import.rules.nullable_bool', ['attribute' => 'Auto generate SKU'])),
                // ImportColumn::make('generate_license_code')
                //     ->rules(['nullable', 'bool'], trans('plugins/ecommerce::products.import.rules.nullable_bool', ['attribute' => 'Generate license code'])),
                ImportColumn::make('minimum_order_quantity')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Minimum order quantity'])),
                ImportColumn::make('maximum_order_quantity')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Maximum order quantity'])),

                //         ImportColumn::make('handle')
                // ->rules(['nullable', 'string', 'max:250'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'Handle', 'max' => 250])),
                ImportColumn::make('variant_grams')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Variant grams', 'min' => 0])),
                ImportColumn::make('variant_inventory_tracker')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant inventory tracker'])),
                ImportColumn::make('variant_inventory_quantity')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Variant inventory quantity', 'min' => 0])),
                ImportColumn::make('variant_inventory_policy')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant inventory policy'])),
                ImportColumn::make('variant_fulfillment_service')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant fulfillment service'])),
                ImportColumn::make('variant_requires_shipping')
                    ->rules(['nullable', 'bool'], trans('plugins/ecommerce::products.import.rules.nullable_bool', ['attribute' => 'Variant requires shipping'])),
                ImportColumn::make('variant_barcode')
                    ->rules(['nullable', 'string', 'max:50'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'Variant barcode', 'max' => 50])),
                ImportColumn::make('gift_card')
                    ->rules(['nullable', 'bool'], trans('plugins/ecommerce::products.import.rules.nullable_bool', ['attribute' => 'Gift card'])),
                ImportColumn::make('seo_title')
                    ->rules(['nullable', 'string', 'max:250'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'SEO title', 'max' => 250])),
                ImportColumn::make('seo_description')
                    ->rules(['nullable', 'string', 'max:500'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'SEO description', 'max' => 500])),
                ImportColumn::make('google_shopping_category')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Google Shopping category'])),
                    ImportColumn::make('video_path')
                    ->label('Upload Video')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Upload Video'])),

                // ImportColumn::make('refund_policy')
                //     ->label('Refund Policy')
                //     ->rules(['nullable', 'boolean'], trans('plugins/ecommerce::products.import.rules.nullable_boolean', ['attribute' => 'Refund Policy'])),
                // ImportColumn::make('shipping_weight_option')
                //     ->label('Shipping Weight Option')
                //     ->rules(['nullable', 'in:Kg,g,pounds,oz'], trans('plugins/ecommerce::products.import.rules.nullable_enum', ['attribute' => 'Shipping Weight Option'])),
                ImportColumn::make('shipping_weight')
                    ->label('Shipping Weight')
                    ->rules(['nullable', 'numeric'], trans('plugins/ecommerce::products.import.rules.nullable_numeric', ['attribute' => 'Shipping Weight'])),
                // ImportColumn::make('shipping_dimension_option')
                //     ->label('Shipping Dimension Option')
                //     ->rules(['nullable', 'in:inch,mm,cm'], trans('plugins/ecommerce::products.import.rules.nullable_enum', ['attribute' => 'Shipping Dimension Option'])),
                ImportColumn::make('shipping_width')
                    ->label('Shipping Width')
                    ->rules(['nullable', 'numeric'], trans('plugins/ecommerce::products.import.rules.nullable_numeric', ['attribute' => 'Shipping Width'])),
                ImportColumn::make('shipping_width_id')
                    ->label('Shipping Width ID')
                    ->rules(['nullable', 'integer'], trans('plugins/ecommerce::products.import.rules.nullable_integer', ['attribute' => 'Shipping Width ID'])),
                ImportColumn::make('shipping_depth')
                    ->label('Shipping Depth')
                    ->rules(['nullable', 'numeric'], trans('plugins/ecommerce::products.import.rules.nullable_numeric', ['attribute' => 'Shipping Depth'])),
                ImportColumn::make('shipping_depth_id')
                    ->label('Shipping Depth ID')
                    ->rules(['nullable', 'integer'], trans('plugins/ecommerce::products.import.rules.nullable_integer', ['attribute' => 'Shipping Depth ID'])),
                ImportColumn::make('shipping_height')
                    ->label('Shipping Height')
                    ->rules(['nullable', 'numeric'], trans('plugins/ecommerce::products.import.rules.nullable_numeric', ['attribute' => 'Shipping Height'])),
                ImportColumn::make('shipping_height_id')
                    ->label('Shipping Height ID')
                    ->rules(['nullable', 'integer'], trans('plugins/ecommerce::products.import.rules.nullable_integer', ['attribute' => 'Shipping Height ID'])),
                ImportColumn::make('shipping_length')
                    ->label('Shipping Length')
                    ->rules(['nullable', 'numeric'], trans('plugins/ecommerce::products.import.rules.nullable_numeric', ['attribute' => 'Shipping Length'])),
                    ImportColumn::make('frequently_bought_together')
                    ->label('Frequently Bought Together')
                    ->rules(['nullable', new JsonArrayRule()], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Frequently Bought Together'])),
                //     ImportColumn::make('compare_type')
                //     ->label('Compare Type')
                //     ->rules(['nullable', 'array'], trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Compare Type'])),
                ImportColumn::make('compare_type')
                ->label('Compare Type')
                ->rules(['nullable', 'array'],trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Compare Type']) ),
                ImportColumn::make('compare_products')
                ->label('Compare Products')
                ->rules(['nullable', 'array'],trans('plugins/ecommerce::products.import.rules.nullable_array', ['attribute' => 'Compare Products']) ),

                ImportColumn::make('warranty_information')
                    ->label('Warranty information')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Warranty information'])),

                ImportColumn::make('refund')
                    ->label('Refund Policy')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Refund'])),
                ImportColumn::make('delivery_days')
                    ->label('Delivery Days')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Delivery Days'])),
                ImportColumn::make('currency_id')
                    ->label('Currency ID')
                    ->rules(['nullable', 'integer'], trans('plugins/ecommerce::products.import.rules.nullable_integer', ['attribute' => 'Currency ID'])),
                ImportColumn::make('variant_1_title')
                    ->label('Variant 1 Title')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 1 Title'])),
                ImportColumn::make('variant_1_value')
                    ->label('Variant 1 Value')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 1 Value'])),
                ImportColumn::make('variant_1_products')
                    ->label('Variant 1 Products')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 1 Products'])),
                ImportColumn::make('variant_2_title')
                    ->label('Variant 2 Title')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 2 Title'])),
                ImportColumn::make('variant_2_value')
                    ->label('Variant 2 Value')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 2 Value'])),
                ImportColumn::make('variant_2_products')
                    ->label('Variant 2 Products')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 2 Products'])),
                ImportColumn::make('variant_3_title')
                    ->label('Variant 3 Title')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 3 Title'])),
                ImportColumn::make('variant_3_value')
                    ->label('Variant 3 Value')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 3 Value'])),
                ImportColumn::make('variant_3_products')
                    ->label('Variant 3 Products')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant 3 Products'])),
                ImportColumn::make('variant_color_title')
                    ->label('Variant Color Title')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant Color Title'])),
                ImportColumn::make('variant_color_value')
                    ->label('Variant Color Value')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant Color Value'])),
                ImportColumn::make('variant_color_products')
                    ->label('Variant Color Products')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Variant Color Products'])),

                // ImportColumn::make('google_shopping_gender')
                //     ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Google Shopping gender'])),
                // ImportColumn::make('google_shopping_age_group')
                //     ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Google Shopping age group'])),
                 ImportColumn::make('google_shopping_mpn')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Google Shopping MPN'])),
                ImportColumn::make('box_quantity')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Box quantity'])),



                ImportColumn::make('buying_quantity_1')
                    ->label('Buying Quantity1')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Buying Quantity1'])),
                ImportColumn::make('discount_1')
                    ->label('Discount1')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Discount1'])),
                ImportColumn::make('start_date_1')
                    ->label('Start Date1')
                    ->rules(['nullable', 'date']),
                ImportColumn::make('end_date_1')
                    ->label('End Date1')
                    ->rules(['nullable', 'date']),

                ImportColumn::make('buying_quantity_2')
                    ->label('Buying Quantity2')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Buying Quantity2'])),
                ImportColumn::make('discount_2')
                    ->label('Discount2')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Discount2'])),
                ImportColumn::make('start_date_2')
                    ->label('Start Date2')
                    ->rules(['nullable', 'date']),
                ImportColumn::make('end_date_2')
                    ->label('End Date2')
                    ->rules(['nullable', 'date']),

                ImportColumn::make('buying_quantity_3')
                    ->label('Buying Quantity3')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Buying Quantity3'])),
                ImportColumn::make('discount_3')
                    ->label('Discount3')
                    ->rules(['nullable', 'numeric', 'min:0'], trans('plugins/ecommerce::products.import.rules.nullable_numeric_min', ['attribute' => 'Discount3'])),
                ImportColumn::make('start_date_3')
                    ->label('Start Date3')
                    ->rules(['nullable', 'date']),
                ImportColumn::make('end_date_3')
                    ->label('End Date3')
                    ->rules(['nullable', 'date']),
                // ImportColumn::make('technical_table')
                //     ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Technical table'])),
                // ImportColumn::make('technical_spec')
                //     ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Technical spec'])),

            ];
        }
        if (is_plugin_active('marketplace')) {
            $columns[] = ImportColumn::make('vendor')
                ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Vendor']));
        }

        foreach ($this->supportedLocales as $properties) {
            if ($properties['lang_code'] != $this->defaultLanguage) {
                $columns[] = ImportColumn::make("name_({$properties['lang_code']})")
                    ->label('Name (' . strtoupper($properties['lang_code']) . ')')
                    ->rules(['nullable', 'string', 'max:250'], trans('plugins/ecommerce::products.import.rules.nullable_string_max', ['attribute' => 'Name (' . strtoupper($properties['lang_code']) . ')', 'max' => 250]));
                $columns[] = ImportColumn::make("description_({$properties['lang_code']})")
                    ->label('Description (' . strtoupper($properties['lang_code']) . ')')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Description (' . strtoupper($properties['lang_code']) . ')']));
                $columns[] = ImportColumn::make("content_({$properties['lang_code']})")
                    ->label('Features (' . strtoupper($properties['lang_code']) . ')')
                    ->rules(['nullable', 'string'], trans('plugins/ecommerce::products.import.rules.nullable_string', ['attribute' => 'Features (' . strtoupper($properties['lang_code']) . ')']));
            }
        }

        return $columns;
    }

    public function examples(): array
    {
        $products = Product::query()
            ->where('is_variation', false)
            ->take(5)
            ->get()
            ->map(function (Product $product) {
                $product = [
                    ...$product->toArray(),
                    'url' => $product->url,
                    'slug' => $product->slug,
                    'brand' => $product->brand?->name,
                    'import_type' => 'product',
                    'is_variation_default' => true,
                    'auto_generate_sku' => true,
                    'description' => Str::limit($product->description),
                    'Features' => Str::limit($product->content),
                    'categories' => $product->categories?->pluck('name')->join(','),
                    'product_collections' => $product->productCollections?->pluck('name')->join(','),
                    'labels' => $product->labels?->pluck('name')->join(','),
                    'taxes' => $product->taxes?->pluck('name')->join(','),
                    'images' => collect($product->images)->map(fn ($image) => RvMedia::getImageUrl($image))->join(','),
                    'images' => collect($product->images) ->map(fn($image) => preg_replace('/\?.*/', '', RvMedia::getImageUrl($image))) ->join(','),

                    'product_attributes' => $product->productAttributes?->pluck('name')->join(','),
                    'tags' => $product->tags?->pluck('name')->join(','),
                    'producttypes' => $product->producttypes?->pluck('name')->join(','),
                   // 'handle' => $product->handle,
                    'variant_grams' => $product->variant_grams,
                    'variant_inventory_tracker' => $product->variant_inventory_tracker,
                    'variant_inventory_quantity' => $product->variant_inventory_quantity,
                    'variant_inventory_policy' => $product->variant_inventory_policy,
                    'variant_fulfillment_service' => $product->variant_fulfillment_service,
                    'variant_requires_shipping' => $product->variant_requires_shipping,
                    'variant_barcode' => $product->variant_barcode,
                    'gift_card' => $product->gift_card,
                    'seo_title' => $product->seo_title,
                    'seo_description' => $product->seo_description,
                    'google_shopping_category' => $product->google_shopping_category,
                    // 'google_shopping_gender' => $product->google_shopping_gender,
                    // 'google_shopping_age_group' => $product->google_shopping_age_group,
                    'google_shopping_mpn' => $product->google_shopping_mpn,
                    // 'google_shopping_condition' => $product->google_shopping_condition,
                    // 'google_shopping_custom_product' => $product->google_shopping_custom_product,
                    // 'google_shopping_custom_label_0' => $product->google_shopping_custom_label_0,
                    // 'google_shopping_custom_label_1' => $product->google_shopping_custom_label_1,
                    // 'google_shopping_custom_label_2' => $product->google_shopping_custom_label_2,
                    // 'google_shopping_custom_label_3' => $product->google_shopping_custom_label_3,
                    // 'google_shopping_custom_label_4' => $product->google_shopping_custom_label_4,
                    'box_quantity' => $product->box_quantity,
                    // 'technical_table' => $product->technical_table,
                    // 'technical_spec' => $product->technical_spec,
                ];

                if (is_plugin_active('marketplace')) {
                    $stores = DB::table('mp_stores')->pluck('name', 'id');

                    $product['vendor'] = $stores->count() ? $stores->random() : null;
                }

                foreach ($this->supportedLocales as $properties) {
                    if ($properties['lang_code'] != $this->defaultLanguage) {
                        $product['name_' . $properties['lang_code']] = $product['name'] . ' (' . strtoupper($properties['lang_code']) . ')';
                        $product['description_' . $properties['lang_code']] = $product['description'] . ' (' . strtoupper($properties['lang_code']) . ')';
                        $product['content_' . $properties['lang_code']] = $product['content'] . ' (' . strtoupper($properties['lang_code']) . ')';
                    }
                }

                return $product;
            });

        if ($products->isNotEmpty()) {
            return $products->all();
        }

        $examples = [
            [
                'name' => 'Product name',
                'description' => 'Product description',
                'slug' => 'product-slug',
                'url' => 'product-url',
                'sku' => 'product-sku',
                'categories' => 'category1,category2',
                'status' => 'publish',
                'is_featured' => 1,
                'brand' => 'brand-name',
                'product_collections' => 'collection1,collection2',
                'labels' => 'label1,label2',
                'taxes' => 'tax1,tax2',
                'image' => 'image-url',
                'images' => 'image-url1,image-url2',
                'price' => '100',
                'product_attributes' => 'attribute1,attribute2',
                'import_type' => 'product',
                'is_variation_default' => 1,
                'stock_status' => 'in_stock',
                'with_storehouse_management' => 1,
                'quantity' => '100',
                'sale_price' => '90',
                'start_date' => '2021-01-01',
                'end_date' => '2021-01-31',
                'weight' => 1,
                'length' => 1,
                'width' => 1,
                'depth' => 1,
                'shipping_height' => 1,
                'shipping_width' => 1,
                'shipping_depth' => 1,
                'shipping_length' => 1,
                'height' => 1,
                'cost_per_item' => 10,
                'barcode' => 'product-barcode',
                'Features' => 'product-content',
                'tags' => 'tag1,tag2',
                'producttypes' => 'producttypes1,producttypes2',
                'product_type' => 'physical',
                'vendor' => 'vendor-name',
                'auto_generate_sku' => 1,
               // 'generate_license_code' => 1,
                'minimum_order_quantity' => 1,
                'maximum_order_quantity' => 10,
                //'handle' => 'product-handle',
                'variant_grams' => 200,
                'variant_inventory_tracker' => 'tracker-name',
                'variant_inventory_quantity' => 50,
                'variant_inventory_policy' => 'deny',
                'variant_fulfillment_service' => 'manual',
                'variant_requires_shipping' => true,
                'variant_barcode' => 'variant-barcode',
                'gift_card' => false,
                'seo_title' => 'SEO Title',
                'seo_description' => 'SEO Description',
                'google_shopping_category' => 'category-name',
                // 'google_shopping_gender' => 'unisex',
                // 'google_shopping_age_group' => 'adult',
                'google_shopping_mpn' => 'mpn-code',
                // 'google_shopping_condition' => 'new',
                // 'google_shopping_custom_product' => 'custom-product',
                // 'google_shopping_custom_label_0' => 'label0',
                // 'google_shopping_custom_label_1' => 'label1',
                // 'google_shopping_custom_label_2' => 'label2',
                // 'google_shopping_custom_label_3' => 'label3',
                // 'google_shopping_custom_label_4' => 'label4',
                'box_quantity' => 10,
               'frequently_bought_together' => 'product-id1,product-id2',
                'compare_type' => 'type',
                'compare_products' => 'product-id3,product-id4',
                'refund' => '15 days',
                'warranty_information' => 'warranty_information',
                'delivery_days' => '3-5 days',
                'currency_id' => 1,
                'variant_1_title' => 'Size',
                'variant_1_value' => 'M',
                'variant_1_products' => 'product-id5',
                'variant_2_title' => 'Color',
                'variant_2_value' => 'Red',
                'variant_2_products' => 'product-id6',
                'variant_3_title' => 'Style',
                'variant_3_value' => 'Classic',
                'variant_3_products' => 'product-id7',
                'variant_color_title' => 'Color',
                'variant_color_value' => 'Red',
                'variant_color_products' => 'product-id8',
            ],
        ];

        foreach ($this->supportedLocales as $properties) {
            if ($properties['lang_code'] != $this->defaultLanguage) {
                $examples[0]['name_' . $properties['lang_code']] = 'Product name (' . strtoupper($properties['lang_code']) . ')';
                $examples[0]['description_' . $properties['lang_code']] = 'Product description (' . strtoupper($properties['lang_code']) . ')';
                $examples[0]['content_' . $properties['lang_code']] = 'Product content (' . strtoupper($properties['lang_code']) . ')';
            }
        }

        return $examples;
    }

    public function getValidateUrl(): string
    {
        return route('tools.data-synchronize.import.products.validate');
    }

    public function getImportUrl(): string
    {
        return route('tools.data-synchronize.import.products.store');
    }

    public function getDownloadExampleUrl(): ?string
    {
        return route('tools.data-synchronize.import.products.download-example');
    }

    public function getExportUrl(): ?string
    {
        return Auth::user()->hasPermission('ecommerce.export.products.index')
            ? route('tools.data-synchronize.export.products.index')
            : null;
    }

    public function handle(array $data): int
    {
        foreach ($data as $row) {
            $importType = $this->getImportType();

            if ($importType === 'products' && $row['import_type'] === 'product') {
                $this->storeProduct($row);

                continue;
            }

            if ($importType === 'variations' && $row['import_type'] === 'variation') {
                $product = $this->getProduct($row['name'], $row['slug']);

                $this->storeVariant($row, $product);

                continue;
            }

            if ($row['import_type'] === 'variation') {
                if ($slug = $row['slug']) {
                    $collection = $this->successes()
                        ->where('import_type', 'product')
                        ->where('slug', $slug)
                        ->last();
                } else {
                    $collection = $this->successes()
                        ->where('import_type', 'product')
                        ->where('name', $row['name'])
                        ->last();
                }

                if ($collection) {
                    $product = $collection['model'];
                } else {
                    $product = $this->getProduct($row['name'], $slug);
                }

                $this->storeVariant($row, $product);
            } else {
                $this->storeProduct($row);
            }
        }

        return $this->successes()->count();
    }

    protected function getProduct(string $name, ?string $slug): Model|Builder|null
    {
        if ($slug) {
            $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Product::class), Product::class);

            if ($slug) {
                return Product::query()
                    ->where([
                        'id' => $slug->reference_id,
                        'is_variation' => 0,
                    ])
                    ->first();
            }
        }

        return Product::query()
            ->where(function ($query) use ($name) {
                $query
                    ->where('name', $name)
                    ->orWhere('id', $name);
            })
            ->where('is_variation', 0)
            ->first();
    }

    public function map(mixed $row): array
    {
        $this->currentRow++;
        if(auth()->user() && \DB::table('role_users')->where('user_id', auth()->user()->id)->where('role_id', 22)->exists() )
        {
            $row['import_type'] = Arr::get($row, 'import_type');
            if ($row['import_type'] != 'variation') {
                $row['import_type'] = 'product';
            }
        } else {
            $row = $this->mapLocalization($row);
            $row = $this->setCategoriesToRow($row);
            $row = $this->setTaxToRow($row);
            $row = $this->setProductCollectionsToRow($row);
            $row = $this->setProductLabelsToRow($row);
        }
        $row = $this->setBrandToRow($row);

        return apply_filters('ecommerce_import_product_row_data', $row);
    }

    public function storeProduct(array $row): Product|Model|null
    {
        if (array_key_exists('id', $row) && Product::find($row['id'])) {
            $product = $this->updateProduct($row, 'id');
            return $product;
        } else {
            if (array_key_exists('sku', $row) && array_key_exists('name', $row) && Product::query()->where('name', $row['name'])->where('sku', $row['sku'])->first()
            ) {
                $product = $this->updateProduct($row, 'sku');
                return $product;
            } else {
                // code...
                $request = new Request();
                $request->merge($row);

                $product = new Product();
                $preparedData = $this->prepareProductAttributes($request, $product);
                $request = $preparedData['request'];
                $product = $preparedData['product'];

                $product = (new StoreProductService())->execute($request, $product);

                if (isset($product->id) && !empty($product->id)) {
                    // Required fields for discounts
                    $requiredFieldValues = [
                        'quantity1' => $row['buying_quantity_1'] ?? null,
                        'value1' => $row['discount_1'] ?? null,
                        'start_date1' => $row['start_date_1'] ?? null,
                        'quantity2' => $row['buying_quantity_2'] ?? null,
                        'value2' => $row['discount_2'] ?? null,
                        'start_date2' => $row['start_date_2'] ?? null,
                    ];

                    $requiredFieldsProvided = !empty($requiredFieldValues['quantity1']) && !empty($requiredFieldValues['value1']) && !empty($requiredFieldValues['start_date1']) && !empty($requiredFieldValues['quantity2']) && !empty($requiredFieldValues['value2']) && !empty($requiredFieldValues['start_date2']);
                    if ($requiredFieldsProvided) {
                        for ($i = 1; $i <= 3; $i++) {
                            // Check if the current iteration is optional (3rd discount)
                            $isOptional = ($i === 3);

                            // Required fields for discounts
                            $requiredFields = [
                                'quantity' => $row['buying_quantity_' . $i] ?? null,
                                'value' => $row['discount_' . $i] ?? null,
                                'start_date' => $row['start_date_' . $i] ?? null,
                            ];

                            // Check if all required fields are non-empty
                            $allFieldsProvided = !empty($requiredFields['quantity']) && !empty($requiredFields['value']) && !empty($requiredFields['start_date']);

                            // Validate required fields for discounts
                            if ($allFieldsProvided) {
                                // Create a new discount
                                $discount = new Discount();
                                $discount->product_quantity = $requiredFields['quantity'];
                                $discount->title = $discount->product_quantity . ' products';
                                $discount->type_option = 'percentage';
                                $discount->type = 'promotion';
                                $discount->value = $requiredFields['value'];
                                $discount->start_date = !empty($requiredFields['start_date']) ? Carbon::parse($requiredFields['start_date']) : null;
                                $discount->end_date = !empty($row['end_date_' . $i]) ? Carbon::parse($row['end_date_' . $i]) : null;
                                $discount->save();

                                // Associate the discount with the product
                                $discountProduct = new DiscountProduct();
                                $discountProduct->discount_id = $discount->id;
                                $discountProduct->product_id = $product->id;
                                $discountProduct->save();
                            }
                        }
                    }
                }

                $this->createTranslations($product, $row);

                $tagsInput = (array) $request->input('tags', []);
                if ($tagsInput) {
                    $tags = [];
                    foreach ($tagsInput as $tag) {
                        $tags[] = ['value' => $tag];
                    }
                    $request->merge(['tag' => json_encode($tags)]);
                    app(StoreProductTagService::class)->execute($request, $product);
                }

                /* Product Types*/
                $productTypeNames = (array) $request->input('producttypes', []);
                $productTypeIds = [];
                foreach ($productTypeNames as $productTypeName) {
                    $productTypeId = ProductTypes::where('name', $productTypeName)->value('id');
                    if (empty($productTypeId)) {
                        $productType = ProductTypes::create(['name' => $productTypeName]);
                        $request->merge(['slug' => $productTypeName]);
                        event(new CreatedContentEvent(PRODUCT_TYPES_MODULE_SCREEN_NAME, $request, $productType));
                        $productTypeId = $productType->id;
                    }
                    $productTypeIds[] = (string) $productTypeId;
                }
                sort($productTypeIds);
                $request->merge(['producttypes' => $productTypeIds]);

                if ($request->producttypes) {
                    app(StoreProductTypesService::class)->execute($request, $product);
                }
                /* Product Types*/

                $attributeSets = $request->input('attribute_sets', []);

                $product->productAttributeSets()->sync($attributeSets);

                $this->onSuccess([
                    'name' => $product->name,
                    'slug' => $request->input('slug'),
                    'import_type' => 'product',
                    'attribute_sets' => $attributeSets,
                    'model' => $product,
                ]);

                return $product;
            }
        }
    }

    public function updateProduct(array $row, $updatedBy): Product|Model|null
    {
        // dd(22, $row);
        $request = new Request();
        $request->merge($row);

        if ($updatedBy == 'id') {
            $product = Product::find($row['id']);
        } else if ($updatedBy == 'sku') {
            $product = Product::where('name', $row['name'])->where('sku', $row['sku'])->first();
        }


        if(auth()->user() && \DB::table('role_users')->where('user_id', auth()->user()->id)->where('role_id', 22)->exists() )
        {

        } else {
            $preparedData = $this->prepareProductAttributes($request, $product);
            $request = $preparedData['request'];
            $product = $preparedData['product'];
        }
        $product->sku = $request->sku ? $request->sku : $product->sku;
        $product->price = $request->price ? $request->price : $product->price;
        $product->sale_price = $request->sale_price ? $request->sale_price : $product->sale_price;
        $product->start_date = $request->start_date ? $request->start_date : $product->start_date;
        $product->end_date = $request->end_date ? $request->end_date : $product->end_date;
        $product->cost_per_item = $request->cost_per_item ? $request->cost_per_item : $product->cost_per_item;
        $product->quantity = $request->quantity ? $request->quantity : $product->quantity;
        $product->store_id = $request->store_id ? $request->store_id : $product->store_id;
        $product->minimum_order_quantity = $request->minimum_order_quantity ? $request->minimum_order_quantity : $product->minimum_order_quantity;
        $product->variant_requires_shipping = $request->variant_requires_shipping ? $request->variant_requires_shipping : $product->variant_requires_shipping;
        $product->refund = $request->refund ? $request->refund : $product->refund;
        $product->unit_of_measurement_id = $request->unit_of_measurement_id ? $request->unit_of_measurement_id : $product->unit_of_measurement_id;
        $product->delivery_days = $request->delivery_days ? $request->delivery_days : $product->delivery_days;
        $product->google_shopping_mpn = $request->google_shopping_mpn ? $request->google_shopping_mpn : $product->google_shopping_mpn;
        $product->box_quantity = $request->box_quantity ? $request->box_quantity : $product->box_quantity;
        $product->save();


        $discountIds = $product->discounts->pluck('id')->toArray();

        // Required fields for discounts
        $requiredFieldValues = [
            'quantity1' => $row['buying_quantity_1'] ?? null,
            'value1' => $row['discount_1'] ?? null,
            'start_date1' => $row['start_date_1'] ?? null,
            'quantity2' => $row['buying_quantity_2'] ?? null,
            'value2' => $row['discount_2'] ?? null,
            'start_date2' => $row['start_date_2'] ?? null,
        ];

        $requiredFieldsProvided = !empty($requiredFieldValues['quantity1']) && !empty($requiredFieldValues['value1']) && !empty($requiredFieldValues['start_date1']) && !empty($requiredFieldValues['quantity2']) && !empty($requiredFieldValues['value2']) && !empty($requiredFieldValues['start_date2']);
        if ($requiredFieldsProvided) {
            for ($i = 1; $i <= 3; $i++) {
                // Check if the current iteration is optional (3rd discount)
                $isOptional = ($i === 3);

                // Required fields for discounts
                $requiredFields = [
                    'quantity' => $row['buying_quantity_' . $i] ?? null,
                    'value' => $row['discount_' . $i] ?? null,
                    'start_date' => $row['start_date_' . $i] ?? null,
                ];

                // Check if all required fields are non-empty
                $allFieldsProvided = !empty($requiredFields['quantity']) && !empty($requiredFields['value']) && !empty($requiredFields['start_date']);

                // Validate required fields for discounts
                if (!$isOptional || ($isOptional && array_filter($requiredFields) && $allFieldsProvided)) {
                    // Check if the discount already exists
                    if (isset($discountIds[$i - 1])) {
                        $discount = Discount::find($discountIds[$i - 1]);
                        $discExist = true;
                    } else {
                        $discount = new Discount();
                        $discExist = false;
                    }

                    // Update or create the discount
                    $discount->product_quantity = $requiredFields['quantity'];
                    $discount->title = $discount->product_quantity . ' products';
                    $discount->type_option = 'percentage';
                    $discount->type = 'promotion';
                    $discount->value = $requiredFields['value'];
                    $discount->start_date = !empty($requiredFields['start_date']) ? Carbon::parse($requiredFields['start_date']) : null;
                    $discount->end_date = !empty($row['end_date_' . $i]) ? Carbon::parse($row['end_date_' . $i]) : null;
                    $discount->save();

                    // If new discount, associate it with the product
                    if (!$discExist) {
                        $discountProduct = new DiscountProduct();
                        $discountProduct->discount_id = $discount->id;
                        $discountProduct->product_id = $product->id;
                        $discountProduct->save();
                    }
                } elseif ($isOptional) {
                    // Handle optional case for 3rd entry if fields are incomplete
                    if (isset($discountIds[$i - 1])) {
                        // Delete the discount if it exists but the optional fields are incomplete
                        $discount = Discount::find($discountIds[$i - 1]);
                        $discount->delete();
                    }
                }
            }
        }

        return $product;
    }

    public function prepareProductAttributes($request, $product)
    {
        $images = $this->getImageURLs((array) $request->input('images', []));

        $request->merge(['images' => $images]);

        $image = Arr::first($images);

        if ($request->input('image')) {
            $imageFromRequest = $this->getImageURLs([$request->input('image')]);

            if ($imageFromRequest) {
                $image = Arr::first($imageFromRequest);
            }
        }

        $request->merge(['image' => $image]);

        if ($description = $request->input('description')) {
            $request->merge(['description' => BaseHelper::clean($description)]);
        }

        if ($content = $request->input('Features')) {
            $request->merge(['content' => BaseHelper::clean($content)]);
        }

        // Set additional fields to the product
        //$product->handle = $request->input('handle');
        $product->variant_grams = $request->input('variant_grams');
        $product->variant_inventory_tracker = $request->input('variant_inventory_tracker');
        $product->variant_inventory_quantity = $request->input('variant_inventory_quantity');
        $product->variant_inventory_policy = $request->input('variant_inventory_policy');
        $product->variant_fulfillment_service = $request->input('variant_fulfillment_service');
        $product->variant_requires_shipping = $request->input('variant_requires_shipping');
        $product->variant_barcode = $request->input('variant_barcode');
        $product->gift_card = $request->input('gift_card');
        $product->seo_title = $request->input('seo_title');
        $product->seo_description = $request->input('seo_description');
        $product->google_shopping_category = $request->input('google_shopping_category');
        $product->google_shopping_mpn = $request->input('google_shopping_mpn');
        // Define your ImportColumns

        // Handle video path input
        $videoPaths = $request->input('video_path');

        // Ensure the input is a string
        if (is_array($videoPaths)) {
            $videoPaths = implode(',', $videoPaths); // Convert to string if it's an array
        }

        // Save the video path to the product model
        $product->video_path = $videoPaths;
        $product->warranty_information = $request->input('warranty_information');
        // $product->refund_policy = $request->input('refund_policy');
        // $product->shipping_weight_option = $request->input('shipping_weight_option');
        $product->shipping_weight = $request->input('shipping_weight');
        // $product->shipping_dimension_option = $request->input('shipping_dimension_option');
        $product->shipping_width = $request->input('shipping_width');
        $product->shipping_width_id = $request->input('shipping_width_id');
        $product->shipping_depth = $request->input('shipping_depth');
        $product->shipping_depth_id = $request->input('shipping_depth_id');
        $product->shipping_height = $request->input('shipping_height');
        $product->shipping_height_id = $request->input('shipping_height_id');
        $product->shipping_length = $request->input('shipping_length');
        $product->shipping_length_id = $request->input('shipping_length_id');
        $frequentlyBoughtTogether = $request->input('frequently_bought_together', []);

        // Convert each item to an associative array if it's in the wrong format
        $formattedData = array_map(function ($item) {
            // Decode if needed and ensure the structure
            if (is_string($item)) {
                $decoded = json_decode($item, true);
                return is_array($decoded) ? $decoded : ['value' => $item];
            }
            return $item;
        }, $frequentlyBoughtTogether);

        // Now, encode the properly formatted array
        $product->frequently_bought_together = json_encode($formattedData);

        $compareType = $request->input('compare_type');

        // Check if it's in string format and decode it if necessary
        if (is_string($compareType)) {
            // Remove square brackets and split the string by commas
            $compareTypeArray = explode(',', trim($compareType, '[]'));

            // Trim whitespace and ensure the values are numeric strings
            $compareTypeArray = array_map('trim', $compareTypeArray);
            $compareTypeArray = array_filter($compareTypeArray, 'is_numeric');
        } else {
            // If it's already an array, use it directly
            $compareTypeArray = (array) $compareType;
        }

        // Convert all values to strings (if needed) to match your desired format
        $compareTypeArray = array_map('strval', $compareTypeArray);

        // Convert the cleaned array to a JSON string for storage
        $product->compare_type = json_encode(array_values($compareTypeArray));



        $compareProductsInput = $request->input('compare_products');

        // Check if it's in string format and decode it if necessary
        if (is_string($compareProductsInput)) {
            // Remove square brackets and split the string by commas
            $compareProductsArray = explode(',', trim($compareProductsInput, '[]'));

            // Trim whitespace and ensure the values are numeric strings
            $compareProductsArray = array_map('trim', $compareProductsArray);
            $compareProductsArray = array_filter($compareProductsArray, 'is_numeric');
        } else {
            // If it's already an array, use it directly
            $compareProductsArray = (array) $compareProductsInput;
        }

        // Convert all values to strings (if needed) to match your desired format
        $compareProductsArray = array_map('strval', $compareProductsArray);

        // Convert the cleaned array to a JSON string for storage
        $product->compare_products = json_encode(array_values($compareProductsArray));



        $product->delivery_days = $request->input('delivery_days');
        $product->currency_id = $request->input('currency_id', 1); // Default to 1 if not provided
        $product->variant_1_title = $request->input('variant_1_title');
        $product->variant_1_value = $request->input('variant_1_value');
        $product->variant_1_products = $request->input('variant_1_products');
        $product->variant_2_title = $request->input('variant_2_title');
        $product->variant_2_value = $request->input('variant_2_value');
        $product->variant_2_products = $request->input('variant_2_products');
        $product->variant_3_title = $request->input('variant_3_title');
        $product->variant_3_value = $request->input('variant_3_value');
        $product->variant_3_products = $request->input('variant_3_products');
        $product->variant_color_title = $request->input('variant_color_title', 'Color');
        $product->variant_color_value = $request->input('variant_color_value');
        $product->variant_color_products = $request->input('variant_color_products');



        $product->status = strtolower($request->input('status'));

        return [
            'product' => $product,
            'request' => $request
        ];
    }

    protected function createTranslations(Product $product, array $row): void
    {
        if (! defined('LANGUAGE_MODULE_SCREEN_NAME')) {
            return;
        }

        /** @var \Botble\Language\Models\Language $language */
        $languages = Language::getActiveLanguage(['lang_code', 'lang_is_default']);

        foreach ($languages as $language) {
            if ($language->lang_is_default) {
                continue;
            }

            $translation = DB::table('ec_products_translations')->where([
                'lang_code' => $language->lang_code,
                'ec_products_id' => $product->getKey(),
            ]);

            if ($translation->exists()) {
                $translation->update([
                    'name' => $row["name_({$language->lang_code})"] ?? $row['name'],
                    'description' => $row["description_({$language->lang_code})"] ?? $row['description'],
                    'content' => $row["content_({$language->lang_code})"] ?? $row['Features'],
                ]);
            }
        }
    }

    public function mapLocalization(array $row): array
    {
        //$row['generate_license_code'] = (bool) Arr::get($row, 'generate_license_code', false);
        $row['minimum_order_quantity'] = (int) Arr::get($row, 'minimum_order_quantity', 0);
        $row['maximum_order_quantity'] = (int) Arr::get($row, 'maximum_order_quantity', 0);
        $row['stock_status'] = (string) Arr::get($row, 'stock_status');
        if (! in_array($row['stock_status'], StockStatusEnum::toArray())) {
            $row['stock_status'] = StockStatusEnum::IN_STOCK;
        }

        $row['status'] = Arr::get($row, 'status');
        if (! in_array($row['status'], BaseStatusEnum::toArray())) {
            $row['status'] = BaseStatusEnum::PENDING;
        }

        $row['product_type'] = Arr::get($row, 'product_type');
        if (! in_array($row['product_type'], ProductTypeEnum::toArray())) {
            $row['product_type'] = ProductTypeEnum::PHYSICAL;
        }

        $row['import_type'] = Arr::get($row, 'import_type');
        if ($row['import_type'] != 'variation') {
            $row['import_type'] = 'product';
        }

        $row['is_slug_editable'] = true;
        $row['barcode'] = (string) Arr::get($row, 'barcode');

        // Call setValues to handle new fields
        $this->setValues($row, [
            ['key' => 'slug', 'type' => 'string', 'default' => 'name'],
            ['key' => 'sku', 'type' => 'string'],
            ['key' => 'price', 'type' => 'number'],
            ['key' => 'weight', 'type' => 'number'],
            ['key' => 'length', 'type' => 'number'],
            ['key' => 'width', 'type' => 'number'],
            ['key' => 'height', 'type' => 'number'],
            ['key' => 'depth', 'type' => 'number'],
            ['key' => 'shipping_height', 'type' => 'number'],
            ['key' => 'shipping_length', 'type' => 'number'],
            ['key' => 'shipping_width', 'type' => 'number'],
            ['key' => 'shipping_depth', 'type' => 'number'],
            ['key' => 'cost_per_item', 'type' => 'number'],
            ['key' => 'barcode', 'type' => 'string'],
            ['key' => 'is_featured', 'type' => 'bool'],
            ['key' => 'product_labels', 'type' => 'array'],
            ['key' => 'labels', 'type' => 'array'],
            ['key' => 'images', 'type' => 'array'],
            ['key' => 'categories', 'type' => 'array'],
            ['key' => 'product_collections', 'type' => 'array'],
            ['key' => 'product_attributes', 'type' => 'array'],
            ['key' => 'is_variation_default', 'type' => 'bool'],
            ['key' => 'auto_generate_sku', 'type' => 'bool'],
            ['key' => 'with_storehouse_management', 'type' => 'bool'],
            ['key' => 'allow_checkout_when_out_of_stock', 'type' => 'bool'],
            ['key' => 'quantity', 'type' => 'number'],
            ['key' => 'sale_price', 'type' => 'number'],
            ['key' => 'start_date', 'type' => 'datetime'],
            ['key' => 'end_date', 'type' => 'datetime'],
            ['key' => 'tags', 'type' => 'array'],
            ['key' => 'producttypes', 'type' => 'array'],
            ['key' => 'taxes', 'type' => 'array'],
           // ['key' => 'handle', 'type' => 'string'], // New fields
            ['key' => 'variant_grams', 'type' => 'number'],
            ['key' => 'variant_inventory_tracker', 'type' => 'string'],
            ['key' => 'variant_inventory_quantity', 'type' => 'number'],
            ['key' => 'variant_inventory_policy', 'type' => 'string'],
            ['key' => 'variant_fulfillment_service', 'type' => 'string'],
            ['key' => 'variant_requires_shipping', 'type' => 'bool'],
            ['key' => 'variant_barcode', 'type' => 'string'],
            ['key' => 'gift_card', 'type' => 'bool'],
            ['key' => 'seo_title', 'type' => 'string'],
            ['key' => 'seo_description', 'type' => 'string'],
            ['key' => 'google_shopping_category', 'type' => 'string'],
            // ['key' => 'google_shopping_gender', 'type' => 'string'],
            // ['key' => 'google_shopping_age_group', 'type' => 'string'],
            ['key' => 'google_shopping_mpn', 'type' => 'string'],
            // ['key' => 'google_shopping_condition', 'type' => 'string'],
            // ['key' => 'google_shopping_custom_product', 'type' => 'string'],
            // ['key' => 'google_shopping_custom_label_0', 'type' => 'string'],
            // ['key' => 'google_shopping_custom_label_1', 'type' => 'string'],
            // ['key' => 'google_shopping_custom_label_2', 'type' => 'string'],
            // ['key' => 'google_shopping_custom_label_3', 'type' => 'string'],
            // ['key' => 'google_shopping_custom_label_4', 'type' => 'string'],
            ['key' => 'box_quantity', 'type' => 'number'],
            // ['key' => 'technical_table', 'type' => 'string'],
            // ['key' => 'technical_spec', 'type' => 'string'],

            ['key' => 'video_path', 'type' => 'string'], // Added video_path
           // ['key' => 'refund_policy', 'type' => 'bool'], // Assuming tinyint(1) is for boolean
           // ['key' => 'shipping_weight_option', 'type' => 'string'], // Assuming enum is string
            ['key' => 'shipping_weight', 'type' => 'number'], // Added shipping_weight
            // ['key' => 'shipping_dimension_option', 'type' => 'string'], // Assuming enum is string
            ['key' => 'shipping_width', 'type' => 'number'], // Added shipping_width
            ['key' => 'shipping_width_id', 'type' => 'number'], // Added shipping_width_id
            ['key' => 'shipping_depth', 'type' => 'number'], // Added shipping_depth
            ['key' => 'shipping_depth_id', 'type' => 'number'], // Added shipping_depth_id
            ['key' => 'shipping_height', 'type' => 'number'], // Added shipping_height
            ['key' => 'shipping_height_id', 'type' => 'number'], // Added shipping_height_id
            ['key' => 'shipping_length', 'type' => 'number'], // Added shipping_length
            ['key' => 'shipping_length_id', 'type' => 'number'], // Added shipping_length_id
             ['key' => 'frequently_bought_together', 'type' => 'array'], // Keep as array
             ['key' => 'compare_type', 'type' => 'array'], // Set as string for JSON storage
             ['key' => 'compare_products', 'type' => 'array'], // Set as string for JSON storage
            ['key' => 'refund', 'type' => 'string'], // Assuming enum is string
            ['key' => 'delivery_days', 'type' => 'string'], // Assuming varchar(20) is string
            ['key' => 'warranty_information', 'type' => 'string'],
            ['key' => 'currency_id', 'type' => 'number'], // Added currency_id
            ['key' => 'variant_1_title', 'type' => 'string'], // Added variant_1_title
            ['key' => 'variant_1_value', 'type' => 'string'], // Added variant_1_value
            ['key' => 'variant_1_products', 'type' => 'string'], // Change to string
            ['key' => 'variant_2_title', 'type' => 'string'], // Added variant_2_title
            ['key' => 'variant_2_value', 'type' => 'string'], // Added variant_2_value
            ['key' => 'variant_2_products', 'type' => 'string'], // Change to string
            ['key' => 'variant_3_title', 'type' => 'string'], // Added variant_3_title
            ['key' => 'variant_3_value', 'type' => 'string'], // Added variant_3_value
            ['key' => 'variant_3_products', 'type' => 'string'], // Change to string
            ['key' => 'variant_color_title', 'type' => 'string'], // Added variant_color_title
            ['key' => 'variant_color_value', 'type' => 'string'], // Added variant_color_value
            ['key' => 'variant_color_products', 'type' => 'string'], // Change to string
            ['key' => 'google_shopping_mpn', 'type' => 'string'],



        ]);

        $row['product_labels'] = $row['labels'];

        if ($row['import_type'] == 'product' && ! $row['sku'] && $row['auto_generate_sku']) {
            $row['sku'] = (new Product())->generateSKU();
        }

        $row['sale_type'] = 0;
        if ($row['start_date'] || $row['end_date']) {
            $row['sale_type'] = 1;
        }

        if (! $row['with_storehouse_management']) {
            $row['quantity'] = null;
            $row['allow_checkout_when_out_of_stock'] = false;
        }

        $attributeSets = Arr::get($row, 'product_attributes');
        $row['attribute_sets'] = [];
        $row['product_attributes'] = [];

        if ($row['import_type'] == 'variation') {
            foreach ($attributeSets as $attrSet) {
                $attrSet = explode(':', $attrSet);
                $title = Arr::get($attrSet, 0);
                $valueX = Arr::get($attrSet, 1);

                $attribute = $this->productAttributeSets->filter(function ($value) use ($title) {
                    return $value['title'] == $title;
                })->first();

                if ($attribute) {
                    $attr = $attribute->attributes->filter(function ($value) use ($valueX) {
                        return $value['title'] == $valueX;
                    })->first();

                    if ($attr) {
                        $row['attribute_sets'][$attribute->id] = $attr->id;
                    }
                }
            }
        }

        if ($row['import_type'] == 'product') {
            foreach ($attributeSets as $attrSet) {
                $attribute = $this->productAttributeSets->filter(function ($value) use ($attrSet) {
                    return $value['title'] == $attrSet;
                })->first();

                if ($attribute) {
                    $row['attribute_sets'][] = $attribute->id;
                }
            }
        }

        return $row;
    }

    public function storeVariant(array $row, ?Product $product): ProductVariation|Model|null
    {
        $request = new Request();
        $request->merge($row);

        if (! $product) {
            $this->onFailure(
                $this->currentRow,
                'Name',
                [__('Product name ":name" does not exists', ['name' => $request->input('name')])]
            );

            return null;
        }

        $addedAttributes = $request->input('attribute_sets', []);

        $result = ProductVariation::getVariationByAttributesOrCreate($product->getKey(), $addedAttributes);

        if (! $result['created']) {
            $this->onFailure(
                $this->currentRow,
                'variation',
                [
                    trans('plugins/ecommerce::products.form.variation_existed') . ' ' . trans(
                        'plugins/ecommerce::products.form.product_id'
                    ) . ': ' . $product->getKey(),
                ],
            );

            return null;
        }

        $variation = $result['variation'];

        $version = array_merge($variation->toArray(), $request->toArray());

        if (
            ($sku = Arr::get($version, 'sku')) &&
            $existingVariation = Product::query()->where('is_variation', true)->where('sku', $sku)->first()
        ) {
            return $existingVariation;
        }

        $version['variation_default_id'] = Arr::get($version, 'is_variation_default') ? $version['id'] : null;
        $version['attribute_sets'] = $addedAttributes;

        if ($version['description']) {
            $version['description'] = BaseHelper::clean($version['description']);
        }

        if ($version['content']) {
            $version['content'] = BaseHelper::clean($version['content']);
        }

        $productRelatedToVariation = new Product();
        $productRelatedToVariation->fill($version);

        $productRelatedToVariation->name = $product->name;
        $productRelatedToVariation->status = $product->status;
        $productRelatedToVariation->brand_id = $product->brand_id;
        $productRelatedToVariation->is_variation = 1;

        $productRelatedToVariation->sku = Arr::get($version, 'sku');
        if (! $productRelatedToVariation->sku && Arr::get($version, 'auto_generate_sku')) {
            $productRelatedToVariation->sku = $product->sku;
            foreach ($version['attribute_sets'] as $setId => $attributeId) {
                $attributeSet = $this->productAttributeSets->firstWhere('id', $setId);
                if ($attributeSet) {
                    $attribute = $attributeSet->attributes->firstWhere('id', $attributeId);
                    if ($attribute) {
                        $productRelatedToVariation->sku .= '-' . Str::upper($attribute->slug);
                    }
                }
            }
        }

        $productRelatedToVariation->price = Arr::get($version, 'price', $product->price);
        $productRelatedToVariation->sale_price = Arr::get($version, 'sale_price', $product->sale_price);
        //$variation->handle = Arr::get($version, 'handle', $product->handle);
        $variation->variant_grams = Arr::get($version, 'variant_grams', $product->variant_grams);
        $variation->variant_inventory_tracker = Arr::get($version, 'variant_inventory_tracker', $product->variant_inventory_tracker);
        $variation->variant_inventory_quantity = Arr::get($version, 'variant_inventory_quantity', $product->variant_inventory_quantity);
        $variation->variant_inventory_policy = Arr::get($version, 'variant_inventory_policy', $product->variant_inventory_policy);
        $variation->variant_fulfillment_service = Arr::get($version, 'variant_fulfillment_service', $product->variant_fulfillment_service);
        $variation->variant_requires_shipping = Arr::get($version, 'variant_requires_shipping', $product->variant_requires_shipping);
        $variation->variant_barcode = Arr::get($version, 'variant_barcode', $product->variant_barcode);
        $variation->gift_card = Arr::get($version, 'gift_card', $product->gift_card);
        $variation->seo_title = Arr::get($version, 'seo_title', $product->seo_title);
        $variation->seo_description = Arr::get($version, 'seo_description', $product->seo_description);
        $variation->google_shopping_category = Arr::get($version, 'google_shopping_category', $product->google_shopping_category);
        // $variation->google_shopping_gender = Arr::get($version, 'google_shopping_gender', $product->google_shopping_gender);
        // $variation->google_shopping_age_group = Arr::get($version, 'google_shopping_age_group', $product->google_shopping_age_group);
        $variation->google_shopping_mpn = Arr::get($version, 'google_shopping_mpn', $product->google_shopping_mpn);
        // $variation->google_shopping_condition = Arr::get($version, 'google_shopping_condition', $product->google_shopping_condition);
        // $variation->google_shopping_custom_product = Arr::get($version, 'google_shopping_custom_product', $product->google_shopping_custom_product);
        // $variation->google_shopping_custom_label_0 = Arr::get($version, 'google_shopping_custom_label_0', $product->google_shopping_custom_label_0);
        // $variation->google_shopping_custom_label_1 = Arr::get($version, 'google_shopping_custom_label_1', $product->google_shopping_custom_label_1);
        // $variation->google_shopping_custom_label_2 = Arr::get($version, 'google_shopping_custom_label_2', $product->google_shopping_custom_label_2);
        // $variation->google_shopping_custom_label_3 = Arr::get($version, 'google_shopping_custom_label_3', $product->google_shopping_custom_label_3);
        // $variation->google_shopping_custom_label_4 = Arr::get($version, 'google_shopping_custom_label_4', $product->google_shopping_custom_label_4);
        $variation->box_quantity = Arr::get($version, 'box_quantity', $product->box_quantity);
        // $variation->technical_table = Arr::get($version, 'technical_table', $product->technical_table);
        // $variation->technical_spec = Arr::get($version, 'technical_spec', $product->technical_spec);


        if (Arr::get($version, 'description')) {
            $productRelatedToVariation->description = BaseHelper::clean($version['description']);
        }

        if (Arr::get($version, 'content')) {
            $productRelatedToVariation->content = BaseHelper::clean($version['content']);
        }

        $productRelatedToVariation->length = Arr::get($version, 'length', $product->length);
        $productRelatedToVariation->width = Arr::get($version, 'width', $product->width);
        $productRelatedToVariation->height = Arr::get($version, 'height', $product->height);
        $productRelatedToVariation->depth = Arr::get($version, 'depth', $product->depth);
        $productRelatedToVariation->weight = Arr::get($version, 'weight', $product->weight);

        $productRelatedToVariation->shipping_depth = Arr::get($version, 'shipping_depth', $product->shipping_depth);
        $productRelatedToVariation->shipping_width = Arr::get($version, 'shipping_width', $product->shipping_width);
        $productRelatedToVariation->shipping_length = Arr::get($version, 'shipping_length', $product->shipping_length);
        $productRelatedToVariation->shipping_height = Arr::get($version, 'shipping_height', $product->shipping_height);

        $productRelatedToVariation->sale_type = (int) Arr::get($version, 'sale_type', $product->sale_type);

        if ($productRelatedToVariation->sale_type == 0) {
            $productRelatedToVariation->start_date = null;
            $productRelatedToVariation->end_date = null;
        } else {
            $productRelatedToVariation->start_date = Carbon::parse(
                Arr::get($version, 'start_date', $product->start_date)
            )->toDateTimeString();
            $productRelatedToVariation->end_date = Carbon::parse(
                Arr::get($version, 'end_date', $product->end_date)
            )->toDateTimeString();
        }

        $productRelatedToVariation->images = json_encode(
            $this->getImageURLs((array) Arr::get($version, 'images', []) ?: [])
        );

        $productRelatedToVariation->status = strtolower(Arr::get($version, 'status', $product->status));

        $productRelatedToVariation->product_type = $product->product_type;
        $productRelatedToVariation->save();

        event(new CreatedContentEvent(PRODUCT_MODULE_SCREEN_NAME, $request, $productRelatedToVariation));

        $variation->product_id = $productRelatedToVariation->getKey();

        $variation->is_default = Arr::get($version, 'variation_default_id', 0) == $variation->id;

        $variation->save();

        if ($version['attribute_sets']) {
            $variation->productAttributes()->sync($version['attribute_sets']);
        }

        $this->createTranslations($productRelatedToVariation, $row);

        $this->onSuccess([
            'name' => $variation->name,
            'slug' => '',
            'import_type' => 'variation',
            'attribute_sets' => [],
            'model' => $variation,
        ]);

        return $variation;
    }

    protected function setBrandToRow(array $row): array
    {
        $row['brand_id'] = 0;

        if (! empty($row['brand'])) {
            $row['brand'] = trim($row['brand']);

            $brand = $this->brands->firstWhere('keyword', $row['brand']);
            if ($brand) {
                $brandId = $brand['brand_id'];
            } else {
                if (is_numeric($row['brand'])) {
                    $brand = Brand::query()->find($row['brand']);
                } else {
                    $brand = Brand::query()->where('name', $row['brand'])->first();
                }

                $brandId = $brand ? $brand->getKey() : 0;
                $this->brands->push([
                    'keyword' => $row['brand'],
                    'brand_id' => $brandId,
                ]);
            }
            $row['brand_id'] = $brandId;
        }

        return $row;
    }

    protected function setTaxToRow(array $row): array
    {
        $row['tax_id'] = null;

        $taxIds = [];
        if (! empty($row['tax'])) {
            $tax = $this->getTaxByKeyword(trim($row['tax']));
            if ($tax) {
                $taxIds[] = $tax->getKey();
            }
        }

        if ($row['taxes']) {
            foreach ($row['taxes'] as $value) {
                $tax = $this->getTaxByKeyword(trim($value));
                if ($tax) {
                    $taxIds[] = $tax->getKey();
                }
            }

            $row['taxes'] = array_filter($taxIds);
        }

        return $row;
    }

    protected function setCategoriesToRow(array $row): array
    {
        if ($row['categories']) {
            $categories = $row['categories'];
            $categoryIds = [];
            foreach ($categories as $value) {
                $value = trim($value);

                $category = $this->categories->firstWhere('keyword', $value);
                if ($category) {
                    $categoryId = $category['category_id'];
                } else {
                    if (is_numeric($value)) {
                        $category = ProductCategory::query()->find($value);
                    } else {
                        $category = ProductCategory::query()->where('name', $value)->first();
                    }

                    $categoryId = $category ? $category->getKey() : 0;
                    $this->categories->push([
                        'keyword' => $value,
                        'category_id' => $categoryId,
                    ]);
                }
                $categoryIds[] = $categoryId;
            }

            $row['categories'] = array_filter($categoryIds);
        }

        return $row;
    }

    protected function setProductCollectionsToRow(array $row): array
    {
        if ($row['product_collections']) {
            $productCollections = $row['product_collections'];
            $collectionIds = [];
            foreach ($productCollections as $value) {
                $value = trim($value);

                $collection = $this->productCollections->firstWhere('keyword', $value);
                if ($collection) {
                    $collectionId = $collection['collection_id'];
                } else {
                    if (is_numeric($value)) {
                        $collection = ProductCollection::query()->find($value);
                    } else {
                        $collection = ProductCollection::query()->where('name', $value)->first();
                    }

                    $collectionId = $collection ? $collection->getKey() : 0;
                    $this->productCollections->push([
                        'keyword' => $value,
                        'collection_id' => $collectionId,
                    ]);
                }
                $collectionIds[] = $collectionId;
            }

            $row['product_collections'] = array_filter($collectionIds);
        }

        return $row;
    }

    protected function setProductLabelsToRow(array $row): array
    {
        if ($row['product_labels']) {
            $productLabels = $row['product_labels'];
            $productLabelIds = [];
            foreach ($productLabels as $value) {
                $value = trim($value);

                $productLabel = $this->productLabels->firstWhere('keyword', $value);
                if ($productLabel) {
                    $productLabelId = $productLabel['product_label_id'];
                } else {
                    if (is_numeric($value)) {
                        $productLabel = ProductLabel::query()->find($value);
                    } else {
                        $productLabel = ProductLabel::query()->where('name', $value)->first();
                    }

                    $productLabelId = $productLabel ? $productLabel->getKey() : 0;
                    $this->productLabels->push([
                        'keyword' => $value,
                        'product_label_id' => $productLabelId,
                    ]);
                }
                $productLabelIds[] = $productLabelId;
            }

            $row['product_labels'] = array_filter($productLabelIds);
        }

        return $row;
    }

    protected function getTaxByKeyword(string|int $keyword): ?Tax
    {
        return $this->allTaxes->filter(function ($item) use ($keyword) {
            if (is_numeric($keyword)) {
                return $item->id == $keyword;
            }

            return $item->title == $keyword;
        })->first();
    }

    protected function setValues(array &$row, array $attributes = []): self
    {
        foreach ($attributes as $attribute) {
            $this->setValue(
                $row,
                Arr::get($attribute, 'key'),
                Arr::get($attribute, 'type', 'array'),
                Arr::get($attribute, 'default'),
                Arr::get($attribute, 'from')
            );
        }

        return $this;
    }

    protected function setValue(array &$row, string $key, string $type = 'array', $default = null, $from = null): self
{
    $value = Arr::get($row, $from ?: $key, $default);

    switch ($type) {
        case 'array':
            $value = $value ? explode(',', $value) : [];
            break;
        case 'bool':
            if (is_string($value) && (Str::lower($value) == 'false' || $value == '0' || Str::lower($value) == 'no')) {
                $value = false;
            }
            $value = (bool) $value;
            break;
        case 'datetime':
            if ($value) {
                $value = $this->getDate($value);
            }
            break;
        case 'number':
            $value = is_numeric($value) ? $value : null;
            break;
        case 'string':
        default:
            $value = (string) $value;
            break;
    }

    Arr::set($row, $key, $value);

    return $this;
}


    protected function getImageURLs(array $images): array
    {
        $images = array_values(array_filter($images));

        foreach ($images as $key => $image) {
            $images[$key] = str_replace(RvMedia::getUploadURL() . '/', '', trim($image));

            if (Str::startsWith($images[$key], ['http://', 'https://'])) {
                $images[$key] = $this->uploadImageFromURL($images[$key]);
            }
        }

        return $images;
    }

    // protected function uploadImageFromURL(?string $url): ?string
    // {
    //     $result = RvMedia::uploadFromUrl($url, 0, 'products');

    //     if (! $result['error']) {
    //         $url = $result['data']->url;
    //     } else {
    //         Log::error($result['message']);
    //     }

    //     return $url;
    // }





    //     protected function uploadImageFromURL(?string $url): ?string
    // {
    //     // Directory within public directory
    //     $productsDirectory = 'storage/products';

    //     // Ensure products directory exists only if it doesn't already
    //     $publicProductsPath = public_path($productsDirectory);
    //     if (!is_dir($publicProductsPath)) {
    //         // Create the directory only if it doesn't exist
    //         mkdir($publicProductsPath, 0755, true);
    //     }

    //     // Fetch the image content from the URL
    //     $imageContents = file_get_contents($url);

    //     if ($imageContents !== false) {
    //         // Sanitize the file name
    //         $fileNameWithQuery = basename(parse_url($url, PHP_URL_PATH));
    //         $fileName = preg_replace('/\?.*/', '', $fileNameWithQuery); // Remove query parameters
    //         $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME); // Get base name without extension

    //         // Save the original image
    //         $filePath = $publicProductsPath . '/' . $fileName;
    //         if (file_put_contents($filePath, $imageContents) === false) {
    //             Log::error('Failed to write image to file: ' . $filePath);
    //             return null;
    //         }

    //         // Resize the image
    //         $sizes = [
    //             'thumb' => [150, 150],
    //             'medium' => [300, 300],
    //             'large' => [790, 510],
    //         ];

    //         foreach ($sizes as $key => $dimensions) {
    //             [$width, $height] = $dimensions;

    //             // Load the original image
    //             $src = imagecreatefromjpeg($filePath);
    //             if (!$src) {
    //                 Log::error('Failed to load image from path: ' . $filePath);
    //                 continue;
    //             }

    //             // Create a new true color image with the new dimensions
    //             $dst = imagecreatetruecolor($width, $height);
    //             if (!$dst) {
    //                 Log::error('Failed to create true color image for size: ' . $key);
    //                 continue;
    //             }

    //             // Resample the original image into the new image
    //             if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src))) {
    //                 Log::error('Failed to resample image for size: ' . $key);
    //             }

    //             // Save the resized image
    //             $resizedImagePath = $publicProductsPath . '/' . $fileBaseName . '-' . $width . 'x' . $height . '.webp';
    //             if (!imagewebp($dst, $resizedImagePath)) {
    //                 Log::error('Failed to save resized image at path: ' . $resizedImagePath);
    //             } else {
    //                 Log::info('Saved resized image at path: ' . $resizedImagePath);
    //             }

    //             // Free up memory
    //             imagedestroy($src);
    //             imagedestroy($dst);
    //         }

    //         // Generate the URL for the saved image
    //         return url('storage/products/' . $fileName);
    //     } else {
    //         Log::error('Failed to download image from URL: ' . $url);
    //         return null;
    //     }
    // }









    // protected function uploadImageFromURL(?string $url): ?string
    // {
    //     // Directory within public directory
    //     $productsDirectory = 'storage/products';

    //     // Ensure products directory exists only if it doesn't already
    //     $publicProductsPath = public_path($productsDirectory);
    //     if (!is_dir($publicProductsPath)) {
    //         // Create the directory only if it doesn't exist
    //         mkdir($publicProductsPath, 0755, true);
    //     }

    //     // Fetch the image content from the URL
    //     $imageContents = file_get_contents($url);

    //     if ($imageContents !== false) {
    //         // Sanitize the file name
    //         $fileNameWithQuery = basename(parse_url($url, PHP_URL_PATH));
    //         $fileName = preg_replace('/\?.*/', '', $fileNameWithQuery); // Remove query parameters
    //         $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME); // Get base name without extension

    //         // Save the original image
    //         $filePath = $publicProductsPath . '/' . $fileName;
    //         if (file_put_contents($filePath, $imageContents) === false) {
    //             Log::error('Failed to write image to file: ' . $filePath);
    //             return null;
    //         }

    //         // Get the MIME type of the image
    //         $imageInfo = getimagesize($filePath);
    //         if (!$imageInfo) {
    //             Log::error('Failed to get image size for path: ' . $filePath);
    //             return null;
    //         }
    //         $mimeType = $imageInfo['mime'];

    //         // Define the image creation function based on MIME type
    //         $imageCreateFunction = null;
    //         $imageSaveFunction = null;

    //         switch ($mimeType) {
    //             case 'image/jpeg':
    //                 $imageCreateFunction = 'imagecreatefromjpeg';
    //                 $imageSaveFunction = 'imagejpeg';
    //                 break;
    //             case 'image/png':
    //                 $imageCreateFunction = 'imagecreatefrompng';
    //                 $imageSaveFunction = 'imagepng';
    //                 break;
    //             case 'image/gif':
    //                 $imageCreateFunction = 'imagecreatefromgif';
    //                 $imageSaveFunction = 'imagegif';
    //                 break;
    //             default:
    //                 Log::error('Unsupported image type: ' . $mimeType);
    //                 return null;
    //         }

    //         foreach (['thumb' => [150, 150], 'medium' => [300, 300], 'large' => [790, 510]] as $key => $dimensions) {
    //             [$width, $height] = $dimensions;

    //             // Load the original image
    //             $src = $imageCreateFunction($filePath);
    //             if (!$src) {
    //                 Log::error('Failed to load image from path: ' . $filePath);
    //                 continue;
    //             }

    //             // Create a new true color image with the new dimensions
    //             $dst = imagecreatetruecolor($width, $height);
    //             if (!$dst) {
    //                 Log::error('Failed to create true color image for size: ' . $key);
    //                 continue;
    //             }

    //             // Resample the original image into the new image
    //             if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src))) {
    //                 Log::error('Failed to resample image for size: ' . $key);
    //             }

    //             // Save the resized image
    //             $resizedImagePath = $publicProductsPath . '/' . $fileBaseName . '-' . $width . 'x' . $height . '.webp';
    //             if (!$imageSaveFunction($dst, $resizedImagePath)) {
    //                 Log::error('Failed to save resized image at path: ' . $resizedImagePath);
    //             } else {
    //                 Log::info('Saved resized image at path: ' . $resizedImagePath);
    //             }

    //             // Free up memory
    //             imagedestroy($src);
    //             imagedestroy($dst);
    //         }

    //         // Generate the URL for the saved image
    //         return url('storage/products/' . $fileName);
    //     } else {
    //         Log::error('Failed to download image from URL: ' . $url);
    //         return null;
    //     }
    // }








    protected function uploadImageFromURL(?string $url): ?string
    {
        // Check if URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::error('Invalid URL provided: ' . $url);
            return null;
        }

        // Directory within public directory
        $productsDirectory = 'storage/products';

        // Ensure products directory exists only if it doesn't already
        $publicProductsPath = public_path($productsDirectory);
        if (!is_dir($publicProductsPath)) {
            // Create the directory only if it doesn't exist
            mkdir($publicProductsPath, 0755, true);
        }

        // Fetch the image content from the URL
        $imageContents = file_get_contents($url); // Use without error suppression to capture errors

        if ($imageContents === false) {
            Log::error('Failed to download image from URL: ' . $url);
            return null;
        }

        // Sanitize the file name
        $fileNameWithQuery = basename(parse_url($url, PHP_URL_PATH));
        $fileName = preg_replace('/\?.*/', '', $fileNameWithQuery); // Remove query parameters
        $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME); // Get base name without extension

        // Save the original image
        $filePath = $publicProductsPath . '/' . $fileName;
        if (file_put_contents($filePath, $imageContents) === false) {
            Log::error('Failed to write image to file: ' . $filePath);
            return null;
        }

        // Get the MIME type of the image
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            Log::error('Failed to get image size for path: ' . $filePath);
            return null;
        }
        $mimeType = $imageInfo['mime'];
        Log::info('MIME type of the image: ' . $mimeType); // Log the MIME type

        // Define the image creation function based on MIME type
        $imageCreateFunction = null;
        $imageSaveFunction = null;

        switch ($mimeType) {
            case 'image/jpeg':
                $imageCreateFunction = 'imagecreatefromjpeg';
                $imageSaveFunction = 'imagejpeg';
                break;
            case 'image/png':
                $imageCreateFunction = 'imagecreatefrompng';
                $imageSaveFunction = 'imagepng';
                break;
            case 'image/gif':
                $imageCreateFunction = 'imagecreatefromgif';
                $imageSaveFunction = 'imagegif';
                break;
            default:
                Log::error('Unsupported image type: ' . $mimeType);
                return null;
        }

        foreach (['thumb' => [150, 150], 'medium' => [300, 300], 'large' => [790, 510]] as $key => $dimensions) {
            [$width, $height] = $dimensions;

            // Load the original image
            $src = $imageCreateFunction($filePath);
            if (!$src) {
                Log::error('Failed to load image from path: ' . $filePath);
                continue;
            }

            // Create a new true color image with the new dimensions
            $dst = imagecreatetruecolor($width, $height);
            if (!$dst) {
                Log::error('Failed to create true color image for size: ' . $key);
                continue;
            }

            // Resample the original image into the new image
            if (!imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src))) {
                Log::error('Failed to resample image for size: ' . $key);
            }

            // Save the resized image
            $resizedImagePath = $publicProductsPath . '/' . $fileBaseName . '-' . $width . 'x' . $height . '.webp';
            if (!$imageSaveFunction($dst, $resizedImagePath)) {
                Log::error('Failed to save resized image at path: ' . $resizedImagePath);
            } else {
                Log::info('Saved resized image at path: ' . $resizedImagePath);
            }

            // Free up memory
            imagedestroy($src);
            imagedestroy($dst);
        }

        // Generate the URL for the saved image
        return url('storage/products/' . $fileName);
    }















    // protected function uploadImageFromURL(?string $url): ?string
    // {
    //     // Directory within public directory
    //     $productsDirectory = 'storage/products';

    //     // Ensure products directory exists only if it doesn't already
    //     $publicProductsPath = public_path($productsDirectory);
    //     if (!is_dir($publicProductsPath)) {
    //         // Create the directory only if it doesn't exist
    //         mkdir($publicProductsPath, 0755, true);
    //     }

    //     // Fetch the image content from the URL
    //     $imageContents = file_get_contents($url);

    //     if ($imageContents !== false) {
    //         // Sanitize the file name
    //         $fileNameWithQuery = basename(parse_url($url, PHP_URL_PATH));
    //         $fileName = preg_replace('/\?.*/', '', $fileNameWithQuery); // Remove query parameters
    //         $filePath = $publicProductsPath . '/' . $fileName;

    //         // Save the image content directly to the public/storage/products directory
    //         if (file_put_contents($filePath, $imageContents) === false) {
    //             Log::error('Failed to write image to file: ' . $filePath);
    //             return null;
    //         }

    //         // Generate the URL for the saved image
    //         return url('storage/products/' . $fileName);
    //     } else {
    //         Log::error('Failed to download image from URL: ' . $url);
    //         return null;
    //     }
    // }
    // protected function uploadImageFromURL(?string $url): ?string
    // {
    //     // Directory within public directory
    //     $productsDirectory = 'storage/products';

    //     // Ensure products directory exists only if it doesn't already
    //     $publicProductsPath = public_path($productsDirectory);
    //     if (!is_dir($publicProductsPath)) {
    //         // Create the directory only if it doesn't exist
    //         mkdir($publicProductsPath, 0755, true);
    //     }

    //     // Fetch the image content from the URL
    //     $imageContents = file_get_contents($url);

    //     if ($imageContents !== false) {
    //         // Sanitize the file name
    //         $fileNameWithQuery = basename(parse_url($url, PHP_URL_PATH));
    //         $fileName = preg_replace('/\?.*/', '', $fileNameWithQuery); // Remove query parameters
    //         $filePath = $publicProductsPath . '/' . $fileName;

    //         // Save the image content directly to the public/storage/products directory
    //         if (file_put_contents($filePath, $imageContents) === false) {
    //             Log::error('Failed to write image to file: ' . $filePath);
    //             return null;
    //         }

    //         // Generate the URL for the saved image
    //         return url('storage/products/' . $fileName);
    //     } else {
    //         Log::error('Failed to download image from URL: ' . $url);
    //         return null;
    //     }
    // }



    // protected function uploadImageFromURL(?string $url): ?string
    // {
    //     // Check if the URL is valid
    //     if (filter_var($url, FILTER_VALIDATE_URL)) {
    //         // Upload the external URL using RvMedia
    //         $result = RvMedia::uploadFromUrl($url, 0, 'products');

    //         // Check if the upload was successful
    //         if (!$result['error']) {
    //             // Return the URL of the uploaded image
    //             return $result['data']->url;
    //         } else {
    //             // Log the error message if the upload failed
    //             Log::error('Failed to upload external image: ' . $result['message']);
    //             return null;
    //         }
    //     } else {
    //         // Log an error message if the URL is not valid
    //         Log::error('Provided URL is not valid: ' . $url);
    //         return null;
    //     }
    // }


}
