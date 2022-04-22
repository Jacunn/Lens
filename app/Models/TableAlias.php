<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableAlias extends Model
{
    protected $table = 'table_aliases';

    protected $fillable = [
        'database_name',
        'table_name',
        'alias'
    ];
}
