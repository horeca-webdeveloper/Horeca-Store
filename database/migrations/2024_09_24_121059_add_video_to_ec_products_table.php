<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVideoToEcProductsTable extends Migration
{
    public function up()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->string('video_url')->nullable(); // Store the external link
            $table->string('video_path')->nullable(); // Store the local path if uploaded
        });
    }

    public function down()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'video_path']);
        });
    }
}
