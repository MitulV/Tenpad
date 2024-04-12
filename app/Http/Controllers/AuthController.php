<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordOtpMail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Response;


class AuthController extends Controller
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
            'password' => bcrypt($request->input('password')),
            'user_type' => 'User',
        ]);

        $SECRET = env("SENCTUM_SECRET", "APP_SECRET");
        $token = $user->createToken($SECRET)->plainTextToken;

        return response()->json([
            'message' => 'User created Successfully',
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

        $user = User::where('email', $request->email)->with('profile')->first();

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

    public function logout(Request $request)
    {
        /** @var User|null $user */
        $user = auth()->user();
        
        $user->tokens()->delete();

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'action' => ['required'],
            'email' => ['required', 'email']
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($request->action == 'send-otp') {
            $otp = $this->generateAndStoreOTP($user->id, $request->email);
            Mail::to($request->email)->send(new ForgotPasswordOtpMail($otp));

            return response()->json(['message' => 'OTP Sent Successfully'], 200);
        } else if ($request->action == 'validate-otp') {
            $request->validate([
                'otp'  => ['required']
            ]);
            if ($this->validateOTP($user->id, $request->email, $request->otp)) {
                return response()->json(['message' => 'Valid OTP'], 200);
            } else {
                return response()->json(['error' => 'Invalid OTP'], 400);
            }
        } else if ($request->action == 'reset-password') {
            $request->validate([
                'password' => ['required'],
                'otp'  => ['required']
            ]);

            if ($this->validateOTP($user->id, $request->email, $request->otp)) {
                $user->update($request->only('password'));
                return response()->json(['message' => 'Password updated successfully'], 200);
            } else {
                return response()->json(['error' => 'Invalid OTP'], 400);
            }
        } else {
            return response()->json(['error' => 'Invalid Action'], 400);
        }
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => ['required']
        ]);

        /** @var User|null $user */
        $user = auth()->user();

        $user->update($request->only('password'));

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }

    public function generateAndStoreOTP($userId, $email)
    {
        // Generate a 4-digit OTP
        $otp = random_int(1000, 9999);

        OtpVerification::create([
            'user_id' => $userId,
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5), // OTP valid for 5 minutes
        ]);

        return $otp;
    }

    public function validateOTP($userId, $email, $otp)
    {
        // Retrieve the stored OTP for the user
        $storedOTP = OtpVerification::where('user_id', $userId)
            ->where('email', $email)
            ->where('expires_at', '>', now()) // Check if OTP is still valid
            ->latest()
            ->value('otp');

        // Compare the provided OTP with the stored OTP
        return $storedOTP == $otp;
    }
}
