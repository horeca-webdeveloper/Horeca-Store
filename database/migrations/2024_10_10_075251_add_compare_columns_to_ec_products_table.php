<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompareColumnsToEcProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            // Column for Frequently Bought Together (this can be JSON or text)
            $table->json('frequently_bought_together')->nullable();

            // Column for Compare Type with default values ['good', 'better', 'best']
            $table->json('compare_type')->nullable();

            // Column for Compare Products (SKUs for the good, better, best products)
            $table->json('compare_products')->nullable();

            // Column for Refund Policy (15 days, 90 days, non-refundable)
            $table->enum('refund', ['15 days', '90 days', 'non-refundable'])->nullable();
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
            $table->dropColumn('frequently_bought_together');
            $table->dropColumn('compare_type');
            $table->dropColumn('compare_products');
            $table->dropColumn('refund');
        });
    }
}
