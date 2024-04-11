<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'contact_number',
        'website',
        'latitude',
        'longitude',
        'facilities',
        'price_per_hour',
        'slot_duration',
        'is_padel_available',
        'is_pickle_ball_available'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // Assuming 'facilities' is a JSON column, you may want to cast it as an array
    protected $casts = [
        'facilities' => 'array',
    ];

    public function openingHours()
    {
        return $this->hasMany(OpeningHours::class);
    }

    public function images()
    {
        return $this->hasMany(ClubImage::class);
    }

    public function courts()
    {
        return $this->hasMany(Court::class);
    }

}
