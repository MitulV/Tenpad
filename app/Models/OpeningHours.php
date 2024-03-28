<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningHours extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'day',
        'open_time',
        'close_time',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
