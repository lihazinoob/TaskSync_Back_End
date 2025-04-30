<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use Exception;
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

        foreach ($validatedData['projects'] as $projectData) {
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
            ]
        );
    }

    // function for inviting a user to a project
    public function inviteUser(Request $request, Project $project)
    {
        try {
            $user = Auth::user();
            // Checking if the user actually exist
            // and more importantly checking if it is any user other than project creator. 
            if (!$user || $project->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            // Checking if the is already assigned or invited
            if ($project->users()->where('user_id', $validatedData['user_id'])->exists()) {
                return response()->json([
                    'message' => 'This User has been already invited or assigned'
                ], 400);
            }

            // Add user to project_users table  with pending status
            $project->users()->attach($validatedData['user_id'], ['status' => 'pending']);

            // Creating a notification for the invitation
            Notification::create([
                'user_id' => $validatedData['user_id'],
                'project_id' => $project->id,
                'type' => 'invitation',
                'message' => "{$user->name} has invited you to join the project '{$project->name}'",
            ]);

            return response()->json([
                'message' => 'Invitation is sent successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }
    }

    // function for accepted the sent invitation
    public function acceptInvitation(Request $request)
    {
        try {
            $user = Auth::user();

            // validate the request data
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'project_id' => 'required|exists:projects,id'
            ]);

            // Find the project related data from the Project table
            $project = Project::findOrFail($validatedData['project_id']);
            $pivot = $project->users()->where('user_id', $validatedData['user_id'])->first();

            // Check if the record exists
            if (!$pivot) {
                return response()->json([
                    'message' => 'No invitation found for this user and project'
                ], 404);
            }

            // Check if the status is pending
            if ($pivot->pivot->status !== 'pending') {
                return response()->json([
                    'message' => 'Invitation is not pending'
                ], 400);
            }

            // Update the status to accepted
            $project->users()->updateExistingPivot($validatedData['user_id'], ['status' => 'accepted']);

            //Optionally, mark the related notification as read
            Notification::where('user_id', $user->id)
                ->where('project_id', $validatedData['project_id'])
                ->where('type', 'invitation')
                ->update(['read' => true]);

            return response()->json([
                'message' => 'Invitation accepted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to accept invitation: ' . $e->getMessage()
            ], 500);
        }
    }
}
