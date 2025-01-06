<?php

namespace Botble\SocialLogin\Forms;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\AlertFieldOption;
use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\AlertField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Setting\Forms\SettingForm;
use Botble\SocialLogin\Facades\SocialService;
use Botble\SocialLogin\Http\Requests\Settings\SocialLoginSettingRequest;
use Illuminate\Support\Arr;

class SocialLoginSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/social-login::social-login.settings.title'))
            ->setSectionDescription(trans('plugins/social-login::social-login.settings.description'))
            ->setValidatorClass(SocialLoginSettingRequest::class);

        $this
            ->add(
                'social_login_enable',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label(trans('plugins/social-login::social-login.settings.enable'))
                    ->value($enabled = old('social_login_enable', setting('social_login_enable')))
            )
            ->addOpenCollapsible('social_login_enable', '1', $enabled);

        foreach (SocialService::getProviders() as $provider => $item) {
            $this->add(
                $enabledKey = sprintf('social_login_%s_enable', $provider),
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->label($item['label']['enable'] ?? trans(sprintf('plugins/social-login::social-login.settings.%s.enable', $provider)))
                    ->value($enabled = old($enabledKey, setting($enabledKey)))
            );

            $this->addOpenCollapsible($enabledKey, '1', $enabled);

            foreach ($item['data'] as $input) {
                $isDisabled = in_array(app()->environment(), SocialService::getEnvDisableData()) && in_array($input, Arr::get($item, 'disable', []));
                $label = $item['label'][$input] ?? trans('plugins/social-login::social-login.settings.' . $provider . '.' . $input);
                $key = 'social_login_' . $provider . '_' . $input;

                $this
                    ->add(
                        $key,
                        TextField::class,
                        TextFieldOption::make()
                            ->label($label)
                            ->value($isDisabled ? SocialService::getDataDisable($provider . '_' . $input) : setting($key))
                            ->disabled($isDisabled)
                            ->toArray()
                    );
            }

            $this
                ->add(
                    'social_login_' . $provider . '_helper',
                    AlertField::class,
                    AlertFieldOption::make()
                        ->content(BaseHelper::clean($item['label']['helper'] ?? trans('plugins/social-login::social-login.settings.' . $provider . '.helper', [
                            'callback' => '<code class=\'text-danger\'>' . route('auth.social.callback', $provider) . '</code>',
                        ])))
                        ->toArray()
                )
                ->when($provider === 'facebook', function (FormAbstract $form) {
                    $form
                        ->add(
                            'social_login_facebook_data_deletion_request_callback_url',
                            AlertField::class,
                            AlertFieldOption::make()
                                ->content(trans('plugins/social-login::social-login.settings.facebook.data_deletion_request_callback_url', [
                                    'url' => sprintf('<code class="text-danger">%s</code>', route('facebook-data-deletion-request-callback')),
                                ]))
                                ->toArray()
                        );
                });

            $this->addCloseCollapsible($enabledKey, '1');
        }

        $this->addCloseCollapsible('social_login_enable', '1');
    }
}
