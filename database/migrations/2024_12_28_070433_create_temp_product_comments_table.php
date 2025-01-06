<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('temp_product_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temp_product_id')->constrained()->onDelete('cascade');
            $table->string('comment_type');
            $table->text('highlighted_text');
            $table->text('comment');
            $table->string('status');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_product_comments');
    }
};
