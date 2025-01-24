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
	<div class="mb-3">
		<label class="label-field">{{ __("Download Template") }}</label>
		<a href="{{ asset('templates/product-import-template.csv') }}"><button class="button-set"><i class="fa fa-download"></i> {{ __("Download") }}</button></a>
	</div>
	<form action="{{ route('tools.data-synchronize.import.products.upload') }}" method="POST" enctype="multipart/form-data">
		@csrf

		<div class="form-group">
			<label for="fileInput">Upload File:</label>
			<input class="form-control" type="file" name="upload_file" id="fileInput" class="form-control">
		</div>
		<button type="submit" class="btn btn-primary">Upload</button>
	</form>

	<h2 class="mt-5">Import Logs</h2>
	<table class="table table-striped table-bordered mt-3">
		<thead>
			<tr>
				<th>Module</th>
				<th>Action</th>
				<th>Identifier</th>
				<th>Status</th>
				<th>Created By</th>
				<th>Created At</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			@foreach($logs as $log)
			<tr>
				<td>{{ $log->module }}</td>
				<td>{{ $log->action }}</td>
				<td>{{ $log->identifier }}</td>
				<td><span class="statusStyle" @if($log->status=="Completed") style="background-color: green" @elseif($log->status=="Failed") style="background-color: red" @elseif($log->status=="In-progress") style="background-color: #d7d73f" @endif>{{ $log->status }}</span></td>
				<td>{{ $log->createdBy->name }}</td>
				<td>{{ $log->created_at }}</td>
				<td>
					<a href="{{ route('tools.data-synchronize.import.products.import_view', $log->id) }}" class="btn btn-sm btn-info">View</a>
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>
</div>
<style type="text/css">
	.statusStyle {
		border-radius: 5px;
		padding: 5px;
		color: white;
	}
</style>
@endsection