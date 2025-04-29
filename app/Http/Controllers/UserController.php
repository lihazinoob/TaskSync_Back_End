<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function fetchAlltheUserData()
    {
        $users = User::select('id','name','profile_picture')->get();
        return response()->json([
            'message' => 'All the User Data have been retrieved successfully',
            'users' => $users
        ]);
    }
}
