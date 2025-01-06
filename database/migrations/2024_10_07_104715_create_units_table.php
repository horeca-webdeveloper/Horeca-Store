<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitsTable extends Migration
{
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->string('symbol')->unique(); // Measurement unit symbol (e.g., 'cm', 'm')
            $table->string('full_name'); // Full name of the unit (e.g., 'centimeters')
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('units');
    }
}
