<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Club;
use App\Models\ClubImage;
use App\Models\Court;
use App\Models\OpeningHours;
use Illuminate\Support\Facades\DB;

class ClubSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 10; $i++) {

            try {
                DB::beginTransaction();

                $club = Club::create([
                    'name' => $faker->company,
                    'address' => $faker->address,
                    'contact_number' => $faker->numerify('##########'),
                    'latitude' => $faker->latitude,
                    'longitude' => $faker->longitude,
                    'price_per_hour' => $faker->randomFloat(2, 10, 100),
                    'slot_duration' => $faker->randomElement([30, 60, 90, 120]),
                    'is_padel_available' => true,
                    'is_pickle_ball_available' => $faker->boolean,
                ]);

                // Create club images
                for ($j = 0; $j < 3; $j++) {
                    $imageUrl = $faker->imageUrl();
                    ClubImage::create([
                        'club_id' => $club->id,
                        'image' => $imageUrl,
                    ]);
                }

                // Create opening hours
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach ($days as $day) {
                    $openTime = $faker->time('H:i:s');
                    $closeTime = $faker->time('H:i:s');
                    OpeningHours::create([
                        'club_id' => $club->id,
                        'day' => $day,
                        'open_time' => $openTime,
                        'close_time' => $closeTime,
                    ]);
                }

                // Create courts
                $sports = ['Padel', 'Pickle Ball'];
                $courtTypes = ['indoor', 'outdoor', 'roofed outdoor'];

                for ($k = 0; $k < 3; $k++) {
                    Court::create([
                        'club_id' => $club->id,
                        'name' => $faker->word,
                        'description' => $faker->sentence,
                        'sport' => $faker->randomElement($sports),
                        'court_type' => $faker->randomElement($courtTypes),
                        'features' => implode(', ', $faker->randomElements(['Lighting', 'Refreshment area', 'Locker room', 'Shower facilities'], $faker->numberBetween(1, 4))),
                        'status' => 'active',
                    ]);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }
}
