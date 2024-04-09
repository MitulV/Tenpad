<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = ['slot_time', 'slot_group'];

    protected $hidden = ['created_at', 'updated_at'];


    public function bookings()
    {
        return $this->hasMany(Booking::class, 'time_slot_id');
    }
}
