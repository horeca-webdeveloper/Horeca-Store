<div class="mb-3">
    <label class="form-label">Time</label>
    <input type="datetime-local" name="time" value="{{ Arr::get($attributes, 'time') }}" class="form-control" placeholder="Time">
</div>

<div class="mb-3">
    <label class="form-label">Image</label>
    {!! Form::mediaImage('image', Arr::get($attributes, 'image')) !!}
</div>
