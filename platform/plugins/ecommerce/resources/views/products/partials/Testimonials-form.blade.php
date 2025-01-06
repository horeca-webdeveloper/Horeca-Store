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
                        <div class="accordion-item mb-3" data-index="0" data-testimonial-index="0">
                            <input type="hidden" name="testimonials[0][id]" value="0">
                            <input type="hidden" class="testimonial-order" name="testimonials[0][order]" value="0">
                            <h2 class="accordion-header" id="testimonial-0">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-testimonial-0" aria-expanded="true" aria-controls="testimonial-0">
                                    #1
                                </button>
                            </h2>
                            <div id="collapse-testimonial-0" class="accordion-collapse collapse show" aria-labelledby="testimonial-0" data-bs-parent="#accordion-testimonial">
                                <div class="accordion-body">
                                    <div class="row align-items-end">
                                        <div class="col">
                                            <label class="form-label">Client Name</label>
                                            <input type="text" name="testimonials[0][customer_name]" class="form-control" placeholder="Client Name">
                                        </div>
                                        <div class="col">
                                            <label class="form-label">Rating</label>
                                            <input type="number" name="testimonials[0][star]" class="form-control" placeholder="Rating" min="1" max="5">
                                        </div>
                                        <div class="col-12 mt-2">
                                            <label class="form-label">Description</label>
                                            <textarea name="testimonials[0][comment]" class="form-control" placeholder="Description" rows="4"></textarea>
                                        </div>
                                        <div class="mb-3 mt-2">
                                            <label for="images[]" class="form-label">Images</label>
                                            <div class="gallery-images-wrapper">
                                                <div data-bb-toggle="gallery-add" class="text-center cursor-pointer default-placeholder-gallery-image" data-name="testimonials[0][images][]">
                                                    <p class="mb-0 text-body">Click here to add more images.</p>
                                                </div>
                                                <input name="testimonials[0][images][]" type="hidden">
                                                <div class="row w-100 list-gallery-media-images hidden" data-name="testimonials[0][images][]"></div>
                                            </div>
                                            <div style="display: none;" class="footer-action">
                                                <a data-bb-toggle="gallery-add" href="#" class="me-2 cursor-pointer">Add Images</a>
                                                <button class="text-danger cursor-pointer btn-link" data-bb-toggle="gallery-reset">Reset</button>
                                            </div>
                                        </div>
                                        <div class="col text-end mt-5">
                                            <button class="btn btn-icon btn-danger remove-testimonial" type="button" data-index="0">
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
                    </div>
                    <div class="row">
                        <div class="col">
                            <button class="btn add-new-testimonial" type="button" id="add-new-testimonial">Add new testimonial</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
 document.addEventListener('DOMContentLoaded', function () {
    let testimonialIndex = 0;

    document.getElementById('add-new-testimonial').addEventListener('click', function () {
        testimonialIndex++;
        const newTestimonial = document.querySelector('.accordion-item').cloneNode(true);
        newTestimonial.dataset.index = testimonialIndex;
        newTestimonial.querySelector('input[name="testimonials[0][id]"]').name = `testimonials[${testimonialIndex}][id]`;
        newTestimonial.querySelector('input[name="testimonials[0][order]"]').name = `testimonials[${testimonialIndex}][order]`;
        newTestimonial.querySelector('input[name="testimonials[0][customer_name]"]').name = `testimonials[${testimonialIndex}][customer_name]`;
        newTestimonial.querySelector('input[name="testimonials[0][star]"]').name = `testimonials[${testimonialIndex}][star]`;
        newTestimonial.querySelector('textarea[name="testimonials[0][comment]"]').name = `testimonials[${testimonialIndex}][comment]`;
        newTestimonial.querySelector('input[name="testimonials[0][images][]"]').name = `testimonials[${testimonialIndex}][images][]`;
        newTestimonial.querySelector('.accordion-header button').setAttribute('data-bs-target', `#collapse-testimonial-${testimonialIndex}`);
        newTestimonial.querySelector('#collapse-testimonial-0').id = `collapse-testimonial-${testimonialIndex}`;
        newTestimonial.querySelector('#testimonial-0').id = `testimonial-${testimonialIndex}`;
        newTestimonial.querySelector('.remove-testimonial').setAttribute('data-index', testimonialIndex);
        document.getElementById('accordion-testimonial').appendChild(newTestimonial);
    });

    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-testimonial')) {
            event.target.closest('.accordion-item').remove();
        }
    });
});

</script>