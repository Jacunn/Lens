@extends('layout.main', [
'elementName' => 'query-crafter'
])

@push('js')
<script>
    // Long life models...
    window.database_schema = @json($tables);

    // On demand models...
    window.column_checkbox_model;

    function load_checkbox_model(_table) {
        var html = '<div class="row">';

        for(x in window.database_schema[_table]) {
            html += '<div class="col-sm-4">'
                    + '<input class=form-check-input"" type="checkbox" name="{{ $param_names['select-column'] }}[]" id="checkbox-source-columns-'+x+'" value="'+x+'" checked></input>'
                    + '<label class="form-check-label" for="checkbox-source-columns-'+x+'"> '+x+'</label>'
                + '</div>';
        }

        html += '</div>';

        window.column_checkbox_model = html;
    }

    function html_where_column(_table) {

        var html = '<select name="{{ $param_names['where-column'] }}[]" class="form-control">';

        for(key in window.database_schema[_table]) {
            html += '<option value="'+key+'">'+key+'</option>';
        }

        html += '</select>';

        return html;
    }

    function html_where_condition(_table, _column) {
        var html = '<select name="{{ $param_names['where-condition'] }}[]" class="form-control">';
        if(_column === null) {
            html += '<option value=""> - </option>';
        }
        else {
            switch(window.database_schema[_table][_column]) {
                case 'STRING':
                    html += '<option value="EQUALS">EQUALS</option>'
                        + '<option value="NOT EQUALS">NOT EQUALS</option>'
                        + '<option value="CONTAINS">CONTAINS</option>'
                        + '<option value="NOT CONTAINS">NOT CONTAINS</option>';
                    break;
                case 'INTEGER':
                    html += '<option value="EQUALS">EQUALS</option>'
                        + '<option value="NOT EQUALS">NOT EQUALS</option>'
                        + '<option value="GREATER THAN">GREATER THAN</option>'
                        + '<option value="LESS THAN">LESS THAN</option>';
                    break;
                case 'FLOAT':
                    html += '<option value="EQUALS">EQUALS</option>'
                        + '<option value="NOT EQUALS">NOT EQUALS</option>'
                        + '<option value="GREATER THAN">GREATER THAN</option>'
                        + '<option value="LESS THAN">LESS THAN</option>';
                    break;
                case 'BOOLEAN':
                    html += '<option value="IS">IS</option>'
                        + '<option value="IS NOT">IS NOT</option>'
                    break;
                case 'TIME':
                    html += '<option value="EQUALS">EQUALS</option>'
                        + '<option value="NOT EQUALS">NOT EQUALS</option>'
                        + '<option value="GREATER THAN">GREATER THAN</option>'
                        + '<option value="LESS THAN">LESS THAN</option>';
                    break;                    
            }
        } 
        

        html += '</select>';

        return html;
    }

    function html_where_input(_table, _column) {
        var html;

        if(_column === null) {
            html = '<input class="form-control" name="{{ $param_names['where-value'] }}[]" type="text" placeholder=" - " readonly></input>';
        } else {
            switch(window.database_schema[_table][_column]) {
                case 'STRING':
                    html = '<input class="form-control" name="{{ $param_names['where-value'] }}[]" type="text" placeholder="Enter text..."></input>';
                    break;
                case 'INTEGER':
                    html = '<input class="form-control" name="{{ $param_names['where-value'] }}[]" type="number" step="1" placeholder="0"></input>';
                    break;
                case 'FLOAT':
                    html = '<input class="form-control" name="{{ $param_names['where-value'] }}[]" type="number" placeholder="0"></input>';
                    break;
                case 'BOOLEAN':
                    html = '<select class="form-control" name="{{ $param_names['where-value'] }}[]"><option value="TRUE">TRUE</option><option value="FALSE">FALSE</option></select>'
                    break;
                case 'TIME':
                    html = '<input class="form-control" name="{{ $param_names['where-value'] }}[]" type="date"></input>';
                    break;
            }
        }

        return html;
    }

    function html_where_row(_table) {
        return '<div class="row mb-2">'
                + '<div class="col where-column">' + html_where_column(_table) + '</div>'
                + '<div class="col where-condition">' + html_where_condition(_table, null) + '</div>'
                + '<div class="col where-input">' + html_where_input(_table, null) + '</div>'
                + '<div class="col d-grid"><button type="button" class="remove-condition btn btn-danger">Remove</button></div>'
            + '</div>';
    }

    /* When a table is selected, we then need to allow the user to:
        - Specify which sets of information they want from it.
        - Create where conditions
    */
   $(document).ready(function() {

    /* On a table being selected */
        $('#table-select').on('change', function(x) {
            //Clean up old query.
            $('#condition-container').empty();

           //Update column-checkbox JS model.
           load_checkbox_model($(this).find(":selected").text());

           //Render the column-checkbox JS model.
           $('#column-group').html(window.column_checkbox_model);
       }).trigger('change');

       /* Add a condition when the condition button is clicked */
       $('#add-condition').on('click', function(x) {
           $('#condition-container').append(html_where_row($('#table-select').find(":selected").text()));
           
           $('.remove-condition').on('click', function(i) {
                    console.log(i);
                    $(i.currentTarget).parent().parent().remove();
            }).trigger('change');

           $('.form-control[name="{{ $param_names['where-column'] }}[]"]').on('change', function(e) {
                $(e.currentTarget).parent().siblings('.where-condition').html(html_where_condition($('#table-select').find(':selected').text(), $(e.currentTarget).val()));
                

                $('.form-control[name="{{ $param_names['where-condition'] }}[]"]').on('change', function(i) {
                    var current_column = $(i.currentTarget).parent().siblings('.where-column').children('select').val();
                    console.log(current_column);
                    $(i.currentTarget).parent().siblings('.where-input').html(html_where_input($('#table-select').find(':selected').text(), current_column));
                }).trigger('change');
            }).trigger('change');
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
                <h2>Query Crafting</h2>
            </div>
            <div class="col-sm-4 text-center mx-auto">
                
            </div>
        </div>
    </div>
    <div class="card-body">
        <span>Please build the question that you'd like to ask the database...</span>
        <form action="{{ route('table-view') }}" method="GET">
            {{-- Hidden Variables --}}
            @csrf
            <input name="{{ $param_names['database-name'] }}" type="text" value="{{ $database }}" hidden readonly></input>
            {{-- Table Selection --}}
            <div class="form-group">
                <label for="table-select">Table</label>
                <select id='table-select' name="{{ $param_names['table-name'] }}" class="form-control">
                @foreach($tables as $table_name => $table_columns)
                    <option value="{{ $table_name }}">{{ $table_name }}</option>
                @endforeach
                </select>
                <small id="table-select-help" class="form-text text-muted">This is where you would like to pull the information from...</small>
            </div>
            </br>
            {{-- Join Selection --}}

            {{-- Column Selection --}}
            <div class="form-group">
                <label for="column-group">Columns</label>
                <div id="column-group"></div>
                <small id="column-group-help" class="form-text text-muted">These are the bits of information you'd like to retrieve. Just tick the boxes for the columns that you want information about.</small>
            </div>
            </br>
            {{-- Where Selection --}}
            <div class="form-group">
                <label for="where-group">Where</label>
                <div id="where-group"></div>
                <small id="where-group-help" class="form-text text-muted">This is where you define any filtering you would like on this query. Build it by pressing 'Add Condition' and filling out the condition from left-to-right.</small>
            </div>
            <div class="form-group">
                <div class="d-grid">
                    <button id="add-condition" type="button" class="btn btn-secondary">Add Condition</button>
                </div>
                <div id="condition-container" class="container-fluid mt-3">

                </div>
            </div>

            {{-- Submit Button --}}
            <div class="d-grid">
                <button class="btn btn-primary" type="submit">Confirm</button>
            </div>
        </form>
    </div>
</div>
@endsection