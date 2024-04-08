<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfilesTable extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->string('profile_pic')->nullable();
            $table->string('country_code');
            $table->string('number');
            $table->date('dob');
            $table->enum('gender', ['Male', 'Female', 'Undisclosed']);
            $table->enum('best_hand', ['Right-handed', 'Left-handed', 'Both']);
            $table->enum('court_side', ['Backhand', 'Forehand', 'Both Sides']);
            $table->enum('match_type', ['Competitive', 'Friendly', 'Both']);
            $table->enum('preferred_time_to_play', ['Morning', 'Afternoon', 'Evening', 'All day']);
            $table->enum('experience_level', ['Beginner', 'Intermediate', 'Advanced', 'Semi-Pro', 'Professional']);
            $table->enum('matches_played_last_3_months', ['Less than 5', 'More than 5', 'Less than 10', 'More than 10', 'More than 15']);
            $table->enum('fitness_level', ['Excellent', 'Good', 'Normal', 'Low', 'Very low']);
            $table->enum('padel_experience', ['Less than 1 year', 'Less than 2 years', 'More than 2 years'])->required();
            $table->enum('has_played_other_sport', ['No, Never', 'Yes, less than two years', 'Yes, more than two years', 'Yes, more than five years'])->required();
            $table->enum('is_tenpad_advance_member', ['Yes', 'No'])->required();
            $table->string('tenpad_advance_padel_federation_name')->nullable();
            $table->string('tenpad_advance_membership_number')->nullable();
            $table->string('tenpad_advance_current_rank')->nullable();
            $table->decimal('profile_score', 4, 2)->default(0.00)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
}
