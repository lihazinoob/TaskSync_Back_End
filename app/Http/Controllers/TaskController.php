<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskController extends Controller
{
    // Function for creating a Task under a Project
    public function createTask(Request $request, Project $project)
    {
        // Getting the user via access Token
        $user = JWTAuth::user();
        if (!$user || $project->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Validate the Incoming data from the frontend

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:Todo,In Progress,Completed',
            'description' => 'nullable|string',
            'created_by' => 'required|string',
            'assignees' => 'nullable|array',
            'assignees.*' => 'string|max:255',
            'timeline.start' => 'required|date',
            'timeline.end' => 'required|date|after_or_equal:timeline.start',
            'status' => 'required|string|max:255',
        ]);

        // Create a task and store it into the database
        $task = $project->tasks()->create([
            'title' => $validatedData['title'],
            'category' => $validatedData['category'],
            'description' => $validatedData['description'],
            'created_by' => $validatedData['created_by'],
            'assignees' => $validatedData['assignees'] ?? [],
            'timeline' => [
                'start' => $validatedData['timeline']['start'],
                'end' => $validatedData['timeline']['end'],
            ],
            'status' => $validatedData['status'],
        ]);

        return response()->json([
            'message' => 'Task has been created Successfully',
            // Here is also eager loading the subtasks data to optimize the query
            'data' => $task->load('subtasks')
        ]);
    }
}
