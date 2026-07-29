<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('role');
            $table->timestamp('restricted_at')->nullable()->after('is_primary');
        });

        // Existing first admin (lowest id) becomes the main admin and cannot be restricted.
        $firstId = DB::table('admins')->orderBy('id')->value('id');
        if ($firstId) {
            DB::table('admins')->where('id', $firstId)->update(['is_primary' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['is_primary', 'restricted_at']);
        });
    }
};
