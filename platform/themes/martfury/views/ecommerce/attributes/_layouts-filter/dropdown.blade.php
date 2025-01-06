<figure class="dropdown-swatches-wrapper widget-filter-item">
    <h4 class="widget-title">{{ __('By') }} {{ $set->title }}</h4>
    <div class="widget-content ps-custom-scrollbar">
        <div class="attribute-values">
            <div class="dropdown-swatch">
                <label>
                    <select class="form-control product-filter-item" name="attributes[{{ $set->slug }}][]">
                        <option value="">{{ __('-- Select --') }}</option>
                        @foreach($attributes->where('attribute_set_id', $set->id) as $attribute)
                            <option value="{{ $attribute->id }}" {{ in_array($attribute->id, $selected) ? 'selected' : '' }}>{{ $attribute->title }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
    </div>
</figure>
