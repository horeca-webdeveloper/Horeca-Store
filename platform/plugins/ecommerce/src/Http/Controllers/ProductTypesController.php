<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Supports\Breadcrumb;
use Botble\Ecommerce\Forms\ProductTypesForm;
use Botble\Ecommerce\Http\Requests\ProductTypesRequest;
use Botble\Ecommerce\Models\ProductTypes;
use Botble\Ecommerce\Tables\ProductTypesTable;
use Exception;
use Illuminate\Http\Request;

class ProductTypesController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/ecommerce::product-types.name'), route('product-types.index'));
    }

    public function index(ProductTypesTable $table)
    {
        $this->pageTitle(trans('plugins/ecommerce::product-types.name'));

        return $table->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/ecommerce::product-types.create'));

        return ProductTypesForm::create()->renderForm();
    }

    public function store(ProductTypesRequest $request)
    {
        $ProductTypes = ProductTypes::query()->create($request->input());

        event(new CreatedContentEvent(PRODUCT_TYPES_MODULE_SCREEN_NAME, $request, $ProductTypes));

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('product-types.index'))
            ->setNextUrl(route('product-types.edit', $ProductTypes->id))
            ->withCreatedSuccessMessage();
    }

    public function edit(int|string $id, Request $request)
    {
        $ProductTypes = ProductTypes::query()->findOrFail($id);

        event(new BeforeEditContentEvent($request, $ProductTypes));

        $this->pageTitle(trans('core/base::forms.edit_item', ['name' => $ProductTypes->name]));

        return ProductTypesForm::createFromModel($ProductTypes)->renderForm();
    }

    public function update(int|string $id, ProductTypesRequest $request)
    {
        $ProductTypes = ProductTypes::query()->findOrFail($id);

        $ProductTypes->fill($request->input());
        $ProductTypes->save();

        event(new UpdatedContentEvent(PRODUCT_TYPES_MODULE_SCREEN_NAME, $request, $ProductTypes));

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('product-types.index'))
            ->withUpdatedSuccessMessage();
    }

    public function destroy(int|string $id, Request $request)
    {
        try {
            $ProductTypes = ProductTypes::query()->findOrFail($id);

            $ProductTypes->delete();

            event(new DeletedContentEvent(PRODUCT_TYPES_MODULE_SCREEN_NAME, $request, $ProductTypes));

            return $this
                ->httpResponse()
                ->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function getAllTypes()
    {
        return ProductTypes::query()->pluck('name')->all();
    }

}
