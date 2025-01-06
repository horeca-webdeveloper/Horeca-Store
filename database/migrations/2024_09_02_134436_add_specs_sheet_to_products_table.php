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
        Schema::table('ec_products', function (Blueprint $table) {
            $table->string('specs_sheet_heading')->nullable();
            $table->json('specs_sheet')->nullable(); // To store the specs sheet data
        });
    }
    
    public function down()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->dropColumn(['specs_sheet_heading', 'specs_sheet']);
        });
    }
    
};
