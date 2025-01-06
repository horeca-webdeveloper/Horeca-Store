<?php

namespace Botble\Ecommerce\Forms\Concerns;

use Botble\Base\Facades\AdminHelper;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\Html;
use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\FieldOptions\InputFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Arr;

trait HasLocationFields
{
    public function addLocationFields(
        array $countryAttributes = [],
        array $stateAttributes = [],
        array $cityAttributes = [],
        array $addressAttributes = [],
        array $zipCodeAttributes = []
    ): static {
        $loadLocationsFromPluginLocation = EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation();

        if ($loadLocationsFromPluginLocation) {
            Assets::addScriptsDirectly('vendor/core/plugins/location/js/location.js');

            if (request()->ajax()) {
                $this->add(
                    'locationScript',
                    HtmlField::class,
                    HtmlFieldOption::make()
                        ->content(Html::script('vendor/core/plugins/location/js/location.js')->toHtml())
                );
            }

            if (! AdminHelper::isInAdmin() && class_exists(Theme::class)) {
                Theme::asset()
                    ->container('footer')
                    ->add('location-js', 'vendor/core/plugins/location/js/location.js', ['jquery']);
            }
        }

        $isMultipleCountries = EcommerceHelper::isUsingInMultipleCountries();

        $isZipcodeEnabled = EcommerceHelper::isZipCodeEnabled();

        $countryFieldName = Arr::get($countryAttributes, 'name', 'country');
        $stateFieldName = Arr::get($stateAttributes, 'name', 'state');
        $cityFieldName = Arr::get($cityAttributes, 'name', 'city');
        $addressFieldName = Arr::get($addressAttributes, 'name', 'address');
        $zipCodeFieldName = Arr::get($zipCodeAttributes, 'name', 'zip_code');

        $countryAttributes = Arr::except($countryAttributes, ['name']);

        $countryAttributes['selected'] = old($countryFieldName, $this->getModel()->country) ?: Arr::get($countryAttributes, 'value');

        $stateAttributes = Arr::except($stateAttributes, ['name']);

        $stateAttributes['selected'] = old($stateFieldName, $this->getModel()->state) ?: Arr::get($stateAttributes, 'value');

        $cityAttributes = Arr::except($cityAttributes, ['name']);

        $cityAttributes['selected'] = old($cityFieldName, $this->getModel()->city) ?: Arr::get($cityAttributes, 'value');

        $addressAttributes = Arr::except($addressAttributes, ['name']);

        $zipCodeAttributes = Arr::except($zipCodeAttributes, ['name']);

        $this
            ->when($isMultipleCountries, function (FormAbstract $form) use ($countryFieldName, $countryAttributes) {
                $form->add(
                    $countryFieldName,
                    SelectField::class,
                    [
                        ...SelectFieldOption::make()
                            ->label(trans('plugins/ecommerce::addresses.country'))
                            ->attributes([
                                'data-type' => 'country',
                            ])
                            ->choices(EcommerceHelper::getAvailableCountries())
                            ->colspan(3)
                            ->toArray(),
                        ...$countryAttributes,
                    ]
                );
            }, function (FormAbstract $form) use ($countryFieldName, $countryAttributes) {
                $form->add(
                    $countryFieldName,
                    'hidden',
                    [
                        ...InputFieldOption::make()
                            ->value(EcommerceHelper::getFirstCountryId())
                            ->toArray(),
                        ...$countryAttributes,
                    ]
                );
            })
            ->when($loadLocationsFromPluginLocation, function (FormAbstract $form) use (
                $countryAttributes,
                $stateFieldName,
                $stateAttributes,
                $isMultipleCountries
            ) {
                $model = $this->getModel();

                $form->add(
                    $stateFieldName,
                    SelectField::class,
                    [
                        ...SelectFieldOption::make()
                            ->choices(
                                ['' => __('Select state...')] + EcommerceHelper::getAvailableStatesByCountry(
                                    $countryAttributes['selected']
                                )
                            )
                            ->attributes([
                                'data-type' => 'state',
                                'data-url' => route('ajax.states-by-country'),
                            ])
                            ->colspan($isMultipleCountries ? 2 : 3)
                            ->label(trans('plugins/ecommerce::addresses.state'))
                            ->toArray(),
                        ...$stateAttributes,
                    ]
                );
            }, function (FormAbstract $form) use ($loadLocationsFromPluginLocation, $stateFieldName, $stateAttributes) {
                $form->add(
                    $stateFieldName,
                    TextField::class,
                    [
                        ...TextFieldOption::make()
                            ->label(trans('plugins/ecommerce::addresses.state'))
                            ->colspan($loadLocationsFromPluginLocation ? 2 : 3)
                            ->toArray(),
                        ...$stateAttributes,
                    ]
                );
            })
            ->when(EcommerceHelper::useCityFieldAsTextField(), function (FormAbstract $form) use (
                $loadLocationsFromPluginLocation,
                $cityFieldName,
                $cityAttributes
            ) {
                $form->add(
                    $cityFieldName,
                    TextField::class,
                    [
                        ...TextFieldOption::make()
                            ->label(trans('plugins/ecommerce::addresses.city'))
                            ->colspan($loadLocationsFromPluginLocation ? 2 : 3)
                            ->toArray(),
                        ...$cityAttributes,
                    ]
                );
            }, function (FormAbstract $form) use ($stateAttributes, $cityFieldName, $cityAttributes, $isMultipleCountries) {
                $form->add(
                    $cityFieldName,
                    SelectField::class,
                    [
                        ...SelectFieldOption::make()
                            ->label(trans('plugins/ecommerce::addresses.city'))
                            ->attributes([
                                'data-type' => 'city',
                                'data-url' => route('ajax.cities-by-state'),
                            ])
                            ->colspan($isMultipleCountries ? 2 : 3)
                            ->choices(
                                ['' => __('Select city...')] + EcommerceHelper::getAvailableCitiesByState(
                                    $stateAttributes['selected']
                                )
                            )
                            ->toArray(),
                        ...$cityAttributes,
                    ]
                );
            })
            ->add(
                $addressFieldName,
                TextField::class,
                [
                    ...TextFieldOption::make()
                        ->label(trans('plugins/ecommerce::addresses.address'))
                        ->placeholder(trans('plugins/ecommerce::addresses.address_placeholder'))
                        ->colspan($isZipcodeEnabled ? ($isMultipleCountries ? 3 : 2) : 3)
                        ->toArray(),
                    ...$addressAttributes,
                ]
            )
            ->when($isZipcodeEnabled, function (FormAbstract $form) use (
                $isMultipleCountries,
                $zipCodeAttributes,
                $zipCodeFieldName
            ) {
                $form->add(
                    $zipCodeFieldName,
                    TextField::class,
                    [
                        ...TextFieldOption::make()
                            ->placeholder(trans('plugins/ecommerce::addresses.zip_placeholder'))
                            ->label(trans('plugins/ecommerce::addresses.zip'))
                            ->colspan($isMultipleCountries ? 3 : 2)
                            ->toArray(),
                        ...$zipCodeAttributes,
                    ]
                );
            });

        return $this;
    }
}
