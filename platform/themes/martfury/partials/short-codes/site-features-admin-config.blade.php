@for ($i = 1; $i < 6; $i++)
    <div class="mb-3">
        <label class="form-label">{{ __('Icon :number', ['number' => $i]) }}</label>
        {!! Form::themeIcon('icon' . $i, Arr::get($attributes, 'icon' . $i)) !!}
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Title :number', ['number' => $i]) }}</label>
        <input type="text" name="title{{ $i }}" value="{{ Arr::get($attributes, 'title' . $i) }}" class="form-control" placeholder="{{ __('Title :number', ['number' => $i]) }}">
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Subtitle :number', ['number' => $i]) }}</label>
        <input type="text" name="subtitle{{ $i }}" value="{{ Arr::get($attributes, 'subtitle' . $i) }}" class="form-control"
               placeholder="{{ __('Subtitle :number', ['number' => $i]) }}">
    </div>
@endfor
