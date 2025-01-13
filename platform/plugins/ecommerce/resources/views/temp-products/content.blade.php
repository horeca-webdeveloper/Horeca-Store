@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Temp Products</title>

	<!-- Bootstrap CSS -->
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
	<!-- Select2 CSS -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
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
				<tr>
					<td>{{ $tempContentProduct->product_id }}</td>
					<td>{{ $tempContentProduct->name }}</td>
					<td>{{ $tempContentProduct->created_at->format('Y-m-d H:i:s') }}</td>
					<td>{{ $approvalStatuses[$tempContentProduct->approval_status] ?? '' }}</td>
					<td>
						@if($tempContentProduct->approval_status == 'in-process' || $tempContentProduct->approval_status == 'rejected')
						<button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editContentModal" data-product="{{ htmlspecialchars(json_encode($tempContentProduct->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') }}" data-comments="{{json_encode($tempContentProduct->comments->toArray())}}">
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
							<label for="content_category_ids">Categories</label>
							<select class="form-control select2" multiple="multiple" id="content_category_ids" name="category_ids[]"></select>
						</div>

						<div class="form-group">
							<label for="content_google_shopping_category">Google Shopping Category</label>
							<textarea class="form-control" id="content_google_shopping_category" name="google_shopping_category"></textarea>
						</div>

						<div class="form-group">
							<label for="content_product_type_ids">Product Types</label>
							<select class="form-control select2" multiple="multiple" id="content_product_type_ids" name="product_type_ids[]"></select>
						</div>

						<div class="form-group">
							<label for="content_name">Name</label>
							<textarea class="form-control" id="content_name" name="name"></textarea>
						</div>

						<div class="form-group">
							<label for="content_slug">Slug</label>
							<textarea class="form-control" id="content_slug" name="slug"></textarea>
						</div>

						<div class="form-group">
							<label for="content_sku">SKU</label>
							<input class="form-control" type="text" id="content_sku" name="sku">
						</div>

						<div class="form-group">
							<label for="content_description">Description</label>
							<textarea class="form-control" id="content_description" name="description"></textarea>
						</div>

						<div class="form-group">
							<label for="content_content">Content</label>
							<textarea class="form-control" id="content_content" name="content"></textarea>
						</div>

						<div class="form-group">
							<label for="content_warranty_information">Warranty Information</label>
							<textarea class="form-control" id="content_warranty_information" name="warranty_information"></textarea>
						</div>

						<div class="form-group">
							<label for="content_seo_title">SEO Title</label>
							<textarea class="form-control" id="content_seo_title" name="seo_title"></textarea>
						</div>

						<div class="form-group">
							<label for="content_seo_description">SEO Description</label>
							<textarea class="form-control" id="content_seo_description" name="seo_description"></textarea>
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
	<!-- Select2 JS -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

	<script>
		// References to CKEditor instances
		let descriptionEditor = null;
		let contentEditor = null;
		let warrantyInfoEditor = null;


		// Initialize modal content dynamically
		$(document).on('click', '[data-target="#editContentModal"]', function () {
			const productData = $(this).attr('data-product');
			const decodedData = $('<textarea/>').html(productData).text();

			// Parse the JSON string into a JavaScript object
			const product = JSON.parse(decodedData);

			// Populate modal fields
			$('#content_product_id').text(product.product_id);
			$('#content_id').val(product.id);
			$('#content_name').val(product.name);
			$('#content_remarks').val(product.remarks);
			$('#content_google_shopping_category').val(product.google_shopping_category);
			$('#content_slug').val(product.slug);
			$('#content_sku').val(product.sku);
			$('#content_seo_title').val(product.seo_title);
			$('#content_seo_description').val(product.seo_description);

			// Initialize or update the CKEditor instance for description
			if (descriptionEditor) {
				descriptionEditor.setData(product.description); // Update existing editor's data
			} else {
				ClassicEditor.create(document.querySelector('#content_description'))
				.then(editor => {
					descriptionEditor = editor;
					editor.setData(product.description);
				})
				.catch(error => console.error(error));
			}

			// Initialize or update the CKEditor instance for content
			if (contentEditor) {
				contentEditor.setData(product.content); // Update existing editor's data
			} else {
				ClassicEditor.create(document.querySelector('#content_content'))
				.then(editor => {
					contentEditor = editor;
					editor.setData(product.content);
				})
				.catch(error => console.error(error));
			}

			// Initialize or update the CKEditor instance for description
			if (warrantyInfoEditor) {
				warrantyInfoEditor.setData(product.warranty_information); // Update existing editor's data
			} else {
				ClassicEditor.create(document.querySelector('#content_warranty_information'))
				.then(editor => {
					warrantyInfoEditor = editor;
					editor.setData(product.warranty_information);
				})
				.catch(error => console.error(error));
			}

			var comments = $(this).data('comments'); // Get comments string from the button

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

			// Product Categories Data
			const productCategories = @json($productCategories);
			let selectedCategoryIds = JSON.parse(product.category_ids);

			// Populate the category select box
			let $categorySelect = $('#content_category_ids');

			productCategories.forEach((category) => {
				// First-level option group
				let $optgroup = $(`<optgroup label="${category.name}"></optgroup>`);

				category.childrens.forEach((subCategory) => {
					// Add subcategory options with indentation
					$optgroup.append(`<option disabled>&nbsp;&nbsp;${subCategory.name}</option>`);

					subCategory.childrens.forEach((childCategory) => {
						// Add child category options with further indentation
						const isSelected = selectedCategoryIds.includes(String(childCategory.id)) ? 'selected' : '';
						$optgroup.append(`
				<option value="${childCategory.id}" ${isSelected}>
					&nbsp;&nbsp;&nbsp;&nbsp;${childCategory.name}
				</option>
						`);
					});
				});

				$categorySelect.append($optgroup);
			});

			// Initialize Select2 for Categories
			$categorySelect.select2({
				placeholder: "Select Categories",
				allowClear: true,
				width: '100%',
			});

			// Product Types Data
			const productTypes = @json($productTypes);
			let selectedProductTypeIds = JSON.parse(product.product_type_ids);

			// Populate the product type select element
			let $productTypeSelect = $('#content_product_type_ids');
			$.each(productTypes, function (key, value) {
				const isSelected = selectedProductTypeIds.includes(String(key)) ? 'selected' : '';
				$productTypeSelect.append(`<option value="${key}" ${isSelected}>${value}</option>`);
			});

			// Initialize Select2 for Product Types
			$productTypeSelect.select2({
				placeholder: "Select Product Type",
				allowClear: true,
				width: '100%',
			});
		});


		// Destroy CKEditor instances when modal is closed
		$('#editContentModal').on('hidden.bs.modal', function () {
			if (descriptionEditor) {
				descriptionEditor.destroy().then(() => {
					descriptionEditor = null;
				});
			}
			if (contentEditor) {
				contentEditor.destroy().then(() => {
					contentEditor = null;
				});
			}
			if (warrantyInfoEditor) {
				warrantyInfoEditor.destroy().then(() => {
					warrantyInfoEditor = null;
				});
			}
		});
	</script>
</body>
@endsection
