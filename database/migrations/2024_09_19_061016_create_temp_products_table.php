<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('temp_products', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Core product fields
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->text('content')->nullable();

            // Approval status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Product details
            $table->json('images')->nullable();
            $table->string('sku')->nullable();
            $table->integer('order')->default(0);
            $table->integer('quantity')->default(0);
            $table->boolean('allow_checkout_when_out_of_stock')->default(false);
            $table->boolean('with_storehouse_management')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->boolean('is_variation')->default(false);
            $table->string('sale_type')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->float('length')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->float('weight')->nullable();
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->integer('views')->default(0);

            // User and timestamp fields
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->timestamps();

            // Additional product fields
            $table->string('stock_status')->nullable();
            $table->string('image')->nullable();
            $table->string('product_type')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('cost_per_item', 15, 2)->nullable();
            $table->boolean('generate_license_code')->default(false);
            $table->integer('minimum_order_quantity')->default(1);
            $table->integer('maximum_order_quantity')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('specs_sheet_heading')->nullable();
            $table->text('specs_sheet')->nullable();
            $table->string('handle')->nullable();
            $table->float('variant_grams')->nullable();
            $table->string('variant_inventory_tracker')->nullable();
            $table->integer('variant_inventory_quantity')->nullable();
            $table->string('variant_inventory_policy')->nullable();
            $table->string('variant_fulfillment_service')->nullable();
            $table->boolean('variant_requires_shipping')->default(false);
            $table->string('variant_barcode')->nullable();
            $table->boolean('gift_card')->default(false);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('google_shopping_category')->nullable();
            $table->string('google_shopping_gender')->nullable();
            $table->string('google_shopping_age_group')->nullable();
            $table->string('google_shopping_mpn')->nullable();
            $table->string('google_shopping_condition')->nullable();
            $table->boolean('google_shopping_custom_product')->default(false);
            $table->string('google_shopping_custom_label_0')->nullable();
            $table->string('google_shopping_custom_label_1')->nullable();
            $table->string('google_shopping_custom_label_2')->nullable();
            $table->string('google_shopping_custom_label_3')->nullable();
            $table->string('google_shopping_custom_label_4')->nullable();
            $table->integer('box_quantity')->nullable();
            $table->text('technical_table')->nullable();
            $table->text('technical_spec')->nullable();

            // Reference to the original product in ec_products
            $table->unsignedBigInteger('product_id')->nullable();

            // Additional fields for approval process
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');

            // Foreign keys (if applicable)
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('ec_products')->onDelete('cascade');

            // Indexes for faster queries (optional)
            $table->index('sku');
            $table->index('status');
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('temp_products');
    }
}
