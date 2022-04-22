<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'last_login'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    public function canAccessDatabase(string $_database) {
        return DatabaseAccess::where([['name', '=', $_database],['user_id', '=', $this->id]])->count() === 0 ? false : true;
    }

    public function canAccessTable(string $_database, string $_table) {
        return TableAccess::where([['database_name', '=', $_database],['table_name', '=', $_table],['user_id', '=', $this->id]])->count() === 0 ? false : true;
    }
}
