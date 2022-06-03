<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use App\Models\DatabaseAlias;
use App\Models\TableAlias;
use App\Models\ColumnAlias;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    // Constants for inputs shared across functions. These are kept short for the purposes of generating shorter links.
    private $param_names = [
        'where-column'      => 'a',
        'where-condition'   => 'b',
        'where-value'       => 'c',
        'select-column'     => 'd',
        'table-name'        => 'e',
        'database-name'     => 'f',
        'dt-order'          => 'g',
        'dt-search'         => 'h',
        'dt-start'          => 'i',
        'dt-length'         => 'j',
        'dt-draw'           => 'k',
        'dt-order-col'      => 'l',
        'dt-order-dir'      => 'm',
        'dt-search-value'   => 'n',
        'dt-search-regex'   => 'o'
    ]; 

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    function page_database_select() {
        $database_results = DB::select('SHOW DATABASES');
        $database_list = [];

        // Select all potential aliases so that we're only performing one query...
        $database_aliases = DatabaseAlias::all()->keyBy('name')->toArray();

        // Select all of the databases accessible to the user...
        $accessible_databases = Auth::user()->databasesAccessible();

        foreach($database_results as $database) {
            // Check if the user has access to that database, or no access is required.
            if(isset($accessible_databases[$database->Database]) || config('lens.require_permission')) {
                // Check if an alias for this database exists - If so, use that. If not, use the default name.
                $database_list[] = [
                    'alias' => isset($database_aliases[$database->Database]) ? $database_aliases[$database->Database]['alias'] : $database->Database, 
                    'name' => $database->Database
                ];
            }
        }

        return view('page.database-select', [
            'select_database' => $database_list,
            'param_names' => $this->param_names
        ]);
    }

    function page_query_crafter(Request $_request) {
        $database_input = $_request->input($this->param_names['database-name']);
        $database_exists = count(DB::select('SHOW DATABASES WHERE `Database` LIKE ?', [$database_input]));

        // Check whether we're being provided a valid database - If not, redirect the user to the database selection page.
        if($database_exists)
        {
            // Get table and column aliases for a given database so we're only querying this once...
            $table_aliases = TableAlias::where('database_name', '=', $this->param_names['database-name'])->get()->keyBy('table_name');
            $column_aliases = ColumnAlias::where('database_name', '=', $this->param_names['database-name'])->get()->keyBy(function($x) {
                return "$x->table_name.$x->column_name";
            });

            $table_results = DB::select("SHOW FULL TABLES FROM $database_input WHERE Table_Type NOT LIKE 'VIEW'");
            $table_list = [];

            // Select all of the tables accessible to the user...
            $accessible_tables = Auth::user()->tablesAccessible($database_input);

            foreach($table_results as $table) {
                // Extract the name from our above query results...
                $table_name = $table->{"Tables_in_$database_input"};

                // Check if the user has permission to access the table, or if access permission is not required.
                if(isset($accessible_tables[$table_name]) || config('lens.require_permission')) {
                    // Get a result list of columns for a given table...
                    $table_description_results = DB::select("DESCRIBE $database_input.$table_name");

                    $table_list[$table_name] = [
                        'table_name' => $table_name,
                        'table_alias' => isset($table_aliases->$table_name) ? $table_aliases->$table_name->alias : $table_name,
                        'table_columns' => []
                    ];

                    // Iterate across those columns and produce a model of how the data is going to look...
                    foreach($table_description_results as $column) {
                        $table_list[$table_name]['table_columns'][$column->Field] = [
                            'name' => $column->Field,
                            'alias' => isset($column_aliases->{"$table_name.$column->Field"}) ? $column_aliases->{"$table_name.$column->Field"}->alias : $column->Field,
                            'type' => $this->process_type($column->Type)
                        ]; 
                    }
                }
            }

            return view('page.query-crafter', [
                'tables' => $table_list,
                'database' => $database_input,
                'back_link' => route('database-select'),
                'param_names' => $this->param_names
            ]);
        }
    }

    function page_table_view(Request $_request) {
        // The database, table and columns we're returning from.
        $database_name = $_request->input($this->param_names['database-name']);
        $table_name = $_request->input($this->param_names['table-name']);
        $column_names = $_request->input($this->param_names['select-column']);
                
        // The filtering we're performing against the database.
        $where_columns = $_request->input($this->param_names['where-column']);
        $where_conditions = $_request->input($this->param_names['where-condition']);
        $where_inputs = $_request->input($this->param_names['where-value']);

        // Build simple model of the query to pass on to the end user...
        $query = DB::table("$database_name.$table_name");

        // Build up our where conditions for insertion.
        $conditions = [];

        if($where_columns !== null) {
            for($i = count($where_columns) - 1; $i >= 0; --$i) {
                if($this->valid_condition($database_name, $table_name, $where_columns[$i], $where_conditions[$i])) {
                    
                    $conditions[] = $this->translate_condition($where_columns[$i], $where_conditions[$i], $where_inputs[$i]);
                }
            }
        }

        // Add the where conditions on. We'll never execute this, we just want it's SQL...
        $query = $query->where($conditions);

        // Generate our columns
        $column_aliases = ColumnAlias::where([
            ['database_name', '=', $this->param_names['database-name']],
            ['table_name', '=', $this->param_names['table-name']]
        ])->get()->keyBy('column_name');
        
        return view('page.table-view', [
            'get_parameters' => $_request->all(),
            'query_sql' => $query->toSql(),
            'columns' => $column_names,
            'column_aliases' => $column_aliases,
            'copy_link' => route('table-view', $_request->all()),
            'back_link' => route('query-crafter', [$database_name => $database_name]),
            'param_names' => $this->param_names
        ]);
    }

    function callback_table_csv(Request $_request) {
        $database_name = $_request->input($this->param_names['database-name']);
        $table_name = $_request->input($this->param_names['table-name']);
        $column_names = $_request->input($this->param_names['select-column']);

        if(Auth::user()->tablesAccessible($database_name) && Auth::user()->tablesAccessible($table_name)) {
            // Reserve our query object for building.
            $query = DB::table("$database_name.$table_name");

            // Build up our where conditions for insertion.
            $conditions = [];

            $where_columns = $_request->input($this->param_names['where-column']);
            $where_conditions = $_request->input($this->param_names['where-condition']);
            $where_inputs = $_request->input($this->param_names['where-value']);

            if($where_columns !== null) {
                for($i = count($where_columns) - 1; $i >= 0; --$i) {
                    if($this->valid_condition($database_name, $table_name, $where_columns[$i], $where_conditions[$i])) {
                        
                        $conditions[] = $this->translate_condition($where_columns[$i], $where_conditions[$i], $where_inputs[$i]);
                    }
                }
            }

            // Put in our pagination and attach the where conditions.
            $results = $query->where($conditions)->get();

            // Filter for our desired columns.
            $product = [];

            foreach($results as $row) {
                $filtered_row = [];
                foreach($column_names as $column) {
                    $filtered_row[] = $row->$column;
                }

                $product[] = $filtered_row;
            }

            // Convert it into data-tables format.    
            return view('export.csv', [ 'rows' => $product ]);
        }
    }

    function callback_table_view(Request $_request) {
        // The database, table and columns we're returning from.
        $select_database = $_request->input($this->param_names['database-name']);
        $select_table = $_request->input($this->param_names['table-name']);
        $select_columns = $_request->input($this->param_names['select-column']);
        
        // The filtering we're performing against the database.
        $where_columns = $_request->input($this->param_names['where-column']);
        $where_conditions = $_request->input($this->param_names['where-condition']);
        $where_inputs = $_request->input($this->param_names['where-value']);

        // Additional filtering for pagination and searching.
        $skip_rows = $_request->input($this->param_names['dt-start']) ?? 0;
        $take_rows = $_request->input($this->param_names['dt-length']) ?? 100;
        $search_criteria = $_request->input($this->param_names['dt-search'])[$this->param_names['dt-search-value']];

        // Reserve our query object for building.
        $filtered_query = DB::table("$select_database.$select_table");

        // Build up our where conditions for insertion.
        $conditions = [];

        if($where_columns !== null) {
            for($i = count($where_columns) - 1; $i >= 0; --$i) {
                if($this->valid_condition($select_database, $select_table, $where_columns[$i], $where_conditions[$i])) {
                    
                    $conditions[] = $this->translate_condition($where_columns[$i], $where_conditions[$i], $where_inputs[$i]);
                }
            }
        }

        // Put in our pagination and attach the where conditions.
        $filtered_query = $filtered_query->where($conditions)->where(function ($x) use($select_columns, $search_criteria) {
            // Generate additional where criteria if search conditions are supplied.
            if($search_criteria !== null) {
                for($i = count($select_columns) - 1; $i >= 0; --$i) {
                    $x->orWhere($select_columns[$i], 'LIKE', "%$search_criteria%");
                }
            }
        });

        // Now get the total of records there are.
        $total = DB::table("$select_database.$select_table")->count();
        
        // Get the total that meet our filter criteria.
        $filtered = $filtered_query->count();

        // Execute the query.
        $full_query = $filtered_query->skip($skip_rows)->take($take_rows);
        $results = $full_query->get();

        // Filter for our desired columns.
        $product = [];

        foreach($results as $row) {
            $filtered_row = [];
            foreach($select_columns as $column) {
                $filtered_row[$column] = $row->$column;
            }

            $product[] = $filtered_row;
        }

        // Convert it into data-tables format.    
        return response()->json([
            'draw' => $_request->input($this->param_names['dt-draw']),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $product,
            'query' => $full_query->toSql()
        ]);
    }

    /* Helper Functions */
    function get_schema($_database) {
        // Check if the database exists first.
        $database_exists = count(DB::select('SHOW DATABASES WHERE `Database` LIKE ?', [$_database]));

        if($database_exists) {
            // Get list of tables, and reserve a product.
            $table_results = DB::select("SHOW FULL TABLES FROM $_database WHERE Table_Type NOT LIKE 'VIEW'");
            $table_list = [];

            // Iterate over tables, building our schema.
            foreach($table_results as $table) {
                // Extract the name from our above query results...
                $table_name = $table->{"Tables_in_$_database"};

                // Get a result list of columns for a given table...
                $table_description_results = DB::select("DESCRIBE $_database.$table_name");

                // Iterate across those columns and produce a model of how the data is going to look...
                foreach($table_description_results as $column) {
                    $table_list[$table_name][$column->Field] = Controller::class::process_type($column->Type);
                }
            }

            return $table_list;
        }

        return null;
    }

    function translate_condition($_column, $_operator, $_value) {
        $condition = [];
        switch($_operator) {
            case 'EQUALS':
                $condition = [$_column, '=', $_value];
                break;
            case 'NOT EQUALS':
                $condition = [$_column, '!=', $_value];
                break;
            case 'GREATER THAN':
                $condition = [$_column, '>', $_value];
                break;
            case 'LESS THAN':
                $condition = [$_column, '<', $_value];
                break;
            case 'IS':
                $condition = [$_column, 'IS', $_value];
                break;
            case 'IS NOT':
                $condition = [$_column, 'IS NOT', $_value];
                break;
            case 'CONTAINS':
                $condition = [$_column, 'LIKE', "%$_value%"];
                break;
            case 'NOT CONTAINS':
                $condition = [$_column, 'NOT LIKE', "%$_value%"];
                break;
        }

        return $condition;
    }

    function valid_condition($_database, $_table, $_column, $_condition) {
        if($this->schema === null) {
            $this->schema = $this->get_schema($_database);
        }

        switch($this->schema[$_table][$_column]) {
            case 'STRING':
                if(preg_match('/(EQUALS|NOT EQUALS|CONTAINS|NOT CONTAINS)/i', $_condition) === 1) return true;
                break;
            case 'INTEGER':
                if(preg_match('/(EQUALS|NOT EQUALS|GREATER THAN|LESS THAN)/i', $_condition) === 1) return true;
                break;
            case 'FLOAT':
                if(preg_match('/(EQUALS|NOT EQUALS|GREATER THAN|LESS THAN)/i', $_condition) === 1) return true;
                break;
            case 'BOOLEAN':
                if(preg_match('/(IS|IS NOT)/i', $_condition) === 1) return true;
                break;
            case 'TIME':
                if(preg_match('/(EQUALS|NOT EQUALS|CONTAINS|NOT CONTAINS|GREATER THAN|LESS THAN)/i', $_condition) === 1) return true;
                break;   
        }

        return false;
    }

    static function process_type($_type) {
        // Determine what we're dealing with - We'll classify it as an integer, boolean, a float, bit, a string, or an IP address.
        $product = 'UNKNOWN';
        
        if(preg_match('/(TINYINT\(1\)|BOOLEAN)/i', $_type) === 1) { // Boolean group - TINYINT(1), BOOLEAN
            $product = 'BOOLEAN';
        }
        elseif(preg_match('/(TINYINT|SMALLINT|MEDIUMINT|INT|INTEGER|BIGINT)/i', $_type) === 1) { // Integer group - TINYINT(n), SMALLINT, MEDIUMINT, INT, INTEGER, BIGINT, INT1, INT2, INT3, INT4, INT8
            $product = 'INTEGER';
        }
        elseif(preg_match('/(DECIMAL|DEC|NUMERIC|FIXED|NUMBER|FLOAT|DOUBLE|DOUBLE PRECISION|REAL)/i', $_type) === 1) { // Float group - DECIMAL, DEC, NUMERIC, FIXED, NUMBER, FLOAT, DOUBLE, DOUBLE PRECISION, REAL
            $product = 'FLOAT';
        }
        elseif(preg_match('/(BIT)/', $_type) === 1) { // Bit group - BIT - Convert to hexadecimal and binary
            $product = 'BIT';
        }
        elseif(preg_match('/(BINARY|BLOB|TEXT|CHAR|CHAR BYTE|ENUM|JSON|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONG|LONG VARCHAR|LONGTEXT|TEXT|TINTBLOB|TINYTEXT|VARBINARY|VARCHAR|SET|UUID)/i', $_type) === 1) { // String group - BINARY, BLOB, TEXT, CHAR, CHAR BYTE, ENUM, JSON, MEDIUMBLOB, MEDIUMTEXT, LONGBLOB, LONG, LONG VARCHAR, LONGTEXT, TEXT, TINYBLOB, TINYTEXT, VARBINARY, VARCHAR, SET, UUID
            $product = 'STRING';
        }
        elseif(preg_match('/(INET6)/i', $_type) === 1) { // IP Address Group - INET6
            $product = 'IP';
        }
        elseif(preg_match('/(DATE|TIME|DATETIME|TIMESTAMP|YEAR)/i', $_type) === 1) { // Time Group - DATE, TIME, DATETIME, TIMESTAMP, YEAR
            $product =  'TIME';
        }
    
        return $product;
    }
}
