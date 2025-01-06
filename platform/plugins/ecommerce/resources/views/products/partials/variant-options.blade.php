<!-- resources/views/admin/products/partials/variant-options.blade.php -->

<div id="variant-options-container">
    <div class="variant-option-row">
        <label for="variant-name">Variant Name:</label>
        <input type="text" name="options[0][name]" class="form-control" placeholder="e.g., Color" />

        <label for="variant-type">Variant Type:</label>
        <input type="text" name="options[0][option_type]" class="form-control" placeholder="e.g., Red, Blue, Green" />

        <button type="button" class="btn btn-primary add-variant-option">Add Option</button>
        <button type="button" class="btn btn-danger remove-variant-option">Remove</button>
    </div>
</div>

<script>
    let optionIndex = 1;

    document.querySelector('.add-variant-option').addEventListener('click', function () {
        const container = document.getElementById('variant-options-container');
        const newRow = document.createElement('div');
        newRow.classList.add('variant-option-row');

        newRow.innerHTML = `
            <label for="variant-name">Variant Name:</label>
            <input type="text" name="options[${optionIndex}][name]" class="form-control" placeholder="e.g., Color" />

            <label for="variant-type">Variant Type:</label>
            <input type="text" name="options[${optionIndex}][option_type]" class="form-control" placeholder="e.g., Red, Blue, Green" />

            <button type="button" class="btn btn-primary add-variant-option">Add Option</button>
            <button type="button" class="btn btn-danger remove-variant-option">Remove</button>
        `;

        container.appendChild(newRow);
        optionIndex++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-variant-option')) {
            e.target.closest('.variant-option-row').remove();
        }
    });
</script>
