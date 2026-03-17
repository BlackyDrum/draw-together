<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'home'])->name('home');
Route::post('/room/create', [HomeController::class, 'create_room'])->name('room.create');
