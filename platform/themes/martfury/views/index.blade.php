@php
    Theme::layout('homepage')
@endphp

<div class="container">
    <div style="margin: 40px 0;">
        <h4 style="color: #f00; margin-bottom: 15px;">You need to setup your homepage first!</h4>

        <p><strong>1. Go to Admin -> Plugins then activate all plugins.</strong></p>
        <p><strong>2. Go to Admin -> Pages and create a page:</strong></p>

        <div style="margin: 20px 0;">
            <div>- Content:</div>
            <div style="border: 1px solid rgba(0,0,0,.1); padding: 10px; margin-top: 10px;direction: ltr;">
                <div>[simple-slider key="home-slider"][/simple-slider]</div>
                <div>[site-features icon1="icon-rocket" title1="Free Delivery" subtitle1="For all orders over $99" icon2="icon-sync" title2="90 Days Return" subtitle2="If goods have problems" icon3="icon-credit-card" title3="Secure Payment" subtitle3="100% secure payment" icon4="icon-bubbles" title4="24/7 Support" subtitle4="Dedicated support" icon5="icon-gift" title5="Gift Service" subtitle5="Support gift service"][/site-features]</div>
                <div>[flash-sale title="Deal of the day" flash_sale_id="1"][/flash-sale]</div>
                <div>[featured-product-categories title="Top Categories"][/featured-product-categories]</div>
                <div>[theme-ads key_1="IZ6WU8KUALYD" key_2="ILSFJVYFGCPZ" key_3="ZDOZUZZIU7FT"][/theme-ads]</div>
                <div>[featured-products title="Featured products"][/featured-products]</div>
                <div>[theme-ads key_1="Q9YDUIC9HSWS" key_2="Q9YDUIC9HSWS"][/theme-ads]</div>
                <div>[product-collections title="Exclusive Products"][/product-collections]</div>
                <div>[product-category-products category_id="17"][/product-category-products]</div>
                <div>[download-app title="Download Martfury App Now!" subtitle="Shopping fastly and easily more with our app. Get a link to download the app on your phone." screenshot="general/app.png" android_app_url="https://www.appstore.com" ios_app_url="https://play.google.com/store"][/download-app]</div>
                <div>[product-category-products category_id="15"][/product-category-products]</div>
                <div>[newsletter-form title="Join Our Newsletter Now" subtitle="Subscribe to get information about products and coupons"][/newsletter-form]</div>
            </div>
            <br>
            <div>- Template: <strong>Homepage</strong>.</div>
        </div>

        <p><strong>3. Then go to Admin -> Appearance -> Theme options -> Page to set your homepage.</strong></p>
    </div>
</div>
