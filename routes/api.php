<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(UserController::class)->group(function () {
    Route::post('/user/register', 'register');
    Route::post('/user/login', 'login');
});
Route::get('/public/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');

Route::middleware(['auth:sanctum', 'isAuth'])->group(function () {

    Route::controller(UserController::class)->group(function () {
        Route::post('/user/logout', 'logout');
        Route::post('assign/plan', 'assignPlan');
    });

    Route::post('/campaign/create', [CampaignController::class, 'createCampaign']);

    Route::controller(DonationController::class)->group(function () {
        Route::post('campaign/{code}/donate', 'donations');
        Route::get('campaign/{code}/donation', 'getDonationByCampaign');
        Route::get('/account/donation', 'getDonationByAccount');
    });
});

Route::get('/campaign/{code}', [CampaignController::class, 'getCampaignByCode']);
