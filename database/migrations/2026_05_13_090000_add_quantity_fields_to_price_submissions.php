<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_submissions', function (Blueprint $table) {
            $table->decimal('quantity_value', 10, 3)->default(1)->after('price');
            $table->string('quantity_unit', 64)->nullable()->after('quantity_value');
            $table->decimal('price_per_unit', 10, 2)->nullable()->after('quantity_unit');
        });

        DB::table('price_submissions')->update([
            'quantity_value' => 1,
            'price_per_unit' => DB::raw('price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('price_submissions', function (Blueprint $table) {
            $table->dropColumn(['quantity_value', 'quantity_unit', 'price_per_unit']);
        });
    }
};
