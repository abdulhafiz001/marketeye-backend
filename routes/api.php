<?php

use App\Http\Controllers\Api\V1\Admin\ActivityLogController;
use App\Http\Controllers\Api\V1\Admin\CategoryManageController;
use App\Http\Controllers\Api\V1\Admin\ClaimManageController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\ExternalSeedController;
use App\Http\Controllers\Api\V1\Admin\ManualPriceController;
use App\Http\Controllers\Api\V1\Admin\MarketManageController;
use App\Http\Controllers\Api\V1\Admin\ProductManageController;
use App\Http\Controllers\Api\V1\Admin\SubmissionManageController;
use App\Http\Controllers\Api\V1\Admin\UserManageController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BasketOptimizerController;
use App\Http\Controllers\Api\V1\BatchPriceSubmitController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardSummaryController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\GoogleSocialiteController;
use App\Http\Controllers\Api\V1\InsightsController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\MarketController;
use App\Http\Controllers\Api\V1\MarketPriceController;
use App\Http\Controllers\Api\V1\PriceAlertController;
use App\Http\Controllers\Api\V1\PriceCompareController;
use App\Http\Controllers\Api\V1\PriceSubmitController;
use App\Http\Controllers\Api\V1\PriceTrendingController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PublicApi\PublicMarketDataController;
use App\Http\Controllers\Api\V1\UserMarketWatchController;
use App\Http\Controllers\Api\V1\UserSubmissionController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/markets', [MarketController::class, 'index']);
    Route::get('/markets/{id}/prices', [MarketPriceController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/dashboard/summary', DashboardSummaryController::class);
    Route::get('/prices/compare', PriceCompareController::class);
    Route::post('/prices/basket-compare', BasketOptimizerController::class);
    Route::get('/prices/trending', PriceTrendingController::class);
    Route::get('/insights', InsightsController::class);

    Route::prefix('public')->middleware('public_api_key')->group(function (): void {
        Route::get('/markets', [PublicMarketDataController::class, 'markets']);
        Route::get('/categories', [PublicMarketDataController::class, 'categories']);
        Route::get('/products', [PublicMarketDataController::class, 'products']);
        Route::get('/markets/{id}/prices', [PublicMarketDataController::class, 'marketPrices']);
    });

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/google', [AuthController::class, 'google']);
        Route::get('/google/redirect', [GoogleSocialiteController::class, 'redirect']);
        Route::get('/google/callback', [GoogleSocialiteController::class, 'callback']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode'])->middleware('throttle:10,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

        Route::middleware(['auth:sanctum', 'not_banned'])->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::middleware(['auth:sanctum', 'not_banned'])->group(function (): void {
        Route::post('/prices/submit', [PriceSubmitController::class, 'store']);
        Route::post('/submissions/batch', [BatchPriceSubmitController::class, 'store']);
        Route::get('/user/submissions', UserSubmissionController::class);
        Route::get('/user/leaderboard', LeaderboardController::class);
        Route::get('/user/market-watches', [UserMarketWatchController::class, 'index']);
        Route::post('/user/market-watches', [UserMarketWatchController::class, 'store']);
        Route::delete('/user/market-watches/{productId}/{marketId}', [UserMarketWatchController::class, 'destroy']);
        Route::get('/user/price-alerts', [PriceAlertController::class, 'index']);
        Route::post('/user/price-alerts', [PriceAlertController::class, 'store']);
        Route::put('/user/price-alerts/{id}', [PriceAlertController::class, 'update']);
        Route::delete('/user/price-alerts/{id}', [PriceAlertController::class, 'destroy']);
        Route::post('/user/device-token', [DeviceTokenController::class, 'store']);
        Route::delete('/user/device-token', [DeviceTokenController::class, 'destroy']);
        Route::get('/wallet', [WalletController::class, 'show']);
        Route::post('/wallet/claim', [WalletController::class, 'claim']);
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'not_banned', 'admin_role'])->group(function (): void {
        Route::get('/dashboard/stats', AdminDashboardController::class);

        Route::get('/submissions', [SubmissionManageController::class, 'index']);
        Route::post('/submissions/{id}/approve', [SubmissionManageController::class, 'approve']);
        Route::post('/submissions/{id}/reject', [SubmissionManageController::class, 'reject']);
        Route::post('/submissions/bulk-approve', [SubmissionManageController::class, 'bulkApprove']);

        Route::get('/claims', [ClaimManageController::class, 'index']);
        Route::post('/claims/{id}/paid', [ClaimManageController::class, 'markPaid']);
        Route::post('/claims/{id}/reject', [ClaimManageController::class, 'reject']);

        Route::get('/markets', [MarketManageController::class, 'index']);
        Route::post('/markets', [MarketManageController::class, 'store']);
        Route::put('/markets/{id}', [MarketManageController::class, 'update']);
        Route::delete('/markets/{id}', [MarketManageController::class, 'destroy']);

        Route::get('/products', [ProductManageController::class, 'index']);
        Route::post('/products', [ProductManageController::class, 'store']);
        Route::put('/products/{id}', [ProductManageController::class, 'update']);
        Route::delete('/products/{id}', [ProductManageController::class, 'destroy']);

        Route::get('/categories', [CategoryManageController::class, 'index']);
        Route::post('/categories', [CategoryManageController::class, 'store']);
        Route::put('/categories/{id}', [CategoryManageController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryManageController::class, 'destroy']);

        Route::get('/users', [UserManageController::class, 'index']);
        Route::put('/users/{id}/role', [UserManageController::class, 'updateRole']);
        Route::put('/users/{id}/ban', [UserManageController::class, 'ban']);
        Route::put('/users/{id}/unban', [UserManageController::class, 'unban']);
        Route::get('/users/{id}/submissions', [UserManageController::class, 'submissions']);

        Route::post('/prices/manual-entry', [ManualPriceController::class, 'store']);
        Route::post('/prices/seed-external', [ExternalSeedController::class, 'run']);

        Route::get('/activity-log', ActivityLogController::class);
    });
});
