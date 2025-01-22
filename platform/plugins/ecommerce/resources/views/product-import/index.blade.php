@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        });
    </script>
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-success" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{ session('success') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    @php(session()->forget('success'))
@endif

@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        });
    </script>
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="successModalLabel">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{ session('error') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    @php(session()->forget('error'))
@endif

<div class="container mt-1">
	<h2>Product Import</h2>
	<form action="{{ route('products.upload') }}" method="POST" enctype="multipart/form-data">
		@csrf
		<div class="form-group">
			<label for="fileInput">Upload File:</label>
			<input type="file" name="upload_file" id="fileInput" class="form-control">
		</div>
		<button type="submit" class="btn btn-primary">Upload</button>
	</form>
</div>

@endsection