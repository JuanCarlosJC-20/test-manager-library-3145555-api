<?php

use App\Adapters\Inbound\Http\Controllers\DeleteBookController;
use App\Adapters\Inbound\Http\Controllers\DeleteBookCoverController;
use App\Adapters\Inbound\Http\Controllers\SearchMembersController;
use App\Adapters\Inbound\Http\Controllers\ShowMemberController;
use App\Adapters\Inbound\Http\Controllers\StoreMemberController;
use App\Adapters\Inbound\Http\Controllers\StoreLoanController;
use App\Adapters\Inbound\Http\Controllers\ShowLoanController;
use App\Adapters\Inbound\Http\Controllers\SearchLoansController;
use App\Adapters\Inbound\Http\Controllers\ReturnLoanItemController;
use App\Adapters\Inbound\Http\Controllers\ListOverdueItemsController;
use App\Adapters\Inbound\Http\Controllers\MostBorrowedReportController;
use App\Adapters\Inbound\Http\Controllers\ListCategoriesController;
use App\Adapters\Inbound\Http\Controllers\LoginController;
use App\Adapters\Inbound\Http\Controllers\SearchBooksController;
use App\Adapters\Inbound\Http\Controllers\ShowBookController;
use App\Adapters\Inbound\Http\Controllers\ShowBookCoverController;
use App\Adapters\Inbound\Http\Controllers\StoreBookController;
use App\Adapters\Inbound\Http\Controllers\StoreLibrarianController;
use App\Adapters\Inbound\Http\Controllers\UpdateBookController;
use App\Adapters\Inbound\Http\Controllers\UploadBookCoverController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class);

Route::middleware('api.token')->group(function (): void {
    Route::get('/categories', ListCategoriesController::class);
    Route::get('/books', SearchBooksController::class);
    Route::get('/books/{id}', ShowBookController::class)->whereUuid('id');
    Route::get('/books/{id}/cover', ShowBookCoverController::class)->whereUuid('id');
    Route::middleware('api.role:admin,librarian')->group(function (): void {
        Route::get('/members', SearchMembersController::class);
        Route::get('/members/{id}', ShowMemberController::class)->whereUuid('id');
        Route::post('/members', StoreMemberController::class);
        Route::post('/loans', StoreLoanController::class);
        Route::get('/loans', SearchLoansController::class);
        Route::get('/loans/overdue', ListOverdueItemsController::class);
        Route::get('/loans/{id}', ShowLoanController::class)->whereUuid('id');
        Route::post('/loans/{loan_id}/items/{item_id}/return', ReturnLoanItemController::class)
            ->whereUuid('loan_id')->whereUuid('item_id');
        Route::middleware('api.role:admin')->get('/reports/most-borrowed', MostBorrowedReportController::class);
    });
    Route::middleware('api.role:admin')->post('/users', StoreLibrarianController::class);
    Route::middleware('api.role:admin')->group(function (): void {
        Route::post('/books', StoreBookController::class);
        Route::put('/books/{id}', UpdateBookController::class)->whereUuid('id');
        Route::delete('/books/{id}', DeleteBookController::class)->whereUuid('id');
        Route::put('/books/{id}/cover', UploadBookCoverController::class)->whereUuid('id');
        Route::delete('/books/{id}/cover', DeleteBookCoverController::class)->whereUuid('id');
    });
});
