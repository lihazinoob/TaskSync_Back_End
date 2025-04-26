<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    // This function is for creating a project
    public function projectCreation(Request $request)
    {
        // The Request Data is validated First
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:Active,On Hold,Closed',
            'techStack' => 'required|string|max:255',
            'workType' => 'required|string|max:255'
        ]);
        // Generate the slack from the project name
        $slack = Str::lower(str_replace(' ', '', $validatedData['name']));


        // Get the authenticated User
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

    // This function is for fetching all the projects a user has created
    public function getProjects()
    {
        // Getting the user via the access Token
        $user = JWTAuth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Fetching the Projects of a user (The Projects a user has created)
        $projects = $user->projects()->with('tasks.subtasks')->get();  //Eager Loading is implemented to minimize queries by loading data upfront

        return response()->json([
            'message' => 'Projects have been retrieved Successfully',
            'data' => $projects
        ]);
    }

    // function for syncing projects
    public function syncProjects(Request $request)
    {
        // Getting the user via access Token
        $user = JWTAuth::user();
        if (!$user) {
            return response()->json(
                [
                    'message' => 'Unauthenticated'
                ],
                401
            );
        }

        // Validate the incoming data from the frontend
        $validatedData = $request->validate([
            'projects' => 'required|array',
            'projects.*.id' => 'required|exists:projects,id',
            'projects.*.tasks' => 'sometimes|array',
            'projects.*.tasks.*.id' => 'required|exists:tasks,id',
            'projects.*.tasks.*.subtasks' => 'sometimes|array',
            'projects.*.tasks.*.subtasks.*.id' => 'required|exists:subtasks,id',
            'projects.*.tasks.*.subtasks.*.completed' => 'required|boolean',
        ]);

        // Iterating over all project data and their subsequent task and subtask data

        foreach ($validatedData['projects'] as $projectData)
        {
            // Finding the Project according to the project ID
            $project = Project::find($projectData['id']);

            if ($project->user_id !== $user->id) {
                // Skip if the project does not belong to the user
                continue;
            }

            // Cheking if the tasks are set meaning if the tasks exist under a project
            if (isset($projectData['tasks'])) {
                foreach ($projectData['tasks'] as $taskData) {
                    $task = Task::find($taskData['id']);
                    if ($task->project_id !== $project->id) {
                        continue;
                    }
                    if (isset($taskData['subtasks'])) {
                        foreach ($taskData['subtasks'] as $subtaskData) {
                            $subtask = Subtask::find($subtaskData['id']);
                            if ($subtask->task_id !== $task->id) {
                                continue;
                            }
                            $subtask->update(['completed' => $subtaskData['completed']]);
                        }
                    }
                }
            }
        }

        $updatedProjects = $user->projects()->with('tasks.subtasks')->get();
        return response()->json(
            [
                'message' => 'Projects synced Successfully',
                'data' => $updatedProjects
            ]);
    }
}
