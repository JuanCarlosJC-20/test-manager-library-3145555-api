<?php

use App\Adapters\Inbound\Http\Controllers\LoginController;
use App\Adapters\Inbound\Http\Controllers\StoreLibrarianController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class);

Route::middleware('api.token')->group(function (): void {
    Route::middleware('api.role:admin')->post('/users', StoreLibrarianController::class);
});
