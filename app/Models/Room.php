<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'name',
        'code',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function participants(): HasMany
    {
        return $this->hasMany(UsersInRooms::class);
    }

    public function strokes(): HasMany
    {
        return $this->hasMany(RoomStroke::class);
    }
}
