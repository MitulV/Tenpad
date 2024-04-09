<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'image',
        'contact_number',
        'website',
        'latitude',
        'longitude',
        'facilities',
        'price_per_hour',
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

}
