{{-- <div class="document-upload">
    <label for="documents">{{ __('Upload Documents (PDFs)') }}</label>
    <input type="file" name="documents[]" id="documents" class="file-input" multiple accept=".pdf">
    
    <div class="upload-preview" id="uploadPreview">
        <h4>{{ __('Uploaded Document Previews') }}</h4>
        <div class="preview-box" id="previewBox"></div>
    </div>
    
    <div class="loader" id="loader" style="display: none;">
        <div class="progress" id="progressContainer" style="display: none;">
            <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
            <span id="progressText">0%</span>
        </div>
    </div>

    @if (!empty($documents))
        <h4>{{ __('Existing Documents') }}</h4>
        <ul class="uploaded-docs">
            @foreach (json_decode($documents) as $document)
                <li>
                    <a href="{{ Storage::url($document) }}" target="_blank">{{ basename($document) }}</a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

<style>
/* Keep your CSS styles here */
</style>

<script>
document.getElementById('documents').addEventListener('change', function(event) {
    const files = event.target.files;
    const previewBox = document.getElementById('previewBox');
    const loader = document.getElementById('loader');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    // Clear existing previews
    previewBox.innerHTML = '';
    loader.style.display = 'block';
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.textContent = '0%';

    Array.from(files).forEach(file => {
        const reader = new FileReader();

        reader.onloadstart = function() {
            // Simulating progress
            let progress = 0;
            const interval = setInterval(() => {
                if (progress < 100) {
                    progress += 10;
                    progressBar.style.width = progress + '%';
                    progressText.textContent = progress + '%';
                } else {
                    clearInterval(interval);
                }
            }, 100);
        };

        reader.onload = function(e) {
            if (file.type === 'application/pdf') {
                const previewItem = document.createElement('div');
                previewItem.classList.add('preview-item');
                previewItem.innerHTML = `
                    <span class="remove-btn" onclick="removeFile(this)">✖</span>
                    <iframe src="${e.target.result}" frameborder="0"></iframe>
                `;
                previewBox.appendChild(previewItem);
            } else {
                alert('Only PDF files are supported for preview.');
            }
        };

        reader.readAsDataURL(file);
    });

    // Hide loader after simulating upload time
    setTimeout(() => {
        loader.style.display = 'none';
        progressContainer.style.display = 'none';
    }, 2000);
});

function removeFile(element) {
    element.parentElement.remove();
}
</script> --}}




{{-- <div class="document-upload">
    <label for="documents">{{ __('Upload Documents (PDFs)') }}</label>
    <input type="file" name="documents[]" id="documents" class="file-input" multiple accept=".pdf">

    <div class="title-inputs">
        <!-- Fixed Titles -->
        <div>
            <label>{{ __('Document 1 Title (Fixed)') }}</label>
            <input type="text" value="Document 1" disabled>
        </div>
        <div>
            <label>{{ __('Document 2 Title (Fixed)') }}</label>
            <input type="text" value="Document 2" disabled>
        </div>
        <div>
            <label>{{ __('Document 3 Title (Fixed)') }}</label>
            <input type="text" value="Document 3" disabled>
        </div>
        <div>
            <label>{{ __('Document 4 Title (Fixed)') }}</label>
            <input type="text" value="Document 4" disabled>
        </div>
        <!-- Custom Titles -->
        <div>
            <label>{{ __('Document 5 Title (Custom)') }}</label>
            <input type="text" name="titles[4]" placeholder="Enter Custom Title">
        </div>
        <div>
            <label>{{ __('Document 6 Title (Custom)') }}</label>
            <input type="text" name="titles[5]" placeholder="Enter Custom Title">
        </div>
    </div>
    
    <div class="upload-preview" id="uploadPreview">
        <h4>{{ __('Uploaded Document Previews') }}</h4>
        <div class="preview-box" id="previewBox"></div>
    </div>
    
    <div class="loader" id="loader" style="display: none;">
        <div class="progress" id="progressContainer" style="display: none;">
            <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
            <span id="progressText">0%</span>
        </div>
    </div>

    @if (!empty($documents))
        <h4>{{ __('Existing Documents') }}</h4>
        <ul class="uploaded-docs">
            @foreach (json_decode($documents) as $document)
                <li>
                    <a href="{{ Storage::url($document) }}" target="_blank">{{ basename($document) }}</a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

<style>
/* Keep your CSS styles here */
</style>

<script>
document.getElementById('documents').addEventListener('change', function(event) {
    const files = event.target.files;
    const previewBox = document.getElementById('previewBox');
    const loader = document.getElementById('loader');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    // Clear existing previews
    previewBox.innerHTML = '';
    loader.style.display = 'block';
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.textContent = '0%';

    Array.from(files).forEach(file => {
        const reader = new FileReader();

        reader.onloadstart = function() {
            // Simulating progress
            let progress = 0;
            const interval = setInterval(() => {
                if (progress < 100) {
                    progress += 10;
                    progressBar.style.width = progress + '%';
                    progressText.textContent = progress + '%';
                } else {
                    clearInterval(interval);
                }
            }, 100);
        };

        reader.onload = function(e) {
            if (file.type === 'application/pdf') {
                const previewItem = document.createElement('div');
                previewItem.classList.add('preview-item');
                previewItem.innerHTML = `
                    <span class="remove-btn" onclick="removeFile(this)">✖</span>
                    <iframe src="${e.target.result}" frameborder="0"></iframe>
                `;
                previewBox.appendChild(previewItem);
            } else {
                alert('Only PDF files are supported for preview.');
            }
        };

        reader.readAsDataURL(file);
    });

    // Hide loader after simulating upload time
    setTimeout(() => {
        loader.style.display = 'none';
        progressContainer.style.display = 'none';
    }, 2000);
});

function removeFile(element) {
    element.parentElement.remove();
}
</script> --}}












{{-- <div class="document-upload">
    <h4>{{ __('Upload Documents (PDFs)') }}</h4>

    @php
    $titles = ['Specsheet', 'Manual', 'Warranty', 'Brochure'];

@endphp

<div class="documents-container">
    @for ($i = 0; $i < 6; $i++)
        <div class="document-item">
            <label for="document_{{ $i }}">{{ __('Document ' . ($i + 1)) }}</label>
            <input type="file" name="documents[]" id="document_{{ $i }}" class="file-input" accept=".pdf" onchange="handleFileUpload(this, {{ $i }})">

            @if ($i < 4)
                <input type="text" value="{{ $titles[$i] }}" disabled>
            @else
                <input type="text" name="titles[{{ $i }}]" placeholder="Enter Custom Title">
            @endif

            <div class="preview-box" id="previewBox_{{ $i }}"></div>
        </div>
    @endfor
</div> --}}


<div class="document-upload">
    <h4>{{ __('Upload Documents (PDFs)') }}</h4>

    @php
        $titles = ['Specsheet', 'Manual', 'Warranty', 'Brochure'];
    @endphp

    <div class="documents-container">
        @for ($i = 0; $i < 6; $i++)
            <div class="document-item">
                <label for="document_{{ $i }}">{{ __('Document ' . ($i + 1)) }}</label>
                <input type="file" name="documents[]" id="document_{{ $i }}" class="file-input" accept=".pdf" onchange="handleFileUpload(this, {{ $i }})">

                @if ($i < count($titles))
                    <input type="text" value="{{ $titles[$i] }}" disabled>
                @else
                    <input type="text" name="titles[{{ $i }}]" placeholder="Enter Custom Title">
                @endif

                <div class="preview-box" id="previewBox_{{ $i }}"></div>
            </div>
        @endfor
    </div>
    <div class="loader" id="loader" style="display: none;">
        <div class="progress" id="progressContainer" style="display: none;">
            <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
            <span id="progressText">0%</span>
        </div>
    </div>



    {{-- @if (!empty($documents))
    <h4>{{ __('Existing Documents') }}</h4>
    <ul class="uploaded-docs">
        @foreach (json_decode($documents) as $document)
            <li>
                <a href="{{ Storage::url($document->path) }}" target="_blank">{{ $document->title }}</a>
            </li>
        @endforeach
    </ul>
@endif

</div> --}}

@if (!empty($documents))
<h4>{{ __('Existing Documents') }}</h4>
<ul class="uploaded-docs">
    @foreach (json_decode($documents) as $document)
        <li>
            <a href="{{ Storage::url($document->path) }}" target="_blank">{{ $document->title }}</a>
        </li>
    @endforeach
</ul>
@endif
</div>

<style>
.document-upload {
    margin: 20px 0;
}
.documents-container {
    display: flex;
    flex-direction: column;
}
.document-item {
    margin-bottom: 20px;
}
.preview-box {
    margin-top: 10px;
}
.preview-item {
    display: flex;
    align-items: center;
}
.preview-item iframe {
    width: 100px;
    height: 100px;
    margin-right: 10px;
}
.remove-btn {
    cursor: pointer;
    color: red;
    margin-left: 10px;
}
</style>

<script>
function handleFileUpload(input, index) {
    const file = input.files[0];
    const previewBox = document.getElementById(`previewBox_${index}`);
    
    // Clear existing previews
    previewBox.innerHTML = '';
    
    if (file && file.type === 'application/pdf') {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.classList.add('preview-item');
            previewItem.innerHTML = `
                <iframe src="${e.target.result}" frameborder="0"></iframe>
                <span class="remove-btn" onclick="removeFile(this)">✖</span>
            `;
            previewBox.appendChild(previewItem);
        };

        reader.readAsDataURL(file);
    } else {
        alert('Only PDF files are supported for preview.');
    }
}

function removeFile(element) {
    element.parentElement.remove();
}
</script>
