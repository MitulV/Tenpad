<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\Club;
use App\Models\Court;
use App\Models\OpeningHours;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{

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

    public function getTimeSlots($clubId, $date)
    {
        if (!Carbon::createFromFormat('Y-m-d', $date)) {
            return response()->json(['error' => 'Invalid date format.'], 400);
        }

        $club = Club::findOrFail($clubId);

        // Retrieve the opening hours for the club on the given date
        $openingHours = OpeningHours::where('club_id', $clubId)
            ->where('day', Carbon::parse($date)->englishDayOfWeek)
            ->first();

        // If no opening hours found, return error
        if (!$openingHours) {
            return response()->json(['error' => 'Club is closed on this day'], 400);
        }

        // Initialize start time and end time
        $startTime = Carbon::parse($openingHours->open_time);
        $endTime = Carbon::parse($openingHours->close_time);

        // Initialize an array to store time slots
        $timeSlots = [];

        // Loop through time slots and collect available slots
        while ($startTime < $endTime) {
            // Calculate end time for the current time slot
            $endTimeSlot = clone $startTime;
            $endTimeSlot->addMinutes($club->slot_duration);

            // Determine the time slot period (Morning, Afternoon, or Evening)
            $timeSlotPeriod = $this->getTimeSlotPeriod($startTime);

            // If the time slot period is not yet added, initialize it
            if (!isset($timeSlots[$timeSlotPeriod])) {
                $timeSlots[$timeSlotPeriod] = [];
            }

            // Retrieve all courts for the club
            $courts = Court::where('club_id', $clubId)->get();

            // Initialize array to store courts for the current time slot
            $timeSlotCourts = [];

            // Loop through each court and check availability
            foreach ($courts as $court) {
                // Check if the slot is available
                $isSlotAvailable = !$this->isSlotBooked($clubId, $court->id, $date, $startTime, $endTimeSlot);

                Log::info('Slot availability: ' . ($isSlotAvailable ? 'Available' : 'Not Available'));

                // Retrieve booking object and user if the slot is not available
                $booking = null;
                $user = null;
                if (!$isSlotAvailable) {
                    Log::info('Slot is not available. Retrieving booking object and user.');
                    Log::info('club_id-'.$clubId);
                    Log::info('court_id-'.$court->id);
                    Log::info('booking_date'.$date);
                    Log::info('start_time'.$startTime);
                    Log::info('end_time'.$endTimeSlot);
                    $booking = Booking::where('club_id', $clubId)
                        ->where('court_id', $court->id)
                        ->where('booking_date', $date)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTimeSlot)
                        ->first();

                        Log::info('Generated SQL Query: ' . Booking::where('club_id', $clubId)
                        ->where('court_id', $court->id)
                        ->where('booking_date', $date)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTimeSlot)
                        ->toSql());    

                    if ($booking) {
                        Log::info('Booking object: ' . json_encode($booking));
                        $user = $booking->user()->with('profile')->first();
                        Log::info('User: ' . json_encode($user));
                    }
                }

                // Add court details to the time slot courts array
                $timeSlotCourts[] = [
                    'court' => $court->toArray(),
                    'is_available' => $isSlotAvailable,
                    'booking' => $isSlotAvailable ? null : [
                        'user' => $user,
                        'booking_info' => $booking,
                    ],
                ];
            }

            // Add time slot details to the time slots array
            $timeSlots[$timeSlotPeriod][] = [
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTimeSlot->format('H:i'),
                'courts' => $timeSlotCourts,
            ];

            // Move to the next time slot
            $startTime = $endTimeSlot;
        }

        // Return the time slots for all courts
        return response()->json(['data' => $timeSlots], 200);
    }



    // Helper function to check if the slot is booked
    private function isSlotBooked($clubId, $courtId, $date, $startTime, $endTimeSlot)
    {
        return Booking::where('club_id', $clubId)
            ->where('court_id', $courtId)
            ->where('booking_date', $date)
            ->where(function ($query) use ($startTime, $endTimeSlot) {
                $query->where(function ($q) use ($startTime, $endTimeSlot) {
                    $q->where('start_time', '>=', $startTime)
                        ->where('start_time', '<', $endTimeSlot);
                })->orWhere(function ($q) use ($startTime, $endTimeSlot) {
                    $q->where('end_time', '>', $startTime)
                        ->where('end_time', '<=', $endTimeSlot);
                })->orWhere(function ($q) use ($startTime, $endTimeSlot) {
                    $q->where('start_time', '<', $startTime)
                        ->where('end_time', '>', $endTimeSlot);
                });
            })
            ->exists();
    }

    // Helper function to determine the time slot period
    private function getTimeSlotPeriod($time)
    {
        $hour = $time->hour;
        if ($hour >= 6 && $hour < 12) {
            return 'Morning';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'Afternoon';
        } else {
            return 'Evening';
        }
    }


    public function bookClub(Request $request)
    {
        $request->validate([
            'club_id' => 'required|exists:clubs,id',
            'court_id' => 'required|exists:courts,id',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'match_type' => 'required|in:Competitive,Friendly',
            'play_with_gender' => 'required|in:"All players","Men only","Female only"',
            'match_visibility' => 'required|in:Public,Private',
        ]);

        $user = Auth::user();
        $clubId = $request->input('club_id');
        $courtId = $request->input('court_id');
        $date = $request->input('date');
        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = Carbon::parse($request->input('end_time'));

        // Check if the court belongs to the specified club
        $court = Court::where('id', $courtId)->where('club_id', $clubId)->first();
        if (!$court) {
            return response()->json(['error' => 'The specified court does not belong to the club.'], 400);
        }

        // Check if the time slot falls within the club's opening hours
        $openingHours = OpeningHours::where('club_id', $clubId)
            ->where('day', Carbon::parse($date)->englishDayOfWeek)
            ->first();

        if (!$openingHours) {
            return response()->json(['error' => 'Club is closed on this day'], 400);
        }

        if ($startTime->lt(Carbon::parse($openingHours->open_time)) || $endTime->gt(Carbon::parse($openingHours->close_time))) {
            return response()->json(['error' => 'Booking time is outside club opening hours (Open: ' . $openingHours->open_time . ', Close: ' . $openingHours->close_time . ').'], 400);
        }

        // Check if the booking time duration matches the club's slot_duration
        $club = Club::findOrFail($clubId);
        $slotDuration = $club->slot_duration;
        $bookingDuration = $startTime->diffInMinutes($endTime);

        if ($bookingDuration != $slotDuration) {
            return response()->json(['error' => 'Booking duration must match the club slot duration'], 400);
        }

        // Check if the time slot is available for the specified court
        $isSlotAvailable = !$this->isSlotBooked($clubId, $courtId, $date, $startTime, $endTime);

        if (!$isSlotAvailable) {
            return response()->json(['error' => 'The selected time slot is not available for the specified court.'], 400);
        }

        // Book the court
        $booking = Booking::create([
            'user_id' => $user->id,
            'club_id' => $clubId,
            'court_id' => $courtId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'booking_date' => $date,
            'match_type' => $request->input('match_type'),
            'play_with_gender' => $request->input('play_with_gender'),
            'match_visibility' => $request->input('match_visibility'),
        ]);


        return response()->json(['data' => $booking], 200);
    }
}
