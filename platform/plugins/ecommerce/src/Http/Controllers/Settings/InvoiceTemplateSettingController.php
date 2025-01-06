<?php

namespace Botble\Ecommerce\Http\Controllers\Settings;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Ecommerce\Facades\InvoiceHelper;
use Botble\Ecommerce\Http\Requests\Settings\InvoiceTemplateSettingRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class InvoiceTemplateSettingController extends SettingController
{
    public function edit(): View
    {
        $this->pageTitle(trans('plugins/ecommerce::setting.invoice_templates'));

        Assets::addScriptsDirectly('vendor/core/core/setting/js/email-template.js');

        $content = InvoiceHelper::getInvoiceTemplate();
        $variables = InvoiceHelper::getVariables();

        return view('plugins/ecommerce::invoice-template.settings', compact('content', 'variables'));
    }

    public function update(InvoiceTemplateSettingRequest $request)
    {
        $filePath = InvoiceHelper::getInvoiceTemplateCustomizedPath();

        File::ensureDirectoryExists(File::dirname($filePath));

        BaseHelper::saveFileData($filePath, $request->input('content'), false);

        return $this
            ->httpResponse()
            ->withUpdatedSuccessMessage();
    }

    public function reset()
    {
        File::delete(InvoiceHelper::getInvoiceTemplateCustomizedPath());

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::invoice-template.reset_success'));
    }

    public function preview()
    {
        $invoice = InvoiceHelper::getDataForPreview();

        return InvoiceHelper::streamInvoice($invoice);
    }
}
