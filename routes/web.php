<?php

use App\Http\Controllers\AdminWeb\AdminWebController;
use App\Http\Controllers\Developer\DeveloperPortalController;
use App\Http\Controllers\Site\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'home'])->name('home');
Route::get('/developers', [LandingController::class, 'developers'])->name('developers');

// Developer portal (public register/login — separate from admin & mobile users)
Route::prefix('developer')->group(function (): void {
    Route::get('/', fn () => redirect()->route('developer.login'));
    Route::get('/register', [DeveloperPortalController::class, 'showRegister'])->name('developer.register');
    Route::post('/register', [DeveloperPortalController::class, 'register'])
        ->middleware('throttle:5,60')
        ->name('developer.register.post');
    Route::get('/login', [DeveloperPortalController::class, 'showLogin'])->name('developer.login');
    Route::post('/login', [DeveloperPortalController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('developer.login.post');

    Route::middleware(['web', 'developer'])->group(function (): void {
        Route::get('/dashboard', [DeveloperPortalController::class, 'dashboard'])->name('developer.dashboard');
        Route::post('/keys', [DeveloperPortalController::class, 'storeKey'])
            ->middleware('throttle:10,60')
            ->name('developer.keys.store');
        Route::post('/keys/{id}/revoke', [DeveloperPortalController::class, 'revokeKey'])->name('developer.keys.revoke');
        Route::post('/logout', [DeveloperPortalController::class, 'logout'])->name('developer.logout');
    });
});

// Admin panel (secret URL — not linked from landing)
Route::get('/admin/login', [AdminWebController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminWebController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('admin.login.post');
Route::get('/admin/setup', [AdminWebController::class, 'showSetup'])->name('admin.setup');
Route::post('/admin/setup', [AdminWebController::class, 'setup'])->name('admin.setup.post');

Route::middleware(['web', 'auth:admin', 'web_admin'])->group(function (): void {
    Route::get('/admin', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/products', [AdminWebController::class, 'products'])->name('admin.products.index');
    Route::get('/admin/categories', [AdminWebController::class, 'categories'])->name('admin.categories.index');
    Route::get('/admin/markets', [AdminWebController::class, 'markets'])->name('admin.markets.index');
    Route::get('/admin/users', [AdminWebController::class, 'users'])->name('admin.users.index');
    Route::get('/admin/submissions', [AdminWebController::class, 'submissions'])->name('admin.submissions.index');
    Route::get('/admin/claims', [AdminWebController::class, 'claims'])->name('admin.claims.index');
    Route::get('/admin/prices', [AdminWebController::class, 'prices'])->name('admin.prices.index');
    Route::get('/admin/reports', [AdminWebController::class, 'reports'])->name('admin.reports.index');
    Route::get('/admin/activity', [AdminWebController::class, 'activity'])->name('admin.activity');
    Route::get('/admin/external-data', [AdminWebController::class, 'externalData'])->name('admin.external.index');
    Route::get('/admin/api-keys', [AdminWebController::class, 'apiKeys'])->name('admin.api-keys.index');
    Route::post('/admin/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
    Route::post('/admin/markets', [AdminWebController::class, 'storeMarket'])->name('admin.markets.store');
    Route::post('/admin/markets/{id}', [AdminWebController::class, 'updateMarket'])->name('admin.markets.update');
    Route::post('/admin/products', [AdminWebController::class, 'storeProduct'])->name('admin.products.store');
    Route::post('/admin/products/{id}', [AdminWebController::class, 'updateProduct'])->name('admin.products.update');
    Route::post('/admin/categories', [AdminWebController::class, 'storeCategory'])->name('admin.categories.store');
    Route::post('/admin/categories/{id}', [AdminWebController::class, 'updateCategory'])->name('admin.categories.update');
    Route::post('/admin/categories/{id}/delete', [AdminWebController::class, 'destroyCategory'])->name('admin.categories.destroy');
    Route::post('/admin/users/{id}', [AdminWebController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/admin/submissions/{id}/approve', [AdminWebController::class, 'approveSubmission'])->name('admin.submissions.approve');
    Route::post('/admin/submissions/{id}/reject', [AdminWebController::class, 'rejectSubmission'])->name('admin.submissions.reject');
    Route::post('/admin/claims/{id}/paid', [AdminWebController::class, 'markClaimPaid'])->name('admin.claims.paid');
    Route::post('/admin/claims/{id}/reject', [AdminWebController::class, 'rejectClaim'])->name('admin.claims.reject');
    Route::post('/admin/prices', [AdminWebController::class, 'storePrice'])->name('admin.prices.store');
    Route::post('/admin/external-data/pull', [AdminWebController::class, 'pullExternalData'])->name('admin.external.pull');
    Route::post('/admin/external-data/{id}/approve', [AdminWebController::class, 'approveExternalSeed'])->name('admin.external.approve');
    Route::post('/admin/api-keys/{id}', [AdminWebController::class, 'updateApiKey'])->name('admin.api-keys.update');
    Route::post('/admin/api-keys/{id}/revoke', [AdminWebController::class, 'revokeApiKey'])->name('admin.api-keys.revoke');
});
