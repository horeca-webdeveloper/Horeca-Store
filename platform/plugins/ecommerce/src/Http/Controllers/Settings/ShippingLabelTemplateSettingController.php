<?php

namespace Botble\Ecommerce\Http\Controllers\Settings;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Pdf;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Facades\InvoiceHelper;
use Botble\Ecommerce\Http\Requests\Settings\ShippingLabelTemplateSettingRequest;
use Botble\Media\Facades\RvMedia;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ShippingLabelTemplateSettingController extends SettingController
{
    public function edit(): View
    {
        $this->pageTitle(trans('plugins/ecommerce::shipping-label-template.name'));

        Assets::addScriptsDirectly('vendor/core/core/setting/js/email-template.js');

        $content = (new Pdf())->getContent(
            plugin_path('ecommerce/resources/templates/shipping-label.tpl'),
            storage_path('app/templates/ecommerce/shipping-label.tpl')
        );

        $variables = [

        ];

        return view('plugins/ecommerce::shipping-label-template.settings', compact('content', 'variables'));
    }

    public function update(ShippingLabelTemplateSettingRequest $request)
    {
        $filePath = storage_path('app/templates/ecommerce/shipping-label.tpl');

        File::ensureDirectoryExists(File::dirname($filePath));

        BaseHelper::saveFileData($filePath, $request->input('content'), false);

        return $this
            ->httpResponse()
            ->withUpdatedSuccessMessage();
    }

    public function reset()
    {
        File::delete(storage_path('app/templates/ecommerce/shipping-label.tpl'));

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::shipping-label-template.reset_success'));
    }

    public function preview()
    {
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        $url = 'https://mydhl.express.dhl/us/en/tracking.html#/track-by-reference?ref=1234567890';

        $qrCode = $writer->writeString($url);

        return (new Pdf())
            ->templatePath(plugin_path('ecommerce/resources/templates/shipping-label.tpl'))
            ->destinationPath(storage_path('app/templates/ecommerce/shipping-label.tpl'))
            ->paperSizeHalfLetter()
            ->supportLanguage(InvoiceHelper::getLanguageSupport())
            ->data(
                [
                    'shipment' => [
                        'order_number' => get_order_code(123),
                        'code' => get_shipment_code(345),
                        'weight' => 1000,
                        'weight_unit' => ecommerce_weight_unit(),
                        'created_at' => BaseHelper::formatDate(Carbon::now()),
                        'shipping_method' => Arr::random(ShippingMethodEnum::labels()),
                        'shipping_fee' => format_price(30),
                        'shipping_company_name' => Arr::random(['DHL', 'AliExpress', 'GHN', 'FastShipping']),
                        'tracking_id' => 'JJD00' . rand(1111111, 99999999),
                        'tracking_link' => $url,
                        'note' => 'Note here',
                        'qr_code' => base64_encode($qrCode),
                    ],
                    'sender' => [
                        'logo' => RvMedia::getRealPath(theme_option('logo')),
                        'name' => get_ecommerce_setting('store_name'),
                        'phone' => get_ecommerce_setting('store_phone'),
                        'email' => get_ecommerce_setting('store_email'),
                        'country' => $country = get_ecommerce_setting('store_country'),
                        'state' => $state = get_ecommerce_setting('store_state'),
                        'city' => $city = get_ecommerce_setting('store_city'),
                        'zip_code' => $zipCode = get_ecommerce_setting('store_zip_code'),
                        'address' => $address = get_ecommerce_setting('store_address'),
                        'full_address' => implode(', ', array_filter([
                            $address,
                            $city,
                            $state,
                            $country,
                            EcommerceHelper::isZipCodeEnabled() ? $zipCode : '',
                        ])),
                    ],
                    'receiver' => [
                        'name' => 'Odie Miller',
                        'full_address' => '14059 Triton Crossroad South Lillie, NH 84777-1634',
                        'email' => 'contact@example.com',
                        'phone' => '+0123456789',
                    ],
                ]
            )
            ->compile()
            ->stream();
    }
}
