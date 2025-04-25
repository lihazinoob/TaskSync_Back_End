<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    public function projectCreation(Request $request)
    {
        // The Request Data is validated First
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:Active,On Hold,Closed',
            'techStack' => 'required|string|max:255',
            'workType' => 'required|string|max:255'
        ]);
        // Generate the slack from the project name
        $slack = Str::lower(str_replace(' ', '', $validatedData['name']));


        // Get the authenmticated User
        $user = JWTAuth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }


        // Create the project
        $project = $user->projects()->create([
            'name' => $validatedData['name'],
            'category' => $validatedData['category'],
            'techStack' => $validatedData['techStack'],
            'workType' => $validatedData['workType'],
            'slack' => $slack,
        ]);




        // Then return the response
        return response()->json([
            'message' => 'Project Created Successfully',
            'data' => $project
        ], 201);
    }
}
