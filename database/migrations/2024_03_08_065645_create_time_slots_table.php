<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->time('slot_time');
            $table->string('slot_group');
            $table->timestamps();
        });

        $startTime = now()->setTime(6, 30);
        $endTime = now()->setTime(18, 30);
        $interval = 60; // 1 hour in minutes

        while ($startTime <= $endTime) {
            $slotTime = $startTime->format('H:i:s');
            $slotGroup = $this->getSlotGroup($startTime->format('H'));

            DB::table('time_slots')->insert([
                'slot_time' => $slotTime,
                'slot_group' => $slotGroup,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $startTime->addMinutes($interval);
        }
    }

    private function getSlotGroup($hour)
    {
        if ($hour >= 6 && $hour < 12) {
            return 'Morning';
        } elseif ($hour >= 12 && $hour < 18) {
            return 'Afternoon';
        } else {
            return 'Evening';
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
