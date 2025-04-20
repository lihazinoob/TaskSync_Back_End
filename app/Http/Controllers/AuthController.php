<?php

namespace App\Http\Controllers;

use App\Models\User;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    // Function for registering a new user
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
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(), // Remove in production
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email|max:256',
                'password' => 'required|string|min:8'
            ]);
            $credentials = $request->only('email', 'password');
            $accessToken = JWTAuth::attempt($credentials);
            if (!$accessToken) {
                return response()->json([
                    'error' => 'Invalid Credentials',
                    'message' => 'The provided credentials are not correct'
                ], 401);
            }

            $user = Auth::user();
            $refreshToken = JWTAuth::fromUser($user);
            return response()->json([
                'access_token' => $accessToken,
                'message' => 'Registration is Successful'
            ], 201)
                ->cookie('refresh_token', $refreshToken, 43200, null, null, true, true, false, 'Strict');
        } catch (ValidationException $validationException) {
            return response()->json([
                'error' => 'Validation Failed',
                'messages' => $validationException->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(), // Remove in production
            ], 500);
        }
    }

    // Function for generating a new access token from the refresh token stored in the cookie
    public function generateAccessTokenFromRefreshToken(Request $request)
    {
        try {
            // Fetching the refre3sh token from the cookie
            $refreshToken = $request->cookie('refresh_token');
            if (!$refreshToken) {
                return response()->json([
                    'error' => 'There is no refresh token'
                ], 401);
            }
            JWTAuth::setToken($refreshToken);
            $user = JWTAuth::toUser($refreshToken);
            $newAccessToken = JWTAuth::fromUser($user);
            return response()->json([
                'access_token' => $newAccessToken,
                'message' => 'Token Defined',
            ]);
        } catch (JWTException $jWTException) {
            return response()->json([
                'error' => 'Could not Refresh the access tokem from the refresh token',
            ], 401);
        }
    }
}
