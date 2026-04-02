<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::post('/room/create', 'createRoom')->name('room.create');
    Route::post('/room/join', 'joinRoom')->name('room.join');
    Route::get('/room/{room}', 'room')->name('room.show');
    Route::post('/room/{room}/presence', 'syncPresence')->name('room.presence');
    Route::post('/room/{room}/leave', 'leaveRoom')->name('room.leave');
    Route::post('/room/{room}/strokes', 'storeStroke')->name('room.strokes.store');
    Route::post('/room/{room}/strokes/undo', 'undoStroke')->name('room.strokes.undo');
    Route::post('/room/{room}/clear', 'clearRoom')->name('room.clear');
});
