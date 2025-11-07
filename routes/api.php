<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])->whereNumber('project');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->whereNumber('project');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->whereNumber('task');
    Route::post('/tasks/{task}/update', [TaskController::class, 'update'])->whereNumber('task');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->whereNumber('task');

});
