<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableAccess extends Model
{
    protected $table = 'table_access';

    protected $fillable = [
        'database_name',
        'table_name',
        'user_id'
    ];
}
