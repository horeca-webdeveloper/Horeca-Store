<?php

namespace Botble\Blog\Models;
use Botble\Base\Models\BaseModel;

class PostComment extends BaseModel
{
	protected $fillable = [
		'post_id',
		'parent_id',
		'comment',
		'created_by',
	];

	public function post()
	{
		return $this->belongsTo(Post::class);
	}
}
