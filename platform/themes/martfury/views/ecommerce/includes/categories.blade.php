@php
    $categoriesRequest ??= [];
    $activeCategoryId ??= 0;
@endphp

<ul class="{{ $wrapperClass ?? 'ps-list--categories' }}"
    @if (isset($category) && in_array($category->id, $categoriesRequest) && ($activeCategoryId == $category->id || $urlCurrent != route('public.single', $category->url))) style="display: block" @endif>
    @php
        if (!isset($groupedCategories)) {
            $groupedCategories = $categories->groupBy('parent_id');
        }

        $currentCategories = $groupedCategories->get($parentId ?? 0);
    @endphp

    @if($currentCategories)
        @foreach ($currentCategories as $category)
            @if (!empty($categoriesRequest) && $loop->first && !$category->parent_id)
                <li>
                    <a href="{{ route('public.products') }}">
                        <i class="icon-chevron-left"></i> <span>{{ __('All categories') }}</span>
                    </a>
                </li>
            @endif

            @php
                $hasChildren = $groupedCategories->has($category->id);
                $isActive = (isset($category) && in_array($category->id, $categoriesRequest)) && ($activeCategoryId == $category->id || $urlCurrent != route('public.single', $category->url));
            @endphp

            <li
                @class([
                    'menu-item-has-children' => $hasChildren,
                    'current-menu-item' => $activeCategoryId == $category->id,
                ])>
                <a href="{{ route('public.single', $category->url) }}">{{ $category->name }}</a>
                @if ($hasChildren)
                    <span class="sub-toggle @if ($isActive) active @endif"><i class="icon-angle"></i></span>

                    @include(Theme::getThemeNamespace() . '::views.ecommerce.includes.categories', [
                        'categories' => $groupedCategories,
                        'parentId' => $category->id,
                        'wrapperClass' => 'sub-menu'
                    ])
                @endif
            </li>
        @endforeach
    @endif
</ul>
