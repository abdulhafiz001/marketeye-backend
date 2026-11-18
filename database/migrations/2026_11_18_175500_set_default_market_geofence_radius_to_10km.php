<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('markets', 'geofence_radius_m')) {
            Schema::table('markets', function (Blueprint $table) {
                $table->unsignedInteger('geofence_radius_m')->default(10000)->change();
            });

            DB::table('markets')
                ->whereNull('geofence_radius_m')
                ->orWhere('geofence_radius_m', 750)
                ->update(['geofence_radius_m' => 10000]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('markets', 'geofence_radius_m')) {
            Schema::table('markets', function (Blueprint $table) {
                $table->unsignedInteger('geofence_radius_m')->default(750)->change();
            });
        }
    }
};
