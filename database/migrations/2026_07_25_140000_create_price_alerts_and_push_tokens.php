<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('expo_push_token', 255)->nullable()->after('phone');
        });

        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->decimal('target_price', 12, 2);
            $table->string('condition', 16); // ABOVE | BELOW
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->decimal('last_known_price', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'market_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_alerts');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('expo_push_token');
        });
    }
};
