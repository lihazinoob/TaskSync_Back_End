<?php

namespace App\Http\Controllers;

use App\Models\User;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'name' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $accesstoken = JWTAuth::fromUser($user);
            $refreshtoken = JWTAuth::fromUser($user); // Adjust if you have a proper refresh token mechanism

            return response()->json([
                'access_token' => $accesstoken,
                'message' => 'Registration is Successful'
            ], 201)
            ->cookie('refresh_token', $refreshtoken, 43200, null, null, true, true, false, 'Strict');
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(), // Remove in production
            ], 500);
        }
    }
}
