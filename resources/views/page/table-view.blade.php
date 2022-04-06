@extends('layout.main', [
'elementName' => 'table-view'
])
@push('js')
<script>
    $(document).ready(function() {
        
        $('#share-link').on('click', function() {
            var x = $("<input>");
            $("body").append(x);
            x.val( "{!! addcslashes($copy_link, "'") !!}" ).select();
            document.execCommand("copy");
            x.remove();
        });

        $('#export-csv').on('click', function() {
            $.ajax({
                type : 'GET',
                data: @json($get_parameters),
                url : "{{ route('table-csv') }}",
                success : function(data) {  
                    let csvContent = 'data:text/csv;charset=utf-8,' + data; //Generate our content headers...

                    var link = document.createElement("a"); //Generate a faux link to download our content with a name...
                    
                    link.setAttribute("href", encodeURI(csvContent)); //Set the link to point at the contet we generated...

                    link.setAttribute("download", 'query-export.csv'); //Give it a name.
                    
                    document.body.appendChild(link); //Temporarily append for download...
                    
                    link.click(); //Click it...

                    link.parentNode.removeChild(link); //Remove it.
                }
            });
        });

        $('#results').DataTable({
            "processing": true,
            "serverSide": true,
            "pageLength": 50,
            "responsive": true,
            "paging": true,
            "ajax": {
                "type": "GET",
                "url": "{{ route('table-data') }}",
                "dataType": "json",
                "data": function(d) {
                    var get = @json($get_parameters);
                    var order = [];
                    d.order.forEach(x => {
                        order.push({ {{$param_names['dt-order-col']}}: x.column, {{$param_names['dt-order-dir']}}: x.dir });
                    });
                    
                    var search = { {{$param_names['dt-search-value']}}: d.search.value, {{$param_names['dt-search-regex']}}: d.search.regex };
                    
                    get.{{ $param_names['dt-order'] }} = order;
                    get.{{ $param_names['dt-search'] }} = search;
                    get.{{ $param_names['dt-start'] }} = d.start;
                    get.{{ $param_names['dt-length'] }} = d.length;
                    get.{{ $param_names['dt-draw'] }} = d.draw;
                    return get;
                },
                "dataSrc": function(x) {
                    console.log(x);
                    $('#sql-query').html(x['query']);
                    return x.data;
                }
            },
            "columns": [
            @isset($columns)
            @foreach($columns as $column)
                {
                    "data": "{{ $column }}"
                },
            @endforeach
            @endisset
            ],
        });
    });
</script>
@endpush
@section('content')
<div class="mx-5 card">
    <div class="card-header">
        <div class="row">
            <div class="col-sm-4 text-left mx-auto">
                <a href="{{ $back_link }}" class="btn btn-secondary" role="button">< Back</a>
            </div>
            <div class="col-sm-4 text-center mx-auto">
                <h2>Query Results</h2>
            </div>
            <div class="col-sm-4 text-center mx-auto">
                
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <table id="results" class="display" style="width:100%">
                    <thead>
                        <tr>
                            @foreach($columns as $column)
                            <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col text-left mx-auto">
                <button id="export-csv" class="btn btn-secondary">Export CSV</button>
                <button id="share-link" class="btn btn-secondary">Share Link</button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col text-left mx-auto">
                <span><strong>Simplified Query:</strong></span>
                </br>
                <span>{{ $query_sql }}</span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col text-left mx-auto">
                <span><strong>Full Query:</strong></span>
                </br>
                <span id="sql-query">Loading...</span>
            </div>
        </div>
    </div>
</div>
@endsection