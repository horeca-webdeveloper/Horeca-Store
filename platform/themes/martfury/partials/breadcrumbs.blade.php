<ul class="breadcrumb">
    @foreach ($crumbs = Theme::breadcrumb()->getCrumbs() as $i => $crumb)
        @if (! $loop->last)
            <li>
                <a href="{{ $crumb['url'] }}" itemprop="item">{!! BaseHelper::clean($crumb['label']) !!}</a>
                <span class="extra-breadcrumb-name"></span>
            </li>
        @else
            <li aria-current="page">
                <span>{!! BaseHelper::clean($crumb['label']) !!}</span>
            </li>
        @endif
    @endforeach
</ul>
