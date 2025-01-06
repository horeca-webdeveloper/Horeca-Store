<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductIdToTempproductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tempproduct', function (Blueprint $table) {
            // Add the foreign key column
            $table->unsignedBigInteger('product_id')->nullable();

            // Define the foreign key constraint
            $table->foreign('product_id')
                  ->references('id')->on('ec_products')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tempproduct', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['product_id']);

            // Drop the column
            $table->dropColumn('product_id');
        });
    }
}
