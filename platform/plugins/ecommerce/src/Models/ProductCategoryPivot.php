<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;

class ProductCategoryPivot extends BaseModel
{
	protected $table = 'ec_product_category_product';
	public $timestamps = false;

	// Optionally, you can define the `created_at` column for this model:
	protected $fillable = ['product_id', 'category_id', 'created_at'];
	}