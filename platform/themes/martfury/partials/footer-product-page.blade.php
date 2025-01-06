@if (EcommerceHelper::isCartEnabled())
    <nav class="navigation--mobile-product">
        <button class="ps-btn ps-btn--black add-to-cart-button @if ($product->isOutOfStock()) btn-disabled @endif" type="button" name="add_to_cart" value="1">{{ __('Add to cart') }}</button>
        @if (EcommerceHelper::isQuickBuyButtonEnabled())
            <button class="ps-btn add-to-cart-button @if ($product->isOutOfStock()) btn-disabled @endif" type="button" name="checkout" value="1">{{ __('Buy Now') }}</button>
        @endif
    </nav>
@endif
