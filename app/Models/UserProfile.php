<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'profile_pic',
        'country_code',
        'number',
        'dob',
        'gender',
        'best_hand',
        'court_side',
        'match_type',
        'preferred_time_to_play',
        'experience_level',
        'matches_played_last_3_months',
        'fitness_level',
        'padel_experience',
        'has_played_other_sport',
        'is_tenpad_advance_member',
        'tenpad_advance_padel_federation_name',
        'tenpad_advance_membership_number',
        'tenpad_advance_current_rank',
        'profile_score'
    ];

    protected $casts = [
        'dob' => 'date',
        'profile_pic' => 'string',
        'profile_score' => 'double', 
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
