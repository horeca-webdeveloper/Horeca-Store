@if ($socialLinks = Theme::getSocialLinks())
    <ul class="ps-list--social">
        @foreach($socialLinks as $socialLink)
            @continue(! $socialLink->getUrl() || ! $socialLink->getIconHtml())

            <li>
                <a {!! $socialLink->getAttributes() !!}>{{ $socialLink->getIconHtml() }}</a>
            </li>
        @endforeach
    </ul>
@endif
