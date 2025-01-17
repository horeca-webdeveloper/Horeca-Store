<div class="modal fade" id="viewPricingModal" tabindex="-1" role="dialog" aria-labelledby="viewPricingModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">View Product Details</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<h5>Product ID: <span id="pricing_view_product_id"></span></h5>
				</div>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Product Name</dt>
					<dd class="col-md-9" id="pricing_view_name"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Temp Product ID</dt>
					<dd class="col-md-9" id="pricing_view_id"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">SKU</dt>
					<dd class="col-md-9" id="pricing_view_sku"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Price</dt>
					<dd class="col-md-9" id="pricing_view_price"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Price After Discount</dt>
					<dd class="col-md-9" id="pricing_view_sale_price"></dd>
				</dl>

				<div id="date_section" class="d-none">
					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">From Date</dt>
						<dd class="col-md-9" id="pricing_view_from_date"></dd>
					</dl>

					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">To Date</dt>
						<dd class="col-md-9" id="pricing_view_to_date"></dd>
					</dl>
				</div>
				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Unit Of Measurement</dt>
					<dd class="col-md-9" id="pricing_view_unit_of_measurement"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Cost Per Item</dt>
					<dd class="col-md-9" id="pricing_view_cost_per_item"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Margin (%)</dt>
					<dd class="col-md-9" id="pricing_view_margin"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">With Storehouse Management</dt>
					<dd class="col-md-9" id="pricing_view_with_storehouse_management"></dd>
				</dl>

				<div id="storehouse_management_section_1" class="d-none">
					<dl class="row mt-1 pb-1 border-bottom d">
						<dt class="col-md-3">Quantity</dt>
						<dd class="col-md-9" id="pricing_view_quantity"></dd>
					</dl>

					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">Allow Checkout When Out Of Stock</dt>
						<dd class="col-md-9" id="pricing_view_allow_checkout_when_out_of_stock"></dd>
					</dl>
				</div>

				<div id="storehouse_management_section_2" class="d-none">
					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">Stock Status</dt>
						<dd class="col-md-9" id="pricing_view_stock_status"></dd>
					</dl>
				</div>

				<div id="buy_more_save_more_section"></div>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Store ID</dt>
					<dd class="col-md-9" id="pricing_view_store_id"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Minimum Order Quantity</dt>
					<dd class="col-md-9" id="pricing_view_minimum_order_quantity"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Box Quantity</dt>
					<dd class="col-md-9" id="pricing_view_box_quantity"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Delivery Days</dt>
					<dd class="col-md-9" id="pricing_view_delivery_days"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Variant Requires Shipping</dt>
					<dd class="col-md-9" id="pricing_view_variant_requires_shipping"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Refund</dt>
					<dd class="col-md-9" id="pricing_view_refund"></dd>
				</dl>

				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Remarks</dt>
					<dd class="col-md-9" id="pricing_view_remarks"></dd>
				</dl>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
{{-- <script type="text/javascript">
	$(document).on('click', '[data-target="#viewPricingModal"]', function () {
		// Retrieve product data from the clicked element
		const productData = $(this).attr('data-product');
		const decodedData = $('<textarea/>').html(productData).text();

		// Parse the JSON string into a JavaScript object
		const product = JSON.parse(decodedData);
		var unitOfMeasurements = @json($unitOfMeasurements);
		var stores = @json($stores);

		// Populate modal fields
		$('#pricing_view_product_id').text(product.product_id || '');
		$('#pricing_view_name').text(product.name || '');
		$('#pricing_view_id').text(product.id || '');
		$('#pricing_view_sku').text(product.sku || '');
		$('#pricing_view_price').text(product.price || '');
		$('#pricing_view_sale_price').text(product.sale_price || '');

		// Handle date fields
		if (product.start_date && product.end_date) {
			$('#date_section').removeClass('d-none');
			$('#pricing_view_from_date').text(product.start_date);
			$('#pricing_view_to_date').text(product.end_date);
		} else {
			$('#date_section').addClass('d-none');
			$('#pricing_view_from_date').text('');
			$('#pricing_view_to_date').text('');
		}

		// Handle unit of measurement
		const unitOfMeasurement = unitOfMeasurements[product.unit_of_measurement_id] || '';
		if (product.unit_of_measurement_id) {
			$('#pricing_view_unit_of_measurement').text(unitOfMeasurement);
		} else {
			$('#pricing_view_unit_of_measurement').text('');
		}

		// Populate additional fields
		$('#pricing_view_cost_per_item').text(product.cost_per_item || '');
		$('#pricing_view_margin').text(
			calculateMyMargin(product.price, product.sale_price, product.cost_per_item)
		);
		$('#pricing_view_with_storehouse_management').text(
			product.storehouse_management ? 'Yes' : 'No'
		);

		if(product.storehouse_management) {//value can be 1 or 0
			$('#storehouse_management_section_1').removeClass('d-none');
			$('#storehouse_management_section_2').addClass('d-none');
			$('#pricing_view_quantity').text(product.quantity);
			$('#pricing_view_allow_checkout_when_out_of_stock').text(product.allow_checkout_when_out_of_stock ? 'Yes' : 'No');
		} else {
			$('#storehouse_management_section_1').addClass('d-none');
			$('#storehouse_management_section_2').removeClass('d-none');
			$('#pricing_view_stock_status').text(product.stock_status);
		}

		console.log(product.discount);
		// buy_more_save_more_section
		//discount

		// Handle vendor
		if (product.store_id) {
			const store = stores[product.store_id] || '';
			$('#pricing_view_store_id').text(store);
		} else {
			$('#pricing_view_store_id').text('');
		}
		$('#pricing_view_minimum_order_quantity').text(product.variant_requires_shipping || '');
		$('#pricing_view_box_quantity').text(product.minimum_order_quantity || '');
		$('#pricing_view_delivery_days').text(product.refund || '');
		$('#pricing_view_variant_requires_shipping').text(product.delivery_days || '');
		$('#pricing_view_refund').text(product.box_quantity || '');
		$('#pricing_view_remarks').html(product.remarks);



		if (product.discount && product.discount.length) {
			let discountSection = `
				<dl class="row mt-1 pb-1 border-bottom">
					<dt class="col-md-3">Buy more Save more</dt>
					<dd class="col-md-9" id="">
			`;

			const unitText = unitOfMeasurement || 'Units';

			// Update all labels in the discount group
			document.querySelectorAll('.quantity-label').forEach((label, index) => {
				label.textContent = `Buying Quantity Tier ${index+1} (in ${unitText})`;
			});
			product.discount.forEach((discount, index) => {
				discountSection += `
					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">Buying Quantity Tier ${index+1} (in ${unitText})</dt>
						<dd class="col-md-9">${discount.product_quantity || ''}</dd>
					</dl>
					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">Discount (%)</dt>
						<dd class="col-md-9">${discount.discount || ''}</dd>
					</dl>
					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">Price after Discount</dt>
						<dd class="col-md-9">${discount.price_after_discount || ''}</dd>
					</dl>
					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">Margin (%)</dt>
						<dd class="col-md-9">${discount.margin || ''}</dd>
					</dl>
					<dl class="row mt-1 pb-1 border-bottom">
						<dt class="col-md-3">From Date</dt>
						<dd class="col-md-9">${discount.discount_from_date || ''}</dd>
					</dl>
					<dl class="row my-1 pb-1 border-bottom">
						<dt class="col-md-3">To Date</dt>
						<dd class="col-md-9">${discount.never_expired} ? 'Never Expired' : ${discount.discount_to_date}</dd>
					</dl>
				`;

			discountSection += `
					</dd>
				</dl>
			`;
			$('#buy_more_save_more_section').append(discountItem);
			});
		}
	});

	/**
	 * Calculate the margin based on price, sale price, and cost per item.
	 * @param {number} price - The product price.
	 * @param {number} salePrice - The product sale price.
	 * @param {number} costPerItem - The cost per item.
	 * @returns {string} The calculated margin as a percentage or 0.
	 */
	function calculateMyMargin(price, salePrice, costPerItem) {
		const finalPrice = salePrice || price || 0;
		const cost = costPerItem || 0;

		if (finalPrice > 0 && cost > 0) {
			const margin = ((finalPrice - cost) / finalPrice) * 100;
			return `${margin.toFixed(2)}%`;
		}
		return '0%';
	}
</script> --}}

<script type="text/javascript">
	$(document).on('click', '[data-target="#viewPricingModal"]', function () {
		// Retrieve product data from the clicked element
		const productData = $(this).attr('data-product');
		const decodedData = $('<textarea/>').html(productData).text();

		// Parse the JSON string into a JavaScript object
		const product = JSON.parse(decodedData);
		const unitOfMeasurements = @json($unitOfMeasurements);
		const stores = @json($stores);

		// Populate modal fields
		$('#pricing_view_product_id').text(product.product_id || '');
		$('#pricing_view_name').text(product.name || '');
		$('#pricing_view_id').text(product.id || '');
		$('#pricing_view_sku').text(product.sku || '');
		$('#pricing_view_price').text(product.price || '');
		$('#pricing_view_sale_price').text(product.sale_price || '');

		// Handle date fields
		if (product.start_date && product.end_date) {
			$('#date_section').removeClass('d-none');
			$('#pricing_view_from_date').text(product.start_date);
			$('#pricing_view_to_date').text(product.end_date);
		} else {
			$('#date_section').addClass('d-none');
		}

		// Handle unit of measurement
		const unitOfMeasurement = unitOfMeasurements[product.unit_of_measurement_id] || '';
		$('#pricing_view_unit_of_measurement').text(unitOfMeasurement);

		// Populate additional fields
		$('#pricing_view_cost_per_item').text(product.cost_per_item || '');
		$('#pricing_view_margin').text(
			calculateMyMargin(product.price, product.sale_price, product.cost_per_item)
		);
		$('#pricing_view_with_storehouse_management').text(
			product.storehouse_management ? 'Yes' : 'No'
		);

		// Handle storehouse management
		if (product.storehouse_management) {
			$('#storehouse_management_section_1').removeClass('d-none');
			$('#storehouse_management_section_2').addClass('d-none');
			$('#pricing_view_quantity').text(product.quantity || '');
			$('#pricing_view_allow_checkout_when_out_of_stock').text(
				product.allow_checkout_when_out_of_stock ? 'Yes' : 'No'
			);
		} else {
			$('#storehouse_management_section_1').addClass('d-none');
			$('#storehouse_management_section_2').removeClass('d-none');
			$('#pricing_view_stock_status').text(product.stock_status || '');
		}

		// Handle vendor
		const store = stores[product.store_id] || '';
		$('#pricing_view_store_id').text(store || '');
		$('#pricing_view_minimum_order_quantity').text(product.minimum_order_quantity || '');
		$('#pricing_view_box_quantity').text(product.box_quantity || '');
		$('#pricing_view_delivery_days').text(product.delivery_days || '');
		$('#pricing_view_variant_requires_shipping').text(product.variant_requires_shipping ? 'Yes' : 'No');
		$('#pricing_view_refund').text(product.refund || '');
		$('#pricing_view_remarks').html(product.remarks || '');

		// Handle discounts (Buy More Save More)
		if (product.discount && product.discount.length > 0) {
			let discountSection = '';
			const unitText = unitOfMeasurement || 'Units';

			product.discount.forEach((discount, index) => {
				discountSection += `
					<div class="discount-tier border p-3 mb-3">
						<dl class="row mt-1 pb-1 border-bottom">
							<dt class="col-md-3">Buying Quantity Tier ${index + 1} (in ${unitText})</dt>
							<dd class="col-md-9">${discount.product_quantity || ''}</dd>
						</dl>
						<dl class="row mt-1 pb-1 border-bottom">
							<dt class="col-md-3">Discount (%)</dt>
							<dd class="col-md-9">${discount.discount || ''}</dd>
						</dl>
						<dl class="row mt-1 pb-1 border-bottom">
							<dt class="col-md-3">Price after Discount</dt>
							<dd class="col-md-9">${discount.price_after_discount || ''}</dd>
						</dl>
						<dl class="row mt-1 pb-1 border-bottom">
							<dt class="col-md-3">Margin (%)</dt>
							<dd class="col-md-9">${discount.margin || ''}</dd>
						</dl>
						<dl class="row mt-1 pb-1 border-bottom">
							<dt class="col-md-3">From Date</dt>
							<dd class="col-md-9">${discount.discount_from_date || ''}</dd>
						</dl>
						<dl class="row mt-1 pb-1">
							<dt class="col-md-3">To Date</dt>
							<dd class="col-md-9">
								${discount.never_expired === '1' ? 'Never Expired' : (discount.discount_to_date || '')}
							</dd>
						</dl>
					</div>
				`;
			});

			// Add "Buy More Save More" section with special styling
			$('#buy_more_save_more_section').empty().append(`
				<h5>Buy More, Save More</h5>
				<div style="margin-left: 20px;">
					${discountSection}
				</div>
			`);
		} else {
			$('#buy_more_save_more_section').empty();
		}
	});

	/**
	 * Calculate the margin based on price, sale price, and cost per item.
	 * @param {number} price - The product price.
	 * @param {number} salePrice - The product sale price.
	 * @param {number} costPerItem - The cost per item.
	 * @returns {string} The calculated margin as a percentage or '0%'.
	 */
	function calculateMyMargin(price, salePrice, costPerItem) {
		const finalPrice = salePrice || price || 0;
		const cost = costPerItem || 0;

		if (finalPrice > 0 && cost > 0) {
			const margin = ((finalPrice - cost) / finalPrice) * 100;
			return `${margin.toFixed(2)}%`;
		}
		return '0%';
	}
</script>

<style type="text/css">
	.discount-tier {
	    border: 1px solid #dee2e6;
	    padding: 20px;
	    margin-bottom: 20px;
	    background-color: #f8f9fa;
	    border-radius: 5px;
	}

	.discount-tier dl {
	    margin-bottom: 10px;
	}

	.discount-tier .row {
	    margin-bottom: 0;
	}
</style>


