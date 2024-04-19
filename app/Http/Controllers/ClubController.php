<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Club;
use Illuminate\Http\Request;
use App\Models\ClubImage;
use App\Models\Court;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClubController extends Controller
{

    public function signup(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email,{$user->id}'],
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $user = User::create([
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'user_type' => 'Club',
        ]);

        $SECRET = env("SENCTUM_SECRET", "APP_SECRET");
        $token = $user->createToken($SECRET)->plainTextToken;

        return response()->json([
            'message' => 'Club created Successfully',
            'user' => $user,
            'token' => $token
        ], Response::HTTP_CREATED);
    }


    public function signin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user ||  !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'UnAuthenticated'], 401);
        }

        $SECRET = env("SENCTUM_SECRET", "APP_SECRET");
        $token = $user->createToken($SECRET)->plainTextToken;


        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'contact_number' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'facilities' => 'nullable|array',
            'price_per_hour' => 'required|numeric',
            'opening_hours' => 'required|array',
            'club_images' => ['required', 'array'],
            'club_images.*' => ['image'],
            'slot_duration' => 'required|integer|min:1',
            'is_padel_available' => ['required'],
            'is_pickle_ball_available' => ['required'],
            'courts' => 'required|array',
            'courts.*.name' => 'required|string',
            'courts.*.description' => 'nullable|string',
            'courts.*.sport' => 'required|in:Padel,"Pickle Ball"',
            'courts.*.court_type' => 'required|in:indoor,outdoor,"roofed outdoor"',
            'courts.*.features' => 'nullable|string',
            'courts.*.status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            $club = Club::create($request->except(['opening_hours', 'club_images']));

            foreach ($request->file('club_images') as $image) {
                $uniqueFilename = 'club_image_' . $club->id . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('club_images', $uniqueFilename, 'public');
                $imageUrl = asset('storage/' . $imagePath);

                ClubImage::create([
                    'club_id' => $club->id,
                    'image' => $imageUrl,
                ]);
            }

            foreach ($request->input('opening_hours') as $openingHour) {
                $openTime = Carbon::createFromFormat('h:i A', $openingHour['timeSlots'][0]['openTime'])->format('H:i:s');
                $closeTime = Carbon::createFromFormat('h:i A', $openingHour['timeSlots'][0]['closeTime'])->format('H:i:s');

                $club->openingHours()->create([
                    'day' => $openingHour['day'],
                    'open_time' => $openTime,
                    'close_time' => $closeTime,
                ]);
            }


            foreach ($request->courts as $courtData) {
                Court::create([
                    'club_id' => $club->id,
                    'name' => $courtData['name'],
                    'description' => $courtData['description'] ?? null,
                    'sport' => $courtData['sport'],
                    'court_type' => $courtData['court_type'],
                    'features' => $courtData['features'] ?? null,
                    'status' => $courtData['status'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Club created successfully', 'club' => $club->load('openingHours', 'courts')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create club.', 'error_obj' => $e], 500);
        }
    }

    public function searchClubs(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string',
            'is_padel_available' => 'nullable|boolean',
            'is_pickle_ball_available' => 'nullable|boolean',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        $query = $request->input('query');
        $isPadelAvailable = $request->input('is_padel_available');
        $isPickleBallAvailable = $request->input('is_pickle_ball_available');
        $date = $request->input('date');
        $time = $request->input('time');

        $queryBuilder = Club::query();

        // Filter clubs based on is_padel_available if given
        if ($isPadelAvailable !== null) {
            $queryBuilder->where('is_padel_available', $isPadelAvailable);
        }

        // Filter clubs based on is_pickle_ball_available if given
        if ($isPickleBallAvailable !== null) {
            $queryBuilder->where('is_pickle_ball_available', $isPickleBallAvailable);
        }

        // If query is provided, search clubs by name or address containing the query
        if (!empty($query)) {
            $queryBuilder->where(function ($queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', "%$query%")
                    ->orWhere('address', 'like', "%$query%");
            });

            /** @var User|null $user */
            $user = Auth::user();
            $user->recentSearches()->create(['query' => $query]);
        }

        // Get all clubs with images
        $clubsWithImages = $queryBuilder->with('clubImages')->get();
        $clubsWithAvailableCourts = [];
        
        // Iterate over each club
        foreach ($clubsWithImages as $club) {

            $clubId = $club->id;

            $courts = Court::where('club_id', $clubId)->get();

            $isAvailable = false;

            foreach ($courts as $court) {
                $courtId = $court->id;

                $isSlotAvailable = !Booking::where('club_id', $clubId)
                    ->where('court_id', $courtId)
                    ->where('booking_date', $date)
                    ->where('start_time', '<=', $time) // Check if the booking starts before or at the specified time
                    ->where('end_time', '>', $time)    // Check if the booking ends after the specified time
                    ->exists();

                if ($isSlotAvailable) {
                    $isAvailable = true;
                    break;
                }
            }

            if ($isAvailable) {
                $clubsWithAvailableCourts[] = $club;
            }
        }

        return response()->json(['data' => $clubsWithAvailableCourts], 200);
    }



    public function searchClubsByLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        // Default range in Km
        $rangeInKm = 5;

        $clubs = Club::selectRaw("
        clubs.*,
        club_images.image,
        (6371 * acos(cos(radians(?)) * cos(radians(clubs.latitude)) * cos(radians(clubs.longitude) - radians(?)) + sin(radians(?)) * sin(radians(clubs.latitude))))
        AS distance
    ", [$request->input('latitude'), $request->input('longitude'), $request->input('latitude')])
            ->leftJoin('club_images', 'clubs.id', '=', 'club_images.club_id')
            ->having('distance', '<=', $rangeInKm)
            ->orderBy('distance')
            ->get();


        return response()->json(['data' => $clubs], 200);
    }

    public function getClubDetails(Request $request, $id)
    {
        $club = Club::with(['openingHours', 'clubImages', 'courts'])->find($id);

        if (!$club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        return response()->json(['data' => $club], 200);
    }

    public function calculateDistanceBetweenUserAndClub(Request $request, $clubId)
    {
        $userLatitude = $request->query('latitude');
        $userLongitude = $request->query('longitude');

        // Fetch the club's latitude and longitude based on the club ID
        $club = Club::findOrFail($clubId);

        $clubLatitude = $club->latitude;
        $clubLongitude = $club->longitude;

        // Earth radius in kilometers
        $earthRadius = 6371;

        // Convert latitude and longitude from degrees to radians
        $userLatRad = deg2rad($userLatitude);
        $clubLatRad = deg2rad($clubLatitude);
        $latDiffRad = deg2rad($clubLatitude - $userLatitude);
        $lonDiffRad = deg2rad($clubLongitude - $userLongitude);


        // Haversine formula
        $a = sin($latDiffRad / 2) * sin($latDiffRad / 2) +
            cos($userLatRad) * cos($clubLatRad) *
            sin($lonDiffRad / 2) * sin($lonDiffRad / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        // Return the calculated distance
        return $distance;
    }
}
