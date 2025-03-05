<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('user-register');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/send-message', [ChatController::class, 'storeMessage']);
    Route::post('/messages', [ChatController::class, 'getMessages']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount']);
    Route::get('/users', function () {
        return response()->json(\App\Models\User::where('id', '!=', auth()->id())->get());
    });
    
});