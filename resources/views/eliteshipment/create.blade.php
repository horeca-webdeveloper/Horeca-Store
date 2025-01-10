


@extends('core/base::layouts.master')

@section('content')
<div class="container">
    <h1>Create Shipment</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {!! form_start($form) !!}

    <form action="{{ route('eliteshipment.store') }}" method="POST">
        @csrf

        <!-- Fields from the form setup -->
        @foreach($form->getFields() as $field)
            <div class="form-group">
                <!-- Automatically render label and input -->
                {!! $field->render() !!}
            </div>
        @endforeach

        <!-- Submit button -->
        <button type="submit" class="btn btn-primary">Create Shipment</button>
    </form>
</div>
@endsection
