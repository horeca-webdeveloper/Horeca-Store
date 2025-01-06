<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecificationsTable extends Migration
{
    public function up()
    {
        Schema::create('specifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Foreign key for products
            $table->string('spec_name'); // Name of the specification
            $table->string('spec_value'); // Value of the specification
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('product_id')->references('id')->on('ec_products')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('specifications');
    }
}
