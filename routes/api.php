<?php

use App\Http\Controllers\SendMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Группа роутов, требующих авторизации
//Route::middleware('auth:sanctum')->group(function () {

    // Роут для отправки сообщения
Route::post('/messages', [SendMessageController::class, 'store']);

//});
