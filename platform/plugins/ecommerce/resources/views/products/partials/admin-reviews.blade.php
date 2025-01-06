{{-- resources/views/plugins/ecommerce/products/partials/admin-reviews.blade.php --}}
<div class="card mb-3">
    <div class="card-header">
        <h4 class="card-title">Testimonials</h4>
    </div>
    <div class="card-body">
        <div class="product-option-form-wrap">
            <div class="product-option-form-group">
                <div class="product-option-form-body">
                    <input name="has_testimonials" type="hidden" value="1">
                    <div class="accordion" id="accordion-testimonial">
                        @foreach($reviews as $index => $review)
                        <div class="accordion-item mb-3" data-index="{{ $index }}" data-testimonial-index="{{ $index }}">
                            <input type="hidden" name="testimonials[{{ $index }}][id]" value="{{ $review->id }}">
                            <input type="hidden" class="testimonial-order" name="testimonials[{{ $index }}][order]" value="{{ $index }}">
                            <h2 class="accordion-header" id="testimonial-{{ $index }}">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-testimonial-{{ $index }}" aria-expanded="true" aria-controls="testimonial-{{ $index }}">
                                    #{{ $index + 1 }}
                                </button>
                            </h2>
                            <div id="collapse-testimonial-{{ $index }}" class="accordion-collapse collapse show" aria-labelledby="testimonial-{{ $index }}" data-bs-parent="#accordion-testimonial">
                                <div class="accordion-body">
                                    <div class="row align-items-end">
                                        <div class="col">
                                            <label class="form-label">Client Name</label>
                                            <input type="text" name="testimonials[{{ $index }}][customer_name]" class="form-control" placeholder="Client Name" value="{{ $review->customer_name }}">
                                        </div>
                                        <div class="col">
                                            <label class="form-label">Rating</label>
                                            <input type="number" name="testimonials[{{ $index }}][star]" class="form-control" placeholder="Rating" min="1" max="5" value="{{ $review->star }}">
                                        </div>
                                        <div class="col-12 mt-2">
                                            <label class="form-label">Description</label>
                                            <textarea name="testimonials[{{ $index }}][comment]" class="form-control" placeholder="Description" rows="4">{{ $review->comment }}</textarea>
                                        </div>
                                        <div class="mb-3 mt-2">
                                            <label for="images[]" class="form-label">Images</label>
                                            <div class="gallery-images-wrapper">
                                                <div data-bb-toggle="gallery-add" class="text-center cursor-pointer default-placeholder-gallery-image" data-name="testimonials[{{ $index }}][images][]">
                                                    <p class="mb-0 text-body">Click here to add more images.</p>
                                                </div>
                                                <input name="testimonials[{{ $index }}][images][]" type="hidden">
                                                <div class="row w-100 list-gallery-media-images hidden" data-name="testimonials[{{ $index }}][images][]"></div>
                                            </div>
                                            <div style="display: none;" class="footer-action">
                                                <a data-bb-toggle="gallery-add" href="#" class="me-2 cursor-pointer">Add Images</a>
                                                <button class="text-danger cursor-pointer btn-link" data-bb-toggle="gallery-reset">Reset</button>
                                            </div>
                                        </div>
                                        <div class="col text-end mt-5">
                                            <button class="btn btn-icon btn-danger remove-testimonial" type="button" data-index="{{ $index }}">
                                                <svg class="icon icon-left" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M4 7l16 0"></path>
                                                    <path d="M10 11l0 6"></path>
                                                    <path d="M14 11l0 6"></path>
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    {{-- <div class="row">
                        <div class="col">
                            <button class="btn add-new-testimonial" type="button" id="add-new-testimonial">Add new testimonial</button>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
</div>
