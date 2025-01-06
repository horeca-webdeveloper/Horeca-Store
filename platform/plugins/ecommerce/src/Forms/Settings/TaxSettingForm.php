<?php

namespace Botble\Ecommerce\Forms\Settings;

use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Http\Requests\Settings\TaxSettingRequest;
use Botble\Ecommerce\Models\Tax;
use Botble\Setting\Forms\SettingForm;

class TaxSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/ecommerce::setting.tax.tax_setting'))
            ->setSectionDescription(trans('plugins/ecommerce::setting.tax.tax_setting_description'))
            ->contentOnly()
            ->setValidatorClass(TaxSettingRequest::class)
            ->add('ecommerce_tax_enabled', OnOffCheckboxField::class, [
                'label' => trans('plugins/ecommerce::setting.tax.form.enable_tax'),
                'value' => EcommerceHelper::isTaxEnabled(),
                'wrapper' => [
                    'class' => 'mb-0',
                ],
                'attr' => [
                    'data-bb-toggle' => 'collapse',
                    'data-bb-target' => '.tax-settings',
                ],
            ])
            ->add('open_fieldset_tax_settings', HtmlField::class, [
                'html' => sprintf(
                    '<fieldset class="form-fieldset mt-3 tax-settings" style="display: %s;" data-bb-value="1">',
                    EcommerceHelper::isTaxEnabled() ? 'block' : 'none'
                ),
            ])
            ->add('default_tax_rate', SelectField::class, [
                'label' => trans('plugins/ecommerce::setting.tax.form.default_tax_rate'),
                'selected' => get_ecommerce_setting('default_tax_rate'),
                'choices' => [0 => trans('plugins/ecommerce::setting.tax.form.select_tax')] +
                    app(Tax::class)->pluck('title', 'id')->all(),
                'help_block' => [
                    'text' => trans('plugins/ecommerce::setting.tax.form.default_tax_rate_description'),
                ],
            ])
            ->add(
                'display_product_price_including_taxes',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/ecommerce::setting.tax.form.display_product_price_including_taxes'))
                    ->value(EcommerceHelper::isDisplayProductIncludingTaxes())
            )
            ->add(
                'display_tax_fields_at_checkout_page',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/ecommerce::setting.tax.form.display_company_invoice_information_fields_at_checkout_page'))
                    ->value(EcommerceHelper::isDisplayTaxFieldsAtCheckoutPage())
                    ->helperText(trans('plugins/ecommerce::setting.tax.form.display_company_invoice_information_fields_at_checkout_page_helper'))
            )
            ->add('close_fieldset_tax_settings', 'html', [
                'html' => '</fieldset>',
            ]);
    }
}
