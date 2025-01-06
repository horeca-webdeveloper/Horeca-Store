<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryDateToEcProductsTable extends Migration
{
    public function up()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->date('delivery_date')->nullable(); // Add the delivery_date column
        });
    }

    public function down()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->dropColumn('delivery_date'); // Remove the column if rolling back
        });
    }
}
