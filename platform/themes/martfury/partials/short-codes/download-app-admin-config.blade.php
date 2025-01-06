<div class="mb-3">
    <label class="form-label">{{ __('Title') }}</label>
    <input type="text" name="title" value="{{ Arr::get($attributes, 'title') }}" class="form-control" placeholder="{{ __('Title') }}">
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Subtitle') }}</label>
    <textarea name="subtitle" class="form-control" placeholder="{{ __('Subtitle') }}" rows="3">{{ Arr::get($attributes, 'subtitle') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Screenshot') }}</label>
    {!! Form::mediaImage('screenshot', Arr::get($attributes, 'screenshot')) !!}
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Android app URL') }}</label>
    <input type="text" name="android_app_url" value="{{ Arr::get($attributes, 'android_app_url') }}" class="form-control" placeholder="{{ __('Android app URL') }}">
</div>

<div class="mb-3">
    <label class="form-label">{{ __('iOS app URL') }}</label>
    <input type="text" name="ios_app_url" value="{{ Arr::get($attributes, 'ios_app_url') }}" class="form-control" placeholder="{{ __('iOS app URL') }}">
</div>
