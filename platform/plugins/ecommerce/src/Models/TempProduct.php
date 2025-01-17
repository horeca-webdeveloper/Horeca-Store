<?php

namespace Botble\Ecommerce\Models;
use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;

class TempProduct extends BaseModel
{
	protected $table = 'temp_products';

	protected $fillable = [
		/***** Content role *****/
		'product_id',
		'name',
		'slug_id',
		'slug_model',
		'slug',
		'sku',
		'description',
		'content',
		'warranty_information',
		'specification_details',
		'seo_title',
		'seo_description',
		'category_ids',
		'product_type_ids',
		'google_shopping_category',
		'created_by',
		'role_id',
		'approved_by',
		'approval_status',
		'remarks',
		'rejection_count',
		/***** Content role *****/

		/***** pricing  role *****/
		'price',
		'sale_price',
		'unit_of_measurement_id',
		'start_date',
		'end_date',
		'cost_per_item',
		'margin',
		'with_storehouse_management',
		'quantity',
		'allow_checkout_when_out_of_stock',
		'stock_status',
		'discount',
		'store_id',
	   'variant_requires_shipping',
		'minimum_order_quantity',
		'refund',
		'delivery_days',
		'box_quantity',
		/***** pricing  role *****/


		'image', // Featured image
		'images',
		'order',
		'is_featured',
		'brand_id',
		'is_variation',
		'sale_type',
		'length',
		'width',
		'height',
		'weight',
		'tax_id',
		'views',
		'barcode',
	   // 'generate_license_code',
		'maximum_order_quantity',
		'specs_sheet_heading',
		'specs_sheet',
	];

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id');
	}

	public function comments()
	{
		return $this->hasMany(TempProductComment::class, 'temp_product_id')->orderBy('created_at', 'desc');
	}

	public function createdBy()
	{
		return $this->belongsTo(User::class, 'created_by');
	}
}
