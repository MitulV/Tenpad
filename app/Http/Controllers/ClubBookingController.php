<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\Club;
use App\Models\OpeningHours;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ClubBookingController extends Controller
{
    public function create(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'contact_number' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'facilities' => 'nullable|array',
            'price_per_hour' => 'required|numeric',
            'opening_hours' => 'nullable|array', // assuming opening_hours is an array of day, open_time, close_time
        ]);

        // Create a new club with all non-mandatory fields
        $club = Club::create($request->except('opening_hours'));

        // Add opening hours if provided
        if ($request->has('opening_hours')) {
            foreach ($request->input('opening_hours') as $openingHour) {
                $club->openingHours()->create([
                    'day' => $openingHour['day'],
                    'open_time' => $openingHour['open_time'],
                    'close_time' => $openingHour['close_time'],
                ]);
            }
        }

        // Return a response or redirect as needed
        return response()->json(['message' => 'Club created successfully', 'club' => $club], 201);
    }

    public function searchClubs(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'query' => 'required|string',
        ]);

        // Sanitize the input
        $query = trim($request->input('query'));

        // Fetch clubs based on the club name or address
        $clubs = Club::where('name', 'like', '%' . $request->input('query') . '%')
            ->orWhere('address', 'like', '%' . $request->input('query') . '%')
            ->select('id', 'name', 'image', 'price_per_hour')
            ->get();

        // Store recent search for the authenticated user
        if (Auth::check()) {
            /** @var User|null $user */
            $user = Auth::user();
            $user->recentSearches()->create(['query' => $query]);
        }

        // Return the response
        return response()->json(['data' => $clubs], 200);
    }

    public function searchClubsByLocation(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        // Default range in Km
        $rangeInKm = 5;

        $clubs = Club::selectRaw("
        *,
        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))
        AS distance
    ", [$request->input('latitude'), $request->input('longitude'), $request->input('latitude')])
            ->having('distance', '<=', $rangeInKm)
            ->orderBy('distance')
            ->get();


        // Return the response
        return response()->json(['data' => $clubs], 200);
    }

    public function getClubDetails(Request $request, $id)
    {

        // Fetch the club based on the provided ID with its opening hours
        $club = Club::with('openingHours')->find($id);

        if (!$club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        // Return the response with club details and opening hours
        return response()->json(['data' => $club], 200);
    }


    public function getRecentSearches()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $recentSearches = $user->recentSearches()->latest()->get();

        return response()->json(['data' => $recentSearches], 200);
    }

    public function getTimeSlots($clubId)
    {
        $club = Club::findOrFail($clubId);
        $currentDate = now()->toDateString();

        $allTimeSlots = TimeSlot::all();



        // Fetch booked time slots
        $bookedTimeSlots = Booking::where('club_id', $clubId)
            ->whereDate('booking_date', $currentDate)
            ->with('user') // Include the user relationship
            ->pluck('time_slot_id')
            ->toArray();

        $response = [];
        foreach ($allTimeSlots as $timeSlot) {
            $isSlotAvailable = !in_array($timeSlot->id, $bookedTimeSlots);
            $booking = Booking::where('club_id', $clubId)
                ->whereDate('booking_date', $currentDate)->first();
            $user_profile = $booking ?  $booking->user->profile : null;

            $response[] = [
                'id' => $timeSlot->id,
                'slot_time' => $timeSlot->slot_time,
                'is_slot_available' => $isSlotAvailable,
                'user_profile' => $user_profile,
            ];
        }

        // Return the available time slots in JSON format
        return response()->json(['data' => $response], 200);
    }


    public function bookClub(Request $request)
    {
        $request->validate([
            'club_id' => 'required|exists:clubs,id',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        $user = Auth::user();
        $clubId = $request->input('club_id');
        $timeSlotId = $request->input('time_slot_id');
        $currentDate = Carbon::today()->toDateString();

        // Check if the time slot is available
        $isSlotAvailable = Booking::where('club_id', $clubId)
            ->where('time_slot_id', $timeSlotId)
            ->whereDate('booking_date', $currentDate) // Add the booking_date check
            ->doesntExist();

        if (!$isSlotAvailable) {
            return response()->json(['error' => 'The selected time slot is not available.'], 400);
        }


        // Book the club
        $booking = Booking::create([
            'user_id' => $user->id,
            'club_id' => $clubId,
            'time_slot_id' => $timeSlotId,
            'booking_date' => $currentDate,
        ]);

        return response()->json(['data' => $booking], 200);
    }
}
