<div class="modal fade" id="editPricingModal" tabindex="-1" role="dialog" aria-labelledby="editPricingModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="editPricingModalLabel">Edit Product</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form action="{{ route('temp-products.pricing_update') }}" method="POST">
					@csrf
					<div class="product-card">
						<div class="product-header">
							<h6>Product ID: <span id="pricing_temp_header_id"></span></h6>
							<h4 id="pricing_temp_header_name"></h4>
							<input type="hidden" id="pricing_temp_id" name="id">
						</div>
						<div class="row">
							<div class="mb-3 col-md-6">
								<label for="sku" class="form-label">SKU</label>
								<input type="text" class="form-control" id="pricing_sku" name="sku">
							</div>

							<div class="mb-3 col-md-6">
								<label for="price" class="form-label">Price</label>
								<input type="number" step="0.01" class="form-control" id="pricing_price" name="price" onchange="calculateMargin()">
							</div>

							<div class="mb-3 col-md-6">
								<div class="d-flex justify-content-between">
									<label for="priceSale" class="form-label">Price After Discount</label>
									<a href="javascript:void(0)" id="chooseDiscountPeriod">Choose Discount Period</a>
								</div>

								<input type="number" step="0.01" class="form-control me-2" id="pricing_sale_price" name="sale_price" onchange="calculateMargin()">
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label">Unit of Measurement</label>
								<select id="pricing_unit_of_measurement_id" name="unit_of_measurement_id" class="form-control">
									<option value="">Select a unit</option>
										@foreach($unitOfMeasurements as $id => $name)
											<option value="{{ $id }}">{{ $name }}</option>
										@endforeach
								</select>
							</div>
						</div>
						<div id="discountPeriodFields" class="d-none">
							<div class="row mb-3">
								<div class="col">
									<label for="fromDate" class="form-label">From Date</label>
									<input type="date" class="form-control" id="pricing_from_date" name="from_date">
								</div>
								<div class="col">
									<label for="toDate" class="form-label">To Date</label>
									<input type="date" class="form-control" id="pricing_to_date" name="to_date">
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="costPerItem" class="form-label">Cost per Item</label>
								<input type="number" step="0.01" class="form-control" id="pricing_cost_per_item" name="cost_per_item" placeholder="Enter cost per item" onchange="calculateMargin()">
							</div>
							<div class="col-md-6 mb-3">
								<label for="costPerItem" class="form-label">Margin (%)</label>
								<input type="text" class="form-control" id="pricing_margin" name="margin" readonly>
							</div>
						</div>

						<div class="mb-3 ms-3">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="pricing_with_storehouse_management" name="with_storehouse_management"/>
								<label class="form-check-label" for="pricing_with_storehouse_management">With storehouse management</label>
							</div>
						</div>

						<div id="quantity_section" class="mb-3 ms-3">
							<div class="mb-3">
								<label for="quantity" class="form-label">Quantity</label>
								<input type="number" class="form-control" id="pricing_quantity" name="quantity">
							</div>

							<div class="form-check ms-3">
								<input class="form-check-input" type="checkbox" id="pricing_allow_checkout_when_out_of_stock" name="allow_checkout_when_out_of_stock">
								<label class="form-check-label" for="allowCheckout">
									Allow customer checkout when this product is out of stock
								</label>
							</div>
						</div>

						<div id="stock_status_section" class="mb-3 ms-3">
							<label class="form-label">Stock Status</label>
							<div class="d-flex flex-row">
								<div class="form-check me-3 ms-3">
									<input type="radio" id="pricing_in_stock" class="form-check-input" name="stock_status" value="in_stock" checked>
									<label for="pricing_in_stock" class="form-check-label">In stock</label>
								</div>
								<div class="form-check me-3 ms-3">
									<input type="radio" id="pricing_out_of_stock" class="form-check-input" name="stock_status" value="out_of_stock">
									<label for="pricing_out_of_stock" class="form-check-label">Out of stock</label>
								</div>
								<div class="form-check ms-3">
									<input type="radio" id="pricing_pre_order" class="form-check-input" name="stock_status" value="pre_order">
									<label for="pricing_pre_order" class="form-check-label">Pre-order</label>
								</div>
							</div>
						</div>

						<legend>
							<h5>Buy more Save more</h5>
						</legend>

						<div id="discount-group">
						</div>

						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="storeSelect" class="form-label">Vendor</label>
								<select class="form-select" id="pricing_store_id" name="store_id">
									<option value="">Select a store</option>
										@foreach ($stores as $id => $name)
											<option value="{{ $id }}">{{ $name }}</option>
										@endforeach
								</select>
							</div>

							<div class="col-md-6 mb-3">
								<label for="variantRequiresShipping" class="form-label">Variant Requires Shipping</label>
								<select class="form-select" id="pricing_variant_requires_shipping" name="variant_requires_shipping">
									<option value="1">Yes</option>
									<option value="0">No</option>
								</select>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="price" class="form-label">Minimum Order Quantity</label>
								<input type="number" class="form-control" id="pricing_minimum_order_quantity" name="minimum_order_quantity">
							</div>

							<div class="col-md-6 mb-3">
								<label for="refundPolicy" class="form-label">Refund Policy</label>
								<select id="pricing_refund" name="refund" class="form-control">
									<option value="non-refundable">Non-refundable</option>
									<option value="15 days">15 Days Refund</option>
									<option value="90 days">90 Days Refund</option>
								</select>
							</div>
						</div>

						<div class="row">
							<!-- Delivery Days -->
							<div class="col-md-6 mb-3">
								<label for="deliveryDays" class="form-label">Delivery Days</label>
								<input type="number" class="form-control" id="pricing_delivery_days" name="delivery_days" placeholder="Enter delivery days" min="1" step="1">
							</div>

							<!-- Box Quantity -->
							<div class="col-md-6 mb-3">
								<label for="boxQuantity" class="form-label">Box Quantity</label>
								<input type="number" class="form-control" id="pricing_box_quantity" name="box_quantity" placeholder="Enter box quantity" min="1" step="1">
							</div>
						</div>


						<div class="row g-3 mb-3 ms-1">
							<div class="col-md-4 d-flex align-items-center">
								<div class="form-check">
									<input class="form-check-input me-2" type="checkbox" id="pricing_in_process" name="in_process" value="1">
									<label class="form-check-label" for="in_process">Is Draft</label>
								</div>
							</div>
						</div>

						{{-- <div class="mb-3">
							<input type="hidden" id="pricing_initial_approval_status" name="initial_approval_status">
							<label for="pricing_approval_status" class="form-label">Approval Status</label>
							<select class="form-select" id="pricing_approval_status" name="approval_status">
								@foreach ($approvalStatuses as $value => $label)
								<option value="{{ $value }}">{{ $label }}</option>
								@endforeach
							</select>
						</div> --}}

						<div class="mb-3">
							<label for="pricing_remarks" class="form-label">Remarks</label>
							<textarea class="form-select" id="pricing_remarks" name="remarks" readonly></textarea>
						</div>

						<button type="submit" class="btn btn-primary">Submit</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
