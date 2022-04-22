<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseAlias extends Model
{
    protected $table = 'database_aliases';

    protected $fillable = [
        'name',
        'alias'
    ];
}
