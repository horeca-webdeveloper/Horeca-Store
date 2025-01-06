<ul {!! $options !!}>
    @foreach ($menu_nodes->loadMissing('metadata') as $key => $row)
        <li @if ($row->has_child || $row->css_class || $row->active) class="@if ($row->has_child) menu-item-has-children @endif @if ($row->css_class) {{ $row->css_class }} @endif @if ($row->active) current-menu-item @endif" @endif>
            <a href="{{ url($row->url) }}" @if ($row->target !== '_self') target="{{ $row->target }}" @endif>
                @if ($iconImage = $row->getMetaData('icon_image', true))
                    <img src="{{ RvMedia::getImageUrl($iconImage) }}" alt="icon image" width="15" height="15" style="vertical-align: top; margin-top: 2px"/>
                @elseif ($row->icon_font) <i class="{{ trim($row->icon_font) }}"></i> @endif {{ $row->title }}
            </a>
            @if ($row->has_child)
                <span class="sub-toggle"></span>
                {!! Menu::generateMenu([
                    'menu'       => $menu,
                    'menu_nodes' => $row->child,
                    'view'       => 'menu',
                    'options' => [
                        'class' => 'sub-menu',
                    ]
                ]) !!}
            @endif
        </li>
    @endforeach
</ul>
