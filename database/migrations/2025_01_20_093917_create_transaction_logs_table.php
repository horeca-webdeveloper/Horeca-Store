<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('transaction_logs', function (Blueprint $table) {
			$table->id();
			$table->string('module')->index();
			$table->string('action')->index();
			$table->string('identifier')->nullable();
			$table->string('status')->nullable();
			$table->longText('change_obj')->nullable();
			$table->longText('description')->nullable();
			$table->integer('created_by')->nullable();
			$table->timestamp('created_at')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('transaction_logs');
	}
};
