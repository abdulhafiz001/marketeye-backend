<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_otps', function (Blueprint $table) {
            $table->string('purpose', 32)->default('password_reset')->after('email');
            $table->index(['email', 'purpose']);
        });

        // Existing accounts already use the app — do not lock them behind a new code.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update([
                'verified' => true,
                'email_verified_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('password_otps', function (Blueprint $table) {
            $table->dropIndex(['email', 'purpose']);
            $table->dropColumn('purpose');
        });
    }
};
