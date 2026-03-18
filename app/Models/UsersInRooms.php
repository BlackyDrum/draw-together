<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersInRooms extends Model
{
    protected $fillable = [
        'session_id',
        'room_id'
    ];
}
