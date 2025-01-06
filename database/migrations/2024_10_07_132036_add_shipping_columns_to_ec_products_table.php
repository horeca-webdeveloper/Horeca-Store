<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShippingColumnsToEcProductsTable extends Migration
{
    public function up()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            // Add units sold
            $table->integer('units_sold')->default(0);

            // Shipping weight and its option (Kg, g, pounds, etc.)
            $table->enum('shipping_weight_option', ['Kg', 'g', 'pounds', 'oz'])->nullable();
            $table->decimal('shipping_weight', 8, 2)->nullable();

            // Shipping dimension option (inch, mm, cm, etc.)
            $table->enum('shipping_dimension_option', ['inch', 'mm', 'cm'])->nullable();

            // Shipping dimensions with IDs referencing the units table
            $table->decimal('shipping_width', 8, 2)->nullable();
            $table->unsignedBigInteger('shipping_width_id')->nullable();  // Reference to units table

            $table->decimal('shipping_depth', 8, 2)->nullable();
            $table->unsignedBigInteger('shipping_depth_id')->nullable();  // Reference to units table

            $table->decimal('shipping_height', 8, 2)->nullable();
            $table->unsignedBigInteger('shipping_height_id')->nullable();  // Reference to units table

            $table->decimal('shipping_length', 8, 2)->nullable();
            $table->unsignedBigInteger('shipping_length_id')->nullable();  // Reference to units table

            // Foreign key constraints for the units table
            $table->foreign('shipping_width_id')->references('id')->on('units');
            $table->foreign('shipping_depth_id')->references('id')->on('units');
            $table->foreign('shipping_height_id')->references('id')->on('units');
            $table->foreign('shipping_length_id')->references('id')->on('units');
        });
    }

    public function down()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->dropForeign(['shipping_width_id']);
            $table->dropForeign(['shipping_depth_id']);
            $table->dropForeign(['shipping_height_id']);
            $table->dropForeign(['shipping_length_id']);

            $table->dropColumn([
                'units_sold',
                'shipping_weight_option',
                'shipping_weight',
                'shipping_dimension_option',
                'shipping_width', 'shipping_width_id',
                'shipping_depth', 'shipping_depth_id',
                'shipping_height', 'shipping_height_id',
                'shipping_length', 'shipping_length_id',
            ]);
        });
    }
}
