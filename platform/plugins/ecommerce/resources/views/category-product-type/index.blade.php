@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="container mt-4">
	<!-- Filter Form -->
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h2>Categories</h2>
		<form action="{{ route('categoryFilter.index') }}" method="GET" class="d-flex">
			<input
			type="text"
			name="search"
			class="form-control me-2"
			placeholder="Search by ID or Name"
			value="{{ request('search') }}"
			aria-label="Search"
			/>
			<button type="submit" class="btn btn-primary">Search</button>
		</form>
	</div>

	<!-- Table -->
	<table class="table table-bordered table-hover">
		<thead>
			<tr>
				<th class="fw-bold fs-4">ID</th>
				<th class="fw-bold fs-4">Child Category Name</th>
				{{-- <th class="fw-bold fs-4">Product Types</th> --}}
				<th class="fw-bold fs-4">Specifications</th>
				<th class="fw-bold fs-4">Actions</th>
			</tr>
		</thead>
		<tbody>
			@foreach($categories as $category)
			<tr>
				<td>{{ $category['id'] }}</td>
				<td>{{ $category['name'] }}</td>
				{{-- <td>{{ $category['product_types'] }}</td> --}}
				<td>{{ $category['specifications'] }}</td>
				<td>
					<a href="{{ route('categoryFilter.edit', ['id' => $category['id'], 'search' => request('search'), 'page' => request('page')]) }}"
						class="btn btn-sm btn-warning"
						title="Edit">
						<i class="fas fa-edit"></i>
					</a>
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<!-- Pagination Links -->
	<div class="d-flex justify-content-center">
		{{ $categories->appends(['search' => request('search')])->links('pagination::bootstrap-4') }}
	</div>
</div>
@endsection
