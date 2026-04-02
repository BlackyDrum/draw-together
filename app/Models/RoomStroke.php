<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomStroke extends Model
{
    protected $fillable = [
        'room_id',
        'stroke_id',
        'session_id',
        'display_name',
        'tool',
        'color',
        'size',
        'points',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'array',
            'size' => 'integer',
            'position' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
