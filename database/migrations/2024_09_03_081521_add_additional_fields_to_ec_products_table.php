<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalFieldsToEcProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ec_products', function (Blueprint $table) {
            $table->string('handle')->nullable();
            $table->integer('variant_grams')->nullable();
            $table->string('variant_inventory_tracker')->nullable();
            $table->integer('variant_inventory_quantity')->nullable();
            $table->string('variant_inventory_policy')->nullable();
            $table->string('variant_fulfillment_service')->nullable();
            $table->boolean('variant_requires_shipping')->nullable();
            $table->string('variant_barcode')->nullable();
            $table->boolean('gift_card')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->string('google_shopping_category')->nullable();
            $table->string('google_shopping_gender')->nullable();
            $table->string('google_shopping_age_group')->nullable();
            $table->string('google_shopping_mpn')->nullable();
            $table->string('google_shopping_condition')->nullable();
            $table->string('google_shopping_custom_product')->nullable();
            $table->string('google_shopping_custom_label_0')->nullable();
            $table->string('google_shopping_custom_label_1')->nullable();
            $table->string('google_shopping_custom_label_2')->nullable();
            $table->string('google_shopping_custom_label_3')->nullable();
            $table->string('google_shopping_custom_label_4')->nullable();
            $table->integer('box_quantity')->nullable();
            $table->text('technical_table')->nullable();
            $table->text('technical_spec')->nullable();
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
            $table->dropColumn([
                'handle',
                'variant_grams',
                'variant_inventory_tracker',
                'variant_inventory_quantity',
                'variant_inventory_policy',
                'variant_fulfillment_service',
                'variant_requires_shipping',
                'variant_barcode',
                'gift_card',
                'seo_title',
                'seo_description',
                'google_shopping_category',
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
                'box_quantity',
                'technical_table',
                'technical_spec',
            ]);
        });
    }
}
