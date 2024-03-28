<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClubsTable extends Migration
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
            $table->string('image')->nullable(); // Assuming image is optional
            $table->string('contact_number');
            $table->string('website')->nullable(); // Assuming website is optional
            $table->double('latitude', 10, 6); // Adjust precision and scale as needed
            $table->double('longitude', 10, 6); // Adjust precision and scale as needed
            $table->json('facilities')->nullable();
            $table->decimal('price_per_hour', 10, 2); // Adjust precision and scale as needed
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
}
