<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('role', 32)->default('user')->after('avatar');
            $table->unsignedInteger('points')->default(0)->after('role');
            $table->boolean('verified')->default(false)->after('points');
            $table->timestamp('banned_at')->nullable()->after('verified');
            $table->unsignedSmallInteger('submission_streak')->default(0)->after('banned_at');
            $table->date('last_submission_date')->nullable()->after('submission_streak');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'avatar',
                'role',
                'points',
                'verified',
                'banned_at',
                'submission_streak',
                'last_submission_date',
            ]);
        });
    }
};
