<div class="mb-3">
    <label class="form-label">{{ __('Select category') }}</label>
    <select name="category_id" class="form-select">
        {!! ProductCategoryHelper::renderProductCategoriesSelect(Arr::get($attributes, 'category_id')) !!}
    </select>
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Limit number of categories') }}</label>
    <input type="number" name="number_of_categories" value="{{ Arr::get($attributes, 'number_of_categories', 3) }}" class="form-control" placeholder="{{ __('Default: 3') }}">
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Limit number of products') }}</label>
    <input type="number" name="limit" value="{{ Arr::get($attributes, 'limit') }}" class="form-control" placeholder="{{ __('Unlimited by default') }}">
</div>
