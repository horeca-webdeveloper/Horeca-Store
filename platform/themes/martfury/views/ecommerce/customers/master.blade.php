 <section class="ps-section--account crop-avatar customer-page">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="ps-section__left">
                    <aside class="ps-widget--account-dashboard">
                        <div class="ps-widget__content">
                            <ul>
                                @foreach (DashboardMenu::getAll('customer') as $item)
                                    <li class="nav-item text-truncate" id="{{ $item['id'] }}">
                                        <a
                                            class="nav-link
                                            @if ($item['active']) active @endif"
                                                href="{{ $item['url']  }}"
                                                aria-current="@if ($item['active']) true @else false @endif"
                                            title="{{ __($item['name']) }}"
                                            >
                                            @if ($item['icon'])
                                                <x-core::icon :name="$item['icon']" />
                                            @endif
                                            {{ __($item['name']) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="ps-section__right">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</section>
