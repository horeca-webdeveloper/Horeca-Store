<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnsFromEcProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->dropColumn([
                'specs_sheet_heading',
                'specs_sheet',
                'generate_license_code',
                'google_shopping_gender',
                'google_shopping_age_group',
                'google_shopping_mpn',
                'google_shopping_condition',
                'google_shopping_custom_product',
                'google_shopping_custom_label_0',
                'google_shopping_custom_label_1',
                'google_shopping_custom_label_2',
                'google_shopping_custom_label_3',
                'google_shopping_custom_label_4',
                'technical_table',
                'technical_spec'
            ]);
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
            $table->string('specs_sheet_heading')->nullable();
            $table->json('specs_sheet')->nullable();
            $table->boolean('generate_license_code')->default(false);
            $table->string('google_shopping_gender')->nullable();
            $table->string('google_shopping_age_group')->nullable();
            $table->string('google_shopping_mpn')->nullable();
            $table->string('google_shopping_condition')->nullable();
            $table->boolean('google_shopping_custom_product')->nullable();
            $table->string('google_shopping_custom_label_0')->nullable();
            $table->string('google_shopping_custom_label_1')->nullable();
            $table->string('google_shopping_custom_label_2')->nullable();
            $table->string('google_shopping_custom_label_3')->nullable();
            $table->string('google_shopping_custom_label_4')->nullable();
            $table->text('technical_table')->nullable();
            $table->text('technical_spec')->nullable();
        });
    }
}
