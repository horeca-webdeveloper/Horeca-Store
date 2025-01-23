<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Botble\ACL\Models\User;

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

	public function createdBy()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	/**
	 * Prepare a date for array / JSON serialization.
	 *
	 * @param  \DateTimeInterface  $date
	 * @return string
	 */
	protected function serializeDate(\DateTimeInterface $date)
	{
		return $date->format('Y-m-d H:i:s');
	}
}