<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/refresh',[AuthController::class,'generateAccessTokenFromRefreshToken']);
Route::post('/login',[AuthController::class,'login']);

Route::get('/auth/github',[AuthController::class,'redirectToGithub']);
Route::get('/auth/github/callback',[AuthController::class,'handleGitHubCallBack']);

// API for fetching all the user data.Works fine and tested
Route::get("/allUserData",[UserController::class,'fetchAlltheUserData']);


Route::middleware('auth:api')->group(function(){

  // API for fetching the user data after login or registration.Works Successfully
  Route::get('/userData',[AuthController::class,'fetchUserData']);
  

  // API for creating a project. This Successfully works
  Route::post('/createProject',[ProjectController::class,'projectCreation']);
  // API for getting all the projects the user has created
  Route::get('/projects',[ProjectController::class,'getProjects']);
  // API for creating a Task under a project
  Route::post('/projects/{project}/tasks',[TaskController::class,'createTask']);
  // API for creating a subTask under a Task
  Route::post('/tasks/{task}/subtasks',[SubtaskController::class,'createSubTask']);
  // API for updating the subtask
  Route::patch('/subtasks/{subtask}',[SubtaskController::class,'updateSubTask']);
  // API for syncing the projects 
  Route::post('/projects/sync',[ProjectController::class,'syncProjects']);

  

});