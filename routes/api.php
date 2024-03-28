<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClubBookingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('signup', [AuthController::class, 'signup']);
Route::post('signin', [AuthController::class, 'signin']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/user-profile/create', [UserController::class, 'createUserProfile']);
    Route::get('/user-profile', [UserController::class, 'getUserProfile']);

    Route::post('change-password', [AuthController::class, 'changePassword']);

    Route::post('/clubs/create', [ClubBookingController::class, 'create']);
    Route::get('/clubs/search', [ClubBookingController::class, 'searchClubs']);
    Route::get('/clubs/search-by-location', [ClubBookingController::class, 'searchClubsByLocation']);
    Route::get('/clubs/{id}/details', [ClubBookingController::class, 'getClubDetails']);
    Route::get('/recent-searches', [ClubBookingController::class, 'getRecentSearches']);
    Route::get('/time-slots', [ClubBookingController::class, 'getTimeSlots']);

    Route::post('/book-club', [ClubBookingController::class, 'bookClub']);
});
