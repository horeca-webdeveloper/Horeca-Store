@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Temp Products</title>

	<!-- Bootstrap CSS -->
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
	<div class="row text-center">
		<div class="col-md-3 mb-3">
			<div class="bg-info text-white py-3 h-100 d-flex flex-column justify-content-center">
				<h6>Content In Progress</h6>
				<h2>{{ $tempContentProducts->where('approval_status', 'in-process')->count() }}</h2>
			</div>
		</div>
		<div class="col-md-3 mb-3">
			<div class="bg-warning text-white py-3 h-100 d-flex flex-column justify-content-center">
				<h6>Submitted for Approval</h6>
				<h2>{{ $tempContentProducts->where('approval_status', 'pending')->count() }}</h2>
			</div>
		</div>
		<div class="col-md-3 mb-3">
			<div class="bg-success text-white py-3 h-100 d-flex flex-column justify-content-center">
				<h6>Ready to Publish</h6>
				<h2>{{ $tempContentProducts->where('approval_status', 'approved')->count() }}</h2>
			</div>
		</div>
		<div class="col-md-3 mb-3">
			<div class="bg-danger text-white py-3 h-100 d-flex flex-column justify-content-center">
				<h6>Rejected for Corrections</h6>
				<h2>{{ $tempContentProducts->sum('rejection_count') }}</h2>
			</div>
		</div>
	</div>

	<div class="table-responsive">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Product ID</th>
					<th>Product Name</th>
					<th>Created At</th>
					<th>Approval Status</th>
					<th>Edit</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($tempContentProducts as $tempContentProduct)
				{{-- @dd($tempContentProduct->comments->toArray()) --}}
					<tr>
						<td>{{ $tempContentProduct->product_id }}</td>
						<td>{{ $tempContentProduct->name }}</td>
						<td>{{ $tempContentProduct->created_at->format('Y-m-d H:i:s') }}</td>
						<td>{{ $approvalStatuses[$tempContentProduct->approval_status] ?? '' }}</td>
						<td>
							@if($tempContentProduct->approval_status == 'in-process' || $tempContentProduct->approval_status == 'rejected')
								<button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editContentModal"
									data-id="{{ $tempContentProduct->id }}"
									data-name="{{ $tempContentProduct->name }}"
									data-description="{{ $tempContentProduct->description }}"
									data-content="{{ $tempContentProduct->content }}"
									data-remarks="{{ $tempContentProduct->remarks }}"
									data-product_id="{{ $tempContentProduct->product_id }}"
									data-comments="{{json_encode($tempContentProduct->comments->toArray())}}">
									<i class="fas fa-pencil-alt"></i> Edit
								</button>
							@endif
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<!-- Content Modal -->
	<div class="modal fade" id="editContentModal" tabindex="-1" aria-labelledby="editContentModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Edit Product</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form action="{{ route('temp-products.content_update') }}" method="POST">
						@csrf
						<div class="mb-3">
							<h6>Product ID: <span id="content_product_id"></span></h6>
							<input type="hidden" id="content_id" name="id">
						</div>

						<div class="form-group">
							<label for="content_name">Name</label>
							<textarea class="form-control" id="content_name" name="name"></textarea>
						</div>

						<div class="form-group">
							<label for="content_description">Description</label>
							<textarea class="form-control" id="content_description" name="description"></textarea>
						</div>

						<div class="form-group">
							<label for="content_content">Content</label>
							<textarea class="form-control" id="content_content" name="content"></textarea>
						</div>

						{{-- Comments Section --}}
						<div class="row mt-3 d-none" id="comments">
							<div class="col-md-12">
								<label>Issues</label>
								<table class="table table-striped table-bordered">
									<thead>
										<tr>
											<th>Field Name</th>
											<th>Highlighted Text</th>
											<th>Comment</th>
											<th>Created At</th>
										</tr>
									</thead>
									<tbody>
										{{-- Data populated dynamically by jQuery --}}
									</tbody>
								</table>
							</div>
						</div>

						<div class="form-check ms-3">
							<input type="checkbox" class="form-check-input" id="pricing_in_process" name="in_process" value="1">
							<label class="form-check-label" for="pricing_in_process">Is Draft</label>
						</div>

						<div class="form-group mt-3">
							<label for="content_remarks">Remarks</label>
							<textarea class="form-control" id="content_remarks" name="remarks" readonly></textarea>
						</div>
						<button type="submit" class="btn btn-primary">Submit</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- JS Dependencies -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
	<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

	<script>
		// Initialize modal content dynamically
		$(document).on('click', '[data-target="#editContentModal"]', function () {
			let productID = $(this).data('product_id');
			let id = $(this).data('id');
			let name = $(this).data('name');
			let description = $(this).data('description');
			let content = $(this).data('content');
			let remarks = $(this).data('remarks');

			// Populate modal fields
			$('#content_product_id').text(productID);
			$('#content_id').val(id);
			$('#content_name').val(name);
			$('#content_remarks').val(remarks);

			ClassicEditor.create(document.querySelector('#content_description')).then(editor => {
				editor.setData(description);
			}).catch(error => console.error(error));

			ClassicEditor.create(document.querySelector('#content_content')).then(editor => {
				editor.setData(content);
			}).catch(error => console.error(error));






			// Example data for comments (replace with server-side data as needed)
			var comments = $(this).data('comments'); // Get comments string from the button
			// console.log(commentsData);
			// var comments = []; // Initialize as an empty array

			// // Parse comments if valid
			// if (commentsData) {
			// 	try {
			// 		comments = JSON.parse(commentsData); // Convert JSON string to object
			// 	} catch (e) {
			// 		console.error("Error parsing comments:", e);
			// 	}
			// }
			console.log(comments);
			var tbody = $("#comments tbody");
			// Clear previous rows
			tbody.empty();

			// Populate comments table
			if (comments && comments.length > 0) {
				$("#comments").removeClass("d-none"); // Show comments section
				comments.forEach(function (comment) {
					var row = `
						<tr>
							<td>${comment.comment_type}</td>
							<td>${comment.highlighted_text}</td>
							<td>${comment.comment}</td>
							<td>${new Date(comment.created_at).toLocaleString()}</td>
						</tr>
					`;
					tbody.append(row);
				});
			} else {
				$("#comments").addClass("d-none"); // Hide comments section if no comments
			}
		});
	</script>
</body>
@endsection
