<?php

use App\Http\Controllers\AdminWeb\AdminWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', [AdminWebController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminWebController::class, 'login'])->name('admin.login.post');
Route::get('/admin/setup', [AdminWebController::class, 'showSetup'])->name('admin.setup');
Route::post('/admin/setup', [AdminWebController::class, 'setup'])->name('admin.setup.post');

Route::middleware(['web', 'auth', 'web_admin'])->group(function (): void {
    Route::get('/admin', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/products', [AdminWebController::class, 'products'])->name('admin.products.index');
    Route::get('/admin/markets', [AdminWebController::class, 'markets'])->name('admin.markets.index');
    Route::get('/admin/users', [AdminWebController::class, 'users'])->name('admin.users.index');
    Route::get('/admin/submissions', [AdminWebController::class, 'submissions'])->name('admin.submissions.index');
    Route::get('/admin/prices', [AdminWebController::class, 'prices'])->name('admin.prices.index');
    Route::get('/admin/reports', [AdminWebController::class, 'reports'])->name('admin.reports.index');
    Route::get('/admin/activity', [AdminWebController::class, 'activity'])->name('admin.activity');
    Route::get('/admin/external-data', [AdminWebController::class, 'externalData'])->name('admin.external.index');
    Route::post('/admin/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
    Route::post('/admin/markets', [AdminWebController::class, 'storeMarket'])->name('admin.markets.store');
    Route::post('/admin/markets/{id}', [AdminWebController::class, 'updateMarket'])->name('admin.markets.update');
    Route::post('/admin/products', [AdminWebController::class, 'storeProduct'])->name('admin.products.store');
    Route::post('/admin/products/{id}', [AdminWebController::class, 'updateProduct'])->name('admin.products.update');
    Route::post('/admin/users/{id}', [AdminWebController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/admin/submissions/{id}/approve', [AdminWebController::class, 'approveSubmission'])->name('admin.submissions.approve');
    Route::post('/admin/submissions/{id}/reject', [AdminWebController::class, 'rejectSubmission'])->name('admin.submissions.reject');
    Route::post('/admin/prices', [AdminWebController::class, 'storePrice'])->name('admin.prices.store');
    Route::post('/admin/external-data/pull', [AdminWebController::class, 'pullExternalData'])->name('admin.external.pull');
    Route::post('/admin/external-data/{id}/approve', [AdminWebController::class, 'approveExternalSeed'])->name('admin.external.approve');
});
