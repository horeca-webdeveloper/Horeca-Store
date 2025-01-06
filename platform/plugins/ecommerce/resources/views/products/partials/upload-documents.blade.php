{{-- @extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

<form action="{{ route('product-documents.upload') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <label for="folder">Upload Product Folder (ZIP):</label>
    <input type="file" name="folder" id="folder" accept=".zip">
    <button type="submit">Upload</button>
</form>

@endsection --}}



























@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

<form id="uploadForm" enctype="multipart/form-data">
    @csrf
    <label for="folder">Upload Product Folder (ZIP):</label>
    <input type="file" name="folder" id="folder" accept=".zip" required>
    <button type="submit">Upload</button>
</form>

<!-- Progress Bar -->
<div id="progressContainer" style="display:none;">
    <label for="progress">Uploading...</label>
    <progress id="progress" value="0" max="100" style="width: 100%;"></progress>
    <span id="progressPercent">0%</span>
</div>

<!-- Success Message -->
<div id="successMessage" style="display:none; color: green;"></div>

<script>
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent the form from submitting the traditional way

        var formData = new FormData();
        formData.append('folder', document.getElementById('folder').files[0]);

        // Show progress bar
        document.getElementById('progressContainer').style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route('product-documents.upload') }}', true);

        // Track progress
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percent = (e.loaded / e.total) * 100;
                document.getElementById('progress').value = percent;
                document.getElementById('progressPercent').textContent = Math.round(percent) + '%';
            }
        });

        // Handle response
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);

                // Display success message
                if (response.message) {
                    document.getElementById('successMessage').innerText = response.message;
                    document.getElementById('successMessage').style.display = 'block';
                }

                // Hide progress bar
                document.getElementById('progressContainer').style.display = 'none';
            } else {
                alert('Error: ' + xhr.statusText);
            }
        };

        // Handle errors
        xhr.onerror = function() {
            alert('Request failed');
        };

        // Send the form data via AJAX
        xhr.send(formData);
    });
</script>

@endsection

