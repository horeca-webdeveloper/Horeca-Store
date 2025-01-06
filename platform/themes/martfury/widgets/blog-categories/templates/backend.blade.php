<div class="mb-3">
    <label class="form-label" for="widget-name">{{ trans('core/base::forms.name') }}</label>
    <input type="text" class="form-control" name="name" value="{{ $config['name'] }}">
</div>
<div class="mb-3">
    <label class="form-label" for="number_display">{{ __('Number categories to display') }}</label>
    <input type="number" class="form-control" name="number_display" value="{{  $config['number_display'] }}">
</div>
