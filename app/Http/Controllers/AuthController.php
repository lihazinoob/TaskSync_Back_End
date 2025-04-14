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
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            // generate the access token
            $accesstoken = JWTAuth::fromUser($user);
            // generate the refresh token
            $refreshtoken = JWTAuth::fromUser($user, true);

            // Store the refresh token in HTTP only cookie

            return response()->json([
                'access_token' => $accesstoken,
                'message' => 'Registration is Successfull'
            ], 201)
            ->cookie('refresh_token', $refreshtoken, 43200, null, null, true, true, false, 'Strict');
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->getMessage(),
            ], 422);
        }
    }
}
