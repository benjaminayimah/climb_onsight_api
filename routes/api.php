<?php

use App\Http\Controllers\API\AdminsController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\ImageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SignUpController;
use App\Http\Controllers\API\StripeController;
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
Route::post('/delete-gallery-image', [ImageController::class, 'deleteGalleryImage']);
Route::post('/set-temp-update', [ImageController::class, 'setTempUpdate']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
Route::post('/reset-password', [ForgotPasswordController::class, 'ResetPassword']);
Route::put('/accept-guide/{id}', [SignUpController::class, 'AcceptGuide']);
Route::put('/decline-guide/{id}', [SignUpController::class, 'DeclineGuide']);
Route::post('/create-guide-login', [SignUpController::class, 'CreateGuideLogin']);
Route::get('/get-this-guide/{id}', [EventController::class, 'GetThisGuide']);
Route::post('/search-events/{query}', [EventController::class, 'SearchEvents']);
Route::post('/get-nearby-events', [EventController::class, 'GetNearByEvents']);
Route::post('/cos-connect-whs', [BookingController::class, 'ConnectWebHooks']);
Route::post('/complete-booking', [BookingController::class, 'CompleteBooking']);
Route::post('/cancel-booking', [BookingController::class, 'CancelBooking']);








// 




Route::middleware(['auth:api'])->group(function () {
    // Define your API resource routes here
    Route::apiResources([
        'user' => UserController::class,
        'auth-user' => AuthController::class,
        'event' => EventController::class,
        'sub-admins' => AdminsController::class,
        'stripe' => StripeController::class
    ]);
    Route::post('/prebook-event/{id}', [BookingController::class, 'PreBookEvent']);
    Route::post('/accept-booking/{id}', [BookingController::class, 'AcceptBooking']);
    Route::post('/decline-booking/{id}', [BookingController::class, 'DeclineBooking']);
    Route::post('/attempt-payment/{id}', [BookingController::class, 'AttemptPayment']);
    Route::post('/get-booking-count/{id}', [BookingController::class, 'GetBookingCount']);
    Route::post('/update-permissions/{id}', [AdminsController::class, 'UpdatePermissions']);
    Route::delete('/delete-user/{id}', [AuthController::class, 'DeleteUser']);
    Route::put('/reset-admin-password/{id}', [AdminsController::class, 'ChangeAdminPassword']);
    Route::post('/get-this-stripe/{stripe_id}', [StripeController::class, 'getThisStripe']);
    Route::post('/finish-onboarding', [StripeController::class, 'FinishOnboarding']);
    Route::post('/goto-stripe-dashboard/{stripe_id}', [StripeController::class, 'GoToStripeDashboard']);

    


    


    

    


    


    


    
    Route::delete('logout', [AuthController::class, 'destroy']);
    
    Route::post('/temp-image-upload', [ImageController::class, 'tempUpload']);
    Route::delete('/delete-temp-image', [ImageController::class, 'deleteTempImage']);



    
});

