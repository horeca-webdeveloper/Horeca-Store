<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;
use Botble\ACL\Models\User;

class ProductGoogleShoppingCategory extends BaseModel
{
	protected $table = 'product_google_shopping_categories';

	protected $fillable = [
		'parent_id',
		'name',
		'created_by',
		'updated_by'
	];

	// Relationship to itself (parent-child relationship)
	public function parent()
	{
		return $this->belongsTo(ProductGoogleShoppingCategory::class, 'parent_id');
	}

	// Relationship to child categories
	public function children()
	{
		return $this->hasMany(ProductGoogleShoppingCategory::class, 'parent_id');
	}

	// Relationship to the user who created the category
	public function createdBy()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	// Relationship to the user who last updated the category
	public function updatedBy()
	{
		return $this->belongsTo(User::class, 'updated_by');
	}
}
