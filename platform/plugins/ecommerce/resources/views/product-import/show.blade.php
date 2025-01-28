@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

@php
$jsonDescription = false;
if ($log->description && json_validate($log->description)) {
	$jsonDescription = json_decode($log->description, true);
}
@endphp

<div class="container mt-4">
	<div class="card">
		<div class="card-header d-flex justify-content-between align-items-center">
			<h3 class="mb-0">{{ __("Import Log") }}</h3>
			<a href="{{ route('tools.data-synchronize.import.products.index') }}" class="btn btn-primary">
				{{ __("Back to Import File") }}
			</a>
		</div>
		<div class="card-body">

		<div class="db-details mb-2">
			<div class="row">
				<div class="col-md-2">
					<div class="detailed-head">
						<p>{{ __("ID") }}</p>
					</div>
				</div>
				<div class="col-md-10">
					<div class="detailed-description">
						<p>{{ $log->id }}</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2">
					<div class="detailed-head">
						<p>{{ __("Module") }}</p>
					</div>
				</div>
				<div class="col-md-10">
					<div class="detailed-description">
						<p>{{ $log->module }}</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2">
					<div class="detailed-head">
						<p>{{ __("Action") }}</p>
					</div>
				</div>
				<div class="col-md-10">
					<div class="detailed-description">
						<p>{{ $log->action }}</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2">
					<div class="detailed-head">
						<p>{{ __("Identifier") }}</p>
					</div>
				</div>
				<div class="col-md-10">
					<div class="detailed-description">
						<p>{{ $log->identifier }}</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2">
					<div class="detailed-head">
						<p>{{ __("Status") }}</p>
					</div>
				</div>
				<div class="col-md-10">
					<div class="detailed-description">
						<p>
							<span class="statusStyle" @if($log->status=="Completed") style="background-color: green" @elseif($log->status=="Failed") style="background-color: red" @elseif($log->status=="In-progress") style="background-color: #d7d73f" @endif> {{ is_array($log->status) ? json_encode($log->status):$log->status }}</span>
						</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2">
					<div class="detailed-head">
						<p>{{ __("Created By") }}</p>
					</div>
				</div>
				<div class="col-md-10">
					<div class="detailed-description">
						<p>{{ $log->createdBy->name ?? '' }}</p>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2">
					<div class="detailed-head">
						<p>{{ __("Created At") }}</p>
					</div>
				</div>
				<div class="col-md-10">
					<div class="detailed-description">
						<p>{{ $log->created_at }}</p>
					</div>
				</div>
			</div>
		</div>

		@if ($jsonDescription)
			<h4 class="section-title">{{ __("Import Description") }}</h4>
			<div class="table-responsive">
				<table class="table">
					<thead>
						<tr>
							<th width="17%">{{ __("Label") }}</th>
							<th width="83%">{{ __("Value") }}</th>
						</tr>
					</thead>
					<tbody>
							@foreach($jsonDescription as $key => $value)
								<tr>
									<td><b>{{ $key }}</b></td>
									<td>
										<div style="max-height: 300px; overflow-y: auto;">
											@if(is_array($value) && count($value) > 0)
												@foreach($value as $key1 => $value1)
													@if(is_array($value1))
														@foreach($value1 as $key2 => $value2)
															@if($key2 == "Error")
																@php($errorArray = explode(' | ', $value2))
																@foreach($errorArray as $error)
																	&nbsp; &nbsp; &nbsp; {{$loop->iteration}}. {{ $error }}<br>
																@endforeach
															@else
																<b>{{ $key2 }}</b>: {!! is_array($value2) ? json_encode($value2) : $value2 !!}<br>
															@endif
														@endforeach
													@else
														<b>{{ $key1 }}</b>: {{ $value1 }}<br>
													@endif
													<br>
												@endforeach
											@else
												{{ is_array($value) ? json_encode($value) : $value }}
											@endif
										</div>
									</td>

								</tr>
							@endforeach
					</tbody>
				</table>
			</div>
		@endif
	</div>
</div>

<style type="text/css">
	.statusStyle {
		border-radius: 5px;
		padding: 5px;
		color: white;
	}
</style>
@endsection