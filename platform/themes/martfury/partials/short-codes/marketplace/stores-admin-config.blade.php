<div class="mb-3">
    <label class="form-label">{{ __('Title') }}</label>
    <input
        class="form-control"
        name="title"
        type="text"
        value="{{ Arr::get($attributes, 'title') }}"
        placeholder="{{ __('Title') }}"
    >
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Stores') }}</label>
    <input name="stores" class="form-control list-tagify" data-list="{{ json_encode($stores) }}" value="{{ Arr::get($attributes, 'stores') }}" placeholder="{{ __('Select stores from the list') }}">
</div>
