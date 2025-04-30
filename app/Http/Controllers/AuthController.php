<?php

namespace App\Http\Controllers;

use App\Models\User;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use PhpParser\Node\Stmt\TryCatch;

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
                'profile_picture' => $request->profile_picture
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
        } catch (Exception $e) {
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

    // Returns the GitHub OAuth URL for the frontend to redirect the user.
    public function redirectToGithub(Request $request)
    {
        Log::info("Inside The redirect top GitHub Function");
        try {
            $redirectUri = $request->query('redirect', 'http://localhost:5173/register');
            $state = $request->query('state');
            Log::info('Dynamic Redirect URI:', ['redirect' => $redirectUri, 'state' => $state]);

            $redirectUrl = Socialite::driver('github')
                ->scopes(['user:email'])
                ->stateless()
                ->redirectUrl($redirectUri)
                ->redirect()
                ->getTargetUrl();

            // Manually append the state parameter to the URL
            if ($state) {
                $redirectUrl .= (parse_url($redirectUrl, PHP_URL_QUERY) ? '&' : '?') . 'state=' . urlencode($state);
            }
            Log::info("After The Socialite Execution");
            return response()->json([
                'url' => $redirectUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('RedirectToGithub Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Failed to generate GitHub redirect URL',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function handleGitHubCallBack()
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();
            // Log::info('GitHub User Data:', (array)$githubUser);
            $user = User::where('email', $githubUser->email)->first();
            if ($user) {
                if (!$user->github_id) {
                    $user->update([
                        'github_id' => $githubUser->id
                    ]);
                }
            } else {
                $user = User::create([
                    'name' => $githubUser->name ?? $githubUser->nickname ?? 'user_' . Str::random(8),
                    'email' => $githubUser->email,
                    'github_id' => $githubUser->id,
                    'password' => bcrypt(Str::random(16)), // Random password for social login

                ]);

                $accessToken = JWTAuth::fromUser($user);
                $refreshToken = JWTAuth::fromUser($user);


                return response()->json([
                    'access_token' => $accessToken,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'message' => 'GitHub authentication successful',
                ])->cookie('refresh_token', $refreshToken, 43200, null, null, true, true, false, 'Strict');
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to authenticate with Github',
                'message' => $e->getMessage()
            ], 401);
        }
    }

    // Function for fething the user data after registration or log in
    public function fetchUserData()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => "This is not a valid user" 
                ], 500);
            }
            else
            {
                return response()->json([
                    'message' => "User Data successfully retrieved",
                    'userInfo' => $user
                ], 201);
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => $e->getMessage(), 
            ], 500);
        }
    }
}
