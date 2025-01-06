<div id="specs-fields">
    <div class="spec-field" style="display: flex; align-items: center; margin-bottom: 10px;">
        <input type="text" name="specs[][name]" placeholder="Spec Name" style="flex: 1; margin-right: 10px;" />
        <input type="text" name="specs[][value]" placeholder="Spec Value" style="flex: 1;" />
        <button type="button" class="remove-spec" style="margin-left: 10px;">Remove</button>
    </div>
</div>
<button type="button" id="add-spec" style="margin-top: 10px;">Add Spec</button>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('add-spec').addEventListener('click', function () {
            const container = document.getElementById('specs-fields');
            const newField = document.createElement('div');
            newField.classList.add('spec-field');
            newField.style.cssText = 'display: flex; align-items: center; margin-bottom: 10px;';
            newField.innerHTML = `
                <input type="text" name="specs[][name]" placeholder="Spec Name" style="flex: 1; margin-right: 10px;" />
                <input type="text" name="specs[][value]" placeholder="Spec Value" style="flex: 1;" />
                <button type="button" class="remove-spec" style="margin-left: 10px;">Remove</button>
            `;
            container.appendChild(newField);
        });

        document.getElementById('specs-fields').addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-spec')) {
                event.target.parentElement.remove();
            }
        });
    });
</script>
