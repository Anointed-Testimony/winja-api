<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpVerification;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'age_group' => 'nullable|string',
            'geo_location' => 'nullable|string',
            'academic_level' => 'nullable|string',
            'interests' => 'nullable|array',
            'notification_preferences' => 'nullable|array',
            'referred_by' => 'nullable|string|exists:users,referral_code',
        ]);

        // Generate a unique referral code
        do {
            $referralCode = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (User::where('referral_code', $referralCode)->exists());

        // Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'age_group' => $validated['age_group'],
            'geo_location' => $validated['geo_location'],
            'academic_level' => $validated['academic_level'],
            'interests' => $validated['interests'],
            'notification_preferences' => $validated['notification_preferences'],
            'referral_code' => $referralCode,
            'referred_by' => $validated['referred_by'] ?? null,
        ]);

        // If user was referred, create a referral record
        if (isset($validated['referred_by'])) {
            $referrer = User::where('referral_code', $validated['referred_by'])->first();
            if ($referrer) {
                \App\Models\Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                    'status' => 'pending',
                ]);

                // Check for new badges for the referrer
                $referrer->checkAndAwardBadges();
            }
        }

        // Generate and send OTP
        $otp = $this->generateAndSendOtp($user);

        // Log the registration
        ActivityLog::create([
            'type' => 'user_registered',
            'description' => 'New user registered: ' . $user->name,
            'user_id' => $user->id,
            'metadata' => [
                'email' => $user->email,
                'referred_by' => $validated['referred_by'] ?? null,
            ]
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful. Please check your email for OTP verification.'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        
        $otp = $user->otps()
            ->where('code', $validated['otp'])
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json([
                'message' => 'Invalid or expired OTP'
            ], 422);
        }

        // Mark OTP as used
        $otp->update(['is_used' => true]);

        // Mark user's email as verified
        $user->update(['email_verified_at' => now()]);

        // Log the verification
        ActivityLog::create([
            'type' => 'email_verified',
            'description' => 'User email verified: ' . $user->name,
            'user_id' => $user->id,
            'metadata' => [
                'email' => $user->email
            ]
        ]);

        return response()->json([
            'message' => 'Email verified successfully',
            'verified' => true
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        // Generate and send new OTP
        $otp = $this->generateAndSendOtp($user);

        return response()->json([
            'message' => 'OTP resent successfully'
        ]);
    }

    private function generateAndSendOtp($user)
    {
        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Create OTP record
        $otpRecord = $user->otps()->create([
            'code' => $otp,
            'expires_at' => now()->addMinutes(10), // OTP expires in 10 minutes
        ]);

        // Send OTP via email
        Mail::to($user->email)->send(new OtpVerification($user, $otp));

        return $otpRecord;
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Log the login
        ActivityLog::create([
            'type' => 'user_login',
            'description' => 'User logged in: ' . $user->name,
            'user_id' => $user->id,
            'metadata' => [
                'email' => $user->email
            ]
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
} 
 