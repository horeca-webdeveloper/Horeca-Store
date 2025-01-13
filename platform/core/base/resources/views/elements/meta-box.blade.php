{{--
@if (!empty($data))
    <div
        id="{{ $context }}-sortables"
        class="meta-box-sortables"
    >
        {!! $data !!}
    </div>
@endif --}}


@php
    $user = Auth::user(); // Get the logged-in user
    $userRoles = $user->roles->pluck('name')->all() ?? [];

    // Check if the user's role ID is 19 (Graphic Designer)
    $hasGraphicsRole = in_array('Graphic Designer', $userRoles);

@endphp

@if (!empty($data) && !$hasGraphicsRole)
    <div
        id="{{ $context }}-sortables"
        class="meta-box-sortables"
    >
        {!! $data !!}
    </div>
@endif
