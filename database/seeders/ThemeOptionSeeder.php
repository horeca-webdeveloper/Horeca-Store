<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Theme\Database\Traits\HasThemeOptionSeeder;
use Botble\Theme\Supports\ThemeSupport;
use Carbon\Carbon;

class ThemeOptionSeeder extends BaseSeeder
{
    use HasThemeOptionSeeder;

    public function run(): void
    {
        $this->uploadFiles('general');

        $options = [
            'site_title' => 'MartFury - Laravel Ecommerce system',
            'seo_description' => 'MartFury is a clean & modern Laravel Ecommerce System for multipurpose online stores. With design clean and trendy, MartFury will make your online store look more impressive and attractive to viewers.',
            'copyright' => '© %Y MartFury. All Rights Reserved.',
            'favicon' => 'general/favicon.png',
            'logo' => 'general/logo.png',
            'welcome_message' => 'Welcome to MartFury Online Shopping Store!',
            'address' => '502 New Street, Brighton VIC, Australia',
            'hotline' => '1800 97 97 69',
            'email' => 'contact@martfury.co',
            'newsletter_image' => 'general/newsletter.jpg',
            'homepage_id' => '1',
            'blog_page_id' => '6',
            'cookie_consent_message' => 'Your experience on this site will be improved by allowing cookies ',
            'cookie_consent_learn_more_url' => '/cookie-policy',
            'cookie_consent_learn_more_text' => 'Cookie Policy',
            'number_of_products_per_page' => 42,
            'product_feature_1_title' => 'Shipping worldwidth',
            'product_feature_1_icon' => 'icon-network',
            'product_feature_2_title' => 'Free 7-day return if eligible, so easy',
            'product_feature_2_icon' => 'icon-3d-rotate',
            'product_feature_3_title' => 'Supplier give bills for this product.',
            'product_feature_3_icon' => 'icon-receipt',
            'product_feature_4_title' => 'Pay online or when receiving goods',
            'product_feature_4_icon' => 'icon-credit-card',
            'contact_info_box_1_title' => 'Contact Directly',
            'contact_info_box_1_subtitle' => 'contact@martfury.com',
            'contact_info_box_1_details' => '(+004) 912-3548-07',
            'contact_info_box_2_title' => 'Headquarters',
            'contact_info_box_2_subtitle' => '17 Queen St, South bank, Melbourne 10560, Australia',
            'contact_info_box_2_details' => '',
            'contact_info_box_3_title' => 'Work With Us',
            'contact_info_box_3_subtitle' => 'Send your CV to our email:',
            'contact_info_box_3_details' => 'career@martfury.com',
            'contact_info_box_4_title' => 'Customer Service',
            'contact_info_box_4_subtitle' => 'customercare@martfury.com',
            'contact_info_box_4_details' => '(800) 843-2446',
            'contact_info_box_5_title' => 'Media Relations',
            'contact_info_box_5_subtitle' => 'media@martfury.com',
            'contact_info_box_5_details' => '(801) 947-3564',
            'contact_info_box_6_title' => 'Vendor Support',
            'contact_info_box_6_subtitle' => 'vendorsupport@martfury.com',
            'contact_info_box_6_details' => '(801) 947-3100',
            'number_of_cross_sale_product' => 7,
            'logo_in_the_checkout_page' => 'general/logo-dark.png',
            'logo_in_invoices' => 'general/logo-dark.png',
            'logo_vendor_dashboard' => 'general/logo-dark.png',
            'primary_font' => 'Work Sans',
            'payment_methods' => [
                'general/payment-method-1.jpg',
                'general/payment-method-2.jpg',
                'general/payment-method-3.jpg',
                'general/payment-method-4.jpg',
                'general/payment-method-5.jpg',
            ],
            'social_links' => ThemeSupport::getDefaultSocialLinksData(),
            'social_sharing' => ThemeSupport::getDefaultSocialSharingData(),
        ];

        $this->createThemeOptions($options);
    }
}
