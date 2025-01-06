<script>
    $(function () {
        if (typeof fbq !== 'function') {
            return;
        }
        $(document).on('click', '[data-bb-toggle="add-to-cart-in-form"]', function (e) {
            var currentTarget = $(e.currentTarget);
            var form = currentTarget.closest('form');
            var price = currentTarget.data('product-price');
            var quantity = form.find('input[name="qty"]').val();

            fbq('track', 'AddToCart', {
                content_ids: [currentTarget.data('product-id')],
                content_name: currentTarget.data('product-name'),
                content_type: 'product',
                value: price * quantity,
                currency: '{{ get_application_currency()->title }}',
            });
        });
        $(document).on('click', '[data-bb-toggle="add-to-cart"]', function (e) {
            var currentTarget = $(e.currentTarget);
            var price = currentTarget.data('product-price');

            fbq('track', 'AddToCart', {
                content_ids: [currentTarget.data('product-id')],
                content_name: currentTarget.data('product-name'),
                content_type: 'product',
                value: price,
                currency: '{{ get_application_currency()->title }}',
            });
        });
    });
</script>
