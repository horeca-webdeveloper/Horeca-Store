<div class="form-group">
    <label for="shipping_weight">Shipping Weight</label>
    <input type="number" step="0.01" name="shipping_weight" id="shipping_weight" class="form-control" value="{{ old('shipping_weight', $shipping_weight) }}">
</div>

<div class="form-group">
    <label for="shipping_weight_option">Shipping Weight Option</label>
    <select name="shipping_weight_option" id="shipping_weight_option" class="form-control">
        <option value="Kg" {{ (old('shipping_weight_option', $shipping_weight_option) == 'Kg') ? 'selected' : '' }}>Kg</option>
        <option value="g" {{ (old('shipping_weight_option', $shipping_weight_option) == 'g') ? 'selected' : '' }}>Grams</option>
        <option value="pounds" {{ (old('shipping_weight_option', $shipping_weight_option) == 'pounds') ? 'selected' : '' }}>Pounds</option>
        <option value="oz" {{ (old('shipping_weight_option', $shipping_weight_option) == 'oz') ? 'selected' : '' }}>Ounces</option>
    </select>
</div>
