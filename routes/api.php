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


Route::post('/user/register', [UserController::class, 'register']);
Route::post('/user/login', [UserController::class, 'login']);
Route::post('/user/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/email/verify/{id}', [VerificationController::class, 'verify'])->name('verification.verify');

Route::post('assign/plan', [UserController::class, 'assignPlan']);

Route::post('/campaign/create', [CampaignController::class, 'createCampaign']);
Route::get('/campaign/{code}', [CampaignController::class, 'getCampaignByCode']);
Route::post('/campaign/{code}/donate', [DonationController::class, 'donations']);
Route::get('/campaign/{code}/donation', [DonationController::class, 'getDonationByCampaign']);
