<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('wallet_balance')->default(0)->after('points');
            $table->string('google_id')->nullable()->unique()->after('email');
        });

        Schema::table('price_submissions', function (Blueprint $table) {
            $table->boolean('wallet_rewarded')->default(false)->after('status');
        });

        Schema::create('airtime_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('phone', 20);
            $table->string('status', 20)->default('pending');
            $table->string('admin_note')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'claimed_at']);
        });

        Schema::create('password_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('key_prefix', 16);
            $table->string('key_hash');
            $table->unsignedInteger('daily_limit')->default(1000);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('key_prefix');
        });

        Schema::create('api_key_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('api_keys')->cascadeOnDelete();
            $table->date('usage_date');
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamps();

            $table->unique(['api_key_id', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_usages');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('password_otps');
        Schema::dropIfExists('airtime_claims');

        Schema::table('price_submissions', function (Blueprint $table) {
            $table->dropColumn('wallet_rewarded');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wallet_balance', 'google_id']);
        });
    }
};
