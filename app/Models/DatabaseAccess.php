<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseAccess extends Model
{
    protected $table = 'database_access';

    protected $fillable = [
        'name',
        'user_id'
    ];
}
