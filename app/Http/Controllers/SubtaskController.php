<?php

namespace App\Http\Controllers;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class SubtaskController extends Controller
{
    // Function for creating a subTask under a task
    public function createSubTask(Request $request,Task $task)
    {
        // getting the user by the access Token
        $user = JWTAuth::user();
        if(!$user || $task->project->user_id !== $user->id)
        {
            return response()->json([
                'message' => 'UnAuthorized'
            ],403);
        }

        // validate the incoming data from the frontend
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $subTask = $task->subtasks()->create([
            'title' => $validatedData['title'],
            'completed' => false,
        ]); 

        return response()->json([
            'message' => 'SubTask has been created Successfully',
            'data' => $subTask
        ],201);
    }

    // Function for updating a SubTask
    public function updateSubTask(Request $request,Subtask $subtask)
    {
        // Getting the user via access Token
        $user = JWTAuth::user();
        if(!$user || $subtask->task->project->user_id !== $user->id)
        {
            return response()->json([
                'message' => 'UnAuthorized'
            ],403);
        }

        // Validating the data
        $validatedData = $request->validate(
            [
                'completed' => 'required|boolean'
            ]
        );

        $subtask->update([
            'completed' => $validatedData['completed']
        ]);

        return response()->json(
            [
                'message' => 'Subtask has been updates successfully',
                'data' => $subtask                
            ],201);
    }
}
