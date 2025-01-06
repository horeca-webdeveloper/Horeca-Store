<?php

namespace Botble\Ecommerce\Forms\Settings;

use Botble\Base\Forms\Fields\TextField;
use Botble\Ecommerce\Forms\Fronts\Auth\FieldOptions\TextFieldOption;
use Botble\Ecommerce\Http\Requests\Settings\WebhookSettingRequest;
use Botble\Setting\Forms\SettingForm;

class WebhookSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/ecommerce::setting.webhook.webhook_setting'))
            ->setSectionDescription(trans('plugins/ecommerce::setting.webhook.webhook_setting_description'))
            ->setValidatorClass(WebhookSettingRequest::class)
            ->add(
                'order_placed_webhook_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/ecommerce::setting.webhook.form.order_placed_webhook_url'))
                    ->value(get_ecommerce_setting('order_placed_webhook_url'))
                    ->placeholder(trans('plugins/ecommerce::setting.webhook.form.order_placed_webhook_url_placeholder'))
                    ->wrapperAttributes([
                        'class' => 'mb-0',
                    ])
                    ->helperText(trans('plugins/ecommerce::setting.webhook.form.order_placed_webhook_url_helper'))
            );
    }
}
