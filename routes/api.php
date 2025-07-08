<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and assigned to the "api"
| middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->group(function () {
  Route::post('/register', [AuthController::class, 'register']);
  Route::post('/login', [AuthController::class, 'login']);
  Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail']);
  Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink']);
  Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->group(function () {
  Route::prefix('auth')->group(function () {
    Route::get('/profile', [AuthController::class, 'Profile']);
    Route::get('/logout', [AuthController::class, 'logout']);
  });

  Route::prefix('team')->group(function() {
    Route::get('/allTeams', [TeamController::class, 'index']);
    Route::post('/create-team', [TeamController::class, 'store']);
    Route::get('/show-team/{team}', [TeamController::class, 'show']);
    Route::put('/update-team/{team}', [TeamController::class, 'update']);
    Route::delete('/delete-team/{team}', [TeamController::class, 'destroy']);
    Route::post('/{team}/members', [TeamController::class, 'addMember']);
    Route::delete('/{team}/members/{user}', [TeamController::class, 'removeMember']);
  });

  Route::prefix('tasks')->group(function () {
    Route::post('/create-task', [TaskController::class, 'store']);
    Route::get('/myTask', [TaskController::class, 'index']);
    Route::get('/show-task/{task:uuid}', [TaskController::class, 'show']);
    Route::delete('/delete/{task:uuid}', [TaskController::class, 'destroy']);
    Route::patch('/{task:uuid}/status', [TaskController::class, 'updateStatus']);
    Route::put('/update/{task:uuid}', [TaskController::class, 'update']);
  });
});
