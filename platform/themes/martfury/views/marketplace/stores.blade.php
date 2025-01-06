<div class="ps-page--single ps-page--vendor">
    <section class="ps-store-list">
        <div class="container">
            <div class="ps-section__header">
                <h3>{{ __('Our Stores') }}</h3>
            </div>
            <div class="ps-section__content">
                <div class="ps-section__search row">
                    <div class="col-md-4">
                        <form action="{{ route('public.stores') }}" method="get">
                            <div class="form-group mb-3">
                                <button><i class="icon-magnifier"></i></button>
                                <input class="form-control" name="q" value="{{ BaseHelper::stringify(request()->query('q')) }}" type="text" placeholder="{{ __('Search vendor...') }}">
                            </div>
                        </form>
                    </div>
                </div>
                    @include(Theme::getThemeNamespace('views.marketplace.includes.store-items'))

                    <div class="ps-pagination">
                        {!! $stores->withQueryString()->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
