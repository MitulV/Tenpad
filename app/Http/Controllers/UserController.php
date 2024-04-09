<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function createUserProfile(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string',
            'profile_pic' => ['required', 'image'],
            'country_code' => 'required|string',
            'number' => 'required|string',
            'dob' => 'required|date',
            'gender' => 'required|in:Male,Female,Undisclosed',
            'best_hand' => 'required|in:Right-handed,Left-handed,Both',
            'court_side' => 'required|in:Backhand,Forehand,Both Sides',
            'match_type' => 'required|in:Competitive,Friendly,Both',
            'preferred_time_to_play' => 'required|in:Morning,Afternoon,Evening,All day',
            'experience_level' => 'required|in:Beginner,Intermediate,Advanced,Semi-Pro,Professional',
            'matches_played_last_3_months' => 'required|in:"Less than 5","More than 5","Less than 10","More than 10","More than 15"',
            'fitness_level' => 'required|in:Excellent,Good,Normal,Low,Very low',
            'padel_experience' => 'required|in:"Less than 1 year","Less than 2 years","More than 2 years"',
            'has_played_other_sport' => 'required|in:"No, never","Yes, less than two years","Yes, more than two years","Yes, more than five years"',
            'is_tenpad_advance_member' => 'required|in:Yes,No',
            'tenpad_advance_padel_federation_name' => 'required_if:is_tenpad_advance_member,No',
            'tenpad_advance_membership_number' => 'required_if:is_tenpad_advance_member,No',
            'tenpad_advance_current_rank' => 'required_if:is_tenpad_advance_member,No',
        ]);

        // Get the selected experience level and matches played
        $experienceLevel = $request['experience_level'];
        $matchesPlayed = $request['matches_played_last_3_months'];
        $profileScore = 0.00;

        // Define the score ranges
        $scoreRanges = [
            'Beginner' => ['min' => 0.5, 'max' => 2.49],
            'Intermediate' => ['min' => 2.5, 'max' => 3.99],
        ];

        // Calculate profile score based on conditions
        if (in_array($experienceLevel, ['Beginner', 'Intermediate'])) {
            // Set the default score
            $profileScore = $scoreRanges[$experienceLevel]['min'];

            // Adjust the score based on matches played
            switch ($matchesPlayed) {
                case 'More than 5':
                case 'Less than 10':
                case 'More than 10':
                    $profileScore += 1;
                    break;
                case 'More than 15':
                    $profileScore = $scoreRanges[$experienceLevel]['max'];
                    break;
                default:
                    // No need to adjust the score for other cases
                    break;
            }
        }

        $user = auth()->user();

        $existingProfile = $user->profile;

        if ($existingProfile) {
            // If profile already exists, return a message
            return response()->json(['message' => 'Profile already exists for the user', 'user_profile' => $existingProfile]);
        }

        // If no existing profile, create a new one
        $uniqueFilename = 'profile_pic_' . $user->id . '_' . uniqid() . '.' . $request->file('profile_pic')->getClientOriginalExtension();

        $imagePath = $request->file('profile_pic')->storeAs('profile_pics', $uniqueFilename, 'public');

        $imageUrl = asset('storage/' . $imagePath);

        $userProfile = UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $request->input('full_name'),
            'profile_pic' => $imageUrl,
            'country_code' => $request->input('country_code'),
            'number' => $request->input('number'),
            'dob' => $request->input('dob'),
            'gender' => $request->input('gender'),
            'best_hand' => $request->input('best_hand'),
            'court_side' => $request->input('court_side'),
            'match_type' => $request->input('match_type'),
            'preferred_time_to_play' => $request->input('preferred_time_to_play'),
            'experience_level' => $request->input('experience_level'),
            'matches_played_last_3_months' => $request->input('matches_played_last_3_months'),
            'fitness_level' => $request->input('fitness_level'),
            'padel_experience' => $request->input('padel_experience'),
            'has_played_other_sport' => $request->input('has_played_other_sport'),
            'is_tenpad_advance_member' => $request->input('is_tenpad_advance_member'),
            'tenpad_advance_padel_federation_name' => $request->input('tenpad_advance_padel_federation_name'),
            'tenpad_advance_membership_number' => $request->input('tenpad_advance_membership_number'),
            'tenpad_advance_current_rank' => $request->input('tenpad_advance_current_rank'),
            'profile_score' => $profileScore
        ]);

        return response()->json([
            'message' => 'User profile created successfully',
            'user' => $user,
            'profile' => $userProfile
        ], Response::HTTP_CREATED);
    }


    public function getUserProfile()
    {

        $user = auth()->user();
        $userProfile = $user->profile;

        if (!$userProfile) {
            return response()->json(['message' => 'User profile not found'], 404);
        }

        $result = [
            "id" => $userProfile->id,
            "user_id" =>  $userProfile->user_id,
            "full_name" => $userProfile->full_name,
            "profile_pic" => $userProfile->profile_pic,
            "country_code" => $userProfile->country_code,
            "number" => $userProfile->number,
            "dob" => $userProfile->dob,
            "gender" => $userProfile->gender,
            "best_hand" => $userProfile->best_hand,
            "court_side" => $userProfile->court_side,
            "match_type" => $userProfile->match_type,
            "preferred_time_to_play" => $userProfile->preferred_time_to_play,
            "experience_level" => $userProfile->experience_level,
            "matches_played_last_3_months" => $userProfile->matches_played_last_3_months,
            "fitness_level" => $userProfile->fitness_level,
            "padel_experience" => $userProfile->padel_experience,
            "has_played_other_sport" => $userProfile->has_played_other_sport,
            "is_tenpad_advance_member" => $userProfile->is_tenpad_advance_member,
            "tenpad_advance_padel_federation_name" => $userProfile->tenpad_advance_padel_federation_name,
            "tenpad_advance_membership_number" => $userProfile->tenpad_advance_membership_number,
            "tenpad_advance_current_rank" => $userProfile->tenpad_advance_current_rank,
            "profile_score" => $userProfile->profile_score,
            'matches' => 12,
            'followers' => 50,
            'followings' => 75,
            'current level of reliability' => 'High',
            'location' => 'United States',
            'address' => 'United States',
        ];

        return response()->json(['data' => $result], 200);
    }
}
