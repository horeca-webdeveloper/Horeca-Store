@php
    $groupedCategories = $categories->groupBy('parent_id');

    $currentCategories = $groupedCategories->get(0);
@endphp

@if($currentCategories)
    @foreach ($currentCategories as $category)
        @php
            $hasChildren = $groupedCategories->has($category->id);
        @endphp

        <li @if ($hasChildren) class="menu-item-has-children has-mega-menu" @endif>
            <a href="{{ route('public.single', $category->url)  }}">
                @if ($category->icon_image)
                    <img
                        src="{{ RvMedia::getImageUrl($category->icon_image) }}"
                        alt="{{ $category->name }}"
                        width="18"
                        height="18"
                    >
                @elseif ($category->icon)
                    <i class="{{ $category->icon }}"></i>
                @endif
                <span class="ms-1">{{ $category->name }}</span>
            </a>
            @if ($hasChildren)
                <span class="sub-toggle"></span>
                @php
                    $currentCategories = $groupedCategories->get($category->id);
                @endphp

                <div class="mega-menu" @if(! $groupedCategories->has($currentCategories[0]->id)) style="min-width: 250px;" @endif>
                    @if($currentCategories)
                        @foreach ($currentCategories as $childCategory)
                            @php
                                $hasChildren = $groupedCategories->has($childCategory->id);
                            @endphp
                            <div class="mega-menu__column">
                                @if ($hasChildren)
                                    <a href="{{ $childCategory->url }}"><h4>{{ $childCategory->name }}<span class="sub-toggle"></span></h4></a>
                                    <ul class="mega-menu__list">
                                        @php
                                            $currentCategories = $groupedCategories->get($childCategory->id);
                                        @endphp
                                        @if($currentCategories)
                                            @foreach ($currentCategories as $item)
                                                <li><a href="{{ route('public.single', $item->url) }}">{{ $item->name }}</a></li>
                                            @endforeach
                                        @endif
                                    </ul>
                                @else
                                    <a href="{{ route('public.single', $childCategory->url) }}">{{ $childCategory->name }}</a>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif
        </li>
    @endforeach
@endif
