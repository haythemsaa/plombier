<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:client,provider',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'language' => 'nullable|in:ar,fr',
            // Provider specific fields
            'cin' => 'required_if:type,provider|string|max:20',
            'years_experience' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create user
            $user = User::create([
                'type' => $request->type,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => $request->password,
                'language' => $request->language ?? 'fr',
                'status' => 'active',
            ]);

            // Create client or provider profile
            if ($request->type === 'client') {
                Client::create([
                    'user_id' => $user->id,
                    'subscription_type' => 'free',
                    'loyalty_points' => 0,
                ]);
            } else {
                Provider::create([
                    'user_id' => $user->id,
                    'cin' => $request->cin,
                    'years_experience' => $request->years_experience,
                    'status' => 'pending', // Pending verification
                    'subscription_type' => 'starter',
                ]);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Registration successful',
                'user' => $user->load($request->type),
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Account is not active'
            ], 403);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Generate new token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load relationship based on user type
        if ($user->isClient()) {
            $user->load('client');
        } elseif ($user->isProvider()) {
            $user->load('provider');
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();

        if ($user->isClient()) {
            $user->load('client', 'addresses');
        } elseif ($user->isProvider()) {
            $user->load('provider.services', 'provider.zones');
        }

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'avatar_url' => 'nullable|string|max:500',
            'language' => 'nullable|in:ar,fr',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only([
            'first_name', 'last_name', 'email', 'avatar_url', 'language'
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Verify phone number
     */
    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Implement actual SMS verification logic
        // For now, accept any 6-digit code
        if (strlen($request->code) !== 6) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->update([
            'phone_verified_at' => now()
        ]);

        return response()->json([
            'message' => 'Phone verified successfully'
        ]);
    }
}
