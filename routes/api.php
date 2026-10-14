<?php

use App\Adapters\Inbound\Http\Controllers\DeleteBookController;
use App\Adapters\Inbound\Http\Controllers\ListCategoriesController;
use App\Adapters\Inbound\Http\Controllers\LoginController;
use App\Adapters\Inbound\Http\Controllers\SearchBooksController;
use App\Adapters\Inbound\Http\Controllers\ShowBookController;
use App\Adapters\Inbound\Http\Controllers\StoreBookController;
use App\Adapters\Inbound\Http\Controllers\StoreLibrarianController;
use App\Adapters\Inbound\Http\Controllers\UpdateBookController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class);

Route::middleware('api.token')->group(function (): void {
    Route::get('/categories', ListCategoriesController::class);
    Route::get('/books', SearchBooksController::class);
    Route::get('/books/{id}', ShowBookController::class)->whereUuid('id');
    Route::middleware('api.role:admin')->post('/users', StoreLibrarianController::class);
    Route::middleware('api.role:admin')->group(function (): void {
        Route::post('/books', StoreBookController::class);
        Route::put('/books/{id}', UpdateBookController::class)->whereUuid('id');
        Route::delete('/books/{id}', DeleteBookController::class)->whereUuid('id');
    });
});
