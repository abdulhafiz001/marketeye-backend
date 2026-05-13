<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_price_seeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 32);
            $table->decimal('raw_price', 12, 4)->nullable();
            $table->decimal('normalized_price', 10, 2)->nullable();
            $table->date('effective_date')->nullable();
            $table->timestamps();

            $table->index(['source', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_price_seeds');
    }
};
