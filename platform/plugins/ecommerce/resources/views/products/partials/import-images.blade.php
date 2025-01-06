@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')

<div class="container">
    <h1 class="mb-4">{{ __('Import Product Images') }}</h1>
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
    <div class="card">
        <div class="card-header">
            <h5>{{ __('Upload Images via CSV') }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('product-images.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf  {{-- Include CSRF token for security --}}

                <div class="form-group">
                    <label for="csv_file">{{ __('Select CSV File') }}</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                    <small class="form-text text-muted">{{ __('Upload a CSV file with the format: Product ID, Image URLs (comma separated).') }}</small>
                </div>

                <h3>{{ __('CSV Format Example') }}</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('Product ID') }}</th>
                            <th>{{ __('Images') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>123</td>
                            <td>http://example.com/image1.jpg, http://example.com/image2.jpg</td>
                        </tr>
                        <tr>
                            <td>456</td>
                            <td>http://example.com/image3.jpg</td>
                        </tr>
                        <tr>
                            <td>789</td>
                            <td>http://example.com/image4.jpg, http://example.com/image5.jpg, http://example.com/image6.jpg</td>
                        </tr>
                    </tbody>
                </table>

                {{-- <a href="{{ asset('example_product_images.csv') }}" class="btn btn-secondary" download>{{ __('Download Example CSV') }}</a> --}}

                <button type="submit" class="btn btn-primary">{{ __('Import Images') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
