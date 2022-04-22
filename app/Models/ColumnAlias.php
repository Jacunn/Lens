<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColumnAlias extends Model
{
    protected $table = 'column_aliases';

    protected $fillable = [
        'database_name',
        'table_name',
        'column_name',
        'alias'
    ];
}
