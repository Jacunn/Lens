@extends('layout.main', [
'elementName' => 'database-select'
])

@section('content')
<div class="mx-5 card">
    <div class="card-header">
        <div class="row">
            <div class="col text-center mx-auto">
                <h2>Database Selection</h2>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ secure_url('query-crafter') }}" method="GET">
            @csrf
            <div class="form-group">
                <label for="database">Database</label>
                <select id="database" class="form-control" name="{{ $param_names['database-name'] }}">
                @foreach($select_database as $database)
                    <option value="{{ $database }}">{{ $database }}</option>
                @endforeach
                </select>
                <small id="database-select-help" class="form-text text-muted">This is the database you wish to pull information from...</small>
            </div>
            <div class="d-grid mt-3">
                <button class="btn btn-success" type="submit">Confirm</button>
            </div>
        </form>
    </div>
</div>
@endsection