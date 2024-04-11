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
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('contact_number');
            $table->string('website')->nullable();
            $table->double('latitude', 10, 6);
            $table->double('longitude', 10, 6);
            $table->json('facilities')->nullable();
            $table->decimal('price_per_hour', 10, 2);
            $table->integer('slot_duration')->default(60);
            $table->boolean('is_padel_available')->default(false);
            $table->boolean('is_pickle_ball_available')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
