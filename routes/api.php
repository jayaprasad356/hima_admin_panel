<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthControllerApi;

 Route::post('login', [AuthController::class, 'login']);
 Route::post('register', [AuthController::class, 'register']);
 Route::post('customerdetails', [AuthController::class, 'customerdetails']);
 Route::post('update_profile', [AuthController::class, 'update_profile']);
 Route::post('coins_list', [AuthController::class, 'coins_list']);
 Route::post('avatar_list', [AuthController::class, 'avatar_list']);
 Route::post('transaction_list', [AuthController::class, 'transaction_list']);
 Route::post('send_otp', [AuthController::class, 'send_otp']);
 Route::post('settings_list', [AuthController::class, 'settings_list']);
 Route::post('delete_users', [AuthController::class, 'delete_users']);
 Route::post('user_validations', [AuthController::class, 'user_validations']);
 Route::post('speech_text', [AuthController::class, 'speech_text']);
 Route::post('update_voice', [AuthController::class, 'update_voice']);
 Route::post('female_users_list', [AuthController::class, 'female_users_list']);
 Route::post('withdrawals_list', [AuthController::class, 'withdrawals_list']);
 Route::post('calls_status_update', [AuthController::class, 'calls_status_update']);
 Route::post('random_user', [AuthController::class, 'random_user']);
 Route::post('update_connected_call', [AuthController::class, 'update_connected_call']);
 Route::post('calls_list', [AuthController::class, 'calls_list']);
 Route::post('call_female_user', [AuthController::class, 'call_female_user']);
 Route::post('female_call_attend', [AuthController::class, 'female_call_attend']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
],function($router){
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/userdetails', [AuthController::class, 'userdetails']);
     Route::post('/update_profile', [AuthController::class, 'update_profile']);
     Route::post('/coins_list', [AuthController::class, 'coins_list']);
     Route::post('/best_offers', [AuthController::class, 'best_offers']);
     Route::post('/avatar_list', [AuthController::class, 'avatar_list']);
     Route::post('transaction_list', [AuthController::class, 'transaction_list']);
     Route::post('send_otp', [AuthController::class, 'send_otp']);
     Route::post('settings_list', [AuthController::class, 'settings_list']);
     Route::post('appsettings_list', [AuthController::class, 'appsettings_list']);
     Route::post('delete_users', [AuthController::class, 'delete_users']);
    Route::post('user_validations', [AuthController::class, 'user_validations']);
    Route::post('speech_text', [AuthController::class, 'speech_text']);
    Route::post('update_voice', [AuthController::class, 'update_voice']);
    Route::post('female_users_list', [AuthController::class, 'female_users_list']);
    Route::post('withdrawals_list', [AuthController::class, 'withdrawals_list']);
    Route::post('calls_status_update', [AuthController::class, 'calls_status_update']);
    Route::post('random_user', [AuthController::class, 'random_user']);
    Route::post('update_connected_call', [AuthController::class, 'update_connected_call']);
    Route::post('calls_list', [AuthController::class, 'calls_list']);
    Route::post('call_female_user', [AuthController::class, 'call_female_user']);
    Route::post('female_call_attend', [AuthController::class, 'female_call_attend']);
    Route::post('reports', [AuthController::class, 'reports']);
});
