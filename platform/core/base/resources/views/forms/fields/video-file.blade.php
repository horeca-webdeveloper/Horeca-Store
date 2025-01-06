<div class="form-group">
    <label for="{{ $id }}">{{ $label }}</label>
    <input type="file" name="{{ $name }}" id="{{ $id }}" class="form-control" accept="{{ $accept }}">
    @if ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
</div>
