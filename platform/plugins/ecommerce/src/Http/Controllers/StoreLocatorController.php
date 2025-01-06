<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Forms\StoreLocatorForm;
use Botble\Ecommerce\Http\Requests\StoreLocatorRequest;
use Botble\Ecommerce\Models\StoreLocator;
use Botble\Setting\Supports\SettingStore;

class StoreLocatorController extends BaseController
{
    public function store(StoreLocatorRequest $request)
    {
        StoreLocator::query()->create([
            ...$request->validated(),
            'is_primary' => false,
        ]);

        return $this
            ->httpResponse()
            ->withCreatedSuccessMessage();
    }

    public function edit(int|string|null $id = null)
    {
        $locator = $id ? StoreLocator::query()->findOrFail($id) : new StoreLocator();

        $form = StoreLocatorForm::createFromModel($locator)
            ->setUrl($locator->exists ? route('ecommerce.store-locators.edit.post', $locator->getKey()) : route('ecommerce.store-locators.create'))
            ->renderForm();

        return $this
            ->httpResponse()
            ->setData($form);
    }

    public function update(StoreLocator $locator, StoreLocatorRequest $request, SettingStore $settingStore)
    {
        $locator->update($request->validated());

        if ($locator->is_primary) {
            $prefix = EcommerceHelper::getSettingPrefix();

            $settingStore
                ->set([
                    $prefix . 'store_phone' => $locator->phone,
                    $prefix . 'store_address' => $locator->address,
                    $prefix . 'store_country' => $locator->country,
                    $prefix . 'store_state' => $locator->state,
                    $prefix . 'store_city' => $locator->city,
                ])
                ->save();
        }

        return $this
            ->httpResponse()
            ->withUpdatedSuccessMessage();
    }

    public function destroy(StoreLocator $locator)
    {
        return DeleteResourceAction::make($locator);
    }
}
