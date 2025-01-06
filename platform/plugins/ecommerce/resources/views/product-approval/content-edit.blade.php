@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Temp Products</title>

	<!-- Bootstrap CSS -->
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
	<!-- Select2 CSS -->
	{{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet"> --}}
</head>
<body>
	<div class="container mt-4">
		<div class="card">
			<div class="card-header d-flex justify-content-between align-items-center">
				<h5 class="mb-0">Edit Product</h5>
				<a href="{{ route('product_approval.index') }}?tab=content_tab" class="btn btn-primary btn-sm">Product Approval List</a>
			</div>
			<div class="card-body">
				<form action="{{ route('product_approval.admin_content_approve', $tempContentProduct->id) }}" method="POST">
					@csrf
					@method('PUT')

					<div class="mb-3">
						<h6>Product ID: {{ $tempContentProduct->product_id }}</h6>
						{{-- <h4>{{ $tempContentProduct->name }}</h4> --}}
					</div>

					<div class="row mt-3">
						<div class="col-md-12">
							<div class="d-flex justify-content-between align-items-center mb-2">
								<label for="name">Name</label>
								<button type="button" class="btn btn-primary btn-sm add-comment-btn" data-toggle="modal" data-target="#comment-modal" data-type="name" disabled>Add Comment</button>
							</div>
							<div class="border rounded p-3 annotatable-text">{!! $tempContentProduct->name !!}</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-12">
							<div class="d-flex justify-content-between align-items-center mb-2">
								<label for="description">Description</label>
								<button type="button" class="btn btn-primary btn-sm add-comment-btn" data-toggle="modal" data-target="#comment-modal" data-type="description" disabled>Add Comment</button>
							</div>
							<div class="border rounded p-3 annotatable-text">{!! $tempContentProduct->description !!}</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-12">
							<div class="d-flex justify-content-between align-items-center mb-2">
								<label for="content">Content</label>
								<button type="button" class="btn btn-primary btn-sm add-comment-btn" data-toggle="modal" data-target="#comment-modal" data-type="content" disabled>Add Comment</button>
							</div>
							<div class="border rounded p-3 annotatable-text">{!! $tempContentProduct->content !!}</div>
						</div>
					</div>

					<div class="row mt-3 {{ $tempContentProduct->comments->count() ? '' : 'd-none' }}" id="comments">
						<div class="col-md-12">
							<label>Issues</label>
							<table class="table table-striped table-bordered">
								<thead>
									<tr>
										<th>Field Name</th>
										<th>Highlighted Text</th>
										<th>Comment</th>
										{{-- <th>Status</th> --}}
										{{-- <th>Created By</th> --}}
										<th>Created At</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($tempContentProduct->comments as $comment)
									<tr>
										<td>{{ ucfirst($comment->comment_type) }}</td>
										<td>{!! $comment->highlighted_text !!}</td>
										<td>{!! $comment->comment !!}</td>
										{{-- <td>{{ ucfirst($comment->status) }}</td> --}}
										{{-- <td>{{ $comment->createdBy->name }}</td> --}}
										<td>{{ $comment->created_at->format('Y-m-d H:i:s') }}</td>
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-md-12">
							<label for="approval_status">Approval Status</label>
							<select class="form-select" id="content_approval_status" name="approval_status">
								@foreach ($approvalStatuses as $value => $label)
									@if(in_array($value, ['approved', 'rejected']))
										<option value="{{ $value }}">{{ $label }}</option>
									@endif
								@endforeach
							</select>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-md-12">
							<label for="remarks">Remarks</label>
							<textarea class="form-control" id="content_remarks" name="remarks" rows="4">{!! $tempContentProduct->remarks !!}</textarea>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-md-12">
							<button type="submit" class="btn btn-primary">Submit</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Comment Modal -->
	<div id="comment-modal" class="modal fade" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Add Comment</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form id="comment-form">
						@csrf
						<input type="hidden" name="comment_type" id="comment-type">
						<input type="hidden" name="highlighted_text" id="highlighted-text">
						<textarea class="form-control" name="comment" id="comment-input" rows="4" placeholder="Add your comment here" required></textarea>
					</form>
				</div>
				<div class="modal-footer">
					<button type="submit" form="comment-form" class="btn btn-primary">Save Comment</button>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
	<!-- Select2 JS -->
	{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script> --}}

	<script>
		$(document).ready(function() {
			// Function to update the "required" attribute based on approval status
			function updateContentRemarksRequirement() {
				const contentAprovalStatus = $('#content_approval_status');
				const contentRemarks = $('#content_remarks');
				if (contentAprovalStatus.val() === 'rejected') { // Replace 'rejected' with the actual value for rejection
					contentRemarks.attr('required', 'required');
				} else {
					contentRemarks.removeAttr('required');
				}
			}

			// Initialize Select2
			$('.select2').select2();

			// Enable add comment buttons when text is selected
			$('.annotatable-text').on('mouseup', function () {
				const selectedText = window.getSelection().toString().trim();
				const addCommentBtn = $(this).closest('.row').find('.add-comment-btn');

				if (selectedText.length > 0) {
					addCommentBtn.prop('disabled', false);
				} else {
					addCommentBtn.prop('disabled', true);
				}
			});

			// Set highlighted text and comment type when the "Add Comment" button is clicked
			$('.add-comment-btn').on('click', function () {
				const textType = $(this).data('type'); // "description" or "content"
				const selection = window.getSelection();

				// Get the selected range
				if (selection.rangeCount > 0) {
					const range = selection.getRangeAt(0);

					// Create a temporary container to hold the HTML content
					const tempDiv = document.createElement('div');
					tempDiv.appendChild(range.cloneContents());

					// Get the HTML content of the selected text
					const selectedHtml = tempDiv.innerHTML;

					// Set the highlighted text and comment type in the modal
					$('#highlighted-text').val(selectedHtml); // Save the HTML markup
					$('#comment-type').val(textType);
				}
			});

			$('#content_approval_status').on('change', updateContentRemarksRequirement);

		});

		// Handle form submission for adding comments
		document.getElementById('comment-form').addEventListener('submit', function (e) {
			let tempContentProductId = '{{ $tempContentProduct->id}}'
			e.preventDefault();
			const formData = new FormData(this);
			fetch(`/admin/product-approval/${tempContentProductId}/comments`, {
				method: 'POST',
				body: formData,
				headers: {
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
				}
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					alert('Comment saved successfully');
					location.reload();
				} else {
					alert('Something went wrong, please open the console for more details.');
					console.error('Error:', data);
				}
			})
			.catch(error => {
				alert('An error occurred while saving the comment, please open the console for details.');
				console.error('Fetch error:', error);
			});
		});
	</script>

</body>
@endsection
