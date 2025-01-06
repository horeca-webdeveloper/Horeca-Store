<div class="mb-3">
    <label class="form-label" for="widget-name">{{ __('Name') }}</label>
    <input type="text" id="widget-name" class="form-control" name="name" value="{{ $config['name'] }}">
</div>

<div class="mb-3">
    <label class="form-label" for="widget_menu">{{ __('Select menu') }}</label>
    {!! Form::customSelect('menu_id', $menus, $config['menu_id'], ['class' => 'form-control select-full']) !!}
</div>
