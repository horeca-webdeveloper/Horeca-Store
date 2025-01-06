<div id="specs-wrapper">
	@foreach($categorySpecs as $spec_name => $spec_values)
		@php
			$specVals = $spec_values ? explode("|", $spec_values) : [];
			$selectedValue = collect($selectedSpecs)->firstWhere('spec_name', $spec_name)['spec_value'] ?? '';
		@endphp
		<div class="spec-item d-flex m-2">
			<input type="text" class="form-control me-2" name="specs[{{ $loop->index }}][name]" value="{{ $spec_name }}" readonly />
			<select class="form-control select2" name="specs[{{ $loop->index }}][value]">
				<option value="">--Select--</option>
				@foreach ($specVals as $val)
					<option value="{{ $val }}" {{ $val === $selectedValue ? 'selected' : '' }}>{{ $val }}</option>
				@endforeach
			</select>
		</div>
	@endforeach
</div>

<script>
	document.addEventListener("DOMContentLoaded", function () {
		// Initialize Select2
		$('.select2').select2({
			placeholder: "--Select--",
			allowClear: true
		});
	});
</script>
