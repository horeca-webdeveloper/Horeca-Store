<div class="form-group">

    <div class="form-group">
        <h3>Variant Color</h3>
        <label for="variant_3_title">Variant Color Title</label>
        <input type="text" name="variant_color_title" id="variant_color_title" class="form-control" value="Color" readonly>

        
        <label for="variant_3_value">Variant Color Value</label>
        <input type="text" name="variant_color_value" id="variant_color_value" class="form-control" value="{{ old('variant_color_value', $product->variant_color_value) }}">
        
        <label for="variant_3_products">Variant Color Products (Comma Separated IDs)</label>
        <input type="text" name="variant_color_products" id="variant_color_products" class="form-control" value="{{ old('variant_color_products', $product->variant_color_products) }}">
    </div>

    <h3>Variant 1</h3>
    <label for="variant_1_title">Variant 1 Title</label>
    <input type="text" name="variant_1_title" id="variant_1_title" class="form-control" value="{{ old('variant_1_title', $product->variant_1_title) }}">
    
    <label for="variant_1_value">Variant 1 Value</label>
    <input type="text" name="variant_1_value" id="variant_1_value" class="form-control" value="{{ old('variant_1_value', $product->variant_1_value) }}">
    
    <label for="variant_1_products">Variant 1 Products (Comma Separated IDs)</label>
    <input type="text" name="variant_1_products" id="variant_1_products" class="form-control" value="{{ old('variant_1_products', $product->variant_1_products) }}">
</div>

<div class="form-group">
    <h3>Variant 2</h3>
    <label for="variant_2_title">Variant 2 Title</label>
    <input type="text" name="variant_2_title" id="variant_2_title" class="form-control" value="{{ old('variant_2_title', $product->variant_2_title) }}">
    
    <label for="variant_2_value">Variant 2 Value</label>
    <input type="text" name="variant_2_value" id="variant_2_value" class="form-control" value="{{ old('variant_2_value', $product->variant_2_value) }}">
    
    <label for="variant_2_products">Variant 2 Products (Comma Separated IDs)</label>
    <input type="text" name="variant_2_products" id="variant_2_products" class="form-control" value="{{ old('variant_2_products', $product->variant_2_products) }}">
</div>

<div class="form-group">
    <h3>Variant 3</h3>
    <label for="variant_3_title">Variant 3 Title</label>
    <input type="text" name="variant_3_title" id="variant_3_title" class="form-control" value="{{ old('variant_3_title', $product->variant_3_title) }}">
    
    <label for="variant_3_value">Variant 3 Value</label>
    <input type="text" name="variant_3_value" id="variant_3_value" class="form-control" value="{{ old('variant_3_value', $product->variant_3_value) }}">
    
    <label for="variant_3_products">Variant 3 Products (Comma Separated IDs)</label>
    <input type="text" name="variant_3_products" id="variant_3_products" class="form-control" value="{{ old('variant_3_products', $product->variant_3_products) }}">
</div>

