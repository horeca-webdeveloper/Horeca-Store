<?php

namespace Botble\Ecommerce\Models;
use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;

class TempProduct extends BaseModel
{
	protected $table = 'temp_products';

	protected $fillable = [
		'name',
		'description',
		'content',
		'image', // Featured image
		'images',
		'sku',
		'order',
		'quantity' => 'required|integer|min:10', // or any other rules that apply
		'allow_checkout_when_out_of_stock',
		'with_storehouse_management',
		'is_featured',
		'brand_id',
		'is_variation',
		'sale_type',
		'price',
		'sale_price',
		'start_date',
		'end_date',
		'length',
		'width',
		'height',
		'weight',
		'tax_id',
		'views',
		'stock_status',
		'barcode',
		'cost_per_item',
	   // 'generate_license_code',
		'minimum_order_quantity',
		'maximum_order_quantity',
		'specs_sheet_heading',
		'specs_sheet',
		'product_id',
		'approval_status',
		'box_quantity',
		'discount',
		'margin',
		'remarks',
		'rejection_count'
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
		return $this->belongsTo(User::class);
	}
}
