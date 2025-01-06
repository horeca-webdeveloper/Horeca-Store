<div class="ps-contact-form">
    <div class="container">
        <h3>{{ __('Get In Touch') }}</h3>

        {!!
            $form
                ->setFormOption('class', 'ps-form--contact-us contact-form')
                ->setFormInputClass('form-control')
                ->setFormLabelClass('d-none sr-only')
                ->modify(
                    'submit',
                    'submit',
                    Botble\Base\Forms\FieldOptions\ButtonFieldOption::make()
                        ->cssClass('ps-btn')
                        ->label(__('Send message'))
                        ->wrapperAttributes(['class' => 'form-group submit'])
                        ->toArray(),
                    true
                )
                ->renderForm()
        !!}

    </div>
</div>
