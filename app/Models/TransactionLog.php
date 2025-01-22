<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
	protected $fillable = [
		'module',
		'action',
		'identifier',
		'change_obj',
		'description',
		'created_by',
	];

	public $timestamps = ["created_at"]; //only want to used created_at column
	const UPDATED_AT = null;
}