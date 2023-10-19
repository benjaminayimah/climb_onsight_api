<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\ForgotPasswordController;
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

Route::resource('events', EventController::class);

Route::post('/register-climber', [SignUpController::class, 'registerClimber']);
Route::post('/register-guide', [SignUpController::class, 'registerGuide']);
Route::post('/verify-account-email', [SignUpController::class, 'VerifyAccount']);
Route::post('/sign-in', [AuthController::class, 'store']);
Route::post('/update-password', [AuthController::class, 'UpadatePassword']);
Route::post('/temp-pdf-upload', [ImageController::class, 'tempPDFUpload']);
Route::post('/delete-uploaded-file', [ImageController::class, 'deleteUploadedFIle']);
Route::post('/set-temp-update', [ImageController::class, 'setTempUpdate']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
Route::post('/reset-password', [ForgotPasswordController::class, 'ResetPassword']);
Route::put('/accept-guide/{id}', [SignUpController::class, 'AcceptGuide']);
Route::put('/decline-guide/{id}', [SignUpController::class, 'DeclineGuide']);
Route::post('/create-guide-login', [SignUpController::class, 'CreateGuideLogin']);



Route::middleware(['auth:api'])->group(function () {
    // Define your API resource routes here
    Route::apiResources([
        'user' => UserController::class,
        'auth-user' => AuthController::class,
        'event' => EventController::class,
    ]);
    Route::delete('logout', [AuthController::class, 'destroy']);

    Route::post('temp-image-upload', [ImageController::class, 'tempUpload']);
    Route::delete('delete-temp-image', [ImageController::class, 'deleteTempImage']);


    
});

