@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::form
        :url="route('ecommerce.settings.shipping-label-template.update')"
        method="PUT"
    >
        <x-core-setting::section
            :title="trans('plugins/ecommerce::shipping-label-template.setting')"
            :description="trans('plugins/ecommerce::shipping-label-template.setting_description')"
        >
            <x-core::form-group>
                <x-core::form.label for="email_content">
                    {{ trans('plugins/ecommerce::shipping-label-template.setting_content') }}
                </x-core::form.label>

                <x-core::twig-editor
                    :variables="$variables"
                    :functions="EmailHandler::getFunctions()"
                    :value="$content"
                    name="content"
                    mode="html"
                >
                </x-core::twig-editor>
            </x-core::form-group>
        </x-core-setting::section>

        <x-core-setting::section.action>
            <div class="btn-list">
                <x-core::button
                    type="submit"
                    color="primary"
                    icon="ti ti-device-floppy"
                >
                    {{ trans('core/setting::setting.save_settings') }}
                </x-core::button>

                <x-core::button
                    class="btn-trigger-reset-to-default"
                    icon="ti ti-arrow-back-up"
                    data-bb-toggle="reset-default"
                >
                    {{ trans('plugins/ecommerce::shipping-label-template.reset_to_default') }}
                </x-core::button>

                <x-core::button
                    target="_blank"
                    tag="a"
                    href="{{ route('ecommerce.settings.shipping-label-template.preview') }}"
                    icon="ti ti-eye"
                >
                    {{ trans('plugins/ecommerce::shipping-label-template.preview') }}
                </x-core::button>
            </div>
        </x-core-setting::section.action>
    </x-core::form>

    <x-core::modal.action
        type="warning"
        id="reset-template-to-default-modal"
        :title="trans('plugins/ecommerce::shipping-label-template.confirm_reset')"
        :submit-button-label="trans('plugins/ecommerce::shipping-label-template.continue')"
        :submit-button-attrs="['id' => 'reset-template-to-default-button', 'data-target' => route('ecommerce.settings.shipping-label-template.reset')]"
    >
        {!! trans('plugins/ecommerce::shipping-label-template.confirm_message') !!}
    </x-core::modal.action>
@endsection
