<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('signup', [AuthController::class, 'signup']);
Route::post('signin', [AuthController::class, 'signin']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/user-profile/create', [UserController::class, 'createUserProfile']);
    Route::get('/user-profile', [UserController::class, 'getUserProfile']);

    Route::post('change-password', [AuthController::class, 'changePassword']);

    Route::post('club/signup', [ClubController::class, 'signup']);
    Route::post('club/signin', [ClubController::class, 'signin']);
    Route::post('/clubs/create', [ClubController::class, 'create']);
    Route::get('/clubs/search', [ClubController::class, 'searchClubs']);
    Route::get('/clubs/search-by-location', [ClubController::class, 'searchClubsByLocation']);
    Route::get('/clubs/{clubId}/distance', [ClubController::class, 'calculateDistanceBetweenUserAndClub']);
    Route::get('/clubs/{id}/details', [ClubController::class, 'getClubDetails']);
    Route::get('/recent-searches', [BookingController::class, 'getRecentSearches']);
    Route::get('/clubs/{clubId}/time-slots/{date}', [BookingController::class, 'getTimeSlots']);
    Route::post('/book-club', [BookingController::class, 'bookClub']);
});
