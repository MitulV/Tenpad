<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubImage extends Model
{
    use HasFactory;

    protected $fillable = ['image'];
    protected $hidden = ['created_at', 'updated_at'];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
