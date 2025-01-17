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