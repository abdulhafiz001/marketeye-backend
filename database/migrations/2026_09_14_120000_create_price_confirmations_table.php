<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->string('action', 16); // CONFIRM | DISPUTE
            $table->decimal('reported_price', 12, 2)->nullable();
            $table->string('notes', 255)->nullable();
            $table->boolean('is_geoverified')->default(false);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id', 'market_id']);
            $table->index(['product_id', 'market_id', 'action']);
            $table->index(['market_id', 'created_at']);
        });

        // Add confidence score and community validation fields to price_snapshots
        Schema::table('price_snapshots', function (Blueprint $table) {
            $table->unsignedSmallInteger('confidence_score')->default(50)->after('low_confidence');
            $table->unsignedInteger('confirmations_count')->default(0)->after('confidence_score');
            $table->unsignedInteger('disputes_count')->default(0)->after('confirmations_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_confirmations');

        Schema::table('price_snapshots', function (Blueprint $table) {
            $table->dropColumn(['confidence_score', 'confirmations_count', 'disputes_count']);
        });
    }
};
