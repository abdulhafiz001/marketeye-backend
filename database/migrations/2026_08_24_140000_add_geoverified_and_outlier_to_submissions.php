<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_submissions', function (Blueprint $table) {
            $table->boolean('is_geoverified')->default(false)->after('quantity_unit');
            $table->decimal('latitude', 10, 7)->nullable()->after('is_geoverified');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->boolean('is_outlier')->default(false)->after('rejection_reason');
            $table->text('outlier_reason')->nullable()->after('is_outlier');
        });
    }

    public function down(): void
    {
        Schema::table('price_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'is_geoverified',
                'latitude',
                'longitude',
                'is_outlier',
                'outlier_reason',
            ]);
        });
    }
};
