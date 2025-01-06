<?php

use Carbon\Carbon;

app()->booted(function () {
    theme_option()
        ->setField([
            'id' => 'copyright',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'text',
            'label' => __('Copyright'),
            'attributes' => [
                'name' => 'copyright',
                'value' => sprintf('© %s Company Name. All right reserved.', Carbon::now()->year),
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Change copyright'),
                    'data-counter' => 250,
                ],
            ],
            'helper' => __('Copyright on footer of site'),
        ])
        ->setField([
            'id' => 'logo_height',
            'section_id' => 'opt-text-subsection-logo',
            'type' => 'number',
            'label' => __('Logo height (px)'),
            'attributes' => [
                'name' => 'logo_height',
                'value' => 40,
                'options' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 150,
                ],
            ],
            'helper' => __('Set the height of the logo in pixels. The default value is 40px.'),
        ])
        ->setSection([
            'title' => __('Style'),
            'desc' => __('Style of theme'),
            'id' => 'opt-text-subsection-style',
            'subsection' => true,
            'icon' => 'ti ti-brush',
        ])
        ->setField([
            'id' => 'primary_font',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'googleFonts',
            'label' => __('Primary font'),
            'attributes' => [
                'name' => 'primary_font',
                'value' => 'Work Sans',
            ],
        ])
        ->setField([
            'id' => 'primary_color',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'customColor',
            'label' => __('Primary color'),
            'attributes' => [
                'name' => 'primary_color',
                'value' => '#fcb800',
            ],
        ])
        ->setField([
            'id' => 'secondary_color',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'customColor',
            'label' => __('Secondary color'),
            'attributes' => [
                'name' => 'secondary_color',
                'value' => '#222222',
            ],
        ])
        ->setField([
            'id' => 'header_text_color',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'customColor',
            'label' => __('Header text color'),
            'attributes' => [
                'name' => 'header_text_color',
                'value' => '#000',
            ],
        ])
        ->setField([
            'id' => 'header_text_hover_color',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'customColor',
            'label' => __('Header text hover color'),
            'attributes' => [
                'name' => 'header_text_hover_color',
                'value' => '#fff',
            ],
        ])
        ->setField([
            'id' => 'header_text_accent_color',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'customColor',
            'label' => __('Header text accent color'),
            'attributes' => [
                'name' => 'header_text_accent_color',
                'value' => '#000',
            ],
        ])
        ->setField([
            'id' => 'header_button_background_color',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'customColor',
            'label' => __('Header button background color'),
            'attributes' => [
                'name' => 'header_button_background_color',
                'value' => '#000',
            ],
        ])
        ->setField([
            'id' => 'header_button_text_color',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'customColor',
            'label' => __('Header button text color'),
            'attributes' => [
                'name' => 'header_button_text_color',
                'value' => '#fff',
            ],
        ])
        ->setField([
            'id' => 'button_text_color',
            'section_id' => 'opt-text-subsection-style',
            'type' => 'customColor',
            'label' => __('Button text color'),
            'attributes' => [
                'name' => 'button_text_color',
                'value' => '#000',
            ],
        ])
        ->setField([
            'id' => 'preloader_enabled',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'customSelect',
            'label' => __('Enable Preloader?'),
            'attributes' => [
                'name' => 'preloader_enabled',
                'list' => [
                    'yes' => trans('core/base::base.yes'),
                    'no' => trans('core/base::base.no'),
                ],
                'value' => 'no',
                'options' => [
                    'class' => 'form-control',
                ],
            ],
        ])
        ->setField([
            'id' => 'enable_newsletter_popup',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'customSelect',
            'label' => __('Enable newsletter popup?'),
            'attributes' => [
                'name' => 'enable_newsletter_popup',
                'list' => [
                    'no' => trans('core/base::base.no'),
                    'yes' => trans('core/base::base.yes'),
                ],
                'value' => 'yes',
                'options' => [
                    'class' => 'form-control',
                ],
            ],
        ])
        ->setField([
            'id' => 'newsletter_image',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'mediaImage',
            'label' => __('Image for newsletter popup'),
            'attributes' => [
                'name' => 'newsletter_image',
                'value' => null,
            ],
        ])
        ->setField([
            'id' => 'newsletter_popup_title',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'text',
            'label' => __('Newsletter popup title (Default: Get 25% Discount)'),
            'attributes' => [
                'name' => 'newsletter_popup_title',
                'value' => null,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Newsletter popup title'),
                ],
            ],
        ])
        ->setField([
            'id' => 'newsletter_popup_description',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'text',
            'label' => __('Newsletter popup description (Default: Subscribe to the mailing list to receive updates on new arrivals, special offers and our promotions.)'),
            'attributes' => [
                'name' => 'newsletter_popup_description',
                'value' => null,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Newsletter popup description'),
                ],
            ],
        ])
        ->setField([
            'id' => 'newsletter_show_after_seconds',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'number',
            'label' => __('Newsletter popup delay time (seconds)'),
            'attributes' => [
                'name' => 'newsletter_show_after_seconds',
                'value' => 10,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Default: 10 (seconds)'),
                ],
            ],
        ])
        ->setField([
            'id' => 'welcome_message',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'text',
            'label' => __('Welcome message'),
            'attributes' => [
                'name' => 'welcome_message',
                'value' => null,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Welcome message'),
                    'data-counter' => 120,
                ],
            ],
        ])
        ->setField([
            'id' => 'sell_on_site_text',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'text',
            'label' => __('Sell on site text (default: Sell on Martfury)'),
            'attributes' => [
                'name' => 'sell_on_site_text',
                'value' => null,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Sell on site text'),
                    'data-counter' => 120,
                ],
            ],
        ])
        ->setField([
            'id' => 'hotline',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'text',
            'label' => __('Hotline'),
            'attributes' => [
                'name' => 'hotline',
                'value' => null,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Hotline'),
                    'data-counter' => 30,
                ],
            ],
        ])
        ->setField([
            'id' => 'address',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'text',
            'label' => __('Address'),
            'attributes' => [
                'name' => 'address',
                'value' => null,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Address'),
                    'data-counter' => 120,
                ],
            ],
        ])
        ->setField([
            'id' => 'email',
            'section_id' => 'opt-text-subsection-general',
            'type' => 'email',
            'label' => __('Email'),
            'attributes' => [
                'name' => 'email',
                'value' => null,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => __('Email'),
                    'data-counter' => 120,
                ],
            ],
        ])
        ->setField([
            'id' => 'payment_methods',
            'section_id' => 'opt-text-subsection-ecommerce',
            'type' => 'mediaImages',
            'label' => __('Accepted Payment methods'),
            'attributes' => [
                'name' => 'payment_methods[]',
                'values' => theme_option('payment_methods', []),
                'attributes' => [
                    'allow_thumb' => false,
                ],
            ],
        ])
        ->setField([
            'id' => 'payment_methods_link',
            'section_id' => 'opt-text-subsection-ecommerce',
            'type' => 'text',
            'label' => __('Accepted Payment methods link (optional)'),
            'attributes' => [
                'name' => 'payment_methods_link',
                'values' => null,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://...',
                    'data-counter' => 255,
                ],
            ],
        ])
        ->setField([
            'id' => 'show_featured_brands_on_products_page',
            'section_id' => 'opt-text-subsection-ecommerce',
            'type' => 'customSelect',
            'label' => __('Show featured brands on the products page?'),
            'attributes' => [
                'name' => 'show_featured_brands_on_products_page',
                'list' => [
                    'no' => trans('core/base::base.no'),
                    'yes' => trans('core/base::base.yes'),
                ],
                'value' => 'yes',
                'options' => [
                    'class' => 'form-control',
                ],
            ],
        ])
        ->setField([
            'id' => 'show_recommend_items_on_products_page',
            'section_id' => 'opt-text-subsection-ecommerce',
            'type' => 'customSelect',
            'label' => __('Show recommend items on the products page?'),
            'attributes' => [
                'name' => 'show_recommend_items_on_products_page',
                'list' => [
                    'no' => trans('core/base::base.no'),
                    'yes' => trans('core/base::base.yes'),
                ],
                'value' => 'yes',
                'options' => [
                    'class' => 'form-control',
                ],
            ],
        ])
        ->setSection([
            'title' => __('Product features'),
            'desc' => __('Product features'),
            'id' => 'opt-text-subsection-product-features',
            'subsection' => true,
            'icon' => 'ti ti-cube',
        ])
        ->setSection([
            'title' => __('Contact info boxes'),
            'desc' => __('Contact info boxes'),
            'id' => 'opt-text-subsection-contact-info-boxes',
            'subsection' => true,
            'icon' => 'ti ti-mail',
        ]);

    for ($i = 1; $i <= 5; $i++) {
        theme_option()
            ->setField([
                'id' => 'product_feature_' . $i . '_title',
                'section_id' => 'opt-text-subsection-product-features',
                'type' => 'text',
                'label' => __('Product feature title :number', ['number' => $i]),
                'attributes' => [
                    'name' => 'product_feature_' . $i . '_title',
                    'value' => null,
                    'options' => [
                        'class' => 'form-control',
                    ],
                ],
            ])
            ->setField([
                'id' => 'product_feature_' . $i . '_icon',
                'section_id' => 'opt-text-subsection-product-features',
                'type' => 'themeIcon',
                'label' => __('Product feature icon :number', ['number' => $i]),
                'attributes' => [
                    'name' => 'product_feature_' . $i . '_icon',
                    'value' => null,
                    'options' => [
                        'class' => 'form-control',
                    ],
                ],
            ]);
    }

    for ($i = 1; $i <= 6; $i++) {
        theme_option()
            ->setField([
                'id' => 'contact_info_box_' . $i . '_title',
                'section_id' => 'opt-text-subsection-contact-info-boxes',
                'type' => 'text',
                'label' => __('Contact box title :number', ['number' => $i]),
                'attributes' => [
                    'name' => 'contact_info_box_' . $i . '_title',
                    'value' => null,
                    'options' => [
                        'class' => 'form-control',
                    ],
                ],
            ])
            ->setField([
                'id' => 'contact_info_box_' . $i . '_subtitle',
                'section_id' => 'opt-text-subsection-contact-info-boxes',
                'type' => 'text',
                'label' => __('Contact box subtitle :number', ['number' => $i]),
                'attributes' => [
                    'name' => 'contact_info_box_' . $i . '_subtitle',
                    'value' => null,
                    'options' => [
                        'class' => 'form-control',
                    ],
                ],
            ])
            ->setField([
                'id' => 'contact_info_box_' . $i . '_details',
                'section_id' => 'opt-text-subsection-contact-info-boxes',
                'type' => 'text',
                'label' => __('Contact box detail :number', ['number' => $i]),
                'attributes' => [
                    'name' => 'contact_info_box_' . $i . '_details',
                    'value' => null,
                    'options' => [
                        'class' => 'form-control',
                    ],
                ],
            ]);
    }
});
