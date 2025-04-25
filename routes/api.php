<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/refresh',[AuthController::class,'generateAccessTokenFromRefreshToken']);
Route::post('/login',[AuthController::class,'login']);

Route::get('/auth/github',[AuthController::class,'redirectToGithub']);
Route::get('/auth/github/callback',[AuthController::class,'handleGitHubCallBack']);

// Project Creation API
Route::post('/createProject',[ProjectController::class,'projectCreation'])->middleware('auth:api');