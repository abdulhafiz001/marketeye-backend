<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('product_name', 120);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category_name', 80)->nullable();
            $table->string('unit', 64);
            $table->json('market_prices');
            $table->text('reason')->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_suggestions');
    }
};
