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
			<a href="{{ route(Route::currentRouteName(), ['type' => 'in-process']) }}" class="text-decoration-none custom-link">
				<label class="form-label bg-info text-white text-center py-3 h6">
					Content In Progress<br/>
					<span class="h2">{{ $tempContentProducts->where('approval_status', 'in-process')->count() }}</span>
				</label>
			</a>
		</div>
		<div class="col-md-3 mb-3">
			<a href="{{ route(Route::currentRouteName(), ['type' => 'pending']) }}" class="text-decoration-none custom-link">
				<label class="form-label bg-warning text-white text-center py-3 h6">
					Submitted for Approval<br/>
					<span class="h2">{{ $tempContentProducts->where('approval_status', 'pending')->count() }}</span>
				</label>
			</a>
		</div>
		<div class="col-md-3 mb-3">
			<a href="{{ route(Route::currentRouteName(), ['type' => 'approved']) }}" class="text-decoration-none custom-link">
				<label class="form-label bg-success text-white text-center py-3 h6">
					Ready to Publish<br/>
					<span class="h2">{{ $tempContentProducts->where('approval_status', 'approved')->count() }}</span>
				</label>
			</a>
		</div>
		<div class="col-md-3 mb-3">
			<a href="{{ route(Route::currentRouteName(), ['type' => 'rejected']) }}" class="text-decoration-none custom-link">
				<label class="form-label bg-danger text-white text-center py-3 h6">
					Rejected for Corrections<br/>
					<span class="h2">{{ $tempContentProducts->sum('rejection_count') }}</span>
				</label>
			</a>
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
				@php
					if(!empty($type)) {
						$tempContentProducts = $tempContentProducts->where('approval_status', $type);
					}
				@endphp
				@foreach ($tempContentProducts as $tempContentProduct)
				<tr>
					<td>{{ $tempContentProduct->product_id }}</td>
					<td>{{ $tempContentProduct->name }}</td>
					<td>{{ $tempContentProduct->created_at->format('Y-m-d H:i:s') }}</td>
					<td>{{ $approvalStatuses[$tempContentProduct->approval_status] ?? '' }}</td>
					<td>
						@if($tempContentProduct->approval_status == 'in-process' || $tempContentProduct->approval_status == 'rejected')
							<button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#editContentModal" data-product="{{ htmlspecialchars(json_encode($tempContentProduct->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') }}" data-comments="{{json_encode($tempContentProduct->comments->toArray())}}">
								<i class="fas fa-pencil-alt"></i> Edit
							</button>
						@else
							<button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#viewContentModal" data-product="{{ htmlspecialchars(json_encode($tempContentProduct->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') }}" data-comments="{{json_encode($tempContentProduct->comments->toArray()) }}">
								<i class="fas fa-eye"></i> View
							</button>
						@endif
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	@include('plugins/ecommerce::temp-products.content.edit')
	@include('plugins/ecommerce::temp-products.content.view')

	<!-- JS Dependencies -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
	<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
	<!-- Select2 JS -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
	<style>
		.custom-link {
			cursor: pointer;
			display: block; /* Ensures the entire area of the link is clickable */
		}

		.custom-link label {
			cursor: pointer; /* Ensures the label inside the link also shows the pointer */
		}
	</style>

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


		// Initialize modal content dynamically
		$(document).on('click', '[data-target="#viewContentModal"]', function () {
			const productData = $(this).attr('data-product');
			const decodedData = $('<textarea/>').html(productData).text();

			// Parse the JSON string into a JavaScript object
			const product = JSON.parse(decodedData);

			// Populate modal fields
			$('#content_view_product_id').text(product.product_id);
			$('#content_view_id').text(product.id);
			$('#content_view_google_shopping_category').text(product.google_shopping_category);
			$('#content_view_name').text(product.name);
			$('#content_view_slug').text(product.slug);
			$('#content_view_sku').text(product.sku);
			$('#content_view_description').html(product.description);
			$('#content_view_content').html(product.content);
			$('#content_view_warranty_information').html(product.warranty_information);
			$('#content_view_seo_title').html(product.seo_title);
			$('#content_view_seo_description').html(product.seo_description);
			$('#content_view_remarks').html(product.remarks);

			// Function to get category names based on IDs
			const productCategories = @json($productCategories);
			let selectedCategoryIds = JSON.parse(product.category_ids);
			const categoryNames = getCategoryNamesFromIds(selectedCategoryIds, productCategories);
			$('#content_view_categories').text(categoryNames);

			const productTypes = @json($productTypes);
			let selectedProductTypeIds = JSON.parse(product.product_type_ids);
			const productTypeNames = getProductTypeNamesFromIds(selectedProductTypeIds, productTypes);
			$('#content_view_product_types').text(productTypeNames);


			// var comments = $(this).data('comments'); // Get comments string from the button

			// var tbody = $("#comments tbody");
			// // Clear previous rows
			// tbody.empty();

			// // Populate comments table
			// if (comments && comments.length > 0) {
			// 	$("#comments").removeClass("d-none"); // Show comments section
			// 	comments.forEach(function (comment) {
			// 		var row = `
			// 			<tr>
			// 				<td>${comment.comment_type}</td>
			// 				<td>${comment.highlighted_text}</td>
			// 				<td>${comment.comment}</td>
			// 				<td>${new Date(comment.created_at).toLocaleString()}</td>
			// 			</tr>
			// 		`;
			// 		tbody.append(row);
			// 	});
			// } else {
			// 	$("#comments").addClass("d-none"); // Hide comments section if no comments
			// }
		});

		function getProductTypeNamesFromIds(typeIds, allTypes) {
			return typeIds
				.map(id => allTypes[id]) // Map each ID to its name
				.filter(Boolean) // Remove any undefined/null values
				.join(', '); // Join the names with a comma
		}
		function getCategoryNamesFromIds(categoryIds, allCategories) {
			// Helper function to recursively find categories by ID
			function findCategoryNameById(id, categories) {
				for (let category of categories) {
					if (category.id == id) {
						return category.name;
					}
					if (category.childrens && category.childrens.length) {
						const name = findCategoryNameById(id, category.childrens);
						if (name) return name;
					}
				}
				return null; // Return null if not found
			}
			// Map each ID to its corresponding name
			return categoryIds.map(id => findCategoryNameById(id, allCategories)).filter(Boolean).join(', ');
		}
	</script>
</body>
@endsection
