<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'description',
        'sport',
        'court_type',
        'features',
        'status',
    ];

    protected $hidden = ['created_at', 'updated_at'];


    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
