<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ImageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SignUpController;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register-climber', [SignUpController::class, 'registerClimber']);
Route::post('verify-account-email', [SignUpController::class, 'VerifyAccount']);
Route::post('sign-in', [AuthController::class, 'store']);



Route::middleware(['auth:api'])->group(function () {
    // Define your API resource routes here
    Route::apiResources([
        'user' => UserController::class
    ]);
    Route::delete('logout', [AuthController::class, 'destroy']);

    Route::post('temp-image-upload', [ImageController::class, 'tempUpload']);
    Route::delete('delete-temp-image', [ImageController::class, 'deleteTempImage']);


    
});

