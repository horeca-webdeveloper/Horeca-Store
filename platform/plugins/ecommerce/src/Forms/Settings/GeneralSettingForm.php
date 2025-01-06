<?php

namespace Botble\Ecommerce\Forms\Settings;

use Botble\Base\Forms\Fields\TextField;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Forms\Concerns\HasLocationFields;
use Botble\Ecommerce\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\Ecommerce\Http\Requests\Settings\GeneralSettingRequest;
use Botble\Setting\Forms\SettingForm;

class GeneralSettingForm extends SettingForm
{
    use HasLocationFields;

    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/ecommerce::setting.general.name'))
            ->setSectionDescription(trans('plugins/ecommerce::store-locator.description'))
            ->setValidatorClass(GeneralSettingRequest::class)
            ->columns(6)
            ->add(
                'store_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ecommerce::store-locator.shop_name'))
                    ->value(get_ecommerce_setting('store_name'))
                    ->colspan(3)
            )
            ->add(
                'store_company',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ecommerce::ecommerce.company'))
                    ->value(get_ecommerce_setting('store_company'))
                    ->colspan(3)
            )
            ->add(
                'store_phone',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ecommerce::ecommerce.phone'))
                    ->value(get_ecommerce_setting('store_phone'))
                    ->colspan(3)
            )
            ->add(
                'store_email',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ecommerce::ecommerce.email'))
                    ->value(get_ecommerce_setting('store_email'))
                    ->colspan(3)
            )
            ->addLocationFields(
                countryAttributes: [
                    'name' => 'store_country',
                    'value' => get_ecommerce_setting('store_country'),
                ],
                stateAttributes: [
                    'name' => 'store_state',
                    'value' => get_ecommerce_setting('store_state'),
                ],
                cityAttributes: [
                    'name' => 'store_city',
                    'value' => get_ecommerce_setting('store_city'),
                ],
                addressAttributes: [
                    'name' => 'store_address',
                    'value' => get_ecommerce_setting('store_address'),
                ],
                zipCodeAttributes: [
                    'name' => 'store_zip_code',
                    'value' => get_ecommerce_setting('store_zip_code'),
                ]
            )
            ->add(
                'store_vat_number',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ecommerce::ecommerce.tax_id'))
                    ->value(get_ecommerce_setting('store_vat_number'))
                    ->colspan(EcommerceHelper::isUsingInMultipleCountries() && EcommerceHelper::isZipCodeEnabled() ? 3 : 2)
            );
    }
}
