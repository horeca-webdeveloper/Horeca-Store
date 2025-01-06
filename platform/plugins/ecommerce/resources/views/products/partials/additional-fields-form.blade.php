<div id="additional-fields-wrapper">
    <!-- Existing fields will be inserted here -->
</div>
<button type="button" id="add-more-fields" class="btn btn-primary">Add More Fields</button>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let fieldIndex = 0;

        document.getElementById('add-more-fields').addEventListener('click', function () {
            fieldIndex++;

            const newFieldHtml = `
                <div class="form-group additional-field-group">
                    <input type="text" name="additional_fields[${fieldIndex}][field1]" class="form-control" placeholder="Field 1">
                    <input type="text" name="additional_fields[${fieldIndex}][field2]" class="form-control" placeholder="Field 2">
                    <button type="button" class="btn btn-danger remove-field">Remove</button>
                </div>
            `;

            document.getElementById('additional-fields-wrapper').insertAdjacentHTML('beforeend', newFieldHtml);
        });

        // Remove field handler
        document.getElementById('additional-fields-wrapper').addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-field')) {
                event.target.parentElement.remove();
            }
        });
    });
</script>
