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

    // Check if the user has the role with ID 19 (Graphics Role)
    $hasGraphicsRole = DB::table('role_users')
        ->where('user_id', $user->id)
        ->where('role_id', 19)
        ->exists();

@endphp

@if (!empty($data) && !$hasGraphicsRole) 
    <div
        id="{{ $context }}-sortables"
        class="meta-box-sortables"
    >
        {!! $data !!}
    </div>
@endif
