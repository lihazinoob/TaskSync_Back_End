<?php

use App\Http\Controllers\AuthController;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Route;

Route::get("test",function()
{
  return view("welcome");
});
Route::post('register', [AuthController::class, 'register'])->name('register');