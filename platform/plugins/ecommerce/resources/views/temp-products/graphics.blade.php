@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Temp Products</title>

	<!-- Bootstrap CSS -->
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">

	<!-- Custom CSS (Optional) -->
	<style>
		.edit-icon {
			cursor: pointer;
			font-size: 18px;
		}
	</style>
</head>
<body>
	<div class="row">
		<div class="col-md-3 mb-3">
			<label class="form-label bg-info text-white text-center py-3 h6">Content In Progress<br/><span class="h2">{{ $tempGraphicsProducts->where('approval_status', 'in-process')->count() }}</span></label>
		</div>
		<div class="col-md-3 mb-3">
			<label class="form-label bg-warning text-white text-center py-3 h6">Submitted for Approval<br/><span class="h2">{{ $tempGraphicsProducts->where('approval_status', 'pending')->count() }}</span></label>
		</div>
		<div class="col-md-3 mb-3">
			<label class="form-label bg-success text-white text-center py-3 h6">Ready to Publish<br/><span class="h2">{{ $tempGraphicsProducts->where('approval_status', 'approved')->count() }}</span></label>
		</div>
		<div class="col-md-3 mb-3">
			<label class="form-label bg-danger text-white text-center py-3 h6">Rejected for Corrections<br/><span class="h2">{{ $tempGraphicsProducts->sum('rejection_count') }}</span></label>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Product ID</th>
					<th>Product Name</th>
					<th>SKU</th>
					<th>Approval Status</th>
					<th>Edit</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($tempGraphicsProducts as $tempGraphicsProduct)
					<tr id="product-row-{{ $tempGraphicsProduct->id }}">
						<td>{{ $tempGraphicsProduct->product_id }}</td>
						<td class="product-name">{{ $tempGraphicsProduct->name }}</td>
						<td class="product-description">{{ $tempGraphicsProduct->sku }}</td>
						<td class="product-description">{{ $approvalStatuses[$tempGraphicsProduct->approval_status] ?? '' }}</td>
						<td>
							@if($tempGraphicsProduct->approval_status == 'in-process' || $tempGraphicsProduct->approval_status == 'rejected')
								<button type="button" id="edit_graphics_modal" data-toggle="modal" data-target="#editGraphicsModal" data-product="{{ htmlspecialchars(json_encode($tempGraphicsProduct->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') }}">
									<i class="fas fa-pencil-alt"></i>
								</button>
							@endif
						</td>
					</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<!-- Graphics Modal -->
	<div class="modal fade" id="editGraphicsModal" tabindex="-1" role="dialog" aria-labelledby="editGraphicsModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="editgraphicsModalLabel">Edit Product</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form action="{{ route('temp-products.graphics_update') }}" method="POST">
						@csrf
						<div class="product-card">
							<div class="product-header">
								<h6>Product ID: <span id="graphics_temp_header_id"></span></h6>
								<h4 id="graphics_temp_header_name"></h4>
								<input type="hidden" id="graphics_temp_id" name="id">
							</div>

							<div class="row">
								<div class="mb-3 col-md-6">
									<label for="sku" class="form-label">SKU</label>
									<input type="text" class="form-control" id="graphics_sku" name="sku">
								</div>
							</div>
							<div class="row">
								<div class="mb-3 col-md-12">
									<div id="image-container"></div>
								</div>
							</div>
							<div class="row">
								<div class="mb-3 col-md-12">
									<div id="video-container"></div>
								</div>
							</div>
							<div class="row">
								<div class="mb-3 col-md-12">
									<div id="document-container"></div>
								</div>
							</div>

							<div class="mb-3">
								<input type="hidden" id="graphics_initial_approval_status" name="initial_approval_status">
								<label for="graphics_approval_status" class="form-label">Approval Status</label>
								<select class="form-select" id="graphics_approval_status" name="approval_status">
									@foreach ($approvalStatuses as $value => $label)
									<option value="{{ $value }}">{{ $label }}</option>
									@endforeach
								</select>
							</div>

							<div class="row g-3 mb-3 ms-1">
								<div class="col-md-4 d-flex align-items-center">
									<div class="form-check">
										<input class="form-check-input me-2" type="checkbox" id="pricing_in_process" name="in_process" value="1">
										<label class="form-check-label" for="in_process">Is Draft</label>
									</div>
								</div>
							</div>

							<div class="mb-3">
								<label for="graphics_remarks" class="form-label">Remarks</label>
								<textarea class="form-select" id="graphics_remarks" name="remarks" readonly></textarea>
							</div>

							<button type="submit" class="btn btn-primary">Submit</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<!-- Graphics Modal -->

	<!-- jQuery -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<!-- Bootstrap JS -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

	<style>
		.product-card {
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 15px;
			background-color: #f9f9f9;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
			display: flex;
			flex-direction: column;
			justify-content: space-between;
		}
		.product-header {
			margin-bottom: 10px;
		}
		.product-status {
			margin: 10px 0;
			font-weight: bold;
		}
		.product-description ,   .product-content {
			margin-bottom: 10px;
		}
		.approval-status-container, .edit-button-container {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.editor {
			width: 100%;
			height: 150px; /* Set the height of the editor */
		}
		/* Optional styling to reinforce default link appearance */
		.product-link {
			color: blue; /* Ensure blue color for links */
			text-decoration: underline; /* Underline the link text */
			cursor: pointer; /* Show a pointer cursor */
		}
		.product-link:hover {
			text-decoration: none; /* Remove underline on hover */
			color: darkblue; /* Slightly darker blue on hover */
		}
	</style>

	<script>
		// Function to update the "required" attribute based on approval status
		function updateGraphicsRemarksRequirement() {
			const graphicsAprovalStatus = $('#graphics_approval_status');
			const graphicsRemarks = $('#graphics_remarks');
			if (graphicsAprovalStatus.val() === 'rejected') { // Replace 'rejected' with the actual value for rejection
				graphicsRemarks.attr('required', 'required');
			} else {
				graphicsRemarks.removeAttr('required');
			}
		}
		$(document).on('click', '#edit_graphics_modal', function () {
			/* Get the product data from the button's data-product attribute */
			const productData = $(this).attr('data-product');
			const decodedData = $('<textarea/>').html(productData).text();

			/* Parse the JSON string into a JavaScript object */
			const product = JSON.parse(decodedData);

			/* Populate the modal fields */
			$('#graphics_temp_header_id').text(product.product_id);
			if (product.product_id && product.name) {
				const editProductLink = `{{url('admin/ecommerce/products/edit/')}}/${product.product_id}`;
				const productLink = $('<a>', {
					href: editProductLink,
					text: product.name,
					target: '_blank',
					class: 'product-link', // Optional: Add a class for styling
				});
				$('#graphics_temp_header_name').empty().append(productLink);
			}

			// $('#graphics_temp_header_name').text(product.name);
			$('#graphics_temp_id').val(product.id);
			$('#graphics_sku').val(product.sku);

			console.log('Parsed Product:', product.video_path); // Example:(string format need to convert in array) ["69436-2.jpg", "64900k.webp", "70250k.webp", "70118k.webp"]

			/* Clear any existing images in the container */
			$('#image-container').empty();

			/* Generate dynamic image links and append them to the image container */
			let imagesArray;
			if (Array.isArray(product.images)) {
				imagesArray = product.images;
			} else {
			    imagesArray = JSON.parse(product.images);
			}

			const baseUrl = "{{url('storage')}}";
			$('#image-container').append('<h5>Images</h5>');
			imagesArray.forEach(function (image) {
				const imageLink = `${baseUrl}/${image}`;
				const imgElement = $('<a>', {
					href: imageLink,
					'data-lightbox': 'gallery', // Enables lightbox grouping
					'data-title': 'Zoomable Image',
					html: $('<img>', {
						src: imageLink,
						alt: 'Dynamic Image',
						style: 'width: 200px; height: auto; margin: 5px; border: 1px solid #ccc;'
					})
				});
				$('#image-container').append(imgElement);
			});




			/* Clear any existing videos in the container */
			$('#video-container').empty();

			/* Parse and validate the video paths */
			let videoArray;
			try {
				videoArray = Array.isArray(product.video_path)
					? product.video_path
					: JSON.parse(product.video_path);
			} catch (error) {
				console.error('Failed to parse video paths:', error);
				videoArray = [];
			}

			/* Append videos to the video container */
			const baseUrl1 = "{{asset('storage')}}";
			$('#video-container').append('<h5>Videos</h5>');
			if(videoArray.length > 0) {
				videoArray.forEach(function (video) {
					const videoElement = $('<div>', { class: 'uploaded-video mt-2' }).append(
						$('<video>', {
							width: 320,
							height: 240,
							controls: true,
						}).append(
							$('<source>', {
								src: `${baseUrl1}/${video}`,
								type: 'video/mp4',
							})
						),

					);
					$('#video-container').append(videoElement);
				});
			} else {
				$('#video-container').append('<p>No videos available.</p>');
			}


			/* Clear the existing documents in the container */
			$('#document-container').empty();

			/* Parse and validate the documents */
			let documents;
			try {
				documents = JSON.parse(product.documents);
			} catch (error) {
				console.error('Failed to parse documents:', error);
				documents = {};
			}
			$('#document-container').append('<h5>Existing Documents</h5>');
			/* Check if documents exist and append them */
			if (Object.keys(documents).length > 0) {
				const documentList = $('<ul>', { class: 'uploaded-docs' });

				$.each(documents, function (key, document) {
					const documentLink = $('<a>', {
						href: `{{ url('storage') }}/${document.path}`,
						text: document.title,
						target: '_blank',
					});

					const documentItem = $('<li>').append(documentLink);
					documentList.append(documentItem);
				});

				$('#document-container').append(documentList);
			} else {
				$('#document-container').append('<p>No documents available.</p>');
			}
			$('#graphics_initial_approval_status').val(product.approval_status);
			$('#graphics_approval_status').val(product.approval_status);
			$('#graphics_remarks').val(product.remarks);

			// Initial check when the page loads
			updateGraphicsRemarksRequirement();
		});

		// Update requirement whenever the approval status changes
		$('#graphics_approval_status').on('change', updateGraphicsRemarksRequirement);
	</script>
</body>

@endsection