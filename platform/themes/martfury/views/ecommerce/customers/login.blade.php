<div class="ps-my-account">
    {!!
        $form
            ->formClass('ps-form--account')
            ->modify('submit', 'submit', [
                    'attr' => [
                        'class' => 'ps-btn ps-btn--fullwidth',
                    ],
                ])
            ->modify('remember', 'html', ['html' => sprintf('<div class="col-6"><div class="ps-checkbox">
                        <input class="form-control" type="checkbox" name="remember" id="remember-me">
                        <label for="remember-me" class="control-label">%s</label>
                    </div></div>', __('Remember me'))], true)
            ->renderForm()
    !!}
</div>
