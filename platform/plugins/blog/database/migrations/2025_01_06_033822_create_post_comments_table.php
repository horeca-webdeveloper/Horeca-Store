<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
	public function up(): void
	{
		Schema::create('post_comments', function (Blueprint $table) {
			$table->id();
			$table->integer('post_id');
			$table->integer('parent_id')->nullable();
			$table->longText('comment');
			$table->integer('created_by');
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('post_comments');
	}
};
