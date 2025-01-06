<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitOfMeasurementIdToEcProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_of_measurement_id')->nullable()->after('variant_requires_shipping'); // Foreign key column

            // Set up the foreign key relationship
            $table->foreign('unit_of_measurement_id')
                  ->references('id')->on('unit_of_measurements')
                  ->onDelete('set null'); // When a unit is deleted, set the value to null in ec_products
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            // Drop the foreign key and the column
            $table->dropForeign(['unit_of_measurement_id']);
            $table->dropColumn('unit_of_measurement_id');
        });
    }
}
