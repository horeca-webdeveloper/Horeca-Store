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
			<a href="{{ route(Route::currentRouteName(), ['type' => 'in-process']) }}" class="text-decoration-none custom-link">
				<label class="form-label bg-info text-white text-center py-3 h6">
					Content In Progress<br/>
					<span class="h2">{{ $tempPricingProducts->where('approval_status', 'in-process')->count() }}</span>
				</label>
			</a>
		</div>
		<div class="col-md-3 mb-3">
			<a href="{{ route(Route::currentRouteName(), ['type' => 'pending']) }}" class="text-decoration-none custom-link">
				<label class="form-label bg-warning text-white text-center py-3 h6">
					Submitted for Approval<br/>
					<span class="h2">{{ $tempPricingProducts->where('approval_status', 'pending')->count() }}</span>
				</label>
			</a>
		</div>
		<div class="col-md-3 mb-3">
			<a href="{{ route(Route::currentRouteName(), ['type' => 'approved']) }}" class="text-decoration-none custom-link">
				<label class="form-label bg-success text-white text-center py-3 h6">
					Ready to Publish<br/>
					<span class="h2">{{ $tempPricingProducts->where('approval_status', 'approved')->count() }}</span>
				</label>
			</a>
		</div>
		<div class="col-md-3 mb-3">
			<a href="{{ route(Route::currentRouteName(), ['type' => 'rejected']) }}" class="text-decoration-none custom-link">
				<label class="form-label bg-danger text-white text-center py-3 h6">
					Rejected for Corrections<br/>
					<span class="h2">{{ $tempPricingProducts->sum('rejection_count') }}</span>
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
					<th>SKU</th>
					<th>Price</th>
					<th>Sale Price</th>
					{{-- <th>Current Status</th> --}}
					<th>Approval Status</th>
					<th>Edit</th>
				</tr>
			</thead>
			<tbody>
				@php
					if(!empty($type)) {
						$tempPricingProduct = $tempPricingProduct->where('approval_status', $type);
					}
				@endphp
				@foreach ($tempPricingProducts as $tempPricingProduct)
				<tr id="product-row-{{ $tempPricingProduct->id }}">
					<td>{{ $tempPricingProduct->product_id }}</td>
					<td class="product-name">{{ $tempPricingProduct->name }}</td>
					<td class="product-description">{{ $tempPricingProduct->sku }}</td>
					<td class="product-description">{{ $tempPricingProduct->price }}</td>
					<td class="product-description">{{ $tempPricingProduct->sale_price }}</td>
					{{-- <td class="product-description">{{ $tempPricingProduct->status }}</td> --}}
					<td class="product-description">{{ $approvalStatuses[$tempPricingProduct->approval_status] ?? '' }}</td>
					<td>
						@if($tempPricingProduct->approval_status == 'in-process' || $tempPricingProduct->approval_status == 'rejected')
							<button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#editPricingModal" data-product="{{ htmlspecialchars(json_encode($tempPricingProduct->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') }}">
								<i class="fas fa-pencil-alt"></i> Edit
							</button>
						@else
							<button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#viewPricingModal" data-product="{{ htmlspecialchars(json_encode($tempPricingProduct->toArray(), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') }}">
								<i class="fas fa-eye"></i> View
							</button>
						@endif
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	@include('plugins/ecommerce::temp-products.pricing.edit')
	@include('plugins/ecommerce::temp-products.pricing.view')

	<!-- jQuery -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<!-- Bootstrap JS -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
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
		// Function to toggle the "To Date" field for each discount group
		function toggleToDateField(checkbox) {
			// Find the discount item container (group) that contains the checkbox
			const discountItem = checkbox.closest('.discount-item');

			// Get the "To Date" input field within this group
			const toDateInput = discountItem.querySelector('.to-date');

			// If "Never Expired" is checked, disable the "To Date" field
			if (checkbox.checked) {
				toDateInput.disabled = true;
			} else {
				toDateInput.disabled = false;
			}
		}

		function calculateDiscount(element) {
			const discountItem = element.closest('.discount-item');
			const productRequiredInput = discountItem.querySelector('.product-quantity');
			const discountPercentageInput = discountItem.querySelector('.discount-percentage');
			const priceAfterDiscountInput = discountItem.querySelector('.price-after-discount');
			const marginInput = discountItem.querySelector('.margin');

			const price = document.querySelector('input[name="sale_price"]').value || document.querySelector('input[name="price"]').value || 0;
			const costPerItem = document.querySelector('input[name="cost_per_item"]').value || 0;
			const productRequired = parseFloat(productRequiredInput.value) || 0;
			const discountPercentage = parseFloat(discountPercentageInput.value) || 0;

			// Ensure all inputs are valid
			if (price > 0 && productRequired > 0 && discountPercentage > 0) {
				// Calculate discount amount
				const discountAmount = price * (discountPercentage / 100);

				// Calculate final price after discount
				const priceAfterDiscount = price - discountAmount;

				// Set the result in the readonly input field
				priceAfterDiscountInput.value = priceAfterDiscount.toFixed(2);

				const marginValue = (priceAfterDiscountInput.value - costPerItem)*100/priceAfterDiscountInput.value;
				marginInput.value = marginValue.toFixed(2);
			} else {
				// Clear the price after discount field if inputs are invalid or missing
				priceAfterDiscountInput.value = '';
			}
		}

		function calculateMargin() {
			const price = document.querySelector('#pricing_sale_price').value || document.querySelector('#pricing_price').value || 0;
			const costPerItem = document.querySelector('#pricing_cost_per_item').value || 0;
			const marginInput = document.querySelector('#pricing_margin');

			if (price > 0 && costPerItem > 0) {
				const margin = ((price - costPerItem) / price) * 100;
				marginInput.value = `${margin.toFixed(2)}`;
			} else {
				marginInput.value = 0;
			}
		}

		const unitOfMeasurementDropdown = document.getElementById('pricing_unit_of_measurement_id');
		const unitLabels = {
			1: 'Pieces',
			2: 'Dozen',
			3: 'Box',
			4: 'Case'
		};

		// Function to update all quantity labels
		function updateAllQuantityLabels() {
			const selectedValue = unitOfMeasurementDropdown.value;
			const unitText = unitLabels[selectedValue] || 'Units';

			// Update all labels in the discount group
			document.querySelectorAll('.quantity-label').forEach((label, index) => {
				label.textContent = `Buying Quantity Tier ${index+1} (in ${unitText})`;
			});
		}

		$(document).on('click', '[data-target="#editPricingModal"]', function () {
			// Get the product data from the button's data-product attribute
			const productData = $(this).attr('data-product');
			const decodedData = $('<textarea/>').html(productData).text();

			// Parse the JSON string into a JavaScript object
			const product = JSON.parse(decodedData);

			// Populate the modal fields
			$('#pricing_temp_header_id').text(product.product_id);
			$('#pricing_temp_header_name').text(product.name);

			$('#pricing_temp_id').val(product.id);
			$('#pricing_sku').val(product.sku);
			$('#pricing_price').val(product.price);
			$('#pricing_sale_price').val(product.sale_price);
			$('#pricing_from_date').val(product.from_date);
			$('#pricing_to_date').val(product.to_date);
			$('#pricing_cost_per_item').val(product.cost_per_item);
			$('#pricing_margin').val(product.margin);
			$('#pricing_quantity').val(product.quantity);

			$('#pricing_store_id').val(product.store_id);
			$('#pricing_minimum_order_quantity').val(product.minimum_order_quantity);
			$('#pricing_box_quantity').val(product.box_quantity);
			$('#pricing_delivery_days').val(product.delivery_days);
			$('#pricing_unit_of_measurement_id').val(product.unit_of_measurement_id);
			$('#pricing_variant_requires_shipping').val(product.variant_requires_shipping);
			$('#pricing_refund').val(product.refund);
			// $('#pricing_initial_approval_status').val(product.approval_status);
			// $('#pricing_approval_status').val(product.approval_status);
			$('#pricing_remarks').val(product.remarks);

			// Set checkbox values
			$('#pricing_with_storehouse_management').prop('checked', product.with_storehouse_management);
			$('#pricing_allow_checkout_when_out_of_stock').prop('checked', product.allow_checkout_when_out_of_stock);
			$(`#pricing_${productData.stock_status}`).prop('checked', true);


			// Clear existing discount items
			const discountGroup = $('#discount-group');
			discountGroup.empty();

			// Populate discount items
			if (product.discount && product.discount.length) {
				product.discount.forEach((discount, index) => {
					const discountItem = `
						<div class="discount-item">
							<div class="row g-3 mb-3">
								<div class="col-md-6">
									<input type="hidden" name="discount[${index}][discount_id]" value="${discount.discount_id || ''}">
									<label for="product_quantity_${index}" class="form-label quantity-label">Buying Quantity</label>
									<input type="number" class="form-control product-quantity"
										   name="discount[${index}][product_quantity]"
										   value="${discount.product_quantity || ''}"
										   onchange="calculateDiscount(this)">
								</div>

								<div class="col-md-6">
									<label for="discount_${index}" class="form-label">Discount (%)</label>
									<input type="number" class="form-control discount-percentage"
										   name="discount[${index}][discount]"
										   value="${discount.discount || ''}"
										   onchange="calculateDiscount(this)">
								</div>

								<div class="col-md-6">
									<label for="price_after_discount_${index}" class="form-label">Price after Discount</label>
									<input type="number" class="form-control price-after-discount"
										   name="discount[${index}][price_after_discount]"
										   value="${discount.price_after_discount || ''}" readonly>
								</div>

								<div class="col-md-6">
									<label for="margin_${index}" class="form-label">Margin (%)</label>
									<input type="number" class="form-control margin"
										   name="discount[${index}][margin]"
										   value="${discount.margin || ''}" readonly>
								</div>
							</div>

							<div class="row g-3 mb-3">
								<div class="col-md-4">
									<label for="fromDate_${index}" class="form-label">From Date</label>
									<input type="datetime-local" class="form-control"
										   name="discount[${index}][discount_from_date]"
										   value="${discount.discount_from_date || ''}">
								</div>

								<div class="col-md-4">
									<label for="toDate_${index}" class="form-label">To Date</label>
									<input type="datetime-local" class="form-control to-date"
											${discount.never_expired==1 ? 'disabled' : ''}
										   name="discount[${index}][discount_to_date]"
										   value="${discount.discount_to_date || ''}">
								</div>

								<div class="col-md-4 d-flex align-items-center">
									<div class="form-check">
										<input class="form-check-input me-2 never-expired-checkbox"
											   type="checkbox"
											   name="discount[${index}][never_expired]"
											   value="1"
											   ${discount.never_expired ? 'checked' : ''}
											   onchange="toggleToDateField(this)">
										<label class="form-check-label" for="never_expired_${index}">Never Expired</label>
									</div>
								</div>
							</div>

							<div class="row g-3 my-3">
								<div class="col-md-12">&nbsp;
								</div>
							</div>
						</div>
					`;
					discountGroup.append(discountItem);
				});

				// Add "Add" button if items are less than 3
				if (product.discount.length < 3) {
					discountGroup.append(`
						<div class="row g-3 mb-3">
							<div class="col-md-12 text-end">
								<button type="button" class="btn btn-success add-btn"><i class="fas fa-plus"></i> Add</button>
							</div>
						</div>
					`);
				}

				// Ensure the new label reflects the current UoM
				updateAllQuantityLabels();
			}

			// Show the discount period fields if the dates are available
			if (product.from_date || product.to_date) {
				$('#discountPeriodFields').removeClass('d-none');
			} else {
				$('#discountPeriodFields').addClass('d-none');
			}

			// Initially hide the storehouse fields if checkbox is unchecked
			if ($('#pricing_with_storehouse_management').is(':checked')) {
				$('#quantity_section').removeClass('d-none');
				$('#stock_status_section').addClass('d-none')
			} else {
				$('#quantity_section').addClass('d-none');
				$('#stock_status_section').removeClass('d-none');
			}

			$('#pricing_with_storehouse_management').val($('#pricing_with_storehouse_management').is(':checked') ? 1 : 0);
			$('#pricing_allow_checkout_when_out_of_stock').val($('#pricing_allow_checkout_when_out_of_stock').is(':checked') ? 1 : 0);

			// Toggle storehouse fields and checkbox value

			$('#pricing_with_storehouse_management').change(function () {
				if ($(this).is(':checked')) {
					$(this).val(1); // Set value to 1 when checked
					$('#quantity_section').removeClass('d-none');
					$('#stock_status_section').addClass('d-none')
				} else {
					$(this).val(0); // Set value to 0 when unchecked
					$('#quantity_section').addClass('d-none');
					$('#stock_status_section').removeClass('d-none');
				}
			});
			$('#pricing_allow_checkout_when_out_of_stock').change(function() {
				$(this).val(this.checked ? 1 : 0);
			});

			$('#chooseDiscountPeriod').click(function() {
				$('#discountPeriodFields').toggleClass('d-none');

				// Toggle text between "Choose Discount Period" and "Cancel"
				const linkText = $(this).text().trim();
				$(this).text(linkText === 'Choose Discount Period' ? 'Cancel' : 'Choose Discount Period');
			});


			// Get references to the select and textarea elements
			// const $approvalStatus = $('#pricing_approval_status');
			// const $remarks = $('#pricing_remarks');

			// // Function to update the "required" attribute based on approval status
			// function updateRemarksRequirement() {
			// 	if ($approvalStatus.val() === 'rejected') { // Replace 'rejected' with the actual value for rejection
			// 		$remarks.attr('required', 'required');
			// 	} else {
			// 		$remarks.removeAttr('required');
			// 	}
			// }

			// Initial check when the page loads
			// updateRemarksRequirement();

			// Update requirement whenever the approval status changes
			// $approvalStatus.on('change', updateRemarksRequirement);
		});

		const discountGroup = document.getElementById('discount-group');
		discountGroup.addEventListener('click', (event) => {
			if (event.target.classList.contains('add-btn')) {
				// Disable the "Add" button temporarily
				event.target.classList.add('disabled');
				event.target.disabled = true;

				/* Find the current count of discount items */
				const count = discountGroup.querySelectorAll('.discount-item').length;

				if (count < 3) {
					/* Create a new input field group with updated name attributes */
					const newField = document.createElement('div');
					newField.classList.add('discount-item');
					newField.innerHTML = `
						<div class="row g-3 mb-3">
							<div class="col-md-6">
								<label for="product_quantity" class="form-label quantity-label">Buying Quantity</label>
								<input type="number" class="form-control product-quantity" name="discount[${count}][product_quantity]" onchange="calculateDiscount(this)">
							</div>
							<div class="col-md-6">
								<label for="discount" class="form-label">Discount (%)</label>
								<input type="number" class="form-control discount-percentage" name="discount[${count}][discount]" onchange="calculateDiscount(this)">
							</div>
							<div class="col-md-6">
								<label for="price_after_discount" class="form-label">Price after Discount</label>
								<input type="number" class="form-control price-after-discount" name="discount[${count}][price_after_discount]" readonly>
							</div>
							<div class="col-md-6">
								<label for="margin" class="form-label">Margin (%)</label>
								<input type="number" class="form-control margin" name="discount[${count}][margin]" readonly>
							</div>
						</div>
						<div class="row g-3 mb-3">
							<div class="col-md-4">
								<label for="fromDate" class="form-label">From Date</label>
								<input type="datetime-local" class="form-control" name="discount[${count}][discount_from_date]">
							</div>
							<div class="col-md-4">
								<label for="toDate" class="form-label">To Date</label>
								<input type="datetime-local" class="form-control to-date" name="discount[${count}][discount_to_date]">
							</div>
							<div class="col-md-4 d-flex align-items-center">
								<div class="form-check">
									<input class="form-check-input me-2 never-expired-checkbox" type="checkbox" name="discount[${count}][never_expired]" value="1" onchange="toggleToDateField(this)">
									<label class="form-check-label" for="never_expired">Never Expired</label>
								</div>
							</div>
						</div>
						<div class="row g-3 mb-3">
							<div class="col-md-12 text-end">
								<button type="button" class="btn btn-danger remove-btn1"><i class="fas fa-minus"></i> Remove</button>
							</div>
						</div>
					`;
					discountGroup.appendChild(newField);

					// Ensure the new label reflects the current UoM
					updateAllQuantityLabels();
				}
			} else if (event.target.classList.contains('remove-btn1')) {
				/* Remove input fields */
				const discountItem = event.target.closest('.discount-item');
				if (discountItem) {
					discountItem.remove();
				}

				// Re-enable the Add button after a remove
				const addButton = discountGroup.querySelector('.add-btn');
				if (addButton) {
					addButton.classList.remove('disabled');
					addButton.disabled = false;
				}
			}
		});

		// Trigger label updates when the UoM dropdown changes
		unitOfMeasurementDropdown.addEventListener('change', updateAllQuantityLabels);
	</script>
</body>

@endsection