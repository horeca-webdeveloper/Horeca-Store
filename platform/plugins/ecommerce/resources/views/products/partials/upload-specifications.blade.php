

@extends($layout ?? BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<h1>Upload Product Specifications</h1>

<p>Please upload a CSV file with the following format:</p>
<pre>
product_id,spec_name,spec_value
1,Weight,200g
1,Color,Red
2,Weight,150g
2,Color,Blue
</pre>

<p>You can download a sample CSV file <a href="{{ asset('path/to/sample-specifications.csv') }}" download>here</a>.</p>

<form action="{{ route('specifications.upload') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div>
        <label for="csv_file">Upload CSV file:</label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
    </div>
    <button type="submit">Upload Specifications</button>
</form>

@if(session('success'))
    <div>{{ session('success') }}</div>
@endif

@if($errors->any())
    <div>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@endsection

