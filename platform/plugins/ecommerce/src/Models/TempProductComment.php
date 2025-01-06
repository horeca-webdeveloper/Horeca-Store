<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;
use Botble\ACL\Models\User;

class TempProductComment extends BaseModel
{
	protected $fillable = [
		'temp_product_id',
		'comment_type',
		'highlighted_text',
		'comment',
		'status',
		'created_by',
		'updated_by',
	];

	public function tempProduct()
	{
		return $this->belongsTo(TempProduct::class, 'temp_product_id');
	}

	public function createdBy()
	{
		return $this->belongsTo(User::class, 'created_by');
	}
}
