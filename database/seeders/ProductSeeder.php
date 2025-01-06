<?php

namespace Database\Seeders;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Models\BaseModel;
use Botble\Base\Supports\BaseSeeder;
use Botble\Ecommerce\Enums\ProductTypeEnum;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderAddress;
use Botble\Ecommerce\Models\OrderHistory;
use Botble\Ecommerce\Models\OrderProduct;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductFile;
use Botble\Ecommerce\Models\ProductVariation;
use Botble\Ecommerce\Models\ProductVariationItem;
use Botble\Ecommerce\Models\Shipment;
use Botble\Ecommerce\Models\ShipmentHistory;
use Botble\Ecommerce\Models\Wishlist;
use Botble\Ecommerce\Services\Products\StoreProductService;
use Botble\Payment\Models\Payment;
use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('products');

        Product::query()->truncate();
        DB::table('ec_product_with_attribute_set')->truncate();
        DB::table('ec_product_variations')->truncate();
        DB::table('ec_product_variation_items')->truncate();
        DB::table('ec_product_collection_products')->truncate();
        DB::table('ec_product_label_products')->truncate();
        DB::table('ec_product_category_product')->truncate();
        DB::table('ec_product_related_relations')->truncate();
        Wishlist::query()->truncate();
        Order::query()->truncate();
        OrderAddress::query()->truncate();
        OrderProduct::query()->truncate();
        OrderHistory::query()->truncate();
        Shipment::query()->truncate();
        ShipmentHistory::query()->truncate();
        Payment::query()->truncate();
        ProductFile::query()->truncate();

        $faker = $this->fake();

        $products = [
            [
                'name' => 'Dual Camera 20MP',
                'price' => 80.25,
                'is_featured' => true,
            ],
            [
                'name' => 'Smart Watches',
                'price' => 40.5,
                'sale_price' => 35,
                'is_featured' => true,
            ],
            [
                'name' => 'Beat Headphone',
                'price' => 20,
                'is_featured' => true,
            ],
            [
                'name' => 'Red & Black Headphone',
                'price' => $faker->numberBetween(500, 600),
                'is_featured' => true,
            ],
            [
                'name' => 'Smart Watch External',
                'price' => $faker->numberBetween(700, 900),
                'is_featured' => true,
            ],
            [
                'name' => 'Nikon HD camera',
                'price' => $faker->numberBetween(400, 500),
                'is_featured' => true,
            ],
            [
                'name' => 'Audio Equipment',
                'price' => $faker->numberBetween(500, 600),
                'is_featured' => true,
            ],
            [
                'name' => 'Smart Televisions',
                'price' => $faker->numberBetween(1100, 1300),
                'sale_price' => $faker->numberBetween(800, 1000),
                'is_featured' => true,
            ],
            [
                'name' => 'Samsung Smart Phone',
                'price' => $faker->numberBetween(500, 600),
                'is_featured' => true,
            ],
            [
                'name' => 'Herschel Leather Duffle Bag In Brown Color',
                'price' => $faker->numberBetween(1100, 1300),
                'sale_price' => $faker->numberBetween(800, 1000),
            ],
            [
                'name' => 'Xbox One Wireless Controller Black Color',
                'price' => $faker->numberBetween(1100, 1300),
                'sale_price' => $faker->numberBetween(500, 700),
            ],
            [
                'name' => 'EPSION Plaster Printer',
                'price' => $faker->numberBetween(500, 600),
            ],
            [
                'name' => 'Sound Intone I65 Earphone White Version',
                'price' => $faker->numberBetween(500, 600),
            ],
            [
                'name' => 'B&O Play Mini Bluetooth Speaker',
                'price' => $faker->numberBetween(500, 600),
            ],
            [
                'name' => 'Apple MacBook Air Retina 13.3-Inch Laptop',
                'price' => $faker->numberBetween(500, 600),
            ],
            [
                'name' => 'Apple MacBook Air Retina 12-Inch Laptop',
                'price' => $faker->numberBetween(500, 600),
            ],
            [
                'name' => 'Samsung Gear VR Virtual Reality Headset',
                'price' => $faker->numberBetween(500, 600),
            ],
            [
                'name' => 'Aveeno Moisturizing Body Shower 450ml',
                'price' => $faker->numberBetween(900, 1300),
                'sale_price' => $faker->numberBetween(200, 700),
            ],
            [
                'name' => 'NYX Beauty Couton Pallete Makeup 12',
                'price' => $faker->numberBetween(900, 1300),
                'sale_price' => $faker->numberBetween(300, 800),
            ],
            [
                'name' => 'NYX Beauty Couton Pallete Makeup 12',
                'price' => $faker->numberBetween(700, 1000),
                'sale_price' => $faker->numberBetween(400, 700),
            ],
            [
                'name' => 'MVMTH Classical Leather Watch In Black',
                'price' => $faker->numberBetween(600, 1000),
                'sale_price' => $faker->numberBetween(200, 500),
            ],
            [
                'name' => 'Baxter Care Hair Kit For Bearded Mens',
                'price' => $faker->numberBetween(400, 700),
                'sale_price' => $faker->numberBetween(100, 300),
            ],
            [
                'name' => 'Ciate Palemore Lipstick Bold Red Color',
                'price' => $faker->numberBetween(500, 1300),
                'sale_price' => $faker->numberBetween(200, 400),
            ],
        ];

        foreach ($products as $key => $item) {
            $item['description'] = '<ul><li> Unrestrained and portable active stereo speaker</li>
            <li> Free from the confines of wires and chords</li>
            <li> 20 hours of portable capabilities</li>
            <li> Double-ended Coil Cord with 3.5mm Stereo Plugs Included</li>
            <li> 3/4″ Dome Tweeters: 2X and 4″ Woofer: 1X</li></ul>';
            $item['content'] = '<p>Short Hooded Coat features a straight body, large pockets with button flaps, ventilation air holes, and a string detail along the hemline. The style is completed with a drawstring hood, featuring Rains&rsquo; signature built-in cap. Made from waterproof, matte PU, this lightweight unisex rain jacket is an ode to nostalgia through its classic silhouette and utilitarian design details.</p>
                                <p>- Casual unisex fit</p>

                                <p>- 64% polyester, 36% polyurethane</p>

                                <p>- Water column pressure: 4000 mm</p>

                                <p>- Model is 187cm tall and wearing a size S / M</p>

                                <p>- Unisex fit</p>

                                <p>- Drawstring hood with built-in cap</p>

                                <p>- Front placket with snap buttons</p>

                                <p>- Ventilation under armpit</p>

                                <p>- Adjustable cuffs</p>

                                <p>- Double welted front pockets</p>

                                <p>- Adjustable elastic string at hempen</p>

                                <p>- Ultrasonically welded seams</p>

                                <p>This is a unisex item, please check our clothing &amp; footwear sizing guide for specific Rains jacket sizing information. RAINS comes from the rainy nation of Denmark at the edge of the European continent, close to the ocean and with prevailing westerly winds; all factors that contribute to an average of 121 rain days each year. Arising from these rainy weather conditions comes the attitude that a quick rain shower may be beautiful, as well as moody- but first and foremost requires the right outfit. Rains focus on the whole experience of going outside on rainy days, issuing an invitation to explore even in the most mercurial weather.</p>';
            $item['status'] = BaseStatusEnum::PUBLISHED;
            $item['sku'] = 'SW-' . $faker->numberBetween(100, 200);
            $item['brand_id'] = $faker->numberBetween(1, 7);
            $item['views'] = $faker->numberBetween(1000, 200000);
            $item['quantity'] = $faker->numberBetween(10, 20);
            $item['length'] = $faker->numberBetween(10, 20);
            $item['width'] = $faker->numberBetween(10, 20);
            $item['height'] = $faker->numberBetween(10, 20);
            $item['weight'] = $faker->numberBetween(500, 900);
            $item['with_storehouse_management'] = true;

            // Support Digital Product
            $productName = $item['name'];
            if ($key % 4 == 0) {
                $item['product_type'] = ProductTypeEnum::DIGITAL;
                $item['name'] .= ' (' . ProductTypeEnum::DIGITAL()->label() . ')';
            }

            $images = [
                'products/' . ($key + 1) . '.jpg',
            ];

            for ($i = 1; $i <= 3; $i++) {
                if (File::exists(database_path('seeders/files/products/' . ($key + 1) . '-' . $i . '.jpg'))) {
                    $images[] = 'products/' . ($key + 1) . '-' . $i . '.jpg';
                }
            }

            $item['images'] = json_encode($images);

            $product = Product::query()->create($item);

            $product->productCollections()->sync([$faker->numberBetween(1, 3)]);

            if (! BaseModel::determineIfUsingUuidsForId() && $product->id % 3 == 0) {
                $product->productLabels()->sync([$faker->numberBetween(1, 3)]);
            }

            $product->categories()->sync([
                $faker->numberBetween(1, 37),
                $faker->numberBetween(1, 37),
                $faker->numberBetween(1, 37),
                $faker->numberBetween(15, 37),
            ]);

            $product->tags()->sync([
                $faker->numberBetween(1, 6),
                $faker->numberBetween(1, 6),
                $faker->numberBetween(1, 6),
            ]);

            $product->taxes()->sync([1]);

            Slug::query()->create([
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'key' => Str::slug($productName),
                'prefix' => SlugHelper::getPrefix(Product::class),
            ]);

            MetaBox::saveMetaBoxData(
                $product,
                'faq_schema_config',
                json_decode(
                    '[[{"key":"question","value":"What Shipping Methods Are Available?"},{"key":"answer","value":"Ex Portland Pitchfork irure mustache. Eutra fap before they sold out literally. Aliquip ugh bicycle rights actually mlkshk, seitan squid craft beer tempor."}],[{"key":"question","value":"Do You Ship Internationally?"},{"key":"answer","value":"Hoodie tote bag mixtape tofu. Typewriter jean shorts wolf quinoa, messenger bag organic freegan cray."}],[{"key":"question","value":"How Long Will It Take To Get My Package?"},{"key":"answer","value":"Swag slow-carb quinoa VHS typewriter pork belly brunch, paleo single-origin coffee Wes Anderson. Flexitarian Pitchfork forage, literally paleo fap pour-over. Wes Anderson Pinterest YOLO fanny pack meggings, deep v XOXO chambray sustainable slow-carb raw denim church-key fap chillwave Etsy. +1 typewriter kitsch, American Apparel tofu Banksy Vice."}],[{"key":"question","value":"What Payment Methods Are Accepted?"},{"key":"answer","value":"Fashion axe DIY jean shorts, swag kale chips meh polaroid kogi butcher Wes Anderson chambray next level semiotics gentrify yr. Voluptate photo booth fugiat Vice. Austin sed Williamsburg, ea labore raw denim voluptate cred proident mixtape excepteur mustache. Twee chia photo booth readymade food truck, hoodie roof party swag keytar PBR DIY."}],[{"key":"question","value":"Is Buying On-Line Safe?"},{"key":"answer","value":"Art party authentic freegan semiotics jean shorts chia cred. Neutra Austin roof party Brooklyn, synth Thundercats swag 8-bit photo booth. Plaid letterpress leggings craft beer meh ethical Pinterest."}]]',
                    true
                )
            );
        }

        foreach ($products as $key => $item) {
            $product = Product::skip($key)->first();
            $product->productAttributeSets()->sync([1, 2]);

            $product->crossSales()->sync([
                $this->random(1, 20, [$product->id]),
                $this->random(1, 20, [$product->id]),
                $this->random(1, 20, [$product->id]),
                $this->random(1, 20, [$product->id]),
                $this->random(1, 20, [$product->id]),
                $this->random(1, 20, [$product->id]),
                $this->random(1, 20, [$product->id]),
            ]);

            for ($j = 0; $j < $faker->numberBetween(1, 5); $j++) {
                $variation = Product::query()->create([
                    'name' => $product->name,
                    'status' => BaseStatusEnum::PUBLISHED,
                    'sku' => $product->sku . '-A' . $j,
                    'quantity' => $product->quantity,
                    'weight' => $product->weight,
                    'height' => $product->height,
                    'width' => $product->width,
                    'length' => $product->length,
                    'price' => $product->price,
                    'sale_price' => is_int(
                        $product->id
                    ) && $product->id % 4 == 0 ? ($product->price - $product->price * $faker->numberBetween(
                        10,
                        30
                    ) / 100) : null,
                    'brand_id' => $product->brand_id,
                    'with_storehouse_management' => $product->with_storehouse_management,
                    'is_variation' => true,
                    'images' => json_encode([$product->images[$j] ?? Arr::first($product->images)]),
                    'product_type' => $product->product_type,
                ]);

                $productVariation = ProductVariation::query()->create([
                    'product_id' => $variation->id,
                    'configurable_product_id' => $product->id,
                    'is_default' => $j == 0,
                ]);

                if ($productVariation->is_default) {
                    $product->update([
                        'sku' => $variation->sku,
                        'sale_price' => $variation->sale_price,
                    ]);
                }

                ProductVariationItem::query()->create([
                    'attribute_id' => $faker->numberBetween(1, 5),
                    'variation_id' => $productVariation->id,
                ]);

                ProductVariationItem::query()->create([
                    'attribute_id' => $faker->numberBetween(6, 10),
                    'variation_id' => $productVariation->id,
                ]);

                if ($product->isTypeDigital()) {
                    foreach ($product->images as $img) {
                        $productFile = database_path('seeders/files/' . $img);

                        if (! File::isFile($productFile)) {
                            continue;
                        }

                        $fileUpload = new UploadedFile(
                            $productFile,
                            Str::replace('products/', '', $img),
                            'image/jpeg',
                            null,
                            true
                        );
                        $productFileData = app(StoreProductService::class)->saveProductFile($fileUpload);
                        $variation->productFiles()->create($productFileData);
                    }
                }
            }
        }
    }
}
