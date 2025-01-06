 <div class="form-group">
        <h3>Product Comparison</h3>
        
        <div class="row">
            <div class="col-md-6">
                <label for="compare_type">Compare Type (Comma Separated)</label>
                <input type="text" name="compare_type" id="compare_type" class="form-control" 
                       value="{{ old('compare_type', implode(', ', json_decode($product->compare_type ?? '[]'))) }}">
            </div>
    
            <div class="col-md-6">
                <label for="compare_products">Compare Products (Comma Separated IDs)</label>
                <input type="text" name="compare_products" id="compare_products" class="form-control" 
                       value="{{ old('compare_products', implode(', ', json_decode($product->compare_products ?? '[]'))) }}">
            </div>
        </div>
    </div>
    
    
    
    
    
    
<!--    {{-- <div id="comparison-container">-->
<!--    @if (isset($comparisons['compare_type']) && is_array($comparisons['compare_type']) && count($comparisons['compare_type']) > 0)-->
<!--        @foreach ($comparisons['compare_type'] as $index => $type)-->
<!--            <div class="comparison-item">-->
<!--                <div class="form-group">-->
<!--                    <label for="compare_type_{{ $index }}">Comparison Type</label>-->
<!--                    <select name="compare_type[]" class="form-control">-->
<!--                        <option value="good" {{ $type === 'good' ? 'selected' : '' }}>Good</option>-->
<!--                        <option value="better" {{ $type === 'better' ? 'selected' : '' }}>Better</option>-->
<!--                        <option value="best" {{ $type === 'best' ? 'selected' : '' }}>Best</option>-->
<!--                    </select>-->
<!--                </div>-->
<!--                <div class="form-group">-->
<!--                    <label for="compare_products_{{ $index }}">Comparison Products</label>-->
<!--                    <select name="compare_products[]" class="form-control">-->
<!--                        @foreach ($products as $id => $sku)-->
<!--                            <option value="{{ $id }}" {{ (isset($comparisons['compare_products'][$index]) && $comparisons['compare_products'][$index] == $id) ? 'selected' : '' }}>{{ $sku }}</option>-->
<!--                        @endforeach-->
<!--                    </select>-->
<!--                </div>-->
<!--                <button type="button" class="remove-comparison btn btn-danger">Remove</button>-->
<!--            </div>-->
<!--        @endforeach-->
<!--    @else-->
<!--        <p>No comparisons found.</p>-->
<!--    @endif-->
<!--</div>-->

<!--<button type="button" id="add-comparison" class="btn btn-primary">Add Comparison</button>-->




<!--<script>-->
<!--document.addEventListener('DOMContentLoaded', function () {-->
<!--    const addButton = document.getElementById('add-comparison');-->
<!--    const comparisonContainer = document.getElementById('comparison-container');-->

<!--    addButton.addEventListener('click', function () {-->
<!--        const comparisonItems = comparisonContainer.getElementsByClassName('comparison-item');-->

        <!--// Check if we already have 3 comparison items-->
<!--        if (comparisonItems.length >= 3) {-->
<!--            alert('You can only add up to three comparisons.');-->
<!--            return;-->
<!--        }-->

        <!--// Create a new comparison item-->
<!--        const newItem = document.createElement('div');-->
<!--        newItem.classList.add('comparison-item');-->

<!--        newItem.innerHTML = `-->
<!--            <div class="form-group">-->
<!--                <label for="compare_type[]">Comparison Type</label>-->
<!--                <select name="compare_type[]" class="form-control">-->
<!--                    <option value="good">Good</option>-->
<!--                    <option value="better">Better</option>-->
<!--                    <option value="best">Best</option>-->
<!--                </select>-->
<!--            </div>-->
<!--            <div class="form-group">-->
<!--                <label for="compare_products[]">Comparison Products</label>-->
<!--                <select name="compare_products[]" class="form-control">-->
<!--                    @foreach($products as $id => $sku)-->
<!--                        <option value="{{ $id }}">{{ $sku }}</option>-->
<!--                    @endforeach-->
<!--                </select>-->
<!--            </div>-->
<!--            <button type="button" class="remove-comparison btn btn-danger">Remove</button>-->
<!--        `;-->

<!--        comparisonContainer.appendChild(newItem);-->

        <!--// Attach remove event to the new remove button-->
<!--        newItem.querySelector('.remove-comparison').addEventListener('click', function () {-->
<!--            comparisonContainer.removeChild(newItem);-->
<!--        });-->
<!--    });-->

    <!--// Attach event listener for existing remove buttons-->
<!--    document.querySelectorAll('.remove-comparison').forEach(function (button) {-->
<!--        button.addEventListener('click', function () {-->
<!--            comparisonContainer.removeChild(button.parentElement.parentElement);-->
<!--        });-->
<!--    });-->
<!--});-->
<!--</script> --}}-->

<!--{{-- <label for="compare_type">Compare Type (Comma Separated)</label>-->
<!--<input type="text" name="compare_type" id="compare_type" class="form-control" -->
<!--       value="{{ old('compare_type', implode(', ', json_decode($product->compare_type ?? '[]'))) }}">-->
    
<!--<label for="compare_products">Compare Products (Comma Separated IDs)</label>-->
<!--<input type="text" name="compare_products" id="compare_products" class="form-control" -->
<!--       value="{{ old('compare_products', implode(', ', json_decode($product->compare_products ?? '[]'))) }}"> --}}-->
<!--       <div class="form-group">-->
<!--        <h3>Product Comparison</h3>-->
        
<!--        <div class="row">-->
<!--            <div class="col-md-6">-->
<!--                <label for="compare_type">Compare Type (Comma Separated)</label>-->
<!--                <input type="text" name="compare_type" id="compare_type" class="form-control" -->
<!--                       value="{{ old('compare_type', implode(', ', json_decode($product->compare_type ?? '[]'))) }}">-->
<!--            </div>-->
    
<!--            <div class="col-md-6">-->
<!--                <label for="compare_products">Compare Products (Comma Separated IDs)</label>-->
<!--                <input type="text" name="compare_products" id="compare_products" class="form-control" -->
<!--                       value="{{ old('compare_products', implode(', ', json_decode($product->compare_products ?? '[]'))) }}">-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
    

<!--{{-- -->

<!-- <div id="comparison-container">-->
<!--    @foreach ($comparisons as $index => $type)-->
<!--        <div class="comparison-item">-->
            <!-- Comparison Type -->
<!--            <div class="form-group">-->
<!--                <label for="compare_type_{{ $index }}">Comparison Type</label>-->
<!--                <select name="compare_type[]" class="form-control compare-type-select">-->
<!--                    <option value="good" {{ $type == 'good' ? 'selected' : '' }}>Good</option>-->
<!--                    <option value="better" {{ $type == 'better' ? 'selected' : '' }}>Better</option>-->
<!--                    <option value="best" {{ $type == 'best' ? 'selected' : '' }}>Best</option>-->
<!--                </select>-->
<!--            </div>-->

            <!-- Comparison Products -->
<!--            <div class="form-group">-->
<!--                <label for="compare_products_{{ $index }}">Comparison Products</label>-->
<!--                <select name="compare_products[]" class="form-control">-->
<!--                    @foreach ($products as $id => $sku)-->
<!--                        <option value="{{ $id }}" {{ $compareProducts[$index] == $id ? 'selected' : '' }}>{{ $sku }}</option>-->
<!--                    @endforeach-->
<!--                </select>-->
<!--            </div>-->

<!--            <button type="button" class="remove-comparison btn btn-danger">Remove</button>-->
<!--        </div>-->
<!--    @endforeach-->
<!--</div>-->

<!--<button type="button" id="add-comparison" class="btn btn-primary">Add Comparison</button>-->

<!--<script>-->
<!--document.addEventListener('DOMContentLoaded', function () {-->
<!--    const addButton = document.getElementById('add-comparison');-->
<!--    const comparisonContainer = document.getElementById('comparison-container');-->

<!--    addButton.addEventListener('click', function () {-->
<!--        const comparisonItems = comparisonContainer.getElementsByClassName('comparison-item');-->

<!--        if (comparisonItems.length >= 3) {-->
<!--            alert('You can only add up to three comparisons.');-->
<!--            return;-->
<!--        }-->

<!--        const newItem = document.createElement('div');-->
<!--        newItem.classList.add('comparison-item');-->

<!--        newItem.innerHTML = `-->
<!--            <div class="form-group">-->
<!--                <label for="compare_type[]">Comparison Type</label>-->
<!--                <select name="compare_type[]" class="form-control compare-type-select">-->
<!--                    <option value="good">Good</option>-->
<!--                    <option value="better">Better</option>-->
<!--                    <option value="best">Best</option>-->
<!--                </select>-->
<!--            </div>-->
<!--            <div class="form-group">-->
<!--                <label for="compare_products[]">Comparison Products</label>-->
<!--                <select name="compare_products[]" class="form-control">-->
<!--                    @foreach($products as $id => $sku)-->
<!--                        <option value="{{ $id }}">{{ $sku }}</option>-->
<!--                    @endforeach-->
<!--                </select>-->
<!--            </div>-->
<!--            <button type="button" class="remove-comparison btn btn-danger">Remove</button>-->
<!--        `;-->

<!--        comparisonContainer.appendChild(newItem);-->

        <!--// Attach remove event to the new remove button-->
<!--        newItem.querySelector('.remove-comparison').addEventListener('click', function () {-->
<!--            comparisonContainer.removeChild(newItem);-->
<!--        });-->
<!--    });-->

    <!--// Attach event listener for existing remove buttons-->
<!--    document.querySelectorAll('.remove-comparison').forEach(function (button) {-->
<!--        button.addEventListener('click', function () {-->
<!--            comparisonContainer.removeChild(button.parentElement.parentElement);-->
<!--        });-->
<!--    });-->
<!--});-->
<!--</script> --}}-->
