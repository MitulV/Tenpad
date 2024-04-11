<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\Club;
use App\Models\Court;
use App\Models\OpeningHours;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

        // Retrieve all courts for the club
        $courts = Court::where('club_id', $clubId)->get();

        // Initialize an array to store time slots for each court
        $timeSlots = [];

        // Loop through each court
        foreach ($courts as $court) {
            // Initialize start time and end time
            $startTime = Carbon::parse($openingHours->open_time);
            $endTime = Carbon::parse($openingHours->close_time);

            while ($startTime < $endTime) {
                // Calculate end time for the current time slot
                $endTimeSlot = clone $startTime;
                $endTimeSlot->addMinutes($club->slot_duration);

                $isSlotAvailable = !Booking::where('club_id', $clubId)
                    ->where('court_id', $court->id)
                    ->where('booking_date', $date)
                    ->where(function ($query) use ($startTime, $endTimeSlot, $endTime) {
                        $query->where(function ($q) use ($startTime, $endTimeSlot) {
                            $q->where('start_time', '>=', $startTime)
                                ->where('start_time', '<', $endTimeSlot);
                        })->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('end_time', '>', $startTime)
                                ->where('end_time', '<=', $endTime);
                        })->orWhere(function ($q) use ($startTime, $endTimeSlot) {
                            $q->where('start_time', '<', $startTime)
                                ->where('end_time', '>', $endTimeSlot);
                        });
                    })
                    ->exists();

                // Retrieve booking object and user if the slot is not available
                $booking = null;
                $user = null;
                if (!$isSlotAvailable) {
                    $booking = Booking::where('club_id', $clubId)
                        ->where('court_id', $court->id)
                        ->where('booking_date', $date)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTimeSlot)
                        ->first();

                    if ($booking) {
                        $user = $booking->user()->with('profile')->first();
                    }
                }

                // Add the time slot details to the array
                $timeSlots[$court->id][] = [
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $endTimeSlot->format('H:i'),
                    'is_available' => $isSlotAvailable,
                    'booking' => $isSlotAvailable ? null : [
                        'user' => $user,
                        'booking_info' => $booking,
                    ],
                ];

                // Move to the next time slot
                $startTime = $endTimeSlot;
            }
        }

        // Return the time slots for all courts
        return response()->json(['data' => $timeSlots], 200);
    }

    public function bookClub(Request $request)
    {
        $request->validate([
            'club_id' => 'required|exists:clubs,id',
            'court_id' => 'required|exists:courts,id', // Add validation for court_id
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ]);

        $user = Auth::user();
        $clubId = $request->input('club_id');
        $courtId = $request->input('court_id'); // Retrieve court_id from the request
        $date = $request->input('date');
        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = Carbon::parse($request->input('end_time'));

        // Check if the time slot falls within the club's opening hours
        $openingHours = OpeningHours::where('club_id', $clubId)
            ->where('day', Carbon::parse($date)->englishDayOfWeek)
            ->first();

        if (!$openingHours) {
            return response()->json(['error' => 'Club is closed on this day'], 400);
        }

        if ($startTime->lt(Carbon::parse($openingHours->open_time)) || $endTime->gt(Carbon::parse($openingHours->close_time))) {
            return response()->json(['error' => 'Booking time is outside opening hours'], 400);
        }

        // Check if the time slot is available for the specified court
        $isSlotAvailable = !Booking::where('club_id', $clubId)
            ->where('court_id', $courtId) // Check availability for the specified court
            ->where('booking_date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '>=', $startTime)
                        ->where('start_time', '<', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('end_time', '>', $startTime)
                        ->where('end_time', '<=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $startTime)
                        ->where('end_time', '>', $endTime);
                });
            })
            ->exists();

        if (!$isSlotAvailable) {
            return response()->json(['error' => 'The selected time slot is not available for the specified court.'], 400);
        }

        // Book the court
        $booking = Booking::create([
            'user_id' => $user->id,
            'club_id' => $clubId,
            'court_id' => $courtId, // Save the court_id in the booking
            'start_time' => $startTime,
            'end_time' => $endTime,
            'booking_date' => $date,
        ]);

        return response()->json(['data' => $booking], 200);
    }
}
