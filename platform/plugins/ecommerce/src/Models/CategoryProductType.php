<?php

namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryProductType extends Model
{
	protected $fillable = [
		'category_id',
		'product_type_id',
		'created_by',
		'updated_by'
	];
}