<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            if (! Schema::hasColumn('markets', 'geofence_radius_m')) {
                $table->unsignedInteger('geofence_radius_m')->default(750)->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            if (Schema::hasColumn('markets', 'geofence_radius_m')) {
                $table->dropColumn('geofence_radius_m');
            }
        });
    }
};
