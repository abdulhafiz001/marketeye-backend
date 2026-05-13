<?php

use App\Services\ExternalDataSeedService;
use App\Services\GamificationService;
use App\Services\PriceSnapshotService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(PriceSnapshotService::class)->runDailyAggregateFromSubmissions();
})->dailyAt('06:00');

Schedule::call(function () {
    app(GamificationService::class)->awardApprovalBonusYesterday();
})->dailyAt('01:00');

Schedule::call(function () {
    app(ExternalDataSeedService::class)->weeklyFallbackForStaleSnapshots();
})->weeklyOn(1, '07:00');
