<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('club_id')->constrained();
            $table->foreignId('court_id')->constrained();
            $table->time('start_time');
            $table->time('end_time');
            $table->date('booking_date');
            $table->enum('match_type', ['Competitive', 'Friendly'])->nullable();
            $table->enum('play_with_gender', ['All players', 'Men only', 'Female only'])->nullable();
            $table->enum('match_visibility', ['Public', 'Private'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
