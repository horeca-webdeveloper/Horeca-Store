<?php

namespace Botble\Ecommerce\Http\Controllers\Customers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Forms\Fronts\Customer\AddressForm;
use Botble\Ecommerce\Http\Requests\CreateAddressFromAdminRequest;
use Botble\Ecommerce\Models\Address;

class AddressController extends BaseController
{
    public function store(CreateAddressFromAdminRequest $request)
    {
        if ($request->boolean('is_default')) {
            Address::query()
                ->where([
                    'is_default' => 1,
                    'customer_id' => $request->input('customer_id'),
                ])
                ->update([
                    'is_default' => 0,
                ]);
        }

        $request->merge([
            'customer_id' => $request->input('customer_id'),
            'is_default' => $request->input('is_default', 0),
        ]);

        Address::query()->create($request->input());

        return $this
            ->httpResponse()
            ->setNextUrl(route('customer.address'))
            ->withCreatedSuccessMessage();
    }

    public function update(Address $address, CreateAddressFromAdminRequest $request)
    {
        if ($request->boolean('is_default')) {
            Address::query()
                ->where([
                    'is_default' => 1,
                    'customer_id' => $request->input('customer_id'),
                ])
                ->update([
                    'is_default' => 0,
                ]);
        }

        $request->merge([
            'customer_id' => $request->input('customer_id'),
            'is_default' => $request->input('is_default', 0),
        ]);

        $address->fill($request->input());

        $address->save();

        return $this
            ->httpResponse()
            ->setNextUrl(route('customer.address'))
            ->withUpdatedSuccessMessage();
    }

    public function destroy(Address $address)
    {
        $address->delete();

        return $this
            ->httpResponse()
            ->setNextUrl(route('customer.address'))
            ->setMessage(trans('core/base::notices.delete_success_message'));
    }

    public function edit(Address $address)
    {
        return AddressForm::createFromModel($address)
            ->setUrl(route('customers.addresses.edit.update', $address->getKey()))
            ->add('customer_id', 'hidden', ['value' => $address->customer_id])
            ->renderForm();
    }
}
